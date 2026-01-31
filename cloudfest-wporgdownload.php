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
