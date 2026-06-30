<?php

namespace Kanboard\Plugin\KanAI\Model;

/**
 * Server-defined assistant "skills" — one-click presets surfaced in the UI.
 * Each skill's instruction is trusted text (not user input) sent through the
 * normal ask flow. No Kanboard dependency.
 */
class AssistantSkills
{
    /** @return array<int,array{key:string,label:string,instruction:string}> */
    public static function all(): array
    {
        return [
            ['key' => 'summary', 'label' => '📋 Project summary',
             'instruction' => 'Give a concise status summary of this project: progress per column, what is in progress, recent changes, and any risks. Read-only — no proposals.'],
            ['key' => 'next', 'label' => '⏭️ What\'s next?',
             'instruction' => 'Recommend what to work on next: pick the few highest-leverage open tasks based on column position, priority, due dates, and blockers, and briefly explain why each. Read-only.'],
            ['key' => 'board_health', 'label' => '📊 Board health',
             'instruction' => 'Assess this board\'s health: flag work-in-progress overload, columns acting as bottlenecks, and tasks sitting too long without activity. Keep it concise and propose fixes only where clearly helpful.'],
            ['key' => 'standup', 'label' => '📝 Stand-up notes',
             'instruction' => 'Write concise stand-up notes for this project: what was recently completed (done), what is in progress (doing), and what is blocked or at risk. Read-only.'],
            ['key' => 'cleanup', 'label' => '🧹 Cleanup suggestions',
             'instruction' => 'Find tasks that need cleanup: done-but-not-closed, overdue, stale (no recent activity), untagged, or unassigned. Propose concrete actions to fix each, with a short reason.'],
            ['key' => 'risks', 'label' => '🚧 Risks & blockers',
             'instruction' => 'Identify blocked, at-risk, and overdue tasks in this project and explain why each is stuck. Propose actions only where clearly helpful.'],
            ['key' => 'workload', 'label' => '🧑‍🤝‍🧑 Workload',
             'instruction' => 'Show how work is distributed across assignees: who has the most open or overdue tasks, and where it looks unbalanced. Propose reassignments only where clearly helpful.'],
            ['key' => 'organize', 'label' => '🗂️ Organize',
             'instruction' => 'Review this project\'s tasks and propose better organization: tags, the right column, an owner, or splitting large tasks into subtasks. Propose actions.'],
            ['key' => 'enrich', 'label' => '✨ Enrich tasks',
             'instruction' => 'Find thin or unclear tasks and propose enrichment: a clearer description (update_task), useful subtasks (add_subtask), or links to related tasks (link_tasks). Propose actions.'],
            ['key' => 'help', 'label' => '🙋 Help & explain',
             'instruction' => 'Act as a guide for a user new to this project: explain what it is, how the board is organized, and answer practical how-do-I questions. Read-only.'],
        ];
    }

    public static function instructionFor(string $key): ?string
    {
        foreach (self::all() as $s) {
            if ($s['key'] === $key) {
                return $s['instruction'];
            }
        }
        return null;
    }
}
