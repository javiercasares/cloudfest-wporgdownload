<?php
/**
 * Bootstrap Class
 *
 * Main initialization class for the CloudFest WPOrg Download plugin.
 * Handles plugin activation, deactivation, and initialization of all components.
 *
 * This class is responsible for:
 * - Checking Action Scheduler dependency on activation
 * - Loading all plugin class files
 * - Registering WordPress hooks
 * - Setting up scheduled jobs
 * - Managing plugin lifecycle
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
 * Bootstrap class for plugin initialization.
 *
 * This class uses static methods only and is never instantiated.
 * It serves as the entry point for all plugin functionality.
 *
 * @since 0.1.0
 */
final class WPInsight_Bootstrap {

	/**
	 * Initialize the plugin.
	 *
	 * This method is called on the 'plugins_loaded' hook and sets up all
	 * plugin components. It loads class files, registers hooks, and initializes
	 * subsystems.
	 *
	 * Order of operations:
	 * 1. Load all class files
	 * 2. Register Custom Post Types
	 * 3. Hook database upgrade checker
	 * 4. Initialize admin UI
	 * 5. Initialize sync engine
	 * 6. Initialize ZIP queue
	 * 7. Register WP-CLI commands (if available)
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function init(): void {
		// Load database class (Phase 2).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-db.php';

		// Load settings class (Phase 2).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-settings.php';

		// Load logger class (Phase 5: v1.1.0+).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-logger.php';

		// Load storage class (Phase 9).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-storage.php';

		// Load CPT class (Phase 3).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-cpt.php';

		// Load Admin class (Phase 4).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-admin.php';

		// Load WordPress.org API Client (Phase 5).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-wporg-client.php';

		// Load Sync Engine (Phase 6).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-sync.php';

		// Load ZIP Queue Worker (Phase 7).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-zip-queue.php';

		// Register CPTs on init hook (Phase 3).
		add_action( 'init', array( 'WPInsight_CPT', 'register' ) );

		// Hook database upgrade checker (Phase 2).
		add_action( 'admin_init', array( 'WPInsight_DB', 'maybe_upgrade' ) );

		// Initialize logger (Phase 5: v1.1.0+).
		WPInsight_Logger::init();

		// Register AJAX handler for dismissing error notices (Phase 5: v1.1.0+).
		add_action( 'wp_ajax_wpinsight_dismiss_errors', array( 'WPInsight_Logger', 'ajax_dismiss_errors' ) );

		// Enqueue admin JavaScript for notice handling (Phase 5: v1.1.0+).
		add_action( 'admin_enqueue_scripts', array( 'WPInsight_Bootstrap', 'enqueue_admin_scripts' ) );

		// Initialize admin UI (Phase 4).
		WPInsight_Admin::init();

		// Initialize sync engine (Phase 6).
		WPInsight_Sync::init();

		// Initialize ZIP queue worker (Phase 7).
		WPInsight_Zip_Queue::init();

		// Schedule daily log cleanup (Phase 5: v1.1.0+).
		if ( ! wp_next_scheduled( 'wpinsight_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'wpinsight_cleanup_logs' );
		}
		add_action(
			'wpinsight_cleanup_logs',
			function () {
				WPInsight_Logger::clear_old_logs();
			}
		);

		// Register WP-CLI commands (Phase 8).
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-cli.php';
			WPInsight_CLI::register();
		}
	}

	/**
	 * Plugin activation handler.
	 *
	 * This method is called when the plugin is activated. It performs critical
	 * checks and setup operations:
	 *
	 * 1. Checks for Action Scheduler availability (REQUIRED dependency)
	 * 2. Creates database tables
	 * 3. Registers Custom Post Types
	 * 4. Flushes rewrite rules
	 * 5. Schedules recurring background jobs
	 * 6. Sets activation timestamp
	 *
	 * If Action Scheduler is not available, the plugin will deactivate itself
	 * and display an admin notice to the user.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function activate(): void {
		// Critical: Check for Action Scheduler dependency.
		if ( ! self::check_action_scheduler() ) {
			// Deactivate this plugin immediately.
			deactivate_plugins( WPINSIGHT_PLUGIN_FILE );

			// Display error notice to user.
			wp_die(
				wp_kses_post( self::get_action_scheduler_error_message() ),
				esc_html__( 'Plugin Activation Failed', 'cloudfest-wporgdownload' ),
				array(
					'back_link' => true,
					'response'  => 500,
				)
			);
		}

		// Install database tables (Phase 2).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-db.php';
		WPInsight_DB::install();

		// Load settings class (required by Sync and Zip Queue).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-settings.php';

		// Register CPTs and flush rewrite rules (Phase 3).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-cpt.php';
		WPInsight_CPT::register();
		flush_rewrite_rules();

		// Schedule sync job (Phase 6).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-sync.php';
		WPInsight_Sync::ensure_scheduled();

		// Schedule ZIP worker (Phase 7).
		require_once WPINSIGHT_PLUGIN_DIR . 'includes/class-wpinsight-zip-queue.php';
		WPInsight_Zip_Queue::ensure_scheduled();

		// Set activation timestamp for future reference.
		update_option( WPINSIGHT_ACTIVATED_AT_OPTION, current_time( 'mysql' ) );
	}

	/**
	 * Plugin deactivation handler.
	 *
	 * This method is called when the plugin is deactivated. It performs cleanup
	 * operations but PRESERVES all user data by default:
	 *
	 * 1. Flushes rewrite rules
	 * 2. Unschedules all Action Scheduler jobs
	 * 3. Does NOT delete CPT posts
	 * 4. Does NOT delete database tables
	 * 5. Does NOT delete uploaded ZIP files
	 *
	 * Data deletion only occurs on plugin uninstall if the user explicitly
	 * opts in via settings (see uninstall.php).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function deactivate(): void {
		// Flush rewrite rules to clean up permalinks.
		flush_rewrite_rules();

		// Unschedule all Action Scheduler jobs.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			// Unschedule sync tick (runs every 5 minutes).
			as_unschedule_all_actions( WPINSIGHT_SYNC_TICK_ACTION, array(), WPINSIGHT_AS_GROUP );

			// Unschedule ZIP worker tick (runs every 1 minute).
			as_unschedule_all_actions( WPINSIGHT_ZIP_WORKER_TICK_ACTION, array(), WPINSIGHT_AS_GROUP );
		}

		// Note: We intentionally do NOT delete any data here.
		// Data is only deleted on uninstall if user opts in (see uninstall.php).
	}

	/**
	 * Check if Action Scheduler is available.
	 *
	 * Action Scheduler is a REQUIRED dependency for this plugin. It provides
	 * reliable background job processing that can handle the massive scale of
	 * this plugin (600,000+ jobs).
	 *
	 * Action Scheduler can be installed via:
	 * - Standalone Action Scheduler plugin
	 * - WooCommerce (includes Action Scheduler)
	 * - Any other plugin that bundles Action Scheduler
	 *
	 * @since 0.1.0
	 * @return bool True if Action Scheduler is available, false otherwise.
	 */
	private static function check_action_scheduler(): bool {
		return function_exists( 'as_schedule_recurring_action' );
	}

