<?php

namespace Kanboard\Plugin\KanAI\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\KanAI\Model\ConversationModel;
use Kanboard\Plugin\KanAI\Model\ConversationTitle;

class AssistantController extends BaseController
{
    public function index(): void
    {
        $project = $this->getProject();
        $enabled = $this->settingsModel->getProjectEnabled($project['id']);
        $conversations = $this->conversationModel->listConversations((int) $project['id']);

        // Active conversation: explicit ?conversation_id, else the most recent,
        // unless ?new=1 forces a blank "new chat" state.
        $activeId = (int) $this->request->getIntegerParam('conversation_id');
        if ($activeId > 0) {
            $conv = $this->conversationModel->getConversation($activeId);
            if (! $conv || (int) $conv['project_id'] !== (int) $project['id']) {
                $activeId = 0;
            }
        }
        $forceNew = $this->request->getIntegerParam('new') === 1;
        if ($activeId === 0 && ! $forceNew && ! empty($conversations)) {
            $activeId = (int) $conversations[0]['id'];
        }

        $this->response->html($this->helper->layout->project('KanAI:assistant/panel', [
            'project' => $project,
            'enabled' => $enabled,
            'conversations' => $conversations,
            'active_id' => $activeId,
            'messages' => $activeId > 0 ? $this->conversationModel->getMessages($activeId, 200) : [],
            'proposals' => $activeId > 0 ? $this->conversationModel->getPendingProposals($activeId) : [],
            'title' => t('KanAI Assistant'),
        ]));
    }

    public function ask(): void
    {
        $project = $this->getProject();
        $userId = (int) $this->userSession->getId();
        // getValues() returns the POST data and auto-validates the CSRF token.
        $values = $this->request->getValues();
        $question = isset($values['question']) ? trim($values['question']) : '';
        $provider = isset($values['provider']) ? $values['provider'] : '';
        $conversationId = (int) (isset($values['conversation_id']) ? $values['conversation_id'] : 0);

        $skill = isset($values['skill']) ? $values['skill'] : '';
        if ($skill !== '') {
            $instruction = \Kanboard\Plugin\KanAI\Model\AssistantSkills::instructionFor($skill);
            if ($instruction !== null) {
                $question = $instruction;
            }
        }

        if ($question === '') {
            if ($this->request->isAjax()) {
                $this->response->json(['error' => t('Please enter a question.')], 400);
                return;
            }
            $this->flash->failure(t('Please enter a question.'));
            $this->response->redirect($this->indexUrl($project, $conversationId));
            return;
        }

        // Rate limit: cap questions per user per hour (0 = unlimited). Pending
        // jobs count too — their user messages aren't written until the worker
        // runs, so counting messages alone would let rapid enqueues slip past.
        $limit = (int) ($this->settingsModel->getGlobal()['kanai_rate_limit_per_hour'] ?? 0);
        $used = $limit > 0
            ? $this->conversationModel->countRecentUserMessages($userId, time() - 3600)
                + $this->conversationModel->countActiveUserJobs($userId, $this->jobStaleAfter())
            : 0;
        if ($limit > 0 && $used >= $limit) {
            $message = t('Rate limit reached (%d questions per hour). Try again later.', $limit);
            if ($this->request->isAjax()) {
                $this->response->json(['error' => $message], 429);
                return;
            }
            $this->flash->failure($message);
            $this->response->redirect($this->indexUrl($project, $conversationId));
            return;
        }

        // Resolve the conversation; create a fresh one when none is active.
        if ($conversationId > 0) {
            $conv = $this->conversationModel->getConversation($conversationId);
            if (! $conv || (int) $conv['project_id'] !== (int) $project['id']) {
                $conversationId = 0;
            }
        }
        if ($conversationId === 0) {
            $conversationId = $this->conversationModel->createConversation(
                (int) $project['id'],
                $userId,
                ConversationTitle::from($question)
            );
        }

        $settings = $this->settingsModel->getGlobal();
        $afterId = $this->conversationModel->getLastMessageId($conversationId);
        $jobId = $this->conversationModel->createJob($conversationId, (int) $project['id'], $userId, $question, $provider);
        $isAjax = $this->request->isAjax();

        // Async: hand the slow LLM call to a detached background worker and let
        // the browser poll for the result, so the web request never blocks (no
        // 504, works with any model size). Falls back to inline processing when
        // async is disabled or a worker can't be spawned.
        if ($isAjax && ($settings['kanai_async'] ?? '1') === '1' && $this->spawnWorker($jobId, $settings['kanai_php_binary'] ?? 'php')) {
            $conv = $this->conversationModel->getConversation($conversationId);
            $this->response->json([
                'conversation_id' => $conversationId,
                'conversation_title' => $conv ? $conv['title'] : '',
                'pending' => true,
                'after_id' => $afterId,
            ]);
            return;
        }

        // Inline fallback: release the session lock first so the rest of Kanboard
        // stays responsive while the model answers.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $this->assistantService->runJob($jobId);

        if ($isAjax) {
            $conv = $this->conversationModel->getConversation($conversationId);
            $this->response->json([
                'conversation_id' => $conversationId,
                'conversation_title' => $conv ? $conv['title'] : '',
                'messages_html' => $this->template->render('KanAI:assistant/messages', [
                    'messages' => $this->conversationModel->getMessagesAfter($conversationId, $afterId),
                    'project' => $project,
                ]),
                'proposals_html' => $this->template->render('KanAI:assistant/proposals', [
                    'project' => $project,
                    'proposals' => $this->conversationModel->getPendingProposals($conversationId),
                ]),
            ]);
            return;
        }
        $this->response->redirect($this->indexUrl($project, $conversationId));
    }

