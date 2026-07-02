<?php

namespace Kanboard\Plugin\KanAI\Model;

use Kanboard\Core\Base;
use Kanboard\Plugin\KanAI\LLM\ProposalValidator;
use RuntimeException;

class AssistantService extends Base
{
    /**
     * Process a queued job (called by the background worker or the inline
     * fallback). Claims the job, runs the LLM turn, and records the outcome —
     * so the web request never has to wait for the model.
     */
    public function runJob(int $jobId): void
    {
        $job = $this->conversationModel->getJob($jobId);
        if ($job === null || ! $this->conversationModel->claimJob($jobId)) {
            return; // gone or already taken by another worker
        }
        try {
            $this->ask((int) $job['project_id'], (int) $job['user_id'], (int) $job['conversation_id'], $job['question'], $job['provider']);
            $this->conversationModel->finishJob($jobId, 'done');
        } catch (\Throwable $e) {
            $this->conversationModel->addMessage((int) $job['conversation_id'], (int) $job['project_id'], (int) $job['user_id'], 'user', $job['question']);
            $this->conversationModel->addMessage((int) $job['conversation_id'], (int) $job['project_id'], (int) $job['user_id'], 'assistant', 'KanAI error: ' . $e->getMessage());
            $this->conversationModel->finishJob($jobId, 'error', $e->getMessage());
        }
        $this->conversationModel->purgeFinishedJobs(time() - 3600);
    }

    public function ask(int $projectId, int $userId, int $conversationId, string $question, string $provider = ''): array
    {
        $settings = $this->settingsModel->getGlobal();
        $budget = (int) $settings['kanai_max_context_tokens'];
        $maxOut = (int) $settings['kanai_max_output_tokens'];

        $contextBuilder = new ContextBuilderModel(
            $this->taskFinderModel,
            $this->commentModel,
            $this->subtaskModel,
            $this->projectModel
        );
        $ctx = $contextBuilder->build($projectId, $question, $budget);
        $client = $this->llmClientFactory->forProject($projectId, $provider);
        $llmInfo = $this->llmClientFactory->infoForProject($projectId, $provider);
        $startedAt = microtime(true);

        // Recent history of THIS conversation (last 10 turns) for multi-turn context.
        $messages = [];
        $history = $this->conversationModel->getMessages($conversationId, 100);
        foreach (array_slice($history, -10) as $m) {
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $ctx['context'] . "\n\nQUESTION: " . $question];

        $raw = $client->complete($ctx['system'], $messages, ['max_tokens' => $maxOut, 'json' => true]);
        try {
            $parsed = ProposalValidator::parse($raw);
        } catch (RuntimeException $e) {
            // One repair retry: ask the model to re-emit strict JSON only.
            $repair = $client->complete(
                $ctx['system'],
                [['role' => 'user', 'content' => "Re-output your previous reply as a single valid JSON object only, no prose:\n" . $raw]],
                ['max_tokens' => $maxOut]
            );
            $parsed = ProposalValidator::parse($repair);
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $this->conversationModel->addMessage($conversationId, $projectId, $userId, 'user', $question);
        $assistantMsgId = $this->conversationModel->addMessage(
            $conversationId, $projectId, $userId, 'assistant', $parsed['answer'],
            $llmInfo['model'], $durationMs
        );

        $proposalSetId = null;
        if (! empty($parsed['proposals'])) {
            $proposalSetId = $this->conversationModel->addProposalSet($conversationId, $projectId, $userId, $assistantMsgId, $parsed['proposals']);
        }

        $this->conversationModel->purgeOlderThan((int) $settings['kanai_history_retention_days'], time());

        return [
            'answer' => $parsed['answer'],
            'proposal_set_id' => $proposalSetId,
            'proposals' => $parsed['proposals'],
        ];
    }
}
