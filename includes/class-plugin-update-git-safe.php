<?php
/**
 * Composite Git Updater plugin deploy workflow.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Plugin_Update_Git_Safe {

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function run( array $input ) {
		$plugin_dir_slug = sanitize_key( (string) ( $input['slug'] ?? '' ) );
		if ( '' === $plugin_dir_slug ) {
			return RF_Errors::invalid_input( 'slug is required (plugin directory name).' );
		}

		if ( ! RF_Git_Updater::plugin_active() ) {
			return RF_Errors::git_updater_unavailable();
		}

		$git_slug          = isset( $input['git_slug'] ) ? sanitize_key( (string) $input['git_slug'] ) : '';
		$target_version    = isset( $input['target_version'] ) ? trim( (string) $input['target_version'] ) : '';
		$refresh_cache     = ! isset( $input['refresh_cache'] ) || filter_var( $input['refresh_cache'], FILTER_VALIDATE_BOOLEAN );
		$rollback_on_fail  = ! isset( $input['rollback_on_failure'] ) || filter_var( $input['rollback_on_failure'], FILTER_VALIDATE_BOOLEAN );
		$purge_breeze      = ! isset( $input['purge_breeze'] ) || filter_var( $input['purge_breeze'], FILTER_VALIDATE_BOOLEAN );
		$force_override    = ! isset( $input['force_override'] ) || filter_var( $input['force_override'], FILTER_VALIDATE_BOOLEAN );

		$resolved_git_slug = RF_Git_Updater::resolve_git_slug( $plugin_dir_slug, $git_slug );
		if ( is_wp_error( $resolved_git_slug ) ) {
			return $resolved_git_slug;
		}

		$phases = array();

		$pre = RF_Plugin_Updater::get_installed_version( $plugin_dir_slug );
		if ( is_wp_error( $pre ) ) {
			return $pre;
		}

		$phases[] = self::phase(
			'capture-baseline',
			true,
			'Recorded pre-update version.',
			array(
				'plugin_dir_slug' => $plugin_dir_slug,
				'git_slug'        => $resolved_git_slug,
				'version'         => $pre,
			)
		);

		if ( $refresh_cache ) {
			$refresh = RF_Git_Updater::refresh_caches( $resolved_git_slug );
			$phases[] = self::phase(
				'refresh-git-updater-cache',
				! empty( $refresh['ok'] ),
				! empty( $refresh['ok'] ) ? 'Git Updater caches refreshed.' : 'Git Updater cache refresh had failures.',
				$refresh
			);
		}

		$api = RF_Git_Updater::fetch_update_api( $resolved_git_slug );
		if ( is_wp_error( $api ) ) {
			$phases[] = self::phase( 'fetch-update-api', false, $api->get_error_message(), array() );

			return self::result( false, $plugin_dir_slug, $resolved_git_slug, $pre, $pre, false, '', $phases, $api->get_error_message() );
		}

		$remote_version = (string) ( $api['version'] ?? '' );
		$phases[] = self::phase(
			'fetch-update-api',
			true,
			$remote_version ? sprintf( 'Remote version available: %s', $remote_version ) : 'Update API returned metadata.',
			array(
				'remote_version' => $remote_version,
				'primary_branch' => (string) ( $api['primary_branch'] ?? $api['branch'] ?? '' ),
			)
		);

		$tag = $target_version;
		if ( '' === $tag && '' !== $remote_version ) {
			$tag = $remote_version;
		}

		if ( '' !== $tag && $pre === ltrim( $tag, 'v' ) ) {
			$phases[] = self::phase( 'update', true, 'Plugin already at target version.', array( 'skipped' => true ) );

			return self::result( true, $plugin_dir_slug, $resolved_git_slug, $pre, $pre, false, '', $phases, 'Plugin already at target version.' );
		}

		$update = RF_Git_Updater::run_update( $resolved_git_slug, $tag, $force_override );
		if ( is_wp_error( $update ) ) {
			$phases[] = self::phase( 'update', false, $update->get_error_message(), array() );

			return self::result( false, $plugin_dir_slug, $resolved_git_slug, $pre, $pre, false, '', $phases, 'Git Updater update failed.' );
		}

		$post = RF_Plugin_Updater::get_installed_version( $plugin_dir_slug );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$phases[] = self::phase( 'update', true, 'Plugin updated via Git Updater.', $update );

		$smoke = RF_Site_Smoke_Test::run_default();
		if ( empty( $smoke['ok'] ) ) {
			$phases[] = self::phase(
				'smoke-test',
				false,
				(string) ( $smoke['message'] ?? 'Smoke test failed.' ),
				$smoke
			);

			if ( ! $rollback_on_fail ) {
				return self::result(
					false,
					$plugin_dir_slug,
					$resolved_git_slug,
					$pre,
					$post,
					false,
					'',
					$phases,
					'Smoke test failed; rollback was disabled.'
				);
			}

			$rollback = RF_Wp_Rollback::rollback_plugin( $plugin_dir_slug, $pre );
			if ( is_wp_error( $rollback ) ) {
				$phases[] = self::phase( 'rollback', false, $rollback->get_error_message(), array() );

				return self::result(
					false,
					$plugin_dir_slug,
					$resolved_git_slug,
					$pre,
					$post,
					false,
					'',
					$phases,
					'Smoke test failed and rollback could not complete.'
				);
			}

			$rollback_ok = ! empty( $rollback['ok'] );
			$phases[] = self::phase(
				'rollback',
				$rollback_ok,
				$rollback_ok ? 'Rolled back to pre-update version.' : 'Rollback failed.',
				$rollback
			);

			$final = RF_Plugin_Updater::get_installed_version( $plugin_dir_slug );
			$final_version = is_wp_error( $final ) ? $pre : $final;

			return self::result(
				false,
				$plugin_dir_slug,
				$resolved_git_slug,
				$pre,
				$final_version,
				$rollback_ok,
				$pre,
				$phases,
				$rollback_ok
					? sprintf( 'Smoke test failed; rolled back to %s.', $pre )
					: 'Smoke test failed; rollback did not complete successfully.'
			);
		}

		$phases[] = self::phase(
			'smoke-test',
			true,
			(string) ( $smoke['message'] ?? 'Smoke test passed.' ),
			$smoke
		);

		if ( $purge_breeze && RF_Breeze::is_available() ) {
			$purge = RF_Breeze::purge_all();
			$phases[] = self::phase(
				'purge-breeze',
				! empty( $purge['ok'] ),
				(string) ( $purge['message'] ?? 'Breeze purge attempted.' ),
				$purge
			);
		}

		return self::result(
			true,
			$plugin_dir_slug,
			$resolved_git_slug,
			$pre,
			$post,
			false,
			'',
			$phases,
			'Git plugin update succeeded.'
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $phases
	 * @return array<string, mixed>
	 */
	private static function result(
		bool $ok,
		string $plugin_dir_slug,
		string $git_slug,
		string $pre,
		string $post,
		bool $rolled_back,
		string $rollback_version,
		array $phases,
		string $message
	): array {
		return array(
			'ok'              => $ok,
			'slug'            => $plugin_dir_slug,
			'git_slug'        => $git_slug,
			'pre_version'     => $pre,
			'post_version'    => $post,
			'rolled_back'     => $rolled_back,
			'rollback_version'=> $rollback_version,
			'message'         => $message,
			'phases'          => $phases,
		);
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private static function phase( string $phase, bool $ok, string $message, array $data ): array {
		return array(
			'phase'   => $phase,
			'ok'      => $ok,
			'message' => $message,
			'data'    => $data,
		);
	}
}
