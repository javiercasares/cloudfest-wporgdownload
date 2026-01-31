<?php
/**
 * WP-CLI Commands for WPInsight.
 *
 * Provides command-line interface for manual operations:
 * - Sync plugins/themes from WordPress.org
 * - Process ZIP download queue
 * - Display statistics and status
 * - Manage download queue (retry, clear)
 *
 * @package    CloudFest_WPOrg_Download
 * @subpackage CLI
 * @since      0.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI commands for WPInsight.
 *
 * @since 0.1.0
 */
final class WPInsight_CLI {

	/**
	 * Register WP-CLI commands.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'wpinsight sync', [ __CLASS__, 'sync' ] );
		WP_CLI::add_command( 'wpinsight zip', [ __CLASS__, 'zip' ] );
		WP_CLI::add_command( 'wpinsight stats', [ __CLASS__, 'stats' ] );
		WP_CLI::add_command( 'wpinsight queue', [ __CLASS__, 'queue' ] );
		WP_CLI::add_command( 'wpinsight reset', [ __CLASS__, 'reset' ] );
	}

	/**
	 * Sync plugins and themes from WordPress.org.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Type to sync: plugins, themes, or both.
	 * ---
	 * default: both
	 * options:
	 *   - plugins
	 *   - themes
	 *   - both
	 * ---
	 *
	 * [--reset]
	 * : Reset sync state before starting (start from page 1).
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpinsight sync
	 *     wp wpinsight sync --type=plugins
	 *     wp wpinsight sync --type=themes --reset
	 *
	 * @since 0.1.0
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function sync( array $args, array $assoc_args ): void {
		$type  = $assoc_args['type'] ?? 'both';
		$reset = isset( $assoc_args['reset'] );

		// Validate type.
		if ( ! in_array( $type, [ 'plugins', 'themes', 'both' ], true ) ) {
			WP_CLI::error( 'Invalid type. Must be: plugins, themes, or both.' );
		}

		// Check if sync is enabled in settings.
		$sync_plugins_enabled = WPInsight_Settings::get( 'sync_plugins_enabled', true );
		$sync_themes_enabled  = WPInsight_Settings::get( 'sync_themes_enabled', true );

		if ( 'plugins' === $type && ! $sync_plugins_enabled ) {
			WP_CLI::warning( 'Plugin sync is disabled in settings.' );
		}

		if ( 'themes' === $type && ! $sync_themes_enabled ) {
			WP_CLI::warning( 'Theme sync is disabled in settings.' );
		}

		// Reset sync state if requested.
		if ( $reset ) {
			if ( 'both' === $type || 'plugins' === $type ) {
				WPInsight_Sync::reset_sync_state( 'plugin' );
				WP_CLI::success( 'Plugin sync state reset.' );
			}
			if ( 'both' === $type || 'themes' === $type ) {
				WPInsight_Sync::reset_sync_state( 'theme' );
				WP_CLI::success( 'Theme sync state reset.' );
			}
		}

		// Run sync.
		WP_CLI::log( 'Starting sync...' );

		$plugins_success = true;
		$themes_success  = true;

		if ( 'both' === $type || 'plugins' === $type ) {
			if ( $sync_plugins_enabled ) {
				WP_CLI::log( 'Syncing plugins...' );
				$plugins_success = WPInsight_Sync::sync_plugins();
			}
		}

		if ( 'both' === $type || 'themes' === $type ) {
			if ( $sync_themes_enabled ) {
				WP_CLI::log( 'Syncing themes...' );
				$themes_success = WPInsight_Sync::sync_themes();
			}
		}

		// Report results.
		if ( $plugins_success && $themes_success ) {
			WP_CLI::success( 'Sync completed successfully.' );
		} else {
			WP_CLI::warning( 'Sync completed with errors. Check logs for details.' );
		}

		// Show stats.
		self::show_sync_stats( $type );
	}

	/**
	 * Process ZIP download queue.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<limit>]
	 * : Maximum number of jobs to process.
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpinsight zip
	 *     wp wpinsight zip --limit=10
	 *
	 * @since 0.1.0
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function zip( array $args, array $assoc_args ): void {
		$limit = isset( $assoc_args['limit'] ) ? absint( $assoc_args['limit'] ) : 0;

		WP_CLI::log( 'Processing ZIP download queue...' );

		$processed = 0;

		// Process jobs until limit reached or queue empty.
		while ( true ) {
			if ( $limit > 0 && $processed >= $limit ) {
				WP_CLI::log( sprintf( 'Limit of %d jobs reached.', $limit ) );
				break;
			}

			// Get queue stats before processing.
			$stats_before = WPInsight_Zip_Queue::get_queue_stats();

			if ( 0 === $stats_before['pending'] ) {
				WP_CLI::log( 'Queue is empty.' );
				break;
			}

			// Process one tick.
			WPInsight_Zip_Queue::worker_tick();

			// Get queue stats after processing.
			$stats_after = WPInsight_Zip_Queue::get_queue_stats();

			// Check if any jobs were processed.
			if ( $stats_before['pending'] === $stats_after['pending'] ) {
				WP_CLI::log( 'No jobs available to process (max concurrency reached or all jobs failed).' );
				break;
			}

			++$processed;

			// Show progress.
			WP_CLI::log(
				sprintf(
					'Processed: %d | Pending: %d | Processing: %d | Completed: %d | Failed: %d',
					$processed,
					$stats_after['pending'],
					$stats_after['processing'],
					$stats_after['completed'],
					$stats_after['failed']
				)
			);

			// Small delay to avoid overwhelming the system.
			sleep( 1 );
		}

		WP_CLI::success( sprintf( 'Processed %d jobs.', $processed ) );
	}

	/**
	 * Display statistics and status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpinsight stats
	 *
	 * @since 0.1.0
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function stats( array $args, array $assoc_args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wpdb;

		WP_CLI::log( '' );
		WP_CLI::log( '=== WPInsight Statistics ===' );
		WP_CLI::log( '' );

		// CPT counts.
		$plugin_count = wp_count_posts( WPInsight_CPT::get_plugin_post_type() )->publish ?? 0;
		$theme_count  = wp_count_posts( WPInsight_CPT::get_theme_post_type() )->publish ?? 0;

		WP_CLI::log( sprintf( 'Plugins: %s', number_format_i18n( $plugin_count ) ) );
		WP_CLI::log( sprintf( 'Themes:  %s', number_format_i18n( $theme_count ) ) );
		WP_CLI::log( '' );

		// Sync state.
		WP_CLI::log( '--- Sync State ---' );
		$plugin_state = WPInsight_Sync::get_sync_state( 'plugin' );
		$theme_state  = WPInsight_Sync::get_sync_state( 'theme' );

		WP_CLI::log( sprintf( 'Plugins: %s (page %d)', $plugin_state['status'], $plugin_state['page'] ) );
		if ( ! empty( $plugin_state['last_error'] ) ) {
			WP_CLI::warning( '  Error: ' . $plugin_state['last_error'] );
		}

		WP_CLI::log( sprintf( 'Themes:  %s (page %d)', $theme_state['status'], $theme_state['page'] ) );
		if ( ! empty( $theme_state['last_error'] ) ) {
			WP_CLI::warning( '  Error: ' . $theme_state['last_error'] );
		}
		WP_CLI::log( '' );

		// Queue stats.
		WP_CLI::log( '--- Download Queue ---' );
		$queue_stats = WPInsight_Zip_Queue::get_queue_stats();

		WP_CLI::log( sprintf( 'Pending:    %s', number_format_i18n( $queue_stats['pending'] ) ) );
		WP_CLI::log( sprintf( 'Processing: %s', number_format_i18n( $queue_stats['processing'] ) ) );
		WP_CLI::log( sprintf( 'Completed:  %s', number_format_i18n( $queue_stats['completed'] ) ) );
		WP_CLI::log( sprintf( 'Failed:     %s', number_format_i18n( $queue_stats['failed'] ) ) );
		WP_CLI::log( sprintf( 'Total:      %s', number_format_i18n( $queue_stats['total'] ) ) );
		WP_CLI::log( '' );

		// Artifacts.
		$artifacts_table = WPInsight_DB::get_table_name( 'artifacts' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$artifact_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$artifacts_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		WP_CLI::log( sprintf( 'Downloaded ZIPs: %s', number_format_i18n( $artifact_count ) ) );
		WP_CLI::log( '' );

		// Storage size (if directory exists).
		$upload_dir  = wp_upload_dir();
		$base_path   = WPInsight_Settings::get( 'storage_base_path', 'wpinsight' );
		$storage_dir = trailingslashit( $upload_dir['basedir'] ) . $base_path;

		if ( is_dir( $storage_dir ) ) {
			$size = self::get_directory_size( $storage_dir );
			WP_CLI::log( sprintf( 'Storage used: %s', size_format( $size, 2 ) ) );
		} else {
			WP_CLI::log( 'Storage directory does not exist yet.' );
		}

		WP_CLI::log( '' );
	}

	/**
	 * Manage download queue.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : Action to perform: retry, clear.
	 * ---
	 * options:
	 *   - retry
	 *   - clear
	 * ---
	 *
	 * [--limit=<limit>]
	 * : Maximum number of jobs to process (for retry).
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--days=<days>]
	 * : Days threshold for clearing completed jobs.
	 * ---
	 * default: 7
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpinsight queue retry
	 *     wp wpinsight queue retry --limit=50
	 *     wp wpinsight queue clear --days=30
	 *
	 * @since 0.1.0
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function queue( array $args, array $assoc_args ): void {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Action required: retry or clear.' );
		}

		$action = $args[0];

		switch ( $action ) {
			case 'retry':
				$limit = isset( $assoc_args['limit'] ) ? absint( $assoc_args['limit'] ) : 100;

				WP_CLI::log( sprintf( 'Retrying up to %d failed jobs...', $limit ) );
				$count = WPInsight_Zip_Queue::retry_failed_jobs( $limit );

				WP_CLI::success( sprintf( '%d jobs reset for retry.', $count ) );
				break;

			case 'clear':
				$days = isset( $assoc_args['days'] ) ? absint( $assoc_args['days'] ) : 7;

				WP_CLI::log( sprintf( 'Clearing completed jobs older than %d days...', $days ) );
				$count = WPInsight_Zip_Queue::clear_completed_jobs( $days );

				WP_CLI::success( sprintf( '%d completed jobs cleared.', $count ) );
				break;

			default:
				WP_CLI::error( sprintf( 'Invalid action: %s. Use retry or clear.', $action ) );
				break;
		}
	}

	/**
	 * Reset sync state.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Type to reset: plugins, themes, or both.
	 * ---
	 * default: both
	 * options:
	 *   - plugins
	 *   - themes
	 *   - both
	 * ---
	 *
	 * [--confirm]
	 * : Confirm the reset action.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpinsight reset --type=plugins --confirm
	 *     wp wpinsight reset --type=both --confirm
	 *
	 * @since 0.1.0
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public static function reset( array $args, array $assoc_args ): void {
		$type    = $assoc_args['type'] ?? 'both';
		$confirm = isset( $assoc_args['confirm'] );

		if ( ! $confirm ) {
			WP_CLI::error( 'This will reset sync state. Add --confirm to proceed.' );
		}

		// Validate type.
		if ( ! in_array( $type, [ 'plugins', 'themes', 'both' ], true ) ) {
			WP_CLI::error( 'Invalid type. Must be: plugins, themes, or both.' );
		}

		if ( 'both' === $type || 'plugins' === $type ) {
			WPInsight_Sync::reset_sync_state( 'plugin' );
			WP_CLI::success( 'Plugin sync state reset.' );
		}

		if ( 'both' === $type || 'themes' === $type ) {
			WPInsight_Sync::reset_sync_state( 'theme' );
			WP_CLI::success( 'Theme sync state reset.' );
		}
	}

	/**
	 * Show sync statistics after sync operation.
	 *
	 * @since 0.1.0
	 * @param string $type Type synced: plugins, themes, or both.
	 * @return void
	 */
	private static function show_sync_stats( string $type ): void {
		WP_CLI::log( '' );
		WP_CLI::log( '--- Sync Results ---' );

		if ( 'both' === $type || 'plugins' === $type ) {
			$plugin_state = WPInsight_Sync::get_sync_state( 'plugin' );
			$plugin_count = wp_count_posts( WPInsight_CPT::get_plugin_post_type() )->publish ?? 0;

			WP_CLI::log(
				sprintf(
					'Plugins: %s total | Status: %s (page %d)',
					number_format_i18n( $plugin_count ),
					$plugin_state['status'],
					$plugin_state['page']
				)
			);
		}

		if ( 'both' === $type || 'themes' === $type ) {
			$theme_state = WPInsight_Sync::get_sync_state( 'theme' );
			$theme_count = wp_count_posts( WPInsight_CPT::get_theme_post_type() )->publish ?? 0;

			WP_CLI::log(
				sprintf(
					'Themes:  %s total | Status: %s (page %d)',
					number_format_i18n( $theme_count ),
					$theme_state['status'],
					$theme_state['page']
				)
			);
		}

		// Show queue stats.
		$queue_stats = WPInsight_Zip_Queue::get_queue_stats();
		WP_CLI::log(
			sprintf(
				'Queue: %s pending | %s completed | %s failed',
				number_format_i18n( $queue_stats['pending'] ),
				number_format_i18n( $queue_stats['completed'] ),
				number_format_i18n( $queue_stats['failed'] )
			)
		);

		WP_CLI::log( '' );
	}

	/**
	 * Get total size of directory recursively.
	 *
	 * @since 0.1.0
	 * @param string $path Directory path.
	 * @return int Total size in bytes.
	 */
	private static function get_directory_size( string $path ): int {
		$size = 0;

		if ( ! is_dir( $path ) ) {
			return 0;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $files as $file ) {
			if ( $file->isFile() ) {
				$size += $file->getSize();
			}
		}

		return $size;
	}
}
