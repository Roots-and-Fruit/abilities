<?php
/**
 * Boots the ability registry and modules.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Plugin {

	private static ?RF_Plugin $instance = null;

	private RF_Ability_Registry $registry;

	private function __construct() {
		$this->registry = new RF_Ability_Registry();
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		RF_Capabilities::ensure_caps();

		add_action( 'wp_abilities_api_categories_init', array( RF_Agent_Abilities::class, 'register_category' ) );

		$this->registry->add_module( new RF_Health_Module() );
		$this->registry->add_module( new RF_Robots_Llms_Module() );
		if ( class_exists( 'DS_Public_Post_Preview' ) ) {
			$this->registry->add_module( new RF_Preview_Module() );
		}
		$this->registry->add_module( new RF_Content_Module() );
		if ( RF_Fluent_Snippets::is_available() ) {
			$this->registry->add_module( new RF_Snippets_Module() );
		}
		if ( RF_Wp_Rollback::is_available() ) {
			$this->registry->add_module( new RF_Plugins_Module() );
		}
		if ( RF_Block_Mcp::is_available() ) {
			$this->registry->add_module( new RF_Blocks_Module() );
		}
		$this->registry->boot();
	}

	public function registry(): RF_Ability_Registry {
		return $this->registry;
	}
}
