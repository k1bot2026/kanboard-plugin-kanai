<?php

namespace Kanboard\Plugin\KanAI;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
use Kanboard\Core\Translator;
use Kanboard\Plugin\KanAI\Console\DigestCommand;

class Plugin extends Base
{
    public function onStartup(): void
    {
        // Load plugin translations for the user's language (called on app.bootstrap).
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__ . '/Locale');
    }

    public function initialize(): void
    {
        // Admin config
        $this->route->addRoute('/kanai/config', 'ConfigController', 'show', 'KanAI');
        $this->route->addRoute('/kanai/config/test', 'ConfigController', 'test', 'KanAI');
        $this->applicationAccessMap->add('ConfigController', '*', Role::APP_ADMIN);
        $this->template->hook->attach('template:config:sidebar', 'KanAI:config/sidebar');

        // Per-project assistant
        $this->route->addRoute('/kanai/project/:project_id', 'AssistantController', 'index', 'KanAI');
        $this->route->addRoute('/kanai/project/:project_id/ask', 'AssistantController', 'ask', 'KanAI');
        $this->route->addRoute('/kanai/project/:project_id/conversation/delete', 'AssistantController', 'deleteConversation', 'KanAI');
        $this->route->addRoute('/kanai/project/:project_id/conversation/rename', 'AssistantController', 'renameConversation', 'KanAI');
        $this->route->addRoute('/kanai/project/:project_id/proposals/apply', 'ActionController', 'apply', 'KanAI');
        $this->route->addRoute('/kanai/project/:project_id/proposals/reject', 'ActionController', 'reject', 'KanAI');

        $this->projectAccessMap->add('AssistantController', '*', Role::PROJECT_MEMBER);
        $this->projectAccessMap->add('ActionController', '*', Role::PROJECT_MEMBER);

        $this->route->addRoute('/kanai/project/:project_id/settings', 'ProjectSettingsController', 'show', 'KanAI');
        $this->route->addRoute('/kanai/project/:project_id/settings/save', 'ProjectSettingsController', 'save', 'KanAI');
        $this->projectAccessMap->add('ProjectSettingsController', '*', Role::PROJECT_MANAGER);

        $this->template->hook->attach('template:project:sidebar', 'KanAI:project/sidebar');

        // KanAI tab in the project view switcher (next to Overview / Board / List),
        // shown only when KanAI is enabled for the project.
        $container = $this->container;
        $this->template->hook->attachCallable(
            'template:project-header:view-switcher',
            'KanAI:project/view_switcher',
            function ($project, $filters = null) use ($container) {
                return ['kanai_enabled' => $container['settingsModel']->getProjectEnabled((int) $project['id'])];
            }
        );

        $this->hook->on('template:layout:js', ['template' => 'plugins/KanAI/Asset/kanai.js']);
        $this->hook->on('template:layout:css', ['template' => 'plugins/KanAI/Asset/kanai.css']);

        // Console: autonomous digest (run from cron: php cli kanai:digest).
        // Only register in CLI context to avoid building the console app per web request.
        if (php_sapi_name() === 'cli') {
            $this->cli->add(new DigestCommand($this->container));
        }
    }

    public function getClasses(): array
    {
        return [
            'Plugin\KanAI\Model' => [
                'SettingsModel', 'ConversationModel', 'AssistantService', 'ActionApplierModel',
            ],
            'Plugin\KanAI\LLM' => ['LlmClientFactory'],
        ];
    }

    public function getPluginName(): string { return 'KanAI'; }
    public function getPluginDescription(): string { return 'AI assistant & project Q&A (RAG) for Kanboard — local LLM first, optional external providers'; }
    public function getPluginAuthor(): string { return 'k1bot2026'; }
    public function getPluginVersion(): string { return '1.4.1'; }
    public function getCompatibleVersion(): string { return '>=1.2.46'; }
    public function getPluginHomepage(): string { return 'https://github.com/k1bot2026/kanboard-plugin-kanai'; }
}
