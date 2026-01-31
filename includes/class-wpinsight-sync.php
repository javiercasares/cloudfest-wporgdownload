<?php
/**
 * Sync Engine Class
 *
 * Manages synchronization of WordPress.org plugin and theme metadata.
 * Coordinates with the API Client to fetch data and stores it in CPTs.
 * Uses Action Scheduler for background processing.
 *
 * Sync Process:
 * 1. Check if sync is enabled for plugins/themes
 * 2. Query WordPress.org API for paginated results
 * 3. Store metadata in Custom Post Types
 * 4. Enqueue ZIP downloads (if enabled)
 * 5. Update sync state in database
 * 6. Continue to next page or mark as complete
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Includes
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync engine class.
 *
 * This class uses static methods only and is never instantiated.
 * It coordinates the synchronization process with WordPress.org.
 *
 * @since 0.1.0
 */
final class WPInsight_Sync {

	/**
	 * Sync state: Not started.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const STATE_IDLE = 'idle';

	/**
	 * Sync state: In progress.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const STATE_RUNNING = 'running';

	/**
	 * Sync state: Completed.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const STATE_COMPLETED = 'completed';

	/**
	 * Sync state: Paused.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const STATE_PAUSED = 'paused';

	/**
	 * Sync state: Error.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const STATE_ERROR = 'error';

	/**
	 * Initialize the sync engine.
	 *
	 * Registers Action Scheduler hooks for background processing.
	 * Called from bootstrap on plugins_loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function init(): void {
		// Register sync tick handler.
		add_action( WPINSIGHT_SYNC_TICK_ACTION, [ __CLASS__, 'sync_tick' ] );
	}

	/**
	 * Ensure sync job is scheduled.
	 *
	 * Called during plugin activation to set up recurring sync job.
	 * Uses Action Scheduler to run sync_tick every X seconds.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function ensure_scheduled(): void {
		// Check if Action Scheduler is available.
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		// Check if already scheduled.
		$next_run = as_next_scheduled_action( WPINSIGHT_SYNC_TICK_ACTION, [], WPINSIGHT_AS_GROUP );
		if ( false !== $next_run ) {
			return; // Already scheduled.
		}

		// Schedule recurring sync tick.
		$interval = WPInsight_Settings::get( 'sync_interval', 300 ); // Default: 5 minutes.
		as_schedule_recurring_action(
			time(),
			$interval,
			WPINSIGHT_SYNC_TICK_ACTION,
			[],
			WPINSIGHT_AS_GROUP
		);
	}

	/**
	 * Sync tick handler.
	 *
	 * Called by Action Scheduler on schedule. Processes one page of plugins
	 * or themes and updates sync state.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function sync_tick(): void {
		// Check if auto sync is enabled.
		if ( ! WPInsight_Settings::get( 'auto_sync_enabled', true ) ) {
			return;
		}

		// Sync plugins if enabled.
		if ( WPInsight_Settings::get( 'sync_plugins_enabled', true ) ) {
			self::sync_plugins();
		}

		// Sync themes if enabled.
		if ( WPInsight_Settings::get( 'sync_themes_enabled', true ) ) {
			self::sync_themes();
		}
	}

	/**
	 * Sync plugins.
	 *
	 * Fetches one page of plugins from WordPress.org and processes them.
	 * Updates sync state after processing.
	 *
	 * @since 0.1.0
	 * @return bool True on success, false on failure.
	 */
	public static function sync_plugins(): bool {
		// Get current sync state.
		$state = self::get_sync_state( 'plugin' );

		// Skip if already completed or in error state.
		if ( in_array( $state['status'], [ self::STATE_COMPLETED, self::STATE_ERROR ], true ) ) {
			return false;
		}

		// Mark as running.
		self::update_sync_state( 'plugin', self::STATE_RUNNING, $state['page'] );

		// Fetch plugins from API.
		$per_page = WPInsight_Settings::get( 'per_page', 100 );
		$response = WPInsight_WPOrg_Client::query_plugins(
			[
				'browse'   => 'updated',
				'page'     => $state['page'],
				'per_page' => $per_page,
			]
		);

		// Handle API error.
		if ( false === $response ) {
			self::update_sync_state( 'plugin', self::STATE_ERROR, $state['page'], 'API request failed' );
			return false;
		}

		// Process each plugin.
		$processed = 0;
		if ( isset( $response['plugins'] ) && is_array( $response['plugins'] ) ) {
			foreach ( $response['plugins'] as $plugin ) {
				if ( self::process_plugin( $plugin ) ) {
					++$processed;
				}
			}
		}

		// Check if we've reached the end.
		$total_pages = isset( $response['info']->pages ) ? (int) $response['info']->pages : 0;
		$has_more    = $state['page'] < $total_pages;

		if ( $has_more ) {
			// Move to next page.
			self::update_sync_state( 'plugin', self::STATE_RUNNING, $state['page'] + 1 );
		} else {
			// Mark as completed.
			self::update_sync_state( 'plugin', self::STATE_COMPLETED, $state['page'] );
		}

		return true;
	}

