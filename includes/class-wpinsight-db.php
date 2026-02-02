<?php
/**
 * Database Schema Class
 *
 * Handles database table creation, schema versioning, and migrations for the
 * CloudFest WPOrg Download plugin.
 *
 * This class manages three custom tables:
 * - wpinsight_sync_state: Tracks sync pagination and status
 * - wpinsight_zip_queue: Download job queue (~600K rows expected)
 * - wpinsight_artifacts: Downloaded ZIP file records (~600K rows expected)
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
 * Database schema management class.
 *
 * This class uses static methods only and is never instantiated.
 * It handles all database table operations using WordPress dbDelta() API.
 *
 * @since 0.1.0
 */
final class WPInsight_DB {

	/**
	 * Current database schema version.
	 *
	 * Increment this when making schema changes to trigger upgrades.
	 *
	 * Version History:
	 * - 1.0.0: Initial schema
	 * - 1.1.0: Added last_error to sync_state, composite indexes to zip_queue, error_log table
	 * - 1.2.0: Added remote_filesize to zip_queue for size detection system
	 *
	 * @since 0.1.0
	 * @var string SCHEMA_VERSION Current schema version.
	 */
	private const SCHEMA_VERSION = '1.2.0';


	/**
	 * Get current schema version.
	 *
	 * Returns the hard-coded schema version constant. This is used to compare
	 * against the stored version in the database to determine if an upgrade
	 * is needed.
	 *
	 * @since 0.1.0
	 * @return string Current schema version (e.g., '1.0.0').
	 */
	public static function get_schema_version(): string {
		return self::SCHEMA_VERSION;
	}

	/**
	 * Install database tables.
	 *
	 * This method is called on plugin activation. It creates all required
	 * custom tables if they don't exist and stores the schema version.
	 *
	 * Uses WordPress dbDelta() for safe table creation that won't fail if
	 * tables already exist.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function install(): void {
		// Create tables.
		self::create_tables();

		// Store schema version.
		update_option( WPINSIGHT_DB_VERSION_OPTION, self::SCHEMA_VERSION );
	}

	/**
	 * Create custom database tables.
	 *
	 * Creates three custom tables using WordPress dbDelta() API:
	 * 1. wpinsight_sync_state - Sync pagination and status tracking
	 * 2. wpinsight_zip_queue - Download job queue
	 * 3. wpinsight_artifacts - Downloaded ZIP file records
	 *
	 * dbDelta() is safe to run multiple times - it will only create tables
	 * that don't exist or update schema if needed.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		// Get charset collation for tables.
		$charset_collate = $wpdb->get_charset_collate();

		// Load dbDelta() function.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// SQL statements for all tables.
		$sql = [];

		// Table 1: Sync State - tracks pagination cursor and sync status.
		$sql[] = 'CREATE TABLE ' . self::get_table_name( 'sync_state' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sync_type varchar(20) NOT NULL DEFAULT 'plugin',
			page int(11) unsigned NOT NULL DEFAULT 1,
			per_page int(11) unsigned NOT NULL DEFAULT 250,
			total_items int(11) unsigned DEFAULT NULL,
			total_pages int(11) unsigned DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'idle',
			last_error text DEFAULT NULL,
			last_run_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY sync_type (sync_type),
			KEY status (status)
		) $charset_collate;";

		// Table 2: ZIP Queue - download job queue (~600K rows expected).
		$sql[] = 'CREATE TABLE ' . self::get_table_name( 'zip_queue' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			item_type varchar(20) NOT NULL DEFAULT 'plugin',
			slug varchar(255) NOT NULL,
			version varchar(50) NOT NULL,
			download_url varchar(500) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
			max_attempts tinyint(3) unsigned NOT NULL DEFAULT 3,
			priority int(11) NOT NULL DEFAULT 0,
			last_error text DEFAULT NULL,
			remote_filesize bigint(20) unsigned DEFAULT NULL,
			scheduled_at datetime DEFAULT NULL,
			queued_at datetime DEFAULT NULL,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug_version (slug(191), version(50), item_type),
			KEY status (status),
			KEY item_type (item_type),
			KEY scheduled_at (scheduled_at),
			KEY idx_status_started (status, started_at),
			KEY idx_pending_priority (status, priority, queued_at)
		) $charset_collate;";

		// Table 3: Artifacts - downloaded ZIP file records (~600K rows expected).
		$sql[] = 'CREATE TABLE ' . self::get_table_name( 'artifacts' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			item_type varchar(20) NOT NULL DEFAULT 'plugin',
			slug varchar(255) NOT NULL,
			version varchar(50) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_size bigint(20) unsigned DEFAULT NULL,
			file_hash varchar(64) DEFAULT NULL,
			download_url varchar(500) NOT NULL,
			downloaded_at datetime NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug_version (slug(191), version(50), item_type),
			KEY item_type (item_type),
			KEY slug (slug(191))
		) $charset_collate;";

		// Table 4: Error Log - centralized error logging (since v1.1.0).
		$sql[] = 'CREATE TABLE ' . self::get_table_name( 'error_log' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			severity varchar(20) NOT NULL,
			message text NOT NULL,
			context text DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_severity_time (severity, created_at)
		) $charset_collate;";

		// Execute all CREATE TABLE statements.
		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * Check if database upgrade is needed and perform it.
	 *
	 * This method is called on admin_init hook. It compares the stored schema
	 * version against the current code version and triggers an upgrade if needed.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$stored_version = get_option( WPINSIGHT_DB_VERSION_OPTION, '0.0.0' );

		// Compare stored version with current version.
		if ( version_compare( $stored_version, self::SCHEMA_VERSION, '<' ) ) {
			self::upgrade( $stored_version );
		}
	}

	/**
	 * Perform database schema upgrade.
	 *
	 * Handles schema migrations from one version to another. Runs version-specific
	 * migrations in order before recreating tables.
	 *
	 * @since 0.1.0
	 * @param string $from_version The version we're upgrading from.
	 * @return void
	 */
	private static function upgrade( string $from_version ): void {
		// Run version-specific migrations before recreating tables.
		if ( version_compare( $from_version, '1.1.0', '<' ) ) {
			self::migrate_to_1_1_0();
		}

		if ( version_compare( $from_version, '1.2.0', '<' ) ) {
			self::migrate_to_1_2_0();
		}

		// Recreate tables (dbDelta will update schema if needed).
		self::create_tables();

		// Update stored version.
		update_option( WPINSIGHT_DB_VERSION_OPTION, self::SCHEMA_VERSION );
	}

