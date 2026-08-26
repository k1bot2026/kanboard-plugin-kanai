<div class="page-header"><h2><?= t('KanAI Settings') ?></h2></div>
<form method="post" action="<?= $this->url->href('ProjectSettingsController', 'save', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
    <?= $this->form->csrf() ?>
    <?php if ($enable_policy === 'all_projects'): ?>
        <p class="form-help"><i class="fa fa-check-circle"></i> <?= t('KanAI is enabled for all projects by the administrator.') ?></p>
        <?php /* Preserve the project's own flag so it takes effect again if the
                 admin later switches back to the per-project policy. */ ?>
        <input type="hidden" name="kanai_enabled" value="<?= $enabled ? '1' : '0' ?>">
    <?php else: ?>
        <?= $this->form->checkbox('kanai_enabled', t('Enable KanAI for this project'), '1', $enabled) ?>
    <?php endif ?>

    <?php if ($external_policy === 'project_optin'): ?>
        <?= $this->form->checkbox('kanai_external_opt_in', t('Allow external AI providers for this project'), '1', $external_opt_in) ?>
    <?php elseif ($external_policy === 'all_projects'): ?>
        <p class="form-help"><i class="fa fa-cloud"></i> <?= t('External AI is enabled for all projects by the administrator.') ?></p>
    <?php else: ?>
        <p class="form-help"><i class="fa fa-lock"></i> <?= t('External AI providers are disabled by the administrator; only the local LLM is used.') ?></p>
    <?php endif ?>

    <?= $this->form->checkbox('kanai_auto_digest', t('Enable the automatic daily digest (requires the kanai:digest cron job)'), '1', $auto_digest) ?>

    <div class="form-actions"><button type="submit" class="btn btn-blue"><?= t('Save') ?></button></div>
</form>
