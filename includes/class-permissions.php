<?php
/**
 * Permission callbacks for R&F abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Permissions {

	public static function can_read(): bool {
		return current_user_can( 'read' );
	}

	public static function can_create_posts( $input = null ): bool {
		return current_user_can( 'edit_posts' );
	}

	public static function can_list_posts( $input = null ): bool {
		return current_user_can( 'edit_posts' );
	}

	public static function can_edit_post( $input = null ): bool {
		$post_id = self::extract_post_id( $input );
		if ( $post_id <= 0 ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	public static function can_publish_post( $input = null ): bool {
		$post_id = self::extract_post_id( $input );
		if ( $post_id <= 0 ) {
			return false;
		}

		return current_user_can( 'publish_posts' ) && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * @param mixed $input Ability input.
	 */
	public static function can_set_post_author( $input = null ): bool {
		if ( ! is_array( $input ) || ! isset( $input['post_id'], $input['author'] ) ) {
			return false;
		}

		$post_id = (int) $input['post_id'];
		if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		$author_id = self::resolve_author_id_from_input( $input['author'] );
		if ( $author_id <= 0 ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( (int) $post->post_author === $author_id ) {
			return true;
		}

		return current_user_can( 'edit_others_posts' );
	}

	/**
	 * FluentSnippets mutating operations require unfiltered_html (matches FluentSnippets REST).
	 */
	public static function can_manage_snippets( $input = null ): bool {
		return current_user_can( 'unfiltered_html' );
	}

	public static function can_update_plugins( $input = null ): bool {
		return current_user_can( 'update_plugins' );
	}

	/**
	 * @param mixed $input Ability input.
	 */
	private static function extract_post_id( $input ): int {
		if ( ! is_array( $input ) || ! isset( $input['post_id'] ) ) {
			return 0;
		}

		return (int) $input['post_id'];
	}

	/**
	 * @param mixed $author User ID or login string.
	 */
	private static function resolve_author_id_from_input( $author ): int {
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
}