	/**
	 * Sync themes.
	 *
	 * Fetches one page of themes from WordPress.org and processes them.
	 * Updates sync state after processing.
	 *
	 * @since 0.1.0
	 * @return bool True on success, false on failure.
	 */
	public static function sync_themes(): bool {
		// Get current sync state.
		$state = self::get_sync_state( 'theme' );

		// Skip if already completed or in error state.
		if ( in_array( $state['status'], [ self::STATE_COMPLETED, self::STATE_ERROR ], true ) ) {
			return false;
		}

		// Mark as running.
		self::update_sync_state( 'theme', self::STATE_RUNNING, $state['page'] );

		// Fetch themes from API.
		$per_page = WPInsight_Settings::get( 'per_page', 100 );
		$response = WPInsight_WPOrg_Client::query_themes(
			[
				'browse'   => 'updated',
				'page'     => $state['page'],
				'per_page' => $per_page,
			]
		);

		// Handle API error.
		if ( false === $response ) {
			self::update_sync_state( 'theme', self::STATE_ERROR, $state['page'], 'API request failed' );
			return false;
		}

		// Process each theme.
		$processed = 0;
		if ( isset( $response['themes'] ) && is_array( $response['themes'] ) ) {
			foreach ( $response['themes'] as $theme ) {
				if ( self::process_theme( $theme ) ) {
					++$processed;
				}
			}
		}

		// Check if we've reached the end.
		$total_pages = isset( $response['info']->pages ) ? (int) $response['info']->pages : 0;
		$has_more    = $state['page'] < $total_pages;

		if ( $has_more ) {
			// Move to next page.
			self::update_sync_state( 'theme', self::STATE_RUNNING, $state['page'] + 1 );
		} else {
			// Mark as completed.
			self::update_sync_state( 'theme', self::STATE_COMPLETED, $state['page'] );
		}

		return true;
	}

	/**
	 * Process a single plugin.
	 *
	 * Creates or updates the plugin CPT and enqueues ZIP downloads if enabled.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $plugin Plugin data from API.
	 * @return bool True on success, false on failure.
	 */
	private static function process_plugin( array $plugin ): bool {
		// Validate required fields.
		if ( empty( $plugin['slug'] ) || empty( $plugin['name'] ) ) {
			return false;
		}

		// Find or create plugin post.
		$post_id = WPInsight_CPT::find_or_create_plugin( $plugin['slug'], $plugin['name'] );
		if ( 0 === $post_id ) {
			return false;
		}

		// Save plugin metadata.
		WPInsight_CPT::save_plugin_meta( $post_id, $plugin );

		// Enqueue ZIP downloads if enabled.
		if ( WPInsight_Settings::get( 'download_plugin_zips_enabled', true ) ) {
			// Get full plugin info to access versions.
			$full_info = WPInsight_WPOrg_Client::get_plugin_info( $plugin['slug'] );
			if ( false !== $full_info && isset( $full_info['versions'] ) && is_array( $full_info['versions'] ) ) {
				self::enqueue_plugin_downloads( $plugin['slug'], $full_info['versions'], $post_id );
			}
		}

		return true;
	}

	/**
	 * Process a single theme.
	 *
	 * Creates or updates the theme CPT and enqueues ZIP downloads if enabled.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $theme Theme data from API.
	 * @return bool True on success, false on failure.
	 */
	private static function process_theme( array $theme ): bool {
		// Validate required fields.
		if ( empty( $theme['slug'] ) || empty( $theme['name'] ) ) {
			return false;
		}

		// Find or create theme post.
		$post_id = WPInsight_CPT::find_or_create_theme( $theme['slug'], $theme['name'] );
		if ( 0 === $post_id ) {
			return false;
		}

		// Save theme metadata.
		WPInsight_CPT::save_theme_meta( $post_id, $theme );

		// Enqueue ZIP downloads if enabled.
		if ( WPInsight_Settings::get( 'download_theme_zips_enabled', true ) ) {
			// Get full theme info to access versions.
			$full_info = WPInsight_WPOrg_Client::get_theme_info( $theme['slug'] );
			if ( false !== $full_info && isset( $full_info['versions'] ) && is_array( $full_info['versions'] ) ) {
				self::enqueue_theme_downloads( $theme['slug'], $full_info['versions'], $post_id );
			}
		}

		return true;
	}

