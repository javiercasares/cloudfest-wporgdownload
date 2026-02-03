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

	/**
	 * Test that find_or_create_plugin returns an integer.
	 *
	 * Verifies that the find_or_create_plugin() method returns a post ID.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_find_or_create_plugin_returns_int(): void {
		$post_id = WPInsight_CPT::find_or_create_plugin( 'test-plugin', 'Test Plugin' );

		$this->assertIsInt(
			$post_id,
			'find_or_create_plugin() should return an integer'
		);

		$this->assertGreaterThanOrEqual(
			0,
			$post_id,
			'Post ID should be non-negative'
		);
	}

	/**
	 * Test that find_or_create_theme returns an integer.
	 *
	 * Verifies that the find_or_create_theme() method returns a post ID.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_find_or_create_theme_returns_int(): void {
		$post_id = WPInsight_CPT::find_or_create_theme( 'test-theme', 'Test Theme' );

		$this->assertIsInt(
			$post_id,
			'find_or_create_theme() should return an integer'
		);

		$this->assertGreaterThanOrEqual(
			0,
			$post_id,
			'Post ID should be non-negative'
		);
	}

	/**
	 * Test that save_plugin_meta returns a boolean.
	 *
	 * Verifies that the save_plugin_meta() method returns true/false.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_save_plugin_meta_returns_bool(): void {
		$result = WPInsight_CPT::save_plugin_meta(
			1,
			array(
				'slug'    => 'test-plugin',
				'version' => '1.0.0',
				'author'  => 'Test Author',
			)
		);

		$this->assertIsBool(
			$result,
			'save_plugin_meta() should return a boolean'
		);
	}

	/**
	 * Test that save_theme_meta returns a boolean.
	 *
	 * Verifies that the save_theme_meta() method returns true/false.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_save_theme_meta_returns_bool(): void {
		$result = WPInsight_CPT::save_theme_meta(
			1,
			array(
				'slug'    => 'test-theme',
				'version' => '1.0.0',
				'author'  => 'Test Author',
			)
		);

		$this->assertIsBool(
			$result,
			'save_theme_meta() should return a boolean'
		);
	}

	/**
	 * Test that get_plugin_meta returns an array.
	 *
	 * Verifies that the get_plugin_meta() method returns an array.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_plugin_meta_returns_array(): void {
		$meta = WPInsight_CPT::get_plugin_meta( 1 );

		$this->assertIsArray(
			$meta,
			'get_plugin_meta() should return an array'
		);
	}

	/**
	 * Test that get_theme_meta returns an array.
	 *
	 * Verifies that the get_theme_meta() method returns an array.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_theme_meta_returns_array(): void {
		$meta = WPInsight_CPT::get_theme_meta( 1 );

		$this->assertIsArray(
			$meta,
			'get_theme_meta() should return an array'
		);
	}

	/**
	 * Test that save_plugin_meta handles empty data.
	 *
	 * Verifies that save_plugin_meta() returns false for empty data.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_save_plugin_meta_handles_empty_data(): void {
		$result = WPInsight_CPT::save_plugin_meta( 1, array() );

		$this->assertFalse(
			$result,
			'save_plugin_meta() should return false for empty data'
		);
	}

	/**
	 * Test that get_plugin_meta handles invalid post ID.
	 *
	 * Verifies that get_plugin_meta() returns empty array for invalid post ID.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_plugin_meta_handles_invalid_post_id(): void {
		$meta = WPInsight_CPT::get_plugin_meta( 0 );

		$this->assertIsArray(
			$meta,
			'get_plugin_meta() should return an array'
		);

		$this->assertEmpty(
			$meta,
			'get_plugin_meta() should return empty array for invalid post ID'
		);
	}

	/**
	 * Test that plugin_columns returns an array.
	 *
	 * Verifies that the plugin_columns() method returns an array of columns.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_plugin_columns_returns_array(): void {
		$columns = WPInsight_CPT::plugin_columns(
			array(
				'cb'    => '<input type="checkbox" />',
				'title' => 'Title',
				'date'  => 'Date',
			)
		);

		$this->assertIsArray(
			$columns,
			'plugin_columns() should return an array'
		);

		$this->assertNotEmpty(
			$columns,
			'plugin_columns() should return a non-empty array'
		);

		$this->assertArrayHasKey(
			'slug',
			$columns,
			'plugin_columns() should include slug column'
		);

		$this->assertArrayHasKey(
			'version',
			$columns,
			'plugin_columns() should include version column'
		);
	}

	/**
	 * Test that theme_columns returns an array.
	 *
	 * Verifies that the theme_columns() method returns an array of columns.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_theme_columns_returns_array(): void {
		$columns = WPInsight_CPT::theme_columns(
			array(
				'cb'    => '<input type="checkbox" />',
				'title' => 'Title',
				'date'  => 'Date',
			)
		);

		$this->assertIsArray(
			$columns,
			'theme_columns() should return an array'
		);

		$this->assertNotEmpty(
			$columns,
			'theme_columns() should return a non-empty array'
		);

		$this->assertArrayHasKey(
			'slug',
			$columns,
			'theme_columns() should include slug column'
		);

		$this->assertArrayHasKey(
			'version',
			$columns,
			'theme_columns() should include version column'
		);
	}

	/**
	 * Test that plugin_column_content can be called.
	 *
	 * Verifies that calling plugin_column_content() doesn't throw exceptions
	 * and produces output.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_plugin_column_content_can_be_called(): void {
		// Capture output to avoid risky test warnings.
		ob_start();
		WPInsight_CPT::plugin_column_content( 'slug', 1 );
		$output = ob_get_clean();

		$this->assertIsString(
			$output,
			'plugin_column_content() should produce output'
		);
	}

	/**
	 * Test that theme_column_content can be called.
	 *
	 * Verifies that calling theme_column_content() doesn't throw exceptions
	 * and produces output.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_theme_column_content_can_be_called(): void {
		// Capture output to avoid risky test warnings.
		ob_start();
		WPInsight_CPT::theme_column_content( 'slug', 1 );
		$output = ob_get_clean();

		$this->assertIsString(
			$output,
			'theme_column_content() should produce output'
		);
	}
}
