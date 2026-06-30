<?php

namespace Kanboard\Plugin\KanAI\LLM;

use RuntimeException;

/**
 * Parses the assistant's JSON envelope {answer, proposals[]} and validates each
 * proposed action against the v1 whitelist. Tolerant of models that wrap JSON in
 * prose or code fences. No Kanboard dependency.
 */
class ProposalValidator
{
    /** Actions a standard project user can perform. */
    public const ACTIONS = [
        'create_task', 'update_task', 'close_task', 'reopen_task', 'move_task',
        'assign_task', 'add_tag', 'set_due_date', 'add_comment',
        'add_subtask', 'link_tasks',
    ];

    /** Actions that operate on an existing task and therefore require task_id. */
    private const REQUIRE_TASK_ID = [
        'update_task', 'close_task', 'reopen_task', 'move_task', 'assign_task',
        'add_tag', 'set_due_date', 'add_comment', 'add_subtask', 'link_tasks',
    ];

    public static function parse(string $raw): array
    {
        $json = self::extractJsonObject($raw);
        $data = json_decode($json, true);
        if (! is_array($data)) {
            throw new RuntimeException('Assistant response was not valid JSON.');
        }
        return [
            'answer' => isset($data['answer']) && is_string($data['answer']) ? $data['answer'] : '',
            'proposals' => self::validateProposals($data['proposals'] ?? []),
        ];
    }

    public static function validateProposals($proposals): array
    {
        if (! is_array($proposals)) {
            return [];
        }
        $clean = [];
        foreach ($proposals as $p) {
            if (! is_array($p) || ! isset($p['action']) || ! in_array($p['action'], self::ACTIONS, true)) {
                continue;
            }
            if (in_array($p['action'], self::REQUIRE_TASK_ID, true) && empty($p['task_id'])) {
                continue;
            }
            $clean[] = [
                'action' => $p['action'],
                'task_id' => isset($p['task_id']) ? (int) $p['task_id'] : null,
                'params' => isset($p['params']) && is_array($p['params']) ? $p['params'] : [],
                'reason' => isset($p['reason']) && is_string($p['reason']) ? $p['reason'] : '',
            ];
        }
        return $clean;
    }

    /** Extract the first balanced top-level {...} object from arbitrary text. */
    private static function extractJsonObject(string $raw): string
    {
        $start = strpos($raw, '{');
        if ($start === false) {
            throw new RuntimeException('No JSON object found in assistant response.');
        }
        $depth = 0;
        $len = strlen($raw);
        for ($i = $start; $i < $len; $i++) {
            if ($raw[$i] === '{') {
                $depth++;
            } elseif ($raw[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($raw, $start, $i - $start + 1);
                }
            }
        }
        throw new RuntimeException('Unbalanced JSON in assistant response.');
    }
}
