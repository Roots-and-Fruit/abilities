<?php
/**
 * Post content abilities for agents.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Content_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-content';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Content', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Create, read, update, and publish blog posts via agents.', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		return array(
			RF_Ability_Definition::make( 'rootsandfruit/list-posts' )
				->label( __( 'List posts', 'rootsandfruit-abilities' ) )
				->description( __( 'Lists posts the current user can edit, with optional status filter.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::list_posts_input() )
				->output( RF_Schemas::post_list_output() )
				->execute( array( self::class, 'list_posts' ) )
				->permission( array( RF_Permissions::class, 'can_list_posts' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/get-post' )
				->label( __( 'Get post', 'rootsandfruit-abilities' ) )
				->description( __( 'Returns summary metadata for a single post.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::post_id_input() )
				->output( RF_Schemas::post_summary_output() )
				->execute( array( self::class, 'get_post' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/create-draft' )
				->label( __( 'Create draft post', 'rootsandfruit-abilities' ) )
				->description( __( 'Creates a new draft blog post. Does not publish.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::create_draft_input() )
				->output( RF_Schemas::post_summary_output() )
				->execute( array( self::class, 'create_draft' ) )
				->permission( array( RF_Permissions::class, 'can_create_posts' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/update-post' )
				->label( __( 'Update post', 'rootsandfruit-abilities' ) )
				->description( __( 'Updates title and/or excerpt on an existing post. For block body edits use rootsandfruit/blocks-* abilities.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::update_post_input() )
				->output( RF_Schemas::post_summary_output() )
				->execute( array( self::class, 'update_post' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/publish-post' )
				->label( __( 'Publish post', 'rootsandfruit-abilities' ) )
				->description( __( 'Publishes an existing post. Requires publish_posts capability.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::post_id_input() )
				->output( RF_Schemas::post_summary_output() )
				->execute( array( self::class, 'publish_post' ) )
				->permission( array( RF_Permissions::class, 'can_publish_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::publish() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/set-post-author' )
				->label( __( 'Set post author', 'rootsandfruit-abilities' ) )
				->description( __( 'Assigns a post to a WordPress user by ID or login. Purge Breeze cache before logged-out preview after byline changes.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::set_post_author_input() )
				->output( RF_Schemas::set_post_author_output() )
				->execute( array( self::class, 'set_post_author' ) )
				->permission( array( RF_Permissions::class, 'can_set_post_author' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/set-key-takeaways' )
				->label( __( 'Set key takeaways', 'rootsandfruit-abilities' ) )
				->description( __( 'Sets ordered key takeaway items on a post (_rf_key_takeaways LCF repeater). Use after blocks-create-page in the article pipeline.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::set_key_takeaways_input() )
				->output( RF_Schemas::set_key_takeaways_output() )
				->execute( array( self::class, 'set_key_takeaways' ) )
				->permission( array( RF_Permissions::class, 'can_edit_post' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function list_posts( array $input ) {
		$status   = isset( $input['status'] ) ? (string) $input['status'] : 'any';
		$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;

		$query_args = array(
			'post_type'      => 'post',
			'posts_per_page' => max( 1, min( 100, $per_page ) ),
			'post_status'    => $status,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$posts   = get_posts( $query_args );
		$summary = array();

		foreach ( $posts as $post ) {
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				continue;
			}
			$summary[] = self::format_post_summary( $post );
		}

		return array( 'posts' => $summary );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get_post( array $input ) {
		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		return self::format_post_summary( $post );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create_draft( array $input ) {
		$title = isset( $input['title'] ) ? sanitize_text_field( (string) $input['title'] ) : '';
		if ( '' === $title ) {
			return RF_Errors::invalid_input( 'Title is required.' );
		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => isset( $input['content'] ) ? wp_kses_post( (string) $input['content'] ) : '',
			'post_excerpt' => isset( $input['excerpt'] ) ? sanitize_textarea_field( (string) $input['excerpt'] ) : '',
			'post_status'  => 'draft',
			'post_type'    => 'post',
		);

		$post_id = wp_insert_post( $post_data, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$post = get_post( (int) $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::not_found( 'Post could not be loaded after creation.' );
		}

		return self::format_post_summary( $post );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update_post( array $input ) {
		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		$update = array(
			'ID' => $post_id,
		);

		if ( isset( $input['title'] ) ) {
			$update['post_title'] = sanitize_text_field( (string) $input['title'] );
		}
		if ( isset( $input['content'] ) ) {
			if ( str_contains( (string) $post->post_content, '<!-- wp:' ) || str_contains( (string) $input['content'], '<!-- wp:' ) ) {
				return RF_Errors::invalid_input(
					'Block editor content cannot be updated via rootsandfruit/update-post. Use rootsandfruit/blocks-* abilities.'
				);
			}
			$update['post_content'] = wp_kses_post( (string) $input['content'] );
		}
		if ( isset( $input['excerpt'] ) ) {
			$update['post_excerpt'] = sanitize_textarea_field( (string) $input['excerpt'] );
		}

		if ( count( $update ) === 1 ) {
			return RF_Errors::invalid_input( 'Provide at least one of title, content, or excerpt to update.' );
		}

		$result = wp_update_post( $update, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$updated = get_post( $post_id );
		if ( ! $updated instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		return self::format_post_summary( $updated );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function publish_post( array $input ) {
		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$published = get_post( $post_id );
		if ( ! $published instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		return self::format_post_summary( $published );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function set_post_author( array $input ) {
		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		if ( ! isset( $input['author'] ) ) {
			return RF_Errors::invalid_input( 'Author is required (user ID or login).' );
		}

		$author_id = self::resolve_author_id( $input['author'] );
		if ( $author_id <= 0 ) {
			return RF_Errors::invalid_input( 'Invalid author. Provide a valid user ID or login.' );
		}

		$user = get_user_by( 'id', $author_id );
		if ( ! $user instanceof WP_User ) {
			return RF_Errors::invalid_input( 'Author user not found.' );
		}

		$result = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_author' => $author_id,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$updated = get_post( $post_id );
		if ( ! $updated instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		$summary                      = self::format_post_summary( $updated );
		$summary['author_id']         = $author_id;
		$summary['author_login']      = (string) $user->user_login;
		$summary['breeze_purge_command'] = '.\\tools\\scripts\\purge-breeze-cache.ps1';
		$purge_breeze                 = ! empty( $input['purge_breeze'] );
		$summary['breeze_purged']     = false;
		$summary['breeze_purge_reminder'] = true;

		if ( $purge_breeze && RF_Breeze::is_available() ) {
			$purge_result = RF_Breeze::purge_post( $post_id );
			if ( is_array( $purge_result ) && ! empty( $purge_result['ok'] ) ) {
				$summary['breeze_purged']         = true;
				$summary['breeze_purge_reminder'] = false;
			}
		}

		return $summary;
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function set_key_takeaways( array $input ) {
		$post_id = (int) $input['post_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return RF_Errors::post_not_found( $post_id );
		}

		if ( ! isset( $input['items'] ) || ! is_array( $input['items'] ) ) {
			return RF_Errors::invalid_input( 'items must be a non-empty array of strings.' );
		}

		return RF_Key_Takeaways::set_items( $post_id, $input['items'] );
	}

	/**
	 * @param mixed $author User ID or login string.
	 */
	private static function resolve_author_id( $author ): int {
		if ( is_int( $author ) ) {
			return $author > 0 ? $author : 0;
		}

		if ( is_string( $author ) && ctype_digit( $author ) ) {
			return (int) $author;
		}

		if ( is_string( $author ) && '' !== $author ) {
			$user = get_user_by( 'login', $author );
			if ( $user instanceof WP_User ) {
				return (int) $user->ID;
			}

			$user = get_user_by( 'slug', $author );
			if ( $user instanceof WP_User ) {
				return (int) $user->ID;
			}
		}

		return 0;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function format_post_summary( WP_Post $post ): array {
		return array(
			'post_id'  => (int) $post->ID,
			'status'   => (string) $post->post_status,
			'title'    => (string) get_the_title( $post ),
			'edit_url' => (string) get_edit_post_link( $post->ID, 'raw' ),
			'link'     => (string) get_permalink( $post ),
		);
	}
}
