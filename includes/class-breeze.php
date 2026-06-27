<?php
/**
 * Breeze cache purge helpers for agent abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Breeze {

	public static function is_available(): bool {
		return has_action( 'breeze_clear_all_cache' )
			|| has_action( 'purge_post_cache' )
			|| class_exists( 'Breeze_PurgeCache', false );
	}

	/**
	 * @return array{ok: bool, message: string, scope: string}
	 */
	public static function purge_all(): array {
		if ( ! has_action( 'breeze_clear_all_cache' ) && ! class_exists( 'Breeze_PurgeCache', false ) ) {
			return array(
				'ok'      => false,
				'message' => 'Breeze is not active.',
				'scope'   => 'all',
			);
		}

		do_action( 'breeze_clear_all_cache' );

		return array(
			'ok'      => true,
			'message' => 'Cache cleared.',
			'scope'   => 'all',
		);
	}

	/**
	 * @return array{ok: bool, message: string, scope: string, post_id?: int}
	 */
	public static function purge_post( int $post_id ): array {
		if ( $post_id <= 0 ) {
			return array(
				'ok'      => false,
				'message' => 'Invalid post ID.',
				'scope'   => 'post',
				'post_id' => $post_id,
			);
		}

		if ( has_action( 'purge_post_cache' ) ) {
			do_action( 'purge_post_cache', $post_id );

			return array(
				'ok'      => true,
				'message' => 'Post cache purge triggered.',
				'scope'   => 'post',
				'post_id' => $post_id,
			);
		}

		$all = self::purge_all();
		if ( $all['ok'] ) {
			$all['scope']   = 'all';
			$all['post_id'] = $post_id;
			$all['message'] = 'Per-post Breeze purge unavailable; cleared all cache.';
		}

		return $all;
	}
}
