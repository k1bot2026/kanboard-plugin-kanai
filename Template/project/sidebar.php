<li <?= $this->app->checkMenuSelection('AssistantController', 'index') ?>>
    <?= $this->url->link(t('KanAI'), 'AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>
</li>
