---
name: rf-abilities-dev
description: Develop rootsandfruit-abilities plugin code. Use when adding or changing WordPress Abilities API registrations, PHP modules, schemas, or permission callbacks in the abilities plugin.
---

# R&F abilities dev

**Predictability:** every plugin change follows design → implement → **legwork** → release pointer.

Upstream detail: sibling `wp-*` skills in this folder. Site ops routing: `agent/agent_docs/mcp-routing.md`.

## 1. Design the ability

1. Namespace **`rootsandfruit/<name>`** — exactly one slash (WordPress rejects `rootsandfruit/site/ping`).
2. Define input/output schema, `permission_callback`, and which credential profile may run it.
3. Register in the correct module under `includes/abilities/`; conditional deps (PPP, FluentSnippets, WP Rollback) gate registration.
4. Prefer composite abilities for risky workflows (see `plugin-update-safe`).

**Done when:** ability name, caps, module file, and dependency gates are stated before coding.

## 2. Implement

- Match existing PHP style in sibling files.
- Reuse `class-schemas.php`, `class-errors.php`, `class-permissions.php` patterns.
- Snippet-based abilities: `templates/rf-ability-snippet.example.php`, `rf_register_agent_ability()`.
- Do not register delete abilities unless explicitly requested.

**Done when:** code compiles and follows module conventions.

## 3. Legwork

```powershell
# From agent/
php -l ..\abilities\rootsandfruit-abilities.php
php -l ..\abilities\includes\<touched-path>.php

.\tools\scripts\test-wordpress-mcp-http.ps1 -ExpectRfAbilities -ExpectBlocks
.\tools\scripts\audit-mcp-abilities.ps1 -ExpectBlocks
```

Optional: run `/wp-abilities-verify` workflow from upstream skill when validating registrations.

**Done when:** all touched PHP files pass `php -l` and audit/smoke exit 0 against target environment.

## 4. Ship

Release workflow: `abilities/GITHUB.md` (tag, zip, Git Updater).

**Done when:** user confirms deploy path or release steps are documented — do not tag/push without ask.

## Anti-patterns

- Duplicating MCP routing docs in ability docblocks.
- `update-post` for block body HTML — blocks module owns body edits.
- Hardcoded ability lists in markdown — discover + audit script is canonical.
