# KanAI — AI assistant & project Q&A for Kanboard

KanAI connects a Kanboard instance to an LLM. It works **fully on a local LLM**
(Ollama / LM Studio / vLLM / any OpenAI-compatible server) and can **optionally**
use external providers (Anthropic Claude, OpenAI). An administrator can switch all
external AI off with a single, server-enforced kill switch.

## Capabilities (v1)

- **Ask / RAG** — ask questions about a project and get answers grounded in its
  tasks, descriptions, comments and subtasks. Read-only.
- **Assistant** — the AI proposes maintenance actions (create/close/move/tag/
  assign tasks, set due dates, add comments). Every state-changing action is
  reviewed and approved by a human before it is applied through Kanboard's own
  models, as the approving user.

## Status

`1.0.0` — Ask/RAG + approval-gated assistant. Local LLM default; external providers admin-gated.

## Configure

- **Global:** Settings → KanAI (admin only) — set LLM endpoint/key, enable/disable external providers.
- **Per-project:** open a project → **KanAI Settings** in the sidebar (project managers only) — enable KanAI for that project and opt in to external AI providers.

### Production note: encryption key

KanAI encrypts external-provider API keys at rest. For production, set a stable
secret in your Kanboard `config.php`:

    define('KANAI_SECRET', 'a-long-random-string');

If unset, KanAI generates a per-install key and stores it in the database
(weaker, but keys are never stored in plaintext).

## Install

Symlink or copy this folder into your Kanboard `plugins/` directory as `KanAI`:

    ln -s /absolute/path/to/KanAI <kanboard>/plugins/KanAI

Then open **Settings → Plugins** to confirm KanAI is listed.

## License

MIT — see `LICENSE`.
