<?php
/**
 * Git Updater integration for agent-driven plugin deploys.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Git_Updater {

	private const REST_NAMESPACE = 'git-updater/v1';

	/**
	 * @return array<string, mixed>
	 */
	public static function is_available(): array {
		return array(
			'active'          => self::plugin_active(),
			'api_key_present' => '' !== self::get_api_key(),
		);
	}

	public static function plugin_active(): bool {
		return defined( 'GIT_UPDATER_VERSION' ) || class_exists( 'Fragen\Git_Updater\Bootstrap', false );
	}

	/**
	 * Resolve the Git Updater repository slug (often differs from wp-content folder name).
	 *
	 * @return string|WP_Error
	 */
	public static function resolve_git_slug( string $plugin_dir_slug, string $git_slug = '' ) {
		$git_slug = sanitize_key( $git_slug );
		if ( '' !== $git_slug ) {
			return $git_slug;
		}

		$from_header = self::git_slug_from_plugin_header( $plugin_dir_slug );
		if ( '' !== $from_header ) {
			$api = self::fetch_update_api( $from_header );
			if ( ! is_wp_error( $api ) ) {
				return $from_header;
			}
		}

		$dir_candidate = sanitize_key( $plugin_dir_slug );
		if ( '' !== $dir_candidate ) {
			$api = self::fetch_update_api( $dir_candidate );
			if ( ! is_wp_error( $api ) ) {
				return $dir_candidate;
			}
		}

		$repos = self::fetch_repos();
		if ( is_wp_error( $repos ) ) {
			return $repos;
		}

		$installed = RF_Plugin_Updater::get_installed_version( $plugin_dir_slug );
		if ( is_wp_error( $installed ) ) {
			return $installed;
		}

		foreach ( $repos as $repo ) {
			if ( ! is_array( $repo ) || ( $repo['type'] ?? '' ) !== 'plugin' ) {
				continue;
			}
			if ( (string) ( $repo['version'] ?? '' ) === $installed && ! empty( $repo['slug'] ) ) {
				return (string) $repo['slug'];
			}
		}

		return RF_Errors::invalid_input(
			sprintf(
				'Could not resolve Git Updater slug for plugin directory "%s". Pass git_slug explicitly (e.g. abilities).',
				$plugin_dir_slug
			)
		);
	}

	/**
	 * Flush Git Updater caches and nudge WP-Cron so update metadata is fresh.
	 *
	 * @return array<string, mixed>
	 */
	public static function refresh_caches( string $git_slug ): array {
		$phases = array();

		if ( has_action( 'gu_refresh_transients' ) ) {
			do_action( 'gu_refresh_transients' );
			$phases[] = array(
				'step'    => 'gu_refresh_transients',
				'ok'      => true,
				'message' => 'Git Updater transients refresh triggered.',
			);
		} else {
			$phases[] = array(
				'step'    => 'gu_refresh_transients',
				'ok'      => false,
				'message' => 'gu_refresh_transients hook not available.',
			);
		}

		$flush = self::rest_request(
			'flush-repo-cache',
			array(
				'slug' => $git_slug,
				'key'  => self::get_api_key(),
			)
		);
		$phases[] = array(
			'step'    => 'flush-repo-cache',
			'ok'      => ! is_wp_error( $flush ) && ! empty( $flush['success'] ),
			'message' => is_wp_error( $flush )
				? $flush->get_error_message()
				: wp_json_encode( $flush ),
		);

		delete_site_transient( 'update_plugins' );
		$phases[] = array(
			'step'    => 'delete-update_plugins-transient',
			'ok'      => true,
			'message' => 'Cleared site transient update_plugins.',
		);

		$cron = self::nudge_wp_cron();
		$phases[] = $cron;

		return array(
			'ok'     => self::phases_ok( $phases ),
			'phases' => $phases,
		);
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	public static function fetch_update_api( string $git_slug ) {
		$response = self::rest_request(
			'update-api',
			array( 'slug' => $git_slug )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( isset( $response['error'] ) ) {
			return RF_Errors::plugin_update_failed( (string) $response['error'] );
		}

		return $response;
	}

	/**
	 * Run Git Updater remote update for a repository slug.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public static function run_update( string $git_slug, string $tag = '', bool $force_override = true ) {
		$key = self::get_api_key();
		if ( '' === $key ) {
			return RF_Errors::git_updater_unavailable( 'Git Updater API key is not configured (Remote Management).' );
		}

		$params = array(
			'key'    => $key,
			'plugin' => $git_slug,
		);

		$tag = trim( $tag );
		if ( '' !== $tag ) {
			$params['tag'] = self::normalize_tag( $tag );
		} elseif ( $force_override ) {
			$params['override'] = '1';
		}

		$response = self::rest_request( 'update', $params, 'GET' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$success = ! empty( $response['success'] );
		if ( ! $success ) {
			$messages = $response['data']['messages'] ?? $response['message'] ?? 'Git Updater update failed.';
			if ( is_array( $messages ) ) {
				$messages = implode( ' ', array_map( 'wp_strip_all_tags', $messages ) );
			}

			return RF_Errors::plugin_update_failed( (string) $messages );
		}

		$messages = $response['data']['messages'] ?? $response['messages'] ?? array();
		if ( ! is_array( $messages ) ) {
			$messages = array( (string) $messages );
		}

		return array(
			'ok'       => true,
			'messages' => array_map( 'wp_strip_all_tags', $messages ),
			'git_slug' => $git_slug,
			'tag'      => $tag,
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function fetch_repos_list(): array {
		$repos = self::fetch_repos();
		if ( is_wp_error( $repos ) ) {
			return array();
		}

		return $repos;
	}

	/**
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	private static function fetch_repos() {
		$key = self::get_api_key();
		if ( '' === $key ) {
			return RF_Errors::git_updater_unavailable( 'Git Updater API key is not configured (Remote Management).' );
		}

		$response = self::rest_request(
			'repos',
			array( 'key' => $key )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$slugs = $response['sites']['slugs'] ?? $response['slugs'] ?? array();
		if ( ! is_array( $slugs ) ) {
			return RF_Errors::plugin_update_failed( 'Git Updater repos response was malformed.' );
		}

		return $slugs;
	}

	/**
	 * @param array<string, string> $params
	 * @return array<string, mixed>|WP_Error
	 */
	private static function rest_request( string $route, array $params, string $method = 'GET' ) {
		$url = add_query_arg( $params, rest_url( self::REST_NAMESPACE . '/' . $route ) );

		$args = array(
			'timeout'   => 120,
			'sslverify' => true,
			'headers'   => array(
				'Accept' => 'application/json',
			),
		);

		if ( 'POST' === strtoupper( $method ) ) {
			$response = wp_remote_post( $url, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return RF_Errors::plugin_update_failed(
				sprintf( 'Git Updater %s returned HTTP %d with non-JSON body.', $route, $code )
			);
		}

		if ( $code >= 400 ) {
			$message = $data['data']['messages'] ?? $data['message'] ?? $body;
			if ( is_array( $message ) ) {
				$message = implode( ' ', $message );
			}

			return RF_Errors::plugin_update_failed(
				sprintf( 'Git Updater %s failed (HTTP %d): %s', $route, $code, wp_strip_all_tags( (string) $message ) )
			);
		}

		return $data;
	}

	private static function get_api_key(): string {
		$key = get_site_option( 'git_updater_api_key' );

		return is_string( $key ) ? trim( $key ) : '';
	}

	private static function git_slug_from_plugin_header( string $plugin_dir_slug ): string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $plugin_file ) {
			if ( dirname( $plugin_file ) !== $plugin_dir_slug ) {
				continue;
			}

			$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
			$uri  = isset( $data['PluginURI'] ) ? (string) $data['PluginURI'] : '';

			if ( ! function_exists( 'get_file_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$extra = get_file_data(
				WP_PLUGIN_DIR . '/' . $plugin_file,
				array(
					'github_uri' => 'GitHub Plugin URI',
				),
				'plugin'
			);
			if ( ! empty( $extra['github_uri'] ) ) {
				$uri = (string) $extra['github_uri'];
			}

			if ( preg_match( '#github\.com/[^/]+/([^/]+)/?$#i', $uri, $matches ) ) {
				return sanitize_key( $matches[1] );
			}
		}

		return '';
	}

	private static function normalize_tag( string $tag ): string {
		$tag = trim( $tag );
		if ( '' === $tag ) {
			return $tag;
		}

		if ( preg_match( '/^\d+\.\d+/', $tag ) && ! str_starts_with( $tag, 'v' ) ) {
			return 'v' . $tag;
		}

		return $tag;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function nudge_wp_cron(): array {
		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}

		wp_remote_post(
			site_url( 'wp-cron.php' ),
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			)
		);

		return array(
			'step'    => 'nudge-wp-cron',
			'ok'      => true,
			'message' => 'Triggered non-blocking wp-cron.php request.',
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $phases
	 */
	private static function phases_ok( array $phases ): bool {
		foreach ( $phases as $phase ) {
			if ( empty( $phase['ok'] ) ) {
				return false;
			}
		}

		return true;
	}
}
