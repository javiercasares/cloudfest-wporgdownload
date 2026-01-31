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
	 * Maximum file size for downloads (bytes).
	 * Default: 500 MB per file.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const MAX_FILE_SIZE = 524288000; // 500 MB.

	/**
	 * Download timeout in seconds.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const DOWNLOAD_TIMEOUT = 300; // 5 minutes.

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
		add_action( WPINSIGHT_ZIP_WORKER_TICK_ACTION, [ __CLASS__, 'worker_tick' ] );
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
		$next_run = as_next_scheduled_action( WPINSIGHT_ZIP_WORKER_TICK_ACTION, [], WPINSIGHT_AS_GROUP );
		if ( false !== $next_run ) {
			return; // Already scheduled.
		}

		// Schedule recurring ZIP worker tick.
		$interval = WPInsight_Settings::get( 'zip_worker_interval', 60 ); // Default: 1 minute.
		as_schedule_recurring_action(
			time(),
			$interval,
			WPINSIGHT_ZIP_WORKER_TICK_ACTION,
			[],
			WPINSIGHT_AS_GROUP
		);
	}

	/**
	 * Worker tick handler.
	 *
	 * Called by Action Scheduler on schedule. Processes download jobs
	 * while respecting concurrency limits.
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

		// Process jobs while we have capacity.
		while ( $active_count < $max_concurrent ) {
			// Get next job.
			$job = self::get_next_job();
			if ( ! $job ) {
				break; // No more jobs.
			}

			// Process the job.
			self::process_job( $job );

			// Increment active count.
			++$active_count;
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table}
			WHERE status = 'processing'
			AND started_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
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
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cleaned = $wpdb->query(
			"UPDATE {$table}
			SET status = 'pending', started_at = NULL
			WHERE status = 'processing'
			AND started_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
		);

		if ( $cleaned > 0 ) {
			WPInsight_Logger::warning(
				sprintf( 'Cleaned up %d stale processing locks', $cleaned ),
				[ 'cleaned' => $cleaned ]
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
			[
				'status'       => 'processing',
				'started_at'   => current_time( 'mysql', true ),
				'last_attempt' => current_time( 'mysql', true ),
			],
			[ 'id' => $job['id'] ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Transaction commit required.
		$wpdb->query( 'COMMIT' );

		return $job;
	}

	/**
	 * Process a download job.
	 *
	 * Downloads the ZIP file and saves it to the filesystem.
	 * Updates queue status and creates artifact record on success.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $job Job data from queue.
	 * @return bool True on success, false on failure.
	 */
	private static function process_job( array $job ): bool {
		$slug    = $job['artifact_slug'];
		$version = $job['artifact_version'];
		$type    = $job['artifact_type']; // Type: plugin or theme.
		$url     = $job['download_url'];
		$job_id  = (int) $job['id'];

		// Build destination path.
		$upload_dir = wp_upload_dir();
		$base_path  = WPInsight_Settings::get( 'storage_base_path', 'wpinsight' );
		$dest_dir   = trailingslashit( $upload_dir['basedir'] ) . trailingslashit( $base_path ) . trailingslashit( $type ) . trailingslashit( $slug );
		$dest_file  = $dest_dir . "{$slug}.{$version}.zip";

		// Create directory if needed.
		if ( ! file_exists( $dest_dir ) ) {
			if ( ! wp_mkdir_p( $dest_dir ) ) {
				self::mark_job_failed( $job_id, 'Failed to create destination directory' );
				return false;
			}
		}

		// Check if file already exists.
		if ( file_exists( $dest_file ) ) {
			// File already downloaded, mark as completed.
			self::mark_job_completed( $job_id, $dest_file, filesize( $dest_file ) );
			return true;
		}

		// Download the file.
		$response = wp_remote_get(
			$url,
			[
				'timeout'  => self::DOWNLOAD_TIMEOUT,
				'stream'   => true,
				'filename' => $dest_file,
			]
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			self::mark_job_failed( $job_id, $response->get_error_message(), (int) $job['attempts'] );
			return false;
		}

		// Check HTTP status.
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			self::mark_job_failed( $job_id, "HTTP {$status_code}", (int) $job['attempts'] );
			return false;
		}

		// Verify file was created.
		if ( ! file_exists( $dest_file ) ) {
			self::mark_job_failed( $job_id, 'File not created after download', (int) $job['attempts'] );
			return false;
		}

		// Verify file size.
		$file_size = filesize( $dest_file );
		if ( 0 === $file_size ) {
			wp_delete_file( $dest_file ); // Remove empty file.
			self::mark_job_failed( $job_id, 'Downloaded file is empty', (int) $job['attempts'] );
			return false;
		}

		// Check max file size.
		if ( $file_size > self::MAX_FILE_SIZE ) {
			wp_delete_file( $dest_file ); // Remove oversized file.
			self::mark_job_failed( $job_id, 'File exceeds maximum size limit', (int) $job['attempts'] );
			return false;
		}

		// Verify it's a valid ZIP file.
		if ( ! self::is_valid_zip( $dest_file ) ) {
			wp_delete_file( $dest_file ); // Remove invalid file.
			self::mark_job_failed( $job_id, 'Downloaded file is not a valid ZIP', (int) $job['attempts'] );
			return false;
		}

		// Success! Mark as completed.
		self::mark_job_completed( $job_id, $dest_file, $file_size );

		return true;
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
			[
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $job_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);

		// Invalidate queue stats cache.
		delete_transient( 'wpinsight_queue_stats' );

		// Create artifact record.
		$artifacts_table = WPInsight_DB::get_table_name( 'artifacts' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$artifacts_table,
			[
				'artifact_type'    => $job['artifact_type'],
				'artifact_slug'    => $job['artifact_slug'],
				'artifact_version' => $job['artifact_version'],
				'artifact_post_id' => $job['artifact_post_id'],
				'file_path'        => $file_path,
				'file_size'        => $file_size,
				'file_hash'        => hash_file( 'sha256', $file_path ),
				'downloaded_at'    => current_time( 'mysql', true ),
			],
			[ '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' ]
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
				[
					'status'       => 'failed',
					'attempts'     => $attempts,
					'last_error'   => $error,
					'last_attempt' => current_time( 'mysql', true ),
				],
				[ 'id' => $job_id ],
				[ '%s', '%d', '%s', '%s' ],
				[ '%d' ]
			);
		} else {
			// Retry available, requeue as pending.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				[
					'status'       => 'pending',
					'attempts'     => $attempts,
					'last_error'   => $error,
					'last_attempt' => current_time( 'mysql', true ),
				],
				[ 'id' => $job_id ],
				[ '%s', '%d', '%s', '%s' ],
				[ '%d' ]
			);
		}

		// Invalidate queue stats cache.
		delete_transient( 'wpinsight_queue_stats' );
	}

	/**
	 * Check if file is a valid ZIP.
	 *
	 * Uses ZipArchive to verify file integrity.
	 *
	 * @since 0.1.0
	 * @param string $file_path Path to ZIP file.
	 * @return bool True if valid ZIP, false otherwise.
	 */
	private static function is_valid_zip( string $file_path ): bool {
		if ( ! class_exists( 'ZipArchive' ) ) {
			// ZipArchive not available, assume valid.
			return true;
		}

		$zip = new ZipArchive();
		$res = $zip->open( $file_path, ZipArchive::CHECKCONS );

		if ( true === $res ) {
			$zip->close();
			return true;
		}

		return false;
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
			ARRAY_A
		);

		$stats = [
			'pending'    => 0,
			'processing' => 0,
			'completed'  => 0,
			'failed'     => 0,
			'total'      => 0,
		];

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

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$older_than_days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Delete old completed jobs with prepared statement. Table name from get_table_name() is safe.
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status = 'completed' AND completed_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff_date
			)
		);

		return (int) $result;
	}
}
