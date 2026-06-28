<?php
/**
 * Reusable JSON Schema fragments for abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Schemas {

	public static function post_id_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'WordPress post ID.',
					'minimum'     => 1,
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	public static function create_draft_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'title'   => array(
					'type'        => 'string',
					'description' => 'Post title.',
					'minLength'   => 1,
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Post content (HTML allowed).',
				),
				'excerpt' => array(
					'type'        => 'string',
					'description' => 'Optional excerpt.',
				),
			),
			'required'   => array( 'title' ),
		);
	}

	public static function update_post_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'WordPress post ID.',
					'minimum'     => 1,
				),
				'title'   => array(
					'type'        => 'string',
					'description' => 'New title.',
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Plain HTML only. For block editor posts use rootsandfruit/blocks-* abilities instead.',
				),
				'excerpt' => array(
					'type'        => 'string',
					'description' => 'New excerpt.',
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	public static function list_posts_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'status'   => array(
					'type'        => 'string',
					'description' => 'Post status filter.',
					'enum'        => array( 'draft', 'publish', 'pending', 'private', 'any' ),
					'default'     => 'any',
				),
				'per_page' => array(
					'type'        => 'integer',
					'description' => 'Maximum posts to return.',
					'minimum'     => 1,
					'maximum'     => 100,
					'default'     => 20,
				),
			),
		);
	}

	public static function post_summary_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'  => array( 'type' => 'integer' ),
				'status'   => array( 'type' => 'string' ),
				'title'    => array( 'type' => 'string' ),
				'edit_url' => array( 'type' => 'string' ),
				'link'     => array( 'type' => 'string' ),
			),
			'required'   => array( 'post_id', 'status', 'title', 'edit_url' ),
		);
	}

	public static function set_post_author_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'      => array(
					'type'        => 'integer',
					'description' => 'WordPress post ID.',
					'minimum'     => 1,
				),
				'author'       => array(
					'description' => 'Target author user ID (integer) or login (string).',
					'oneOf'       => array(
						array( 'type' => 'integer', 'minimum' => 1 ),
						array( 'type' => 'string', 'minLength' => 1 ),
					),
				),
				'purge_breeze' => array(
					'type'        => 'boolean',
					'description' => 'When true, purge Breeze cache on the server after author change.',
					'default'     => false,
				),
			),
			'required'   => array( 'post_id', 'author' ),
		);
	}

	public static function set_post_author_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'               => array( 'type' => 'integer' ),
				'status'                => array( 'type' => 'string' ),
				'title'                 => array( 'type' => 'string' ),
				'edit_url'              => array( 'type' => 'string' ),
				'link'                  => array( 'type' => 'string' ),
				'author_id'             => array( 'type' => 'integer' ),
				'author_login'          => array( 'type' => 'string' ),
				'breeze_purge_reminder' => array( 'type' => 'boolean' ),
				'breeze_purged'         => array( 'type' => 'boolean' ),
				'breeze_purge_command'  => array(
					'type'        => 'string',
					'description' => 'Agent-side purge script when breeze_purged is false.',
				),
			),
			'required'   => array( 'post_id', 'status', 'title', 'edit_url', 'author_id', 'author_login', 'breeze_purge_reminder' ),
		);
	}

	public static function purge_breeze_cache_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'Optional post ID for per-post purge (Breeze 2.4+). Omit to clear all cache.',
					'minimum'     => 1,
				),
			),
		);
	}

	public static function purge_breeze_cache_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'ok'      => array( 'type' => 'boolean' ),
				'message' => array( 'type' => 'string' ),
				'scope'   => array(
					'type' => 'string',
					'enum' => array( 'all', 'post' ),
				),
				'post_id' => array( 'type' => 'integer' ),
			),
			'required'   => array( 'ok', 'message', 'scope' ),
		);
	}

	public static function post_list_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'posts' => array(
					'type'  => 'array',
					'items' => self::post_summary_output(),
				),
			),
			'required'   => array( 'posts' ),
		);
	}

	public static function preview_result_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'     => array( 'type' => 'integer' ),
				'enabled'     => array( 'type' => 'boolean' ),
				'preview_url' => array( 'type' => 'string' ),
			),
			'required'   => array( 'post_id', 'enabled' ),
		);
	}

	public static function ping_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'ok'                         => array( 'type' => 'boolean' ),
				'plugin_version'             => array( 'type' => 'string' ),
				'block_mcp_active'           => array( 'type' => 'boolean' ),
				'breeze_active'              => array( 'type' => 'boolean' ),
				'robots_llms_txt_writable'   => array( 'type' => 'boolean' ),
				'robots_llms_txt_capability' => array( 'type' => 'string' ),
			),
			'required'   => array( 'ok', 'plugin_version' ),
		);
	}

	public static function snippets_list_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'status'          => array(
					'type'        => 'string',
					'description' => 'Filter by snippet status.',
					'enum'        => array( 'published', 'draft', 'paused' ),
				),
				'per_page'        => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 50,
				),
				'page'            => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'rf_ability_only' => array(
					'type'        => 'boolean',
					'description' => 'When true (default), only snippets tagged rf-ability.',
					'default'     => true,
				),
			),
		);
	}

	public static function snippet_file_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file_name' => array(
					'type'        => 'string',
					'description' => 'FluentSnippets file name, e.g. 7-my-snippet.php',
					'minLength'   => 1,
				),
			),
			'required'   => array( 'file_name' ),
		);
	}

	public static function snippet_create_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'name'        => array(
					'type'        => 'string',
					'description' => 'Snippet title.',
					'minLength'   => 1,
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional description.',
				),
				'code'        => array(
					'type'        => 'string',
					'description' => 'PHP snippet body without opening <?php tag. Must register rootsandfruit/* abilities.',
				),
				'tags'        => array(
					'type'        => 'string',
					'description' => 'Optional comma-separated tags; rf-ability is added automatically.',
				),
				'group'       => array(
					'type'        => 'string',
					'description' => 'Snippet group label.',
				),
				'priority'    => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
			'required'   => array( 'name', 'code' ),
		);
	}

	public static function snippet_update_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file_name'   => array(
					'type'        => 'string',
					'description' => 'FluentSnippets file name.',
					'minLength'   => 1,
				),
				'name'        => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'code'        => array( 'type' => 'string' ),
				'tags'        => array( 'type' => 'string' ),
				'group'       => array( 'type' => 'string' ),
				'reactivate'  => array(
					'type'        => 'boolean',
					'description' => 'Clear FluentSnippets error state after fix.',
				),
			),
			'required'   => array( 'file_name' ),
		);
	}

	public static function snippet_summary_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file_name'   => array( 'type' => 'string' ),
				'name'        => array( 'type' => 'string' ),
				'status'      => array( 'type' => 'string' ),
				'type'        => array( 'type' => 'string' ),
				'run_at'      => array( 'type' => 'string' ),
				'tags'        => array( 'type' => 'string' ),
				'group'       => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'updated_at'  => array( 'type' => 'string' ),
				'error'       => array( 'type' => 'string' ),
			),
			'required'   => array( 'file_name', 'name', 'status' ),
		);
	}

	public static function snippets_list_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'snippets' => array(
					'type'  => 'array',
					'items' => self::snippet_summary_output(),
				),
				'count'    => array( 'type' => 'integer' ),
				'page'     => array( 'type' => 'integer' ),
				'per_page' => array( 'type' => 'integer' ),
			),
			'required'   => array( 'snippets', 'count' ),
		);
	}

	public static function snippet_detail_output(): array {
		$summary               = self::snippet_summary_output();
		$summary['properties']['code'] = array( 'type' => 'string' );

		return array(
			'type'       => 'object',
			'properties' => array(
				'snippet' => $summary,
			),
			'required'   => array( 'snippet' ),
		);
	}

	public static function snippet_mutation_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file_name' => array( 'type' => 'string' ),
				'snippet'   => self::snippet_summary_output(),
				'message'   => array( 'type' => 'string' ),
			),
			'required'   => array( 'file_name', 'snippet', 'message' ),
		);
	}

	public static function snippet_status_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file_name' => array( 'type' => 'string' ),
				'status'    => array( 'type' => 'string' ),
				'snippet'   => self::snippet_summary_output(),
				'message'   => array( 'type' => 'string' ),
			),
			'required'   => array( 'file_name', 'status', 'message' ),
		);
	}

	public static function snippet_verify_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file_name' => array(
					'type'        => 'string',
					'description' => 'FluentSnippets file name, e.g. 3-my-snippet.php',
					'minLength'   => 1,
				),
				'fresh'     => array(
					'type'        => 'boolean',
					'description' => 'Clear any prior runtime error before loopback verify (default true).',
					'default'     => true,
				),
			),
			'required'   => array( 'file_name' ),
		);
	}

	public static function snippet_verify_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'ok'          => array( 'type' => 'boolean' ),
				'error'       => array( 'type' => 'string' ),
				'file_name'   => array( 'type' => 'string' ),
				'status'      => array( 'type' => 'string' ),
				'skipped'     => array( 'type' => 'boolean' ),
				'http_status' => array( 'type' => 'integer' ),
				'message'     => array( 'type' => 'string' ),
			),
			'required'   => array( 'ok', 'error', 'file_name' ),
		);
	}

	public static function plugin_update_safe_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'type'        => 'string',
					'description' => 'Plugin directory slug, e.g. public-post-preview.',
					'minLength'   => 1,
				),
				'target_version' => array(
					'type'        => 'string',
					'description' => 'Optional WordPress.org version to install. Omit for latest.',
				),
				'rollback_on_failure' => array(
					'type'        => 'boolean',
					'description' => 'Rollback to pre-update version if smoke test fails (default true).',
					'default'     => true,
				),
			),
			'required'   => array( 'slug' ),
		);
	}

	public static function plugin_update_safe_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'ok'               => array( 'type' => 'boolean' ),
				'slug'             => array( 'type' => 'string' ),
				'pre_version'      => array( 'type' => 'string' ),
				'post_version'     => array( 'type' => 'string' ),
				'rolled_back'      => array( 'type' => 'boolean' ),
				'rollback_version' => array( 'type' => 'string' ),
				'message'          => array( 'type' => 'string' ),
				'phases'           => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'phase'   => array( 'type' => 'string' ),
							'ok'      => array( 'type' => 'boolean' ),
							'message' => array( 'type' => 'string' ),
							'data'    => array( 'type' => 'object' ),
						),
					),
				),
			),
			'required'   => array( 'ok', 'slug', 'pre_version', 'post_version', 'rolled_back', 'phases' ),
		);
	}

	public static function plugin_update_git_safe_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'type'        => 'string',
					'description' => 'Plugin directory slug under wp-content/plugins (e.g. rootsandfruit-abilities).',
					'minLength'   => 1,
				),
				'git_slug' => array(
					'type'        => 'string',
					'description' => 'Optional Git Updater repository slug when it differs from the directory slug (e.g. abilities).',
				),
				'target_version' => array(
					'type'        => 'string',
					'description' => 'Optional release tag or version (e.g. 1.5.3 or v1.5.3). Omit for latest from update-api.',
				),
				'refresh_cache' => array(
					'type'        => 'boolean',
					'description' => 'Flush Git Updater repo cache, refresh transients, and nudge wp-cron before update (default true).',
					'default'     => true,
				),
				'force_override' => array(
					'type'        => 'boolean',
					'description' => 'Pass override=1 when no target_version (required for release-asset plugins on main). Default true.',
					'default'     => true,
				),
				'rollback_on_failure' => array(
					'type'        => 'boolean',
					'description' => 'Rollback to pre-update version if smoke test fails (default true).',
					'default'     => true,
				),
				'purge_breeze' => array(
					'type'        => 'boolean',
					'description' => 'Purge Breeze cache after successful update (default true).',
					'default'     => true,
				),
			),
			'required'   => array( 'slug' ),
		);
	}

	public static function plugin_update_git_safe_output(): array {
		$output = self::plugin_update_safe_output();
		$output['properties']['git_slug'] = array( 'type' => 'string' );

		return $output;
	}

	public static function blocks_get_page_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'      => array(
					'type'        => 'integer',
					'description' => 'Post or page ID.',
					'minimum'     => 1,
				),
				'render'       => array(
					'type'        => 'boolean',
					'description' => 'Include server-rendered dynamic block output.',
					'default'     => false,
				),
				'persist_refs' => array(
					'type'        => 'boolean',
					'description' => 'Persist stable gk_ref IDs to post content when missing.',
					'default'     => true,
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	public static function blocks_get_page_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'     => array( 'type' => 'integer' ),
				'revision_id' => array( 'type' => 'integer' ),
				'blocks'      => array( 'type' => 'array' ),
			),
			'required'   => array( 'post_id', 'revision_id', 'blocks' ),
		);
	}

	public static function blocks_update_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'            => array( 'type' => 'integer', 'minimum' => 1 ),
				'ref'                => array( 'type' => 'string' ),
				'flat_index'         => array( 'type' => 'integer', 'minimum' => 0 ),
				'attributes'         => array( 'type' => 'object' ),
				'innerHTML'          => array( 'type' => 'string' ),
				'allow_bound_writes' => array( 'type' => 'boolean', 'default' => false ),
			),
			'required'   => array( 'post_id' ),
		);
	}

	public static function blocks_mutate_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'         => array( 'type' => 'integer', 'minimum' => 1 ),
				'op'              => array( 'type' => 'string' ),
				'path'            => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				'ref'             => array( 'type' => 'string' ),
				'attributes'      => array( 'type' => 'object' ),
				'innerHTML'       => array( 'type' => 'string' ),
				'block'           => array( 'type' => 'object' ),
				'wrapper'         => array( 'type' => 'object' ),
				'position'        => array( 'type' => 'integer' ),
				'destination'     => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
				'destination_ref' => array( 'type' => 'string' ),
				'count'           => array( 'type' => 'integer' ),
				'dry_run'         => array( 'type' => 'boolean', 'default' => false ),
			),
			'required'   => array( 'post_id', 'op' ),
		);
	}

	public static function blocks_insert_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'         => array( 'type' => 'integer', 'minimum' => 1 ),
				'blocks'          => array( 'type' => 'array' ),
				'after_ref'       => array( 'type' => 'string' ),
				'before_ref'      => array( 'type' => 'string' ),
				'after_top_level' => array( 'type' => 'integer', 'minimum' => 0 ),
				'before_top_level'=> array( 'type' => 'integer', 'minimum' => 0 ),
			),
			'required'   => array( 'post_id', 'blocks' ),
		);
	}

	public static function blocks_create_page_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'title'     => array( 'type' => 'string', 'minLength' => 1 ),
				'post_type' => array( 'type' => 'string', 'default' => 'page' ),
				'status'    => array( 'type' => 'string', 'default' => 'draft' ),
				'excerpt'   => array( 'type' => 'string' ),
				'slug'      => array( 'type' => 'string' ),
				'blocks'    => array( 'type' => 'array' ),
			),
			'required'   => array( 'title' ),
		);
	}

	public static function blocks_create_page_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'  => array( 'type' => 'integer' ),
				'edit_url' => array( 'type' => 'string' ),
				'status'   => array( 'type' => 'string' ),
			),
		);
	}

	public static function blocks_list_patterns_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'search'   => array( 'type' => 'string' ),
				'per_page' => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100 ),
			),
		);
	}

	public static function blocks_list_patterns_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'patterns' => array( 'type' => 'array' ),
			),
		);
	}

	public static function blocks_write_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'revision_id' => array( 'type' => 'integer' ),
			),
		);
	}

	public static function robots_llms_file_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file' => array(
					'type'        => 'string',
					'description' => 'Which document-root file to read or update.',
					'enum'        => array( 'robots', 'llms', 'llms-full' ),
				),
			),
			'required'   => array( 'file' ),
		);
	}

	public static function robots_llms_get_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file'         => array( 'type' => 'string' ),
				'basename'     => array( 'type' => 'string' ),
				'url'          => array( 'type' => 'string' ),
				'content'      => array( 'type' => 'string' ),
				'bytes'        => array( 'type' => 'integer' ),
				'sha256'       => array( 'type' => 'string' ),
				'modified_utc' => array( 'type' => 'string' ),
			),
			'required'   => array( 'file', 'basename', 'url', 'content', 'bytes', 'sha256' ),
		);
	}

	public static function robots_llms_update_input(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'file' => array(
					'type'        => 'string',
					'description' => 'Which document-root file to update.',
					'enum'        => array( 'robots', 'llms', 'llms-full' ),
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Full replacement file body (non-empty).',
					'minLength'   => 1,
				),
				'expected_sha256' => array(
					'type'        => 'string',
					'description' => 'SHA-256 of the file on disk from get-robots-llms-txt (optimistic concurrency).',
					'pattern'     => '^[a-f0-9]{64}$',
				),
				'dry_run' => array(
					'type'        => 'boolean',
					'description' => 'When true, validate only; do not write.',
					'default'     => false,
				),
				'rollback_on_failure' => array(
					'type'        => 'boolean',
					'description' => 'Restore from .bak when post-write verification fails.',
					'default'     => true,
				),
				'purge_breeze' => array(
					'type'        => 'boolean',
					'description' => 'Purge Breeze cache after a successful verified write.',
					'default'     => false,
				),
			),
			'required'   => array( 'file', 'content', 'expected_sha256' ),
		);
	}

	public static function robots_llms_update_output(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'ok'            => array( 'type' => 'boolean' ),
				'dry_run'       => array( 'type' => 'boolean' ),
				'file'          => array( 'type' => 'string' ),
				'basename'      => array( 'type' => 'string' ),
				'url'           => array( 'type' => 'string' ),
				'bytes_before'  => array( 'type' => 'integer' ),
				'bytes_after'   => array( 'type' => 'integer' ),
				'sha256_before' => array( 'type' => 'string' ),
				'sha256_after'  => array( 'type' => 'string' ),
				'verified'      => array( 'type' => 'boolean' ),
				'backup_path'   => array( 'type' => 'string' ),
				'purge_breeze'  => array( 'type' => 'object' ),
				'message'       => array( 'type' => 'string' ),
			),
			'required'   => array( 'ok', 'file', 'message' ),
		);
	}
}
