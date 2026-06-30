<?php

namespace Kanboard\Plugin\KanAI\LLM;

interface LLMClientInterface
{
    /**
     * @param string $system   system prompt
     * @param array  $messages list of ['role' => 'user'|'assistant', 'content' => string]
     * @param array  $opts      optional: 'max_tokens' => int
     * @return string assistant text reply
     * @throws \RuntimeException on transport/provider error
     */
    public function complete(string $system, array $messages, array $opts = []): string;
}
