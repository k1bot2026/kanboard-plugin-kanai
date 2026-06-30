<?php

namespace Kanboard\Plugin\KanAI\Controller;

use Kanboard\Controller\BaseController;

class AssistantController extends BaseController
{
    public function index(): void
    {
        $project = $this->getProject();
        $userId = $this->userSession->getId();
        $this->response->html($this->helper->layout->project('KanAI:assistant/panel', [
            'project' => $project,
            'enabled' => $this->settingsModel->getProjectEnabled($project['id']),
            'messages' => $this->conversationModel->getMessages($project['id'], $userId, 20),
            'proposals' => $this->conversationModel->getPendingProposals($project['id']),
            'title' => t('KanAI Assistant'),
        ]));
    }

    public function ask(): void
    {
        $project = $this->getProject();
        $userId = $this->userSession->getId();
        // getValues() returns the POST data and auto-validates the CSRF token
        // (returns [] on an invalid/absent token), so no separate checkCSRFParam.
        $values = $this->request->getValues();
        $question = isset($values['question']) ? trim($values['question']) : '';
        $provider = isset($values['provider']) ? $values['provider'] : '';

        $skill = isset($values['skill']) ? $values['skill'] : '';
        if ($skill !== '') {
            $instruction = \Kanboard\Plugin\KanAI\Model\AssistantSkills::instructionFor($skill);
            if ($instruction !== null) {
                $question = $instruction;
            }
        }

        if ($question === '') {
            $this->flash->failure(t('Please enter a question.'));
        } else {
            // Release the PHP session lock before the (possibly slow) LLM call so the
            // rest of Kanboard stays responsive while KanAI is thinking — otherwise
            // every other request from this user blocks until the model answers.
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            try {
                $this->assistantService->ask((int) $project['id'], (int) $userId, $question, $provider);
            } catch (\Throwable $e) {
                // The session is closed now, so surface the error in the thread (DB)
                // rather than via a flash message that would not persist.
                $this->conversationModel->addMessage((int) $project['id'], (int) $userId, 'user', $question);
                $this->conversationModel->addMessage((int) $project['id'], (int) $userId, 'assistant', 'KanAI error: ' . $e->getMessage());
            }
        }
        $this->response->redirect($this->helper->url->to('AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI']));
    }

    public function clear(): void
    {
        $project = $this->getProject();
        $this->checkCSRFParam();
        $this->conversationModel->clearProject((int) $project['id']);
        $this->flash->success(t('Conversation cleared.'));
        $this->response->redirect($this->helper->url->to('AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI']));
    }
}
