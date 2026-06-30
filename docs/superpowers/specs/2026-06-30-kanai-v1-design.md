# KanAI v1 — Design Spec

**Date:** 2026-06-30
**Status:** Approved scope, pending spec review
**Author:** k1bot2026 (with Claude)

---

## 1. Purpose

KanAI is a Kanboard plugin that connects a Kanboard instance to an LLM — a
**local** model first-class (Ollama / LM Studio / vLLM / any OpenAI-compatible
server), with **optional** external providers (Anthropic Claude, OpenAI). It
delivers two capabilities:

1. **Ask / RAG** — users ask questions about a project ("summarize open work",
   "what's blocked?", "what changed this week?") and get answers grounded in the
   project's tasks, descriptions, comments and subtasks. Read-only.
2. **Assistant** — the AI proposes maintenance actions (close stale tasks, move
   cards, add tags, set due dates, add comments). Every state-changing action is
   reviewed and approved by a human before it is applied through Kanboard's own
   models.

**Hard requirement:** it must work fully on a local LLM. External providers are
opt-in and an admin must be able to switch all external AI off (a hard,
server-enforced kill switch).

---

## 2. Scope (v1)

**In scope**
- Per-project Ask/RAG via SQL context-stuffing (no vector store).
- Assistant proposals via JSON-in-prompt, applied only after human approval.
- Local LLM as the default, always-available provider.
- External providers (Claude/OpenAI) gated behind a global admin kill switch
  (default OFF) **and** a per-project opt-in.
- Admin config page (providers, base URL, models, encrypted API keys).
- Persisted conversation history and pending proposals (DB tables).

**Out of scope (deferred to v2+)**
- Autonomous background agent / cron-driven cleanup.
- True embeddings RAG / vector search.
- Native function/tool-calling (we use JSON-in-prompt for max local-model
  compatibility).
- Cross-project / global assistant.
- Streaming token-by-token UI.

---

## 3. Decisions (locked)

| Topic | Decision |
|---|---|
| v1 scope | RAG (read-only) **+** assistant with human approval |
| External AI default | Local-only; external kill switch default **OFF**; per-project opt-in required |
| Action format | **JSON-in-prompt** (works with any model, incl. small local LLMs) |
| Persistence | **DB-backed** conversation history + pending proposals |
| Processing | **Synchronous** (user waits, with spinner); no cron in v1 |
| RAG | SQL/keyword/recency context-stuffing, scoped per project |

---

## 4. Architecture

Plugin namespace `Kanboard\Plugin\KanAI`, following the conventions already used
by the sibling TeamWork plugin (routes + ACL in `Plugin.php`, template hooks,
`getClasses()` autoload registration, `Schema/{Sqlite,Mysql,Postgres}.php`
migrations).

### 4.1 Folder layout

```
KanAI/
├── Plugin.php                      # routes, ACL, hooks, getClasses, metadata
├── Controller/
│   ├── ConfigController.php        # admin global settings (APP_ADMIN)
│   ├── AssistantController.php     # per-project: ask question -> answer (RAG)
│   └── ActionController.php        # render proposals; apply approved actions
├── Model/
│   ├── SettingsModel.php           # global + project settings; gating/provider resolution
│   ├── ContextBuilderModel.php     # RAG retriever: project data -> delimited text
│   └── ConversationModel.php       # messages + proposals persistence
├── LLM/
│   ├── LLMClientInterface.php      # complete(systemPrompt, messages): string
│   ├── OpenAICompatibleClient.php  # local (Ollama/LM Studio/vLLM) + OpenAI
│   ├── AnthropicClient.php         # Anthropic Messages adapter
│   └── LLMClientFactory.php        # selects provider per settings + gating
├── Security/
│   └── Crypto.php                  # encrypt/decrypt API keys at rest
├── Template/
│   ├── config/settings.php         # admin settings form
│   ├── config/sidebar.php          # admin settings sidebar link
│   ├── project/sidebar.php         # per-project sidebar link
│   ├── assistant/panel.php         # Q&A / chat UI
│   └── assistant/proposals.php     # proposed-actions review list
├── Asset/ kanai.js, kanai.css
├── Schema/ Sqlite.php, Mysql.php, Postgres.php
├── Locale/ en_US/translations.php  (nl_NL optional)
├── README.md, LICENSE, .gitignore, CLAUDE.md
```

### 4.2 LLM client abstraction

```
interface LLMClientInterface {
    // Returns the assistant's text reply. Throws on transport/provider error.
    public function complete(string $systemPrompt, array $messages, array $opts = []): string;
}
```

- **OpenAICompatibleClient** — POSTs `/v1/chat/completions` with
  `{model, messages, stream:false}`. System prompt is a `role:"system"` message.
  Same code path serves a local server (admin-set base URL, e.g.
  `http://localhost:11434/v1`, dummy key) and OpenAI (`https://api.openai.com/v1`,
  `Authorization: Bearer <key>`). Parses `choices[0].message.content`.
