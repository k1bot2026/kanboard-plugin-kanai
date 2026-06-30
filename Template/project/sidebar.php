<li <?= $this->app->checkMenuSelection('AssistantController', 'index') ?>>
    <?= $this->url->link(t('KanAI'), 'AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>
</li>
<?php if ($this->user->hasProjectAccess('ProjectEditController', 'show', $project['id'])): ?>
<li <?= $this->app->checkMenuSelection('ProjectSettingsController', 'show') ?>>
    <?= $this->url->link(t('KanAI Settings'), 'ProjectSettingsController', 'show', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>
</li>
<?php endif ?>
