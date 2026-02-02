<?php
/**
 * REST API Controller
 *
 * Provides REST API endpoints for AJAX real-time updates.
 *
 * Endpoints:
 * - GET  /wp-json/wpinsight/v1/dashboard-stats   - Queue statistics
 * - GET  /wp-json/wpinsight/v1/active-downloads  - Active downloads with progress
 * - GET  /wp-json/wpinsight/v1/sync-status       - Sync worker status
 * - POST /wp-json/wpinsight/v1/download-now      - Enqueue single download
 * - POST /wp-json/wpinsight/v1/pause-downloads   - Pause all downloads
 * - POST /wp-json/wpinsight/v1/resume-downloads  - Resume downloads
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage REST_API
 * @since      1.7.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Controller class.
 *
 * @since 1.7.0
 */
class WPInsight_REST_API {

	/**
	 * REST API namespace.
	 *
	 * @since 1.7.0
	 * @var string
	 */
	const NAMESPACE = 'wpinsight/v1';

	/**
	 * Initialize REST API.
	 *
	 * Registers all REST API routes.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public static function register_routes(): void {
		// Dashboard stats endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/dashboard-stats',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'get_dashboard_stats' ],
				'permission_callback' => [ __CLASS__, 'check_admin_permission' ],
			]
		);

		// Active downloads endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/active-downloads',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'get_active_downloads' ],
				'permission_callback' => [ __CLASS__, 'check_admin_permission' ],
			]
		);

		// Sync status endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/sync-status',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'get_sync_status' ],
				'permission_callback' => [ __CLASS__, 'check_admin_permission' ],
			]
		);

		// Download now action endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/download-now',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'download_now' ],
				'permission_callback' => [ __CLASS__, 'check_admin_permission' ],
				'args'                => [
					'slug'    => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'version' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'type'    => [
						'required'          => true,
						'type'              => 'string',
						'enum'              => [ 'plugin', 'theme' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		// Pause downloads action endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/pause-downloads',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'pause_downloads' ],
				'permission_callback' => [ __CLASS__, 'check_admin_permission' ],
			]
		);

		// Resume downloads action endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/resume-downloads',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'resume_downloads' ],
				'permission_callback' => [ __CLASS__, 'check_admin_permission' ],
			]
		);
	}

	/**
	 * Check if user has admin permissions.
	 *
	 * @since 1.7.0
	 * @return bool True if user can manage options.
	 */
	public static function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get dashboard statistics.
	 *
	 * Returns queue statistics for real-time updates.
	 *
	 * @since 1.7.0
	 * @return WP_REST_Response Dashboard stats.
	 */
	public static function get_dashboard_stats(): WP_REST_Response {
		$stats = WPInsight_Zip_Queue::get_queue_stats();

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => [
					'pending'    => $stats['pending'] ?? 0,
					'processing' => $stats['processing'] ?? 0,
					'completed'  => $stats['completed'] ?? 0,
					'failed'     => $stats['failed'] ?? 0,
					'paused'     => WPInsight_Settings::get( 'downloads_paused', false ),
					'timestamp'  => time(),
				],
			],
			200
		);
	}

	/**
	 * Get active downloads with progress.
	 *
	 * Returns currently processing downloads with progress information.
	 *
	 * @since 1.7.0
	 * @return WP_REST_Response Active downloads.
	 */
	public static function get_active_downloads(): WP_REST_Response {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Get active downloads (processing status).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$downloads = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT slug, version, type, file_size, started_at
				FROM {$table}
				WHERE status = %s
				ORDER BY started_at ASC
				LIMIT 10",
				'processing'
			),
			ARRAY_A
		);

		$active = [];

		foreach ( $downloads as $download ) {
			$started   = strtotime( $download['started_at'] );
			$elapsed   = time() - $started;
			$file_size = (int) $download['file_size'];

			// Calculate progress (estimate based on elapsed time and average speed).
			$avg_speed  = 1024 * 1024 * 2; // 2 MB/s average.
			$downloaded = min( $elapsed * $avg_speed, $file_size );
			$progress   = $file_size > 0 ? min( 99, ( $downloaded / $file_size ) * 100 ) : 50;

			// Calculate speed.
			$speed = $elapsed > 0 ? $downloaded / $elapsed : 0;

			$active[] = [
				'slug'       => $download['slug'],
				'version'    => $download['version'],
				'type'       => $download['type'],
				'file_size'  => $file_size,
				'elapsed'    => $elapsed,
				'progress'   => round( $progress, 1 ),
				'speed'      => $speed,
				'speed_text' => size_format( $speed, 1 ) . '/s',
				'filename'   => $download['slug'] . '.' . $download['version'] . '.zip',
			];
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $active,
			],
			200
		);
	}

	/**
	 * Get sync worker status.
	 *
	 * Returns current sync progress for plugins and themes.
	 *
	 * @since 1.7.0
	 * @return WP_REST_Response Sync status.
	 */
	public static function get_sync_status(): WP_REST_Response {
		global $wpdb;

		$sync_table = WPInsight_DB::get_table_name( 'sync_state' );

		// Get plugin sync state.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$plugin_state = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status, current_page, total_pages, items_processed, total_items, updated_at
				FROM {$sync_table}
				WHERE sync_type = %s",
				'plugin'
			),
			ARRAY_A
		);

		// Get theme sync state.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$theme_state = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status, current_page, total_pages, items_processed, total_items, updated_at
				FROM {$sync_table}
				WHERE sync_type = %s",
				'theme'
			),
			ARRAY_A
		);

		$plugin_progress = 0;
		$theme_progress  = 0;

		if ( $plugin_state && $plugin_state['total_items'] > 0 ) {
			$plugin_progress = round( ( $plugin_state['items_processed'] / $plugin_state['total_items'] ) * 100, 1 );
		}

		if ( $theme_state && $theme_state['total_items'] > 0 ) {
			$theme_progress = round( ( $theme_state['items_processed'] / $theme_state['total_items'] ) * 100, 1 );
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => [
					'plugin' => [
						'status'          => $plugin_state['status'] ?? 'idle',
						'progress'        => $plugin_progress,
						'items_processed' => (int) ( $plugin_state['items_processed'] ?? 0 ),
						'total_items'     => (int) ( $plugin_state['total_items'] ?? 0 ),
						'current_page'    => (int) ( $plugin_state['current_page'] ?? 0 ),
						'total_pages'     => (int) ( $plugin_state['total_pages'] ?? 0 ),
						'updated_at'      => $plugin_state['updated_at'] ?? '',
					],
					'theme'  => [
						'status'          => $theme_state['status'] ?? 'idle',
						'progress'        => $theme_progress,
						'items_processed' => (int) ( $theme_state['items_processed'] ?? 0 ),
						'total_items'     => (int) ( $theme_state['total_items'] ?? 0 ),
						'current_page'    => (int) ( $theme_state['current_page'] ?? 0 ),
						'total_pages'     => (int) ( $theme_state['total_pages'] ?? 0 ),
						'updated_at'      => $theme_state['updated_at'] ?? '',
					],
				],
			],
			200
		);
	}

	/**
	 * Download now action handler.
	 *
	 * Enqueues a single version for immediate download.
	 *
	 * @since 1.7.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public static function download_now( WP_REST_Request $request ): WP_REST_Response {
		$slug    = $request->get_param( 'slug' );
		$version = $request->get_param( 'version' );
		$type    = $request->get_param( 'type' );

		// Get download URL from CPT meta.
		$post_type = 'plugin' === $type ? WPInsight_CPT::get_plugin_post_type() : WPInsight_CPT::get_theme_post_type();

		$posts = get_posts(
			[
				'post_type'      => $post_type,
				'name'           => $slug,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			]
		);

		if ( empty( $posts ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Plugin or theme not found.', 'cloudfest-wporgdownload' ),
				],
				404
			);
		}

		$post_id       = $posts[0]->ID;
		$versions_data = get_post_meta( $post_id, '_wpinsight_versions_data', true );

		if ( empty( $versions_data[ $version ] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Version not found.', 'cloudfest-wporgdownload' ),
				],
				404
			);
		}

		$download_url = $versions_data[ $version ];

		// Enqueue download.
		$result = WPInsight_Zip_Queue::enqueue_download( $slug, $version, $type, $download_url, 10 ); // Priority 10 = high.

		if ( $result ) {
			return new WP_REST_Response(
				[
					'success' => true,
					'message' => __( 'Download enqueued successfully.', 'cloudfest-wporgdownload' ),
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Failed to enqueue download.', 'cloudfest-wporgdownload' ),
			],
			500
		);
	}

	/**
	 * Pause downloads action handler.
	 *
	 * @since 1.7.0
	 * @return WP_REST_Response Response.
	 */
	public static function pause_downloads(): WP_REST_Response {
		WPInsight_Settings::set( 'downloads_paused', true );

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Downloads paused.', 'cloudfest-wporgdownload' ),
			],
			200
		);
	}

	/**
	 * Resume downloads action handler.
	 *
	 * @since 1.7.0
	 * @return WP_REST_Response Response.
	 */
	public static function resume_downloads(): WP_REST_Response {
		WPInsight_Settings::set( 'downloads_paused', false );

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Downloads resumed.', 'cloudfest-wporgdownload' ),
			],
			200
		);
	}
}
