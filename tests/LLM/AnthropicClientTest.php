<?php

namespace Kanboard\Plugin\KanAI\Tests\LLM;

use Kanboard\Plugin\KanAI\LLM\AnthropicClient;
use PHPUnit\Framework\TestCase;

final class AnthropicClientTest extends TestCase
{
    public function testBuildRequestUsesTopLevelSystemAndMaxTokens(): void
    {
        $c = new AnthropicClient(fn() => [], 'KEY', 'claude-sonnet-4-6', 1024);
        $body = $c->buildRequest('SYS', [['role' => 'user', 'content' => 'hi']], []);
        $this->assertSame('claude-sonnet-4-6', $body['model']);
        $this->assertSame('SYS', $body['system']);
        $this->assertSame(1024, $body['max_tokens']);
        $this->assertSame([['role' => 'user', 'content' => 'hi']], $body['messages']);
        $this->assertArrayNotHasKey('stream', $body);
    }

    public function testBuildRequestRespectsOptMaxTokens(): void
    {
        $c = new AnthropicClient(fn() => [], 'KEY', 'm', 1024);
        $body = $c->buildRequest('S', [], ['max_tokens' => 4096]);
        $this->assertSame(4096, $body['max_tokens']);
    }

    public function testParseResponseConcatenatesTextBlocks(): void
    {
        $c = new AnthropicClient(fn() => [], 'KEY', 'm');
        $json = ['content' => [
            ['type' => 'thinking', 'thinking' => 'hmm'],
            ['type' => 'text', 'text' => 'Hello '],
            ['type' => 'text', 'text' => 'world'],
        ], 'stop_reason' => 'end_turn'];
        $this->assertSame('Hello world', $c->parseResponse($json));
    }

    public function testParseResponseThrowsOnError(): void
    {
        $c = new AnthropicClient(fn() => [], 'KEY', 'm');
        $this->expectException(\RuntimeException::class);
        $c->parseResponse(['type' => 'error', 'error' => ['message' => 'overloaded']]);
    }

    public function testCompleteSetsAnthropicHeaders(): void
    {
        $captured = [];
        $http = function (string $url, array $body, array $headers) use (&$captured) {
            $captured = compact('url', 'headers');
            return ['content' => [['type' => 'text', 'text' => 'ok']]];
        };
        $c = new AnthropicClient($http, 'KEY', 'm');
        $this->assertSame('ok', $c->complete('S', [['role' => 'user', 'content' => 'q']]));
        $this->assertSame('https://api.anthropic.com/v1/messages', $captured['url']);
        $this->assertContains('x-api-key: KEY', $captured['headers']);
        $this->assertContains('anthropic-version: 2023-06-01', $captured['headers']);
    }
}
