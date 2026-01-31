<?php
/**
 * Settings Management Class
 *
 * Handles plugin settings storage, retrieval, validation, and defaults.
 *
 * All settings are stored in a single WordPress option (wpinsight_settings)
 * for performance. Settings are validated before saving to ensure data integrity.
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
 * Settings management class.
 *
 * This class uses static methods only and is never instantiated.
 * Provides a clean API for getting, setting, and validating plugin settings.
 *
 * @since 0.1.0
 */
final class WPInsight_Settings {

	/**
	 * Get the WordPress option name used for settings storage.
	 *
	 * Returns the option name constant used to store all plugin settings
	 * in the WordPress options table.
	 *
	 * @since 0.1.0
	 * @return string Option name (e.g., 'wpinsight_settings').
	 */
	public static function get_option_name(): string {
		return WPINSIGHT_SETTINGS_OPTION;
	}

	/**
	 * Get default settings values.
	 *
	 * Returns an array of all default settings with their default values.
	 * These are used when settings haven't been saved yet or when resetting.
	 *
	 * @since 0.1.0
	 * @return array<string,mixed> Associative array of default settings.
	 */
	public static function get_defaults(): array {
		return [
			// Data management.
			'delete_on_uninstall'          => false, // Preserve data by default.

			// Rate limiting - CRITICAL for WordPress.org.
			'max_concurrent_downloads'     => 3, // Max 3 concurrent downloads to avoid bans.

			// Scheduling intervals (in seconds).
			'sync_interval'                => 300, // 5 minutes - sync check frequency.
			'zip_worker_interval'          => 60,  // 1 minute - ZIP download worker frequency.

			// API pagination.
			'per_page'                     => 100, // Items per page from WordPress.org API.

			// Retry logic.
			'max_retries'                  => 3,   // Maximum download retry attempts.

			// Storage paths (relative to wp-content/uploads/).
			'storage_base_path'            => 'wpinsight',

			// Sync behavior.
			'auto_sync_enabled'            => true, // Enable automatic background sync.
			'sync_plugins_enabled'         => true, // Sync plugins.
			'sync_themes_enabled'          => true,  // Sync themes metadata.

			// Download behavior.
			'download_plugin_zips_enabled' => true,  // Download plugin ZIP files.
			'download_theme_zips_enabled'  => true,  // Download theme ZIP files.
		];
	}

