<?php
/**
 * Site health / diagnostic abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Health_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-site';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Site', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Site diagnostics and Breeze cache utilities for Roots & Fruit agents.', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		return array(
			RF_Ability_Definition::make( 'rootsandfruit/ping' )
				->label( __( 'Ping', 'rootsandfruit-abilities' ) )
				->description( __( 'Returns plugin health and version. Use to verify MCP connectivity.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->output( RF_Schemas::ping_output() )
				->execute( array( self::class, 'ping' ) )
				->permission( array( RF_Permissions::class, 'can_read' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/purge-breeze-cache' )
				->label( __( 'Purge Breeze cache', 'rootsandfruit-abilities' ) )
				->description( __( 'Clears Breeze page cache on the server. Prefer this over agent-side REST when available. Optional post_id for per-post purge.', 'rootsandfruit-abilities' ) )
				->category( $this->category_slug() )
				->input( RF_Schemas::purge_breeze_cache_input() )
				->output( RF_Schemas::purge_breeze_cache_output() )
				->execute( array( self::class, 'purge_breeze_cache' ) )
				->permission( array( RF_Permissions::class, 'can_purge_breeze_cache' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	public static function ping( array $input = array() ): array {
		return array(
			'ok'               => true,
			'plugin_version'   => RF_ABILITIES_VERSION,
			'block_mcp_active' => RF_Block_Mcp::is_available(),
			'breeze_active'    => RF_Breeze::is_available(),
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function purge_breeze_cache( array $input = array() ) {
		if ( ! RF_Breeze::is_available() ) {
			return RF_Errors::breeze_unavailable();
		}

		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		if ( $post_id > 0 ) {
			return RF_Breeze::purge_post( $post_id );
		}

		return RF_Breeze::purge_all();
	}
}
