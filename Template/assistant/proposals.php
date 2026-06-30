<?php
$kanaiActionMeta = array(
    'create_task'  => array('➕', t('Create task')),
    'update_task'  => array('✏️', t('Update task')),
    'close_task'   => array('✅', t('Close task')),
    'reopen_task'  => array('↩️', t('Reopen task')),
    'move_task'    => array('➡️', t('Move task')),
    'assign_task'  => array('👤', t('Assign task')),
    'add_tag'      => array('🏷️', t('Add tag')),
    'set_due_date' => array('📅', t('Set due date')),
    'add_comment'  => array('💬', t('Add comment')),
    'add_subtask'  => array('☑️', t('Add subtask')),
    'link_tasks'   => array('🔗', t('Link tasks')),
);
?>
<?php if (! empty($proposals)): ?>
    <?php foreach ($proposals as $set): ?>
        <div class="kanai-proposals">
            <div class="kanai-proposals-head">
                <span><?= t('Proposed actions') ?></span>
                <span class="kanai-proposals-count"><?= count($set['actions']) ?></span>
            </div>
            <form method="post"
                  action="<?= $this->url->href('ActionController', 'apply', ['project_id' => $project['id'], 'plugin' => 'KanAI']) ?>">
                <?= $this->form->csrf() ?>
                <input type="hidden" name="proposal_set_id" value="<?= (int) $set['id'] ?>">
                <ul>
                    <?php foreach ($set['actions'] as $i => $a): ?>
                        <?php $meta = isset($kanaiActionMeta[$a['action']]) ? $kanaiActionMeta[$a['action']] : array('•', ucfirst(str_replace('_', ' ', $a['action']))); ?>
                        <li>
                            <label class="kanai-proposal">
                                <input type="checkbox" name="approve[]" value="<?= (int) $i ?>" checked>
                                <span class="kanai-proposal-main">
                                    <span class="kanai-action"><span class="kanai-action-icon"><?= $meta[0] ?></span><?= $this->text->e($meta[1]) ?></span>
                                    <?php if (! empty($a['task_id'])): ?>
                                        <?= $this->url->link('#' . (int) $a['task_id'], 'TaskViewController', 'show', ['task_id' => (int) $a['task_id'], 'project_id' => $project['id']], false, 'kanai-tasklink') ?>
                                    <?php endif ?>
                                    <?php if (! empty($a['reason'])): ?>
                                        <span class="kanai-reason"><?= $this->text->e($a['reason']) ?></span>
                                    <?php endif ?>
                                </span>
                            </label>
                        </li>
                    <?php endforeach ?>
                </ul>
                <div class="kanai-proposals-foot">
                    <button type="submit" class="btn btn-green"><?= t('Apply selected') ?></button>
                    <?= $this->url->link(t('Reject all'), 'ActionController', 'reject', ['project_id' => $project['id'], 'proposal_set_id' => $set['id'], 'plugin' => 'KanAI'], true, 'btn btn-red') ?>
                </div>
            </form>
        </div>
    <?php endforeach ?>
<?php endif ?>
