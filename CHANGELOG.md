# Changelog

## 1.2.0 — 2026-07-01

### Added
- Markdown rendering of assistant answers via Kanboard's own safe renderer
  (headings, lists, bold, tables, code). Task references like `#9` become
  clickable task links automatically.
- Dutch translation (nl_NL) of the full UI, including the quick-action labels.
  Plugin translations are now loaded on startup (`onStartup` + `Translator::load`).
- Schema v3: index on `kanai_conversations.updated_at` for the retention purge.

## 1.1.0 — 2026-07-01

### Added
- Shared, multi-conversation chat per project (schema v2: `kanai_conversations`);
  conversations are visible to and continuable by every project member.
- Conversation management: new chat, switch, rename (inline), delete.
- Per-message sender avatar + name via Kanboard's avatar helper.
- KanAI tab in the project view switcher (next to Overview / Board / List),
  shown only when KanAI is enabled for the project.
- Four project-management quick actions: What's next?, Board health,
  Stand-up notes, Workload (10 presets total).
- Theme-aware chat UI using Kanboard's CSS variables (light/dark/custom),
  Enter-to-send composer, thinking indicator during slow model calls.
- Configurable LLM request timeout (`kanai_request_timeout`, default 120s) with
  a dedicated cURL transport (Kanboard's HTTP client caps at 10s).

### Fixed
- POST actions now read fields via `Request::getValues()` (which auto-validates
  the CSRF token); previously they read the query string and always failed with
  "Access forbidden".
- The PHP session lock is released before the LLM call, so Kanboard stays
  responsive while KanAI is thinking.
- Container registration of the LLM client factory (`LlmClientFactory`) so
  `$this->llmClientFactory` resolves.

### Changed
- Pre-1.1 per-user chat history is discarded by the schema v2 migration
  (conversations became shared per project).

## 1.0.0 — 2026-06-30

- Initial release: project Q&A (RAG via SQL context-stuffing), approval-gated
  assistant with an 11-action whitelist, local-LLM-first provider model with a
  global external kill switch + per-project opt-in, encrypted API keys at rest,
  admin & per-project settings pages, persisted history with retention.