	/**
	 * Migrate database schema from 1.0.0 to 1.1.0.
	 *
	 * Changes in v1.1.0:
	 * - Add last_error column to sync_state table
	 * - Add priority and queued_at columns to zip_queue table
	 * - Add composite indexes to zip_queue for performance
	 * - Create error_log table
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private static function migrate_to_1_1_0(): void {
		global $wpdb;

		$sync_state_table = self::get_table_name( 'sync_state' );
		$zip_queue_table  = self::get_table_name( 'zip_queue' );

		// Check if last_error column exists in sync_state.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW COLUMNS FROM %i LIKE %s',
				$sync_state_table,
				'last_error'
			)
		);

		if ( empty( $column_exists ) ) {
			// Add last_error column to sync_state (v1.1.0 migration).
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD COLUMN last_error TEXT DEFAULT NULL AFTER status',
					$sync_state_table
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		// Check if priority column exists in zip_queue.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW COLUMNS FROM %i LIKE %s',
				$zip_queue_table,
				'priority'
			)
		);

		if ( empty( $column_exists ) ) {
			// Add priority column to zip_queue (v1.1.0 migration).
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD COLUMN priority INT(11) NOT NULL DEFAULT 0 AFTER max_attempts',
					$zip_queue_table
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		// Check if queued_at column exists in zip_queue.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW COLUMNS FROM %i LIKE %s',
				$zip_queue_table,
				'queued_at'
			)
		);

		if ( empty( $column_exists ) ) {
			// Add queued_at column to zip_queue (v1.1.0 migration).
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD COLUMN queued_at DATETIME DEFAULT NULL AFTER scheduled_at',
					$zip_queue_table
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		// Add composite indexes to zip_queue.
		// Note: dbDelta will handle these in create_tables(), but we check manually for immediate effect.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$indexes = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW INDEX FROM %i WHERE Key_name = %s',
				$zip_queue_table,
				'idx_status_started'
			)
		);

		if ( empty( $indexes ) ) {
			// Add composite index for status+timestamp queries (v1.1.0 migration).
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD INDEX idx_status_started (status, started_at)',
					$zip_queue_table
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$indexes = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW INDEX FROM %i WHERE Key_name = %s',
				$zip_queue_table,
				'idx_pending_priority'
			)
		);

		if ( empty( $indexes ) ) {
			// Add composite index for priority queue queries (v1.1.0 migration).
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD INDEX idx_pending_priority (status, priority, queued_at)',
					$zip_queue_table
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		// error_log table will be created by dbDelta in create_tables().
	}

	/**
	 * Migrate database schema from 1.1.0 to 1.2.0.
	 *
	 * Changes in v1.2.0:
	 * - Add remote_filesize column to zip_queue table for size detection system
	 *
	 * @since 1.3.0
	 * @return void
	 */
	private static function migrate_to_1_2_0(): void {
		global $wpdb;

		$zip_queue_table = self::get_table_name( 'zip_queue' );

		// Check if remote_filesize column exists in zip_queue.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$column_exists = $wpdb->get_results(
			$wpdb->prepare(
				'SHOW COLUMNS FROM %i LIKE %s',
				$zip_queue_table,
				'remote_filesize'
			)
		);

