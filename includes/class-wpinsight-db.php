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
	 *
	 * @since 0.1.0
	 * @var string SCHEMA_VERSION Current schema version.
	 */
	private const SCHEMA_VERSION = '1.1.0';


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
		$sql = array();

		// Table 1: Sync State - tracks pagination cursor and sync status.
		$sql[] = 'CREATE TABLE ' . self::get_table_name( 'sync_state' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sync_type varchar(20) NOT NULL DEFAULT 'plugin',
			page int(11) unsigned NOT NULL DEFAULT 1,
			per_page int(11) unsigned NOT NULL DEFAULT 100,
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
