<?php

namespace Kanboard\Plugin\KanAI\LLM;

use Kanboard\Core\Base;
use Kanboard\Plugin\KanAI\Settings\GatingPolicy;

class LLMClientFactory extends Base
{
    public function forProject(int $projectId, string $requestedProvider = ''): LLMClientInterface
    {
        $settings = $this->settingsModel->getGlobal();
        $requested = $requestedProvider !== '' ? $requestedProvider : $settings['kanai_default_provider'];

        // Throws RuntimeException if AI is off for the project or an external
        // provider is requested without both flags set. Enforced here, server-side.
        $provider = GatingPolicy::resolveProvider(
            $this->settingsModel->getProjectEnabled($projectId),
            $requested,
            $this->settingsModel->isExternalEnabled(),
            $this->settingsModel->getProjectExternalOptIn($projectId)
        );

        $maxOutput = (int) $settings['kanai_max_output_tokens'];
        $timeout = (int) ($settings['kanai_request_timeout'] ?? 120);
        $http = $this->transport($timeout);

        switch ($provider) {
            case 'anthropic':
                return new AnthropicClient($http, $settings['kanai_anthropic_key'], $settings['kanai_anthropic_model'], $maxOutput);
            case 'openai':
                return new OpenAiCompatibleClient($http, 'https://api.openai.com/v1', $settings['kanai_openai_key'], $settings['kanai_openai_model']);
            case 'local':
            default:
                return new OpenAiCompatibleClient($http, $settings['kanai_local_base_url'], '', $settings['kanai_local_model']);
        }
    }

    /** @return callable fn(string $url, array $body, array $headers): array */
    private function transport(int $timeout): callable
    {
        return function (string $url, array $body, array $headers) use ($timeout): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($raw === false || $raw === '') {
                throw new \RuntimeException('KanAI LLM request failed: ' . ($err !== '' ? $err : 'empty response'));
            }
            $json = json_decode($raw, true);
            return is_array($json) ? $json : [];
        };
    }
}