		if ( empty( $column_exists ) ) {
			// Add remote_filesize column to zip_queue (v1.2.0 migration).
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query(
				$wpdb->prepare(
					'ALTER TABLE %i ADD COLUMN remote_filesize BIGINT(20) UNSIGNED DEFAULT NULL AFTER last_error',
					$zip_queue_table
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

			WPInsight_Logger::info( 'Database migrated to v1.2.0: added remote_filesize column to zip_queue' );
		}
	}

	/**
	 * Get statistics for a specific table.
	 *
	 * Returns detailed information about a table including size, row count,
	 * data size, index size, and last optimization time.
	 *
	 * @since 1.5.0
	 * @param string $table_name Full table name with prefix.
	 * @return array{
	 *     name: string,
	 *     rows: int,
	 *     data_size: int,
	 *     index_size: int,
	 *     total_size: int,
	 *     data_size_formatted: string,
	 *     index_size_formatted: string,
	 *     total_size_formatted: string,
	 *     engine: string,
	 *     last_optimize: string|null
	 * }|null Table statistics or null if table doesn't exist.
	 */
	public static function get_table_stats( string $table_name ): ?array {
		global $wpdb;

		// Get table status from information_schema.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT
					TABLE_NAME as name,
					TABLE_ROWS as row_count,
					DATA_LENGTH as data_size,
					INDEX_LENGTH as index_size,
					(DATA_LENGTH + INDEX_LENGTH) as total_size,
					ENGINE as engine,
					UPDATE_TIME as last_optimize
				FROM information_schema.TABLES
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s',
				DB_NAME,
				$table_name
			),
			ARRAY_A
		);

		if ( ! $result ) {
			return null;
		}

		// Format sizes for display.
		$result['rows']                 = (int) $result['row_count'];
		$result['data_size']            = (int) $result['data_size'];
		$result['index_size']           = (int) $result['index_size'];
		$result['total_size']           = (int) $result['total_size'];
		$result['data_size_formatted']  = size_format( $result['data_size'], 2 );
		$result['index_size_formatted'] = size_format( $result['index_size'], 2 );
		$result['total_size_formatted'] = size_format( $result['total_size'], 2 );
		$result['last_optimize']        = $result['last_optimize'] ?? null;

		// Remove temporary row_count key.
		unset( $result['row_count'] );

