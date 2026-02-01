<?php
/**
 * Storage Manager
 *
 * Handles downloading and storing WordPress.org ZIP files with validation
 * and integrity checks.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Includes
 * @since      0.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage Manager Class
 *
 * Manages ZIP file downloads, storage, and artifact recording.
 * Provides centralized storage operations with security and validation.
 *
 * @since 0.1.0
 */
class WPInsight_Storage {

	/**
	 * Maximum file size for downloads (500MB).
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const MAX_FILE_SIZE = 524288000; // 500MB in bytes.

	/**
	 * Download timeout in seconds.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const DOWNLOAD_TIMEOUT = 300; // 5 minutes.

	/**
	 * Download and store ZIP file.
	 *
	 * Downloads a ZIP file from WordPress.org and stores it in the local
	 * filesystem. Creates necessary directories, validates the download,
	 * and records the artifact in the database.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type: 'plugin' or 'theme'.
	 * @param string $slug        Plugin or theme slug.
	 * @param string $version     Version number.
	 * @param string $url         Download URL.
	 * @return array{success: bool, path?: string, size?: int, hash?: string, error?: string} Result array.
	 */
	public static function download_and_store_zip( string $entity_type, string $slug, string $version, string $url ): array {
		// Validate entity type.
		if ( ! in_array( $entity_type, array( 'plugin', 'theme' ), true ) ) {
			return array(
				'success' => false,
				'error'   => 'Invalid entity type',
			);
		}

		// Get storage path.
		$storage_dir = self::get_storage_path( $entity_type, $slug );
		if ( ! $storage_dir ) {
			return array(
				'success' => false,
				'error'   => 'Failed to create storage directory',
			);
		}

		// Build destination file path.
		$dest_file = $storage_dir . "{$slug}.{$version}.zip";

		// Validate path is safe.
		if ( ! self::validate_download_path( $dest_file ) ) {
			return array(
				'success' => false,
				'error'   => 'Invalid destination path',
			);
		}

		// Check if file already exists.
		if ( file_exists( $dest_file ) ) {
			$file_size = filesize( $dest_file );
			$sha256    = hash_file( 'sha256', $dest_file );

			return array(
				'success' => true,
				'path'    => $dest_file,
				'size'    => false !== $file_size ? $file_size : 0,
				'hash'    => false !== $sha256 ? $sha256 : '',
			);
		}

		// Download to temporary file first.
		$temp_file = wp_tempnam( $slug . '-' . $version );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'  => self::DOWNLOAD_TIMEOUT,
				'stream'   => true,
				'filename' => $temp_file,
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		// Check HTTP status.
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'error'   => "HTTP {$status_code}",
			);
		}

		// Verify file was created.
		if ( ! file_exists( $temp_file ) ) {
			return array(
				'success' => false,
				'error'   => 'File not created after download',
			);
		}

		// Verify file size.
		$file_size = filesize( $temp_file );
		if ( false === $file_size || 0 === $file_size ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'error'   => 'Downloaded file is empty or unreadable',
			);
		}

		// Check max file size.
		if ( $file_size > self::MAX_FILE_SIZE ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'error'   => 'File exceeds maximum size limit',
			);
		}

		// Verify it's a valid ZIP file.
		if ( ! self::is_valid_zip( $temp_file ) ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'error'   => 'Downloaded file is not a valid ZIP',
			);
		}

		// Calculate SHA256 hash.
		$sha256 = hash_file( 'sha256', $temp_file );
		if ( false === $sha256 ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'error'   => 'Failed to calculate file hash',
			);
		}

		// Atomic move: temp → final destination.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Need atomic filesystem operation.
		if ( ! rename( $temp_file, $dest_file ) ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'error'   => 'Failed to move file to destination',
			);
		}

		// Record artifact in database.
		self::record_artifact( $entity_type, $slug, $version, $dest_file, $sha256 );

		// Success!
		return array(
			'success' => true,
			'path'    => $dest_file,
			'size'    => $file_size,
			'hash'    => $sha256,
		);
	}

	/**
	 * Get storage path for entity.
	 *
	 * Builds the storage path for a plugin or theme and creates the directory
	 * if it doesn't exist.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type: 'plugin' or 'theme'.
	 * @param string $slug        Plugin or theme slug.
	 * @return string|false Storage path with trailing slash, or false on failure.
	 */
	public static function get_storage_path( string $entity_type, string $slug ) {
		$upload_dir = wp_upload_dir();
		$base_path  = WPInsight_Settings::get( 'storage_base_path', 'wpinsight' );

		// Build path: uploads/wpinsight/{type}/{slug}/.
		$storage_path = trailingslashit( $upload_dir['basedir'] )
			. trailingslashit( $base_path )
			. trailingslashit( $entity_type )
			. trailingslashit( $slug );

		// Create directory if needed.
		if ( ! file_exists( $storage_path ) ) {
			if ( ! wp_mkdir_p( $storage_path ) ) {
				WPInsight_Logger::error(
					'Failed to create storage directory',
					array(
						'path' => $storage_path,
						'slug' => $slug,
						'type' => $entity_type,
					)
				);
				return false;
			}
		}

		return $storage_path;
	}

	/**
	 * Validate download path.
	 *
	 * Ensures the download path is within the WordPress uploads directory
	 * to prevent directory traversal attacks.
	 *
	 * @since 0.1.0
	 * @param string $path Path to validate.
	 * @return bool True if path is valid and safe.
	 */
	private static function validate_download_path( string $path ): bool {
		$upload_dir = wp_upload_dir();
		$base_dir   = realpath( $upload_dir['basedir'] );

		// Resolve path to handle symlinks and relative paths.
		$real_path = realpath( dirname( $path ) );

		// Path doesn't exist yet, check parent directory.
		if ( false === $real_path ) {
			return false;
		}

		// Ensure path is within uploads directory.
		return 0 === strpos( $real_path, $base_dir );
	}

	/**
	 * Record artifact in database.
	 *
	 * Inserts artifact record into the artifacts table. Uses INSERT IGNORE
	 * to prevent duplicate entries.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type: 'plugin' or 'theme'.
	 * @param string $slug        Plugin or theme slug.
	 * @param string $version     Version number.
	 * @param string $path        File path.
	 * @param string $sha256      SHA256 hash.
	 * @return void
	 */
	private static function record_artifact( string $entity_type, string $slug, string $version, string $path, string $sha256 ): void {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'artifacts' );

		$file_size = file_exists( $path ) ? filesize( $path ) : 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Insert artifact record. Table name from get_table_name() is safe.
		$wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO %i
				(artifact_type, artifact_slug, artifact_version, file_path, file_size, sha256_hash, downloaded_at)
				VALUES (%s, %s, %s, %s, %d, %s, %s)',
				$table,
				$entity_type,
				$slug,
				$version,
				$path,
				$file_size,
				$sha256,
				current_time( 'mysql', true )
			)
		);

		// Log artifact creation.
		if ( $wpdb->insert_id > 0 ) {
			WPInsight_Logger::info(
				'Artifact recorded successfully',
				array(
					'type'    => $entity_type,
					'slug'    => $slug,
					'version' => $version,
					'size'    => $file_size,
					'hash'    => substr( $sha256, 0, 16 ) . '...',
				)
			);
		}
	}

	/**
	 * Check if file is a valid ZIP.
	 *
	 * Validates ZIP file by checking magic bytes and attempting to open
	 * with ZipArchive.
	 *
	 * @since 0.1.0
	 * @param string $file_path Path to file.
	 * @return bool True if valid ZIP file.
	 */
	private static function is_valid_zip( string $file_path ): bool {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		// Check magic bytes (PK signature).
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Need to read magic bytes.
		$magic = file_get_contents( $file_path, false, null, 0, 4 );
		if ( false === $magic || 'PK' !== substr( $magic, 0, 2 ) ) {
			return false;
		}

		// Try to open with ZipArchive if available.
		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new ZipArchive();
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Checking ZIP validity.
			$result = $zip->open( $file_path, ZipArchive::RDONLY );
			$zip->close();

			return true === $result;
		}

		// Fallback: magic bytes check passed.
		return true;
	}

	/**
	 * Delete artifact file and record.
	 *
	 * Removes the physical file and deletes the database record.
	 * Used for cleanup operations.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type: 'plugin' or 'theme'.
	 * @param string $slug        Plugin or theme slug.
	 * @param string $version     Version number.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_artifact( string $entity_type, string $slug, string $version ): bool {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'artifacts' );

		// Get artifact record.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Get artifact record. Table name from get_table_name() is safe.
		$artifact = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE artifact_type = %s AND artifact_slug = %s AND artifact_version = %s',
				$table,
				$entity_type,
				$slug,
				$version
			),
			ARRAY_A
		);

		if ( ! $artifact ) {
			return false;
		}

		// Delete physical file.
		$file_path = $artifact['file_path'];
		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}

		// Delete database record.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Delete artifact record. Table name from get_table_name() is safe.
		$deleted = $wpdb->delete(
			$table,
			array(
				'artifact_type'    => $entity_type,
				'artifact_slug'    => $slug,
				'artifact_version' => $version,
			),
			array( '%s', '%s', '%s' )
		);

		if ( $deleted ) {
			WPInsight_Logger::info(
				'Artifact deleted',
				array(
					'type'    => $entity_type,
					'slug'    => $slug,
					'version' => $version,
				)
			);
		}

		return (bool) $deleted;
	}

	/**
	 * Get artifact info.
	 *
	 * Retrieves artifact record from database.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Entity type: 'plugin' or 'theme'.
	 * @param string $slug        Plugin or theme slug.
	 * @param string $version     Version number.
	 * @return array<string, mixed>|null Artifact data array or null if not found.
	 */
	public static function get_artifact( string $entity_type, string $slug, string $version ): ?array {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'artifacts' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Get artifact record. Table name from get_table_name() is safe.
		$artifact = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE artifact_type = %s AND artifact_slug = %s AND artifact_version = %s',
				$table,
				$entity_type,
				$slug,
				$version
			),
			ARRAY_A
		);

		return $artifact ? $artifact : null;
	}
}
