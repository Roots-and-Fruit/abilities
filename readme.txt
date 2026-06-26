=== Roots & Fruit Abilities ===
Contributors: rootsandfruit
Tags: abilities, mcp, ai, agents, blocks
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.5.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Registers Roots & Fruit agent abilities for the WordPress Abilities API and MCP Adapter.

== Description ==

Provides a consistent, least-privilege ability surface for Cursor agents via MCP Adapter.

== Ability catalog ==

* rootsandfruit/ping
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

== Agent role capabilities ==

Recommended custom role caps:

* read, edit_posts, publish_posts, upload_files
* edit_published_posts, edit_others_posts (if editing existing content)
* No delete_*, manage_options, or plugin caps

== Installation ==

1. Copy the plugin folder to wp-content/plugins/rootsandfruit-abilities/
2. Activate via Plugins screen
3. Activate gk-block-mcp for block editor abilities
4. Confirm abilities appear in Abilities Explorer
5. Run audit-mcp-abilities.ps1 from the rootsandfruit-as-client repo

== Changelog ==

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
