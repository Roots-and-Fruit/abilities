<?php
/**
 * Read/update robots.txt, llms.txt, and llms-full.txt at the document root.
 *
 * Update-only: files must already exist; no create or delete abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Robots_Llms_Files {

	public const MAX_BYTES = 102400;

	private const AUDIT_OPTION = 'rf_robots_llms_txt_audit';

	private const AUDIT_LIMIT = 20;

	/**
	 * @return array<string, string>
	 */
	public static function allowed_files(): array {
		return array(
			'robots'    => 'robots.txt',
			'llms'      => 'llms.txt',
			'llms-full' => 'llms-full.txt',
		);
	}

	public static function writes_enabled(): bool {
		if ( defined( 'RF_ROBOTS_LLMS_TXT_WRITABLE' ) && ! RF_ROBOTS_LLMS_TXT_WRITABLE ) {
			return false;
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get( array $input ) {
		$file_key = self::normalize_file_key( $input['file'] ?? '' );
		if ( is_wp_error( $file_key ) ) {
			return $file_key;
		}

		$resolved = self::resolve_existing_path( $file_key );
		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		$content = file_get_contents( $resolved['path'] );
		if ( false === $content ) {
			return RF_Errors::invalid_input( sprintf( 'Unable to read "%s".', $resolved['basename'] ) );
		}

		return self::file_payload( $file_key, $resolved['basename'], $resolved['path'], $content );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( array $input ) {
		if ( ! self::writes_enabled() ) {
			return RF_Errors::forbidden( 'robots.txt / llms.txt updates are disabled (RF_ROBOTS_LLMS_TXT_WRITABLE).' );
		}

		$file_key = self::normalize_file_key( $input['file'] ?? '' );
		if ( is_wp_error( $file_key ) ) {
			return $file_key;
		}

		if ( ! isset( $input['content'] ) || ! is_string( $input['content'] ) ) {
			return RF_Errors::invalid_input( 'content is required.' );
		}

		$content = $input['content'];
		if ( '' === $content ) {
			return RF_Errors::invalid_input( 'content cannot be empty (delete is not supported).' );
		}

		if ( ! isset( $input['expected_sha256'] ) || ! is_string( $input['expected_sha256'] ) ) {
			return RF_Errors::invalid_input( 'expected_sha256 is required for optimistic concurrency.' );
		}

		$expected_sha256 = strtolower( trim( $input['expected_sha256'] ) );
		if ( ! preg_match( '/^[a-f0-9]{64}$/', $expected_sha256 ) ) {
			return RF_Errors::invalid_input( 'expected_sha256 must be a 64-character lowercase hex SHA-256 hash.' );
		}

		$resolved = self::resolve_existing_path( $file_key );
		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		$current = file_get_contents( $resolved['path'] );
		if ( false === $current ) {
			return RF_Errors::invalid_input( sprintf( 'Unable to read current "%s".', $resolved['basename'] ) );
		}

		$current_sha256 = hash( 'sha256', $current );
		if ( ! hash_equals( $current_sha256, $expected_sha256 ) ) {
			return RF_Errors::conflict(
				sprintf(
					'"%s" changed on disk since expected_sha256 was captured (current: %s).',
					$resolved['basename'],
					$current_sha256
				)
			);
		}

		$validated = self::validate_content( $file_key, $content );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$dry_run = ! empty( $input['dry_run'] );
		$rollback_on_failure = ! isset( $input['rollback_on_failure'] )
			|| filter_var( $input['rollback_on_failure'], FILTER_VALIDATE_BOOLEAN );

		$before = self::file_payload( $file_key, $resolved['basename'], $resolved['path'], $current );

		if ( $dry_run ) {
			return array(
				'ok'           => true,
				'dry_run'      => true,
				'file'         => $file_key,
				'basename'     => $resolved['basename'],
				'url'          => $before['url'],
				'bytes_before' => $before['bytes'],
				'bytes_after'  => strlen( $content ),
				'sha256_before'=> $before['sha256'],
				'sha256_after' => hash( 'sha256', $content ),
				'verified'     => false,
				'message'      => 'Dry run only; no file written.',
			);
		}

		/**
		 * Filter content immediately before write.
		 *
		 * @param string $content  Proposed file body.
		 * @param string $file_key One of robots, llms, llms-full.
		 */
		$filtered = apply_filters( 'rf_robots_llms_txt_before_write', $content, $file_key );
		if ( is_wp_error( $filtered ) ) {
			return $filtered;
		}
		if ( ! is_string( $filtered ) ) {
			return RF_Errors::invalid_input( 'rf_robots_llms_txt_before_write must return a string or WP_Error.' );
		}

		$revalidated = self::validate_content( $file_key, $filtered );
		if ( is_wp_error( $revalidated ) ) {
			return $revalidated;
		}

		$backup_path = $resolved['path'] . '.bak';
		if ( ! copy( $resolved['path'], $backup_path ) ) {
			return RF_Errors::invalid_input( sprintf( 'Unable to create backup for "%s".', $resolved['basename'] ) );
		}

		$written = self::atomic_write( $resolved['path'], $filtered );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$after_disk = file_get_contents( $resolved['path'] );
		if ( false === $after_disk ) {
			self::restore_backup( $resolved['path'], $backup_path );
			return RF_Errors::invalid_input( sprintf( 'Wrote "%s" but could not re-read for verification.', $resolved['basename'] ) );
		}

		$after_sha256 = hash( 'sha256', $after_disk );
		$expected_after = hash( 'sha256', $filtered );
		$verified       = hash_equals( $after_sha256, $expected_after );

		if ( ! $verified && $rollback_on_failure ) {
			self::restore_backup( $resolved['path'], $backup_path );
			return RF_Errors::invalid_input(
				sprintf( 'Post-write verification failed for "%s"; restored from backup.', $resolved['basename'] )
			);
		}

		$purge = null;
		if ( ! empty( $input['purge_breeze'] ) && RF_Breeze::is_available() ) {
			$purge = RF_Breeze::purge_all();
		}

		self::append_audit_entry(
			array(
				'user_id'    => get_current_user_id(),
				'file'       => $file_key,
				'basename'   => $resolved['basename'],
				'bytes'      => strlen( $filtered ),
				'sha256'     => $after_sha256,
				'verified'   => $verified,
				'timestamp'  => gmdate( 'c' ),
			)
		);

		return array(
			'ok'            => $verified,
			'dry_run'       => false,
			'file'          => $file_key,
			'basename'      => $resolved['basename'],
			'url'           => home_url( '/' . $resolved['basename'] ),
			'bytes_before'  => $before['bytes'],
			'bytes_after'   => strlen( $filtered ),
			'sha256_before' => $before['sha256'],
			'sha256_after'  => $after_sha256,
			'verified'      => $verified,
			'backup_path'   => basename( $backup_path ),
			'purge_breeze'  => $purge,
			'message'       => $verified ? 'File updated and verified.' : 'File updated but verification failed.',
		);
	}

	/**
	 * @return string|WP_Error
	 */
	private static function normalize_file_key( mixed $file_key ) {
		$key = sanitize_key( (string) $file_key );
		if ( '' === $key || ! isset( self::allowed_files()[ $key ] ) ) {
			return RF_Errors::invalid_input(
				'file must be one of: robots, llms, llms-full.'
			);
		}

		return $key;
	}

	/**
	 * @return array{basename: string, path: string}|WP_Error
	 */
	private static function resolve_existing_path( string $file_key ) {
		$basename = self::allowed_files()[ $file_key ];
		$path     = ABSPATH . $basename;

		if ( ! is_file( $path ) ) {
			return RF_Errors::not_found(
				sprintf(
					'"%s" does not exist at the document root. Create is not supported via MCP.',
					$basename
				)
			);
		}

		$real_file = realpath( $path );
		$real_root = realpath( ABSPATH );
		if ( false === $real_file || false === $real_root ) {
			return RF_Errors::invalid_input( 'Unable to resolve document root path.' );
		}

		$normalized_file = wp_normalize_path( $real_file );
		$normalized_root = trailingslashit( wp_normalize_path( $real_root ) );
		if ( ! str_starts_with( $normalized_file, $normalized_root ) ) {
			return RF_Errors::forbidden( 'Resolved path escapes the document root.' );
		}

		if ( is_link( $path ) ) {
			return RF_Errors::forbidden( sprintf( '"%s" is a symlink; refusing to update.', $basename ) );
		}

		return array(
			'basename' => $basename,
			'path'     => $real_file,
		);
	}

	/**
	 * @return true|WP_Error
	 */
	private static function validate_content( string $file_key, string $content ) {
		if ( strlen( $content ) > self::MAX_BYTES ) {
			return RF_Errors::invalid_input(
				sprintf( 'content exceeds maximum size of %d bytes.', self::MAX_BYTES )
			);
		}

		if ( ! mb_check_encoding( $content, 'UTF-8' ) ) {
			return RF_Errors::invalid_input( 'content must be valid UTF-8.' );
		}

		if ( str_contains( $content, "\0" ) ) {
			return RF_Errors::invalid_input( 'content must not contain null bytes.' );
		}

		if ( preg_match( '/<\?(?:php|=)/i', $content ) ) {
			return RF_Errors::invalid_input( 'content must not contain PHP open tags.' );
		}

		if ( in_array( $file_key, array( 'llms', 'llms-full' ), true ) ) {
			if ( preg_match( '/\[cite/i', $content ) ) {
				return RF_Errors::invalid_input( 'llms content must not contain [cite artifacts.' );
			}

			if ( ! str_starts_with( ltrim( $content ), '#' ) ) {
				return RF_Errors::invalid_input( 'llms content must start with a markdown heading (#).' );
			}
		}

		return true;
	}

	/**
	 * @return true|WP_Error
	 */
	private static function atomic_write( string $path, string $content ) {
		$directory = dirname( $path );
		if ( ! is_writable( $directory ) ) {
			return RF_Errors::invalid_input( 'Document root is not writable.' );
		}

		$temp_path = $directory . '/.' . basename( $path ) . '.' . wp_generate_password( 12, false ) . '.tmp';
		$written   = file_put_contents( $temp_path, $content, LOCK_EX );
		if ( false === $written ) {
			return RF_Errors::invalid_input( sprintf( 'Unable to write temporary file for "%s".', basename( $path ) ) );
		}

		@chmod( $temp_path, 0644 );

		if ( ! rename( $temp_path, $path ) ) {
			@unlink( $temp_path );
			return RF_Errors::invalid_input( sprintf( 'Unable to replace "%s".', basename( $path ) ) );
		}

		@chmod( $path, 0644 );

		return true;
	}

	private static function restore_backup( string $path, string $backup_path ): void {
		if ( is_file( $backup_path ) ) {
			@copy( $backup_path, $path );
			@chmod( $path, 0644 );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function file_payload( string $file_key, string $basename, string $path, string $content ): array {
		return array(
			'file'         => $file_key,
			'basename'     => $basename,
			'url'          => home_url( '/' . $basename ),
			'content'      => $content,
			'bytes'        => strlen( $content ),
			'sha256'       => hash( 'sha256', $content ),
			'modified_utc' => gmdate( 'c', (int) filemtime( $path ) ),
		);
	}

	/**
	 * @param array<string, mixed> $entry
	 */
	private static function append_audit_entry( array $entry ): void {
		$log   = get_option( self::AUDIT_OPTION, array() );
		$log   = is_array( $log ) ? $log : array();
		$log[] = $entry;

		if ( count( $log ) > self::AUDIT_LIMIT ) {
			$log = array_slice( $log, -1 * self::AUDIT_LIMIT );
		}

		update_option( self::AUDIT_OPTION, $log, false );
	}
}
