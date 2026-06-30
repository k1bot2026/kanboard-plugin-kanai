<?php if (! empty($proposals)): ?>
    <?php foreach ($proposals as $set): ?>
        <div class="kanai-proposals">
            <h3><?= t('Proposed actions') ?></h3>
            <form method="post" action="<?= $this->url->href('ActionController', 'apply', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
                <?= $this->form->csrf() ?>
                <input type="hidden" name="proposal_set_id" value="<?= (int) $set['id'] ?>">
                <ul>
                    <?php foreach ($set['actions'] as $i => $a): ?>
                        <li>
                            <label>
                                <input type="checkbox" name="approve[]" value="<?= (int) $i ?>" checked>
                                <strong><?= $this->text->e($a['action']) ?></strong>
                                <?php if (! empty($a['task_id'])): ?>#<?= (int) $a['task_id'] ?><?php endif ?>
                                <?php if (! empty($a['reason'])): ?>— <?= $this->text->e($a['reason']) ?><?php endif ?>
                            </label>
                        </li>
                    <?php endforeach ?>
                </ul>
                <button type="submit" class="btn btn-green"><?= t('Apply selected') ?></button>
                <?= $this->url->link(t('Reject all'), 'ActionController', 'reject', ['project_id' => $project['id'], 'proposal_set_id' => $set['id'], 'plugin' => 'KanAI'], true, 'btn btn-red') ?>
            </form>
        </div>
    <?php endforeach ?>
<?php endif ?>
