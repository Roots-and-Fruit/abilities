=== Roots & Fruit Abilities ===
Contributors: rootsandfruit
Tags: abilities, mcp, ai, agents, blocks
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Registers Roots & Fruit agent abilities for the WordPress Abilities API and MCP Adapter.

== Description ==

Provides a consistent, least-privilege ability surface for Cursor agents via MCP Adapter.

== Ability catalog ==

* rootsandfruit/ping
* rootsandfruit/purge-breeze-cache
* rootsandfruit/get-robots-llms-txt
* rootsandfruit/update-robots-llms-txt
* rootsandfruit/enable-public-preview
* rootsandfruit/get-public-preview-url
* rootsandfruit/list-posts
* rootsandfruit/get-post
* rootsandfruit/create-draft
* rootsandfruit/update-post
* rootsandfruit/publish-post
* rootsandfruit/set-post-author

When Block MCP (gk-block-mcp) is active:

* rootsandfruit/blocks-get-page
* rootsandfruit/blocks-update
* rootsandfruit/blocks-mutate
* rootsandfruit/blocks-insert
* rootsandfruit/blocks-create-page
* rootsandfruit/blocks-list-patterns

When FluentSnippets is active:

* rootsandfruit/snippets-list
* rootsandfruit/snippets-get
* rootsandfruit/snippets-create
* rootsandfruit/snippets-update
* rootsandfruit/snippets-activate
* rootsandfruit/snippets-deactivate
* rootsandfruit/snippets-verify

When WP Rollback is active:

* rootsandfruit/plugin-update-safe
* rootsandfruit/plugin-update-git-safe (when Git Updater is active)

== Dependencies ==

* WordPress 6.9+ (Abilities API)
* MCP Adapter plugin (for Cursor MCP discovery)
* Public Post Preview plugin (preview abilities register only when this plugin is active)
* Block MCP by GravityKit (block abilities; see below)
* FluentSnippets plugin (snippet abilities; see below)
* WP Rollback plugin (plugin-update-safe; see below)

== Block editor bridge ==

When gk-block-mcp is active, block abilities register on the same MCP Adapter surface.
Use blocks-* for Gutenberg body edits; use update-post for title/excerpt only on block posts.

== Safe plugin updates ==

Requires `update_plugins` (admin MCP user, not the content agent role).

1. Call `rootsandfruit/plugin-update-safe` with a WordPress.org plugin slug.
2. Ability captures pre-update version, updates from wordpress.org, smoke-tests homepage.
3. On smoke failure, rolls back via WP Rollback step runner (not REST).

== Custom abilities via FluentSnippets ==

1. Create a snippet with `rootsandfruit/snippets-create` (tagged `rf-ability`, saved as draft).
2. Use `templates/rf-ability-snippet.example.php` and `rf_register_agent_abilities()` inside `wp_abilities_api_init`.
3. Activate with `rootsandfruit/snippets-activate` after review.
4. Call `rootsandfruit/snippets-verify` after update/activate to loopback-check runtime (returns `ok` and `error` in one call).
5. Custom `rootsandfruit/*` abilities in the snippet appear in MCP discover.

Snippet management requires `unfiltered_html` (typically administrator).

== robots.txt / llms.txt updates ==

Requires custom capability `update_robots_llms_txt` (granted to Administrator on activate/upgrade).

1. `rootsandfruit/get-robots-llms-txt` with `file`: `robots`, `llms`, or `llms-full` — returns `content` and `sha256`.
2. Edit locally (see agent repo `content/discovery/`).
3. `rootsandfruit/update-robots-llms-txt` with `content`, `expected_sha256` from step 1, optional `dry_run`, `purge_breeze`.
4. Update-only: files must exist at the document root; no create or delete via MCP.

Disable writes in wp-config: `define( 'RF_ROBOTS_LLMS_TXT_WRITABLE', false );`

Use an administrator Application Password — not the content agent role (unless you explicitly grant `update_robots_llms_txt`).

== Agent role capabilities ==

Recommended custom role caps:

* read, edit_posts, publish_posts, upload_files
* edit_published_posts, edit_others_posts (if editing existing content)
* No delete_*, manage_options, update_robots_llms_txt, or plugin caps

== Installation ==

1. Copy the plugin folder to wp-content/plugins/rootsandfruit-abilities/
2. Activate via Plugins screen
3. Activate gk-block-mcp for block editor abilities
4. Confirm abilities appear in Abilities Explorer
5. Run audit-mcp-abilities.ps1 from the rootsandfruit-as-client repo

== Changelog ==

= 1.6.1 =
* Fix plugin-update-git-safe: pass Git Updater override=1 when target_version/tag is set (release-asset deploys).

= 1.6.0 =
* Add `update_robots_llms_txt` capability (Administrator on activate/upgrade).
* Add rootsandfruit/get-robots-llms-txt and update-robots-llms-txt for robots.txt, llms.txt, llms-full.txt (update-only, sha256 concurrency, .bak backup, verify).
* ping reports robots_llms_txt_writable and capability name.

= 1.5.4 =
* Add rootsandfruit/plugin-update-git-safe: flush Git Updater cache, nudge wp-cron, update-api, install with tag/override, smoke test, Breeze purge, rollback on failure.
* Resolves Git Updater slug vs plugin directory slug (e.g. abilities vs rootsandfruit-abilities).

= 1.5.3 =
* Add rootsandfruit/purge-breeze-cache (server-side Breeze purge; optional post_id).
* set-post-author: optional purge_breeze; breeze_purge_command in response; ping reports breeze_active.

= 1.5.2 =
* Fix plugin-update-safe: accept array-shaped `versions` from plugins_api (WordPress.org updates no longer fail with a false outbound-HTTP error).

= 1.5.1 =
* Fix GitHub release zip paths (forward slashes) for Linux/Git Updater installs.

= 1.5.0 =
* Add rootsandfruit/set-post-author for MCP article publish workflow (user ID or login).
* Returns breeze_purge_reminder after byline changes.

= 1.4.0 =
* Block MCP bridge: six rootsandfruit/blocks-* abilities when gk-block-mcp is active.
* ping reports block_mcp_active; update-post rejects block body HTML when inappropriate.
* GitHub Plugin URI headers for Git Updater.
