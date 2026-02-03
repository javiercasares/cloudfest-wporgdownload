<?php
/**
 * WordPress.org API Client Tests
 *
 * Unit tests for the WPInsight_WPOrg_Client class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for WordPress.org API Client class.
 *
 * @since 0.1.0
 */
class WPOrgClientTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load the WPOrg Client class.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-wporg-client.php';
	}

	/**
	 * Test that WPOrg Client class exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_wporg_client_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_WPOrg_Client' ),
			'WPInsight_WPOrg_Client class should exist'
		);
	}

	/**
	 * Test that WPOrg Client class is final.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_wporg_client_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_WPOrg_Client' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_WPOrg_Client class should be final'
		);
	}

	/**
	 * Test that query_plugins method exists and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_query_plugins_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_WPOrg_Client', 'query_plugins' ),
			'query_plugins() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_WPOrg_Client', 'query_plugins' );
		$this->assertTrue(
			$reflection->isStatic(),
			'query_plugins() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'query_plugins() method should be public'
		);
	}

	/**
	 * Test that get_plugin_info method exists and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_plugin_info_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_WPOrg_Client', 'get_plugin_info' ),
			'get_plugin_info() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_WPOrg_Client', 'get_plugin_info' );
		$this->assertTrue(
			$reflection->isStatic(),
			'get_plugin_info() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'get_plugin_info() method should be public'
		);
	}

	/**
	 * Test that query_themes method exists and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_query_themes_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_WPOrg_Client', 'query_themes' ),
			'query_themes() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_WPOrg_Client', 'query_themes' );
		$this->assertTrue(
			$reflection->isStatic(),
			'query_themes() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'query_themes() method should be public'
		);
	}

	/**
	 * Test that get_theme_info method exists and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_theme_info_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_WPOrg_Client', 'get_theme_info' ),
			'get_theme_info() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_WPOrg_Client', 'get_theme_info' );
		$this->assertTrue(
			$reflection->isStatic(),
			'get_theme_info() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'get_theme_info() method should be public'
		);
	}

	/**
	 * Test that get_plugin_info returns false for empty slug.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_plugin_info_rejects_empty_slug(): void {
		$result = WPInsight_WPOrg_Client::get_plugin_info( '' );

		$this->assertFalse(
			$result,
			'get_plugin_info() should return false for empty slug'
		);
	}

	/**
	 * Test that get_theme_info returns false for empty slug.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_theme_info_rejects_empty_slug(): void {
		$result = WPInsight_WPOrg_Client::get_theme_info( '' );

		$this->assertFalse(
			$result,
			'get_theme_info() should return false for empty slug'
		);
	}

	/**
	 * Test that get_plugin_download_url returns a string.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_plugin_download_url_returns_string(): void {
		$url = WPInsight_WPOrg_Client::get_plugin_download_url( 'test-plugin', '1.0.0' );

		$this->assertIsString(
			$url,
			'get_plugin_download_url() should return a string'
		);

		$this->assertStringContainsString(
			'downloads.wordpress.org',
			$url,
			'URL should contain downloads.wordpress.org'
		);

		$this->assertStringContainsString(
			'test-plugin',
			$url,
			'URL should contain plugin slug'
		);

		$this->assertStringContainsString(
			'1.0.0',
			$url,
			'URL should contain version'
		);

		$this->assertStringEndsWith(
			'.zip',
			$url,
			'URL should end with .zip'
		);
	}

	/**
	 * Test that get_theme_download_url returns a string.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_theme_download_url_returns_string(): void {
		$url = WPInsight_WPOrg_Client::get_theme_download_url( 'test-theme', '1.0.0' );

		$this->assertIsString(
			$url,
			'get_theme_download_url() should return a string'
		);

		$this->assertStringContainsString(
			'downloads.wordpress.org',
			$url,
			'URL should contain downloads.wordpress.org'
		);

		$this->assertStringContainsString(
			'test-theme',
			$url,
			'URL should contain theme slug'
		);

		$this->assertStringContainsString(
			'1.0.0',
			$url,
			'URL should contain version'
		);

		$this->assertStringEndsWith(
			'.zip',
			$url,
			'URL should end with .zip'
		);
	}

	/**
	 * Test that is_api_accessible method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_is_api_accessible_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_WPOrg_Client', 'is_api_accessible' ),
			'is_api_accessible() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_WPOrg_Client', 'is_api_accessible' );
		$this->assertTrue(
			$reflection->isStatic(),
			'is_api_accessible() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'is_api_accessible() method should be public'
		);
	}

	/**
	 * Test that query_plugins uses default arguments.
	 *
	 * Verifies that calling query_plugins() without arguments doesn't throw
	 * errors. In the test environment, this will return false because
	 * wp_remote_post is stubbed, but we're testing the method signature.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_query_plugins_uses_defaults(): void {
		$result = WPInsight_WPOrg_Client::query_plugins();

		// In test environment, should return false because wp_remote_post is stubbed.
		$this->assertFalse(
			$result,
			'query_plugins() should return false in test environment'
		);
	}

	/**
	 * Test that query_themes uses default arguments.
	 *
	 * Verifies that calling query_themes() without arguments doesn't throw
	 * errors. In the test environment, this will return false because
	 * wp_remote_post is stubbed, but we're testing the method signature.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_query_themes_uses_defaults(): void {
		$result = WPInsight_WPOrg_Client::query_themes();

		// In test environment, should return false because wp_remote_post is stubbed.
		$this->assertFalse(
			$result,
			'query_themes() should return false in test environment'
		);
	}

	/**
	 * Test that all required methods exist and are static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_all_methods_exist_and_are_static(): void {
		$methods = [
			'query_plugins',
			'get_plugin_info',
			'query_themes',
			'get_theme_info',
			'get_plugin_download_url',
			'get_theme_download_url',
			'is_api_accessible',
		];

		foreach ( $methods as $method ) {
			$this->assertTrue(
				method_exists( 'WPInsight_WPOrg_Client', $method ),
				"{$method}() method should exist"
			);

			$reflection = new ReflectionMethod( 'WPInsight_WPOrg_Client', $method );
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
