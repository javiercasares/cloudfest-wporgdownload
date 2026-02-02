<?php
/**
 * WPInsight Export Class
 *
 * Handles exporting plugin and theme Custom Post Type data to JSON format
 * for backups, migrations, and data portability. Exports metadata only,
 * not ZIP files.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Export
 * @since      1.2.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export handler for CPT data.
 *
 * Provides methods to export plugin and theme metadata from Custom Post Types
 * to JSON format. Supports compression for large exports.
 *
 * @since 1.2.0
 */
class WPInsight_Export {

	/**
	 * Export schema version.
	 *
	 * Used to track export format changes for compatibility checks during import.
	 *
	 * @since 1.2.0
	 */
	private const SCHEMA_VERSION = '1.0.0';

	/**
	 * Export all plugins as JSON.
	 *
	 * Queries all plugin CPT posts with metadata and formats as JSON.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $compress Optional. Whether to compress output with gzip. Default false.
	 * @return string JSON-encoded plugin data, optionally compressed.
	 */
	public static function export_plugins( bool $compress = false ): string {
		$data = self::get_cpt_data( 'wpinsight_plugin' );

		$export = array(
			'schema_version'  => self::SCHEMA_VERSION,
			'exported_at'     => current_time( 'mysql' ),
			'wp_version'      => get_bloginfo( 'version' ),
			'plugin_version'  => WPINSIGHT_VERSION,
			'export_type'     => 'plugins',
			'plugins'         => array(
				'count' => count( $data ),
				'data'  => $data,
			),
		);

		/**
		 * Filter plugin export data before encoding.
		 *
		 * @since 1.2.0
		 *
		 * @param array $export Export data array.
		 */
		$export = apply_filters( 'wpinsight_export_plugins_data', $export );

		$json = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			WPInsight_Logger::error( 'Failed to encode plugins export to JSON', array( 'count' => count( $data ) ) );
			return '';
		}

		if ( $compress ) {
			$compressed = gzencode( $json, 9 );
			if ( false === $compressed ) {
				WPInsight_Logger::warning( 'Failed to compress plugins export, returning uncompressed' );
				return $json;
			}
			return $compressed;
		}

