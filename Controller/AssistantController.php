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
            $this->flash->failure(t('Please enter a question.'));
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

        // Release the session lock so the rest of Kanboard stays responsive while
        // the (possibly slow) model answers.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            $this->assistantService->ask((int) $project['id'], $userId, $conversationId, $question, $provider);
        } catch (\Throwable $e) {
            $this->conversationModel->addMessage($conversationId, (int) $project['id'], $userId, 'user', $question);
            $this->conversationModel->addMessage($conversationId, (int) $project['id'], $userId, 'assistant', 'KanAI error: ' . $e->getMessage());
        }
        $this->response->redirect($this->indexUrl($project, $conversationId));
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
