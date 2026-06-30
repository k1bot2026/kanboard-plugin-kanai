<?php

namespace Kanboard\Plugin\KanAI\Controller;

use Kanboard\Controller\BaseController;

class ProjectSettingsController extends BaseController
{
    public function show(): void
    {
        $project = $this->getProject();
        $this->response->html($this->helper->layout->project('KanAI:project/settings', [
            'project' => $project,
            'enabled' => $this->settingsModel->getProjectEnabled($project['id']),
            'external_opt_in' => $this->settingsModel->getProjectExternalOptIn($project['id']),
            'external_globally_enabled' => $this->settingsModel->isExternalEnabled(),
            'title' => t('KanAI Settings'),
        ]));
    }

    public function save(): void
    {
        $project = $this->getProject();
        // getValues() auto-validates the POST CSRF token (returns [] if invalid).
        $values = $this->request->getValues();
        $this->settingsModel->saveProject(
            (int) $project['id'],
            ! empty($values['kanai_enabled']),
            ! empty($values['kanai_external_opt_in'])
        );
        $this->flash->success(t('Settings saved.'));
        $this->response->redirect($this->helper->url->to('ProjectSettingsController', 'show', ['project_id' => $project['id'], 'plugin' => 'KanAI']));
    }
}
