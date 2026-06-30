<?php

namespace Kanboard\Plugin\KanAI\LLM;

use RuntimeException;

/**
 * OpenAI-compatible chat client. Serves a local server (Ollama/LM Studio/vLLM,
 * dummy key) and OpenAI itself (real key) — the wire shape is identical.
 * Transport is injected as a callable so request/response shaping is unit-tested
 * without HTTP. No direct Kanboard dependency.
 */
class OpenAiCompatibleClient implements LLMClientInterface
{
    /** @var callable fn(string $url, array $body, array $headers): array */
    private $http;
    private string $baseUrl;
    private string $apiKey;
    private string $model;

    public function __construct(callable $http, string $baseUrl, string $apiKey, string $model)
    {
        $this->http = $http;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function buildRequest(string $system, array $messages, array $opts): array
    {
        $msgs = array_merge([['role' => 'system', 'content' => $system]], $messages);
        $body = [
            'model' => $this->model,
            'messages' => $msgs,
            'stream' => false,
        ];
        if (! empty($opts['max_tokens'])) {
            $body['max_tokens'] = (int) $opts['max_tokens'];
        }
        return $body;
    }

    public function parseResponse(array $json): string
    {
        if (isset($json['error'])) {
            $msg = is_array($json['error']) ? ($json['error']['message'] ?? 'unknown') : (string) $json['error'];
            throw new RuntimeException('LLM error: ' . $msg);
        }
        $content = $json['choices'][0]['message']['content'] ?? null;
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('LLM returned an empty/unexpected response.');
        }
        return $content;
    }

    public function complete(string $system, array $messages, array $opts = []): string
    {
        $headers = ['Content-Type: application/json'];
        if ($this->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        $json = ($this->http)(
            $this->baseUrl . '/chat/completions',
            $this->buildRequest($system, $messages, $opts),
            $headers
        );
        return $this->parseResponse($json);
    }
}
