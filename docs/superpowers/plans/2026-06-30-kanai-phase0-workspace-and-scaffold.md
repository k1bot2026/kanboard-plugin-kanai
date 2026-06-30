# KanAI Phase 0 — Workspace Restructure & Plugin Scaffold — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn `/Volumes/ProjectData/KanBoard` from "the TeamWork plugin repo" into a workspace container holding TeamWork and a freshly scaffolded KanAI as two separate Git repos, with shared docs, an AI-agent guide, and a standalone PHPUnit harness — ending with a minimal-but-valid KanAI plugin that loads in Kanboard.

**Architecture:** `KanBoard/` becomes a non-git workspace. The existing TeamWork repo (history + `kanboard-plugin-teamwork` remote) moves intact into `KanBoard/TeamWork/`. A new `KanBoard/KanAI/` is a fresh repo (`kanboard-plugin-kanai`) scaffolded to Kanboard's plugin conventions (mirroring TeamWork's `Plugin.php` structure). Shared workspace docs explain the layout and the per-plugin boundary rule for AI agents.

**Tech Stack:** PHP 7.4+/8.x (Kanboard plugin), Composer + PHPUnit ^9.6 (dev-only, for pure-logic unit tests), Git, zsh.

## Global Constraints

- Plugin namespace: `Kanboard\Plugin\KanAI` (exact case).
- KanAI version: `0.1.0`; `getCompatibleVersion()`: `>=1.2.46`; author `k1bot2026`.
- KanAI GitHub repo: `https://github.com/k1bot2026/kanboard-plugin-kanai` (created by user; **nothing is pushed without explicit go-ahead**).
- The workspace root `KanBoard/` must NOT be a git repo after restructure.
- `KanBoard/docs/` is workspace-level and must NOT be moved into `TeamWork/`.
- Do not modify any TeamWork source/README during the move — only relocate it.
- No Kanboard download — the user has an install elsewhere; we only create structure + symlink instructions.
- Each plugin is its own repo; an AI agent works inside ONE plugin folder, never cross-plugin.

---

## File Structure (created/modified in this plan)

- Move: every current top-level entry under `KanBoard/` **except** `docs/` → `KanBoard/TeamWork/`
- Create: `KanBoard/KanAI/Plugin.php` — minimal valid plugin entrypoint
- Create: `KanBoard/KanAI/README.md`, `LICENSE`, `.gitignore`, `CLAUDE.md`
- Create: `KanBoard/KanAI/composer.json`, `phpunit.xml`, `tests/bootstrap.php`, `tests/HarnessSmokeTest.php`
- Create: `KanBoard/KanAI/docs/superpowers/specs/2026-06-30-kanai-v1-design.md` (relocated), `…/plans/` (relocated)
- Create: `KanBoard/README.md` — workspace overview
- Create: `KanBoard/AGENTS.md` + `KanBoard/CLAUDE.md` — AI-agent guide
- Create: `KanBoard/kanboard/README.md` — symlink instructions placeholder (the real install stays external)

---

### Task 1: Restructure — move TeamWork into its own subfolder

**Files:**
- Move: all current `KanBoard/*` and `KanBoard/.*` entries except `docs/` → `KanBoard/TeamWork/`

**Interfaces:**
- Produces: `KanBoard/TeamWork/` containing the intact TeamWork repo (`.git`, remote `kanboard-plugin-teamwork`, branch `main`); `KanBoard/` no longer a git repo; `KanBoard/docs/` preserved at workspace root.

- [ ] **Step 1: Snapshot current git state (for later verification)**

Run from `/Volumes/ProjectData/KanBoard`:
```bash
git rev-parse HEAD && git remote get-url origin && git status --porcelain
```
Expected: HEAD `7c9ed7f...`, origin `https://github.com/k1bot2026/kanboard-plugin-teamwork.git`, empty porcelain (clean).

- [ ] **Step 2: Create the TeamWork subfolder**

```bash
mkdir -p /Volumes/ProjectData/KanBoard/TeamWork
```

- [ ] **Step 3: Move every entry except `docs` and `TeamWork` into `TeamWork/`**

