<?php

namespace Kanboard\Plugin\KanAI;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    public function initialize(): void
    {
        // v1 features (settings, LLM clients, RAG, assistant, actions, UI) are
        // added by the KanAI v1 feature plan. The scaffold loads cleanly with
        // no routes/hooks so it can be installed and verified in isolation.
    }

    public function getClasses(): array
    {
        return [];
    }

    public function getPluginName(): string
    {
        return 'KanAI';
    }

    public function getPluginDescription(): string
    {
        return 'AI assistant & project Q&A (RAG) for Kanboard — local LLM first, optional external providers';
    }

    public function getPluginAuthor(): string
    {
        return 'k1bot2026';
    }

    public function getPluginVersion(): string
    {
        return '0.1.0';
    }

    public function getCompatibleVersion(): string
    {
        return '>=1.2.46';
    }

    public function getPluginHomepage(): string
    {
        return 'https://github.com/k1bot2026/kanboard-plugin-kanai';
    }
}
