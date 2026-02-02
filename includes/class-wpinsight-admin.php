<?php
/**
 * Admin UI Class
 *
 * Handles all admin interface elements including settings page, menus, and notices.
 *
 * This class provides the WordPress admin UI for configuring the plugin settings.
 * It uses the WordPress Settings API for all settings management, ensuring
 * proper nonce validation, capability checks, and sanitization.
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
 * Admin UI management class.
 *
 * This class uses static methods only and is never instantiated.
 * It handles all WordPress admin interface elements.
 *
 * @since 0.1.0
 */
final class WPInsight_Admin {

	/**
	 * Dashboard page slug.
	 *
	 * @since 0.1.0
	 * @var string DASHBOARD_PAGE_SLUG Dashboard page menu slug.
	 */
	private const DASHBOARD_PAGE_SLUG = 'wpinsight-dashboard';

	/**
	 * Settings page slug.
	 *
	 * @since 0.1.0
	 * @var string SETTINGS_PAGE_SLUG Settings page menu slug.
	 */
	private const SETTINGS_PAGE_SLUG = 'wpinsight-settings';

	/**
	 * Settings option group.
	 *
	 * @since 0.1.0
	 * @var string SETTINGS_GROUP Settings API option group.
	 */
	private const SETTINGS_GROUP = 'wpinsight_settings_group';

