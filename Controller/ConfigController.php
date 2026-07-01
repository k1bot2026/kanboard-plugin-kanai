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

    /**
     * AJAX connection test for a provider. Uses the value typed in the form
     * when present (so the admin can test before saving), else the saved one.
     */
    public function test(): void
    {
        $values = $this->request->getValues(); // CSRF-validated
        $provider = isset($values['provider']) ? $values['provider'] : '';
        $settings = $this->settingsModel->getGlobal();

        try {
            switch ($provider) {
                case 'local':
                    $base = rtrim(trim($values['base_url'] ?? '') !== '' ? trim($values['base_url']) : $settings['kanai_local_base_url'], '/');
                    $json = $this->httpJson('GET', $base . '/models');
                    $models = [];
                    foreach (($json['data'] ?? []) as $m) {
                        if (! empty($m['id'])) {
                            $models[] = (string) $m['id'];
                        }
                    }
                    sort($models);
                    $this->response->json([
                        'ok' => true,
                        'detail' => t('%d model(s) available', count($models)),
                        'models' => $models,
                    ]);
                    return;

                case 'openai':
                    $key = trim($values['api_key'] ?? '') !== '' ? trim($values['api_key']) : $settings['kanai_openai_key'];
                    if ($key === '') {
                        $this->response->json(['ok' => false, 'detail' => t('No API key configured')]);
                        return;
                    }
                    $json = $this->httpJson('GET', 'https://api.openai.com/v1/models', null, ['Authorization: Bearer ' . $key]);
                    if (isset($json['error'])) {
                        $this->response->json(['ok' => false, 'detail' => (string) ($json['error']['message'] ?? 'error')]);
                        return;
                    }
                    $this->response->json(['ok' => true, 'detail' => t('API key accepted')]);
                    return;

                case 'anthropic':
                    $key = trim($values['api_key'] ?? '') !== '' ? trim($values['api_key']) : $settings['kanai_anthropic_key'];
                    if ($key === '') {
                        $this->response->json(['ok' => false, 'detail' => t('No API key configured')]);
                        return;
                    }
                    $json = $this->httpJson('POST', 'https://api.anthropic.com/v1/messages', [
                        'model' => $settings['kanai_anthropic_model'],
                        'max_tokens' => 1,
                        'messages' => [['role' => 'user', 'content' => 'ping']],
                    ], ['x-api-key: ' . $key, 'anthropic-version: 2023-06-01']);
                    if (($json['type'] ?? '') === 'error' || isset($json['error'])) {
                        $this->response->json(['ok' => false, 'detail' => (string) ($json['error']['message'] ?? 'error')]);
                        return;
                    }
                    $this->response->json(['ok' => true, 'detail' => t('API key accepted (model: %s)', $settings['kanai_anthropic_model'])]);
                    return;
            }
            $this->response->json(['ok' => false, 'detail' => 'unknown provider'], 400);
        } catch (\Throwable $e) {
            $this->response->json(['ok' => false, 'detail' => $e->getMessage()]);
        }
    }

    /** Small JSON-over-HTTP helper with a short timeout for connection tests. */
    private function httpJson(string $method, string $url, ?array $body = null, array $headers = []): array
    {
        $headers[] = 'Content-Type: application/json';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $raw === '') {
            throw new \RuntimeException($err !== '' ? $err : t('No response from endpoint'));
        }
        $json = json_decode($raw, true);
        if (! is_array($json)) {
            throw new \RuntimeException(t('Endpoint did not return JSON'));
        }
        return $json;
    }
}
