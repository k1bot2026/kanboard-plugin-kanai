<div class="kanai">
<div class="page-header"><h2>✨ <?= t('KanAI Assistant') ?></h2></div>

<?php if (! $enabled): ?>
    <div class="alert alert-info">
        <?= t('KanAI is disabled for this project. Enable it in the project settings.') ?>
    </div>
<?php else: ?>

<div class="kanai-layout">

    <aside class="kanai-convlist">
        <?= $this->url->link('<i class="fa fa-plus"></i> '.t('New chat'), 'AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI', 'new' => 1], false, 'kanai-newchat') ?>
        <ul>
            <?php foreach ($conversations as $c): ?>
                <li class="kanai-conv <?= (int) $c['id'] === (int) $active_id ? 'is-active' : '' ?>">
                    <?= $this->url->link($this->text->e($c['title'] !== '' ? $c['title'] : t('New conversation')), 'AssistantController', 'index', ['project_id' => $project['id'], 'plugin' => 'KanAI', 'conversation_id' => $c['id']], false, 'kanai-conv-link') ?>
                    <?= $this->url->link('&times;', 'AssistantController', 'deleteConversation', ['project_id' => $project['id'], 'plugin' => 'KanAI', 'conversation_id' => $c['id']], true, 'kanai-conv-del', t('Delete this conversation?')) ?>
                </li>
            <?php endforeach ?>
            <?php if (empty($conversations)): ?>
                <li class="kanai-conv-empty"><?= t('No conversations yet') ?></li>
            <?php endif ?>
        </ul>
    </aside>

    <section class="kanai-main">
        <?php
            $activeTitle = '';
            foreach ($conversations as $c) {
                if ((int) $c['id'] === (int) $active_id) { $activeTitle = $c['title']; break; }
            }
        ?>
        <?php if ($active_id > 0): ?>
            <div class="kanai-conv-header">
                <span class="kanai-conv-title" id="kanai-conv-title"><?= $this->text->e($activeTitle !== '' ? $activeTitle : t('New conversation')) ?></span>
                <a href="#" class="kanai-rename-toggle" title="<?= t('Rename') ?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                <form method="post" class="kanai-rename-form" id="kanai-rename-form"
                      action="<?= $this->url->href('AssistantController', 'renameConversation', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
                    <?= $this->form->csrf() ?>
                    <input type="hidden" name="conversation_id" value="<?= (int) $active_id ?>">
                    <input type="text" name="title" value="<?= $this->text->e($activeTitle) ?>" maxlength="120" autocomplete="off">
                    <button type="submit" class="btn btn-blue"><?= t('Rename') ?></button>
                    <a href="#" class="kanai-rename-cancel"><?= t('Cancel') ?></a>
                </form>
            </div>
        <?php endif ?>
        <div class="kanai-thread" id="kanai-thread">
            <?php if (empty($messages)): ?>
                <div class="kanai-welcome">
                    <div class="kanai-welcome-icon">✨</div>
                    <p><?= t('Ask anything about this project, or pick a quick action below. Conversations are shared with everyone on the project.') ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                    <div class="kanai-msg kanai-msg-<?= $this->text->e($m['role']) ?>">
                        <div class="kanai-msg-avatar">
                            <?php if ($m['role'] === 'assistant'): ?>
                                <span class="kanai-ai">✨</span>
                            <?php elseif (! empty($m['author'])): ?>
                                <?= $this->avatar->render($m['author']['id'], $m['author']['username'], $m['author']['name'], $m['author']['email'], $m['author']['avatar_path'], '', 28) ?>
                            <?php else: ?>
                                <span class="kanai-ai">🧑</span>
                            <?php endif ?>
                        </div>
                        <div class="kanai-msg-body">
                            <div class="kanai-msg-role">
                                <?php if ($m['role'] === 'assistant'): ?>
                                    KanAI
                                <?php elseif (! empty($m['author'])): ?>
                                    <?= $this->text->e(! empty($m['author']['name']) ? $m['author']['name'] : $m['author']['username']) ?>
                                <?php else: ?>
                                    <?= t('You') ?>
                                <?php endif ?>
                            </div>
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
                    <input type="hidden" name="conversation_id" value="<?= (int) $active_id ?>">
                    <button type="submit" class="kanai-skill"><?= $this->text->e($skill['label']) ?></button>
                </form>
            <?php endforeach ?>
        </div>

        <form method="post" class="kanai-ask"
              action="<?= $this->url->href('AssistantController', 'ask', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
            <?= $this->form->csrf() ?>
            <input type="hidden" name="conversation_id" value="<?= (int) $active_id ?>">
            <?= $this->form->textarea('question', [], [], ['placeholder' => t('Message KanAI…'), 'rows' => 3, 'id' => 'kanai-input']) ?>
            <div class="kanai-ask-actions">
                <button type="submit" class="btn btn-blue"><?= t('Send') ?></button>
                <?php if ($active_id > 0): ?>
                    <?= $this->url->link(t('Delete chat'), 'AssistantController', 'deleteConversation', ['project_id' => $project['id'], 'plugin' => 'KanAI', 'conversation_id' => $active_id], true, '', t('Delete this conversation?')) ?>
                <?php endif ?>
                <span class="kanai-hint"><?= t('Enter to send · Shift+Enter for a new line') ?></span>
            </div>
        </form>
    </section>

</div>
<?php endif ?>
</div>