	/**
	 * Get Action Scheduler error message.
	 *
	 * Returns a formatted error message explaining that Action Scheduler is
	 * required and how to install it.
	 *
	 * @since 0.1.0
	 * @return string HTML-formatted error message.
	 */
	private static function get_action_scheduler_error_message(): string {
		$message = '<h1>' . esc_html__( 'CloudFest WPOrg Download: Missing Dependency', 'cloudfest-wporgdownload' ) . '</h1>';

		$message .= '<p><strong>' . esc_html__( 'Action Scheduler is required but not found.', 'cloudfest-wporgdownload' ) . '</strong></p>';

		$message .= '<p>' . esc_html__( 'This plugin requires Action Scheduler for reliable background processing of large-scale operations.', 'cloudfest-wporgdownload' ) . '</p>';

		$message .= '<h2>' . esc_html__( 'How to Install Action Scheduler:', 'cloudfest-wporgdownload' ) . '</h2>';

		$message .= '<ol>';
		$message .= '<li>' . sprintf(
			/* translators: %s: Link to Action Scheduler plugin */
			wp_kses_post( __( 'Install the <a href="%s" target="_blank">Action Scheduler</a> plugin from WordPress.org', 'cloudfest-wporgdownload' ) ),
			'https://wordpress.org/plugins/action-scheduler/'
		) . '</li>';
		$message .= '<li>' . esc_html__( 'OR install WooCommerce (which includes Action Scheduler)', 'cloudfest-wporgdownload' ) . '</li>';
		$message .= '</ol>';

		$message .= '<p>' . esc_html__( 'Once Action Scheduler is installed and activated, you can activate this plugin.', 'cloudfest-wporgdownload' ) . '</p>';

		return $message;
	}

	/**
	 * Enqueue admin scripts for notice handling.
	 *
	 * Loads JavaScript for dismissible error notices with persistent state.
	 *
	 * @since 1.1.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_admin_scripts( string $hook_suffix ): void {
		// Only load on WPInsight admin pages.
		if ( false === strpos( $hook_suffix, 'wpinsight' ) ) {
			return;
		}

		wp_enqueue_script(
			'wpinsight-admin',
			plugins_url( 'assets/admin.js', WPINSIGHT_PLUGIN_FILE ),
			array( 'jquery' ),
			WPINSIGHT_VERSION,
			true
		);

		wp_localize_script(
			'wpinsight-admin',
			'wpinsightAdmin',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'dismissErrorsNonce' => wp_create_nonce( 'wpinsight_dismiss_errors' ),
			)
		);
	}
}