	/**
	 * Enqueue plugin ZIP downloads.
	 *
	 * Adds download jobs to the zip_queue table for each version.
	 *
	 * @since 0.1.0
	 * @param string $slug     Plugin slug.
	 * @param array  $versions Versions array (version => download_url).
	 * @param int    $post_id  Plugin post ID.
	 * @return void
	 */
	private static function enqueue_plugin_downloads( string $slug, array $versions, int $post_id ): void {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		foreach ( $versions as $version => $download_url ) {
			// Skip if already in queue.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE artifact_type = %s AND artifact_slug = %s AND artifact_version = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'plugin',
					$slug,
					$version
				)
			);

			if ( $exists ) {
				continue; // Already queued.
			}

			// Insert into queue.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				[
					'artifact_type'    => 'plugin',
					'artifact_slug'    => $slug,
					'artifact_version' => $version,
					'artifact_post_id' => $post_id,
					'download_url'     => $download_url,
					'status'           => 'pending',
					'priority'         => 50, // Normal priority.
					'attempts'         => 0,
					'queued_at'        => current_time( 'mysql', true ),
				],
				[ '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s' ]
			);
		}
	}

	/**
	 * Enqueue theme ZIP downloads.
	 *
	 * Adds download jobs to the zip_queue table for each version.
	 *
	 * @since 0.1.0
	 * @param string $slug     Theme slug.
	 * @param array  $versions Versions array (version => download_url).
	 * @param int    $post_id  Theme post ID.
	 * @return void
	 */
	private static function enqueue_theme_downloads( string $slug, array $versions, int $post_id ): void {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		foreach ( $versions as $version => $download_url ) {
			// Skip if already in queue.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE artifact_type = %s AND artifact_slug = %s AND artifact_version = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'theme',
					$slug,
					$version
				)
			);

			if ( $exists ) {
				continue; // Already queued.
			}

			// Insert into queue.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table,
				[
					'artifact_type'    => 'theme',
					'artifact_slug'    => $slug,
					'artifact_version' => $version,
					'artifact_post_id' => $post_id,
					'download_url'     => $download_url,
					'status'           => 'pending',
					'priority'         => 50, // Normal priority.
					'attempts'         => 0,
					'queued_at'        => current_time( 'mysql', true ),
				],
				[ '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s' ]
			);
		}
	}

	/**
	 * Get sync state.
	 *
	 * Retrieves the current sync state from the database.
	 *
	 * @since 0.1.0
	 * @param string $type Sync type ('plugin' or 'theme').
	 * @return array {
	 *     Sync state data.
	 *
	 *     @type string $status      Sync status.
	 *     @type int    $page        Current page.
	 *     @type string $last_error  Last error message (if any).
	 *     @type string $updated_at  Last update timestamp.
	 * }
	 */
	public static function get_sync_state( string $type ): array {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'sync_state' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE sync_type = %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$type
			),
			ARRAY_A
		);

		if ( ! $row ) {
			// No state yet, return defaults.
			return [
				'status'     => self::STATE_IDLE,
				'page'       => 1,
				'last_error' => '',
				'updated_at' => current_time( 'mysql', true ),
			];
		}

		return [
			'status'     => $row['status'],
			'page'       => (int) $row['page'],
			'last_error' => $row['last_error'] ?? '',
			'updated_at' => $row['updated_at'],
		];
	}

	/**
	 * Update sync state.
	 *
	 * Updates or creates sync state in the database.
	 *
	 * @since 0.1.0
	 * @param string $type       Sync type ('plugin' or 'theme').
	 * @param string $status     New status.
	 * @param int    $page       Current page.
	 * @param string $last_error Last error message (optional).
	 * @return bool True on success, false on failure.
	 */
	public static function update_sync_state( string $type, string $status, int $page, string $last_error = '' ): bool {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'sync_state' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace(
			$table,
			[
				'sync_type'  => $type,
				'status'     => $status,
				'page'       => $page,
				'last_error' => $last_error,
				'updated_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%d', '%s', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Reset sync state.
	 *
	 * Resets sync state to idle for a given type.
	 * Useful for restarting sync from beginning.
	 *
	 * @since 0.1.0
	 * @param string $type Sync type ('plugin' or 'theme').
	 * @return bool True on success, false on failure.
	 */
	public static function reset_sync_state( string $type ): bool {
		return self::update_sync_state( $type, self::STATE_IDLE, 1 );
	}
}
