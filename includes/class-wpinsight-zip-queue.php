<?php
/**
 * ZIP Download Queue Worker Class
 *
 * Manages the queue of ZIP file downloads with concurrency control.
 * Processes download jobs from the zip_queue table and stores files
 * in the local filesystem.
 *
 * Download Process:
 * 1. Check active downloads count (max 3 concurrent)
 * 2. Fetch next pending job from queue
 * 3. Download ZIP file from WordPress.org
 * 4. Save to filesystem (wp-content/uploads/wpinsight/...)
 * 5. Update queue status (completed/failed)
 * 6. Record in artifacts table if successful
 * 7. Handle retries on failure
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
 * ZIP download queue worker class.
 *
 * This class uses static methods only and is never instantiated.
 * It manages the background download queue with rate limiting.
 *
 * @since 0.1.0
 */
final class WPInsight_Zip_Queue {

	/**
	 * Initialize the ZIP queue worker.
	 *
	 * Registers Action Scheduler hooks for background processing.
	 * Called from bootstrap on plugins_loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function init(): void {
		// Register ZIP worker tick handler.
		add_action( WPINSIGHT_ZIP_WORKER_TICK_ACTION, array( __CLASS__, 'worker_tick' ) );

		// Register size detection worker tick handler.
		add_action( 'wpinsight_size_detection_tick', array( __CLASS__, 'size_detection_tick' ) );
	}

	/**
	 * Ensure ZIP worker job is scheduled.
	 *
	 * Called during plugin activation to set up recurring worker job.
	 * Uses Action Scheduler to run worker_tick every X seconds.
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
		$next_run = as_next_scheduled_action( WPINSIGHT_ZIP_WORKER_TICK_ACTION, array(), WPINSIGHT_AS_GROUP );
		if ( false !== $next_run ) {
			return; // Already scheduled.
		}

		// Schedule recurring ZIP worker tick.
		$interval = WPInsight_Settings::get( 'zip_worker_interval', 60 ); // Default: 1 minute.
		as_schedule_recurring_action(
			time(),
			$interval,
			WPINSIGHT_ZIP_WORKER_TICK_ACTION,
			array(),
			WPINSIGHT_AS_GROUP
		);
	}

	/**
	 * Ensure size detection job is scheduled.
	 *
	 * Called during plugin activation to set up recurring size detection job.
	 * Runs independently of download worker.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public static function ensure_size_detection_scheduled(): void {
		// Check if Action Scheduler is available.
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		// Check if already scheduled.
		$next_run = as_next_scheduled_action( 'wpinsight_size_detection_tick', array(), WPINSIGHT_AS_GROUP );
		if ( false !== $next_run ) {
			return; // Already scheduled.
		}

		// Schedule recurring size detection tick (every 5 minutes).
		as_schedule_recurring_action(
			time(),
			300, // 5 minutes.
			'wpinsight_size_detection_tick',
			array(),
			WPINSIGHT_AS_GROUP
		);
	}

	/**
	 * Size detection tick handler.
	 *
	 * Called by Action Scheduler every 5 minutes. Detects file sizes
	 * for ZIPs that don't have size information yet.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public static function size_detection_tick(): void {
		// Detect sizes for up to 600 ZIPs per tick.
		self::detect_zip_sizes( 600 );
	}

	/**
	 * Worker tick handler.
	 *
	 * Called by Action Scheduler on schedule. Processes download jobs
	 * while respecting concurrency limits.
	 *
	 * Strategy: Each worker processes only ONE job per tick to avoid race
	 * conditions when multiple workers run simultaneously. The next tick
	 * will check capacity again and process another job if available.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function worker_tick(): void {
		// Check if auto sync is enabled (downloads require sync).
		if ( ! WPInsight_Settings::get( 'auto_sync_enabled', true ) ) {
			return;
		}

		// Get max concurrent downloads setting.
		$max_concurrent = WPInsight_Settings::get( 'max_concurrent_downloads', 3 );

		// Count active downloads.
		$active_count = self::count_active_downloads();

		// Only process if we have capacity.
		// Process ONE job per tick to avoid race conditions with parallel workers.
		if ( $active_count < $max_concurrent ) {
			$job = self::get_next_job();
			if ( $job ) {
				self::process_job( $job );
			}
		}
	}

	/**
	 * Count active downloads.
	 *
	 * Returns the number of downloads currently in 'processing' state.
	 * Uses indexed query with timestamp check to avoid full table scans (v1.1.0+).
	 *
	 * @since 0.1.0
	 * @return int Number of active downloads.
	 */
	private static function count_active_downloads(): int {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Use indexed query (idx_status_started) with timestamp to avoid full table scan.
		// Only count jobs started in last 10 minutes (stale locks are cleaned separately).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE status = 'processing'
				AND started_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
				$table
			)
		);

		return (int) $count;
	}

	/**
	 * Cleanup stale processing locks.
	 *
	 * Resets jobs stuck in 'processing' state for more than 10 minutes.
	 * This handles crashed workers that never completed or failed properly.
	 *
	 * @since 1.1.0
	 * @return int Number of stale locks cleaned up.
	 */
	public static function cleanup_stale_locks(): int {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Find jobs stuck in processing for more than 10 minutes.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cleaned = $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i
				SET status = 'pending', started_at = NULL
				WHERE status = 'processing'
				AND started_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
				$table
			)
		);

		if ( $cleaned > 0 ) {
			WPInsight_Logger::warning(
				sprintf( 'Cleaned up %d stale processing locks', $cleaned ),
				array( 'cleaned' => $cleaned )
			);

			// Invalidate cache.
			delete_transient( 'wpinsight_queue_stats' );
		}

		return is_numeric( $cleaned ) ? (int) $cleaned : 0;
	}

	/**
	 * Get next pending job from queue.
	 *
	 * Fetches the highest priority pending job and marks it as processing.
	 *
	 * @since 0.1.0
	 * @return array<string, mixed>|null Job data or null if no jobs available.
	 */
	private static function get_next_job(): ?array {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Start transaction to prevent race conditions.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction required for atomic queue operations.
		$wpdb->query( 'START TRANSACTION' );

		// Get next pending job (highest priority, oldest first).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Select next pending job from queue. Table name from get_table_name() is safe.
		$job = $wpdb->get_row(
			"SELECT * FROM {$table} WHERE status = 'pending' ORDER BY priority DESC, queued_at ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $job ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction commit required.
			$wpdb->query( 'COMMIT' );
			return null;
		}

		// Mark as processing.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'status'       => 'processing',
				'started_at'   => current_time( 'mysql', true ),
				'last_attempt' => current_time( 'mysql', true ),
			),
			array( 'id' => $job['id'] ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction commit required.
		$wpdb->query( 'COMMIT' );

		return $job;
	}

	/**
	 * Process a download job.
	 *
	 * Downloads the ZIP file using the storage manager and updates queue status.
	 * Delegates all storage operations to WPInsight_Storage class.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $job Job data from queue.
	 * @return bool True on success, false on failure.
	 */
	private static function process_job( array $job ): bool {
		$slug    = $job['slug'];
		$version = $job['version'];
		$type    = $job['item_type']; // Type: plugin or theme.
		$url     = $job['download_url'];
		$job_id  = (int) $job['id'];

		// Delegate download and storage to storage manager.
		$result = WPInsight_Storage::download_and_store_zip( $type, $slug, $version, $url );

		if ( $result['success'] ) {
			// Success! Mark job as completed.
			self::mark_job_completed( $job_id, $result['path'], $result['size'] );
			return true;
		} else {
			// Failed. Mark job as failed with error message.
			self::mark_job_failed( $job_id, $result['error'], (int) $job['attempts'] );
			return false;
		}
	}

	/**
	 * Mark job as completed.
	 *
	 * Updates queue status and creates artifact record.
	 *
	 * @since 0.1.0
	 * @param int    $job_id    Job ID.
	 * @param string $file_path Path to downloaded file.
	 * @param int    $file_size File size in bytes.
	 * @return void
	 */
	private static function mark_job_completed( int $job_id, string $file_path, int $file_size ): void {
		global $wpdb;

		$queue_table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Get job details.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Get job by ID with prepared statement. Table name from get_table_name() is safe.
		$job = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$queue_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$job_id
			),
			ARRAY_A
		);

		if ( ! $job ) {
			return;
		}

		// Update queue status.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$queue_table,
			array(
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $job_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		// Invalidate queue stats cache.
		delete_transient( 'wpinsight_queue_stats' );

		// Create artifact record.
		$artifacts_table = WPInsight_DB::get_table_name( 'artifacts' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$artifacts_table,
			array(
				'item_type'     => $job['item_type'],
				'slug'          => $job['slug'],
				'version'       => $job['version'],
				'file_path'     => $file_path,
				'file_size'     => $file_size,
				'file_hash'     => hash_file( 'sha256', $file_path ),
				'downloaded_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Mark job as failed.
	 *
	 * Updates queue status and increments retry count.
	 * Requeues job if retries remain, otherwise marks as permanently failed.
	 *
	 * @since 0.1.0
	 * @param int    $job_id      Job ID.
	 * @param string $error       Error message.
	 * @param int    $attempts    Current attempt count.
	 * @return void
	 */
	private static function mark_job_failed( int $job_id, string $error, int $attempts = 0 ): void {
		global $wpdb;

		$table       = WPInsight_DB::get_table_name( 'zip_queue' );
		$max_retries = WPInsight_Settings::get( 'max_retries', 3 );

		++$attempts;

		if ( $attempts >= $max_retries ) {
			// Max retries reached, mark as failed permanently.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'status'       => 'failed',
					'attempts'     => $attempts,
					'last_error'   => $error,
					'last_attempt' => current_time( 'mysql', true ),
				),
				array( 'id' => $job_id ),
				array( '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Retry available, requeue as pending.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'status'       => 'pending',
					'attempts'     => $attempts,
					'last_error'   => $error,
					'last_attempt' => current_time( 'mysql', true ),
				),
				array( 'id' => $job_id ),
				array( '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
		}

		// Invalidate queue stats cache.
		delete_transient( 'wpinsight_queue_stats' );
	}


	/**
	 * Get queue statistics.
	 *
	 * Returns counts for each queue status.
	 *
	 * @since 0.1.0
	 * @return array<string, int> {
	 *     Queue statistics.
	 *
	 *     @type int $pending    Pending jobs.
	 *     @type int $processing Currently processing.
	 *     @type int $completed  Completed jobs.
	 *     @type int $failed     Failed jobs.
	 *     @type int $total      Total jobs.
	 * }
	 */
	public static function get_queue_stats(): array {
		// Try to get cached stats first (60 second TTL).
		$cache_key = 'wpinsight_queue_stats';
		$stats     = get_transient( $cache_key );

		if ( false !== $stats && is_array( $stats ) ) {
			return $stats;
		}

		// Cache miss - query database.
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT status, COUNT(*) as count FROM %i GROUP BY status',
				$table
			),
			ARRAY_A
		);

		$stats = array(
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'failed'     => 0,
			'total'      => 0,
		);

		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$status           = $row['status'];
				$count            = (int) $row['count'];
				$stats[ $status ] = $count;
				$stats['total']  += $count;
			}
		}

		// Cache for 60 seconds.
		set_transient( $cache_key, $stats, 60 );

		return $stats;
	}

	/**
	 * Retry failed jobs.
	 *
	 * Resets failed jobs to pending status for retry.
	 * Resets attempt counter.
	 *
	 * @since 0.1.0
	 * @param int $limit Maximum number of jobs to retry (default: 100).
	 * @return int Number of jobs requeued.
	 */
	public static function retry_failed_jobs( int $limit = 100 ): int {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reset failed jobs to pending with prepared statement. Table name from get_table_name() is safe.
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'pending', attempts = 0, last_error = '' WHERE status = 'failed' LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			)
		);

		return (int) $result;
	}

	/**
	 * Clear completed jobs.
	 *
	 * Removes completed job records from queue table.
	 * Useful for keeping queue table size manageable.
	 *
	 * @since 0.1.0
	 * @param int $older_than_days Only clear jobs older than X days (default: 7).
	 * @return int Number of jobs removed.
	 */
	public static function clear_completed_jobs( int $older_than_days = 7 ): int {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		$cutoff_timestamp = strtotime( "-{$older_than_days} days" );
		if ( false === $cutoff_timestamp ) {
			return 0; // Invalid date calculation.
		}
		$cutoff_date = gmdate( 'Y-m-d H:i:s', $cutoff_timestamp );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Delete old completed jobs with prepared statement. Table name from get_table_name() is safe.
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status = 'completed' AND completed_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff_date
			)
		);

		return (int) $result;
	}

	/**
	 * Detect file sizes for ZIPs without remote_filesize.
	 *
	 * Makes HEAD requests to get Content-Length header for ZIPs that don't
	 * have size information yet. Processes in batches to avoid timeouts.
	 *
	 * @since 1.3.0
	 * @param int $limit Maximum number of ZIPs to process. Default 100.
	 * @return array Statistics about detection (checked, updated, failed).
	 */
	public static function detect_zip_sizes( int $limit = 100 ): array {
		global $wpdb;
		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Get ZIPs without remote_filesize (limit to batch size).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, download_url
				FROM %i
				WHERE remote_filesize IS NULL
				LIMIT %d',
				$table,
				$limit
			),
			ARRAY_A
		);

		if ( empty( $jobs ) ) {
			return array(
				'checked' => 0,
				'updated' => 0,
				'failed'  => 0,
			);
		}

		$stats = array(
			'checked' => count( $jobs ),
			'updated' => 0,
			'failed'  => 0,
		);

		// Get rate limit from settings (requests per second).
		$rate_limit = WPInsight_Settings::get( 'max_size_detection_rate', 3 );

		// Calculate delay in microseconds: 1 second / rate = delay per request.
		// Example: 3 req/sec = 1/3 = 0.333 seconds = 333333 microseconds.
		$delay_microseconds = (int) ( 1000000 / $rate_limit );

		foreach ( $jobs as $job ) {
			$filesize = self::get_remote_filesize( $job['download_url'] );

			if ( false !== $filesize ) {
				// Update remote_filesize in database.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$updated = $wpdb->update(
					$table,
					array( 'remote_filesize' => $filesize ),
					array( 'id' => $job['id'] ),
					array( '%d' ),
					array( '%d' )
				);

				if ( false !== $updated ) {
					++$stats['updated'];
				} else {
					++$stats['failed'];
				}
			} else {
				++$stats['failed'];
			}

			// Rate limiting delay to be polite to WordPress.org servers.
			usleep( $delay_microseconds );
		}

		WPInsight_Logger::info(
			'ZIP size detection completed',
			array(
				'checked' => $stats['checked'],
				'updated' => $stats['updated'],
				'failed'  => $stats['failed'],
			)
		);

		return $stats;
	}

	/**
	 * Get remote file size via HEAD request.
	 *
	 * Makes a HEAD request to get Content-Length header without downloading the file.
	 *
	 * @since 1.3.0
	 * @param string $url URL to check.
	 * @return int|false File size in bytes, or false on failure.
	 */
	private static function get_remote_filesize( string $url ) {
		// Make HEAD request.
		$response = wp_remote_head(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'WPInsight/' . WPINSIGHT_VERSION . '; ' . home_url(),
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			WPInsight_Logger::warning(
				'Failed to get remote filesize via HEAD request',
				array(
					'url'   => $url,
					'error' => $response->get_error_message(),
				)
			);
			return false;
		}

		// Get Content-Length header.
		$content_length = wp_remote_retrieve_header( $response, 'content-length' );

		if ( empty( $content_length ) ) {
			return false;
		}

		// Convert to integer.
		$filesize = (int) $content_length;

		// Sanity check (ZIP should be at least 1KB).
		if ( $filesize < 1024 ) {
			return false;
		}

		return $filesize;
	}

	/**
	 * Get total size statistics for plugins and themes.
	 *
	 * Returns total sizes for downloaded ZIPs and pending/queued ZIPs.
	 *
	 * @since 1.3.0
	 * @return array Statistics with plugin and theme sizes.
	 */
	public static function get_size_statistics(): array {
		global $wpdb;
		$queue_table     = WPInsight_DB::get_table_name( 'zip_queue' );
		$artifacts_table = WPInsight_DB::get_table_name( 'artifacts' );

		// Get plugin sizes.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$plugin_downloaded = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(file_size), 0)
				FROM %i
				WHERE item_type = %s',
				$artifacts_table,
				'plugin'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$plugin_pending = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(remote_filesize), 0)
				FROM %i
				WHERE item_type = %s
				AND status IN (%s, %s, %s)
				AND remote_filesize IS NOT NULL',
				$queue_table,
				'plugin',
				'pending',
				'queued',
				'failed'
			)
		);

		// Get theme sizes.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$theme_downloaded = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(file_size), 0)
				FROM %i
				WHERE item_type = %s',
				$artifacts_table,
				'theme'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$theme_pending = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(remote_filesize), 0)
				FROM %i
				WHERE item_type = %s
				AND status IN (%s, %s, %s)
				AND remote_filesize IS NOT NULL',
				$queue_table,
				'theme',
				'pending',
				'queued',
				'failed'
			)
		);

		// Get counts with remote_filesize (for percentage calculation).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$plugin_count_with_size = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*)
				FROM %i
				WHERE item_type = %s
				AND remote_filesize IS NOT NULL',
				$queue_table,
				'plugin'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$theme_count_with_size = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*)
				FROM %i
				WHERE item_type = %s
				AND remote_filesize IS NOT NULL',
				$queue_table,
				'theme'
			)
		);

		// Get total counts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$plugin_total_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*)
				FROM %i
				WHERE item_type = %s',
				$queue_table,
				'plugin'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$theme_total_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*)
				FROM %i
				WHERE item_type = %s',
				$queue_table,
				'theme'
			)
		);

		return array(
			'plugins' => array(
				'downloaded_size'    => (int) $plugin_downloaded,
				'pending_size'       => (int) $plugin_pending,
				'total_size'         => (int) $plugin_downloaded + (int) $plugin_pending,
				'count_with_size'    => (int) $plugin_count_with_size,
				'total_count'        => (int) $plugin_total_count,
				'detection_progress' => $plugin_total_count > 0 ? round( ( $plugin_count_with_size / $plugin_total_count ) * 100, 1 ) : 0,
			),
			'themes'  => array(
				'downloaded_size'    => (int) $theme_downloaded,
				'pending_size'       => (int) $theme_pending,
				'total_size'         => (int) $theme_downloaded + (int) $theme_pending,
				'count_with_size'    => (int) $theme_count_with_size,
				'total_count'        => (int) $theme_total_count,
				'detection_progress' => $theme_total_count > 0 ? round( ( $theme_count_with_size / $theme_total_count ) * 100, 1 ) : 0,
			),
		);
	}
}
