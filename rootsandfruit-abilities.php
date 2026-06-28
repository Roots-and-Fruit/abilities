<?php
/**
 * Plugin Name: Roots & Fruit Abilities
 * Plugin URI: https://github.com/Roots-and-Fruit/abilities
 * Description: Registers Roots & Fruit agent abilities for the WordPress Abilities API and MCP Adapter.
 * Version: 1.6.1
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Author: Roots & Fruit
 * License: GPL-2.0-or-later
 * Text Domain: rootsandfruit-abilities
 * GitHub Plugin URI: https://github.com/Roots-and-Fruit/abilities
 * Primary Branch: main
 * Release Asset: true
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RF_ABILITIES_VERSION', '1.6.1' );
define( 'RF_ABILITIES_FILE', __FILE__ );
define( 'RF_ABILITIES_PATH', plugin_dir_path( __FILE__ ) );
define( 'RF_ABILITIES_URL', plugin_dir_url( __FILE__ ) );
define( 'RF_ABILITIES_PREFIX', 'rootsandfruit/' );

require_once RF_ABILITIES_PATH . 'includes/class-capabilities.php';
require_once RF_ABILITIES_PATH . 'includes/class-errors.php';
require_once RF_ABILITIES_PATH . 'includes/class-annotations.php';
require_once RF_ABILITIES_PATH . 'includes/class-schemas.php';
require_once RF_ABILITIES_PATH . 'includes/class-permissions.php';
require_once RF_ABILITIES_PATH . 'includes/class-ability-module.php';
require_once RF_ABILITIES_PATH . 'includes/class-ability-definition.php';
require_once RF_ABILITIES_PATH . 'includes/class-ability-registry.php';
require_once RF_ABILITIES_PATH . 'includes/class-agent-abilities.php';
require_once RF_ABILITIES_PATH . 'includes/class-fluent-snippets.php';
require_once RF_ABILITIES_PATH . 'includes/class-block-mcp.php';
require_once RF_ABILITIES_PATH . 'includes/class-breeze.php';
require_once RF_ABILITIES_PATH . 'includes/class-site-smoke-test.php';
require_once RF_ABILITIES_PATH . 'includes/class-wp-rollback-runner.php';
require_once RF_ABILITIES_PATH . 'includes/class-plugin-updater.php';
require_once RF_ABILITIES_PATH . 'includes/class-git-updater.php';
require_once RF_ABILITIES_PATH . 'includes/class-plugin-update-safe.php';
require_once RF_ABILITIES_PATH . 'includes/class-plugin-update-git-safe.php';
require_once RF_ABILITIES_PATH . 'includes/class-robots-llms-files.php';
require_once RF_ABILITIES_PATH . 'includes/class-plugin.php';
require_once RF_ABILITIES_PATH . 'includes/abilities/class-health-module.php';
require_once RF_ABILITIES_PATH . 'includes/abilities/class-preview-module.php';
require_once RF_ABILITIES_PATH . 'includes/abilities/class-content-module.php';
require_once RF_ABILITIES_PATH . 'includes/abilities/class-blocks-module.php';
require_once RF_ABILITIES_PATH . 'includes/abilities/class-snippets-module.php';
require_once RF_ABILITIES_PATH . 'includes/abilities/class-plugins-module.php';
require_once RF_ABILITIES_PATH . 'includes/abilities/class-robots-llms-module.php';

register_activation_hook(
	__FILE__,
	static function (): void {
		RF_Capabilities::activate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="notice notice-error"><p>';
					esc_html_e( 'Roots & Fruit Abilities requires WordPress 6.9 or newer (Abilities API).', 'rootsandfruit-abilities' );
					echo '</p></div>';
				}
			);
			return;
		}

		RF_Plugin::instance()->boot();
	}
);
