<?php
/**
 * CloudFest WPOrg Download - Uninstall Handler
 *
 * Fired when the plugin is uninstalled via WordPress admin.
 *
 * This file handles the cleanup of all plugin data when the user explicitly
 * uninstalls the plugin. By default, data is PRESERVED unless the user has
 * opted in to delete data on uninstall via the plugin settings.
 *
 * IMPORTANT SECURITY NOTE:
 * This file is executed outside the normal WordPress context. The WP_UNINSTALL_PLUGIN
 * constant is defined by WordPress core before including this file. Always check
 * for this constant to prevent unauthorized direct access.
 *
 * DATA DELETION POLICY:
 * Per AGENTS.md requirements, plugin data must be preserved by default unless
 * the user explicitly opts in to data deletion. This prevents accidental data
 * loss during plugin troubleshooting or temporary removal.
 *
 * @package    CloudFest_WPOrgDownload
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 * @link       https://github.com/javiercasares/cloudfest-wporgdownload
 */

// If uninstall not called from WordPress, exit immediately.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * ============================================================================
 * LOAD PLUGIN CLASSES AND CONSTANTS
 * ============================================================================
 */

// Define plugin constants (same as main plugin file).
if ( ! defined( 'WPINSIGHT_PLUGIN_DIR' ) ) {
	define( 'WPINSIGHT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPINSIGHT_SETTINGS_OPTION' ) ) {
	define( 'WPINSIGHT_SETTINGS_OPTION', 'wpinsight_settings' );
}

if ( ! defined( 'WPINSIGHT_DB_VERSION_OPTION' ) ) {
	define( 'WPINSIGHT_DB_VERSION_OPTION', 'wpinsight_db_version' );
}

if ( ! defined( 'WPINSIGHT_ACTIVATED_AT_OPTION' ) ) {
	define( 'WPINSIGHT_ACTIVATED_AT_OPTION', 'wpinsight_activated_at' );
}

if ( ! defined( 'WPINSIGHT_AS_GROUP' ) ) {
	define( 'WPINSIGHT_AS_GROUP', 'wpinsight' );
}

if ( ! defined( 'WPINSIGHT_SYNC_TICK_ACTION' ) ) {
	define( 'WPINSIGHT_SYNC_TICK_ACTION', 'wpinsight_sync_tick' );
}

if ( ! defined( 'WPINSIGHT_ZIP_WORKER_TICK_ACTION' ) ) {
	define( 'WPINSIGHT_ZIP_WORKER_TICK_ACTION', 'wpinsight_zip_worker_tick' );
}

// Load required classes.
require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-settings.php';
require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-db.php';

/*
 * ============================================================================
 * CHECK USER PREFERENCE FOR DATA DELETION
 * ============================================================================
 */

// Get the user's preference for data deletion using Settings class.
$wpinsight_delete_data = WPInsight_Settings::get( 'delete_on_uninstall', false );

// If user has NOT opted in to delete data, exit early and preserve everything.
if ( ! $wpinsight_delete_data ) {
	// Data preservation - exit without deleting anything.
	// This is the default behavior to protect user data.
	return;
}

/*
 * ============================================================================
 * USER HAS OPTED IN TO DATA DELETION - PROCEED WITH CLEANUP
 * ============================================================================
 */

// Get global WordPress database object.
global $wpdb;

/*
 * ----------------------------------------------------------------------------
 * 1. DELETE CUSTOM POST TYPES
 * ----------------------------------------------------------------------------
 * Remove all plugin/theme CPT posts and their associated post meta.
 * TODO: Implement after CPT class is created in Phase 3.
 */

// TODO: Delete all 'wpinsight_plugin' posts.
// TODO: Delete all 'wpinsight_theme' posts.
// Example implementation (to be uncommented when CPT class exists).

