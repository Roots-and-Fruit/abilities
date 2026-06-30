<?php
/**
 * Key takeaways post meta (_rf_key_takeaways) for LCF ordered-list repeater.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Key_Takeaways {

	public const META_KEY      = '_rf_key_takeaways';
	public const HTML_META_KEY = '_rf_key_takeaways_html';
	public const MIN_ITEMS     = 1;
	public const MAX_ITEMS     = 12;

	/**
	 * @param array<int, mixed> $items
	 * @return array<string, mixed>|WP_Error
	 */
	public static function set_items( int $post_id, array $items ) {
		$sanitized = self::sanitize_items( $items );
		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		$storage_method = 'meta_fallback';

		if ( function_exists( 'lcf_set_olist_items' ) ) {
			$result = lcf_set_olist_items( $post_id, self::META_KEY, $sanitized );
			if ( false === $result ) {
				return RF_Errors::invalid_input( 'LCF could not save key takeaways for this post.' );
			}
			$storage_method = 'lcf';
		} else {
			update_post_meta( $post_id, self::META_KEY, $sanitized );
		}

		$read_back = self::get_items( $post_id );
		if ( count( $read_back ) !== count( $sanitized ) ) {
			return RF_Errors::invalid_input(
				'Key takeaways were not stored correctly (count mismatch after save).'
			);
		}

		$html = (string) get_post_meta( $post_id, self::HTML_META_KEY, true );
		if ( '' === $html ) {
			update_post_meta( $post_id, self::HTML_META_KEY, self::build_ol_html( $read_back ) );
			$html = (string) get_post_meta( $post_id, self::HTML_META_KEY, true );
		}

		return array(
			'post_id'         => $post_id,
			'meta_key'        => self::META_KEY,
			'count'           => count( $read_back ),
			'items'           => $read_back,
			'html_populated'  => '' !== $html,
			'storage_method'  => $storage_method,
			'lcf_available'   => function_exists( 'lcf_set_olist_items' ),
		);
	}

	/**
	 * @return string[]
	 */
	public static function get_items( int $post_id ): array {
		if ( function_exists( 'lcf_get_olist_items' ) ) {
			$items = lcf_get_olist_items( $post_id, self::META_KEY );
			if ( is_array( $items ) ) {
				return self::normalize_item_list( $items );
			}
		}

		$raw = get_post_meta( $post_id, self::META_KEY, true );
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return self::normalize_item_list( $decoded );
			}
		}

		return is_array( $raw ) ? self::normalize_item_list( $raw ) : array();
	}

	/**
	 * @param string[] $items
	 */
	public static function build_ol_html( array $items ): string {
		$lis = array();
		foreach ( $items as $text ) {
			$lis[] = '<li>' . esc_html( $text ) . '</li>';
		}

		return '<ol class="rf-keytakeaways">' . implode( '', $lis ) . '</ol>';
	}

	/**
	 * @param array<int, mixed> $items
	 * @return string[]|WP_Error
	 */
	private static function sanitize_items( array $items ) {
		$out = array();

		foreach ( $items as $item ) {
			if ( ! is_string( $item ) && ! is_int( $item ) && ! is_float( $item ) ) {
				continue;
			}
			$text = sanitize_textarea_field( (string) $item );
			if ( '' !== $text ) {
				$out[] = $text;
			}
		}

		if ( count( $out ) < self::MIN_ITEMS ) {
			return RF_Errors::invalid_input(
				sprintf( 'Provide at least %d non-empty takeaway item.', self::MIN_ITEMS )
			);
		}

		if ( count( $out ) > self::MAX_ITEMS ) {
			return RF_Errors::invalid_input(
				sprintf( 'Maximum %d takeaway items allowed.', self::MAX_ITEMS )
			);
		}

		return $out;
	}

	/**
	 * @param array<int, mixed> $items
	 * @return string[]
	 */
	private static function normalize_item_list( array $items ): array {
		$out = array();
		foreach ( $items as $item ) {
			if ( is_string( $item ) || is_numeric( $item ) ) {
				$text = trim( (string) $item );
				if ( '' !== $text ) {
					$out[] = $text;
				}
			}
		}

		return array_values( $out );
	}
}
