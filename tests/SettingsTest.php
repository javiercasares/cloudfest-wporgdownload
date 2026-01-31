<?php
/**
 * Settings Class Tests
 *
 * Unit tests for the WPInsight_Settings class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Settings class.
 *
 * @since 0.1.0
 */
class SettingsTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load the settings class.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-settings.php';
	}

	/**
	 * Test that settings class exists.
	 *
	 * Verifies that the WPInsight_Settings class is loaded and can be
	 * referenced.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_settings_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_Settings' ),
			'WPInsight_Settings class should exist'
		);
	}

	/**
	 * Test that settings class is final.
	 *
	 * Verifies that the settings class is declared as final to prevent
	 * inheritance.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_settings_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_Settings' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_Settings class should be final'
		);
	}

	/**
	 * Test that get_option_name returns a string.
	 *
	 * Verifies that the option name is a non-empty string.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_option_name_returns_string(): void {
		$option_name = WPInsight_Settings::get_option_name();

		$this->assertIsString(
			$option_name,
			'Option name should be a string'
		);

		$this->assertNotEmpty(
			$option_name,
			'Option name should not be empty'
		);

		$this->assertStringContainsString(
			'wpinsight',
			$option_name,
			'Option name should contain wpinsight prefix'
		);
	}

	/**
	 * Test that get_defaults returns an array.
	 *
	 * Verifies that default settings are returned as an associative array
	 * with expected keys.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_defaults_returns_array(): void {
		$defaults = WPInsight_Settings::get_defaults();

		$this->assertIsArray(
			$defaults,
			'Defaults should be an array'
		);

		$this->assertNotEmpty(
			$defaults,
			'Defaults should not be empty'
		);

		// Check for required keys.
		$required_keys = array(
			'delete_on_uninstall',
			'max_concurrent_downloads',
			'sync_interval',
			'zip_worker_interval',
			'per_page',
			'max_retries',
		);

		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey(
				$key,
				$defaults,
				"Defaults should have key: {$key}"
			);
		}
	}

	/**
	 * Test that get_all returns merged defaults.
	 *
	 * Verifies that get_all() returns defaults when no settings are stored.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_all_returns_merged_defaults(): void {
		$all = WPInsight_Settings::get_all();

		$this->assertIsArray(
			$all,
			'get_all() should return an array'
		);

		// Should have at least the default keys.
		$defaults = WPInsight_Settings::get_defaults();
		foreach ( array_keys( $defaults ) as $key ) {
			$this->assertArrayHasKey(
				$key,
				$all,
				"get_all() should have default key: {$key}"
			);
		}
	}

	/**
	 * Test that get returns default if not set.
	 *
	 * Verifies that get() returns the default value from get_defaults()
	 * when a setting hasn't been explicitly saved.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_returns_default_if_not_set(): void {
		$defaults = WPInsight_Settings::get_defaults();

		// Test a few known defaults.
		$this->assertEquals(
			$defaults['max_concurrent_downloads'],
			WPInsight_Settings::get( 'max_concurrent_downloads' ),
			'Should return default value for max_concurrent_downloads'
		);

		$this->assertEquals(
			$defaults['delete_on_uninstall'],
			WPInsight_Settings::get( 'delete_on_uninstall' ),
			'Should return default value for delete_on_uninstall'
		);
	}

	/**
	 * Test that get returns custom default when provided.
	 *
	 * Verifies that get() returns the custom default parameter when a
	 * setting doesn't exist.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_returns_custom_default_for_unknown_key(): void {
		$custom_default = 'custom_value';
		$value          = WPInsight_Settings::get( 'nonexistent_key', $custom_default );

		$this->assertEquals(
			$custom_default,
			$value,
			'Should return custom default for unknown key'
		);
	}

	/**
	 * Test default value types are correct.
	 *
	 * Verifies that all default values have the expected types.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_default_value_types(): void {
		$defaults = WPInsight_Settings::get_defaults();

		// Boolean defaults.
		$this->assertIsBool( $defaults['delete_on_uninstall'], 'delete_on_uninstall should be boolean' );
		$this->assertIsBool( $defaults['auto_sync_enabled'], 'auto_sync_enabled should be boolean' );

		// Integer defaults.
		$this->assertIsInt( $defaults['max_concurrent_downloads'], 'max_concurrent_downloads should be integer' );
		$this->assertIsInt( $defaults['sync_interval'], 'sync_interval should be integer' );
		$this->assertIsInt( $defaults['zip_worker_interval'], 'zip_worker_interval should be integer' );
		$this->assertIsInt( $defaults['per_page'], 'per_page should be integer' );
		$this->assertIsInt( $defaults['max_retries'], 'max_retries should be integer' );

		// String defaults.
		$this->assertIsString( $defaults['storage_base_path'], 'storage_base_path should be string' );
	}

	/**
	 * Test critical default values are correct.
	 *
	 * Verifies that critical settings have safe default values.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_critical_default_values(): void {
		$defaults = WPInsight_Settings::get_defaults();

		// Data preservation - should default to false (preserve data).
		$this->assertFalse(
			$defaults['delete_on_uninstall'],
			'delete_on_uninstall should default to false (preserve data)'
		);

		// Rate limiting - critical for WordPress.org.
		$this->assertEquals(
			3,
			$defaults['max_concurrent_downloads'],
			'max_concurrent_downloads should default to 3'
		);

		$this->assertLessThanOrEqual(
			5,
			$defaults['max_concurrent_downloads'],
			'max_concurrent_downloads should not exceed 5'
		);

		// Sync and download settings should be enabled by default.
		$this->assertTrue(
			$defaults['sync_plugins_enabled'],
			'sync_plugins_enabled should default to true'
		);

		$this->assertTrue(
			$defaults['sync_themes_enabled'],
			'sync_themes_enabled should default to true'
		);

		$this->assertTrue(
			$defaults['download_plugin_zips_enabled'],
			'download_plugin_zips_enabled should default to true'
		);

		$this->assertTrue(
			$defaults['download_theme_zips_enabled'],
			'download_theme_zips_enabled should default to true'
		);
	}

	/**
	 * Test that all required methods exist and are static.
	 *
	 * Verifies that all public methods exist and are properly defined as static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_all_methods_exist_and_are_static(): void {
		$methods = array(
			'get_option_name',
			'get_defaults',
			'get',
			'get_all',
			'update',
			'update_all',
			'delete',
		);

		foreach ( $methods as $method ) {
			$this->assertTrue(
				method_exists( 'WPInsight_Settings', $method ),
				"{$method}() method should exist"
			);

			$reflection = new ReflectionMethod( 'WPInsight_Settings', $method );
			$this->assertTrue(
				$reflection->isStatic(),
				"{$method}() method should be static"
			);
			$this->assertTrue(
				$reflection->isPublic(),
				"{$method}() method should be public"
			);
		}
	}
}
