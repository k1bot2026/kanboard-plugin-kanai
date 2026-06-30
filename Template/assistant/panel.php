<div class="kanai">
<div class="page-header"><h2>✨ <?= t('KanAI Assistant') ?></h2></div>

<?php if (! $enabled): ?>
    <div class="alert alert-info">
        <?= t('KanAI is disabled for this project. Enable it in the project settings.') ?>
    </div>
<?php else: ?>

    <p class="kanai-intro">
        <?= t('Chat about this project, or pick a quick action. KanAI only proposes changes — you approve before anything is applied.') ?>
    </p>

    <div class="kanai-thread" id="kanai-thread">
        <?php if (empty($messages)): ?>
            <div class="kanai-welcome">
                <div class="kanai-welcome-icon">✨</div>
                <p><?= t('Ask anything about this project, or tap a quick action below to get started.') ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <div class="kanai-msg kanai-msg-<?= $this->text->e($m['role']) ?>">
                    <div class="kanai-msg-avatar"><?= $m['role'] === 'user' ? '🧑' : '✨' ?></div>
                    <div class="kanai-msg-body">
                        <div class="kanai-msg-role"><?= $m['role'] === 'user' ? t('You') : 'KanAI' ?></div>
                        <div class="kanai-msg-text"><?= nl2br($this->text->e($m['content'])) ?></div>
                    </div>
                </div>
            <?php endforeach ?>
        <?php endif ?>
    </div>

    <?= $this->render('KanAI:assistant/proposals', ['project' => $project, 'proposals' => $proposals]) ?>

    <div class="kanai-skills">
        <?php foreach (\Kanboard\Plugin\KanAI\Model\AssistantSkills::all() as $skill): ?>
            <form method="post" class="kanai-skill-form"
                  action="<?= $this->url->href('AssistantController', 'ask', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
                <?= $this->form->csrf() ?>
                <input type="hidden" name="skill" value="<?= $this->text->e($skill['key']) ?>">
                <button type="submit" class="kanai-skill"><?= $this->text->e($skill['label']) ?></button>
            </form>
        <?php endforeach ?>
    </div>

    <form method="post" class="kanai-ask"
          action="<?= $this->url->href('AssistantController', 'ask', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
        <?= $this->form->csrf() ?>
        <?= $this->form->textarea('question', [], [], ['placeholder' => t('Message KanAI…'), 'rows' => 3, 'id' => 'kanai-input']) ?>
        <div class="kanai-ask-actions">
            <button type="submit" class="btn btn-blue"><?= t('Send') ?></button>
            <?= $this->url->link(t('Clear conversation'), 'AssistantController', 'clear', ['project_id' => $project['id'], 'plugin' => 'KanAI'], true, '', t('Delete this project\'s KanAI history?')) ?>
            <span class="kanai-hint"><?= t('Enter to send · Shift+Enter for a new line') ?></span>
        </div>
    </form>
<?php endif ?>
</div>
