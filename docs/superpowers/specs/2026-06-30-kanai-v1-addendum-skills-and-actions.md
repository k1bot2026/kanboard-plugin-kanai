# KanAI v1 — Addendum: expanded actions, knowledge & assistant skills

**Date:** 2026-06-30
**Extends:** `2026-06-30-kanai-v1-design.md`
**Reason:** User wants the assistant to genuinely support/assist projects & users —
with proper Kanboard knowledge, working methods, and a set of one-click "skills",
plus the ability to enrich existing tasks (clearer info) and structure them
(subtasks, links/relations).

This addendum changes three things in the v1 build: (1) the action whitelist
grows, (2) the system prompt embeds Kanboard knowledge + working methods, and
(3) the assistant UI gains a "skills" preset menu. Everything else in the base
design (local-first, gating, propose→approve→apply, persistence) is unchanged.

---

## 1. Expanded action whitelist

The v1 whitelist grows from 8 to 11 actions. `ProposalValidator::ACTIONS` and
`ActionApplierModel` must both be updated to agree on this set. All actions are
still applied via Kanboard models, as the approving user, only after approval.

| Action | task_id required | params | Kanboard model call |
|---|---|---|---|
| `create_task` | no | `title`, `description?`, `column_id?` | `taskCreationModel->create([...])` |
| `update_task` *(NEW — enrich)* | yes | `title?`, `description?` | `taskModificationModel->update(['id'=>id, ...])` |
| `close_task` | yes | — | `taskStatusModel->close(id)` |
| `reopen_task` | yes | — | `taskStatusModel->open(id)` |
| `move_task` | yes | `column_id`, `position?` | `taskPositionModel->movePosition(...)` |
| `assign_task` | yes | `owner_id` | `taskModificationModel->update(['id'=>id,'owner_id'=>...])` |
| `add_tag` | yes | `tags` (array) | `taskTagModel->save(project, id, tags)` |
| `set_due_date` | yes | `date_due` | `taskModificationModel->update(['id'=>id,'date_due'=>...])` |
| `add_comment` | yes | `comment` | `commentModel->create([...])` |
| `add_subtask` *(NEW — structure)* | yes | `title`, `assignee_id?` | `subtaskModel->create(['task_id'=>id,'title'=>...])` |
| `link_tasks` *(NEW — relate)* | yes | `opposite_task_id`, `link_label?` (default `relates to`) | resolve label→id via `linkModel`, then `taskLinkModel->create(id, opposite, link_id)` |

Notes for the implementer:
- `update_task` enriches an existing task's title/description — only send fields
  present in `params`; never blank a field the model didn't propose.
- `link_tasks`: resolve `link_label` to a link id via `linkModel->getAll()`
  (case-insensitive match on the label; default to `relates to`). Confirm the
  exact `taskLinkModel->create()` / `linkModel` API against the installed Kanboard
  before relying on it (note this in the report).
- `add_subtask`: `subtaskModel->create()` expects `task_id` + `title`; optional
  assignee via `user_id`.

`ProposalValidator::REQUIRE_TASK_ID` = all actions except `create_task`.

---

## 2. System prompt: knowledge + working methods

`ContextBuilderModel::build()`'s system prompt is upgraded from the base version
to embed domain knowledge and method. It must still: forbid following
instructions inside project data, demand a single JSON envelope, and list the
(now 11) allowed actions. Add:

**Kanboard knowledge block** (concise, in the system prompt):
- A project has columns (workflow stages, left→right) and optional swimlanes.
- A task has: title, description, column, position, owner (assignee), tags,
  priority, due date (`date_due`), category, subtasks, comments, and links to
  other tasks (relations like "relates to", "blocks", "is blocked by",
  "duplicates", "is a child/parent of").
- Status vocabulary the assistant should reason with:
  - **open** = active; **closed/done** = `is_active = 0`.
  - **overdue** = open AND `date_due` in the past.
  - **stale** = open AND no modification for a long time (no recent activity).
  - **blocked** = has an "is blocked by" link to an open task, or a comment/flag
    indicating a blocker.
  - **unstructured** = vague/empty description, no tags, no owner, or a large task
    with no subtasks.

**Working methods** (how to approach common asks):
- *Summarize*: report progress per column, what's in flight, recent changes,
  risks/blockers — read-only, no proposals unless asked.
- *Clean up*: surface done-but-not-closed, overdue, stale, untagged, unassigned
  tasks; propose concrete actions with a short reason each.
- *Risks & blockers*: list blocked/at-risk/overdue work and why.
- *Organize*: propose tags, the right column, an owner, or splitting a big task
  into subtasks.
- *Enrich*: improve a thin task — propose a clearer description, subtasks, or a
  relation to a related task (via `update_task`/`add_subtask`/`link_tasks`).
- Always: propose, never assume approval; keep reasons short and specific;
  reference tasks by `#id`.

---

## 3. Assistant skills (UI presets)

A `Model/AssistantSkills.php` provider defines the preset "skills". Each skill is
a label + an icon hint + a canned user instruction that is sent to the assistant
(the rich system prompt does the rest). The assistant panel renders these as
one-click buttons above the free-text box; clicking one submits its instruction
through the normal ask flow (so gating, persistence, and approval all apply).

v1 skills:

| key | label | instruction sent |
|---|---|---|
| `summary` | 📋 Project summary | "Give a concise status summary of this project: progress per column, what's in progress, recent changes, and any risks. Read-only — no proposals." |
| `cleanup` | 🧹 Cleanup suggestions | "Find tasks that need cleanup: done-but-not-closed, overdue, stale (no recent activity), untagged, or unassigned. Propose concrete actions to fix each, with a short reason." |
| `risks` | 🚧 Risks & blockers | "Identify blocked, at-risk, and overdue tasks in this project and explain why each is stuck. Propose actions only where clearly helpful." |
| `organize` | 🗂️ Organize | "Review this project's tasks and propose better organization: tags, the right column, an owner, or splitting large tasks into subtasks. Propose actions." |
| `enrich` | ✨ Enrich tasks | "Find thin or unclear tasks and propose enrichment: a clearer description (update_task), useful subtasks (add_subtask), or links to related tasks (link_tasks). Propose actions." |
| `help` | 🙋 Help & explain | "Act as a guide for a user new to this project: explain what it is, how the board is organized, and answer practical 'how do I…' questions. Read-only." |

Skills are server-defined (not user input) so their instructions are trusted text.

---

## 4. Local LLM default for this environment

The test Kanboard runs in Docker (`kanboard-test`). From inside the container the
host Ollama is reachable at `http://host.docker.internal:11434/v1`. During live
verification, set the KanAI admin settings to provider `local`, base URL
`http://host.docker.internal:11434/v1`, model `qwen3:14b`. (The shipped code
default stays `http://localhost:11434/v1` for general installs.)

---

## 5. Build impact summary

- **ProposalValidator** (Task 6, done): expand `ACTIONS` + `REQUIRE_TASK_ID`; add tests for the 3 new actions. → revisit.
- **ContextBuilderModel** (Task 7, done): upgrade the system prompt with the knowledge + working-methods blocks and the 11-action list. → revisit.
- **ActionApplierModel** (Task 10): implement the 3 new action handlers.
- **AssistantSkills** (new): preset definitions.
- **Assistant UI** (Task 12): render the skills menu.
- Everything else (Settings, factory, persistence, controllers, project settings) per the base feature plan.