	/**
	 * Initialize admin interface.
	 *
	 * Hooks into WordPress admin to add menus and register settings.
	 * Called from bootstrap on plugins_loaded.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_dashboard_actions' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_debug_actions' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_database_actions' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_download_control_actions' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_diagnostic_export' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_settings_import' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_export_download' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets (CSS/JS).
	 *
	 * Enqueues scripts and styles for admin pages with real-time updates.
	 *
	 * @since 1.7.0
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_admin_assets( string $hook_suffix ): void {
		// Only enqueue on dashboard page.
		if ( 'tools_page_wpinsight-dashboard' !== $hook_suffix ) {
			return;
		}

		// Enqueue dashboard live updates script.
		wp_enqueue_script(
			'wpinsight-dashboard-live',
			WPINSIGHT_PLUGIN_URL . 'assets/js/dashboard-live.js',
			array( 'jquery' ),
			WPINSIGHT_VERSION,
			true
		);

		// Localize script with REST API endpoints and configuration.
		wp_localize_script(
			'wpinsight-dashboard-live',
			'wpinsightLive',
			array(
				'endpoints' => array(
					'dashboardStats'   => rest_url( 'wpinsight/v1/dashboard-stats' ),
					'activeDownloads'  => rest_url( 'wpinsight/v1/active-downloads' ),
					'syncStatus'       => rest_url( 'wpinsight/v1/sync-status' ),
					'downloadNow'      => rest_url( 'wpinsight/v1/download-now' ),
					'pauseDownloads'   => rest_url( 'wpinsight/v1/pause-downloads' ),
					'resumeDownloads'  => rest_url( 'wpinsight/v1/resume-downloads' ),
				),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'pollInterval' => 5000, // 5 seconds
			)
		);

		// Enqueue inline CSS for animations.
		wp_add_inline_style(
			'wp-admin',
			'
			[data-stat-container].stat-increased [data-stat] {
				color: #00a32a;
				font-weight: 600;
				transition: color 0.3s ease;
			}
			[data-stat-container].stat-decreased [data-stat] {
				color: #d63638;
				font-weight: 600;
				transition: color 0.3s ease;
			}
			'
		);
	}

	/**
	 * Add admin menu items.
	 *
	 * Adds Dashboard and Settings pages to WordPress admin menu.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function add_admin_menu(): void {
		// Get critical error count for badge.
		$error_count = self::get_critical_error_count();
		$error_badge = $error_count > 0 ? sprintf( ' <span class="awaiting-mod">%d</span>', $error_count ) : '';

		// Add main Dashboard page under Tools menu.
		add_management_page(
			__( 'WPInsight Dashboard', 'cloudfest-wporgdownload' ),           // Page title.
			__( 'WPInsight', 'cloudfest-wporgdownload' ) . $error_badge,      // Menu title with badge.
			'manage_options',                                                   // Capability.
			self::DASHBOARD_PAGE_SLUG,                                          // Menu slug.
			array( __CLASS__, 'render_dashboard_page' )                              // Callback.
		);

		// Add Settings page under Settings menu.
		add_options_page(
			__( 'WPInsight Settings', 'cloudfest-wporgdownload' ), // Page title.
			__( 'WPInsight', 'cloudfest-wporgdownload' ),           // Menu title.
			'manage_options',                                        // Capability.
			self::SETTINGS_PAGE_SLUG,                                // Menu slug.
			array( __CLASS__, 'render_settings_page' )                    // Callback.
		);

		// Add Error Log page under Tools menu (only visible when WP_DEBUG is enabled).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_management_page(
				__( 'WPInsight Error Log', 'cloudfest-wporgdownload' ),  // Page title.
				__( 'WPInsight Errors', 'cloudfest-wporgdownload' ),     // Menu title.
				'manage_options',                                          // Capability.
				'wpinsight-error-log',                                     // Menu slug.
				array( __CLASS__, 'render_error_log_page' )               // Callback.
			);
		}

		// Add Import/Export page under Tools menu.
		add_management_page(
			__( 'WPInsight Import/Export', 'cloudfest-wporgdownload' ), // Page title.
			__( 'WPInsight Import/Export', 'cloudfest-wporgdownload' ), // Menu title.
			'manage_options',                                             // Capability.
			'wpinsight-import-export',                                    // Menu slug.
			array( __CLASS__, 'render_import_export_page' )              // Callback.
		);
	}

	/**
	 * Register plugin settings with WordPress Settings API.
	 *
	 * Registers settings, sections, and fields for the settings page.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register_settings(): void {
		// Register the main settings option.
		register_setting(
			self::SETTINGS_GROUP,
			WPINSIGHT_SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		// General Settings Section.
		add_settings_section(
			'wpinsight_general',
			__( 'General Settings', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_section_general' ),
			self::SETTINGS_PAGE_SLUG
		);

		// Sync Settings Section.
		add_settings_section(
			'wpinsight_sync',
			__( 'Sync Settings', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_section_sync' ),
			self::SETTINGS_PAGE_SLUG
		);

		// Rate Limiting Settings Section.
		add_settings_section(
			'wpinsight_rate_limiting',
			__( 'Rate Limiting', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_section_rate_limiting' ),
			self::SETTINGS_PAGE_SLUG
		);

		// Error Notifications Settings Section (v1.1.0+).
		add_settings_section(
			'wpinsight_notifications',
			__( 'Error Notifications', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_section_notifications' ),
			self::SETTINGS_PAGE_SLUG
		);

		// General fields.
		add_settings_field(
			'delete_on_uninstall',
			__( 'Delete Data on Uninstall', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_delete_on_uninstall' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_general'
		);

		// Sync fields.
		add_settings_field(
			'auto_sync_enabled',
			__( 'Enable Auto Sync', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_auto_sync_enabled' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'sync_plugins_enabled',
			__( 'Sync Plugins', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_sync_plugins_enabled' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'sync_themes_enabled',
			__( 'Sync Themes', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_sync_themes_enabled' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'download_plugin_zips_enabled',
			__( 'Download Plugin ZIPs', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_download_plugin_zips_enabled' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'download_theme_zips_enabled',
			__( 'Download Theme ZIPs', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_download_theme_zips_enabled' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'sync_interval',
			__( 'Sync Interval (seconds)', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_sync_interval' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		// Rate limiting fields.
		add_settings_field(
			'max_concurrent_downloads',
			__( 'Max Concurrent Downloads', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_max_concurrent_downloads' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_rate_limiting'
		);

		add_settings_field(
			'max_size_detection_rate',
			__( 'Size Detection Rate (req/sec)', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_max_size_detection_rate' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_rate_limiting'
		);

		add_settings_field(
			'zip_worker_interval',
			__( 'ZIP Worker Interval (seconds)', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_zip_worker_interval' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_rate_limiting'
		);

		add_settings_field(
			'max_retries',
			__( 'Max Download Retries', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_max_retries' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_rate_limiting'
		);

		// Error notification fields (v1.1.0+).
		add_settings_field(
			'admin_email_notifications_enabled',
			__( 'Enable Email Notifications', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_admin_email_notifications_enabled' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_notifications'
		);

		add_settings_field(
			'admin_notification_email',
			__( 'Notification Email', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_admin_notification_email' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_notifications'
		);

		add_settings_field(
			'log_retention_days',
			__( 'Log Retention (days)', 'cloudfest-wporgdownload' ),
			array( __CLASS__, 'render_field_log_retention_days' ),
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_notifications'
		);
	}

	/**
	 * Handle dashboard actions.
	 *
	 * Processes manual sync and queue management actions from the dashboard.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function handle_dashboard_actions(): void {
		// Only process on dashboard page.
		if ( ! isset( $_GET['page'] ) || self::DASHBOARD_PAGE_SLUG !== $_GET['page'] ) {
			return;
		}

		// Check if action is set.
		if ( ! isset( $_POST['wpinsight_action'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['wpinsight_dashboard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpinsight_dashboard_nonce'] ) ), 'wpinsight_dashboard_action' ) ) {
			add_settings_error( 'wpinsight_dashboard', 'invalid_nonce', __( 'Security check failed.', 'cloudfest-wporgdownload' ), 'error' );
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error( 'wpinsight_dashboard', 'insufficient_permissions', __( 'You do not have sufficient permissions.', 'cloudfest-wporgdownload' ), 'error' );
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['wpinsight_action'] ) );

		switch ( $action ) {
			case 'sync_plugins':
				// Check current state and reset if completed/error.
				$state = WPInsight_Sync::get_sync_state( 'plugin' );
				if ( in_array( $state['status'], array( 'completed', 'error' ), true ) ) {
					WPInsight_Sync::reset_sync_state( 'plugin' );
				}

				// Enqueue sync job in Action Scheduler (async).
				if ( function_exists( 'as_enqueue_async_action' ) ) {
					as_enqueue_async_action( 'wpinsight_sync_plugins', array(), WPINSIGHT_AS_GROUP );
					// Set state to queued for immediate user feedback.
					WPInsight_Sync::update_sync_state( 'plugin', 'queued', 1 );
					add_settings_error( 'wpinsight_dashboard', 'sync_enqueued', __( 'Plugin sync queued. It will start processing shortly.', 'cloudfest-wporgdownload' ), 'success' );
				} else {
					add_settings_error( 'wpinsight_dashboard', 'sync_error', __( 'Action Scheduler not available. Cannot start sync.', 'cloudfest-wporgdownload' ), 'error' );
				}
				break;

			case 'sync_themes':
				// Check current state and reset if completed/error.
				$state = WPInsight_Sync::get_sync_state( 'theme' );
				if ( in_array( $state['status'], array( 'completed', 'error' ), true ) ) {
					WPInsight_Sync::reset_sync_state( 'theme' );
				}

				// Enqueue sync job in Action Scheduler (async).
				if ( function_exists( 'as_enqueue_async_action' ) ) {
					as_enqueue_async_action( 'wpinsight_sync_themes', array(), WPINSIGHT_AS_GROUP );
					// Set state to queued for immediate user feedback.
					WPInsight_Sync::update_sync_state( 'theme', 'queued', 1 );
					add_settings_error( 'wpinsight_dashboard', 'sync_enqueued', __( 'Theme sync queued. It will start processing shortly.', 'cloudfest-wporgdownload' ), 'success' );
				} else {
					add_settings_error( 'wpinsight_dashboard', 'sync_error', __( 'Action Scheduler not available. Cannot start sync.', 'cloudfest-wporgdownload' ), 'error' );
				}
				break;

			case 'process_queue':
				WPInsight_Zip_Queue::worker_tick();
				add_settings_error( 'wpinsight_dashboard', 'queue_processed', __( 'Queue processed. Check statistics below.', 'cloudfest-wporgdownload' ), 'success' );
				break;

			case 'retry_failed':
				$count = WPInsight_Zip_Queue::retry_failed_jobs( 100 );
				add_settings_error(
					'wpinsight_dashboard',
					'retry_success',
					sprintf(
						/* translators: %d: number of jobs reset */
						__( '%d failed jobs reset for retry.', 'cloudfest-wporgdownload' ),
						$count
					),
					'success'
				);
				break;

			case 'clear_completed':
				$count = WPInsight_Zip_Queue::clear_completed_jobs( 7 );
				add_settings_error(
					'wpinsight_dashboard',
					'clear_success',
					sprintf(
						/* translators: %d: number of jobs cleared */
						__( '%d completed jobs cleared.', 'cloudfest-wporgdownload' ),
						$count
					),
					'success'
				);
				break;

			case 'clear_old_logs':
				if ( check_admin_referer( 'wpinsight_clear_old_logs', 'wpinsight_clear_logs_nonce' ) ) {
					$days    = 30; // Clear logs older than 30 days.
					$deleted = WPInsight_Logger::clear_old_logs( $days );
					add_settings_error(
						'wpinsight_dashboard',
						'logs_cleared',
						sprintf(
							/* translators: %d: number of logs cleared */
							__( '%d old log entries cleared.', 'cloudfest-wporgdownload' ),
							$deleted
						),
						'success'
					);
				}
				break;

			case 'full_sync_plugins':
				// Enqueue full sync job in Action Scheduler (async).
				if ( function_exists( 'as_enqueue_async_action' ) ) {
					// Reset sync state to start fresh.
					WPInsight_Sync::reset_sync_state( 'plugin' );
					as_enqueue_async_action( 'wpinsight_full_sync_plugins', array(), WPINSIGHT_AS_GROUP );
					// Set state to queued for immediate user feedback.
					WPInsight_Sync::update_sync_state( 'plugin', 'queued', 1 );
					add_settings_error( 'wpinsight_dashboard', 'full_sync_enqueued', __( 'Full plugin sync queued. It will start processing shortly. This may take hours.', 'cloudfest-wporgdownload' ), 'success' );
				} else {
					add_settings_error( 'wpinsight_dashboard', 'sync_error', __( 'Action Scheduler not available. Cannot start sync.', 'cloudfest-wporgdownload' ), 'error' );
				}
				break;

			case 'full_sync_themes':
				// Enqueue full sync job in Action Scheduler (async).
				if ( function_exists( 'as_enqueue_async_action' ) ) {
					// Reset sync state to start fresh.
					WPInsight_Sync::reset_sync_state( 'theme' );
					as_enqueue_async_action( 'wpinsight_full_sync_themes', array(), WPINSIGHT_AS_GROUP );
					// Set state to queued for immediate user feedback.
					WPInsight_Sync::update_sync_state( 'theme', 'queued', 1 );
					add_settings_error( 'wpinsight_dashboard', 'full_sync_enqueued', __( 'Full theme sync queued. It will start processing shortly. This may take hours.', 'cloudfest-wporgdownload' ), 'success' );
				} else {
					add_settings_error( 'wpinsight_dashboard', 'sync_error', __( 'Action Scheduler not available. Cannot start sync.', 'cloudfest-wporgdownload' ), 'error' );
				}
				break;

			case 'reset_sync_plugins':
				// Reset sync state in database.
				WPInsight_Sync::reset_sync_state( 'plugin' );

				// Cancel all pending Action Scheduler jobs for plugin sync.
				if ( function_exists( 'as_unschedule_all_actions' ) ) {
					as_unschedule_all_actions( 'wpinsight_sync_plugins', array(), WPINSIGHT_AS_GROUP );
					as_unschedule_all_actions( 'wpinsight_full_sync_plugins', array(), WPINSIGHT_AS_GROUP );
				}

				add_settings_error( 'wpinsight_dashboard', 'reset_success', __( 'Plugin sync state reset and pending jobs cancelled.', 'cloudfest-wporgdownload' ), 'success' );
				break;

			case 'reset_sync_themes':
				// Reset sync state in database.
				WPInsight_Sync::reset_sync_state( 'theme' );

				// Cancel all pending Action Scheduler jobs for theme sync.
				if ( function_exists( 'as_unschedule_all_actions' ) ) {
					as_unschedule_all_actions( 'wpinsight_sync_themes', array(), WPINSIGHT_AS_GROUP );
					as_unschedule_all_actions( 'wpinsight_full_sync_themes', array(), WPINSIGHT_AS_GROUP );
				}

				add_settings_error( 'wpinsight_dashboard', 'reset_success', __( 'Theme sync state reset and pending jobs cancelled.', 'cloudfest-wporgdownload' ), 'success' );
				break;

			default:
				add_settings_error( 'wpinsight_dashboard', 'invalid_action', __( 'Invalid action.', 'cloudfest-wporgdownload' ), 'error' );
				break;
		}
	}

	/**
	 * Handle debug actions.
	 *
	 * Processes debug tool actions like running cron manually or resetting workers.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public static function handle_debug_actions(): void {
		// Only process on dashboard page.
		if ( ! isset( $_GET['page'] ) || self::DASHBOARD_PAGE_SLUG !== $_GET['page'] ) {
			return;
		}

		// Check if debug action is set.
		if ( ! isset( $_POST['wpinsight_debug_action'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['wpinsight_debug_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpinsight_debug_nonce'] ) ), 'wpinsight_debug_action' ) ) {
			add_settings_error( 'wpinsight_dashboard', 'invalid_nonce', __( 'Security check failed.', 'cloudfest-wporgdownload' ), 'error' );
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error( 'wpinsight_dashboard', 'insufficient_permissions', __( 'You do not have sufficient permissions.', 'cloudfest-wporgdownload' ), 'error' );
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['wpinsight_debug_action'] ) );

		switch ( $action ) {
			case 'run_cron':
				if ( ! isset( $_POST['cron_hook'] ) ) {
					add_settings_error( 'wpinsight_dashboard', 'missing_hook', __( 'Missing cron hook.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				$hook = sanitize_text_field( wp_unslash( $_POST['cron_hook'] ) );

				// Validate hook is one of ours.
				$valid_hooks = array( 'wpinsight_sync_tick', 'wpinsight_zip_worker_tick', 'wpinsight_size_detection_tick' );
				if ( ! in_array( $hook, $valid_hooks, true ) ) {
					add_settings_error( 'wpinsight_dashboard', 'invalid_hook', __( 'Invalid cron hook.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				// Execute the worker directly.
				$start_time = microtime( true );
				$result     = null;

				try {
					switch ( $hook ) {
						case 'wpinsight_sync_tick':
							WPInsight_Sync::sync_tick();
							$result = __( 'Sync tick executed.', 'cloudfest-wporgdownload' );
							break;

						case 'wpinsight_zip_worker_tick':
							WPInsight_Zip_Queue::worker_tick();
							$result = __( 'ZIP worker tick executed.', 'cloudfest-wporgdownload' );
							break;

						case 'wpinsight_size_detection_tick':
							WPInsight_Zip_Queue::size_detection_tick();
							$result = __( 'Size detection tick executed.', 'cloudfest-wporgdownload' );
							break;
					}

					$execution_time = round( ( microtime( true ) - $start_time ) * 1000, 2 );

					add_settings_error(
						'wpinsight_dashboard',
						'cron_executed',
						sprintf(
							/* translators: 1: result message, 2: execution time in milliseconds */
							__( '%1$s (Execution time: %2$s ms)', 'cloudfest-wporgdownload' ),
							$result,
							$execution_time
						),
						'success'
					);
				} catch ( Exception $e ) {
					add_settings_error(
						'wpinsight_dashboard',
						'cron_error',
						sprintf(
							/* translators: %s: error message */
							__( 'Error executing cron: %s', 'cloudfest-wporgdownload' ),
							$e->getMessage()
						),
						'error'
					);
				}
				break;

			case 'schedule_worker':
				if ( ! isset( $_POST['cron_hook'] ) ) {
					add_settings_error( 'wpinsight_dashboard', 'missing_hook', __( 'Missing cron hook.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				$hook = sanitize_text_field( wp_unslash( $_POST['cron_hook'] ) );

				// Validate hook is one of ours.
				$valid_hooks = array( 'wpinsight_sync_tick', 'wpinsight_zip_worker_tick', 'wpinsight_size_detection_tick' );
				if ( ! in_array( $hook, $valid_hooks, true ) ) {
					add_settings_error( 'wpinsight_dashboard', 'invalid_hook', __( 'Invalid cron hook.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
					add_settings_error( 'wpinsight_dashboard', 'scheduler_unavailable', __( 'Action Scheduler is not available.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				// Schedule the specific worker.
				try {
					switch ( $hook ) {
						case 'wpinsight_sync_tick':
							WPInsight_Sync::ensure_scheduled();
							$worker_name = __( 'Sync Worker', 'cloudfest-wporgdownload' );
							break;

						case 'wpinsight_zip_worker_tick':
							WPInsight_Zip_Queue::ensure_scheduled();
							$worker_name = __( 'ZIP Worker', 'cloudfest-wporgdownload' );
							break;

						case 'wpinsight_size_detection_tick':
							WPInsight_Zip_Queue::ensure_size_detection_scheduled();
							$worker_name = __( 'Size Detection Worker', 'cloudfest-wporgdownload' );
							break;
					}

					add_settings_error(
						'wpinsight_dashboard',
						'worker_scheduled',
						sprintf(
							/* translators: %s: worker name */
							__( '%s has been scheduled successfully.', 'cloudfest-wporgdownload' ),
							$worker_name
						),
						'success'
					);
				} catch ( Exception $e ) {
					add_settings_error(
						'wpinsight_dashboard',
						'schedule_error',
						sprintf(
							/* translators: %s: error message */
							__( 'Error scheduling worker: %s', 'cloudfest-wporgdownload' ),
							$e->getMessage()
						),
						'error'
					);
				}
				break;

			case 'reset_crons':
				if ( ! function_exists( 'as_unschedule_all_actions' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
					add_settings_error( 'wpinsight_dashboard', 'scheduler_unavailable', __( 'Action Scheduler is not available.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				// Unschedule all existing workers.
				as_unschedule_all_actions( 'wpinsight_sync_tick', array(), WPINSIGHT_AS_GROUP );
				as_unschedule_all_actions( 'wpinsight_zip_worker_tick', array(), WPINSIGHT_AS_GROUP );
				as_unschedule_all_actions( 'wpinsight_size_detection_tick', array(), WPINSIGHT_AS_GROUP );

				// Reschedule workers.
				WPInsight_Sync::ensure_scheduled();
				WPInsight_Zip_Queue::ensure_scheduled();
				WPInsight_Zip_Queue::ensure_size_detection_scheduled();

				add_settings_error(
					'wpinsight_dashboard',
					'crons_reset',
					__( 'All scheduled workers have been reset and recreated successfully.', 'cloudfest-wporgdownload' ),
					'success'
				);
				break;

			default:
				add_settings_error( 'wpinsight_dashboard', 'invalid_debug_action', __( 'Invalid debug action.', 'cloudfest-wporgdownload' ), 'error' );
				break;
		}
	}

	/**
	 * Handle database diagnostic actions.
	 *
	 * Processes database maintenance actions: check, optimize, repair tables.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function handle_database_actions(): void {
		// Only process on dashboard page.
		if ( ! isset( $_GET['page'] ) || self::DASHBOARD_PAGE_SLUG !== $_GET['page'] ) {
			return;
		}

		// Check if database action is set.
		if ( ! isset( $_POST['wpinsight_db_action'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['wpinsight_db_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpinsight_db_nonce'] ) ), 'wpinsight_db_action' ) ) {
			add_settings_error( 'wpinsight_dashboard', 'invalid_nonce', __( 'Security check failed.', 'cloudfest-wporgdownload' ), 'error' );
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error( 'wpinsight_dashboard', 'insufficient_permissions', __( 'You do not have sufficient permissions.', 'cloudfest-wporgdownload' ), 'error' );
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['wpinsight_db_action'] ) );

		switch ( $action ) {
			case 'check_table':
				if ( ! isset( $_POST['table_name'] ) ) {
					add_settings_error( 'wpinsight_dashboard', 'missing_table', __( 'Missing table name.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				$table_short_name = sanitize_text_field( wp_unslash( $_POST['table_name'] ) );
				$table_name       = WPInsight_DB::get_table_name( $table_short_name );

				try {
					$results = WPInsight_DB::check_table_health( $table_name );

					$messages = array();
					foreach ( $results as $row ) {
						$messages[] = sprintf( '%s: %s', $row['msg_type'], $row['msg_text'] );
					}

					add_settings_error(
						'wpinsight_dashboard',
						'check_complete',
						sprintf(
							/* translators: 1: table name, 2: check results */
							__( 'Table %1$s checked: %2$s', 'cloudfest-wporgdownload' ),
							$table_short_name,
							implode( ', ', $messages )
						),
						'success'
					);
				} catch ( Exception $e ) {
					add_settings_error(
						'wpinsight_dashboard',
						'check_error',
						sprintf(
							/* translators: %s: error message */
							__( 'Error checking table: %s', 'cloudfest-wporgdownload' ),
							$e->getMessage()
						),
						'error'
					);
				}
				break;

			case 'optimize_table':
				if ( ! isset( $_POST['table_name'] ) ) {
					add_settings_error( 'wpinsight_dashboard', 'missing_table', __( 'Missing table name.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				$table_short_name = sanitize_text_field( wp_unslash( $_POST['table_name'] ) );
				$table_name       = WPInsight_DB::get_table_name( $table_short_name );

				try {
					$result = WPInsight_DB::optimize_table( $table_name );

					if ( $result['success'] ) {
						add_settings_error(
							'wpinsight_dashboard',
							'optimize_success',
							sprintf(
								/* translators: 1: table name, 2: result message */
								__( 'Table %1$s optimized: %2$s', 'cloudfest-wporgdownload' ),
								$table_short_name,
								$result['message']
							),
							'success'
						);
						WPInsight_Logger::info( "Table {$table_short_name} optimized by user" );
					} else {
						add_settings_error(
							'wpinsight_dashboard',
							'optimize_failed',
							sprintf(
								/* translators: 1: table name, 2: error message */
								__( 'Failed to optimize table %1$s: %2$s', 'cloudfest-wporgdownload' ),
								$table_short_name,
								$result['message']
							),
							'error'
						);
					}
				} catch ( Exception $e ) {
					add_settings_error(
						'wpinsight_dashboard',
						'optimize_error',
						sprintf(
							/* translators: %s: error message */
							__( 'Error optimizing table: %s', 'cloudfest-wporgdownload' ),
							$e->getMessage()
						),
						'error'
					);
				}
				break;

			case 'repair_table':
				if ( ! isset( $_POST['table_name'] ) ) {
					add_settings_error( 'wpinsight_dashboard', 'missing_table', __( 'Missing table name.', 'cloudfest-wporgdownload' ), 'error' );
					break;
				}

				$table_short_name = sanitize_text_field( wp_unslash( $_POST['table_name'] ) );
				$table_name       = WPInsight_DB::get_table_name( $table_short_name );

				try {
					$result = WPInsight_DB::repair_table( $table_name );

					if ( $result['success'] ) {
						add_settings_error(
							'wpinsight_dashboard',
							'repair_success',
							sprintf(
								/* translators: 1: table name, 2: result message */
								__( 'Table %1$s repaired: %2$s', 'cloudfest-wporgdownload' ),
								$table_short_name,
								$result['message']
							),
							'success'
						);
						WPInsight_Logger::warning( "Table {$table_short_name} repaired by user" );
					} else {
						add_settings_error(
							'wpinsight_dashboard',
							'repair_failed',
							sprintf(
								/* translators: 1: table name, 2: error message */
								__( 'Failed to repair table %1$s: %2$s', 'cloudfest-wporgdownload' ),
								$table_short_name,
								$result['message']
							),
							'error'
						);
					}
				} catch ( Exception $e ) {
					add_settings_error(
						'wpinsight_dashboard',
						'repair_error',
						sprintf(
							/* translators: %s: error message */
							__( 'Error repairing table: %s', 'cloudfest-wporgdownload' ),
							$e->getMessage()
						),
						'error'
					);
				}
				break;

			case 'optimize_all':
				try {
					$tables  = array( 'sync_state', 'zip_queue', 'artifacts', 'error_log' );
					$results = array();

					foreach ( $tables as $table_short_name ) {
						$table_name = WPInsight_DB::get_table_name( $table_short_name );
						$result     = WPInsight_DB::optimize_table( $table_name );
						$results[ $table_short_name ] = $result;
					}

					$success_count = count( array_filter( $results, fn( $r ) => $r['success'] ) );
					$total_count   = count( $results );

					add_settings_error(
						'wpinsight_dashboard',
						'optimize_all_complete',
						sprintf(
							/* translators: 1: successful optimizations, 2: total tables */
							__( 'Optimized %1$d of %2$d tables successfully.', 'cloudfest-wporgdownload' ),
							$success_count,
							$total_count
						),
						$success_count === $total_count ? 'success' : 'warning'
					);
					WPInsight_Logger::info( "All tables optimized by user: {$success_count}/{$total_count} successful" );
				} catch ( Exception $e ) {
					add_settings_error(
						'wpinsight_dashboard',
						'optimize_all_error',
						sprintf(
							/* translators: %s: error message */
							__( 'Error optimizing tables: %s', 'cloudfest-wporgdownload' ),
							$e->getMessage()
						),
						'error'
					);
				}
				break;

			default:
				add_settings_error( 'wpinsight_dashboard', 'invalid_db_action', __( 'Invalid database action.', 'cloudfest-wporgdownload' ), 'error' );
				break;
		}
	}

	/**
	 * Handle export download.
	 *
	 * Intercepts export requests and generates file download BEFORE any HTML is rendered.
	 * This must run on admin_init to work properly.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function handle_export_download(): void {
		// Only process on import/export page.
		if ( ! isset( $_GET['page'] ) || 'wpinsight-import-export' !== $_GET['page'] ) {
			return;
		}

		// Check if export button was clicked.
		if ( ! isset( $_POST['wpinsight_export'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wpinsight_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'cloudfest-wporgdownload' ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'cloudfest-wporgdownload' ) );
		}

		// Get export parameters.
		$export_type = isset( $_POST['export_type'] ) ? sanitize_text_field( wp_unslash( $_POST['export_type'] ) ) : 'all';
		$compress    = isset( $_POST['compress'] );

		// Validate export type.
		if ( ! in_array( $export_type, array( 'plugins', 'themes', 'all' ), true ) ) {
			wp_die( esc_html__( 'Invalid export type.', 'cloudfest-wporgdownload' ) );
		}

		// Generate export data.
		$data = '';
		switch ( $export_type ) {
			case 'plugins':
				$data = WPInsight_Export::export_plugins( $compress );
				break;
			case 'themes':
				$data = WPInsight_Export::export_themes( $compress );
				break;
			case 'all':
			default:
				$data = WPInsight_Export::export_all( $compress );
				break;
		}

		// Check if export was successful.
		if ( empty( $data ) ) {
			wp_die( esc_html__( 'Failed to generate export. Please check error logs.', 'cloudfest-wporgdownload' ) );
		}

		// Generate filename with timestamp.
		$filename = 'wpinsight-export-' . $export_type . '-' . gmdate( 'Y-m-d-His' ) . '.json' . ( $compress ? '.gz' : '' );

		// Clear any output buffers.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Set headers for download.
		header( 'Content-Type: application/' . ( $compress ? 'gzip' : 'json' ) );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $data ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output file content and exit.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary data, JSON, or compressed content.
		echo $data;
		exit;
	}

	/**
	 * Render dashboard page.
	 *
	 * Displays the main dashboard with statistics, sync status, and action buttons.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_dashboard_page(): void {
		global $wpdb;

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cloudfest-wporgdownload' ) );
		}

		// Prepare template variables.
		$plugin_count = wp_count_posts( WPInsight_CPT::get_plugin_post_type() )->publish ?? 0;
		$theme_count  = wp_count_posts( WPInsight_CPT::get_theme_post_type() )->publish ?? 0;

		$artifacts_table = WPInsight_DB::get_table_name( 'artifacts' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Simple COUNT query on custom table for dashboard statistics. Table name from get_table_name() is safe.
		$artifact_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$artifacts_table}" );

		$upload_dir   = wp_upload_dir();
		$base_path    = WPInsight_Settings::get( 'storage_base_path', 'wpinsight' );
		$storage_dir  = trailingslashit( $upload_dir['basedir'] ) . $base_path;
		$storage_size = 0;
		if ( is_dir( $storage_dir ) ) {
			$storage_size = self::get_directory_size( $storage_dir );
		}

		$plugin_state = WPInsight_Sync::get_sync_state( 'plugin' );
		$theme_state  = WPInsight_Sync::get_sync_state( 'theme' );
		$queue_stats  = WPInsight_Zip_Queue::get_queue_stats();

		// Get sync progress data for Phase 18.3
		$plugin_progress = self::get_sync_progress_data( 'plugin' );
		$theme_progress  = self::get_sync_progress_data( 'theme' );

		// Get active downloads and disk space info for Phase 18.4
		$active_downloads = self::get_active_downloads();
		$disk_space       = self::get_disk_space_info();
		$downloads_paused = WPInsight_Settings::get( 'downloads_paused', false );

		// Get system health data for Phase 18.6
		$system_health = self::get_system_health_data();
		$overall_health = self::get_overall_health_status( $system_health );

		// Get API health data for Phase 18.5
		$api_health = self::get_api_health_data();

		// Pass admin class reference for helper methods.
		$admin = __CLASS__;

		// Get recent error logs for dashboard (last 10 entries).
		$logs_table = WPInsight_DB::get_table_name( 'error_log' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$recent_logs = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, severity, message, context, created_at
				FROM %i
				ORDER BY created_at DESC
				LIMIT %d',
				$logs_table,
				10
			),
			ARRAY_A
		);

		// Load template.
		require WPINSIGHT_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	/**
	 * Render recent errors card.
	 *
	 * Displays the 10 most recent error log entries on the dashboard.
	 * Color-coded by severity with truncated messages.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	private static function render_recent_errors_card(): void {
		$recent_errors = WPInsight_Logger::get_recent_errors( 10 );
		?>
		<div class="card">
			<h2><?php esc_html_e( 'Recent Errors', 'cloudfest-wporgdownload' ); ?></h2>

			<?php if ( empty( $recent_errors ) ) : ?>
				<p style="color: #00a32a;">
					<span style="font-size: 20px;">✓</span>
					<strong><?php esc_html_e( 'No recent errors', 'cloudfest-wporgdownload' ); ?></strong>
				</p>
			<?php else : ?>
				<table class="widefat striped" style="font-size: 0.9em;">
					<thead>
						<tr>
							<th style="width: 15%;"><?php esc_html_e( 'Severity', 'cloudfest-wporgdownload' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Time', 'cloudfest-wporgdownload' ); ?></th>
							<th style="width: 65%;"><?php esc_html_e( 'Message', 'cloudfest-wporgdownload' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_errors as $error ) : ?>
							<tr>
								<td><?php echo wp_kses_post( self::get_severity_badge_html( $error['severity'] ) ); ?></td>
								<td style="font-size: 0.85em; color: #646970;">
									<?php
									echo esc_html(
										human_time_diff(
											strtotime( $error['created_at'] ),
											time()
										)
									);
									?>
									<?php esc_html_e( 'ago', 'cloudfest-wporgdownload' ); ?>
								</td>
								<td style="font-size: 0.9em;">
									<?php
									$message = strlen( $error['message'] ) > 150
										? substr( $error['message'], 0, 150 ) . '...'
										: $error['message'];
									echo esc_html( $message );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
					<p style="margin-top: 10px;">
						<a href="<?php echo esc_url( admin_url( 'tools.php?page=wpinsight-error-log' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'View Full Error Log', 'cloudfest-wporgdownload' ); ?>
						</a>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get colored severity badge HTML.
	 *
	 * Returns HTML for a color-coded severity badge based on log level.
	 *
	 * @since 1.1.0
	 * @param string $severity Severity level (emergency, error, warning, info, debug).
	 * @return string HTML badge markup.
	 */
	public static function get_severity_badge_html( string $severity ): string {
		$colors = array(
			'emergency' => '#d63638', // Red.
			'error'     => '#d63638', // Red.
			'warning'   => '#f0b849', // Orange.
			'info'      => '#2271b1', // Blue.
			'debug'     => '#646970', // Gray.
		);

		$color = $colors[ $severity ] ?? '#646970';

		return sprintf(
			'<span style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.75em; font-weight: 600; text-transform: uppercase; background-color: %s; color: #fff;">%s</span>',
			esc_attr( $color ),
			esc_html( $severity )
		);
	}

	/**
	 * Render error log page.
	 *
	 * Displays the full error log with filtering and pagination.
	 * Only accessible when WP_DEBUG is enabled.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function render_error_log_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cloudfest-wporgdownload' ) );
		}

		// Handle clear logs action.
		if ( isset( $_POST['wpinsight_clear_logs'] ) && check_admin_referer( 'wpinsight_clear_logs' ) ) {
			$days    = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;
			$deleted = WPInsight_Logger::clear_old_logs( $days );

			add_settings_error(
				'wpinsight_error_log',
				'logs_cleared',
				sprintf(
					/* translators: %d: number of logs deleted */
					__( '%d log entries deleted.', 'cloudfest-wporgdownload' ),
					$deleted
				),
				'success'
			);
		}

		// Get filter parameters.
		$severity = isset( $_GET['severity'] ) ? sanitize_text_field( wp_unslash( $_GET['severity'] ) ) : null;
		$per_page = 50;
		$page_num = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$offset   = ( $page_num - 1 ) * $per_page;

		// Get total count for pagination.
		global $wpdb;
		$table = WPInsight_DB::get_table_name( 'error_log' );

		if ( null !== $severity ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE severity = %s',
					$table,
					$severity
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table )
			);
		}

		$total_pages = (int) ceil( $total / $per_page );

		// Get logs for current page.
		if ( null !== $severity ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$logs = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, severity, message, context, created_at
					FROM %i
					WHERE severity = %s
					ORDER BY created_at DESC
					LIMIT %d OFFSET %d',
					$table,
					$severity,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$logs = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, severity, message, context, created_at
					FROM %i
					ORDER BY created_at DESC
					LIMIT %d OFFSET %d',
					$table,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'wpinsight_error_log' ); ?>

			<div class="card" style="max-width: 100%; width: 100%;">
				<h2><?php esc_html_e( 'Filter Logs', 'cloudfest-wporgdownload' ); ?></h2>
				<form method="get">
					<input type="hidden" name="page" value="wpinsight-error-log" />
					<label for="severity"><?php esc_html_e( 'Severity:', 'cloudfest-wporgdownload' ); ?></label>
					<select name="severity" id="severity">
						<option value=""><?php esc_html_e( 'All Levels', 'cloudfest-wporgdownload' ); ?></option>
						<option value="emergency" <?php selected( $severity, 'emergency' ); ?>><?php esc_html_e( 'Emergency', 'cloudfest-wporgdownload' ); ?></option>
						<option value="error" <?php selected( $severity, 'error' ); ?>><?php esc_html_e( 'Error', 'cloudfest-wporgdownload' ); ?></option>
						<option value="warning" <?php selected( $severity, 'warning' ); ?>><?php esc_html_e( 'Warning', 'cloudfest-wporgdownload' ); ?></option>
						<option value="info" <?php selected( $severity, 'info' ); ?>><?php esc_html_e( 'Info', 'cloudfest-wporgdownload' ); ?></option>
						<option value="debug" <?php selected( $severity, 'debug' ); ?>><?php esc_html_e( 'Debug', 'cloudfest-wporgdownload' ); ?></option>
					</select>
					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'cloudfest-wporgdownload' ); ?></button>

					<?php if ( null !== $severity ) : ?>
						<a href="<?php echo esc_url( admin_url( 'tools.php?page=wpinsight-error-log' ) ); ?>" class="button">
							<?php esc_html_e( 'Clear Filter', 'cloudfest-wporgdownload' ); ?>
						</a>
					<?php endif; ?>
				</form>
			</div>

			<div class="card" style="margin-top: 20px; max-width: 100%; width: 100%;">
				<h2>
					<?php esc_html_e( 'Error Log', 'cloudfest-wporgdownload' ); ?>
					<span style="font-weight: normal; color: #646970;">
						(<?php echo esc_html( number_format_i18n( $total ) ); ?> <?php esc_html_e( 'entries', 'cloudfest-wporgdownload' ); ?>)
					</span>
				</h2>

				<?php if ( empty( $logs ) ) : ?>
					<p><?php esc_html_e( 'No log entries found.', 'cloudfest-wporgdownload' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th style="width: 5%;"><?php esc_html_e( 'ID', 'cloudfest-wporgdownload' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Severity', 'cloudfest-wporgdownload' ); ?></th>
								<th style="width: 15%;"><?php esc_html_e( 'Time', 'cloudfest-wporgdownload' ); ?></th>
								<th style="width: 70%;"><?php esc_html_e( 'Message', 'cloudfest-wporgdownload' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $log['id'] ); ?></td>
									<td><?php echo wp_kses_post( self::get_severity_badge_html( $log['severity'] ) ); ?></td>
									<td style="font-size: 0.9em; color: #646970;">
										<?php echo esc_html( (string) wp_date( 'Y-m-d H:i:s', strtotime( $log['created_at'] ) ) ); ?>
									</td>
									<td>
										<div style="word-break: break-word;">
											<?php echo esc_html( $log['message'] ); ?>
										</div>
										<?php if ( ! empty( $log['context'] ) ) : ?>
											<details style="margin-top: 5px;">
												<summary style="cursor: pointer; color: #2271b1; font-size: 0.9em;">
													<?php esc_html_e( 'Show Context', 'cloudfest-wporgdownload' ); ?>
												</summary>
												<pre style="background: #f6f7f7; padding: 10px; margin-top: 5px; overflow-x: auto; font-size: 0.85em;"><?php echo esc_html( $log['context'] ); ?></pre>
											</details>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php if ( $total_pages > 1 ) : ?>
						<div style="margin-top: 15px;">
							<?php
							echo wp_kses_post(
								paginate_links(
									array(
										'base'      => add_query_arg( 'paged', '%#%' ),
										'format'    => '',
										'current'   => $page_num,
										'total'     => $total_pages,
										'prev_text' => __( '&laquo; Previous', 'cloudfest-wporgdownload' ),
										'next_text' => __( 'Next &raquo;', 'cloudfest-wporgdownload' ),
									)
								)
							);
							?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="card" style="margin-top: 20px; max-width: 100%; width: 100%;">
				<h2><?php esc_html_e( 'Maintenance', 'cloudfest-wporgdownload' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'wpinsight_clear_logs' ); ?>
					<p>
						<label for="days">
							<?php esc_html_e( 'Delete logs older than:', 'cloudfest-wporgdownload' ); ?>
						</label>
						<input type="number" name="days" id="days" value="30" min="1" max="365" class="small-text" />
						<?php esc_html_e( 'days', 'cloudfest-wporgdownload' ); ?>
					</p>
					<button type="submit" name="wpinsight_clear_logs" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete old logs?', 'cloudfest-wporgdownload' ); ?>');">
						<?php esc_html_e( 'Clear Old Logs', 'cloudfest-wporgdownload' ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Render import/export page.
	 *
	 * Displays the import/export interface for backing up and restoring
	 * plugin and theme metadata (CPT data only, no ZIP files).
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public static function render_import_export_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cloudfest-wporgdownload' ) );
		}

		// Note: Export form submission is handled by handle_export_download() on admin_init.
		// This ensures the file is downloaded before any HTML is rendered.

		// Handle import form submission.
		if ( isset( $_POST['wpinsight_import'] ) && check_admin_referer( 'wpinsight_import' ) ) {
			// Validate file upload.
			if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
				add_settings_error(
					'wpinsight_import_export',
					'no_file',
					__( 'Please select a file to import.', 'cloudfest-wporgdownload' ),
					'error'
				);
			} else {
				$file     = $_FILES['import_file'];
				$tmp_name = $file['tmp_name'];
				$filename = $file['name'];

				// Validate file type.
				$allowed_extensions = array( 'json', 'gz' );
				$file_ext           = pathinfo( $filename, PATHINFO_EXTENSION );

				if ( ! in_array( $file_ext, $allowed_extensions, true ) ) {
					add_settings_error(
						'wpinsight_import_export',
						'invalid_file_type',
						__( 'Invalid file type. Only .json and .json.gz files are allowed.', 'cloudfest-wporgdownload' ),
						'error'
					);
				} else {
					// Validate file size (max 50MB).
					$max_size = 50 * 1024 * 1024; // 50MB in bytes.
					if ( $file['size'] > $max_size ) {
						add_settings_error(
							'wpinsight_import_export',
							'file_too_large',
							sprintf(
								/* translators: %d: maximum file size in MB */
								__( 'File is too large. Maximum size: %d MB.', 'cloudfest-wporgdownload' ),
								50
							),
							'error'
						);
					} else {
						// Prepare import options.
						$options = array(
							'skip_existing'   => isset( $_POST['skip_existing'] ),
							'update_existing' => isset( $_POST['update_existing'] ),
							'dry_run'         => isset( $_POST['dry_run'] ),
						);

						// Perform import.
						$results = WPInsight_Import::import_all( $tmp_name, $options );

						if ( $results['success'] ) {
							$message = sprintf(
								/* translators: 1: imported count, 2: updated count, 3: skipped count, 4: failed count */
								__( 'Import completed: %1$d imported, %2$d updated, %3$d skipped, %4$d failed.', 'cloudfest-wporgdownload' ),
								$results['imported'],
								$results['updated'],
								$results['skipped'],
								$results['failed']
							);

							if ( $options['dry_run'] ) {
								$message .= ' ' . __( '(Dry run - no changes were made)', 'cloudfest-wporgdownload' );
							}

							add_settings_error(
								'wpinsight_import_export',
								'import_success',
								$message,
								$results['failed'] > 0 ? 'warning' : 'success'
							);

							// Show errors if any.
							if ( ! empty( $results['errors'] ) ) {
								foreach ( $results['errors'] as $slug => $error ) {
									add_settings_error(
										'wpinsight_import_export',
										'import_error_' . $slug,
										sprintf(
											/* translators: 1: item slug, 2: error message */
											__( '%1$s: %2$s', 'cloudfest-wporgdownload' ),
											$slug,
											$error
										),
										'warning'
									);
								}
							}
						} else {
							add_settings_error(
								'wpinsight_import_export',
								'import_failed',
								isset( $results['error'] ) ? $results['error'] : __( 'Import failed.', 'cloudfest-wporgdownload' ),
								'error'
							);
						}
					}
				}
			}
		}

		// Get export statistics.
		$stats = WPInsight_Export::get_export_stats();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<p><?php esc_html_e( 'Export and import plugin and theme metadata for backups and migrations. Note: This exports CPT data only, not ZIP files.', 'cloudfest-wporgdownload' ); ?></p>

			<?php settings_errors( 'wpinsight_import_export' ); ?>

			<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
				<!-- Export Section -->
				<div class="card">
					<h2><?php esc_html_e( 'Export Data', 'cloudfest-wporgdownload' ); ?></h2>

					<div style="background: #f0f0f1; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
						<strong><?php esc_html_e( 'Available Data:', 'cloudfest-wporgdownload' ); ?></strong><br />
						<?php
						printf(
							/* translators: 1: plugin count, 2: theme count */
							esc_html__( 'Plugins: %1$s | Themes: %2$s', 'cloudfest-wporgdownload' ),
							'<strong>' . esc_html( number_format_i18n( $stats['plugins'] ) ) . '</strong>',
							'<strong>' . esc_html( number_format_i18n( $stats['themes'] ) ) . '</strong>'
						);
						?>
					</div>

					<form method="post">
						<?php wp_nonce_field( 'wpinsight_export' ); ?>

						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Export Type', 'cloudfest-wporgdownload' ); ?></th>
								<td>
									<fieldset>
										<label><input type="radio" name="export_type" value="all" checked="checked" /> <?php esc_html_e( 'Both (Plugins & Themes)', 'cloudfest-wporgdownload' ); ?></label><br />
										<label><input type="radio" name="export_type" value="plugins" /> <?php esc_html_e( 'Plugins Only', 'cloudfest-wporgdownload' ); ?></label><br />
										<label><input type="radio" name="export_type" value="themes" /> <?php esc_html_e( 'Themes Only', 'cloudfest-wporgdownload' ); ?></label>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Compression', 'cloudfest-wporgdownload' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="compress" value="1" checked="checked" />
										<?php esc_html_e( 'Compress with Gzip (recommended for large exports)', 'cloudfest-wporgdownload' ); ?>
									</label>
								</td>
							</tr>
						</table>

						<p class="submit">
							<button type="submit" name="wpinsight_export" class="button button-primary">
								<?php esc_html_e( 'Export Data', 'cloudfest-wporgdownload' ); ?>
							</button>
						</p>
					</form>
				</div>

				<!-- Import Section -->
				<div class="card">
					<h2><?php esc_html_e( 'Import Data', 'cloudfest-wporgdownload' ); ?></h2>

					<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
						<strong><?php esc_html_e( 'Warning:', 'cloudfest-wporgdownload' ); ?></strong>
						<?php esc_html_e( 'Importing will add or update posts in your database. Use "Dry Run" to preview changes first.', 'cloudfest-wporgdownload' ); ?>
					</div>

					<form method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'wpinsight_import' ); ?>

						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Import File', 'cloudfest-wporgdownload' ); ?></th>
								<td>
									<input type="file" name="import_file" accept=".json,.gz" required />
									<p class="description"><?php esc_html_e( 'Select a .json or .json.gz export file.', 'cloudfest-wporgdownload' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Import Options', 'cloudfest-wporgdownload' ); ?></th>
								<td>
									<fieldset>
										<label><input type="radio" name="duplicate_handling" value="skip" checked="checked" onclick="document.querySelector('input[name=skip_existing]').checked = true; document.querySelector('input[name=update_existing]').checked = false;" /> <?php esc_html_e( 'Skip Existing', 'cloudfest-wporgdownload' ); ?></label><br />
										<label><input type="radio" name="duplicate_handling" value="update" onclick="document.querySelector('input[name=skip_existing]').checked = false; document.querySelector('input[name=update_existing]').checked = true;" /> <?php esc_html_e( 'Update Existing', 'cloudfest-wporgdownload' ); ?></label><br />
										<input type="hidden" name="skip_existing" value="1" />
										<input type="hidden" name="update_existing" value="0" />
									</fieldset>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Preview Mode', 'cloudfest-wporgdownload' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="dry_run" value="1" />
										<?php esc_html_e( 'Dry Run (Preview Only - No Changes)', 'cloudfest-wporgdownload' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Test import without modifying the database.', 'cloudfest-wporgdownload' ); ?></p>
								</td>
							</tr>
						</table>

						<p class="submit">
							<button type="submit" name="wpinsight_import" class="button button-primary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to import this file?', 'cloudfest-wporgdownload' ); ?>');">
								<?php esc_html_e( 'Import Data', 'cloudfest-wporgdownload' ); ?>
							</button>
						</p>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render debug tools section.
	 *
	 * Only shown when WP_DEBUG is enabled. Provides tools for validating
	 * and troubleshooting the plugin state.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_debug_tools(): void {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Debug Tools', 'cloudfest-wporgdownload' ); ?></h2>
			<p><?php esc_html_e( 'Debug tools for developers and troubleshooting.', 'cloudfest-wporgdownload' ); ?></p>

			<?php
			if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
				?>
				<div class="notice notice-info inline">
					<p>
						<strong><?php esc_html_e( 'Note:', 'cloudfest-wporgdownload' ); ?></strong>
						<?php esc_html_e( 'WP_DEBUG is currently disabled. Enable it in wp-config.php to see detailed debug information.', 'cloudfest-wporgdownload' ); ?>
					</p>
				</div>
				<?php
			}
			?>

			<!-- System Information -->
			<h3><?php esc_html_e( 'System Information', 'cloudfest-wporgdownload' ); ?></h3>
			<div style="background: #f6f7f7; padding: 15px; border-left: 3px solid #0073aa; border-radius: 3px; margin-bottom: 20px;">
				<p><strong><?php esc_html_e( 'WordPress Version:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></p>
				<p><strong><?php esc_html_e( 'PHP Version:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></p>
				<p><strong><?php esc_html_e( 'Plugin Version:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( WPINSIGHT_VERSION ); ?></p>
				<p><strong><?php esc_html_e( 'Database Schema:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( WPInsight_DB::get_schema_version() ); ?></p>
				<p><strong><?php esc_html_e( 'WP_DEBUG:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? '✓ ' . esc_html__( 'Enabled', 'cloudfest-wporgdownload' ) : '✗ ' . esc_html__( 'Disabled', 'cloudfest-wporgdownload' ); ?></p>
				<p><strong><?php esc_html_e( 'Action Scheduler:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo function_exists( 'as_next_scheduled_action' ) ? '✓ ' . esc_html__( 'Active', 'cloudfest-wporgdownload' ) : '✗ ' . esc_html__( 'Not Found', 'cloudfest-wporgdownload' ); ?></p>
			</div>

			<!-- Database Information -->
			<h3><?php esc_html_e( 'Database Information', 'cloudfest-wporgdownload' ); ?></h3>
			<?php
			global $wpdb;
			$queue_table     = WPInsight_DB::get_table_name( 'zip_queue' );
			$artifacts_table = WPInsight_DB::get_table_name( 'artifacts' );
			$error_log_table = WPInsight_DB::get_table_name( 'error_log' );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$queue_count     = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $queue_table ) );
			$artifacts_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $artifacts_table ) );
			$error_count     = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $error_log_table ) );
			$plugin_count    = wp_count_posts( WPInsight_CPT::get_plugin_post_type() )->publish ?? 0;
			$theme_count     = wp_count_posts( WPInsight_CPT::get_theme_post_type() )->publish ?? 0;
			// phpcs:enable
			?>
			<div style="background: #f6f7f7; padding: 15px; border-left: 3px solid #0073aa; border-radius: 3px; margin-bottom: 20px;">
				<p><strong><?php esc_html_e( 'Plugin CPTs:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( number_format_i18n( $plugin_count ) ); ?></p>
				<p><strong><?php esc_html_e( 'Theme CPTs:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( number_format_i18n( $theme_count ) ); ?></p>
				<p><strong><?php esc_html_e( 'Queue Jobs:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( number_format_i18n( $queue_count ) ); ?></p>
				<p><strong><?php esc_html_e( 'Downloaded Artifacts:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( number_format_i18n( $artifacts_count ) ); ?></p>
				<p><strong><?php esc_html_e( 'Error Log Entries:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( number_format_i18n( $error_count ) ); ?></p>
			</div>

			<!-- Database Diagnostics -->
			<h3><?php esc_html_e( 'Database Diagnostics', 'cloudfest-wporgdownload' ); ?></h3>
			<p><?php esc_html_e( 'Detailed table statistics, health checks, and optimization tools.', 'cloudfest-wporgdownload' ); ?></p>
			<?php
			$table_stats = WPInsight_DB::get_all_tables_stats();
			?>
			<table class="widefat striped" style="margin-bottom: 20px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Table', 'cloudfest-wporgdownload' ); ?></th>
						<th><?php esc_html_e( 'Rows', 'cloudfest-wporgdownload' ); ?></th>
						<th><?php esc_html_e( 'Data Size', 'cloudfest-wporgdownload' ); ?></th>
						<th><?php esc_html_e( 'Index Size', 'cloudfest-wporgdownload' ); ?></th>
						<th><?php esc_html_e( 'Total Size', 'cloudfest-wporgdownload' ); ?></th>
						<th><?php esc_html_e( 'Engine', 'cloudfest-wporgdownload' ); ?></th>
						<th><?php esc_html_e( 'Last Optimize', 'cloudfest-wporgdownload' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'cloudfest-wporgdownload' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $table_stats as $table_short_name => $stats ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $table_short_name ); ?></strong></td>
							<td><?php echo esc_html( number_format_i18n( $stats['rows'] ) ); ?></td>
							<td><?php echo esc_html( $stats['data_size_formatted'] ); ?></td>
							<td><?php echo esc_html( $stats['index_size_formatted'] ); ?></td>
							<td><strong><?php echo esc_html( $stats['total_size_formatted'] ); ?></strong></td>
							<td><?php echo esc_html( $stats['engine'] ); ?></td>
							<td>
								<?php
								if ( $stats['last_optimize'] ) {
									$time_ago = human_time_diff( strtotime( $stats['last_optimize'] ), time() );
									/* translators: %s: time ago */
									echo esc_html( sprintf( __( '%s ago', 'cloudfest-wporgdownload' ), $time_ago ) );
								} else {
									esc_html_e( 'Never', 'cloudfest-wporgdownload' );
								}
								?>
							</td>
							<td>
								<form method="post" style="display: inline-block; margin-right: 5px;">
									<?php wp_nonce_field( 'wpinsight_db_action', 'wpinsight_db_nonce' ); ?>
									<input type="hidden" name="wpinsight_db_action" value="check_table">
									<input type="hidden" name="table_name" value="<?php echo esc_attr( $table_short_name ); ?>">
									<button type="submit" class="button button-small">
										<?php esc_html_e( 'Check', 'cloudfest-wporgdownload' ); ?>
									</button>
								</form>
								<form method="post" style="display: inline-block; margin-right: 5px;">
									<?php wp_nonce_field( 'wpinsight_db_action', 'wpinsight_db_nonce' ); ?>
									<input type="hidden" name="wpinsight_db_action" value="optimize_table">
									<input type="hidden" name="table_name" value="<?php echo esc_attr( $table_short_name ); ?>">
									<button type="submit" class="button button-small">
										<?php esc_html_e( 'Optimize', 'cloudfest-wporgdownload' ); ?>
									</button>
								</form>
								<form method="post" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to repair this table? Only do this if CHECK indicates corruption.', 'cloudfest-wporgdownload' ); ?>');">
									<?php wp_nonce_field( 'wpinsight_db_action', 'wpinsight_db_nonce' ); ?>
									<input type="hidden" name="wpinsight_db_action" value="repair_table">
									<input type="hidden" name="table_name" value="<?php echo esc_attr( $table_short_name ); ?>">
									<button type="submit" class="button button-small">
										<?php esc_html_e( 'Repair', 'cloudfest-wporgdownload' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="2"><strong><?php esc_html_e( 'Total', 'cloudfest-wporgdownload' ); ?></strong></td>
						<td>
							<?php
							$total_data = array_sum( array_column( $table_stats, 'data_size' ) );
							echo esc_html( size_format( $total_data, 2 ) );
							?>
						</td>
						<td>
							<?php
							$total_index = array_sum( array_column( $table_stats, 'index_size' ) );
							echo esc_html( size_format( $total_index, 2 ) );
							?>
						</td>
						<td>
							<strong>
								<?php
								$total_size = array_sum( array_column( $table_stats, 'total_size' ) );
								echo esc_html( size_format( $total_size, 2 ) );
								?>
							</strong>
						</td>
						<td colspan="3"></td>
					</tr>
				</tfoot>
			</table>

			<div style="background: #f0f6fc; padding: 15px; border-left: 3px solid #0073aa; border-radius: 3px; margin-bottom: 20px;">
				<h4 style="margin-top: 0;"><?php esc_html_e( 'Bulk Actions', 'cloudfest-wporgdownload' ); ?></h4>
				<p><?php esc_html_e( 'Optimize all tables at once. This can take several minutes on large databases.', 'cloudfest-wporgdownload' ); ?></p>
				<form method="post" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to optimize all tables? This may take several minutes.', 'cloudfest-wporgdownload' ); ?>');">
					<?php wp_nonce_field( 'wpinsight_db_action', 'wpinsight_db_nonce' ); ?>
					<input type="hidden" name="wpinsight_db_action" value="optimize_all">
					<button type="submit" class="button button-secondary">
						<?php esc_html_e( 'Optimize All Tables', 'cloudfest-wporgdownload' ); ?>
					</button>
				</form>
			</div>

			<!-- Action Scheduler / Cron Jobs -->
			<h3><?php esc_html_e( 'Scheduled Workers (Cron Jobs)', 'cloudfest-wporgdownload' ); ?></h3>
			<?php
			if ( ! function_exists( 'as_next_scheduled_action' ) ) {
				?>
				<div class="notice notice-error inline">
					<p><?php esc_html_e( 'Action Scheduler is not available. Please install and activate the Action Scheduler plugin.', 'cloudfest-wporgdownload' ); ?></p>
				</div>
				<?php
			} else {
				$workers = array(
					'wpinsight_sync_tick'            => array(
						'name'        => __( 'Sync Worker', 'cloudfest-wporgdownload' ),
						'description' => __( 'Fetches new plugins/themes from WordPress.org API', 'cloudfest-wporgdownload' ),
					),
					'wpinsight_zip_worker_tick'      => array(
						'name'        => __( 'ZIP Worker', 'cloudfest-wporgdownload' ),
						'description' => __( 'Downloads ZIP files from download.wordpress.org', 'cloudfest-wporgdownload' ),
					),
					'wpinsight_size_detection_tick'  => array(
						'name'        => __( 'Size Detection Worker', 'cloudfest-wporgdownload' ),
						'description' => __( 'Detects ZIP file sizes via HEAD requests', 'cloudfest-wporgdownload' ),
					),
				);

				?>
				<table class="widefat striped" style="margin-bottom: 20px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Worker', 'cloudfest-wporgdownload' ); ?></th>
							<th><?php esc_html_e( 'Status', 'cloudfest-wporgdownload' ); ?></th>
							<th><?php esc_html_e( 'Next Run', 'cloudfest-wporgdownload' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'cloudfest-wporgdownload' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $workers as $hook => $worker_data ) : ?>
							<?php
							$next_run     = as_next_scheduled_action( $hook, array(), WPINSIGHT_AS_GROUP );
							$is_scheduled = false !== $next_run;
							$stats        = self::get_action_scheduler_stats( $hook );
							$history      = self::get_action_scheduler_history( $hook, 10 );
							$worker_id    = sanitize_title( $hook );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $worker_data['name'] ); ?></strong>
									<br>
									<span style="color: #646970; font-size: 12px;">
										<?php echo esc_html( $worker_data['description'] ); ?>
									</span>
									<br>
									<button type="button" class="button button-link" onclick="document.getElementById('stats-<?php echo esc_attr( $worker_id ); ?>').style.display = document.getElementById('stats-<?php echo esc_attr( $worker_id ); ?>').style.display === 'none' ? 'table-row' : 'none';">
										<?php esc_html_e( 'Show Stats & History', 'cloudfest-wporgdownload' ); ?> ▼
									</button>
								</td>
								<td>
									<?php if ( $is_scheduled ) : ?>
										<span style="color: #46b450;">✓ <?php esc_html_e( 'Scheduled', 'cloudfest-wporgdownload' ); ?></span>
									<?php else : ?>
										<span style="color: #dc3232;">✗ <?php esc_html_e( 'Not Scheduled', 'cloudfest-wporgdownload' ); ?></span>
									<?php endif; ?>
									<br>
									<small style="color: #646970;">
										<?php
										/* translators: %d: number of completed jobs */
										echo esc_html( sprintf( __( '24h: %d completed', 'cloudfest-wporgdownload' ), $stats['completed'] ) );
										?>
										<?php if ( $stats['failed'] > 0 ) : ?>
											<span style="color: #dc3232;">
												<?php
												/* translators: %d: number of failed jobs */
												echo esc_html( sprintf( __( ', %d failed', 'cloudfest-wporgdownload' ), $stats['failed'] ) );
												?>
											</span>
										<?php endif; ?>
									</small>
								</td>
								<td>
									<?php
									if ( $is_scheduled ) {
										echo esc_html( human_time_diff( $next_run, time() ) );
									} else {
										echo '—';
									}
									?>
									<?php if ( $stats['avg_execution_time'] ) : ?>
										<br>
										<small style="color: #646970;">
											<?php
											/* translators: %s: average execution time */
											echo esc_html( sprintf( __( 'Avg: %ss', 'cloudfest-wporgdownload' ), number_format( $stats['avg_execution_time'], 1 ) ) );
											?>
										</small>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! $is_scheduled ) : ?>
										<form method="post" style="display: inline-block; margin-right: 5px;">
											<?php wp_nonce_field( 'wpinsight_debug_action', 'wpinsight_debug_nonce' ); ?>
											<input type="hidden" name="wpinsight_debug_action" value="schedule_worker">
											<input type="hidden" name="cron_hook" value="<?php echo esc_attr( $hook ); ?>">
											<button type="submit" class="button button-primary button-small">
												<?php esc_html_e( 'Schedule Now', 'cloudfest-wporgdownload' ); ?>
											</button>
										</form>
									<?php endif; ?>
									<form method="post" style="display: inline-block; margin-right: 5px;">
										<?php wp_nonce_field( 'wpinsight_debug_action', 'wpinsight_debug_nonce' ); ?>
										<input type="hidden" name="wpinsight_debug_action" value="run_cron">
										<input type="hidden" name="cron_hook" value="<?php echo esc_attr( $hook ); ?>">
										<button type="submit" class="button button-small">
											<?php esc_html_e( 'Run Now', 'cloudfest-wporgdownload' ); ?>
										</button>
									</form>
								</td>
							</tr>
							<!-- Expandable Stats Row -->
							<tr id="stats-<?php echo esc_attr( $worker_id ); ?>" style="display: none;">
								<td colspan="4" style="background: #f6f7f7; padding: 15px;">
									<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
										<!-- Left Column: Statistics -->
										<div>
											<h4 style="margin-top: 0;"><?php esc_html_e( '24-Hour Statistics', 'cloudfest-wporgdownload' ); ?></h4>
											<table style="width: 100%;">
												<tr>
													<td><strong><?php esc_html_e( 'Completed:', 'cloudfest-wporgdownload' ); ?></strong></td>
													<td style="color: #46b450;"><?php echo esc_html( number_format_i18n( $stats['completed'] ) ); ?></td>
												</tr>
												<tr>
													<td><strong><?php esc_html_e( 'Failed:', 'cloudfest-wporgdownload' ); ?></strong></td>
													<td style="color: <?php echo $stats['failed'] > 0 ? '#dc3232' : '#646970'; ?>;"><?php echo esc_html( number_format_i18n( $stats['failed'] ) ); ?></td>
												</tr>
												<tr>
													<td><strong><?php esc_html_e( 'Pending:', 'cloudfest-wporgdownload' ); ?></strong></td>
													<td><?php echo esc_html( number_format_i18n( $stats['pending'] ) ); ?></td>
												</tr>
												<tr>
													<td><strong><?php esc_html_e( 'In Progress:', 'cloudfest-wporgdownload' ); ?></strong></td>
													<td><?php echo esc_html( number_format_i18n( $stats['in_progress'] ) ); ?></td>
												</tr>
												<?php if ( $stats['avg_execution_time'] ) : ?>
													<tr>
														<td><strong><?php esc_html_e( 'Avg Duration:', 'cloudfest-wporgdownload' ); ?></strong></td>
														<td><?php echo esc_html( number_format( $stats['avg_execution_time'], 2 ) ); ?>s</td>
													</tr>
												<?php endif; ?>
												<?php if ( $stats['last_execution'] ) : ?>
													<tr>
														<td><strong><?php esc_html_e( 'Last Execution:', 'cloudfest-wporgdownload' ); ?></strong></td>
														<td><?php echo esc_html( human_time_diff( strtotime( $stats['last_execution'] ), time() ) ); ?> <?php esc_html_e( 'ago', 'cloudfest-wporgdownload' ); ?></td>
													</tr>
												<?php endif; ?>
											</table>
											<?php if ( $stats['last_error'] ) : ?>
												<div style="margin-top: 10px; padding: 10px; background: #fff; border-left: 3px solid #dc3232;">
													<strong style="color: #dc3232;"><?php esc_html_e( 'Last Error:', 'cloudfest-wporgdownload' ); ?></strong>
													<br>
													<code style="font-size: 11px; word-break: break-all;"><?php echo esc_html( $stats['last_error'] ); ?></code>
												</div>
											<?php endif; ?>
										</div>

										<!-- Right Column: Execution History -->
										<div>
											<h4 style="margin-top: 0;"><?php esc_html_e( 'Recent Executions (Last 10)', 'cloudfest-wporgdownload' ); ?></h4>
											<?php if ( ! empty( $history ) ) : ?>
												<table style="width: 100%; font-size: 12px;">
													<thead>
														<tr style="background: #fff;">
															<th style="text-align: left; padding: 5px;"><?php esc_html_e( 'Status', 'cloudfest-wporgdownload' ); ?></th>
															<th style="text-align: left; padding: 5px;"><?php esc_html_e( 'Completed', 'cloudfest-wporgdownload' ); ?></th>
															<th style="text-align: right; padding: 5px;"><?php esc_html_e( 'Duration', 'cloudfest-wporgdownload' ); ?></th>
														</tr>
													</thead>
													<tbody>
														<?php foreach ( $history as $execution ) : ?>
															<tr style="border-bottom: 1px solid #ddd;">
																<td style="padding: 5px;">
																	<?php if ( 'complete' === $execution['status'] ) : ?>
																		<span style="color: #46b450;">✓ <?php esc_html_e( 'Success', 'cloudfest-wporgdownload' ); ?></span>
																	<?php else : ?>
																		<span style="color: #dc3232;">✗ <?php esc_html_e( 'Failed', 'cloudfest-wporgdownload' ); ?></span>
																	<?php endif; ?>
																</td>
																<td style="padding: 5px;">
																	<?php
																	if ( $execution['completed'] ) {
																		echo esc_html( human_time_diff( strtotime( $execution['completed'] ), time() ) );
																		echo ' ' . esc_html__( 'ago', 'cloudfest-wporgdownload' );
																	} else {
																		echo '—';
																	}
																	?>
																</td>
																<td style="padding: 5px; text-align: right;">
																	<?php
																	if ( $execution['duration'] !== null ) {
																		echo esc_html( number_format( $execution['duration'], 1 ) ) . 's';
																	} else {
																		echo '—';
																	}
																	?>
																</td>
															</tr>
														<?php endforeach; ?>
													</tbody>
												</table>
											<?php else : ?>
												<p style="color: #646970; font-style: italic;"><?php esc_html_e( 'No execution history available.', 'cloudfest-wporgdownload' ); ?></p>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Cron Reset/Reinstall -->
				<div style="background: #fff3cd; padding: 15px; border-left: 3px solid #ffc107; border-radius: 3px; margin-top: 20px;">
					<h4 style="margin-top: 0;"><?php esc_html_e( 'Reset Scheduled Workers', 'cloudfest-wporgdownload' ); ?></h4>
					<p><?php esc_html_e( 'If workers are not running correctly, you can reset all scheduled actions. This will clear existing schedules and recreate them.', 'cloudfest-wporgdownload' ); ?></p>
					<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to reset all scheduled workers? This will clear and recreate all Action Scheduler jobs.', 'cloudfest-wporgdownload' ) ); ?>');">
						<?php wp_nonce_field( 'wpinsight_debug_action', 'wpinsight_debug_nonce' ); ?>
						<input type="hidden" name="wpinsight_debug_action" value="reset_crons">
						<button type="submit" class="button button-secondary">
							<?php esc_html_e( 'Reset All Workers', 'cloudfest-wporgdownload' ); ?>
						</button>
					</form>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render settings page.
	 *
	 * Displays the main settings page with all sections and fields.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_settings_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'cloudfest-wporgdownload' ) );
		}

		// Prepare template variables.
		$admin              = __CLASS__;
		$settings_group     = self::SETTINGS_GROUP;
		$settings_page_slug = self::SETTINGS_PAGE_SLUG;

		// Get health check data for settings page (with fallback to prevent errors).
		try {
			$system_health  = self::get_system_health_data();
			$overall_health = self::get_overall_health_status( $system_health );
			$api_health     = self::get_api_health_data();
			$size_stats     = WPInsight_Zip_Queue::get_size_statistics();
		} catch ( \Exception $e ) {
			// If health check data fails, provide empty arrays to prevent template errors.
			$system_health  = array();
			$overall_health = array( 'status' => 'error', 'message' => 'Health check failed', 'error_count' => 0, 'warning_count' => 0 );
			$api_health     = array();
			$size_stats     = array(
				'plugins' => array( 'detection_progress' => 0, 'downloaded_size' => 0, 'pending_size' => 0, 'total_size' => 0, 'count_with_size' => 0, 'total_count' => 0 ),
				'themes'  => array( 'detection_progress' => 0, 'downloaded_size' => 0, 'pending_size' => 0, 'total_size' => 0, 'count_with_size' => 0, 'total_count' => 0 ),
			);
			error_log( 'WPInsight health check error: ' . $e->getMessage() );
		}

		// Load template.
		require WPINSIGHT_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	/**
	 * Render general section description.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_section_general(): void {
		echo '<p>';
		esc_html_e( 'Configure general plugin behavior and data management.', 'cloudfest-wporgdownload' );
		echo '</p>';
	}

	/**
	 * Render sync settings section description.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_section_sync(): void {
		echo '<p>';
		esc_html_e( 'Configure synchronization behavior with WordPress.org repositories.', 'cloudfest-wporgdownload' );
		echo '</p>';
	}

	/**
	 * Render rate limiting section description.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_section_rate_limiting(): void {
		echo '<p>';
		echo '<strong>';
		esc_html_e( 'CRITICAL:', 'cloudfest-wporgdownload' );
		echo '</strong> ';
		esc_html_e( 'These settings control rate limiting to prevent being banned by WordPress.org. Do not exceed recommended values.', 'cloudfest-wporgdownload' );
		echo '</p>';
	}

	/**
	 * Render Error Notifications section description.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function render_section_notifications(): void {
		echo '<p>';
		esc_html_e( 'Configure error logging and email notifications for critical errors.', 'cloudfest-wporgdownload' );
		echo '</p>';
	}

	/**
	 * Render debug tools section description.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	/**
	 * Render delete_on_uninstall field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_delete_on_uninstall(): void {
		$value = WPInsight_Settings::get( 'delete_on_uninstall', false );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[delete_on_uninstall]' ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Delete all plugin data when uninstalling', 'cloudfest-wporgdownload' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'By default, data is preserved when uninstalling. Enable this to delete all posts, tables, and files on uninstall.', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render auto_sync_enabled field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_auto_sync_enabled(): void {
		$value = WPInsight_Settings::get( 'auto_sync_enabled', true );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[auto_sync_enabled]' ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Enable automatic background synchronization', 'cloudfest-wporgdownload' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, the plugin will automatically sync with WordPress.org repositories in the background.', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render sync_plugins_enabled field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_sync_plugins_enabled(): void {
		$value = WPInsight_Settings::get( 'sync_plugins_enabled', true );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[sync_plugins_enabled]' ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Sync WordPress.org plugins', 'cloudfest-wporgdownload' ); ?>
		</label>
		<?php
	}

	/**
	 * Render sync_themes_enabled field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_sync_themes_enabled(): void {
		$value = WPInsight_Settings::get( 'sync_themes_enabled', true );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[sync_themes_enabled]' ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Sync WordPress.org themes', 'cloudfest-wporgdownload' ); ?>
		</label>
		<?php
	}

	/**
	 * Render download_plugin_zips_enabled field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_download_plugin_zips_enabled(): void {
		$value = WPInsight_Settings::get( 'download_plugin_zips_enabled', true );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[download_plugin_zips_enabled]' ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Download plugin ZIP files (requires Sync Plugins enabled)', 'cloudfest-wporgdownload' ); ?>
		</label>
		<?php
	}

	/**
	 * Render download_theme_zips_enabled field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_download_theme_zips_enabled(): void {
		$value = WPInsight_Settings::get( 'download_theme_zips_enabled', true );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[download_theme_zips_enabled]' ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Download theme ZIP files (requires Sync Themes enabled)', 'cloudfest-wporgdownload' ); ?>
		</label>
		<?php
	}

	/**
	 * Render sync_interval field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_sync_interval(): void {
		$value = WPInsight_Settings::get( 'sync_interval', 300 );
		?>
		<input type="number" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[sync_interval]' ); ?>" value="<?php echo esc_attr( $value ); ?>" min="60" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'How often to check for new plugins/themes (minimum 60 seconds). Default: 300 (5 minutes).', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render max_concurrent_downloads field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_max_concurrent_downloads(): void {
		$value = WPInsight_Settings::get( 'max_concurrent_downloads', 3 );
		?>
		<input type="number" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[max_concurrent_downloads]' ); ?>" value="<?php echo esc_attr( $value ); ?>" min="1" max="5" step="1" class="small-text" />
		<p class="description">
			<strong><?php esc_html_e( 'CRITICAL:', 'cloudfest-wporgdownload' ); ?></strong>
			<?php esc_html_e( 'Maximum concurrent downloads (1-5). Exceeding 5 may result in IP ban from WordPress.org. Default: 3 (recommended).', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render max_size_detection_rate field.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public static function render_field_max_size_detection_rate(): void {
		$value = WPInsight_Settings::get( 'max_size_detection_rate', 3 );
		?>
		<input type="number" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[max_size_detection_rate]' ); ?>" value="<?php echo esc_attr( $value ); ?>" min="1" max="10" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'HEAD requests per second for size detection (1-10). Lower values are more polite to WordPress.org servers. Default: 3 (recommended).', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render zip_worker_interval field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_zip_worker_interval(): void {
		$value = WPInsight_Settings::get( 'zip_worker_interval', 60 );
		?>
		<input type="number" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[zip_worker_interval]' ); ?>" value="<?php echo esc_attr( $value ); ?>" min="30" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'How often to process download queue (minimum 30 seconds). Default: 60 (1 minute).', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render max_retries field.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render_field_max_retries(): void {
		$value = WPInsight_Settings::get( 'max_retries', 3 );
		?>
		<input type="number" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[max_retries]' ); ?>" value="<?php echo esc_attr( $value ); ?>" min="1" max="10" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Maximum retry attempts for failed downloads (1-10). Default: 3.', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render admin_email_notifications_enabled field.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function render_field_admin_email_notifications_enabled(): void {
		$value = WPInsight_Settings::get( 'admin_email_notifications_enabled', false );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[admin_email_notifications_enabled]' ); ?>" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Send email notifications for critical errors', 'cloudfest-wporgdownload' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'When enabled, you will receive email notifications for EMERGENCY and ERROR level events (rate-limited to 1 per hour per error type).', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render admin_notification_email field.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function render_field_admin_notification_email(): void {
		$value = WPInsight_Settings::get( 'admin_notification_email', get_option( 'admin_email' ) );
		?>
		<input type="email" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[admin_notification_email]' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Email address to receive error notifications. Default: site admin email.', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Render log_retention_days field.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function render_field_log_retention_days(): void {
		$value = WPInsight_Settings::get( 'log_retention_days', 30 );
		?>
		<input type="number" name="<?php echo esc_attr( WPINSIGHT_SETTINGS_OPTION . '[log_retention_days]' ); ?>" value="<?php echo esc_attr( $value ); ?>" min="1" max="365" step="1" class="small-text" />
		<p class="description">
			<?php esc_html_e( 'Number of days to retain error logs before automatic deletion (1-365). Default: 30.', 'cloudfest-wporgdownload' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * Validates and sanitizes all settings using the Settings class validation.
	 * Invalid values will be rejected and the field will keep its previous value.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $input Raw input from settings form.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public static function sanitize_settings( array $input ): array {
		$sanitized = array();

		// Convert checkbox values: empty strings become false, '1' becomes true.
		$checkboxes = array(
			'delete_on_uninstall',
			'auto_sync_enabled',
			'sync_plugins_enabled',
			'sync_themes_enabled',
			'download_plugin_zips_enabled',
			'download_theme_zips_enabled',
			'admin_email_notifications_enabled', // v1.1.0+.
		);
		foreach ( $checkboxes as $key ) {
			$sanitized[ $key ] = isset( $input[ $key ] ) && '1' === $input[ $key ];
		}

		// Validate and sanitize number fields using Settings validation only.
		// IMPORTANT: Do NOT call WPInsight_Settings::update() here as it causes infinite loop.
		$number_fields = array(
			'max_concurrent_downloads',
			'max_size_detection_rate',
			'sync_interval',
			'zip_worker_interval',
			'max_retries',
			'log_retention_days',
		);

		foreach ( $number_fields as $key ) {
			if ( isset( $input[ $key ] ) ) {
				// Cast to int and validate directly without calling update().
				$value = (int) $input[ $key ];

				// Simple inline validation (Settings::validate() does more, but we can't call update()).
				$is_valid = false;

				switch ( $key ) {
					case 'max_concurrent_downloads':
						$is_valid = ( $value >= 1 && $value <= 5 );
						break;
					case 'max_size_detection_rate':
						$is_valid = ( $value >= 1 && $value <= 10 );
						break;
					case 'sync_interval':
					case 'zip_worker_interval':
						$is_valid = ( $value >= 60 );
						break;
					case 'max_retries':
						$is_valid = ( $value >= 1 && $value <= 10 );
						break;
					case 'log_retention_days':
						$is_valid = ( $value >= 1 && $value <= 365 );
						break;
					default:
						$is_valid = true;
				}

				if ( $is_valid ) {
					$sanitized[ $key ] = $value;
				} else {
					// Validation failed, keep current value.
					$sanitized[ $key ] = WPInsight_Settings::get( $key );
					add_settings_error(
						WPINSIGHT_SETTINGS_OPTION,
						'invalid_' . $key,
						sprintf(
							/* translators: %s: Setting name */
							__( 'Invalid value for %s. Previous value restored.', 'cloudfest-wporgdownload' ),
							$key
						)
					);
				}
			}
		}

		// Sanitize email field (v1.1.0+).
		if ( isset( $input['admin_notification_email'] ) ) {
			$email = sanitize_email( $input['admin_notification_email'] );
			if ( is_email( $email ) ) {
				$sanitized['admin_notification_email'] = $email;
			} else {
				$sanitized['admin_notification_email'] = get_option( 'admin_email' );
			}
		}

		return $sanitized;
	}

	/**
	 * Get total size of directory recursively.
	 *
	 * Uses multi-tier fallback strategy for memory safety (v1.1.0+):
	 * 1. Check cache (5-minute TTL)
	 * 2. If memory available: Use RecursiveIterator
	 * 3. If low memory: Use exec('du -sb')
	 * 4. If exec disabled: Estimate from database
	 *
	 * @since 0.1.0
	 * @param string $path Directory path.
	 * @return int Total size in bytes.
	 */
	private static function get_directory_size( string $path ): int {
		if ( ! is_dir( $path ) ) {
			return 0;
		}

		// Try cache first (5-minute TTL).
		$cached = self::get_directory_size_cached( $path );
		if ( false !== $cached ) {
			return $cached;
		}

		// Check if we have enough memory for RecursiveIterator.
		if ( self::check_memory_available( 64 ) ) {
			// Method 1: RecursiveIterator (most accurate).
			$size = 0;

			try {
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS )
				);

				foreach ( $files as $file ) {
					if ( $file->isFile() ) {
						$size += $file->getSize();
					}
				}

				// Cache the result.
				set_transient( 'wpinsight_storage_size_' . md5( $path ), $size, 300 );

				return $size;
			} catch ( Exception $e ) {
				WPInsight_Logger::warning(
					'RecursiveIterator failed for directory size calculation',
					array(
						'path'  => $path,
						'error' => $e->getMessage(),
					)
				);
			}
		}

		// Method 2: Fallback to exec('du') if available.
		$size = self::get_directory_size_exec( $path );
		if ( false !== $size ) {
			// Cache the result.
			set_transient( 'wpinsight_storage_size_' . md5( $path ), $size, 300 );
			return $size;
		}

		// Method 3: Final fallback - estimate from database artifacts table.
		$size = self::estimate_size_from_artifacts();

		// Cache the result.
		set_transient( 'wpinsight_storage_size_' . md5( $path ), $size, 300 );

		return $size;
	}

	/**
	 * Check if sufficient memory is available.
	 *
	 * @since 1.1.0
	 * @param int $required_mb Required memory in megabytes.
	 * @return bool True if memory is available.
	 */
	private static function check_memory_available( int $required_mb ): bool {
		$memory_limit = ini_get( 'memory_limit' );

		// Handle -1 (unlimited).
		if ( '-1' === $memory_limit ) {
			return true;
		}

		// Parse memory limit.
		$limit_mb = (int) $memory_limit;

		// Get current usage.
		$current_mb = memory_get_usage( true ) / ( 1024 * 1024 );

		// Check if we have required headroom.
		return ( $limit_mb - $current_mb ) >= $required_mb;
	}

	/**
	 * Get cached directory size.
	 *
	 * @since 1.1.0
	 * @param string $path Directory path.
	 * @return int|false Size in bytes or false if cache miss.
	 */
	private static function get_directory_size_cached( string $path ) {
		return get_transient( 'wpinsight_storage_size_' . md5( $path ) );
	}

	/**
	 * Get directory size using exec('du') command.
	 *
	 * @since 1.1.0
	 * @param string $path Directory path.
	 * @return int|false Size in bytes or false on failure.
	 */
	private static function get_directory_size_exec( string $path ) {
		// Check if exec is available.
		if ( ! function_exists( 'exec' ) ) {
			return false;
		}

		// Check if exec is disabled.
		$disabled = ini_get( 'disable_functions' );
		if ( $disabled && false !== strpos( $disabled, 'exec' ) ) {
			return false;
		}

		// Sanitize path and execute du command.
		$escaped_path = escapeshellarg( $path );
		$output       = array();
		$return_var   = 0;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec( "du -sb {$escaped_path} 2>/dev/null", $output, $return_var );

		if ( 0 === $return_var && ! empty( $output[0] ) ) {
			// Parse output: "12345\t/path/to/dir".
			$parts = preg_split( '/\s+/', $output[0], 2 );
			if ( ! empty( $parts[0] ) && is_numeric( $parts[0] ) ) {
				return (int) $parts[0];
			}
		}

		return false;
	}

	/**
	 * Estimate directory size from database artifacts table.
	 *
	 * @since 1.1.0
	 * @return int Estimated size in bytes.
	 */
	private static function estimate_size_from_artifacts(): int {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'artifacts' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var(
			$wpdb->prepare( 'SELECT SUM(file_size) FROM %i', $table )
		);

		return is_numeric( $total ) ? (int) $total : 0;
	}

	/**
	 * Get count of critical errors in last hour.
	 *
	 * Returns the count of EMERGENCY and ERROR severity logs from the last hour.
	 * Used for displaying error badges in admin menu.
	 *
	 * @since 1.1.0
	 * @return int Number of critical errors.
	 */
	private static function get_critical_error_count(): int {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'error_log' );

		// Safety check: Verify table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $table_exists ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i
				WHERE severity IN (%s, %s)
				AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
				$table,
				'emergency',
				'error'
			)
		);

		return is_numeric( $count ) ? (int) $count : 0;
	}

	/**
	 * Format large numbers with K/M/B abbreviations.
	 *
	 * Converts large numbers to human-readable format:
	 * - 1234 → "1.2K"
	 * - 1234567 → "1.2M"
	 * - 1234567890 → "1.2B"
	 *
	 * @since 1.1.0
	 * @param int $number Number to format.
	 * @return string Formatted number with abbreviation.
	 */
	public static function format_number_abbreviated( int $number ): string {
		if ( $number < 1000 ) {
			return (string) number_format_i18n( $number );
		}

		if ( $number < 1000000 ) {
			return number_format_i18n( $number / 1000, 1 ) . 'K';
		}

		if ( $number < 1000000000 ) {
			return number_format_i18n( $number / 1000000, 1 ) . 'M';
		}

		return number_format_i18n( $number / 1000000000, 1 ) . 'B';
	}

	/**
	 * Render progress bar HTML for sync status.
	 *
	 * Displays a visual progress bar showing sync completion percentage.
	 *
	 * @since 1.1.0
	 * @param array<string, mixed> $sync_state Sync state array with page, total_pages.
	 * @return string Progress bar HTML.
	 */
	public static function render_progress_bar( array $sync_state ): string {
		if ( empty( $sync_state['total_pages'] ) || $sync_state['total_pages'] <= 0 ) {
			return '';
		}

		$current = isset( $sync_state['page'] ) ? (int) $sync_state['page'] : 0;
		$total   = (int) $sync_state['total_pages'];
		$percent = $total > 0 ? min( 100, round( ( $current / $total ) * 100 ) ) : 0;

		// Color based on progress.
		$color = '#00a32a'; // Green.
		if ( $percent < 30 ) {
			$color = '#d63638'; // Red.
		} elseif ( $percent < 70 ) {
			$color = '#dba617'; // Orange.
		}

		$html  = '<div style="background: #f0f0f1; border-radius: 3px; height: 20px; margin: 5px 0; overflow: hidden;">';
		$html .= sprintf(
			'<div style="background: %s; height: 100%%; width: %d%%; transition: width 0.3s ease;"></div>',
			esc_attr( $color ),
			$percent
		);
		$html .= '</div>';
		$html .= sprintf(
			'<div style="font-size: 11px; color: #646970;">%d%% complete (%s / %s pages)</div>',
			$percent,
			esc_html( number_format_i18n( $current ) ),
			esc_html( number_format_i18n( $total ) )
		);

		return $html;
	}

	/**
	 * Get Action Scheduler statistics for a specific hook in the last 24 hours.
	 *
	 * Returns execution statistics including completed, failed, and average execution time.
	 *
	 * @since 1.5.0
	 * @param string $hook The Action Scheduler hook name.
	 * @return array{
	 *     completed: int,
	 *     failed: int,
	 *     pending: int,
	 *     in_progress: int,
	 *     avg_execution_time: float|null,
	 *     last_execution: string|null,
	 *     last_error: string|null
	 * } Statistics array.
	 */
	private static function get_action_scheduler_stats( string $hook ): array {
		global $wpdb;

		$stats = array(
			'completed'          => 0,
			'failed'             => 0,
			'pending'            => 0,
			'in_progress'        => 0,
			'avg_execution_time' => null,
			'last_execution'     => null,
			'last_error'         => null,
		);

		// Check if Action Scheduler tables exist.
		$actions_table = $wpdb->prefix . 'actionscheduler_actions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $actions_table ) );

		if ( ! $table_exists ) {
			return $stats;
		}

		// Get 24h statistics.
		$twentyfour_hours_ago = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );

		// Count by status (last 24h).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) as count
				FROM {$actions_table}
				WHERE hook = %s
				AND last_attempt_gmt > %s
				AND group_id = (
					SELECT group_id FROM {$wpdb->prefix}actionscheduler_groups
					WHERE slug = %s
					LIMIT 1
				)
				GROUP BY status",
				$hook,
				$twentyfour_hours_ago,
				WPINSIGHT_AS_GROUP
			),
			ARRAY_A
		);

		foreach ( $counts as $row ) {
			$status = $row['status'];
			$count  = (int) $row['count'];

			if ( 'complete' === $status ) {
				$stats['completed'] = $count;
			} elseif ( 'failed' === $status ) {
				$stats['failed'] = $count;
			} elseif ( 'pending' === $status ) {
				$stats['pending'] = $count;
			} elseif ( 'in-progress' === $status ) {
				$stats['in_progress'] = $count;
			}
		}

		// Get average execution time (completed actions only, last 24h).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$avg_time = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(TIMESTAMPDIFF(SECOND, last_attempt_gmt, last_attempt_gmt))
				FROM {$actions_table}
				WHERE hook = %s
				AND status = 'complete'
				AND last_attempt_gmt > %s
				AND group_id = (
					SELECT group_id FROM {$wpdb->prefix}actionscheduler_groups
					WHERE slug = %s
					LIMIT 1
				)",
				$hook,
				$twentyfour_hours_ago,
				WPINSIGHT_AS_GROUP
			)
		);

		if ( $avg_time ) {
			$stats['avg_execution_time'] = (float) $avg_time;
		}

		// Get last execution time.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_execution = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT last_attempt_gmt
				FROM {$actions_table}
				WHERE hook = %s
				AND status = 'complete'
				AND group_id = (
					SELECT group_id FROM {$wpdb->prefix}actionscheduler_groups
					WHERE slug = %s
					LIMIT 1
				)
				ORDER BY last_attempt_gmt DESC
				LIMIT 1",
				$hook,
				WPINSIGHT_AS_GROUP
			)
		);

		if ( $last_execution ) {
			$stats['last_execution'] = $last_execution;
		}

		// Get last error message.
		$logs_table = $wpdb->prefix . 'actionscheduler_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$last_error = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT l.message
				FROM {$logs_table} l
				INNER JOIN {$actions_table} a ON l.action_id = a.action_id
				WHERE a.hook = %s
				AND a.status = 'failed'
				AND a.group_id = (
					SELECT group_id FROM {$wpdb->prefix}actionscheduler_groups
					WHERE slug = %s
					LIMIT 1
				)
				ORDER BY l.log_date_gmt DESC
				LIMIT 1",
				$hook,
				WPINSIGHT_AS_GROUP
			)
		);

		if ( $last_error ) {
			$stats['last_error'] = $last_error;
		}

		return $stats;
	}

	/**
	 * Get recent Action Scheduler execution history for a hook.
	 *
	 * Returns last 10 executions with timestamps and status.
	 *
	 * @since 1.5.0
	 * @param string $hook The Action Scheduler hook name.
	 * @param int    $limit Number of executions to retrieve (default: 10).
	 * @return array<int, array{
	 *     status: string,
	 *     started: string,
	 *     completed: string|null,
	 *     duration: float|null
	 * }> Array of execution history.
	 */
	private static function get_action_scheduler_history( string $hook, int $limit = 10 ): array {
		global $wpdb;

		$actions_table = $wpdb->prefix . 'actionscheduler_actions';

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $actions_table ) );

		if ( ! $table_exists ) {
			return array();
		}

		// Get recent executions.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					status,
					scheduled_date_gmt as started,
					last_attempt_gmt as completed
				FROM {$actions_table}
				WHERE hook = %s
				AND group_id = (
					SELECT group_id FROM {$wpdb->prefix}actionscheduler_groups
					WHERE slug = %s
					LIMIT 1
				)
				AND status IN ('complete', 'failed')
				ORDER BY last_attempt_gmt DESC
				LIMIT %d",
				$hook,
				WPINSIGHT_AS_GROUP,
				$limit
			),
			ARRAY_A
		);

		$history = array();
		foreach ( $results as $row ) {
			$started   = $row['started'];
			$completed = $row['completed'];
			$duration  = null;

			if ( $started && $completed ) {
				$start_time = strtotime( $started );
				$end_time   = strtotime( $completed );
				$duration   = $end_time - $start_time;
			}

			$history[] = array(
				'status'    => $row['status'],
				'started'   => $started,
				'completed' => $completed,
				'duration'  => $duration,
			);
		}

		return $history;
	}

	/**
	 * Get comprehensive sync progress data for dashboard.
	 *
	 * Returns progress information, ETA, status, and error count for sync operations.
	 *
	 * @since 1.5.0
	 * @param string $entity_type Entity type ('plugin' or 'theme').
	 * @return array{
	 *     total_items: int,
	 *     synced_items: int,
	 *     current_page: int,
	 *     total_pages: int,
	 *     progress_percent: float,
	 *     status: string,
	 *     status_label: string,
	 *     status_color: string,
	 *     eta_seconds: int|null,
	 *     eta_formatted: string|null,
	 *     error_count: int,
	 *     last_sync: string|null
	 * } Progress data array.
	 */
	private static function get_sync_progress_data( string $entity_type ): array {
		$state = WPInsight_Sync::get_sync_state( $entity_type );

		// Get total count from WordPress.org API (estimated)
		$total_estimated = 'plugin' === $entity_type ? 60000 : 12000;

		// Get current synced count from CPTs
		$cpt_type     = 'plugin' === $entity_type ? WPInsight_CPT::get_plugin_post_type() : WPInsight_CPT::get_theme_post_type();
		$synced_count = wp_count_posts( $cpt_type )->publish ?? 0;

		// Calculate progress
		$progress_percent = $total_estimated > 0 ? ( $synced_count / $total_estimated ) * 100 : 0;
		$progress_percent = min( $progress_percent, 100 ); // Cap at 100%

		// Determine status and styling
		$status       = $state['status'] ?? 'idle';
		$status_label = '';
		$status_color = '#646970';

		switch ( $status ) {
			case 'running':
				$status_label = __( 'Running', 'cloudfest-wporgdownload' );
				$status_color = '#00a32a'; // Green
				break;
			case 'queued':
				$status_label = __( 'Queued', 'cloudfest-wporgdownload' );
				$status_color = '#2271b1'; // Blue
				break;
			case 'completed':
				$status_label = __( 'Completed', 'cloudfest-wporgdownload' );
				$status_color = '#00a32a'; // Green
				break;
			case 'error':
				$status_label = __( 'Error', 'cloudfest-wporgdownload' );
				$status_color = '#d63638'; // Red
				break;
			case 'paused':
				$status_label = __( 'Paused', 'cloudfest-wporgdownload' );
				$status_color = '#dba617'; // Orange
				break;
			default:
				$status_label = __( 'Idle', 'cloudfest-wporgdownload' );
				$status_color = '#646970'; // Gray
				break;
		}

		// Calculate ETA
		$eta_seconds   = null;
		$eta_formatted = null;

		if ( 'running' === $status || 'queued' === $status ) {
			$eta_data = self::calculate_sync_eta( $entity_type, $synced_count, $total_estimated );
			$eta_seconds   = $eta_data['seconds'];
			$eta_formatted = $eta_data['formatted'];
		}

		// Get error count from sync state
		$error_count = 0;
		if ( isset( $state['last_error'] ) && ! empty( $state['last_error'] ) ) {
			$error_count = 1; // At least one error

			// Try to get actual error count from error log
			global $wpdb;
			$logs_table = WPInsight_DB::get_table_name( 'error_log' );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$error_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM %i
					WHERE severity IN ('ERROR', 'EMERGENCY')
					AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
					AND (message LIKE %s OR context LIKE %s)",
					$logs_table,
					'%' . $wpdb->esc_like( $entity_type ) . '%',
					'%' . $wpdb->esc_like( $entity_type ) . '%'
				)
			);
		}

		// Get last sync time
		$last_sync = null;
		if ( isset( $state['last_run_at'] ) && ! empty( $state['last_run_at'] ) ) {
			$last_sync = human_time_diff( strtotime( $state['last_run_at'] ), time() ) . ' ' . __( 'ago', 'cloudfest-wporgdownload' );
		}

		return array(
			'total_items'      => $total_estimated,
			'synced_items'     => $synced_count,
			'current_page'     => $state['current_page'] ?? 0,
			'total_pages'      => $state['total_pages'] ?? 0,
			'progress_percent' => round( $progress_percent, 1 ),
			'status'           => $status,
			'status_label'     => $status_label,
			'status_color'     => $status_color,
			'eta_seconds'      => $eta_seconds,
			'eta_formatted'    => $eta_formatted,
			'error_count'      => (int) $error_count,
			'last_sync'        => $last_sync,
		);
	}

	/**
	 * Calculate ETA for sync completion.
	 *
	 * Estimates time remaining based on current progress and recent execution speed.
	 *
	 * @since 1.5.0
	 * @param string $entity_type Entity type ('plugin' or 'theme').
	 * @param int    $current Current number of synced items.
	 * @param int    $total Total number of items to sync.
	 * @return array{
	 *     seconds: int|null,
	 *     formatted: string|null
	 * } ETA data.
	 */
	private static function calculate_sync_eta( string $entity_type, int $current, int $total ): array {
		if ( $current >= $total || $current === 0 ) {
			return array(
				'seconds'   => null,
				'formatted' => null,
			);
		}

		// Get average execution time from Action Scheduler
		$hook  = 'wpinsight_sync_tick';
		$stats = self::get_action_scheduler_stats( $hook );

		$avg_time_per_execution = $stats['avg_execution_time'] ?? 3; // Default 3 seconds

		// Get items per execution (from settings)
		$per_page = WPInsight_Settings::get( 'per_page', 250 );

		// Calculate remaining items and executions
		$remaining_items      = $total - $current;
		$remaining_executions = ceil( $remaining_items / $per_page );

		// Calculate ETA in seconds
		$eta_seconds = $remaining_executions * $avg_time_per_execution;

		// Add interval between executions (default: 5 minutes = 300 seconds)
		$sync_interval = WPInsight_Settings::get( 'sync_interval', 300 );
		$eta_seconds  += ( $remaining_executions - 1 ) * $sync_interval;

		// Format ETA
		$eta_formatted = self::format_eta( $eta_seconds );

		return array(
			'seconds'   => (int) $eta_seconds,
			'formatted' => $eta_formatted,
		);
	}

	/**
	 * Format ETA seconds into human-readable string.
	 *
	 * @since 1.5.0
	 * @param int $seconds Number of seconds.
	 * @return string Formatted ETA string.
	 */
	private static function format_eta( int $seconds ): string {
		if ( $seconds < 60 ) {
			/* translators: %d: number of seconds */
			return sprintf( __( '%d seconds', 'cloudfest-wporgdownload' ), $seconds );
		}

		if ( $seconds < 3600 ) {
			$minutes = floor( $seconds / 60 );
			/* translators: %d: number of minutes */
			return sprintf( __( '%d minutes', 'cloudfest-wporgdownload' ), $minutes );
		}

		if ( $seconds < 86400 ) {
			$hours   = floor( $seconds / 3600 );
			$minutes = floor( ( $seconds % 3600 ) / 60 );

			if ( $minutes > 0 ) {
				/* translators: 1: number of hours, 2: number of minutes */
				return sprintf( __( '%1$d hours %2$d minutes', 'cloudfest-wporgdownload' ), $hours, $minutes );
			}

			/* translators: %d: number of hours */
			return sprintf( __( '%d hours', 'cloudfest-wporgdownload' ), $hours );
		}

		$days  = floor( $seconds / 86400 );
		$hours = floor( ( $seconds % 86400 ) / 3600 );

		if ( $hours > 0 ) {
			/* translators: 1: number of days, 2: number of hours */
			return sprintf( __( '%1$d days %2$d hours', 'cloudfest-wporgdownload' ), $days, $hours );
		}

		/* translators: %d: number of days */
		return sprintf( __( '%d days', 'cloudfest-wporgdownload' ), $days );
	}

	/**
	 * Get active downloads.
	 *
	 * Returns currently processing downloads with progress information.
	 *
	 * @since 1.5.0
	 * @return array<int, array{
	 *     id: int,
	 *     slug: string,
	 *     version: string,
	 *     item_type: string,
	 *     remote_filesize: int|null,
	 *     started_at: string,
	 *     elapsed_seconds: int,
	 *     estimated_speed: float|null,
	 *     progress_percent: float|null
	 * }> Active downloads data.
	 */
	private static function get_active_downloads(): array {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'zip_queue' );

		// Get currently processing downloads (last 10 minutes).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$downloads = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, slug, version, item_type, remote_filesize, started_at
				FROM %i
				WHERE status = 'processing'
				AND started_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
				ORDER BY started_at DESC
				LIMIT 10",
				$table
			),
			ARRAY_A
		);

		if ( ! $downloads ) {
			return array();
		}

		// Calculate progress info for each download.
		$now = time();
		foreach ( $downloads as &$download ) {
			$started_time = strtotime( $download['started_at'] );
			$elapsed      = $now - $started_time;

			$download['elapsed_seconds'] = $elapsed;

			// If we have remote filesize, estimate speed and progress.
			if ( ! empty( $download['remote_filesize'] ) && $elapsed > 0 ) {
				// Estimate download speed (conservative: assume 50% downloaded at this point).
				$estimated_bytes_downloaded = $download['remote_filesize'] * 0.5;
				$download['estimated_speed'] = $estimated_bytes_downloaded / $elapsed; // Bytes per second.

				// Estimate progress (50% if still processing).
				$download['progress_percent'] = 50.0;
			} else {
				$download['estimated_speed']  = null;
				$download['progress_percent'] = null;
			}
		}

		return $downloads;
	}

	/**
	 * Get disk space information.
	 *
	 * Returns disk space statistics for the uploads directory.
	 *
	 * @since 1.5.0
	 * @return array{
	 *     total_space: int,
	 *     free_space: int,
	 *     used_space: int,
	 *     used_percent: float,
	 *     artifacts_size: int,
	 *     pending_size: int,
	 *     total_required: int
	 * } Disk space data.
	 */
	private static function get_disk_space_info(): array {
		$upload_dir = wp_upload_dir();
		$base_path  = $upload_dir['basedir'];

		// Get disk space statistics.
		$total_space = disk_total_space( $base_path );
		$free_space  = disk_free_space( $base_path );

		// Calculate used space.
		$used_space    = $total_space - $free_space;
		$used_percent  = $total_space > 0 ? ( $used_space / $total_space ) * 100 : 0;

		// Get artifacts size from database.
		global $wpdb;
		$artifacts_table = WPInsight_DB::get_table_name( 'artifacts' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$artifacts_size = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(SUM(file_size), 0) FROM %i',
				$artifacts_table
			)
		);

		// Get pending downloads size estimate.
		$queue_table = WPInsight_DB::get_table_name( 'zip_queue' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pending_size = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(remote_filesize), 0) FROM %i
				WHERE status IN ('pending', 'processing')
				AND remote_filesize IS NOT NULL",
				$queue_table
			)
		);

		return array(
			'total_space'     => (int) $total_space,
			'free_space'      => (int) $free_space,
			'used_space'      => (int) $used_space,
			'used_percent'    => round( $used_percent, 1 ),
			'artifacts_size'  => (int) $artifacts_size,
			'pending_size'    => (int) $pending_size,
			'total_required'  => (int) $artifacts_size + (int) $pending_size,
		);
	}

	/**
	 * Handle download control actions.
	 *
	 * Processes pause/resume actions for download queue.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function handle_download_control_actions(): void {
		// Check if action is set.
		if ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], array( 'pause_downloads', 'resume_downloads' ), true ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpinsight_download_control' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'cloudfest-wporgdownload' ) );
		}

		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'cloudfest-wporgdownload' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		try {
			if ( 'pause_downloads' === $action ) {
				// Pause downloads.
				WPInsight_Settings::update( 'downloads_paused', true );

				WPInsight_Logger::info(
					'Downloads paused by user',
					array( 'user_id' => get_current_user_id() )
				);

				add_settings_error(
					'wpinsight_messages',
					'downloads_paused',
					__( 'Downloads have been paused. No new downloads will start.', 'cloudfest-wporgdownload' ),
					'success'
				);
			} elseif ( 'resume_downloads' === $action ) {
				// Resume downloads.
				WPInsight_Settings::update( 'downloads_paused', false );

				WPInsight_Logger::info(
					'Downloads resumed by user',
					array( 'user_id' => get_current_user_id() )
				);

				add_settings_error(
					'wpinsight_messages',
					'downloads_resumed',
					__( 'Downloads have been resumed. Download queue will continue processing.', 'cloudfest-wporgdownload' ),
					'success'
				);
			}
		} catch ( Exception $e ) {
			WPInsight_Logger::error(
				'Download control action failed',
				array(
					'action' => $action,
					'error'  => $e->getMessage(),
				)
			);

			add_settings_error(
				'wpinsight_messages',
				'download_control_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'Action failed: %s', 'cloudfest-wporgdownload' ),
					$e->getMessage()
				),
				'error'
			);
		}

		// Redirect back to dashboard.
		$redirect_url = add_query_arg(
			array(
				'page' => self::DASHBOARD_PAGE_SLUG,
			),
			admin_url( 'tools.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get system health check data.
	 *
	 * Performs comprehensive system health checks including PHP version,
	 * memory, extensions, permissions, and requirements.
	 *
	 * @since 1.5.0
	 * @return array{
	 *     php_version: array{status: string, value: string, required: string, message: string},
	 *     php_memory: array{status: string, value: string, used: int, limit: int, percent: float, message: string},
	 *     wp_version: array{status: string, value: string, required: string, message: string},
	 *     extensions: array<string, array{status: string, installed: bool, message: string}>,
	 *     permissions: array{status: string, path: string, writable: bool, message: string},
	 *     disk_space: array{status: string, free: int, total: int, percent: float, message: string},
	 *     database: array{status: string, version: string, message: string},
	 *     action_scheduler: array{status: string, installed: bool, message: string}
	 * } System health data.
	 */
	private static function get_system_health_data(): array {
		global $wpdb;

		$health = array();

		// Check PHP Version.
		$php_version     = PHP_VERSION;
		$required_php    = '8.4';
		$php_version_ok  = version_compare( $php_version, $required_php, '>=' );
		$health['php_version'] = array(
			'status'   => $php_version_ok ? 'ok' : 'error',
			'value'    => $php_version,
			'required' => $required_php . '+',
			'message'  => $php_version_ok
				? sprintf( __( 'PHP %s (meets requirement)', 'cloudfest-wporgdownload' ), $php_version )
				: sprintf( __( 'PHP %s (requires %s+)', 'cloudfest-wporgdownload' ), $php_version, $required_php ),
		);

		// Check PHP Memory.
		$memory_limit     = ini_get( 'memory_limit' );
		$memory_limit_bytes = wp_convert_hr_to_bytes( $memory_limit );
		$memory_usage     = memory_get_usage( true );
		$memory_percent   = $memory_limit_bytes > 0 ? ( $memory_usage / $memory_limit_bytes ) * 100 : 0;

		$memory_status = 'ok';
		if ( $memory_percent > 90 ) {
			$memory_status = 'error';
		} elseif ( $memory_percent > 75 ) {
			$memory_status = 'warning';
		}

		$health['php_memory'] = array(
			'status'  => $memory_status,
			'value'   => $memory_limit,
			'used'    => $memory_usage,
			'limit'   => $memory_limit_bytes,
			'percent' => round( $memory_percent, 1 ),
			'message' => sprintf(
				/* translators: 1: used memory, 2: memory limit, 3: percentage */
				__( '%1$s used of %2$s (%3$s%%)', 'cloudfest-wporgdownload' ),
				size_format( $memory_usage ),
				$memory_limit,
				round( $memory_percent, 1 )
			),
		);

		// Check WordPress Version.
		$wp_version     = get_bloginfo( 'version' );
		$required_wp    = '6.9';
		$wp_version_ok  = version_compare( $wp_version, $required_wp, '>=' );
		$health['wp_version'] = array(
			'status'   => $wp_version_ok ? 'ok' : 'error',
			'value'    => $wp_version,
			'required' => $required_wp . '+',
			'message'  => $wp_version_ok
				? sprintf( __( 'WordPress %s (meets requirement)', 'cloudfest-wporgdownload' ), $wp_version )
				: sprintf( __( 'WordPress %s (requires %s+)', 'cloudfest-wporgdownload' ), $wp_version, $required_wp ),
		);

		// Check PHP Extensions.
		$required_extensions = array(
			'curl'     => __( 'cURL extension (required for API requests)', 'cloudfest-wporgdownload' ),
			'zip'      => __( 'ZIP extension (required for file validation)', 'cloudfest-wporgdownload' ),
			'json'     => __( 'JSON extension (required for API parsing)', 'cloudfest-wporgdownload' ),
			'mbstring' => __( 'Mbstring extension (recommended for string handling)', 'cloudfest-wporgdownload' ),
		);

		$health['extensions'] = array();
		foreach ( $required_extensions as $ext => $description ) {
			$installed = extension_loaded( $ext );
			$is_required = in_array( $ext, array( 'curl', 'zip', 'json' ), true );

			$health['extensions'][ $ext ] = array(
				'status'    => $installed ? 'ok' : ( $is_required ? 'error' : 'warning' ),
				'installed' => $installed,
				'message'   => $installed
					? sprintf( __( '%s - Installed', 'cloudfest-wporgdownload' ), $description )
					: sprintf( __( '%s - Missing', 'cloudfest-wporgdownload' ), $description ),
			);
		}

		// Check File Permissions.
		$upload_dir = wp_upload_dir();
		$base_path  = $upload_dir['basedir'];
		$writable   = wp_is_writable( $base_path );

		$health['permissions'] = array(
			'status'   => $writable ? 'ok' : 'error',
			'path'     => $base_path,
			'writable' => $writable,
			'message'  => $writable
				? sprintf( __( 'Uploads directory writable: %s', 'cloudfest-wporgdownload' ), $base_path )
				: sprintf( __( 'Uploads directory NOT writable: %s', 'cloudfest-wporgdownload' ), $base_path ),
		);

		// Check Disk Space.
		$total_space = disk_total_space( $base_path );
		$free_space  = disk_free_space( $base_path );
		$used_percent = $total_space > 0 ? ( ( $total_space - $free_space ) / $total_space ) * 100 : 0;

		$disk_status = 'ok';
		if ( $used_percent > 95 ) {
			$disk_status = 'error';
		} elseif ( $used_percent > 85 ) {
			$disk_status = 'warning';
		}

		$health['disk_space'] = array(
			'status'  => $disk_status,
			'free'    => $free_space,
			'total'   => $total_space,
			'percent' => round( $used_percent, 1 ),
			'message' => sprintf(
				/* translators: 1: free space, 2: total space */
				__( '%1$s free of %2$s', 'cloudfest-wporgdownload' ),
				size_format( $free_space ),
				size_format( $total_space )
			),
		);

		// Check Database.
		$db_version = $wpdb->db_version();
		$db_ok      = version_compare( $db_version, '10.6', '>=' );

		$health['database'] = array(
			'status'  => $db_ok ? 'ok' : 'warning',
			'version' => $db_version,
			'message' => $db_ok
				? sprintf( __( 'MariaDB/MySQL %s (meets requirement)', 'cloudfest-wporgdownload' ), $db_version )
				: sprintf( __( 'MariaDB/MySQL %s (10.6+ recommended)', 'cloudfest-wporgdownload' ), $db_version ),
		);

		// Check Action Scheduler.
		$as_installed = function_exists( 'as_schedule_recurring_action' );

		$health['action_scheduler'] = array(
			'status'    => $as_installed ? 'ok' : 'error',
			'installed' => $as_installed,
			'message'   => $as_installed
				? __( 'Action Scheduler - Installed and active', 'cloudfest-wporgdownload' )
				: __( 'Action Scheduler - Missing (REQUIRED)', 'cloudfest-wporgdownload' ),
		);

		return $health;
	}

	/**
	 * Get overall health status.
	 *
	 * Analyzes all health checks and returns overall status.
	 *
	 * @since 1.5.0
	 * @param array<string, mixed> $health_data Health check data.
	 * @return array{
	 *     status: string,
	 *     message: string,
	 *     error_count: int,
	 *     warning_count: int
	 * } Overall health status.
	 */
	private static function get_overall_health_status( array $health_data ): array {
		$error_count   = 0;
		$warning_count = 0;

		// Count errors and warnings.
		foreach ( $health_data as $key => $check ) {
			if ( 'extensions' === $key ) {
				foreach ( $check as $ext_data ) {
					if ( 'error' === $ext_data['status'] ) {
						++$error_count;
					} elseif ( 'warning' === $ext_data['status'] ) {
						++$warning_count;
					}
				}
			} else {
				if ( 'error' === $check['status'] ) {
					++$error_count;
				} elseif ( 'warning' === $check['status'] ) {
					++$warning_count;
				}
			}
		}

		// Determine overall status.
		$overall_status = 'ok';
		$message        = __( 'All system checks passed', 'cloudfest-wporgdownload' );

		if ( $error_count > 0 ) {
			$overall_status = 'error';
			$message        = sprintf(
				/* translators: %d: number of errors */
				_n( '%d critical issue detected', '%d critical issues detected', $error_count, 'cloudfest-wporgdownload' ),
				$error_count
			);
		} elseif ( $warning_count > 0 ) {
			$overall_status = 'warning';
			$message        = sprintf(
				/* translators: %d: number of warnings */
				_n( '%d warning detected', '%d warnings detected', $warning_count, 'cloudfest-wporgdownload' ),
				$warning_count
			);
		}

		return array(
			'status'        => $overall_status,
			'message'       => $message,
			'error_count'   => $error_count,
			'warning_count' => $warning_count,
		);
	}

	/**
	 * Get API health monitor data.
	 *
	 * Monitors WordPress.org API health including response times,
	 * error rates, and configuration recommendations.
	 *
	 * @since 1.5.0
	 * @return array{
	 *     response_time: array{status: string, value: float, message: string},
	 *     error_rate: array{status: string, errors: int, total: int, percent: float, message: string},
	 *     rate_limit: array{status: string, current: int, optimal: int, message: string},
	 *     last_test: array{success: bool, time: float, message: string},
	 *     recommendations: array<int, string>
	 * } API health data.
	 */
	private static function get_api_health_data(): array {
		global $wpdb;

		$health = array();

		// Test API response time (real-time check).
		$test_start = microtime( true );
		$test_response = WPInsight_WPOrg_Client::check_api_health();
		$test_time = microtime( true ) - $test_start;

		$response_ok = ! is_wp_error( $test_response );
		$health['last_test'] = array(
			'success' => $response_ok,
			'time'    => round( $test_time, 3 ),
			'message' => $response_ok
				? sprintf(
					/* translators: %s: response time in seconds */
					__( 'API responding in %ss', 'cloudfest-wporgdownload' ),
					round( $test_time, 3 )
				)
				: sprintf(
					/* translators: %s: error message */
					__( 'API test failed: %s', 'cloudfest-wporgdownload' ),
					$test_response->get_error_message()
				),
		);

		// Calculate average response time from recent successful requests.
		// For now, use the test time as baseline.
		$avg_response = $test_time;
		$response_status = 'ok';

		if ( $avg_response > 3.0 ) {
			$response_status = 'error';
		} elseif ( $avg_response > 1.5 ) {
			$response_status = 'warning';
		}

		$health['response_time'] = array(
			'status'  => $response_status,
			'value'   => round( $avg_response, 3 ),
			'message' => $response_status === 'ok'
				? sprintf(
					/* translators: %s: response time in seconds */
					__( 'Fast (%ss average)', 'cloudfest-wporgdownload' ),
					round( $avg_response, 3 )
				)
				: sprintf(
					/* translators: %s: response time in seconds */
					__( 'Slow (%ss average)', 'cloudfest-wporgdownload' ),
					round( $avg_response, 3 )
				),
		);

		// Get API error rate from error log (last 24 hours).
		$logs_table = WPInsight_DB::get_table_name( 'error_log' );

		// Count API-related errors.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$api_errors = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i
				WHERE severity IN ('ERROR', 'EMERGENCY')
				AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
				AND (message LIKE %s OR message LIKE %s OR context LIKE %s)",
				$logs_table,
				'%API%',
				'%WordPress.org%',
				'%wporg%'
			)
		);

		// Estimate total API requests in last 24h.
		// Based on sync frequency: if sync runs every 5 min = 288 times/day.
		// Each sync makes ~1-3 API calls depending on type.
		// Plus size detection worker, etc.
		// Conservative estimate: ~500-3000 requests per day.
		$sync_interval = WPInsight_Settings::get( 'sync_interval', 300 );
		$syncs_per_day = 86400 / $sync_interval; // 24h / interval.
		$estimated_requests = $syncs_per_day * 2; // Average 2 API calls per sync.

		// Add size detection requests.
		$size_detection_rate = WPInsight_Settings::get( 'max_size_detection_rate', 3 );
		$size_detection_per_day = ( 86400 / 300 ) * 600; // Every 5 min, 600 ZIPs.
		$estimated_requests += $size_detection_per_day;

		$error_percent = $estimated_requests > 0 ? ( $api_errors / $estimated_requests ) * 100 : 0;

		$error_status = 'ok';
		if ( $error_percent > 5 ) {
			$error_status = 'error';
		} elseif ( $error_percent > 1 ) {
			$error_status = 'warning';
		}

		$health['error_rate'] = array(
			'status'  => $error_status,
			'errors'  => (int) $api_errors,
			'total'   => (int) $estimated_requests,
			'percent' => round( $error_percent, 2 ),
			'message' => sprintf(
				/* translators: 1: error count, 2: total requests, 3: error percentage */
				__( '%1$d errors of %2$d requests (%3$s%%)', 'cloudfest-wporgdownload' ),
				$api_errors,
				number_format_i18n( $estimated_requests ),
				round( $error_percent, 2 )
			),
		);

		// Rate limit recommendations.
		$current_rate_limit = WPInsight_Settings::get( 'max_concurrent_downloads', 3 );
		$optimal_rate_limit = 3; // WordPress.org best practice.

		$rate_status = 'ok';
		if ( $current_rate_limit > 5 ) {
			$rate_status = 'error'; // Too aggressive, risk of being banned.
		} elseif ( $current_rate_limit > 3 ) {
			$rate_status = 'warning'; // Above recommended.
		}

		$health['rate_limit'] = array(
			'status'  => $rate_status,
			'current' => $current_rate_limit,
			'optimal' => $optimal_rate_limit,
			'message' => $current_rate_limit === $optimal_rate_limit
				? sprintf(
					/* translators: %d: rate limit value */
					__( 'Optimal (%d concurrent downloads)', 'cloudfest-wporgdownload' ),
					$current_rate_limit
				)
				: sprintf(
					/* translators: 1: current rate, 2: optimal rate */
					__( 'Current: %1$d, Recommended: %2$d', 'cloudfest-wporgdownload' ),
					$current_rate_limit,
					$optimal_rate_limit
				),
		);

		// Generate recommendations.
		$recommendations = array();

		if ( $error_percent > 5 ) {
			$recommendations[] = __( 'High API error rate detected. Consider reducing sync frequency or rate limits.', 'cloudfest-wporgdownload' );
		}

		if ( $current_rate_limit > 3 ) {
			$recommendations[] = sprintf(
				/* translators: %d: current rate limit */
				__( 'Current rate limit (%d) is above WordPress.org recommended limit (3). Reduce to avoid being blocked.', 'cloudfest-wporgdownload' ),
				$current_rate_limit
			);
		}

		if ( $avg_response > 2.0 ) {
			$recommendations[] = __( 'Slow API response times detected. This may indicate network issues or WordPress.org service degradation.', 'cloudfest-wporgdownload' );
		}

		if ( ! $response_ok ) {
			$recommendations[] = __( 'API test failed. Check your network connection and WordPress.org service status.', 'cloudfest-wporgdownload' );
		}

		if ( empty( $recommendations ) ) {
			$recommendations[] = __( 'API health is optimal. No issues detected.', 'cloudfest-wporgdownload' );
		}

		$health['recommendations'] = $recommendations;

		return $health;
	}

	/**
	 * Export diagnostic report.
	 *
	 * Generates a comprehensive diagnostic report for support or debugging.
	 *
	 * @since 1.5.0
	 * @param bool $anonymize Whether to anonymize sensitive data.
	 * @return array<string, mixed> Diagnostic report data.
	 */
	private static function export_diagnostic_report( bool $anonymize = false ): array {
		global $wpdb;

		$report = array(
			'meta' => array(
				'generated_at' => current_time( 'mysql', true ),
				'plugin_version' => WPINSIGHT_VERSION,
				'wp_version' => get_bloginfo( 'version' ),
				'php_version' => PHP_VERSION,
				'anonymized' => $anonymize,
			),
		);

		// System Health.
		$report['system_health'] = self::get_system_health_data();

		// Anonymize sensitive paths.
		if ( $anonymize && isset( $report['system_health']['permissions']['path'] ) ) {
			$report['system_health']['permissions']['path'] = '[REDACTED]';
		}

		// API Health.
		$report['api_health'] = self::get_api_health_data();

		// Database Statistics.
		$report['database_stats'] = WPInsight_DB::get_all_tables_stats();

		// Queue Statistics.
		$report['queue_stats'] = WPInsight_Zip_Queue::get_queue_stats();

		// Sync States.
		$report['sync_states'] = array(
			'plugin' => WPInsight_Sync::get_sync_state( 'plugin' ),
			'theme'  => WPInsight_Sync::get_sync_state( 'theme' ),
		);

		// Settings (optionally anonymized).
		$all_settings = WPInsight_Settings::get_all();

		if ( $anonymize ) {
			// Remove sensitive settings.
			unset( $all_settings['admin_notification_email'] );
			unset( $all_settings['storage_base_path'] );
		}

		$report['settings'] = $all_settings;

		// Recent errors (last 50).
		$logs_table = WPInsight_DB::get_table_name( 'error_log' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$recent_errors = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT severity, message, created_at FROM %i
				ORDER BY created_at DESC
				LIMIT 50',
				$logs_table
			),
			ARRAY_A
		);

		if ( $anonymize && $recent_errors ) {
			// Redact file paths and URLs from error messages.
			foreach ( $recent_errors as &$error ) {
				$error['message'] = preg_replace( '#(/[^\s]+)#', '[PATH]', $error['message'] );
				$error['message'] = preg_replace( '#(https?://[^\s]+)#', '[URL]', $error['message'] );
			}
		}

		$report['recent_errors'] = $recent_errors;

		// Active downloads.
		$report['active_downloads'] = count( self::get_active_downloads() );

		// Disk space.
		$report['disk_space'] = self::get_disk_space_info();

		// WordPress environment info.
		if ( ! $anonymize ) {
			$report['environment'] = array(
				'home_url'        => home_url(),
				'site_url'        => site_url(),
				'wp_debug'        => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'wp_debug_log'    => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
				'wp_memory_limit' => WP_MEMORY_LIMIT,
				'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			);
		}

		return $report;
	}

	/**
	 * Handle diagnostic export action.
	 *
	 * Exports diagnostic report as JSON download.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function handle_diagnostic_export(): void {
		// Check if export action is set.
		if ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], array( 'export_diagnostics', 'export_diagnostics_anon' ), true ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wpinsight_export_diagnostics' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'cloudfest-wporgdownload' ) );
		}

		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'cloudfest-wporgdownload' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
		$anonymize = ( 'export_diagnostics_anon' === $action );

		try {
			// Generate report.
			$report = self::export_diagnostic_report( $anonymize );

			// Log export.
			WPInsight_Logger::info(
				'Diagnostic report exported',
				array(
					'anonymized' => $anonymize,
					'user_id'    => get_current_user_id(),
				)
			);

			// Generate filename.
			$filename = sprintf(
				'wpinsight-diagnostics-%s-%s.json',
				$anonymize ? 'anonymous' : 'full',
				gmdate( 'Y-m-d-His' )
			);

			// Send headers for download.
			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// Output JSON.
			echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			exit;

		} catch ( Exception $e ) {
			WPInsight_Logger::error(
				'Diagnostic export failed',
				array(
					'error' => $e->getMessage(),
				)
			);

			wp_die(
				esc_html(
					sprintf(
						/* translators: %s: Error message */
						__( 'Export failed: %s', 'cloudfest-wporgdownload' ),
						$e->getMessage()
					)
				)
			);
		}
	}

	/**
	 * Handle settings import action.
	 *
	 * Imports settings from uploaded JSON file.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function handle_settings_import(): void {
		// Check if import action is set.
		if ( ! isset( $_POST['wpinsight_import_settings'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wpinsight_import_settings' ) ) {
			add_settings_error(
				'wpinsight_messages',
				'import_error',
				__( 'Security check failed.', 'cloudfest-wporgdownload' ),
				'error'
			);
			return;
		}

		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error(
				'wpinsight_messages',
				'import_error',
				__( 'You do not have permission to perform this action.', 'cloudfest-wporgdownload' ),
				'error'
			);
			return;
		}

		// Check if file was uploaded.
		if ( ! isset( $_FILES['settings_file'] ) || UPLOAD_ERR_OK !== $_FILES['settings_file']['error'] ) {
			add_settings_error(
				'wpinsight_messages',
				'import_error',
				__( 'No file uploaded or upload error occurred.', 'cloudfest-wporgdownload' ),
				'error'
			);
			return;
		}

		try {
			// Validate file type.
			$file_name = sanitize_file_name( wp_unslash( $_FILES['settings_file']['name'] ) );
			if ( ! str_ends_with( $file_name, '.json' ) ) {
				throw new Exception( __( 'Invalid file type. Only JSON files are allowed.', 'cloudfest-wporgdownload' ) );
			}

			// Read file content.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Need to read uploaded file.
			$file_content = file_get_contents( $_FILES['settings_file']['tmp_name'] );
			if ( false === $file_content ) {
				throw new Exception( __( 'Failed to read uploaded file.', 'cloudfest-wporgdownload' ) );
			}

			// Parse JSON.
			$data = json_decode( $file_content, true );
			if ( null === $data ) {
				throw new Exception( __( 'Invalid JSON format.', 'cloudfest-wporgdownload' ) );
			}

			// Validate structure.
			if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
				throw new Exception( __( 'Invalid diagnostic file. Missing settings data.', 'cloudfest-wporgdownload' ) );
			}

			// Import settings (only safe settings).
			$safe_settings = array(
				'auto_sync_enabled',
				'sync_plugins_enabled',
				'sync_themes_enabled',
				'download_plugins_enabled',
				'download_themes_enabled',
				'sync_interval',
				'max_concurrent_downloads',
				'zip_worker_interval',
				'max_retry_attempts',
				'max_size_detection_rate',
				'per_page',
				'log_retention_days',
			);

			$imported_count = 0;
			foreach ( $safe_settings as $setting_key ) {
				if ( isset( $data['settings'][ $setting_key ] ) ) {
					WPInsight_Settings::update( $setting_key, $data['settings'][ $setting_key ] );
					++$imported_count;
				}
			}

			// Log import.
			WPInsight_Logger::info(
				'Settings imported from diagnostic file',
				array(
					'imported_count' => $imported_count,
					'user_id'        => get_current_user_id(),
				)
			);

			add_settings_error(
				'wpinsight_messages',
				'import_success',
				sprintf(
					/* translators: %d: number of settings imported */
					_n( '%d setting imported successfully.', '%d settings imported successfully.', $imported_count, 'cloudfest-wporgdownload' ),
					$imported_count
				),
				'success'
			);

		} catch ( Exception $e ) {
			WPInsight_Logger::error(
				'Settings import failed',
				array(
					'error' => $e->getMessage(),
				)
			);

			add_settings_error(
				'wpinsight_messages',
				'import_error',
				sprintf(
					/* translators: %s: Error message */
					__( 'Import failed: %s', 'cloudfest-wporgdownload' ),
					$e->getMessage()
				),
				'error'
			);
		}
	}
}
