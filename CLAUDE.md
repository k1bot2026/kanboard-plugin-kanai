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
`php composer.phar install && ./vendor/bin/phpunit`. Kanboard-integrated pieces
(controllers, templates, hooks, schema) are verified by loading the plugin into a
running Kanboard.
