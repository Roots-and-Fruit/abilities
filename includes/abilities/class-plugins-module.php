<?php
/**
 * Plugin update and rollback abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Plugins_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-plugins';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Plugins', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Safe plugin updates via WordPress.org or Git Updater with smoke testing and rollback.', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		$definitions = array(
			RF_Ability_Definition::make( 'rootsandfruit/plugin-update-safe' )
				->label( __( 'Safe plugin update', 'rootsandfruit-abilities' ) )
				->description(
					__(
						'Updates a WordPress.org plugin, runs a homepage smoke test, and rolls back automatically on failure. Captures pre-update version internally.',
						'rootsandfruit-abilities'
					)
				)
				->category( $this->category_slug() )
				->input( RF_Schemas::plugin_update_safe_input() )
				->output( RF_Schemas::plugin_update_safe_output() )
				->execute( array( RF_Plugin_Update_Safe::class, 'run' ) )
				->permission( array( RF_Permissions::class, 'can_update_plugins' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
		);

		if ( RF_Git_Updater::plugin_active() ) {
			$definitions[] = RF_Ability_Definition::make( 'rootsandfruit/plugin-update-git-safe' )
				->label( __( 'Safe Git plugin update', 'rootsandfruit-abilities' ) )
				->description(
					__(
						'Updates a Git Updater-managed plugin: refresh GU cache + wp-cron nudge, fetch update-api, install release (tag/override), smoke test, optional Breeze purge, rollback on failure.',
						'rootsandfruit-abilities'
					)
				)
				->category( $this->category_slug() )
				->input( RF_Schemas::plugin_update_git_safe_input() )
				->output( RF_Schemas::plugin_update_git_safe_output() )
				->execute( array( RF_Plugin_Update_Git_Safe::class, 'run' ) )
				->permission( array( RF_Permissions::class, 'can_update_plugins' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build();
		}

		return $definitions;
	}
}
