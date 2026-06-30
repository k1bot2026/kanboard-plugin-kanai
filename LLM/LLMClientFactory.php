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
        $http = $this->transport();

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
    private function transport(): callable
    {
        $httpClient = $this->httpClient;
        return function (string $url, array $body, array $headers) use ($httpClient): array {
            $response = $httpClient->postJson($url, $body, $headers, true);
            return is_array($response) ? $response : [];
        };
    }
}
