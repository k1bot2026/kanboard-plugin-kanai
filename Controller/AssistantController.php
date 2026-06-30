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
        $question = trim($this->request->getStringParam('question'));
        $provider = $this->request->getStringParam('provider');

        $skill = $this->request->getStringParam('skill');
        if ($skill !== '') {
            $instruction = \Kanboard\Plugin\KanAI\Model\AssistantSkills::instructionFor($skill);
            if ($instruction !== null) {
                $question = $instruction;
            }
        }

        if ($question === '') {
            $this->flash->failure(t('Please enter a question.'));
        } else {
            try {
                $this->assistantService->ask((int) $project['id'], (int) $userId, $question, $provider);
            } catch (\Throwable $e) {
                $this->flash->failure(t('KanAI error: %s', $e->getMessage()));
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
