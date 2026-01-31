<?php
/**
 * Custom Post Types Class Tests
 *
 * Unit tests for the WPInsight_CPT class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for CPT class.
 *
 * @since 0.1.0
 */
class CPTTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load the CPT class.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-cpt.php';
	}

	/**
	 * Test that CPT class exists.
	 *
	 * Verifies that the WPInsight_CPT class is loaded and can be referenced.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_cpt_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_CPT' ),
			'WPInsight_CPT class should exist'
		);
	}

	/**
	 * Test that CPT class is final.
	 *
	 * Verifies that the CPT class is declared as final to prevent inheritance.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_cpt_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_CPT' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_CPT class should be final'
		);
	}

	/**
	 * Test that get_plugin_post_type returns a string.
	 *
	 * Verifies that the plugin post type name is a non-empty string.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_plugin_post_type_returns_string(): void {
		$post_type = WPInsight_CPT::get_plugin_post_type();

		$this->assertIsString(
			$post_type,
			'Plugin post type should be a string'
		);

		$this->assertNotEmpty(
			$post_type,
			'Plugin post type should not be empty'
		);

		$this->assertStringContainsString(
			'wpinsight',
			$post_type,
			'Plugin post type should contain wpinsight prefix'
		);
	}

	/**
	 * Test that get_theme_post_type returns a string.
	 *
	 * Verifies that the theme post type name is a non-empty string.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_theme_post_type_returns_string(): void {
		$post_type = WPInsight_CPT::get_theme_post_type();

		$this->assertIsString(
			$post_type,
			'Theme post type should be a string'
		);

		$this->assertNotEmpty(
			$post_type,
			'Theme post type should not be empty'
		);

		$this->assertStringContainsString(
			'wpinsight',
			$post_type,
			'Theme post type should contain wpinsight prefix'
		);
	}

	/**
	 * Test that plugin and theme post types are different.
	 *
	 * Verifies that the two post types have different names.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_post_types_are_different(): void {
		$plugin_type = WPInsight_CPT::get_plugin_post_type();
		$theme_type  = WPInsight_CPT::get_theme_post_type();

		$this->assertNotEquals(
			$plugin_type,
			$theme_type,
			'Plugin and theme post types should be different'
		);
	}

	/**
	 * Test that register method exists and is static.
	 *
	 * Verifies that the register() method exists, is public, and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_register_method_exists_and_is_static(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_CPT', 'register' ),
			'register() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_CPT', 'register' );
		$this->assertTrue(
			$reflection->isStatic(),
			'register() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'register() method should be public'
		);
	}

	/**
	 * Test that register method can be called.
	 *
	 * Verifies that calling register() doesn't throw any exceptions.
	 * Note: This test won't actually register CPTs since WordPress functions
	 * are stubs in the test environment.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_register_can_be_called(): void {
		// Should not throw any exceptions.
		$this->expectNotToPerformAssertions();
		WPInsight_CPT::register();
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
			'register',
			'get_plugin_post_type',
			'get_theme_post_type',
		);

		foreach ( $methods as $method ) {
			$this->assertTrue(
				method_exists( 'WPInsight_CPT', $method ),
				"{$method}() method should exist"
			);

			$reflection = new ReflectionMethod( 'WPInsight_CPT', $method );
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
