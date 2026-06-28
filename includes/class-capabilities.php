<?php
/**
 * Custom capabilities for Roots & Fruit agent abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Capabilities {

	public const UPDATE_ROBOTS_LLMS_TXT = 'update_robots_llms_txt';

	public static function activate(): void {
		self::grant_to_administrators();
	}

	public static function ensure_caps(): void {
		self::grant_to_administrators();
	}

	private static function grant_to_administrators(): void {
		$role = get_role( 'administrator' );
		if ( ! $role instanceof WP_Role ) {
			return;
		}

		if ( ! $role->has_cap( self::UPDATE_ROBOTS_LLMS_TXT ) ) {
			$role->add_cap( self::UPDATE_ROBOTS_LLMS_TXT );
		}
	}
}