	/**
	 * Get a single setting value.
	 *
	 * Retrieves the value of a specific setting. If the setting is not found,
	 * returns the provided default value or the default from get_defaults().
	 *
	 * @since 0.1.0
	 * @param string $key           Setting key to retrieve.
	 * @param mixed  $default_value Default value if setting not found (optional).
	 * @return mixed Setting value or default.
	 */
	public static function get( string $key, $default_value = null ) {
		$settings = self::get_all();

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		// Return provided default or null.
		return $default_value;
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * Retrieves all stored settings from the database and merges them with
	 * default values. Any missing settings will use their default values.
	 *
	 * @since 0.1.0
	 * @return array<string,mixed> All settings merged with defaults.
	 */
	public static function get_all(): array {
		$stored   = get_option( WPINSIGHT_SETTINGS_OPTION, [] );
		$defaults = self::get_defaults();

		// Merge stored settings over defaults.
		return array_merge( $defaults, $stored );
	}

	/**
	 * Update a single setting value.
	 *
	 * Updates one setting after validating the value. If validation fails,
	 * returns false and does not save.
	 *
	 * @since 0.1.0
	 * @param string $key   Setting key to update.
	 * @param mixed  $value New value for the setting.
	 * @return bool True on success, false on failure.
	 */
	public static function update( string $key, $value ): bool {
		try {
			// Validate the value.
			$validated_value = self::validate( $key, $value );

			// Get current settings.
			$settings = self::get_all();

			// Update the specific key.
			$settings[ $key ] = $validated_value;

			// Save to database.
			return update_option( WPINSIGHT_SETTINGS_OPTION, $settings );

		} catch ( InvalidArgumentException $e ) {
			// Validation failed - return false.
			return false;
		}
	}

	/**
	 * Update multiple settings at once.
	 *
	 * Updates multiple settings after validating all values. If any validation
	 * fails, no settings are updated and false is returned.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $new_settings Associative array of settings to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update_all( array $new_settings ): bool {
		try {
			// Validate all values first (fail fast).
			$validated = [];
			foreach ( $new_settings as $key => $value ) {
				$validated[ $key ] = self::validate( $key, $value );
			}

			// Get current settings.
			$settings = self::get_all();

			// Merge validated settings.
			$settings = array_merge( $settings, $validated );

			// Save to database.
			return update_option( WPINSIGHT_SETTINGS_OPTION, $settings );

		} catch ( InvalidArgumentException $e ) {
			// Validation failed - return false.
			return false;
		}
	}

	/**
	 * Delete all plugin settings.
	 *
	 * Removes the settings option from the database. This is used during
	 * plugin uninstall if the user opts in to data deletion.
	 *
	 * @since 0.1.0
	 * @return bool True on success, false on failure.
	 */
	public static function delete(): bool {
		return delete_option( WPINSIGHT_SETTINGS_OPTION );
	}

	/**
	 * Validate and sanitize a setting value.
	 *
	 * Performs type checking and range validation on setting values.
	 * Throws InvalidArgumentException if validation fails.
	 *
	 * @since 0.1.0
	 * @param string $key   Setting key.
	 * @param mixed  $value Value to validate.
	 * @return mixed Validated and sanitized value.
	 * @throws InvalidArgumentException If validation fails.
	 */
	private static function validate( string $key, $value ) {
		switch ( $key ) {
			case 'delete_on_uninstall':
			case 'auto_sync_enabled':
			case 'sync_plugins_enabled':
			case 'sync_themes_enabled':
			case 'download_plugin_zips_enabled':
			case 'download_theme_zips_enabled':
				// Boolean settings.
				if ( ! is_bool( $value ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not user output.
					throw new InvalidArgumentException( "Setting '{$key}' must be a boolean" );
				}
				return $value;

			case 'max_concurrent_downloads':
				// Integer: 1-5 range (rate limiting).
				$value = filter_var( $value, FILTER_VALIDATE_INT );
				if ( false === $value || $value < 1 || $value > 5 ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not user output.
					throw new InvalidArgumentException( "Setting '{$key}' must be an integer between 1 and 5" );
				}
				return $value;

			case 'sync_interval':
				// Integer: minimum 60 seconds.
				$value = filter_var( $value, FILTER_VALIDATE_INT );
				if ( false === $value || $value < 60 ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not user output.
					throw new InvalidArgumentException( "Setting '{$key}' must be an integer >= 60 seconds" );
				}
				return $value;

			case 'zip_worker_interval':
				// Integer: minimum 30 seconds.
				$value = filter_var( $value, FILTER_VALIDATE_INT );
				if ( false === $value || $value < 30 ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not user output.
					throw new InvalidArgumentException( "Setting '{$key}' must be an integer >= 30 seconds" );
				}
				return $value;

			case 'per_page':
				// Integer: 1-250 range (API pagination).
				$value = filter_var( $value, FILTER_VALIDATE_INT );
				if ( false === $value || $value < 1 || $value > 250 ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not user output.
					throw new InvalidArgumentException( "Setting '{$key}' must be an integer between 1 and 250" );
				}
				return $value;

			case 'max_retries':
				// Integer: 1-10 range.
				$value = filter_var( $value, FILTER_VALIDATE_INT );
				if ( false === $value || $value < 1 || $value > 10 ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not user output.
					throw new InvalidArgumentException( "Setting '{$key}' must be an integer between 1 and 10" );
				}
				return $value;

			case 'storage_base_path':
				// String: sanitize file path.
				$value = sanitize_file_name( $value );
				if ( empty( $value ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not user output.
					throw new InvalidArgumentException( "Setting '{$key}' must be a non-empty string" );
				}
				return $value;

			default:
				// Unknown setting - accept as-is but sanitize if string.
				if ( is_string( $value ) ) {
					return sanitize_text_field( $value );
				}
				return $value;
		}
	}
}