- **AnthropicClient** — POSTs `https://api.anthropic.com/v1/messages` with headers
  `x-api-key`, `anthropic-version: 2023-06-01`; body `{model, max_tokens, system,
  messages}`. System prompt is the top-level `system` field (not a message).
  Response `content` is an array of typed blocks; concatenate `type==="text"`
  blocks. Checks `stop_reason`.
- **LLMClientFactory** — reads `SettingsModel`, applies gating (§4.4), and returns
  the correct client. If a project/admin requests an external provider that gating
  forbids, the factory refuses server-side (does not silently fall back in a way
  that leaks data; returns a clear error to the controller).

HTTP via Kanboard's `$this->httpClient` (`postJson` / `getJson`); `isPrivateURL()`
available for SSRF checks. (Local base URLs are intentionally allowed to be
private; external provider hosts are fixed.)

### 4.3 Settings

**Global (admin) — stored via `ConfigModel` (`settings` table):**

| Option | Default | Notes |
|---|---|---|
| `kanai_external_enabled` | `0` | Hard kill switch for ALL external providers |
| `kanai_default_provider` | `local` | `local` / `openai` / `anthropic` |
| `kanai_local_base_url` | `http://localhost:11434/v1` | OpenAI-compatible endpoint |
| `kanai_local_model` | `llama3.1` | model name string |
| `kanai_openai_key` | `''` | **encrypted at rest**, masked in UI |
| `kanai_openai_model` | `gpt-4o-mini` | |
| `kanai_anthropic_key` | `''` | **encrypted at rest**, masked in UI |
| `kanai_anthropic_model` | `claude-sonnet-4-6` | |
| `kanai_max_context_tokens` | `8000` | RAG context budget (configurable per environment) |
| `kanai_max_output_tokens` | `1024` | per reply (configurable per environment) |
| `kanai_history_retention_days` | `0` | `0` = keep forever; `N` = auto-purge messages/proposals older than N days |

**Model-size resilience:** v1 is built to run on a small local test model yet
scale to a large production model with no code change — model name and both token
budgets are configuration, JSON parsing is strict-with-one-repair-retry (small
models produce messier JSON), and RAG truncation keeps the prompt within the
configured budget. Test and production differ only in `kanai_local_*` /
provider settings.

**Per project — stored via `projectMetadataModel`:**

| Key | Default | Notes |
|---|---|---|
| `kanai_enabled` | `0` (off) | AI features on for this project at all |
| `kanai_external_opt_in` | `0` (off) | allow external providers for this project |

### 4.4 Gating (data-egress safety) — enforced in code, not UI

```
canUseExternal(projectId) =
    kanai_external_enabled (global) == 1
    AND project.kanai_external_opt_in == 1

resolveProvider(projectId, requested):
    if project.kanai_enabled != 1: refuse (AI off for project)
    if requested is external and not canUseExternal(projectId): refuse
    else return client for (requested or local)
```

The kill switch is checked in `LLMClientFactory` **before** an external adapter is
instantiated — hiding the option in the UI is not sufficient.

### 4.5 RAG / ContextBuilder

`ContextBuilderModel::build(int $projectId, string $question, int $tokenBudget): array`
returns `['system' => ..., 'context' => ...]`.

- Sources: project meta + columns, tasks (`taskFinderModel`), descriptions,
  comments (`commentModel`), subtasks (`subtaskModel`).
- Selection: keyword overlap with the question + recency + open-status priority;
  truncate to `tokenBudget` (rough chars/4 heuristic), with an explicit
  "[truncated N items]" note rather than silent dropping.
- Formatting: project data wrapped in a clearly delimited block, prefixed with an
  instruction that it is **data, not instructions** (prompt-injection mitigation).
- `RetrieverInterface` boundary so an embeddings-backed retriever can drop in
  later without touching controllers.

### 4.6 Assistant actions (propose → approve → apply)

- The assistant system prompt asks for a strict JSON envelope:
  `{"answer": "...", "proposals": [{"action": "...", "task_id": N, "params": {...}, "reason": "..."}]}`.
- `ConversationModel` parses + validates the JSON (with one repair retry if the
  model returns malformed JSON), persists the answer and any proposals
  (`status='pending'`).
- **Principle: the AI can do what a standard project user can do** — nothing
  more. Actions are applied through Kanboard's own models as the approving user,
  so they are inherently bounded by that user's project permissions.
- v1 action types: `create_task`, `close_task`, `reopen_task`, `move_task`
  (to column), `assign_task`, `add_tag`, `set_due_date`, `add_comment`. Unknown
  actions are rejected at validation. The set maps 1:1 onto standard
  project-member capabilities and can grow as needed without architectural
  change.
- `proposals.php` lists pending proposals with checkboxes; the user approves a
  subset; `ActionController::apply` executes each via Kanboard models
  (`taskStatusModel`, `taskModificationModel`/`taskPositionModel`, `taskTagModel`,
  `commentModel`, …) as the approving user — so permissions and events fire
  normally. Applied/rejected proposals update `status`.
- Read-only answers (no proposals) render directly, no approval gate.

### 4.7 UI & hooks

