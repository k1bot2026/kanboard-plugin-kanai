# KanAI — AI assistant & project Q&A for Kanboard

KanAI connects a Kanboard instance to an LLM. It works **fully on a local LLM**
(Ollama / LM Studio / vLLM / any OpenAI-compatible server) and can **optionally**
use external providers (Anthropic Claude, OpenAI). An administrator can switch all
external AI off with a single, server-enforced kill switch.

## Capabilities

- **Shared chat per project** — a KanAI tab next to Overview / Board / List opens
  a chat panel with multiple conversations (create, rename, delete). Conversations
  are shared with every project member, so a colleague can pick up where you left
  off; each message shows the sender's avatar and name.
- **Ask / RAG** — ask questions about the project and get answers grounded in its
  tasks, descriptions, comments and subtasks. Read-only.
- **Quick actions** — one-click presets: project summary, what's next, board
  health, stand-up notes, cleanup suggestions, risks & blockers, workload,
  organize, enrich tasks, and help & explain.
- **Assistant with approval** — the AI proposes maintenance actions (create /
  update / close / reopen / move / assign tasks, tags, due dates, comments,
  subtasks, task links). Every state-changing action is reviewed and approved by
  a human before it is applied through Kanboard's own models, as the approving
  user.

## Status

`1.1.0` — shared multi-conversation chat, 10 quick actions, 11 approval-gated
actions. Local LLM default; external providers admin-gated. Verified end-to-end
against Ollama (chat, proposals, apply flow, multi-user sharing).

## Configure

- **Global:** Settings → KanAI (admin only) — LLM endpoint/model, API keys,
  external-provider kill switch, token limits, request timeout, history retention.
- **Per-project:** open a project → **KanAI Settings** in the sidebar (project
  managers only) — enable KanAI for that project and opt in to external AI
  providers. The KanAI tab and features appear only when enabled.

Recommended local setup: Ollama with a ~7B model (e.g. `qwen2.5-coder:7b`)
answers in seconds; larger models give richer answers but take longer. When
Kanboard runs in Docker, use `http://host.docker.internal:11434/v1` as the base
URL.

### Automatic daily digest (optional)

Projects can opt in to an autonomous digest (project settings → "Enable the
automatic daily digest"): KanAI starts a fresh conversation with a compact
status/cleanup/risks report and proposals for members to review. Schedule the
command from cron, e.g. daily at 07:00:

    0 7 * * * php /path/to/kanboard/cli kanai:digest >/dev/null 2>&1

(For Docker: `docker exec <container> php /var/www/app/cli kanai:digest`.)

### Production note: encryption key

KanAI encrypts external-provider API keys at rest. For production, set a stable
secret in your Kanboard `config.php`:

    define('KANAI_SECRET', 'a-long-random-string');

If unset, KanAI generates a per-install key and stores it in the database
(weaker, but keys are never stored in plaintext).

## Install

Symlink or copy this folder into your Kanboard `plugins/` directory as `KanAI`:

    ln -s /absolute/path/to/KanAI <kanboard>/plugins/KanAI

For Docker, bind-mount the folder to `/var/www/app/plugins/KanAI`. Then open
**Settings → Plugins** to confirm KanAI is listed, and enable it per project.

Requirements: Kanboard >= 1.2.46, PHP >= 7.4 with cURL and OpenSSL.

## Development

Pure-logic classes are unit-tested standalone:

    php composer.phar install
    ./vendor/bin/phpunit

Design docs and build plans live in `docs/superpowers/`.

## License

MIT — see `LICENSE`.
