<?php

namespace Kanboard\Plugin\KanAI\Controller;

use Kanboard\Controller\BaseController;

class ActionController extends BaseController
{
    public function apply(): void
    {
        $project = $this->getProject();
        $userId = (int) $this->userSession->getId();
        // getValues() auto-validates the POST CSRF token (returns [] if invalid).
        $values = $this->request->getValues();
        $setId = (int) (isset($values['proposal_set_id']) ? $values['proposal_set_id'] : 0);
        $approvedIndexes = array_map('intval', (array) (isset($values['approve']) ? $values['approve'] : []));

        $set = $this->conversationModel->getProposalSet($setId);
        if ($set && (int) $set['project_id'] === (int) $project['id']) {
            $applied = 0;
            foreach ($set['actions'] as $i => $action) {
                if (in_array($i, $approvedIndexes, true)) {
                    try {
                        $this->actionApplierModel->apply((int) $project['id'], $userId, $action);
                        $applied++;
                    } catch (\Throwable $e) {
                        $this->flash->failure(t('Action failed: %s', $e->getMessage()));
                    }
                }
            }
            $this->conversationModel->setProposalStatus($setId, 'applied');
            $this->flash->success(t('%d action(s) applied.', $applied));
        }
        $this->response->redirect($this->helper->url->to('AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI']));
    }

    public function reject(): void
    {
        $project = $this->getProject();
        $this->checkCSRFParam();
        $setId = (int) $this->request->getIntegerParam('proposal_set_id');
        $set = $this->conversationModel->getProposalSet($setId);
        if ($set && (int) $set['project_id'] === (int) $project['id']) {
            $this->conversationModel->setProposalStatus($setId, 'rejected');
        }
        $this->flash->success(t('Proposals rejected.'));
        $this->response->redirect($this->helper->url->to('AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI']));
    }
}
