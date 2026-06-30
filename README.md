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

`0.1.0` — scaffold. Feature implementation tracked in `docs/superpowers/`.

## Install

Symlink or copy this folder into your Kanboard `plugins/` directory as `KanAI`:

    ln -s /absolute/path/to/KanAI <kanboard>/plugins/KanAI

Then open **Settings → Plugins** to confirm KanAI is listed.

## License

MIT — see `LICENSE`.
