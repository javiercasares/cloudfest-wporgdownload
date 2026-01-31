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
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_dashboard_actions' ] );
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
		// Add main Dashboard page under Tools menu.
		add_management_page(
			__( 'WPInsight Dashboard', 'cloudfest-wporgdownload' ), // Page title.
			__( 'WPInsight', 'cloudfest-wporgdownload' ),            // Menu title.
			'manage_options',                                         // Capability.
			self::DASHBOARD_PAGE_SLUG,                                // Menu slug.
			[ __CLASS__, 'render_dashboard_page' ]                    // Callback.
		);

		// Add Settings page under Settings menu.
		add_options_page(
			__( 'WPInsight Settings', 'cloudfest-wporgdownload' ), // Page title.
			__( 'WPInsight', 'cloudfest-wporgdownload' ),           // Menu title.
			'manage_options',                                        // Capability.
			self::SETTINGS_PAGE_SLUG,                                // Menu slug.
			[ __CLASS__, 'render_settings_page' ]                    // Callback.
		);

		// Add Error Log page (only visible when WP_DEBUG is enabled).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_management_page(
				__( 'WPInsight Error Log', 'cloudfest-wporgdownload' ), // Page title.
				__( 'WPInsight Errors', 'cloudfest-wporgdownload' ),    // Menu title.
				'manage_options',                                         // Capability.
				'wpinsight-error-log',                                    // Menu slug.
				[ __CLASS__, 'render_error_log_page' ]                    // Callback.
			);
		}
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
			[
				'type'              => 'array',
				'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ],
			]
		);

		// General Settings Section.
		add_settings_section(
			'wpinsight_general',
			__( 'General Settings', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_section_general' ],
			self::SETTINGS_PAGE_SLUG
		);

		// Sync Settings Section.
		add_settings_section(
			'wpinsight_sync',
			__( 'Sync Settings', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_section_sync' ],
			self::SETTINGS_PAGE_SLUG
		);

		// Rate Limiting Settings Section.
		add_settings_section(
			'wpinsight_rate_limiting',
			__( 'Rate Limiting', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_section_rate_limiting' ],
			self::SETTINGS_PAGE_SLUG
		);

		// Error Notifications Settings Section (v1.1.0+).
		add_settings_section(
			'wpinsight_notifications',
			__( 'Error Notifications', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_section_notifications' ],
			self::SETTINGS_PAGE_SLUG
		);

		// General fields.
		add_settings_field(
			'delete_on_uninstall',
			__( 'Delete Data on Uninstall', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_delete_on_uninstall' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_general'
		);

		// Sync fields.
		add_settings_field(
			'auto_sync_enabled',
			__( 'Enable Auto Sync', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_auto_sync_enabled' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'sync_plugins_enabled',
			__( 'Sync Plugins', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_sync_plugins_enabled' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'sync_themes_enabled',
			__( 'Sync Themes', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_sync_themes_enabled' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'download_plugin_zips_enabled',
			__( 'Download Plugin ZIPs', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_download_plugin_zips_enabled' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'download_theme_zips_enabled',
			__( 'Download Theme ZIPs', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_download_theme_zips_enabled' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		add_settings_field(
			'sync_interval',
			__( 'Sync Interval (seconds)', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_sync_interval' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_sync'
		);

		// Rate limiting fields.
		add_settings_field(
			'max_concurrent_downloads',
			__( 'Max Concurrent Downloads', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_max_concurrent_downloads' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_rate_limiting'
		);

		add_settings_field(
			'zip_worker_interval',
			__( 'ZIP Worker Interval (seconds)', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_zip_worker_interval' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_rate_limiting'
		);

		add_settings_field(
			'max_retries',
			__( 'Max Download Retries', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_max_retries' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_rate_limiting'
		);

		// Error notification fields (v1.1.0+).
		add_settings_field(
			'admin_email_notifications_enabled',
			__( 'Enable Email Notifications', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_admin_email_notifications_enabled' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_notifications'
		);

		add_settings_field(
			'admin_notification_email',
			__( 'Notification Email', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_admin_notification_email' ],
			self::SETTINGS_PAGE_SLUG,
			'wpinsight_notifications'
		);

		add_settings_field(
			'log_retention_days',
			__( 'Log Retention (days)', 'cloudfest-wporgdownload' ),
			[ __CLASS__, 'render_field_log_retention_days' ],
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
				$result = WPInsight_Sync::sync_plugins();
				if ( $result ) {
					add_settings_error( 'wpinsight_dashboard', 'sync_success', __( 'Plugin sync completed successfully.', 'cloudfest-wporgdownload' ), 'success' );
				} else {
					add_settings_error( 'wpinsight_dashboard', 'sync_error', __( 'Plugin sync failed. Check logs for details.', 'cloudfest-wporgdownload' ), 'error' );
				}
				break;

			case 'sync_themes':
				$result = WPInsight_Sync::sync_themes();
				if ( $result ) {
					add_settings_error( 'wpinsight_dashboard', 'sync_success', __( 'Theme sync completed successfully.', 'cloudfest-wporgdownload' ), 'success' );
				} else {
					add_settings_error( 'wpinsight_dashboard', 'sync_error', __( 'Theme sync failed. Check logs for details.', 'cloudfest-wporgdownload' ), 'error' );
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

			case 'reset_sync_plugins':
				WPInsight_Sync::reset_sync_state( 'plugin' );
				add_settings_error( 'wpinsight_dashboard', 'reset_success', __( 'Plugin sync state reset.', 'cloudfest-wporgdownload' ), 'success' );
				break;

			case 'reset_sync_themes':
				WPInsight_Sync::reset_sync_state( 'theme' );
				add_settings_error( 'wpinsight_dashboard', 'reset_success', __( 'Theme sync state reset.', 'cloudfest-wporgdownload' ), 'success' );
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

		// Get statistics.
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

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'wpinsight_dashboard' ); ?>

			<!-- Statistics Overview -->
			<div class="wpinsight-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
				<div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Plugins', 'cloudfest-wporgdownload' ); ?></h3>
					<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( number_format_i18n( $plugin_count ) ); ?></div>
				</div>

				<div style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?></h3>
					<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( number_format_i18n( $theme_count ) ); ?></div>
				</div>

				<div style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Downloaded ZIPs', 'cloudfest-wporgdownload' ); ?></h3>
					<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( number_format_i18n( $artifact_count ) ); ?></div>
				</div>

				<div style="background: #fff; padding: 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
					<h3 style="margin: 0 0 10px 0; font-size: 14px; color: #646970;"><?php esc_html_e( 'Storage Used', 'cloudfest-wporgdownload' ); ?></h3>
					<div style="font-size: 32px; font-weight: 400; color: #1d2327;"><?php echo esc_html( (string) size_format( $storage_size, 2 ) ); ?></div>
				</div>
			</div>

			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">
				<!-- Sync Status -->
				<div class="card">
					<h2><?php esc_html_e( 'Sync Status', 'cloudfest-wporgdownload' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Type', 'cloudfest-wporgdownload' ); ?></th>
								<th><?php esc_html_e( 'Status', 'cloudfest-wporgdownload' ); ?></th>
								<th><?php esc_html_e( 'Page', 'cloudfest-wporgdownload' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'cloudfest-wporgdownload' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><strong><?php esc_html_e( 'Plugins', 'cloudfest-wporgdownload' ); ?></strong></td>
								<td><code><?php echo esc_html( $plugin_state['status'] ); ?></code></td>
								<td><?php echo esc_html( number_format_i18n( $plugin_state['page'] ) ); ?></td>
								<td>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
										<input type="hidden" name="wpinsight_action" value="sync_plugins">
										<button type="submit" class="button button-small"><?php esc_html_e( 'Sync Now', 'cloudfest-wporgdownload' ); ?></button>
									</form>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
										<input type="hidden" name="wpinsight_action" value="reset_sync_plugins">
										<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset the sync state?', 'cloudfest-wporgdownload' ); ?>');"><?php esc_html_e( 'Reset', 'cloudfest-wporgdownload' ); ?></button>
									</form>
								</td>
							</tr>
							<tr>
								<td><strong><?php esc_html_e( 'Themes', 'cloudfest-wporgdownload' ); ?></strong></td>
								<td><code><?php echo esc_html( $theme_state['status'] ); ?></code></td>
								<td><?php echo esc_html( number_format_i18n( $theme_state['page'] ) ); ?></td>
								<td>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
										<input type="hidden" name="wpinsight_action" value="sync_themes">
										<button type="submit" class="button button-small"><?php esc_html_e( 'Sync Now', 'cloudfest-wporgdownload' ); ?></button>
									</form>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
										<input type="hidden" name="wpinsight_action" value="reset_sync_themes">
										<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to reset the sync state?', 'cloudfest-wporgdownload' ); ?>');"><?php esc_html_e( 'Reset', 'cloudfest-wporgdownload' ); ?></button>
									</form>
								</td>
							</tr>
						</tbody>
					</table>
					<?php if ( ! empty( $plugin_state['last_error'] ) ) : ?>
						<div class="notice notice-error inline" style="margin: 10px 0;">
							<p><strong><?php esc_html_e( 'Plugin Sync Error:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( $plugin_state['last_error'] ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $theme_state['last_error'] ) ) : ?>
						<div class="notice notice-error inline" style="margin: 10px 0;">
							<p><strong><?php esc_html_e( 'Theme Sync Error:', 'cloudfest-wporgdownload' ); ?></strong> <?php echo esc_html( $theme_state['last_error'] ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<!-- Download Queue -->
				<div class="card">
					<h2><?php esc_html_e( 'Download Queue', 'cloudfest-wporgdownload' ); ?></h2>
					<table class="widefat striped">
						<tbody>
							<tr>
								<th><?php esc_html_e( 'Pending', 'cloudfest-wporgdownload' ); ?>:</th>
								<td><strong><?php echo esc_html( number_format_i18n( $queue_stats['pending'] ) ); ?></strong></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Processing', 'cloudfest-wporgdownload' ); ?>:</th>
								<td><?php echo esc_html( number_format_i18n( $queue_stats['processing'] ) ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Completed', 'cloudfest-wporgdownload' ); ?>:</th>
								<td style="color: #00a32a;"><strong><?php echo esc_html( number_format_i18n( $queue_stats['completed'] ) ); ?></strong></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Failed', 'cloudfest-wporgdownload' ); ?>:</th>
								<td style="color: #d63638;"><strong><?php echo esc_html( number_format_i18n( $queue_stats['failed'] ) ); ?></strong></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Total', 'cloudfest-wporgdownload' ); ?>:</th>
								<td><strong><?php echo esc_html( number_format_i18n( $queue_stats['total'] ) ); ?></strong></td>
							</tr>
						</tbody>
					</table>

					<div style="margin-top: 15px;">
						<form method="post" style="display: inline;">
							<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
							<input type="hidden" name="wpinsight_action" value="process_queue">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Process Queue', 'cloudfest-wporgdownload' ); ?></button>
						</form>

						<?php if ( $queue_stats['failed'] > 0 ) : ?>
							<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
								<input type="hidden" name="wpinsight_action" value="retry_failed">
								<button type="submit" class="button"><?php esc_html_e( 'Retry Failed', 'cloudfest-wporgdownload' ); ?></button>
							</form>
						<?php endif; ?>

						<?php if ( $queue_stats['completed'] > 0 ) : ?>
							<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_dashboard_action', 'wpinsight_dashboard_nonce' ); ?>
								<input type="hidden" name="wpinsight_action" value="clear_completed">
								<button type="submit" class="button"><?php esc_html_e( 'Clear Completed', 'cloudfest-wporgdownload' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			</div>

		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">
			<!-- Recent Errors -->
			<?php self::render_recent_errors_card(); ?>

			<!-- Quick Links -->
			<div class="card">
				<h2><?php esc_html_e( 'Quick Links', 'cloudfest-wporgdownload' ); ?></h2>
				<p>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::SETTINGS_PAGE_SLUG ) ); ?>" class="button">
						<?php esc_html_e( 'Settings', 'cloudfest-wporgdownload' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . WPInsight_CPT::get_plugin_post_type() ) ); ?>" class="button">
						<?php esc_html_e( 'View Plugins', 'cloudfest-wporgdownload' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . WPInsight_CPT::get_theme_post_type() ) ); ?>" class="button">
						<?php esc_html_e( 'View Themes', 'cloudfest-wporgdownload' ); ?>
					</a>
					<?php if ( defined( 'WP_CLI' ) && WP_CLI ) : ?>
						<a href="<?php echo esc_url( admin_url( 'tools.php?page=' . self::DASHBOARD_PAGE_SLUG . '#cli-commands' ) ); ?>" class="button">
							<?php esc_html_e( 'WP-CLI Commands', 'cloudfest-wporgdownload' ); ?>
						</a>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<?php
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
	private static function get_severity_badge_html( string $severity ): string {
		$colors = [
			'emergency' => '#d63638', // Red.
			'error'     => '#d63638', // Red.
			'warning'   => '#f0b849', // Orange.
			'info'      => '#2271b1', // Blue.
			'debug'     => '#646970', // Gray.
		];

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

			<div class="card">
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

			<div class="card" style="margin-top: 20px;">
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
									[
										'base'      => add_query_arg( 'paged', '%#%' ),
										'format'    => '',
										'current'   => $page_num,
										'total'     => $total_pages,
										'prev_text' => __( '&laquo; Previous', 'cloudfest-wporgdownload' ),
										'next_text' => __( 'Next &raquo;', 'cloudfest-wporgdownload' ),
									]
								)
							);
							?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="card" style="margin-top: 20px;">
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
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_PAGE_SLUG );
				submit_button();
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'System Status', 'cloudfest-wporgdownload' ); ?></h2>
			<table class="widefat">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Database Version', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><?php echo esc_html( WPInsight_DB::get_schema_version() ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Plugin Post Type', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><code><?php echo esc_html( WPInsight_CPT::get_plugin_post_type() ); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Theme Post Type', 'cloudfest-wporgdownload' ); ?>:</th>
						<td><code><?php echo esc_html( WPInsight_CPT::get_theme_post_type() ); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Action Scheduler', 'cloudfest-wporgdownload' ); ?>:</th>
						<td>
							<?php if ( function_exists( 'as_schedule_recurring_action' ) ) : ?>
								<span style="color: green;">✓ <?php esc_html_e( 'Installed', 'cloudfest-wporgdownload' ); ?></span>
							<?php else : ?>
								<span style="color: red;">✗ <?php esc_html_e( 'Not Found', 'cloudfest-wporgdownload' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php
		// Show Debug Tools only when WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			self::render_debug_tools();
		}
		?>
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
		<div class="wpinsight-system-status" style="margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
			<h3 style="margin-top: 0; color: #856404;">
				🔧 <?php esc_html_e( 'Debug Tools', 'cloudfest-wporgdownload' ); ?>
				<small style="font-weight: normal; color: #666;">(<?php esc_html_e( 'Only visible when WP_DEBUG is enabled', 'cloudfest-wporgdownload' ); ?>)</small>
			</h3>

			<?php
			// Handle debug actions.
			if ( isset( $_POST['wpinsight_debug_action'] ) && check_admin_referer( 'wpinsight_debug_tools' ) ) {
				$action = sanitize_text_field( wp_unslash( $_POST['wpinsight_debug_action'] ) );
				self::handle_debug_action( $action );
			}
			?>

			<table class="widefat striped" style="margin-top: 15px;">
				<thead>
					<tr>
						<th style="width: 30%;"><?php esc_html_e( 'Tool', 'cloudfest-wporgdownload' ); ?></th>
						<th style="width: 45%;"><?php esc_html_e( 'Status', 'cloudfest-wporgdownload' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Action', 'cloudfest-wporgdownload' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<!-- Database Tables Check -->
					<tr>
						<th><?php esc_html_e( 'Database Tables', 'cloudfest-wporgdownload' ); ?></th>
						<td><?php echo wp_kses_post( self::check_database_tables() ); ?></td>
						<td>
							<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_debug_tools' ); ?>
								<input type="hidden" name="wpinsight_debug_action" value="check_db" />
								<button type="submit" class="button button-small"><?php esc_html_e( 'Verify', 'cloudfest-wporgdownload' ); ?></button>
							</form>
						</td>
					</tr>

					<!-- Action Scheduler Jobs -->
					<tr>
						<th><?php esc_html_e( 'Scheduled Jobs', 'cloudfest-wporgdownload' ); ?></th>
						<td><?php echo wp_kses_post( self::check_scheduled_jobs() ); ?></td>
						<td>
							<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_debug_tools' ); ?>
								<input type="hidden" name="wpinsight_debug_action" value="reset_jobs" />
								<button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'This will unschedule all current jobs and reschedule them. Continue?', 'cloudfest-wporgdownload' ); ?>');"><?php esc_html_e( 'Reset Jobs', 'cloudfest-wporgdownload' ); ?></button>
							</form>
						</td>
					</tr>

					<!-- CPT Counts -->
					<tr>
						<th><?php esc_html_e( 'Plugin/Theme Posts', 'cloudfest-wporgdownload' ); ?></th>
						<td><?php echo wp_kses_post( self::get_cpt_counts() ); ?></td>
						<td>-</td>
					</tr>

					<!-- WordPress.org API -->
					<tr>
						<th><?php esc_html_e( 'WordPress.org API', 'cloudfest-wporgdownload' ); ?></th>
						<td><?php echo wp_kses_post( self::check_api_connection() ); ?></td>
						<td>
							<form method="post" style="display: inline;">
								<?php wp_nonce_field( 'wpinsight_debug_tools' ); ?>
								<input type="hidden" name="wpinsight_debug_action" value="test_api" />
								<button type="submit" class="button button-small"><?php esc_html_e( 'Test', 'cloudfest-wporgdownload' ); ?></button>
							</form>
						</td>
					</tr>
				</tbody>
			</table>

			<p style="margin-top: 15px; color: #856404; font-size: 0.9em;">
				<strong><?php esc_html_e( 'Note:', 'cloudfest-wporgdownload' ); ?></strong>
				<?php esc_html_e( 'These tools are only available when WP_DEBUG is enabled in wp-config.php. Additional diagnostic tools will be added in future phases.', 'cloudfest-wporgdownload' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Check database tables status.
	 *
	 * @since 0.1.0
	 * @return string HTML status message.
	 */
	private static function check_database_tables(): string {
		global $wpdb;

		$tables = [
			'sync_state',
			'zip_queue',
			'artifacts',
		];

		$missing = [];
		foreach ( $tables as $table_key ) {
			$table_name = WPInsight_DB::get_table_name( $table_key );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			if ( ! $exists ) {
				$missing[] = $table_name;
			}
		}

		if ( empty( $missing ) ) {
			return '<span style="color: green;">✓ ' . esc_html__( 'All tables exist', 'cloudfest-wporgdownload' ) . '</span>';
		}

		return '<span style="color: red;">✗ ' . esc_html__( 'Missing tables:', 'cloudfest-wporgdownload' ) . ' ' . esc_html( implode( ', ', $missing ) ) . '</span>';
	}

	/**
	 * Check scheduled jobs status.
	 *
	 * @since 0.1.0
	 * @return string HTML status message.
	 */
	private static function check_scheduled_jobs(): string {
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return '<span style="color: orange;">⚠ ' . esc_html__( 'Action Scheduler not available', 'cloudfest-wporgdownload' ) . '</span>';
		}

		$sync_jobs = as_get_scheduled_actions(
			[
				'hook'   => WPINSIGHT_SYNC_TICK_ACTION,
				'status' => 'pending',
				'group'  => WPINSIGHT_AS_GROUP,
			],
			'ids'
		);

		$zip_jobs = as_get_scheduled_actions(
			[
				'hook'   => WPINSIGHT_ZIP_WORKER_TICK_ACTION,
				'status' => 'pending',
				'group'  => WPINSIGHT_AS_GROUP,
			],
			'ids'
		);

		$sync_count = count( $sync_jobs );
		$zip_count  = count( $zip_jobs );

		if ( $sync_count > 0 || $zip_count > 0 ) {
			return sprintf(
				'<span style="color: green;">✓ %s</span> (Sync: %d, ZIP: %d)',
				esc_html__( 'Jobs scheduled', 'cloudfest-wporgdownload' ),
				$sync_count,
				$zip_count
			);
		}

		return '<span style="color: orange;">⚠ ' . esc_html__( 'No jobs scheduled', 'cloudfest-wporgdownload' ) . '</span>';
	}

	/**
	 * Get CPT counts.
	 *
	 * @since 0.1.0
	 * @return string HTML with counts.
	 */
	private static function get_cpt_counts(): string {
		$plugin_count = wp_count_posts( WPInsight_CPT::get_plugin_post_type() );
		$theme_count  = wp_count_posts( WPInsight_CPT::get_theme_post_type() );

		return sprintf(
			'%s: <strong>%d</strong> | %s: <strong>%d</strong>',
			esc_html__( 'Plugins', 'cloudfest-wporgdownload' ),
			isset( $plugin_count->publish ) ? (int) $plugin_count->publish : 0,
			esc_html__( 'Themes', 'cloudfest-wporgdownload' ),
			isset( $theme_count->publish ) ? (int) $theme_count->publish : 0
		);
	}

	/**
	 * Check API connection.
	 *
	 * @since 0.1.0
	 * @return string HTML status message.
	 */
	private static function check_api_connection(): string {
		$is_accessible = WPInsight_WPOrg_Client::is_api_accessible();

		if ( $is_accessible ) {
			return '<span style="color: green;">✓ ' . esc_html__( 'API accessible', 'cloudfest-wporgdownload' ) . '</span>';
		}

		return '<span style="color: red;">✗ ' . esc_html__( 'API not accessible', 'cloudfest-wporgdownload' ) . '</span>';
	}

	/**
	 * Handle debug action.
	 *
	 * @since 0.1.0
	 * @param string $action Action to perform.
	 * @return void
	 */
	private static function handle_debug_action( string $action ): void {
		switch ( $action ) {
			case 'check_db':
				// Database check is always fresh, just show notice.
				add_settings_error(
					'wpinsight_debug',
					'db_checked',
					__( 'Database tables verified.', 'cloudfest-wporgdownload' ),
					'success'
				);
				break;

			case 'reset_jobs':
				// Unschedule all existing jobs.
				if ( function_exists( 'as_unschedule_all_actions' ) ) {
					as_unschedule_all_actions( WPINSIGHT_SYNC_TICK_ACTION, [], WPINSIGHT_AS_GROUP );
					as_unschedule_all_actions( WPINSIGHT_ZIP_WORKER_TICK_ACTION, [], WPINSIGHT_AS_GROUP );

					add_settings_error(
						'wpinsight_debug',
						'jobs_reset',
						__( 'All scheduled jobs have been unscheduled. Jobs will be rescheduled automatically on next plugin load or you can deactivate/reactivate the plugin.', 'cloudfest-wporgdownload' ),
						'success'
					);
				}
				break;

			case 'test_api':
				$is_accessible = WPInsight_WPOrg_Client::is_api_accessible();
				if ( $is_accessible ) {
					add_settings_error(
						'wpinsight_debug',
						'api_test',
						__( 'WordPress.org API is accessible and responding correctly.', 'cloudfest-wporgdownload' ),
						'success'
					);
				} else {
					add_settings_error(
						'wpinsight_debug',
						'api_test',
						__( 'WordPress.org API is not accessible. Check your network connection and firewall settings.', 'cloudfest-wporgdownload' ),
						'error'
					);
				}
				break;
		}
	}

	/**
	 * Render general settings section description.
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
		$sanitized = [];

		// Convert checkbox values: empty strings become false, '1' becomes true.
		$checkboxes = [
			'delete_on_uninstall',
			'auto_sync_enabled',
			'sync_plugins_enabled',
			'sync_themes_enabled',
			'download_plugin_zips_enabled',
			'download_theme_zips_enabled',
			'admin_email_notifications_enabled', // v1.1.0+.
		];
		foreach ( $checkboxes as $key ) {
			$sanitized[ $key ] = isset( $input[ $key ] ) && '1' === $input[ $key ];
		}

		// Validate and sanitize number fields using Settings validation only.
		// IMPORTANT: Do NOT call WPInsight_Settings::update() here as it causes infinite loop.
		$number_fields = [
			'max_concurrent_downloads',
			'sync_interval',
			'zip_worker_interval',
			'max_retries',
			'log_retention_days',
		];

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
					[
						'path'  => $path,
						'error' => $e->getMessage(),
					]
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
		$output       = [];
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
}
