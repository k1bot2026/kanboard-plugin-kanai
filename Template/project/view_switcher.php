<?php if (! empty($kanai_enabled)): ?>
<li <?= $this->app->checkMenuSelection('AssistantController') ?>>
    <?= $this->url->icon('magic', t('KanAI'), 'AssistantController', 'index', array('project_id' => $project['id'], 'plugin' => 'KanAI'), false, 'view-kanai', t('KanAI Assistant')) ?>
</li>
<?php endif ?>