Run from `/Volumes/ProjectData/KanBoard` (zsh):
```bash
setopt local_options null_glob dot_glob
for f in /Volumes/ProjectData/KanBoard/*(N) /Volumes/ProjectData/KanBoard/.*(N); do
  base=${f:t}
  case "$base" in
    .|..|TeamWork|docs) continue ;;
  esac
  mv "$f" /Volumes/ProjectData/KanBoard/TeamWork/
done
```

- [ ] **Step 4: Verify the workspace root now contains only TeamWork, docs (and nothing git)**

```bash
ls -A /Volumes/ProjectData/KanBoard
```
Expected: exactly `TeamWork` and `docs` (no `.git`, no `Plugin.php`).

- [ ] **Step 5: Verify the TeamWork repo is intact inside its new home**

```bash
git -C /Volumes/ProjectData/KanBoard/TeamWork rev-parse HEAD
git -C /Volumes/ProjectData/KanBoard/TeamWork remote get-url origin
git -C /Volumes/ProjectData/KanBoard/TeamWork status --porcelain
git -C /Volumes/ProjectData/KanBoard/TeamWork branch
```
Expected: same HEAD `7c9ed7f...`, same origin URL, clean porcelain, branch `* main`. **If any differ, STOP — do not proceed.**

- [ ] **Step 6: Confirm the workspace root is no longer a git repo**

```bash
git -C /Volumes/ProjectData/KanBoard rev-parse --is-inside-work-tree 2>&1 || echo "OK: not a repo"
```
Expected: prints `OK: not a repo` (or a "not a git repository" error). No commit here — the workspace is intentionally not versioned.

---

### Task 2: Scaffold the KanAI plugin and initialize its repo

**Files:**
- Create: `KanBoard/KanAI/Plugin.php`
- Create: `KanBoard/KanAI/.gitignore`, `LICENSE`, `README.md`, `CLAUDE.md`
- Move: `KanBoard/docs/superpowers/specs/2026-06-30-kanai-v1-design.md` and this plan → `KanBoard/KanAI/docs/superpowers/…`

**Interfaces:**
- Produces: a Kanboard-loadable plugin at `KanBoard/KanAI/` exposing `Kanboard\Plugin\KanAI\Plugin` with name `KanAI`, version `0.1.0`; a fresh git repo with one commit.

- [ ] **Step 1: Create `KanBoard/KanAI/Plugin.php`**

```php
<?php

namespace Kanboard\Plugin\KanAI;

use Kanboard\Core\Plugin\Base;

class Plugin extends Base
{
    public function initialize(): void
    {
        // v1 features (settings, LLM clients, RAG, assistant, actions, UI) are
        // added by the KanAI v1 feature plan. The scaffold loads cleanly with
        // no routes/hooks so it can be installed and verified in isolation.
    }

    public function getClasses(): array
    {
        return [];
    }

    public function getPluginName(): string
    {
        return 'KanAI';
    }

    public function getPluginDescription(): string
    {
        return 'AI assistant & project Q&A (RAG) for Kanboard — local LLM first, optional external providers';
    }

    public function getPluginAuthor(): string
    {
        return 'k1bot2026';
    }

    public function getPluginVersion(): string
    {
        return '0.1.0';
    }

    public function getCompatibleVersion(): string
    {
        return '>=1.2.46';
    }

    public function getPluginHomepage(): string
    {
        return 'https://github.com/k1bot2026/kanboard-plugin-kanai';
    }
}
```

- [ ] **Step 2: Create `KanBoard/KanAI/.gitignore`**

```gitignore
.DS_Store
/vendor/
/.phpunit.result.cache
composer.lock
```

- [ ] **Step 3: Create `KanBoard/KanAI/LICENSE`**

Copy TeamWork's license verbatim so both repos carry the same terms:
```bash
cp /Volumes/ProjectData/KanBoard/TeamWork/LICENSE /Volumes/ProjectData/KanBoard/KanAI/LICENSE
```

- [ ] **Step 4: Create `KanBoard/KanAI/README.md`**

```markdown
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
```

- [ ] **Step 5: Create `KanBoard/KanAI/CLAUDE.md` (per-plugin agent guide)**