		return $result;
	}

	/**
	 * Get statistics for all WPInsight tables.
	 *
	 * Returns statistics for sync_state, zip_queue, artifacts, and error_log tables.
	 *
	 * @since 1.5.0
	 * @return array<string, array> Array of table statistics keyed by short table name.
	 */
	public static function get_all_tables_stats(): array {
		$tables = [ 'sync_state', 'zip_queue', 'artifacts', 'error_log' ];
		$stats  = [];

		foreach ( $tables as $table ) {
			$full_name   = self::get_table_name( $table );
			$table_stats = self::get_table_stats( $full_name );
			if ( $table_stats ) {
				$stats[ $table ] = $table_stats;
			}
		}

		return $stats;
	}

	/**
	 * Check health of a table.
	 *
	 * Runs CHECK TABLE command to verify table integrity and index health.
	 *
	 * @since 1.5.0
	 * @param string $table_name Full table name with prefix.
	 * @return array{
	 *     status: string,
	 *     msg_type: string,
	 *     msg_text: string
	 * }[] Array of check results.
	 */
	public static function check_table_health( string $table_name ): array {
		global $wpdb;

		// Run CHECK TABLE command.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"CHECK TABLE `{$table_name}`",
			ARRAY_A
		);

		if ( ! $results ) {
			return [
				[
					'status'   => 'error',
					'msg_type' => 'error',
					'msg_text' => 'Failed to check table',
				],
			];
		}

		// Format results.
		$formatted = [];
		foreach ( $results as $row ) {
			$formatted[] = [
				'status'   => $row['Msg_type'] ?? 'unknown',
				'msg_type' => $row['Msg_type'] ?? 'unknown',
				'msg_text' => $row['Msg_text'] ?? '',
			];
		}

		return $formatted;
	}

	/**
	 * Optimize a table.
	 *
	 * Runs OPTIMIZE TABLE command to reclaim unused space and defragment the table.
	 * This can improve query performance on large tables.
	 *
	 * @since 1.5.0
	 * @param string $table_name Full table name with prefix.
	 * @return array{
	 *     success: bool,
	 *     message: string
	 * } Result of optimization.
	 */
	public static function optimize_table( string $table_name ): array {
		global $wpdb;

		// Run OPTIMIZE TABLE command.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"OPTIMIZE TABLE `{$table_name}`",
			ARRAY_A
		);

		if ( ! $results ) {
			return [
				'success' => false,
				'message' => 'Failed to optimize table',
			];
		}

		// Check if optimization succeeded.
		$last_result = end( $results );
		$success     = isset( $last_result['Msg_type'] ) && 'status' === $last_result['Msg_type'];

		return [
			'success' => $success,
			'message' => $last_result['Msg_text'] ?? 'Unknown result',
		];
	}

	/**
	 * Repair a table.
	 *
	 * Runs REPAIR TABLE command to fix corrupted tables.
	 * Only use this if CHECK TABLE indicates corruption.
	 *
	 * @since 1.5.0
	 * @param string $table_name Full table name with prefix.
	 * @return array{
	 *     success: bool,
	 *     message: string
	 * } Result of repair.
	 */
	public static function repair_table( string $table_name ): array {
		global $wpdb;

		// Run REPAIR TABLE command.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"REPAIR TABLE `{$table_name}`",
			ARRAY_A
		);

		if ( ! $results ) {
			return [
				'success' => false,
				'message' => 'Failed to repair table',
			];
		}

		// Check if repair succeeded.
		$last_result = end( $results );
		$success     = isset( $last_result['Msg_type'] ) && 'status' === $last_result['Msg_type'];

		return [
			'success' => $success,
			'message' => $last_result['Msg_text'] ?? 'Unknown result',
		];
	}

	/**
	 * Get index information for a table.
	 *
	 * Returns detailed information about all indexes on a table.
	 *
	 * @since 1.5.0
	 * @param string $table_name Full table name with prefix.
	 * @return array<int, array{
	 *     name: string,
	 *     column: string,
	 *     unique: bool,
	 *     type: string,
	 *     cardinality: int
	 * }> Array of index information.
	 */
	public static function get_table_indexes( string $table_name ): array {
		global $wpdb;

		// Get index information from SHOW INDEX.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SHOW INDEX FROM `{$table_name}`",
			ARRAY_A
		);

		if ( ! $results ) {
			return [];
		}

		// Format index information.
		$indexes = [];
		foreach ( $results as $row ) {
			$indexes[] = [
				'name'        => $row['Key_name'] ?? '',
				'column'      => $row['Column_name'] ?? '',
				'unique'      => isset( $row['Non_unique'] ) && 0 === (int) $row['Non_unique'],
				'type'        => $row['Index_type'] ?? 'BTREE',
				'cardinality' => (int) ( $row['Cardinality'] ?? 0 ),
			];
		}

		return $indexes;
	}

	/**
	 * Get full table name with WordPress prefix.
	 *
	 * Converts a short table name (e.g., 'sync_state') to a full WordPress
	 * table name with prefix (e.g., 'wp_wpinsight_sync_state').
	 *
	 * @since 0.1.0
	 * @param string $table Short table name without prefix (e.g., 'sync_state').
	 * @return string Full table name with prefix (e.g., 'wp_wpinsight_sync_state').
	 */
	public static function get_table_name( string $table ): string {
		global $wpdb;
		return $wpdb->prefix . 'wpinsight_' . $table;
	}
}