- **Per project:** `template:project:sidebar` link "KanAI" →
  `AssistantController` page hosting the Q&A panel + pending proposals. A single
  project page (mirrors TeamWork's team page) — no floating global widget in v1.
- **Admin:** `template:config:sidebar` link → `ConfigController` settings page;
  restricted via `applicationAccessMap('ConfigController', '*', Role::APP_ADMIN)`.
- **Assets:** `template:layout:js` / `template:layout:css` for `kanai.js` /
  `kanai.css` (AJAX submit + spinner; render answers/proposals).

### 4.8 Database schema (new tables, versioned migrations)

```
kanai_messages (
  id            INTEGER PK,
  project_id    INTEGER NOT NULL  -> projects(id) ON DELETE CASCADE,
  user_id       INTEGER NOT NULL  -> users(id)    ON DELETE CASCADE,
  role          TEXT NOT NULL,        -- 'user' | 'assistant'
  content       TEXT NOT NULL,
  created_at    INTEGER NOT NULL DEFAULT 0
)
idx: (project_id, user_id, id)

kanai_proposals (
  id            INTEGER PK,
  project_id    INTEGER NOT NULL  -> projects(id) ON DELETE CASCADE,
  user_id       INTEGER NOT NULL  -> users(id)    ON DELETE CASCADE,
  message_id    INTEGER DEFAULT NULL -> kanai_messages(id) ON DELETE SET NULL,
  payload       TEXT NOT NULL,        -- JSON array of proposed actions
  status        TEXT NOT NULL DEFAULT 'pending',  -- pending|applied|rejected
  created_at    INTEGER NOT NULL DEFAULT 0
)
idx: (project_id, status)
```

Provided in all three `Schema/` dialects with a `VERSION` constant, matching the
TeamWork migration pattern.

**Persistence lifecycle:** conversation messages and proposals are stored
**permanently by default** (audit trail + multi-turn memory). Controls:
- Per-project **"Clear conversation"** action (deletes that project's
  `kanai_messages` + `kanai_proposals`).
- Optional global retention (`kanai_history_retention_days`): when > 0, rows
  older than N days are purged. (Purge runs opportunistically on assistant use in
  v1; a cron command can take this over in v2.)
- Rows cascade-delete with their project/user, so removing a project removes its
  AI history automatically.

### 4.9 Security

- API keys encrypted at rest by `Security/Crypto.php` using a key derived from
  Kanboard's `config.php` application secret (or a dedicated `KANAI_SECRET`).
  `ConfigModel` stores plaintext, so encryption is our responsibility. Keys are
  masked in the admin UI (last 4 chars), never emitted to templates/JS.
- External egress only when gating allows; only scoped project text is sent;
  capped by `kanai_max_context_tokens`. Local path may be more permissive (data
  stays on-prem).
- Prompt injection: project content is untrusted; system instructions live in the
  dedicated system field, data is delimited, and **no model output triggers an
  irreversible action without human approval** (§4.6).

---

## 5. Workspace restructure (implementation phase 0)

KanAI lives in its own Git repo, separate from TeamWork. The implementation plan
starts by restructuring `/Volumes/ProjectData/KanBoard` from "the TeamWork repo"
into a **workspace container**:

```
KanBoard/                         # workspace (NOT a git repo)
├── README.md                     # workspace overview (which repo is where)
├── AGENTS.md / CLAUDE.md         # AI-agent guide: per-plugin boundaries
├── docs/                         # shared docs (this spec lives here)
├── kanboard/                     # (symlink target / instructions for existing install)
├── TeamWork/                     # existing repo kanboard-plugin-teamwork (.git moves here)
└── KanAI/                        # new repo kanboard-plugin-kanai
```

- Move all current TeamWork files (incl. `.git`, `.gitignore`, `.claude`) into
  `KanBoard/TeamWork/`; verify remote, branches and clean tree afterward. Do not
  move `docs/` (workspace-level).
- Scaffold `KanBoard/KanAI/` as a minimal valid plugin, `git init`, first commit.
  GitHub repo `kanboard-plugin-kanai` created by the user (or via `gh`) — nothing
  pushed without explicit go-ahead.
- Workspace `README.md` + `AGENTS.md`/`CLAUDE.md`: which folder maps to which
  GitHub repo, the rule that an agent works inside one plugin folder (never
  cross-plugin), and how to symlink an existing Kanboard install
  (`kanboard/plugins/KanAI -> ../../KanAI`).
- No Kanboard download (user has an install elsewhere) — only structure +
  symlink instructions.

---

## 6. Plugin metadata

- Name: `KanAI`
- Version: `0.1.0`
- Author: `k1bot2026`
- `getCompatibleVersion()`: `>=1.2.46` (match TeamWork baseline)
- Homepage: `https://github.com/k1bot2026/kanboard-plugin-kanai`

---

## 7. Open questions / risks

- Exact Kanboard model/method names for applying actions (e.g.
  `taskPositionModel::movePosition` signature) to be confirmed against the
  installed Kanboard version during implementation.
- Source of the encryption key (`config.php` secret vs dedicated constant) to be
  confirmed during implementation.
- Token-budget estimation is heuristic (chars/4); acceptable for v1, refine if
  truncation proves too aggressive.
