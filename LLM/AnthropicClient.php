<?php

namespace Kanboard\Plugin\KanAI\LLM;

use RuntimeException;

/**
 * Anthropic Messages API adapter. Differs from OpenAI: system prompt is a
 * top-level field, max_tokens is required, response content is an array of typed
 * blocks. Transport injected for testability.
 */
class AnthropicClient implements LLMClientInterface
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const VERSION = '2023-06-01';

    /** @var callable */
    private $http;
    private string $apiKey;
    private string $model;
    private int $defaultMaxTokens;

    public function __construct(callable $http, string $apiKey, string $model, int $defaultMaxTokens = 1024)
    {
        $this->http = $http;
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->defaultMaxTokens = $defaultMaxTokens;
    }

    public function buildRequest(string $system, array $messages, array $opts): array
    {
        return [
            'model' => $this->model,
            'max_tokens' => (int) ($opts['max_tokens'] ?? $this->defaultMaxTokens),
            'system' => $system,
            'messages' => array_values($messages),
        ];
    }

    public function parseResponse(array $json): string
    {
        if (($json['type'] ?? '') === 'error' || isset($json['error'])) {
            $msg = $json['error']['message'] ?? 'unknown';
            throw new RuntimeException('Anthropic error: ' . $msg);
        }
        $text = '';
        foreach (($json['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }
        if ($text === '') {
            throw new RuntimeException('Anthropic returned no text content.');
        }
        return $text;
    }

    public function complete(string $system, array $messages, array $opts = []): string
    {
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: ' . self::VERSION,
        ];
        $json = ($this->http)(self::ENDPOINT, $this->buildRequest($system, $messages, $opts), $headers);
        return $this->parseResponse($json);
    }
}
