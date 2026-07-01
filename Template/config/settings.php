<div class="page-header"><h2>✨ <?= t('KanAI Settings') ?></h2></div>

<form method="post" class="kanai-config"
      action="<?= $this->url->href('ConfigController', 'save', ['plugin' => 'KanAI']) ?>" autocomplete="off"
      data-test-url="<?= $this->url->href('ConfigController', 'test', ['plugin' => 'KanAI']) ?>">
    <?= $this->form->csrf() ?>

    <fieldset>
        <legend>🖥️ <?= t('Local LLM (default)') ?></legend>
        <p class="form-help"><?= t('An OpenAI-compatible server such as Ollama, LM Studio or vLLM. All project data stays on your own machine.') ?></p>

        <?= $this->form->label(t('Base URL (OpenAI-compatible)'), 'kanai_local_base_url') ?>
        <?= $this->form->text('kanai_local_base_url', $values, [], ['placeholder="http://localhost:11434/v1"']) ?>
        <p class="form-help"><?= t('When Kanboard runs in Docker, use http://host.docker.internal:11434/v1 to reach the host.') ?></p>

        <?= $this->form->label(t('Model'), 'kanai_local_model') ?>
        <?= $this->form->text('kanai_local_model', $values, [], ['list="kanai-local-models"']) ?>
        <datalist id="kanai-local-models"></datalist>
        <p class="form-help"><?= t('Tip: press "Test connection" to discover the models available on the server.') ?></p>

        <p>
            <button type="button" class="btn kanai-test-btn" data-provider="local"><?= t('Test connection') ?></button>
            <span class="kanai-test-result" id="kanai-test-result-local"></span>
        </p>
    </fieldset>

    <fieldset>
        <legend>☁️ <?= t('External providers') ?></legend>
        <?= $this->form->checkbox('kanai_external_enabled', t('Allow external AI providers (global kill switch)'), '1', $values['kanai_external_enabled'] == '1') ?>
        <p class="form-help"><?= t('When off, only the local LLM is used — no project data leaves this server. Projects must additionally opt in individually.') ?></p>

        <?= $this->form->label(t('Default provider'), 'kanai_default_provider') ?>
        <?= $this->form->select('kanai_default_provider', ['local' => t('Local LLM'), 'openai' => 'OpenAI', 'anthropic' => 'Anthropic (Claude)'], $values) ?>

        <hr>
        <h4>OpenAI
            <?php if ($openai_key_mask !== ''): ?><span class="kanai-badge kanai-badge-ok"><?= t('configured') ?></span>
            <?php else: ?><span class="kanai-badge"><?= t('not configured') ?></span><?php endif ?>
        </h4>
        <?= $this->form->label(t('OpenAI API key'), 'kanai_openai_key') ?>
        <?= $this->form->password('kanai_openai_key', []) ?>
        <p class="form-help"><?= $openai_key_mask !== '' ? t('Saved: %s (leave blank to keep)', $openai_key_mask) : t('Not set') ?></p>
        <?= $this->form->label(t('OpenAI model'), 'kanai_openai_model') ?>
        <?= $this->form->text('kanai_openai_model', $values) ?>
        <p>
            <button type="button" class="btn kanai-test-btn" data-provider="openai" data-key-field="kanai_openai_key"><?= t('Test connection') ?></button>
            <span class="kanai-test-result" id="kanai-test-result-openai"></span>
        </p>

        <hr>
        <h4>Anthropic (Claude)
            <?php if ($anthropic_key_mask !== ''): ?><span class="kanai-badge kanai-badge-ok"><?= t('configured') ?></span>
            <?php else: ?><span class="kanai-badge"><?= t('not configured') ?></span><?php endif ?>
        </h4>
        <?= $this->form->label(t('Anthropic API key'), 'kanai_anthropic_key') ?>
        <?= $this->form->password('kanai_anthropic_key', []) ?>
        <p class="form-help"><?= $anthropic_key_mask !== '' ? t('Saved: %s (leave blank to keep)', $anthropic_key_mask) : t('Not set') ?></p>
        <?= $this->form->label(t('Anthropic model'), 'kanai_anthropic_model') ?>
        <?= $this->form->text('kanai_anthropic_model', $values) ?>
        <p>
            <button type="button" class="btn kanai-test-btn" data-provider="anthropic" data-key-field="kanai_anthropic_key"><?= t('Test connection') ?></button>
            <span class="kanai-test-result" id="kanai-test-result-anthropic"></span>
        </p>
    </fieldset>

    <fieldset>
        <legend>⚙️ <?= t('Limits & retention') ?></legend>

        <?= $this->form->label(t('Max context tokens'), 'kanai_max_context_tokens') ?>
        <?= $this->form->number('kanai_max_context_tokens', $values) ?>
        <p class="form-help"><?= t('How much project data is packed into a question. Larger = better answers, slower and heavier for the model.') ?></p>

        <?= $this->form->label(t('Max output tokens'), 'kanai_max_output_tokens') ?>
        <?= $this->form->number('kanai_max_output_tokens', $values) ?>

        <?= $this->form->label(t('Request timeout (seconds)'), 'kanai_request_timeout') ?>
        <?= $this->form->number('kanai_request_timeout', $values) ?>
        <p class="form-help"><?= t('Local models can take a while; 120–180s is a safe range for larger models.') ?></p>

        <?= $this->form->label(t('Rate limit (questions per user per hour, 0 = unlimited)'), 'kanai_rate_limit_per_hour') ?>
        <?= $this->form->number('kanai_rate_limit_per_hour', $values) ?>

        <?= $this->form->label(t('History retention (days, 0 = forever)'), 'kanai_history_retention_days') ?>
        <?= $this->form->number('kanai_history_retention_days', $values) ?>
        <p class="form-help"><?= t('Conversations idle longer than this are removed automatically.') ?></p>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
    </div>
</form>