```markdown
# KanAI — guidance for AI agents

You are working inside the **KanAI** Kanboard plugin (repo
`kanboard-plugin-kanai`). Stay within this folder — never edit the sibling
`../TeamWork` plugin from here.

## Conventions (follow the sibling TeamWork plugin)

- Entry point `Plugin.php` (`Kanboard\Plugin\KanAI`): register routes, ACL
  (`applicationAccessMap` for admin-only, `projectAccessMap` for per-project),
  template hooks (`$this->template->hook->attachCallable`), and classes via
  `getClasses()`.
- Controllers extend `Kanboard\Controller\BaseController`.
- DB migrations live in `Schema/{Sqlite,Mysql,Postgres}.php` with a `VERSION`
  constant and `version_N(PDO $pdo)` functions.
- Project settings via `projectMetadataModel`; global/admin settings via
  `configModel`. Outbound HTTP via `$this->httpClient`.

## Design

The authoritative design is `docs/superpowers/specs/2026-06-30-kanai-v1-design.md`.
Local LLM is first-class; external providers are gated behind a global admin
kill switch (default OFF) **and** a per-project opt-in, enforced in code.

## Testing

Pure-logic classes (no Kanboard dependency) are unit-tested with PHPUnit:
`composer install && ./vendor/bin/phpunit`. Kanboard-integrated pieces
(controllers, templates, hooks, schema) are verified by loading the plugin into a
running Kanboard.
```

- [ ] **Step 6: Relocate the KanAI spec and plan into the KanAI repo**

```bash
mkdir -p /Volumes/ProjectData/KanBoard/KanAI/docs/superpowers/specs \
         /Volumes/ProjectData/KanBoard/KanAI/docs/superpowers/plans
mv /Volumes/ProjectData/KanBoard/docs/superpowers/specs/*kanai* \
   /Volumes/ProjectData/KanBoard/KanAI/docs/superpowers/specs/ 2>/dev/null || true
mv /Volumes/ProjectData/KanBoard/docs/superpowers/plans/*kanai* \
   /Volumes/ProjectData/KanBoard/KanAI/docs/superpowers/plans/ 2>/dev/null || true
```
This relocates the KanAI design spec AND both KanAI plans (phase-0 and the v1
feature build) into the KanAI repo before the first commit, so they're versioned
with the plugin they describe.

- [ ] **Step 7: Initialize the repo and make the first commit**

```bash
cd /Volumes/ProjectData/KanBoard/KanAI
git init -b main
git add -A
git commit -m "chore: scaffold KanAI plugin (0.1.0)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

- [ ] **Step 8: Verify the scaffold**

```bash
php -l /Volumes/ProjectData/KanBoard/KanAI/Plugin.php
git -C /Volumes/ProjectData/KanBoard/KanAI log --oneline -1
```
Expected: `No syntax errors detected`, and one commit listed.

---

### Task 3: Add a standalone PHPUnit harness (for pure-logic unit tests in the feature plan)

**Files:**
- Create: `KanBoard/KanAI/composer.json`, `phpunit.xml`, `tests/bootstrap.php`, `tests/HarnessSmokeTest.php`

**Interfaces:**
- Produces: `./vendor/bin/phpunit` runs green against `tests/`; PSR-4 autoload maps `Kanboard\Plugin\KanAI\` → repo root, so feature-plan classes like `Kanboard\Plugin\KanAI\Security\Crypto` resolve to `Security/Crypto.php` in tests.

- [ ] **Step 1: Create `KanBoard/KanAI/composer.json`**

```json
{
    "name": "k1bot2026/kanboard-plugin-kanai",
    "description": "AI assistant & project Q&A (RAG) for Kanboard",
    "type": "kanboard-plugin",
    "license": "MIT",
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.6"
    },
    "autoload": {
        "psr-4": {
            "Kanboard\\Plugin\\KanAI\\": ""
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Kanboard\\Plugin\\KanAI\\Tests\\": "tests/"
        }
    }
}
```

- [ ] **Step 2: Create `KanBoard/KanAI/phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         cacheResult="false">
    <testsuites>
        <testsuite name="KanAI">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 3: Create `KanBoard/KanAI/tests/bootstrap.php`**

```php
<?php

// Standalone test bootstrap — loads Composer autoload only.
// Pure-logic KanAI classes (Security\Crypto, proposal validation, context
// formatting/truncation helpers) must NOT depend on Kanboard core, so they are
// testable here without a running Kanboard.
require __DIR__ . '/../vendor/autoload.php';
```