/*
$plugin_posts = get_posts( array(
	'post_type'      => 'wpinsight_plugin',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );
foreach ( $plugin_posts as $post_id ) {
	wp_delete_post( $post_id, true ); // true = force delete, bypass trash.
}

$theme_posts = get_posts( array(
	'post_type'      => 'wpinsight_theme',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );
foreach ( $theme_posts as $post_id ) {
	wp_delete_post( $post_id, true );
}
*/

/*
 * ----------------------------------------------------------------------------
 * 2. DROP CUSTOM DATABASE TABLES
 * ----------------------------------------------------------------------------
 * Remove all custom tables created by the plugin using DB class.
 */

// Get table names using DB class helper method.
$wpinsight_table_sync_state = WPInsight_DB::get_table_name( 'sync_state' );
$wpinsight_table_zip_queue  = WPInsight_DB::get_table_name( 'zip_queue' );
$wpinsight_table_artifacts  = WPInsight_DB::get_table_name( 'artifacts' );

// Drop all custom tables.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpinsight_table_sync_state ) );
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpinsight_table_zip_queue ) );
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpinsight_table_artifacts ) );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

/*
 * ----------------------------------------------------------------------------
 * 3. DELETE PLUGIN OPTIONS
 * ----------------------------------------------------------------------------
 * Remove all options stored by the plugin in wp_options table.
 */

// Delete main settings option using Settings class.
WPInsight_Settings::delete();

// Delete database version option (used for schema upgrades).
delete_option( WPINSIGHT_DB_VERSION_OPTION );

// Delete activation timestamp.
delete_option( WPINSIGHT_ACTIVATED_AT_OPTION );

// Delete any full sync status options.
delete_option( 'wpinsight_full_sync_running' );
delete_option( 'wpinsight_full_sync_progress' );

/*
 * ----------------------------------------------------------------------------
 * 4. DELETE TRANSIENTS
 * ----------------------------------------------------------------------------
 * Remove all transients created by the plugin.
 * Transients are temporary cached data with expiration times.
 */

// Delete active downloads lock transient (used for rate limiting).
delete_transient( 'wpinsight_active_downloads' );

// Delete any API response cache transients (if we implement caching).
delete_transient( 'wpinsight_api_cache' );

// Delete full sync cursor transient (if used).
delete_transient( 'wpinsight_sync_cursor' );

/*
 * ----------------------------------------------------------------------------
 * 5. DELETE UPLOADED FILES
 * ----------------------------------------------------------------------------
 * Remove all ZIP files and directories created by the plugin.
 * TODO: Implement after storage class is created in Phase 9.
 */

// TODO: Delete wp-content/uploads/wpinsight/ directory and all contents.
// Example implementation (to be uncommented when storage is implemented).

/*
$upload_dir = wp_upload_dir();
$wpinsight_dir = trailingslashit( $upload_dir['basedir'] ) . 'wpinsight/';

if ( file_exists( $wpinsight_dir ) && is_dir( $wpinsight_dir ) ) {
	// Recursively delete directory and all contents.
	// Use WordPress filesystem API for safety.
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;
	$wp_filesystem->delete( $wpinsight_dir, true ); // true = recursive.
}
*/

/*
 * ----------------------------------------------------------------------------
 * 6. UNSCHEDULE CRON JOBS
 * ----------------------------------------------------------------------------
 * Remove all scheduled Action Scheduler actions.
 * Action Scheduler handles its own cleanup, but we'll explicitly unschedule.
 */

// Unschedule sync tick (runs every 5 minutes).
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( WPINSIGHT_SYNC_TICK_ACTION, array(), WPINSIGHT_AS_GROUP );
}

// Unschedule ZIP worker tick (runs every 1 minute).
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( WPINSIGHT_ZIP_WORKER_TICK_ACTION, array(), WPINSIGHT_AS_GROUP );
}

/*
 * ============================================================================
 * CLEANUP COMPLETE
 * ============================================================================
 * All plugin data has been removed from the WordPress installation.
 * The user can safely reinstall the plugin later and start fresh.
 */
