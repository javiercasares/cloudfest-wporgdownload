<?php
/**
 * CloudFest WPOrg Download - Main Plugin File
 *
 * This plugin downloads and archives ALL WordPress.org plugins including
 * historical versions. Metadata is stored in Custom Post Types and ZIP
 * files are organized in the local filesystem.
 *
 * Developed for the CloudFest Hackathon.
 *
 * REQUIRED DEPENDENCIES:
 * - Action Scheduler plugin (https://wordpress.org/plugins/action-scheduler/)
 *   OR WooCommerce (which includes Action Scheduler)
 *
 * This plugin will NOT activate without Action Scheduler being available.
 * The plugin uses Action Scheduler for reliable background job processing
 * to handle the massive scale of syncing 60,000+ plugins and 600,000+ ZIPs.
 *
 * @package    CloudFest_WPOrgDownload
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 * @link       https://github.com/javiercasares/cloudfest-wporgdownload
 *
 * @wordpress-plugin
 * Plugin Name:       CloudFest WPOrg Download
 * Plugin URI:        https://github.com/javiercasares/cloudfest-wporgdownload
 * Description:       Downloads and archives ALL WordPress.org plugins including historical versions. Requires Action Scheduler.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.4
 * Requires Plugins:  action-scheduler
 * Author:            CloudFest Team
 * Author URI:        https://hackathon.cloudfest.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       cloudfest-wporgdownload
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 *
 * Start at version 0.1.0 and use SemVer - https://semver.org
 *
 * @var string WPINSIGHT_VERSION Plugin version number.
 */
define( 'WPINSIGHT_VERSION', '0.1.0' );

/**
 * Plugin file path.
 *
 * Full path to the main plugin file. Used for activation/deactivation hooks.
 *
 * @var string WPINSIGHT_PLUGIN_FILE Absolute path to main plugin file.
 */
define( 'WPINSIGHT_PLUGIN_FILE', __FILE__ );

/**
 * Plugin directory path.
 *
 * Full path to the plugin directory with trailing slash.
 * Used for requiring class files and accessing plugin resources.
 *
 * @var string WPINSIGHT_PLUGIN_DIR Absolute path to plugin directory.
 */
define( 'WPINSIGHT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * Full URL to the plugin directory with trailing slash.
 * Used for enqueueing assets (CSS, JS, images).
 *
 * @var string WPINSIGHT_PLUGIN_URL URL to plugin directory.
 */
define( 'WPINSIGHT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/*
 * ============================================================================
 * WordPress OPTIONS CONSTANTS
 * ============================================================================
 */

/**
 * WordPress option name for plugin settings.
 *
 * All plugin settings are stored in a single WordPress option for performance.
 * This constant is used by WPInsight_Settings class and uninstall.php.
 *
 * @var string WPINSIGHT_SETTINGS_OPTION Option name for settings storage.
 */
define( 'WPINSIGHT_SETTINGS_OPTION', 'wpinsight_settings' );

/**
 * WordPress option name for database schema version.
 *
 * Used to track database schema version for upgrades and migrations.
 * This constant is used by WPInsight_DB class.
 *
 * @var string WPINSIGHT_DB_VERSION_OPTION Option name for schema version.
 */
define( 'WPINSIGHT_DB_VERSION_OPTION', 'wpinsight_db_version' );

/**
 * WordPress option name for plugin activation timestamp.
 *
 * Stores the datetime when the plugin was first activated.
 * Useful for analytics and tracking plugin age.
 *
 * @var string WPINSIGHT_ACTIVATED_AT_OPTION Option name for activation timestamp.
 */
define( 'WPINSIGHT_ACTIVATED_AT_OPTION', 'wpinsight_activated_at' );

/*
 * ============================================================================
 * RATE LIMITING CONSTANTS
 * ============================================================================
 */

/**
 * Maximum concurrent downloads allowed.
 *
 * CRITICAL: This limit prevents being banned by WordPress.org.
 * Never set higher than 5 concurrent downloads.
 * Default is 3 for safety.
 *
 * @var int WPINSIGHT_MAX_CONCURRENT_DOWNLOADS Maximum concurrent downloads.
 */
define( 'WPINSIGHT_MAX_CONCURRENT_DOWNLOADS', 3 );

/*
 * ============================================================================
 * SCHEDULING CONSTANTS
 * ============================================================================
 */

/**
 * Sync tick interval in seconds.
 *
 * How often the sync engine checks for new work.
 * Default: 300 seconds (5 minutes).
 *
 * @var int WPINSIGHT_SYNC_INTERVAL Sync check interval in seconds.
 */
define( 'WPINSIGHT_SYNC_INTERVAL', 300 );

/**
 * ZIP worker tick interval in seconds.
 *
 * How often the ZIP download worker checks for pending jobs.
 * Default: 60 seconds (1 minute).
 *
 * @var int WPINSIGHT_ZIP_WORKER_INTERVAL ZIP worker interval in seconds.
 */
define( 'WPINSIGHT_ZIP_WORKER_INTERVAL', 60 );

/*
 * ============================================================================
 * ACTION SCHEDULER CONSTANTS
 * ============================================================================
 */

/**
 * Action Scheduler group name.
 *
 * All plugin background jobs are grouped under this name.
 * Used for easy filtering and bulk operations.
 *
 * @var string WPINSIGHT_AS_GROUP Action Scheduler group name.
 */
define( 'WPINSIGHT_AS_GROUP', 'wpinsight' );

/**
 * Sync tick action hook name.
 *
 * Action Scheduler hook for the recurring sync check job.
 *
 * @var string WPINSIGHT_SYNC_TICK_ACTION Sync tick action hook.
 */
define( 'WPINSIGHT_SYNC_TICK_ACTION', 'wpinsight_sync_tick' );

/**
 * ZIP worker tick action hook name.
 *
 * Action Scheduler hook for the recurring ZIP download worker job.
 *
 * @var string WPINSIGHT_ZIP_WORKER_TICK_ACTION ZIP worker tick action hook.
 */
define( 'WPINSIGHT_ZIP_WORKER_TICK_ACTION', 'wpinsight_zip_worker_tick' );

/*
 * ============================================================================
 * BOOTSTRAP & HOOKS
 * ============================================================================
 */

// Load the bootstrap class.
require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-bootstrap.php';

/**
 * Plugin activation hook.
 *
 * Fired when the plugin is activated. Handles:
 * - Action Scheduler dependency check
 * - Database table creation
 * - CPT registration
 * - Rewrite rules flush
 * - Scheduled job setup
 *
 * @since 0.1.0
 */
register_activation_hook( __FILE__, array( 'WPInsight_Bootstrap', 'activate' ) );

/**
 * Plugin deactivation hook.
 *
 * Fired when the plugin is deactivated. Handles:
 * - Rewrite rules flush
 * - Unscheduling background jobs
 * - Data is preserved (not deleted)
 *
 * @since 0.1.0
 */
register_deactivation_hook( __FILE__, array( 'WPInsight_Bootstrap', 'deactivate' ) );

/**
 * Initialize plugin after all plugins are loaded.
 *
 * This ensures all dependencies (including Action Scheduler) are available
 * before we start initializing plugin components.
 *
 * @since 0.1.0
 */
add_action( 'plugins_loaded', array( 'WPInsight_Bootstrap', 'init' ) );