- [ ] **Step 4: Write the harness smoke test `KanBoard/KanAI/tests/HarnessSmokeTest.php`**

```php
<?php

namespace Kanboard\Plugin\KanAI\Tests;

use PHPUnit\Framework\TestCase;

final class HarnessSmokeTest extends TestCase
{
    public function testHarnessRuns(): void
    {
        $this->assertTrue(true);
    }
}
```

- [ ] **Step 5: Install dev dependencies and run the test to verify it passes**

```bash
cd /Volumes/ProjectData/KanBoard/KanAI
composer install
./vendor/bin/phpunit
```
Expected: PHPUnit runs `HarnessSmokeTest` → `OK (1 test, 1 assertion)`.

- [ ] **Step 6: Commit the harness**

```bash
cd /Volumes/ProjectData/KanBoard/KanAI
git add composer.json phpunit.xml tests/
git commit -m "test: add standalone PHPUnit harness

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Create the shared workspace docs & AI-agent guide

**Files:**
- Create: `KanBoard/README.md`, `KanBoard/AGENTS.md`, `KanBoard/CLAUDE.md`, `KanBoard/kanboard/README.md`

**Interfaces:**
- Produces: workspace-level documentation that maps each folder to its GitHub repo and states the per-plugin boundary rule for agents. (No git — workspace is unversioned.)

- [ ] **Step 1: Create `KanBoard/README.md`**

```markdown
# KanBoard — plugin workspace

This folder is a **workspace container**, not a git repository. It holds the
Kanboard plugins maintained here, each as its own independent Git repo and its
own GitHub project.

| Folder | GitHub repo | What it is |
|---|---|---|
| `TeamWork/` | `k1bot2026/kanboard-plugin-teamwork` | Multi-person task assignment |
| `KanAI/` | `k1bot2026/kanboard-plugin-kanai` | AI assistant & project Q&A (RAG) |
| `docs/` | — | Shared, cross-plugin notes (workspace level) |
| `kanboard/` | — | Pointer to your external Kanboard install (see its README) |

## Working rule

Each plugin is self-contained. Open the specific plugin folder when working on
it (e.g. `cd KanAI`), commit and push within that repo only. Do not create a git
repo at this workspace root.

## Local testing

You run Kanboard elsewhere. Symlink each plugin into that install's `plugins/`
directory — see `kanboard/README.md`.
```

- [ ] **Step 2: Create `KanBoard/AGENTS.md`**

```markdown
# Guidance for AI agents working in this workspace

This is a multi-plugin **workspace**, not a single project. Read this before
making changes.

## Golden rule: one plugin at a time

An agent task targets exactly **one** plugin folder. Never edit across plugins in
a single task. The plugins are separate GitHub repos with separate histories:

- `TeamWork/` → repo `kanboard-plugin-teamwork` (live, actively maintained)
- `KanAI/`    → repo `kanboard-plugin-kanai` (in development)

When you start, confirm which plugin you're in and stay there. Commit/push only
within that plugin's repo. The workspace root is intentionally NOT a git repo —
do not run `git init` here.

## Per-plugin instructions

Each plugin has its own `CLAUDE.md` with conventions and its design docs under
`<plugin>/docs/`. Read those for the plugin you're assigned to.

## Kanboard install

Kanboard itself lives outside this workspace (the user runs it separately).
`kanboard/README.md` explains how plugins are symlinked in for testing.
```

- [ ] **Step 3: Create `KanBoard/CLAUDE.md` (pointer so Claude Code picks it up)**

```markdown
# Workspace context

See `AGENTS.md` in this folder for the rules. Summary: this is a non-git
**workspace** holding independent Kanboard plugin repos (`TeamWork/`, `KanAI/`).
Work inside ONE plugin folder per task; commit/push only within that plugin's
repo; never `git init` at this root. Each plugin has its own `CLAUDE.md`.
```

- [ ] **Step 4: Create `KanBoard/kanboard/README.md` (symlink instructions; no install downloaded)**

```markdown
# Kanboard install (external)

