<?php foreach ($messages as $m): ?>
    <div class="kanai-msg kanai-msg-<?= $this->text->e($m['role']) ?>">
        <div class="kanai-msg-avatar">
            <?php if ($m['role'] === 'assistant'): ?>
                <span class="kanai-ai">✨</span>
            <?php elseif (! empty($m['author'])): ?>
                <?= $this->avatar->render($m['author']['id'], $m['author']['username'], $m['author']['name'], $m['author']['email'], $m['author']['avatar_path'], '', 28) ?>
            <?php elseif (empty($m['user_id'])): ?>
                <span class="kanai-ai">🤖</span>
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
                <?php elseif (empty($m['user_id'])): ?>
                    <?= t('KanAI (automatic)') ?>
                <?php else: ?>
                    <?= t('You') ?>
                <?php endif ?>
            </div>
            <div class="kanai-msg-text">
                <?php if ($m['role'] === 'assistant'): ?>
                    <?= $this->text->markdown($m['content']) ?>
                <?php else: ?>
                    <?= nl2br($this->text->e($m['content'])) ?>
                <?php endif ?>
            </div>
        </div>
    </div>
<?php endforeach ?>