    /**
     * Poll for the result of an in-flight question. Read-only (GET), so no CSRF.
     */
    public function poll(): void
    {
        $project = $this->getProject();
        $convId = (int) $this->request->getIntegerParam('conversation_id');
        $afterId = (int) $this->request->getIntegerParam('after');

        $conv = $this->conversationModel->getConversation($convId);
        if (! $conv || (int) $conv['project_id'] !== (int) $project['id']) {
            $this->response->json(['running' => false, 'messages_html' => '', 'proposals_html' => '']);
            return;
        }
        $newMessages = $this->conversationModel->getMessagesAfter($convId, $afterId);
        $this->response->json([
            'running' => $this->conversationModel->getActiveJob($convId, $this->jobStaleAfter()) !== null,
            'messages_html' => $newMessages ? $this->template->render('KanAI:assistant/messages', ['messages' => $newMessages, 'project' => $project]) : '',
            'proposals_html' => $this->template->render('KanAI:assistant/proposals', [
                'project' => $project,
                'proposals' => $this->conversationModel->getPendingProposals($convId),
            ]),
        ]);
    }

    /**
     * Seconds after which a queued/running job is considered dead. Twice the
     * configured LLM timeout (a run may include one repair retry) plus slack,
     * with a 15-minute floor.
     */
    private function jobStaleAfter(): int
    {
        $timeout = (int) ($this->settingsModel->getGlobal()['kanai_request_timeout'] ?? 120);
        return max(900, 2 * $timeout + 60);
    }

    /** Spawn a detached CLI worker to process the job. Returns false if it can't. */
    private function spawnWorker(int $jobId, string $php): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }
        $cli = dirname(__DIR__, 3) . '/cli'; // <kanboard>/plugins/KanAI/Controller -> <kanboard>/cli
        if (! is_file($cli)) {
            return false;
        }
        $cmd = 'nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($cli) . ' kanai:process-job ' . (int) $jobId . ' > /dev/null 2>&1 &';
        $proc = @proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (! is_resource($proc)) {
            return false;
        }
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                @fclose($pipe);
            }
        }
        @proc_close($proc);
        return true;
    }

    public function deleteConversation(): void
    {
        $project = $this->getProject();
        $this->checkCSRFParam();
        $convId = (int) $this->request->getIntegerParam('conversation_id');
        $conv = $this->conversationModel->getConversation($convId);
        if ($conv && (int) $conv['project_id'] === (int) $project['id']) {
            $this->conversationModel->deleteConversation($convId);
            $this->flash->success(t('Conversation deleted.'));
        }
        $this->response->redirect($this->helper->url->to('AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI']));
    }

    public function renameConversation(): void
    {
        $project = $this->getProject();
        // getValues() returns the POST data and auto-validates the CSRF token.
        $values = $this->request->getValues();
        $convId = (int) (isset($values['conversation_id']) ? $values['conversation_id'] : 0);
        $title = isset($values['title']) ? trim($values['title']) : '';
        $conv = $this->conversationModel->getConversation($convId);
        if ($conv && (int) $conv['project_id'] === (int) $project['id'] && $title !== '') {
            $this->conversationModel->renameConversation($convId, ConversationTitle::from($title));
            $this->flash->success(t('Conversation renamed.'));
        }
        $this->response->redirect($this->indexUrl($project, $convId));
    }

    private function indexUrl(array $project, int $conversationId): string
    {
        $params = ['project_id' => $project['id'], 'plugin' => 'KanAI'];
        if ($conversationId > 0) {
            $params['conversation_id'] = $conversationId;
        }
        return $this->helper->url->to('AssistantController', 'index', $params);
    }
}
