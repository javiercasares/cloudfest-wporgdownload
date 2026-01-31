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
	}

	/**
	 * Add admin menu items.
	 *
	 * Adds a settings page under the WordPress Settings menu.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function add_admin_menu(): void {
		add_options_page(
			__( 'WPInsight Settings', 'cloudfest-wporgdownload' ), // Page title.
			__( 'WPInsight', 'cloudfest-wporgdownload' ),           // Menu title.
			'manage_options',                                        // Capability.
			self::SETTINGS_PAGE_SLUG,                                // Menu slug.
			[ __CLASS__, 'render_settings_page' ]               // Callback.
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
	 * Sanitize settings before saving.
	 *
	 * Validates and sanitizes all settings using the Settings class validation.
	 * Invalid values will be rejected and the field will keep its previous value.
	 *
	 * @since 0.1.0
	 * @param array $input Raw input from settings form.
	 * @return array Sanitized settings.
	 */
	public static function sanitize_settings( array $input ): array {
		$sanitized = [];

		// Convert checkbox values (empty = false, '1' = true).
		$checkboxes = [
			'delete_on_uninstall',
			'auto_sync_enabled',
			'sync_plugins_enabled',
			'sync_themes_enabled',
			'download_plugin_zips_enabled',
			'download_theme_zips_enabled',
		];
		foreach ( $checkboxes as $key ) {
			$sanitized[ $key ] = isset( $input[ $key ] ) && '1' === $input[ $key ];
		}

		// Validate and sanitize number fields using Settings class.
		$number_fields = [
			'max_concurrent_downloads',
			'sync_interval',
			'zip_worker_interval',
			'max_retries',
		];

		foreach ( $number_fields as $key ) {
			if ( isset( $input[ $key ] ) ) {
				// Try to validate using Settings class.
				try {
					$validated_value = (int) $input[ $key ];
					// Use update to trigger validation.
					if ( WPInsight_Settings::update( $key, $validated_value ) ) {
						$sanitized[ $key ] = $validated_value;
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
				} catch ( Exception $e ) {
					// Keep current value on exception.
					$sanitized[ $key ] = WPInsight_Settings::get( $key );
				}
			}
		}

		return $sanitized;
	}
}
