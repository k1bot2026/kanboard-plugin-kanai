<div class="page-header"><h2><?= t('KanAI Settings') ?></h2></div>
<form method="post" action="<?= $this->url->href('ProjectSettingsController', 'save', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
    <?= $this->form->csrf() ?>
    <?= $this->form->checkbox('kanai_enabled', t('Enable KanAI for this project'), '1', $enabled) ?>
    <?= $this->form->checkbox('kanai_external_opt_in', t('Allow external AI providers for this project'), '1', $external_opt_in) ?>
    <?php if (! $external_globally_enabled): ?>
        <p class="form-help"><?= t('External providers are globally disabled by the administrator; only the local LLM will be used regardless of this setting.') ?></p>
    <?php endif ?>
    <div class="form-actions"><button type="submit" class="btn btn-blue"><?= t('Save') ?></button></div>
</form>
