<?php

namespace Kanboard\Plugin\KanAI\Tests\LLM;

use Kanboard\Plugin\KanAI\LLM\OpenAiCompatibleClient;
use PHPUnit\Framework\TestCase;

final class OpenAiCompatibleClientTest extends TestCase
{
    public function testBuildRequestPutsSystemFirstAndDisablesStreaming(): void
    {
        $client = new OpenAiCompatibleClient(fn() => [], 'http://x/v1', '', 'llama3.1');
        $body = $client->buildRequest('SYS', [['role' => 'user', 'content' => 'hi']], ['max_tokens' => 256]);
        $this->assertSame('llama3.1', $body['model']);
        $this->assertFalse($body['stream']);
        $this->assertSame(256, $body['max_tokens']);
        $this->assertSame(['role' => 'system', 'content' => 'SYS'], $body['messages'][0]);
        $this->assertSame(['role' => 'user', 'content' => 'hi'], $body['messages'][1]);
        $this->assertArrayNotHasKey('response_format', $body);
    }

    public function testJsonModeSetsResponseFormat(): void
    {
        $client = new OpenAiCompatibleClient(fn() => [], 'http://x/v1', '', 'm');
        $body = $client->buildRequest('SYS', [], ['json' => true]);
        $this->assertSame(['type' => 'json_object'], $body['response_format']);
    }

    public function testParseResponseReturnsContent(): void
    {
        $client = new OpenAiCompatibleClient(fn() => [], 'http://x/v1', '', 'm');
        $json = ['choices' => [['message' => ['role' => 'assistant', 'content' => 'hello']]]];
        $this->assertSame('hello', $client->parseResponse($json));
    }

    public function testParseResponseThrowsOnErrorShape(): void
    {
        $client = new OpenAiCompatibleClient(fn() => [], 'http://x/v1', '', 'm');
        $this->expectException(\RuntimeException::class);
        $client->parseResponse(['error' => ['message' => 'bad key']]);
    }

    public function testCompleteWiresTransportAndParses(): void
    {
        $captured = [];
        $http = function (string $url, array $body, array $headers) use (&$captured) {
            $captured = compact('url', 'body', 'headers');
            return ['choices' => [['message' => ['content' => 'answer']]]];
        };
        $client = new OpenAiCompatibleClient($http, 'http://local/v1', 'KEY', 'm');
        $out = $client->complete('SYS', [['role' => 'user', 'content' => 'q']]);
        $this->assertSame('answer', $out);
        $this->assertSame('http://local/v1/chat/completions', $captured['url']);
        $this->assertContains('Authorization: Bearer KEY', $captured['headers']);
    }

    public function testCompleteOmitsAuthHeaderWhenKeyEmpty(): void
    {
        $captured = [];
        $http = function (string $url, array $body, array $headers) use (&$captured) {
            $captured = compact('headers');
            return ['choices' => [['message' => ['content' => 'answer']]]];
        };
        $client = new OpenAiCompatibleClient($http, 'http://local/v1', '', 'm');
        $client->complete('SYS', [['role' => 'user', 'content' => 'q']]);
        foreach ($captured['headers'] as $h) {
            $this->assertStringStartsNotWith('Authorization:', $h);
        }
    }

    public function testCompleteSendsAuthHeaderForLocalKey(): void
    {
        $captured = [];
        $http = function (string $url, array $body, array $headers) use (&$captured) {
            $captured = compact('headers');
            return ['choices' => [['message' => ['content' => 'ok']]]];
        };
        // A local server behind auth (LocalAI, a reverse proxy) — same wire shape,
        // real key. The bearer token must be sent.
        $client = new OpenAiCompatibleClient($http, 'https://ai.example.com/v1', 'local-secret', 'm');
        $client->complete('SYS', [['role' => 'user', 'content' => 'q']]);
        $this->assertContains('Authorization: Bearer local-secret', $captured['headers']);
    }
}
