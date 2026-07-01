# Changelog

## 1.4.0 — 2026-07-02

### Added
- Reworked admin settings screen: sections with inline help, provider status
  badges, and a **Test connection** button per provider. Testing the local LLM
  discovers the available models and offers them as suggestions for the model
  field; external tests validate the API key without saving.
- Rate limiting: `kanai_rate_limit_per_hour` (default 30, 0 = unlimited) caps
  questions per user per hour; exceeded requests are refused before any LLM
  call (HTTP 429 on AJAX).
- Observability: assistant messages record which model answered and how long it
  took (schema v4), shown subtly under each answer (e.g. "qwen2.5-coder:7b · 8.3s").

### Fixed
- The admin settings page fataled on render since 1.0.0 (`checkMenuSelection`
  received an array in the config sidebar); found by loading the page over real
  HTTP for the first time.
- `kanai_request_timeout` is now clamped to a sane minimum (10s) on save.

## 1.3.0 — 2026-07-01

### Added
- AJAX chat: asking a question or clicking a quick action no longer reloads the
  page — your message appears instantly, a thinking indicator shows while the
  model works, and the answer + proposals slot into the thread in place
  (classic form submit remains as fallback).
- Autonomous daily digest: a `kanai:digest` console command (run from cron)
  generates a status/cleanup/risks digest with proposals for every project that
  opted in via the new project setting. Digest messages render as
  "KanAI (automatic)" with a 🤖 avatar.

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
