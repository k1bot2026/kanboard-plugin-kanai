<div class="page-header"><h2><?= t('KanAI Assistant') ?></h2></div>

<?php if (! $enabled): ?>
    <div class="alert alert-info">
        <?= t('KanAI is disabled for this project. Enable it in the project settings.') ?>
    </div>
<?php else: ?>
    <div class="kanai-history">
        <?php foreach ($messages as $m): ?>
            <div class="kanai-msg kanai-msg-<?= $this->text->e($m['role']) ?>">
                <strong><?= $m['role'] === 'user' ? t('You') : 'KanAI' ?>:</strong>
                <div><?= nl2br($this->text->e($m['content'])) ?></div>
            </div>
        <?php endforeach ?>
    </div>

    <?= $this->render('KanAI:assistant/proposals', ['project' => $project, 'proposals' => $proposals]) ?>

    <div class="kanai-skills">
        <?php foreach (\Kanboard\Plugin\KanAI\Model\AssistantSkills::all() as $skill): ?>
            <form method="post" class="kanai-skill-form"
                  action="<?= $this->url->href('AssistantController', 'ask', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
                <?= $this->form->csrf() ?>
                <input type="hidden" name="skill" value="<?= $this->text->e($skill['key']) ?>">
                <button type="submit" class="btn"><?= $this->text->e($skill['label']) ?></button>
            </form>
        <?php endforeach ?>
    </div>

    <form method="post" action="<?= $this->url->href('AssistantController', 'ask', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>" class="kanai-ask">
        <?= $this->form->csrf() ?>
        <?= $this->form->textarea('question', [], [], ['placeholder' => t('Ask about this project, or ask KanAI to tidy it up…'), 'rows' => 3]) ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-blue"><?= t('Ask KanAI') ?></button>
            <?= $this->url->link(t('Clear conversation'), 'AssistantController', 'clear', ['project_id' => $project['id'], 'plugin' => 'KanAI'], true, 'btn', t('Delete this project\'s KanAI history?')) ?>
        </div>
    </form>
<?php endif ?>
