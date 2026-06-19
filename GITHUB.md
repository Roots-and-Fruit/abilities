# GitHub release — Roots & Fruit Abilities

Canonical repo: [github.com/Roots-and-Fruit/abilities](https://github.com/Roots-and-Fruit/abilities)

## Repo layout

Plugin files live at the **repository root** (not in a monorepo subfolder). Git Updater expects:

```
abilities/
├── rootsandfruit-abilities.php   # main plugin file (includes GitHub Plugin URI headers)
├── readme.txt
├── includes/
└── templates/
```

## Git Updater (production)

Headers in `rootsandfruit-abilities.php`:

```
GitHub Plugin URI: https://github.com/Roots-and-Fruit/abilities
Primary Branch: main
Release Asset: true
```

After install, register in **Git Updater → Additions** if needed:

| Field | Value |
|-------|--------|
| Repository Type | `github_plugin` |
| Repository Slug | `rootsandfruit-abilities/rootsandfruit-abilities.php` |
| Repository URI | `https://github.com/Roots-and-Fruit/abilities` |
| Primary Branch | `main` |
| Release Asset | checked |

Release zip asset name should match Git Updater convention: `abilities-*.zip` (e.g. `abilities-1.4.0.zip`) containing folder `rootsandfruit-abilities/`.

## Tagging a release

1. Bump `Version` and `RF_ABILITIES_VERSION` in `rootsandfruit-abilities.php` and `Stable tag` in `readme.txt`.
2. Commit and push to `main`.
3. Create GitHub release tag `v1.4.0` with attached zip built from plugin root.

## Local dev layout

Clone beside the Cursor **agent** repo:

```
rootsandfruit-as-client/
├── agent/       ← Cursor MCP ops (github.com/Roots-and-Fruit/agent)
└── abilities/   ← this repo
```

Open `rootsandfruit.code-workspace` in Cursor for both folders. Sync changes here before tagging releases.
