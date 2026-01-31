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

/**
 * Option name for the "delete data on uninstall" setting.
 *
 * This option is stored in wp_options and controls whether plugin data
 * should be deleted when the plugin is uninstalled.
 *
 * @var string Option name in database.
 */
define( 'WPINSIGHT_DELETE_ON_UNINSTALL_OPTION', 'wpinsight_delete_on_uninstall' );

/**
 * Settings option name.
 *
 * Contains all plugin settings including the delete_on_uninstall preference.
 *
 * @var string Settings option name in database.
 */
define( 'WPINSIGHT_SETTINGS_OPTION', 'wpinsight_settings' );

// Get the user's preference for data deletion.
$wpinsight_settings    = get_option( WPINSIGHT_SETTINGS_OPTION, array() );
$wpinsight_delete_data = isset( $wpinsight_settings['delete_on_uninstall'] ) ? (bool) $wpinsight_settings['delete_on_uninstall'] : false;

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
 * Remove all custom tables created by the plugin.
 * TODO: Implement after database class is created in Phase 2.
 */

// TODO: Drop wpinsight_sync_state table.
// TODO: Drop wpinsight_zip_queue table.
// TODO: Drop wpinsight_artifacts table.
// Example implementation (to be uncommented when database tables exist).

/*
$table_sync_state = $wpdb->prefix . 'wpinsight_sync_state';
$table_zip_queue  = $wpdb->prefix . 'wpinsight_zip_queue';
$table_artifacts  = $wpdb->prefix . 'wpinsight_artifacts';

$wpdb->query( "DROP TABLE IF EXISTS {$table_sync_state}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$table_zip_queue}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$table_artifacts}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
*/

/*
 * ----------------------------------------------------------------------------
 * 3. DELETE PLUGIN OPTIONS
 * ----------------------------------------------------------------------------
 * Remove all options stored by the plugin in wp_options table.
 */

// Delete main settings option.
delete_option( WPINSIGHT_SETTINGS_OPTION );

// Delete database version option (used for schema upgrades).
delete_option( 'wpinsight_db_version' );

// Delete activation timestamp (if we add it later).
delete_option( 'wpinsight_activated_at' );

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
	as_unschedule_all_actions( 'wpinsight_sync_tick', array(), 'wpinsight' );
}

// Unschedule ZIP worker tick (runs every 1 minute).
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'wpinsight_zip_worker_tick', array(), 'wpinsight' );
}

/*
 * ============================================================================
 * CLEANUP COMPLETE
 * ============================================================================
 * All plugin data has been removed from the WordPress installation.
 * The user can safely reinstall the plugin later and start fresh.
 */
