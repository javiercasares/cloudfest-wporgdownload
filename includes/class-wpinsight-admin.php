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

		// Error logs are now integrated in the main dashboard.
		// No separate menu entry needed.
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
			<!-- Phase 5.5: Add debug tools content here -->
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
}
