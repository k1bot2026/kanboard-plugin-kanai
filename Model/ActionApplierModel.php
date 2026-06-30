<?php

namespace Kanboard\Plugin\KanAI\Model;

use Kanboard\Core\Base;
use RuntimeException;

/**
 * Applies one approved proposal via Kanboard's own models, as the approving user.
 * Bounded to what a standard project user can do; Kanboard validation/events fire
 * normally. Unknown actions are rejected.
 */
class ActionApplierModel extends Base
{
    public function apply(int $projectId, int $userId, array $action): void
    {
        $type = $action['action'] ?? '';
        $taskId = (int) ($action['task_id'] ?? 0);
        $params = $action['params'] ?? [];

        switch ($type) {
            case 'create_task':
                $this->taskCreationModel->create([
                    'project_id' => $projectId,
                    'title' => (string) ($params['title'] ?? 'Untitled'),
                    'description' => (string) ($params['description'] ?? ''),
                    'creator_id' => $userId,
                ]);
                break;

            case 'close_task':
                $this->taskStatusModel->close($taskId);
                break;

            case 'reopen_task':
                $this->taskStatusModel->open($taskId);
                break;

            case 'move_task':
                $task = $this->taskFinderModel->getById($taskId);
                if (! $task) {
                    throw new RuntimeException('move_task: task not found: ' . $taskId);
                }
                $this->taskPositionModel->movePosition(
                    $projectId,
                    $taskId,
                    (int) ($params['column_id'] ?? $task['column_id']),
                    (int) ($params['position'] ?? 1),
                    (int) ($task['swimlane_id'] ?? 0)
                );
                break;

            case 'assign_task':
                $this->taskModificationModel->update(['id' => $taskId, 'owner_id' => (int) ($params['owner_id'] ?? 0)]);
                break;

            case 'add_tag':
                $existing = array_values($this->taskTagModel->getList($taskId));
                $tags = array_unique(array_merge($existing, (array) ($params['tags'] ?? [])));
                $this->taskTagModel->save($projectId, $taskId, $tags);
                break;

            case 'set_due_date':
                $this->taskModificationModel->update(['id' => $taskId, 'date_due' => (string) ($params['date_due'] ?? '')]);
                break;

            case 'add_comment':
                $this->commentModel->create([
                    'task_id' => $taskId,
                    'user_id' => $userId,
                    'comment' => (string) ($params['comment'] ?? ''),
                ]);
                break;

            case 'update_task':
                $update = ['id' => $taskId];
                if (isset($params['title'])) {
                    $update['title'] = (string) $params['title'];
                }
                if (isset($params['description'])) {
                    $update['description'] = (string) $params['description'];
                }
                $this->taskModificationModel->update($update);
                break;

            case 'add_subtask':
                $this->subtaskModel->create([
                    'task_id' => $taskId,
                    'title' => (string) ($params['title'] ?? 'Subtask'),
                    'user_id' => (int) ($params['assignee_id'] ?? 0),
                ]);
                break;

            case 'link_tasks':
                $opposite = (int) ($params['opposite_task_id'] ?? 0);
                if ($opposite > 0) {
                    $label = strtolower((string) ($params['link_label'] ?? 'relates to'));
                    $linkId = 0;
                    foreach ($this->linkModel->getAll() as $link) {
                        if (strtolower((string) $link['label']) === $label) {
                            $linkId = (int) $link['id'];
                            break;
                        }
                    }
                    if ($linkId === 0) {
                        $linkId = 1; // Kanboard default: link id 1 = "relates to"
                    }
                    $this->taskLinkModel->create($taskId, $opposite, $linkId);
                }
                break;

            default:
                throw new RuntimeException('Unknown action: ' . $type);
        }
    }
}
