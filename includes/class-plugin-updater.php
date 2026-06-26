<?php
/**
 * WordPress.org plugin version read + upgrade helpers.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Plugin_Updater {

	/**
	 * @return string|WP_Error
	 */
	public static function get_installed_version( string $slug ) {
		$plugin_file = self::get_plugin_file( $slug );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );

		return isset( $data['Version'] ) ? (string) $data['Version'] : '';
	}

	/**
	 * @return array{version: string, plugin_file: string, package_url: string}|WP_Error
	 */
	public static function update_from_wordpress_org( string $slug, string $target_version = '' ) {
		$plugin_file = self::get_plugin_file( $slug );
		if ( is_wp_error( $plugin_file ) ) {
			return $plugin_file;
		}

		$api = self::fetch_wordpress_org_info( $slug );
		if ( is_wp_error( $api ) ) {
			return $api;
		}

		$versions = self::versions_map_from_api( $api );

		if ( empty( $versions ) ) {
			return RF_Errors::plugin_update_failed(
				sprintf(
					'WordPress.org returned no version map for "%s". plugins_api response may be missing or malformed.',
					$slug
				)
			);
		}

		$target = '' !== trim( $target_version ) ? trim( $target_version ) : (string) ( $api->version ?? '' );
		if ( '' === $target || ! isset( $versions[ $target ] ) ) {
			return RF_Errors::invalid_input(
				sprintf( 'Version "%s" is not available on WordPress.org for "%s".', $target, $slug )
			);
		}

		$current = self::get_installed_version( $slug );
		if ( is_wp_error( $current ) ) {
			return $current;
		}

		if ( $current === $target ) {
			return array(
				'version'     => $target,
				'plugin_file' => $plugin_file,
				'package_url' => (string) $versions[ $target ],
				'skipped'     => true,
			);
		}

		self::load_upgrader_dependencies();

		$skin     = new Automatic_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );

		$result = $upgrader->run(
			array(
				'package'           => (string) $versions[ $target ],
				'destination'       => WP_PLUGIN_DIR,
				'clear_destination' => true,
				'clear_working'       => true,
				'hook_extra'          => array(
					'plugin' => $plugin_file,
					'type'   => 'plugin',
					'action' => 'update',
				),
			)
		);

		if ( is_wp_error( $result ) ) {
			return RF_Errors::plugin_update_failed( $result->get_error_message() );
		}

		if ( false === $result ) {
			$errors = $skin->get_errors();
			if ( is_wp_error( $errors ) && $errors->has_errors() ) {
				return RF_Errors::plugin_update_failed( $errors->get_error_message() );
			}

			return RF_Errors::plugin_update_failed( 'Plugin update failed.' );
		}

		$new_version = self::get_installed_version( $slug );
		if ( is_wp_error( $new_version ) ) {
			return $new_version;
		}

		return array(
			'version'     => $new_version,
			'plugin_file' => $plugin_file,
			'package_url' => (string) $versions[ $target ],
			'skipped'     => false,
		);
	}

	/**
	 * @return object|WP_Error
	 */
	private static function fetch_wordpress_org_info( string $slug ) {
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'versions' => true,
					'version'  => true,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return RF_Errors::plugin_update_failed(
				sprintf(
					'WordPress.org API error for "%s": %s',
					$slug,
					$api->get_error_message()
				)
			);
		}

		if ( empty( $api ) || ! is_object( $api ) || ! empty( $api->error ) ) {
			return RF_Errors::plugin_not_on_wordpress_org( $slug );
		}

		return $api;
	}

	/**
	 * @return string|WP_Error
	 */
	private static function get_plugin_file( string $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $plugin_file ) {
			if ( dirname( $plugin_file ) === $slug ) {
				return $plugin_file;
			}
		}

		return RF_Errors::plugin_not_installed( $slug );
	}

	/**
	 * plugins_api() casts the top-level response to object; nested `versions` stays an array.
	 *
	 * @return array<string, string>
	 */
	private static function versions_map_from_api( object $api ): array {
		if ( ! isset( $api->versions ) ) {
			return array();
		}

		if ( is_array( $api->versions ) ) {
			return $api->versions;
		}

		if ( is_object( $api->versions ) ) {
			return (array) $api->versions;
		}

		return array();
	}

	private static function load_upgrader_dependencies(): void {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
}