The Kanboard application is not stored in this workspace — you run it elsewhere
(e.g. Docker, or another directory). To test a plugin against your install,
symlink the plugin folder into Kanboard's `plugins/` directory:

    # TeamWork
    ln -s /Volumes/ProjectData/KanBoard/TeamWork <kanboard>/plugins/TeamWork
    # KanAI
    ln -s /Volumes/ProjectData/KanBoard/KanAI    <kanboard>/plugins/KanAI

Replace `<kanboard>` with your install's root (the directory containing
`config.php` and `plugins/`). For a Docker install, mount the plugin folder to
`/var/www/app/plugins/KanAI` instead of symlinking.

After linking, open **Settings → Plugins** in Kanboard to confirm the plugin is
listed, then enable it.
```

- [ ] **Step 5: Verify the workspace tree**

```bash
ls -A /Volumes/ProjectData/KanBoard
ls /Volumes/ProjectData/KanBoard/KanAI
```
Expected root: `AGENTS.md CLAUDE.md KanAI README.md TeamWork docs kanboard`.
Expected KanAI: `CLAUDE.md LICENSE Plugin.php README.md composer.json docs phpunit.xml tests` (+ `vendor` after `composer install`).

---

### Task 5: Verify KanAI loads in Kanboard, and prepare (not execute) the GitHub repo

**Files:** none (verification + instructions only)

**Interfaces:**
- Consumes: the scaffold from Tasks 2–4.
- Produces: confirmation the plugin loads; a ready-to-run `gh` command the user triggers themselves.

- [ ] **Step 1: Symlink KanAI into the user's Kanboard install**

Ask the user for their Kanboard root (`<kanboard>`), then:
```bash
ln -s /Volumes/ProjectData/KanBoard/KanAI <kanboard>/plugins/KanAI
```
(For Docker: bind-mount `/Volumes/ProjectData/KanBoard/KanAI` to `/var/www/app/plugins/KanAI` and restart the container.)

- [ ] **Step 2: Confirm the plugin appears**

In Kanboard, open **Settings → Plugins**.
Expected: `KanAI` listed at version `0.1.0`, author `k1bot2026`, with the
description string, and no PHP error in the logs.

- [ ] **Step 3: Prepare the GitHub repo command (DO NOT push without explicit go-ahead)**

Provide this for the user to run when they're ready:
```bash
cd /Volumes/ProjectData/KanBoard/KanAI
gh repo create k1bot2026/kanboard-plugin-kanai --private --source=. --remote=origin --push
```
Confirm with the user before running — per Global Constraints, nothing is pushed
without explicit approval. Stop here; the KanAI v1 feature plan continues the build.

---

## Self-Review

**Spec coverage (against §5 of the design spec — workspace restructure):**
- Move TeamWork into `TeamWork/`, verify remote/branches/clean → Task 1. ✓
- Scaffold `KanAI/` as minimal valid plugin + `git init` + first commit → Task 2. ✓
- GitHub repo created by user, nothing pushed without go-ahead → Task 5 Step 3 + Global Constraints. ✓
- Workspace `README.md` + `AGENTS.md`/`CLAUDE.md` with repo mapping + one-plugin-per-task rule + symlink instructions → Task 4. ✓
- `docs/` not moved into TeamWork → Task 1 Step 3 (excluded), verified Step 4. ✓
- No Kanboard download; symlink instructions only → Task 4 Step 4 + Task 5. ✓
- Standalone PHPUnit harness for the spec's pure-logic classes (Crypto, gating, JSON validation, context formatting) → Task 3 (PSR-4 maps namespace to root so those classes resolve). ✓

**Placeholder scan:** No "TBD"/"TODO"/"add error handling" steps; every file's full content is inline; the only deliberately-empty body is `Plugin::initialize()` and `getClasses()`, which is correct for a scaffold and explained in a comment. ✓

**Type/name consistency:** Namespace `Kanboard\Plugin\KanAI` used identically in `Plugin.php`, `composer.json` PSR-4, and the smoke test namespace. Version `0.1.0` and `getCompatibleVersion '>=1.2.46'` match the spec. Repo name `kanboard-plugin-kanai` consistent across README, CLAUDE.md, AGENTS.md, and the `gh` command. ✓

**Note for executor:** `composer` and `php` must be available on PATH for Task 3. If `composer` is unavailable, the harness install (Task 3 Steps 5–6) can be deferred, but Tasks 1–2 and 4–5 do not depend on it.
