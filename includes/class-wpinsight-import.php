<?php
/**
 * WPInsight Import Class
 *
 * Handles importing plugin and theme Custom Post Type data from JSON exports.
 * Supports various import options including skip/update existing posts.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Import
 * @since      1.2.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import handler for CPT data.
 *
 * Provides methods to import plugin and theme metadata from JSON exports
 * back into Custom Post Types. Supports various options for handling duplicates.
 *
 * @since 1.2.0
 */
class WPInsight_Import {

	/**
	 * Supported schema versions.
	 *
	 * Array of schema versions this importer can handle.
	 *
	 * @since 1.2.0
	 */
	private const SUPPORTED_SCHEMA_VERSIONS = [ '1.0.0' ];

	/**
	 * Import from file.
	 *
	 * Loads and parses JSON export file. Handles gzip compression automatically.
	 *
	 * @since 1.2.0
	 *
	 * @param string $file_path Absolute path to import file.
	 * @return array|WP_Error Parsed data array on success, WP_Error on failure.
	 */
	public static function import_from_file( string $file_path ) {
		// Validate file exists and is readable.
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'Import file not found.', 'cloudfest-wporgdownload' ) );
		}

		if ( ! is_readable( $file_path ) ) {
			return new WP_Error( 'file_not_readable', __( 'Import file is not readable.', 'cloudfest-wporgdownload' ) );
		}

		// Read file contents.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file, not remote.
		$contents = file_get_contents( $file_path );

		if ( false === $contents ) {
			return new WP_Error( 'read_failed', __( 'Failed to read import file.', 'cloudfest-wporgdownload' ) );
		}

		// Detect and decompress gzip.
		if ( str_ends_with( $file_path, '.gz' ) || str_starts_with( $contents, "\x1f\x8b" ) ) {
			$decompressed = gzdecode( $contents );
			if ( false === $decompressed ) {
				return new WP_Error( 'decompress_failed', __( 'Failed to decompress gzip file.', 'cloudfest-wporgdownload' ) );
			}
			$contents = $decompressed;
		}

		// Parse JSON.
		$data = json_decode( $contents, true );

		if ( null === $data ) {
			return new WP_Error(
				'json_parse_error',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Failed to parse JSON: %s', 'cloudfest-wporgdownload' ),
					json_last_error_msg()
				)
			);
		}

		// Validate schema version.
		if ( ! isset( $data['schema_version'] ) ) {
			return new WP_Error( 'missing_schema_version', __( 'Import file missing schema version.', 'cloudfest-wporgdownload' ) );
		}

		if ( ! in_array( $data['schema_version'], self::SUPPORTED_SCHEMA_VERSIONS, true ) ) {
			return new WP_Error(
				'unsupported_schema_version',
				sprintf(
					/* translators: %s: schema version */
					__( 'Unsupported schema version: %s', 'cloudfest-wporgdownload' ),
					$data['schema_version']
				)
			);
		}

		WPInsight_Logger::info(
			'Import file loaded successfully',
			[
				'file'           => basename( $file_path ),
				'schema_version' => $data['schema_version'],
				'export_type'    => $data['export_type'] ?? 'unknown',
			]
		);

		return $data;
	}

	/**
	 * Import plugins from data array.
	 *
	 * Imports plugin CPT posts from parsed export data.
	 *
	 * @since 1.2.0
	 *
	 * @param array $data    Parsed export data.
	 * @param array $options Optional. Import options. Default empty array.
	 * @return array Import results with counts and errors.
	 */
	public static function import_plugins( array $data, array $options = [] ): array {
		// Validate data structure.
		if ( ! isset( $data['plugins']['data'] ) || ! is_array( $data['plugins']['data'] ) ) {
			return [
				'success'  => false,
				'error'    => __( 'Invalid plugins data structure.', 'cloudfest-wporgdownload' ),
				'imported' => 0,
				'updated'  => 0,
				'skipped'  => 0,
				'failed'   => 0,
				'errors'   => [],
			];
		}

		return self::import_cpt_data( 'wpinsight_plugin', $data['plugins']['data'], $options );
	}

	/**
	 * Import themes from data array.
	 *
	 * Imports theme CPT posts from parsed export data.
	 *
	 * @since 1.2.0
	 *
	 * @param array $data    Parsed export data.
	 * @param array $options Optional. Import options. Default empty array.
	 * @return array Import results with counts and errors.
	 */
	public static function import_themes( array $data, array $options = [] ): array {
		// Validate data structure.
		if ( ! isset( $data['themes']['data'] ) || ! is_array( $data['themes']['data'] ) ) {
			return [
				'success'  => false,
				'error'    => __( 'Invalid themes data structure.', 'cloudfest-wporgdownload' ),
				'imported' => 0,
				'updated'  => 0,
				'skipped'  => 0,
				'failed'   => 0,
				'errors'   => [],
			];
		}

		return self::import_cpt_data( 'wpinsight_theme', $data['themes']['data'], $options );
	}

	/**
	 * Import all data from file.
	 *
	 * Convenience method to import both plugins and themes from a file.
	 *
	 * @since 1.2.0
	 *
	 * @param string $file_path Absolute path to import file.
	 * @param array  $options   Optional. Import options. Default empty array.
	 * @return array Import results with counts and errors.
	 */
	public static function import_all( string $file_path, array $options = [] ): array {
		$data = self::import_from_file( $file_path );

		if ( is_wp_error( $data ) ) {
			return [
				'success' => false,
				'error'   => $data->get_error_message(),
			];
		}

		$results = [
			'success'  => true,
			'plugins'  => [],
			'themes'   => [],
			'imported' => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'errors'   => [],
		];

		// Import plugins if present.
		if ( isset( $data['plugins']['data'] ) ) {
			$plugin_results       = self::import_plugins( $data, $options );
			$results['plugins']   = $plugin_results;
			$results['imported'] += $plugin_results['imported'];
			$results['updated']  += $plugin_results['updated'];
			$results['skipped']  += $plugin_results['skipped'];
			$results['failed']   += $plugin_results['failed'];
			$results['errors']    = array_merge( $results['errors'], $plugin_results['errors'] );
		}

		// Import themes if present.
		if ( isset( $data['themes']['data'] ) ) {
			$theme_results        = self::import_themes( $data, $options );
			$results['themes']    = $theme_results;
			$results['imported'] += $theme_results['imported'];
			$results['updated']  += $theme_results['updated'];
			$results['skipped']  += $theme_results['skipped'];
			$results['failed']   += $theme_results['failed'];
			$results['errors']    = array_merge( $results['errors'], $theme_results['errors'] );
		}

		WPInsight_Logger::info(
			'Import completed',
			[
				'imported' => $results['imported'],
				'updated'  => $results['updated'],
				'skipped'  => $results['skipped'],
				'failed'   => $results['failed'],
			]
		);

		return $results;
	}

	/**
	 * Import CPT data for a given post type.
	 *
	 * Generic method to import data for any CPT.
	 *
	 * @since 1.2.0
	 *
	 * @param string $post_type Post type to import (wpinsight_plugin or wpinsight_theme).
	 * @param array  $items     Array of post data to import.
	 * @param array  $options   Import options.
	 * @return array Import results.
	 */
	private static function import_cpt_data( string $post_type, array $items, array $options ): array {
		$defaults = [
			'skip_existing'   => true,
			'update_existing' => false,
			'dry_run'         => false,
			'batch_size'      => 100,
		];

		$options = wp_parse_args( $options, $defaults );

		$results = [
			'success'  => true,
			'imported' => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'errors'   => [],
		];

		$batch_count = 0;

		foreach ( $items as $item ) {
			// Validate required fields.
			if ( empty( $item['slug'] ) || empty( $item['title'] ) ) {
				++$results['failed'];
				$results['errors'][ $item['slug'] ?? 'unknown' ] = __( 'Missing required fields (slug or title).', 'cloudfest-wporgdownload' );
				continue;
			}

			$slug = sanitize_title( $item['slug'] );

			// Check if post exists.
			$existing = get_page_by_path( $slug, OBJECT, $post_type );

			if ( $existing ) {
				if ( $options['skip_existing'] ) {
					++$results['skipped'];
					continue;
				}

				if ( ! $options['update_existing'] ) {
					++$results['failed'];
					$results['errors'][ $slug ] = __( 'Post already exists.', 'cloudfest-wporgdownload' );
					continue;
				}
			}

			// Prepare post data.
			$post_data = [
				'post_type'    => $post_type,
				'post_name'    => $slug,
				'post_title'   => sanitize_text_field( $item['title'] ),
				'post_content' => wp_kses_post( $item['content'] ?? '' ),
				'post_status'  => 'publish',
			];

			if ( $existing ) {
				$post_data['ID'] = $existing->ID;
			}

			/**
			 * Filter post data before importing.
			 *
			 * @since 1.2.0
			 *
			 * @param array $post_data Prepared post data.
			 * @param array $item      Original import item.
			 * @param array $options   Import options.
			 */
			$post_data = apply_filters( "wpinsight_import_{$post_type}_data", $post_data, $item, $options );

			// Dry run: don't actually import.
			if ( $options['dry_run'] ) {
				if ( $existing ) {
					++$results['updated'];
				} else {
					++$results['imported'];
				}
				continue;
			}

			// Insert or update post.
			if ( $existing ) {
				$post_id = wp_update_post( $post_data, true );
			} else {
				$post_id = wp_insert_post( $post_data, true );
			}

			if ( is_wp_error( $post_id ) ) {
				++$results['failed'];
				$results['errors'][ $slug ] = $post_id->get_error_message();
				WPInsight_Logger::error(
					'Failed to import post',
					[
						'slug'  => $slug,
						'error' => $post_id->get_error_message(),
					]
				);
				continue;
			}

			// Import meta data.
			if ( ! empty( $item['meta'] ) && is_array( $item['meta'] ) ) {
				foreach ( $item['meta'] as $key => $value ) {
					update_post_meta( $post_id, sanitize_key( $key ), $value );
				}
			}

			if ( $existing ) {
				++$results['updated'];
			} else {
				++$results['imported'];
			}

			// Batch processing: pause periodically.
			++$batch_count;
			if ( $batch_count >= $options['batch_size'] ) {
				$batch_count = 0;
				wp_cache_flush();
			}
		}

		return $results;
	}

	/**
	 * Validate import file without importing.
	 *
	 * Performs validation checks without modifying database.
	 *
	 * @since 1.2.0
	 *
	 * @param string $file_path Absolute path to import file.
	 * @return array|WP_Error Validation results or WP_Error on failure.
	 */
	public static function validate_import_file( string $file_path ) {
		$data = self::import_from_file( $file_path );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$validation = [
			'valid'          => true,
			'schema_version' => $data['schema_version'],
			'export_type'    => $data['export_type'] ?? 'unknown',
			'exported_at'    => $data['exported_at'] ?? 'unknown',
			'plugins_count'  => $data['plugins']['count'] ?? 0,
			'themes_count'   => $data['themes']['count'] ?? 0,
			'warnings'       => [],
		];

		// Check for empty data.
		if ( empty( $data['plugins']['data'] ) && empty( $data['themes']['data'] ) ) {
			$validation['warnings'][] = __( 'Import file contains no data.', 'cloudfest-wporgdownload' );
		}

		// Validate plugin data structure.
		if ( ! empty( $data['plugins']['data'] ) ) {
			foreach ( $data['plugins']['data'] as $index => $item ) {
				if ( empty( $item['slug'] ) || empty( $item['title'] ) ) {
					$validation['warnings'][] = sprintf(
						/* translators: %d: item index */
						__( 'Plugin at index %d missing required fields.', 'cloudfest-wporgdownload' ),
						$index
					);
				}
			}
		}

		// Validate theme data structure.
		if ( ! empty( $data['themes']['data'] ) ) {
			foreach ( $data['themes']['data'] as $index => $item ) {
				if ( empty( $item['slug'] ) || empty( $item['title'] ) ) {
					$validation['warnings'][] = sprintf(
						/* translators: %d: item index */
						__( 'Theme at index %d missing required fields.', 'cloudfest-wporgdownload' ),
						$index
					);
				}
			}
		}

		return $validation;
	}
}
