<?php

namespace Kanboard\Plugin\KanAI\Controller;

use Kanboard\Controller\BaseController;

class ConfigController extends BaseController
{
    public function show(array $values = [], array $errors = []): void
    {
        $settings = $this->settingsModel->getGlobal();
        $crypto = $this->settingsModel->crypto();
        $this->response->html($this->helper->layout->config('KanAI:config/settings', [
            'values' => empty($values) ? $settings : $values,
            'openai_key_mask' => $crypto->mask($settings['kanai_openai_key']),
            'anthropic_key_mask' => $crypto->mask($settings['kanai_anthropic_key']),
            'title' => t('KanAI Settings'),
        ]));
    }

    public function save(): void
    {
        // getValues() auto-validates the POST CSRF token (returns [] if invalid).
        $values = $this->request->getValues();
        $this->settingsModel->saveGlobal($values);
        $this->flash->success(t('Settings saved.'));
        $this->response->redirect($this->helper->url->to('ConfigController', 'show', ['plugin' => 'KanAI']));
    }
}
