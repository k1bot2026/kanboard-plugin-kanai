<div class="page-header"><h2><?= t('KanAI Settings') ?></h2></div>
<form method="post" action="<?= $this->url->href('ConfigController', 'save', ['plugin' => 'KanAI']) ?>" autocomplete="off">
    <?= $this->form->csrf() ?>

    <fieldset>
        <legend><?= t('Local LLM (default)') ?></legend>
        <?= $this->form->label(t('Base URL (OpenAI-compatible)'), 'kanai_local_base_url') ?>
        <?= $this->form->text('kanai_local_base_url', $values) ?>
        <?= $this->form->label(t('Model'), 'kanai_local_model') ?>
        <?= $this->form->text('kanai_local_model', $values) ?>
    </fieldset>

    <fieldset>
        <legend><?= t('External providers') ?></legend>
        <?= $this->form->checkbox('kanai_external_enabled', t('Allow external AI providers (global kill switch)'), '1', $values['kanai_external_enabled'] == '1') ?>
        <p class="form-help"><?= t('When off, only the local LLM is used — no project data leaves this server.') ?></p>

        <?= $this->form->label(t('Default provider'), 'kanai_default_provider') ?>
        <?= $this->form->select('kanai_default_provider', ['local' => 'Local', 'openai' => 'OpenAI', 'anthropic' => 'Anthropic'], $values) ?>

        <?= $this->form->label(t('OpenAI API key'), 'kanai_openai_key') ?>
        <?= $this->form->password('kanai_openai_key', []) ?>
        <p class="form-help"><?= $openai_key_mask ? t('Saved: %s (leave blank to keep)', $openai_key_mask) : t('Not set') ?></p>
        <?= $this->form->label(t('OpenAI model'), 'kanai_openai_model') ?>
        <?= $this->form->text('kanai_openai_model', $values) ?>

        <?= $this->form->label(t('Anthropic API key'), 'kanai_anthropic_key') ?>
        <?= $this->form->password('kanai_anthropic_key', []) ?>
        <p class="form-help"><?= $anthropic_key_mask ? t('Saved: %s (leave blank to keep)', $anthropic_key_mask) : t('Not set') ?></p>
        <?= $this->form->label(t('Anthropic model'), 'kanai_anthropic_model') ?>
        <?= $this->form->text('kanai_anthropic_model', $values) ?>
    </fieldset>

    <fieldset>
        <legend><?= t('Limits & retention') ?></legend>
        <?= $this->form->label(t('Max context tokens'), 'kanai_max_context_tokens') ?>
        <?= $this->form->number('kanai_max_context_tokens', $values) ?>
        <?= $this->form->label(t('Max output tokens'), 'kanai_max_output_tokens') ?>
        <?= $this->form->number('kanai_max_output_tokens', $values) ?>
        <?= $this->form->label(t('Request timeout (seconds)'), 'kanai_request_timeout') ?>
        <?= $this->form->number('kanai_request_timeout', $values) ?>
        <?= $this->form->label(t('History retention (days, 0 = forever)'), 'kanai_history_retention_days') ?>
        <?= $this->form->number('kanai_history_retention_days', $values) ?>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
    </div>
</form>