		return $json;
	}

	/**
	 * Export all themes as JSON.
	 *
	 * Queries all theme CPT posts with metadata and formats as JSON.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $compress Optional. Whether to compress output with gzip. Default false.
	 * @return string JSON-encoded theme data, optionally compressed.
	 */
	public static function export_themes( bool $compress = false ): string {
		$data = self::get_cpt_data( 'wpinsight_theme' );

		$export = array(
			'schema_version'  => self::SCHEMA_VERSION,
			'exported_at'     => current_time( 'mysql' ),
			'wp_version'      => get_bloginfo( 'version' ),
			'plugin_version'  => WPINSIGHT_VERSION,
			'export_type'     => 'themes',
			'themes'          => array(
				'count' => count( $data ),
				'data'  => $data,
			),
		);

		/**
		 * Filter theme export data before encoding.
		 *
		 * @since 1.2.0
		 *
		 * @param array $export Export data array.
		 */
		$export = apply_filters( 'wpinsight_export_themes_data', $export );

		$json = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			WPInsight_Logger::error( 'Failed to encode themes export to JSON', array( 'count' => count( $data ) ) );
			return '';
		}

		if ( $compress ) {
			$compressed = gzencode( $json, 9 );
			if ( false === $compressed ) {
				WPInsight_Logger::warning( 'Failed to compress themes export, returning uncompressed' );
				return $json;
			}
			return $compressed;
		}

		return $json;
	}

	/**
	 * Export all plugins and themes as JSON.
	 *
	 * Combines plugin and theme CPT data into a single export.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $compress Optional. Whether to compress output with gzip. Default false.
	 * @return string JSON-encoded combined data, optionally compressed.
	 */
	public static function export_all( bool $compress = false ): string {
		$plugins = self::get_cpt_data( 'wpinsight_plugin' );
		$themes  = self::get_cpt_data( 'wpinsight_theme' );

		$export = array(
			'schema_version'  => self::SCHEMA_VERSION,
			'exported_at'     => current_time( 'mysql' ),
			'wp_version'      => get_bloginfo( 'version' ),
			'plugin_version'  => WPINSIGHT_VERSION,
			'export_type'     => 'all',
			'plugins'         => array(
				'count' => count( $plugins ),
				'data'  => $plugins,
			),
			'themes'          => array(
				'count' => count( $themes ),
				'data'  => $themes,
			),
		);

		/**
		 * Filter combined export data before encoding.
		 *
		 * @since 1.2.0
		 *
		 * @param array $export Export data array.
		 */
		$export = apply_filters( 'wpinsight_export_all_data', $export );

		$json = wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			WPInsight_Logger::error(
				'Failed to encode combined export to JSON',
				array(
					'plugins' => count( $plugins ),
					'themes'  => count( $themes ),
				)
			);
			return '';
		}

		if ( $compress ) {
			$compressed = gzencode( $json, 9 );
			if ( false === $compressed ) {
				WPInsight_Logger::warning( 'Failed to compress combined export, returning uncompressed' );
				return $json;
			}
			return $compressed;
		}

		return $json;
	}

	/**
	 * Get all CPT data for a given post type.
	 *
	 * Queries all posts of the specified type and extracts metadata.
	 *
	 * @since 1.2.0
	 *
	 * @param string $post_type Post type to query (wpinsight_plugin or wpinsight_theme).
	 * @return array Array of post data with metadata.
	 */
	private static function get_cpt_data( string $post_type ): array {
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'cache_results'  => false,
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return array();
		}

		$data = array();

		foreach ( $query->posts as $post ) {
			$post_data = array(
				'post_id'    => $post->ID,
				'slug'       => $post->post_name,
				'title'      => $post->post_title,
				'content'    => $post->post_content,
				'status'     => $post->post_status,
				'created_at' => $post->post_date,
				'updated_at' => $post->post_modified,
				'meta'       => array(),
			);

			// Get all post meta.
			$meta = get_post_meta( $post->ID );

			if ( is_array( $meta ) ) {
				foreach ( $meta as $key => $values ) {
					// Skip internal WordPress meta.
					if ( str_starts_with( $key, '_' ) ) {
						continue;
					}

					// Unserialize single values.
					if ( count( $values ) === 1 ) {
						$post_data['meta'][ $key ] = maybe_unserialize( $values[0] );
					} else {
						$post_data['meta'][ $key ] = array_map( 'maybe_unserialize', $values );
					}
				}
			}

			/**
			 * Filter individual post data before adding to export.
			 *
			 * @since 1.2.0
			 *
			 * @param array   $post_data Post data array.
			 * @param WP_Post $post      Post object.
			 */
			$post_data = apply_filters( "wpinsight_export_{$post_type}_item", $post_data, $post );

			$data[] = $post_data;
		}

		wp_reset_postdata();

		return $data;
	}

	/**
	 * Get export statistics.
	 *
	 * Returns counts for exportable data.
	 *
	 * @since 1.2.0
	 *
	 * @return array Statistics array with counts.
	 */
	public static function get_export_stats(): array {
		$plugins = wp_count_posts( 'wpinsight_plugin' );
		$themes  = wp_count_posts( 'wpinsight_theme' );

		return array(
			'plugins' => (int) ( $plugins->publish ?? 0 ),
			'themes'  => (int) ( $themes->publish ?? 0 ),
			'total'   => (int) ( $plugins->publish ?? 0 ) + (int) ( $themes->publish ?? 0 ),
		);
	}

	/**
	 * Estimate export file size.
	 *
	 * Provides rough estimate of export file size in bytes.
	 *
	 * @since 1.2.0
	 *
	 * @param string $type Export type: 'plugins', 'themes', or 'all'.
	 * @param bool   $compressed Whether to estimate compressed size.
	 * @return int Estimated size in bytes.
	 */
	public static function estimate_export_size( string $type = 'all', bool $compressed = false ): int {
		$stats = self::get_export_stats();

		// Rough estimate: ~2KB per plugin, ~1.5KB per theme uncompressed.
		$size = 0;

		switch ( $type ) {
			case 'plugins':
				$size = $stats['plugins'] * 2048;
				break;
			case 'themes':
				$size = $stats['themes'] * 1536;
				break;
			case 'all':
			default:
				$size = ( $stats['plugins'] * 2048 ) + ( $stats['themes'] * 1536 );
				break;
		}

		// Add overhead for JSON structure (~10KB).
		$size += 10240;

		// Compression typically achieves 70-80% reduction.
		if ( $compressed ) {
			$size = (int) ( $size * 0.25 );
		}

		return $size;
	}
}
