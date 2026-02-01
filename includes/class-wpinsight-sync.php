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
		add_action( WPINSIGHT_SYNC_TICK_ACTION, array( __CLASS__, 'sync_tick' ) );

		// Register full sync handlers.
		add_action( 'wpinsight_full_sync_plugins', array( __CLASS__, 'full_sync_plugins_handler' ) );
		add_action( 'wpinsight_full_sync_themes', array( __CLASS__, 'full_sync_themes_handler' ) );
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
		$next_run = as_next_scheduled_action( WPINSIGHT_SYNC_TICK_ACTION, array(), WPINSIGHT_AS_GROUP );
		if ( false !== $next_run ) {
			return; // Already scheduled.
		}

		// Schedule recurring sync tick.
		$interval = WPInsight_Settings::get( 'sync_interval', 300 ); // Default: 5 minutes.
		as_schedule_recurring_action(
			time(),
			$interval,
			WPINSIGHT_SYNC_TICK_ACTION,
			array(),
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
		if ( in_array( $state['status'], array( self::STATE_COMPLETED, self::STATE_ERROR ), true ) ) {
			return false;
		}

		// Mark as running.
		self::update_sync_state( 'plugin', self::STATE_RUNNING, $state['page'] );

		// Fetch plugins from API.
		$per_page = WPInsight_Settings::get( 'per_page', 250 );
		$response = WPInsight_WPOrg_Client::query_plugins(
			array(
				'browse'   => 'updated',
				'page'     => $state['page'],
				'per_page' => $per_page,
			)
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
		if ( in_array( $state['status'], array( self::STATE_COMPLETED, self::STATE_ERROR ), true ) ) {
			return false;
		}

		// Mark as running.
		self::update_sync_state( 'theme', self::STATE_RUNNING, $state['page'] );

		// Fetch themes from API.
		$per_page = WPInsight_Settings::get( 'per_page', 250 );
		$response = WPInsight_WPOrg_Client::query_themes(
			array(
				'browse'   => 'updated',
				'page'     => $state['page'],
				'per_page' => $per_page,
			)
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
	 * Batch check which versions already exist in queue.
	 *
	 * Performs a single SELECT query with IN clause instead of N queries.
	 *
	 * @since 1.1.0
	 * @param string   $slug     Item slug.
	 * @param string   $type     Item type ('plugin' or 'theme').
	 * @param string[] $versions Array of version strings.
	 * @return array<string, bool> Associative array with version => true for existing versions.
	 */
	private static function batch_check_existing_versions( string $slug, string $type, array $versions ): array {
		global $wpdb;

		if ( empty( $versions ) ) {
			return array();
		}

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Build placeholders for IN clause.
		$placeholders = implode( ', ', array_fill( 0, count( $versions ), '%s' ) );

		// Dynamic IN clause with placeholders - PHPCS can't count merged array parameters.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT artifact_version FROM %i WHERE artifact_type = %s AND artifact_slug = %s AND artifact_version IN ($placeholders)",
				array_merge( array( $table, $type, $slug ), $versions )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		// Convert to associative array for fast lookup.
		return is_array( $existing ) ? array_fill_keys( $existing, true ) : array();
	}

	/**
	 * Batch insert multiple queue jobs.
	 *
	 * Performs a single bulk INSERT instead of N individual inserts.
	 *
	 * @since 1.1.0
	 * @param string                $slug     Item slug.
	 * @param string                $type     Item type ('plugin' or 'theme').
	 * @param array<string, string> $versions Versions array (version => download_url).
	 * @param int                   $post_id  Post ID.
	 * @return int Number of rows inserted.
	 */
	private static function batch_insert_queue_jobs( string $slug, string $type, array $versions, int $post_id ): int {
		global $wpdb;

		if ( empty( $versions ) ) {
			return 0;
		}

		$table = WPInsight_DB::get_table_name( 'zip_queue' );
		$now   = current_time( 'mysql', true );

		// Build multi-row INSERT statement.
		$values       = array( $table ); // Table name as first parameter for %i.
		$placeholders = array();

		foreach ( $versions as $version => $download_url ) {
			$placeholders[] = '(%s, %s, %s, %d, %s, %s, %d, %d, %s)';
			$values[]       = $type;
			$values[]       = $slug;
			$values[]       = $version;
			$values[]       = $post_id;
			$values[]       = $download_url;
			$values[]       = 'pending';
			$values[]       = 50; // Normal priority.
			$values[]       = 0;  // Attempts.
			$values[]       = $now;
		}

		$query = 'INSERT INTO %i (artifact_type, artifact_slug, artifact_version, artifact_post_id, download_url, status, priority, attempts, queued_at)
				  VALUES ' . implode( ', ', $placeholders );

		// Bulk insert with prepared statement - PHPCS can't trace $query variable.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $query, $values ) );

		return is_numeric( $result ) ? (int) $result : 0;
	}

	/**
	 * Enqueue plugin ZIP downloads.
	 *
	 * Adds download jobs to the zip_queue table for each version.
	 * Uses batch operations to minimize database queries (v1.1.0+).
	 *
	 * @since 0.1.0
	 * @param string                $slug     Plugin slug.
	 * @param array<string, string> $versions Versions array (version => download_url).
	 * @param int                   $post_id  Plugin post ID.
	 * @return void
	 */
	private static function enqueue_plugin_downloads( string $slug, array $versions, int $post_id ): void {
		if ( empty( $versions ) ) {
			return;
		}

		// Check memory before processing large version arrays.
		$memory_needed_mb = ( count( $versions ) * 1024 ) / ( 1024 * 1024 ); // Rough estimate.
		if ( $memory_needed_mb > 32 ) {
			$memory_available = (int) ini_get( 'memory_limit' );
			if ( $memory_available > 0 && $memory_available < 128 ) {
				WPInsight_Logger::warning(
					sprintf( 'Large version array (%d versions) may exceed memory limit (%dM)', count( $versions ), $memory_available ),
					array(
						'slug'           => $slug,
						'versions_count' => count( $versions ),
					)
				);
			}
		}

		// Batch check which versions already exist (1 query instead of N).
		$existing = self::batch_check_existing_versions( $slug, 'plugin', array_keys( $versions ) );

		// Filter out already-queued versions.
		$new_versions = array_diff_key( $versions, $existing );

		if ( empty( $new_versions ) ) {
			return; // All versions already queued.
		}

		// Batch insert all new versions (1 query instead of N).
		$inserted = self::batch_insert_queue_jobs( $slug, 'plugin', $new_versions, $post_id );

		if ( $inserted > 0 ) {
			WPInsight_Logger::info(
				sprintf( 'Enqueued %d plugin versions for download', $inserted ),
				array(
					'slug'     => $slug,
					'inserted' => $inserted,
				)
			);
		}
	}

	/**
	 * Enqueue theme ZIP downloads.
	 *
	 * Adds download jobs to the zip_queue table for each version.
	 * Uses batch operations to minimize database queries (v1.1.0+).
	 *
	 * @since 0.1.0
	 * @param string                $slug     Theme slug.
	 * @param array<string, string> $versions Versions array (version => download_url).
	 * @param int                   $post_id  Theme post ID.
	 * @return void
	 */
	private static function enqueue_theme_downloads( string $slug, array $versions, int $post_id ): void {
		if ( empty( $versions ) ) {
			return;
		}

		// Check memory before processing large version arrays.
		$memory_needed_mb = ( count( $versions ) * 1024 ) / ( 1024 * 1024 ); // Rough estimate.
		if ( $memory_needed_mb > 32 ) {
			$memory_available = (int) ini_get( 'memory_limit' );
			if ( $memory_available > 0 && $memory_available < 128 ) {
				WPInsight_Logger::warning(
					sprintf( 'Large version array (%d versions) may exceed memory limit (%dM)', count( $versions ), $memory_available ),
					array(
						'slug'           => $slug,
						'versions_count' => count( $versions ),
					)
				);
			}
		}

		// Batch check which versions already exist (1 query instead of N).
		$existing = self::batch_check_existing_versions( $slug, 'theme', array_keys( $versions ) );

		// Filter out already-queued versions.
		$new_versions = array_diff_key( $versions, $existing );

		if ( empty( $new_versions ) ) {
			return; // All versions already queued.
		}

		// Batch insert all new versions (1 query instead of N).
		$inserted = self::batch_insert_queue_jobs( $slug, 'theme', $new_versions, $post_id );

		if ( $inserted > 0 ) {
			WPInsight_Logger::info(
				sprintf( 'Enqueued %d theme versions for download', $inserted ),
				array(
					'slug'     => $slug,
					'inserted' => $inserted,
				)
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
	 * @return array<string, mixed> {
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Get latest sync state. Table name from get_table_name() is safe.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE sync_type = %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$type
			),
			ARRAY_A
		);

		if ( ! $row ) {
			// No state yet, return defaults.
			return array(
				'status'     => self::STATE_IDLE,
				'page'       => 1,
				'last_error' => '',
				'updated_at' => current_time( 'mysql', true ),
			);
		}

		return array(
			'status'     => $row['status'],
			'page'       => (int) $row['page'],
			'last_error' => $row['last_error'] ?? '',
			'updated_at' => $row['updated_at'],
		);
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
			array(
				'sync_type'  => $type,
				'status'     => $status,
				'page'       => $page,
				'last_error' => $last_error,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%d', '%s', '%s' )
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

	/**
	 * Full sync for plugins or themes.
	 *
	 * Processes ALL pages of plugins/themes in a single execution.
	 * This is different from sync_plugins()/sync_themes() which only
	 * process one page per tick.
	 *
	 * Full sync process:
	 * 1. Get current sync state (resume from cursor if interrupted)
	 * 2. Loop through all remaining pages
	 * 3. For each plugin/theme, fetch full details via plugin_information API
	 * 4. Create/update CPT entries
	 * 5. Enqueue ALL historical versions for download
	 * 6. Update cursor after each page (for resume capability)
	 * 7. Check for timeout/memory limits and exit gracefully if needed
	 *
	 * This method can take a LONG time (hours/days) depending on:
	 * - Total number of plugins/themes (~60,000+ plugins)
	 * - API response time
	 * - Server resources
	 *
	 * Use WP-CLI or background Action Scheduler job for execution.
	 *
	 * @since 0.1.0
	 * @param string $entity_type Type of entity ('plugin' or 'theme').
	 * @param int    $max_pages   Maximum pages to process (0 = unlimited). Default: 0.
	 * @param int    $time_limit  Time limit in seconds (0 = no limit). Default: 0.
	 * @return array<string, mixed> {
	 *     Sync result data.
	 *
	 *     @type bool   $completed      Whether sync completed fully.
	 *     @type int    $pages_processed Number of pages processed.
	 *     @type int    $items_processed Number of items processed.
	 *     @type int    $items_enqueued  Number of download jobs enqueued.
	 *     @type string $status         Final status.
	 *     @type string $message        Status message.
	 * }
	 */
	public static function sync_full( string $entity_type, int $max_pages = 0, int $time_limit = 0 ): array {
		// Validate entity type.
		if ( ! in_array( $entity_type, array( 'plugin', 'theme' ), true ) ) {
			return array(
				'completed'       => false,
				'pages_processed' => 0,
				'items_processed' => 0,
				'items_enqueued'  => 0,
				'status'          => self::STATE_ERROR,
				'message'         => 'Invalid entity type. Must be "plugin" or "theme".',
			);
		}

		$start_time      = time();
		$pages_processed = 0;
		$items_processed = 0;
		$items_enqueued  = 0;

		// Get current sync state (resume if interrupted).
		$state = self::get_sync_state( $entity_type );

		// If already completed, reset to start.
		if ( self::STATE_COMPLETED === $state['status'] ) {
			self::reset_sync_state( $entity_type );
			$state = self::get_sync_state( $entity_type );
		}

		// Mark as running.
		self::update_sync_state( $entity_type, self::STATE_RUNNING, $state['page'] );

		// Get settings.
		$per_page = WPInsight_Settings::get( 'per_page', 250 );

		// Log start.
		WPInsight_Logger::info(
			sprintf( 'Starting full %s sync from page %d', $entity_type, $state['page'] ),
			array(
				'entity_type' => $entity_type,
				'start_page'  => $state['page'],
				'per_page'    => $per_page,
			)
		);

		// Main sync loop - process all pages.
		$current_page = $state['page'];
		$has_more     = true;

		while ( $has_more ) {
			// Check time limit.
			if ( $time_limit > 0 && ( time() - $start_time ) >= $time_limit ) {
				WPInsight_Logger::warning(
					sprintf( 'Full sync time limit reached (%d seconds). Pausing at page %d.', $time_limit, $current_page ),
					array(
						'entity_type'     => $entity_type,
						'pages_processed' => $pages_processed,
						'items_processed' => $items_processed,
					)
				);
				break;
			}

			// Check max pages limit.
			if ( $max_pages > 0 && $pages_processed >= $max_pages ) {
				WPInsight_Logger::info(
					sprintf( 'Full sync page limit reached (%d pages). Pausing at page %d.', $max_pages, $current_page ),
					array(
						'entity_type'     => $entity_type,
						'pages_processed' => $pages_processed,
					)
				);
				break;
			}

			// Fetch data from API.
			$response = ( 'plugin' === $entity_type )
				? WPInsight_WPOrg_Client::query_plugins(
					array(
						'browse'   => 'updated',
						'page'     => $current_page,
						'per_page' => $per_page,
					)
				)
				: WPInsight_WPOrg_Client::query_themes(
					array(
						'browse'   => 'updated',
						'page'     => $current_page,
						'per_page' => $per_page,
					)
				);

			// Handle API error.
			if ( false === $response ) {
				$error_msg = sprintf( 'API request failed at page %d', $current_page );
				self::update_sync_state( $entity_type, self::STATE_ERROR, $current_page, $error_msg );
				WPInsight_Logger::error( $error_msg, array( 'entity_type' => $entity_type ) );

				return array(
					'completed'       => false,
					'pages_processed' => $pages_processed,
					'items_processed' => $items_processed,
					'items_enqueued'  => $items_enqueued,
					'status'          => self::STATE_ERROR,
					'message'         => $error_msg,
				);
			}

			// Get items from response.
			$items_key = ( 'plugin' === $entity_type ) ? 'plugins' : 'themes';
			$items     = isset( $response[ $items_key ] ) && is_array( $response[ $items_key ] )
				? $response[ $items_key ]
				: array();

			// Process each item.
			$page_count = 0;
			foreach ( $items as $item ) {
				// Process the item based on type.
				$processed = ( 'plugin' === $entity_type )
					? self::process_plugin( $item )
					: self::process_theme( $item );

				if ( $processed ) {
					++$items_processed;
					++$page_count;

					// Count enqueued downloads from this item.
					// Note: process_plugin/theme already enqueues versions.
					// This is just for statistics tracking.
					if ( isset( $item['slug'] ) ) {
						$versions_count = 0;
						if ( 'plugin' === $entity_type ) {
							$full_info = WPInsight_WPOrg_Client::get_plugin_info( $item['slug'] );
							if ( false !== $full_info && isset( $full_info['versions'] ) && is_array( $full_info['versions'] ) ) {
								$versions_count = count( $full_info['versions'] );
							}
						} else {
							$full_info = WPInsight_WPOrg_Client::get_theme_info( $item['slug'] );
							if ( false !== $full_info && isset( $full_info['versions'] ) && is_array( $full_info['versions'] ) ) {
								$versions_count = count( $full_info['versions'] );
							}
						}
						$items_enqueued += $versions_count;
					}
				}
			}

			// Update cursor after page completion.
			self::update_sync_state( $entity_type, self::STATE_RUNNING, $current_page );

			// Log progress.
			if ( 0 === $current_page % 10 ) { // Log every 10 pages.
				WPInsight_Logger::info(
					sprintf( 'Full sync progress: page %d, %d items processed', $current_page, $items_processed ),
					array(
						'entity_type'     => $entity_type,
						'current_page'    => $current_page,
						'page_count'      => $page_count,
						'items_processed' => $items_processed,
					)
				);
			}

			// Check if we have more pages.
			$total_pages = isset( $response['info']->pages ) ? (int) $response['info']->pages : 0;
			$has_more    = $current_page < $total_pages && $page_count > 0;

			// Move to next page.
			if ( $has_more ) {
				++$current_page;
				++$pages_processed;
			}
		}

		// Determine final status.
		$completed = ! $has_more;
		$status    = $completed ? self::STATE_COMPLETED : self::STATE_RUNNING;

		// Update final sync state.
		self::update_sync_state( $entity_type, $status, $current_page );

		// Log completion.
		$message = $completed
			? sprintf( 'Full %s sync completed. Processed %d items across %d pages.', $entity_type, $items_processed, $pages_processed )
			: sprintf( 'Full %s sync paused at page %d. Processed %d items.', $entity_type, $current_page, $items_processed );

		WPInsight_Logger::info(
			$message,
			array(
				'entity_type'     => $entity_type,
				'completed'       => $completed,
				'pages_processed' => $pages_processed,
				'items_processed' => $items_processed,
				'items_enqueued'  => $items_enqueued,
			)
		);

		return array(
			'completed'       => $completed,
			'pages_processed' => $pages_processed,
			'items_processed' => $items_processed,
			'items_enqueued'  => $items_enqueued,
			'status'          => $status,
			'message'         => $message,
		);
	}

	/**
	 * Full sync plugins handler for Action Scheduler.
	 *
	 * This is called by Action Scheduler when a full plugin sync is scheduled.
	 * Processes up to 50 pages per execution (about 5000 plugins).
	 * If incomplete, schedules another run automatically.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function full_sync_plugins_handler(): void {
		// Process up to 50 pages per run (prevents timeout).
		$result = self::sync_full( 'plugin', 50, 300 ); // Max 50 pages, 5 minute timeout.

		// If not completed, schedule another run.
		if ( ! $result['completed'] && function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 60, 'wpinsight_full_sync_plugins', array(), WPINSIGHT_AS_GROUP );
			WPInsight_Logger::info(
				'Full plugin sync continuing. Scheduled next batch.',
				array(
					'pages_processed' => $result['pages_processed'],
					'items_processed' => $result['items_processed'],
				)
			);
		}
	}

	/**
	 * Full sync themes handler for Action Scheduler.
	 *
	 * This is called by Action Scheduler when a full theme sync is scheduled.
	 * Processes up to 50 pages per execution (about 5000 themes).
	 * If incomplete, schedules another run automatically.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function full_sync_themes_handler(): void {
		// Process up to 50 pages per run (prevents timeout).
		$result = self::sync_full( 'theme', 50, 300 ); // Max 50 pages, 5 minute timeout.

		// If not completed, schedule another run.
		if ( ! $result['completed'] && function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 60, 'wpinsight_full_sync_themes', array(), WPINSIGHT_AS_GROUP );
			WPInsight_Logger::info(
				'Full theme sync continuing. Scheduled next batch.',
				array(
					'pages_processed' => $result['pages_processed'],
					'items_processed' => $result['items_processed'],
				)
			);
		}
	}
}
