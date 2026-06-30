<?php

namespace Kanboard\Plugin\KanAI;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;

class Plugin extends Base
{
    public function initialize(): void
    {
        // Admin config
        $this->route->addRoute('/kanai/config', 'ConfigController', 'show', 'KanAI');
        $this->applicationAccessMap->add('ConfigController', '*', Role::APP_ADMIN);
        $this->template->hook->attach('template:config:sidebar', 'KanAI:config/sidebar');
    }

    public function getClasses(): array
    {
        return [
            'Plugin\KanAI\Model' => [
                'SettingsModel', 'ConversationModel', 'AssistantService', 'ActionApplierModel',
            ],
            'Plugin\KanAI\LLM' => ['LLMClientFactory'],
        ];
    }

    public function getPluginName(): string { return 'KanAI'; }
    public function getPluginDescription(): string { return 'AI assistant & project Q&A (RAG) for Kanboard — local LLM first, optional external providers'; }
    public function getPluginAuthor(): string { return 'k1bot2026'; }
    public function getPluginVersion(): string { return '1.0.0'; }
    public function getCompatibleVersion(): string { return '>=1.2.46'; }
    public function getPluginHomepage(): string { return 'https://github.com/k1bot2026/kanboard-plugin-kanai'; }
}
