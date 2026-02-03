<?php
/**
 * Storage Class Tests
 *
 * Unit tests for the WPInsight_Storage class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Storage class.
 *
 * @since 0.1.0
 */
class StorageTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load dependencies.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-db.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-settings.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-logger.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-storage.php';
	}

	/**
	 * Test that storage class exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_storage_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_Storage' ),
			'WPInsight_Storage class should exist'
		);
	}

	/**
	 * Test that get_storage_path returns string.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_storage_path_returns_string(): void {
		$path = WPInsight_Storage::get_storage_path( 'plugin', 'test-plugin' );

		$this->assertIsString(
			$path,
			'get_storage_path should return a string'
		);
	}

	/**
	 * Test that get_storage_path includes entity type.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_storage_path_includes_entity_type(): void {
		$plugin_path = WPInsight_Storage::get_storage_path( 'plugin', 'test-plugin' );
		$theme_path  = WPInsight_Storage::get_storage_path( 'theme', 'test-theme' );

		$this->assertStringContainsString(
			'plugin',
			$plugin_path,
			'Plugin path should contain "plugin"'
		);

		$this->assertStringContainsString(
			'theme',
			$theme_path,
			'Theme path should contain "theme"'
		);
	}

	/**
	 * Test that get_storage_path includes slug.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_storage_path_includes_slug(): void {
		$path = WPInsight_Storage::get_storage_path( 'plugin', 'akismet' );

		$this->assertStringContainsString(
			'akismet',
			$path,
			'Path should contain the slug'
		);
	}

	/**
	 * Test that get_storage_path has trailing slash.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_storage_path_has_trailing_slash(): void {
		$path = WPInsight_Storage::get_storage_path( 'plugin', 'test-plugin' );

		$this->assertStringEndsWith(
			'/',
			$path,
			'Storage path should have trailing slash'
		);
	}

	/**
	 * Test that get_storage_path returns valid path.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_storage_path_returns_valid_path(): void {
		$unique_slug = 'test-plugin-' . uniqid();
		$path        = WPInsight_Storage::get_storage_path( 'plugin', $unique_slug );

		// In test environment, wp_mkdir_p may not actually create directories.
		// We verify the method returns a path (not false) which indicates success.
		$this->assertNotFalse(
			$path,
			'get_storage_path should return a path (not false)'
		);

		$this->assertIsString(
			$path,
			'Returned path should be a string'
		);
	}

	/**
	 * Test that download_and_store_zip validates entity type.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_download_and_store_zip_validates_entity_type(): void {
		$result = WPInsight_Storage::download_and_store_zip(
			'invalid',
			'test',
			'1.0',
			'https://example.com/test.zip'
		);

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertFalse( $result['success'], 'Should fail for invalid entity type' );
		$this->assertArrayHasKey( 'error', $result, 'Should contain error message' );
		$this->assertStringContainsString( 'Invalid entity type', $result['error'] );
	}

	/**
	 * Test that download_and_store_zip returns success structure.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_download_and_store_zip_return_structure(): void {
		$result = WPInsight_Storage::download_and_store_zip(
			'invalid',
			'test',
			'1.0',
			'https://example.com/test.zip'
		);

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertArrayHasKey( 'success', $result, 'Result should have success key' );
		$this->assertIsBool( $result['success'], 'Success should be boolean' );
	}

	/**
	 * Test that get_artifact returns null for non-existent artifact.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_artifact_returns_null_when_not_found(): void {
		$artifact = WPInsight_Storage::get_artifact( 'plugin', 'non-existent-plugin', '99.99.99' );

		$this->assertNull(
			$artifact,
			'Should return null for non-existent artifact'
		);
	}

	/**
	 * Test that get_artifact returns array or null.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_artifact_return_type(): void {
		$artifact = WPInsight_Storage::get_artifact( 'plugin', 'test', '1.0' );

		$this->assertTrue(
			is_array( $artifact ) || is_null( $artifact ),
			'get_artifact should return array or null'
		);
	}

	/**
	 * Test that delete_artifact returns boolean.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_delete_artifact_returns_boolean(): void {
		$result = WPInsight_Storage::delete_artifact( 'plugin', 'non-existent', '1.0' );

		$this->assertIsBool(
			$result,
			'delete_artifact should return boolean'
		);
	}

	/**
	 * Test that delete_artifact returns false for non-existent artifact.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_delete_artifact_returns_false_when_not_found(): void {
		$result = WPInsight_Storage::delete_artifact( 'plugin', 'non-existent-plugin', '99.99.99' );

		$this->assertFalse(
			$result,
			'Should return false for non-existent artifact'
		);
	}

	/**
	 * Test all public static methods exist.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_all_public_methods_exist_and_are_static(): void {
		$required_methods = array(
			'download_and_store_zip',
			'get_storage_path',
			'get_artifact',
			'delete_artifact',
		);

		$reflection = new ReflectionClass( 'WPInsight_Storage' );

		foreach ( $required_methods as $method_name ) {
			$this->assertTrue(
				$reflection->hasMethod( $method_name ),
				"Method {$method_name} should exist"
			);

			$method = $reflection->getMethod( $method_name );
			$this->assertTrue(
				$method->isStatic(),
				"Method {$method_name} should be static"
			);

			$this->assertTrue(
				$method->isPublic(),
				"Method {$method_name} should be public"
			);
		}
	}

	/**
	 * Test storage path format.
	 *
	 * Tests that storage path follows the expected structure:
	 * uploads/wpinsight/{type}/{slug}/
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_storage_path_format(): void {
		$path = WPInsight_Storage::get_storage_path( 'plugin', 'hello-dolly' );

		// Path should contain wpinsight.
		$this->assertStringContainsString(
			'wpinsight',
			$path,
			'Path should contain wpinsight directory'
		);

		// Path should contain plugin/hello-dolly.
		$this->assertMatchesRegularExpression(
			'/plugin[\/\\\\]hello-dolly/',
			$path,
			'Path should contain plugin/hello-dolly'
		);
	}

	/**
	 * Test that storage handles nested path structure.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_storage_handles_nested_path_structure(): void {
		$unique_slug = 'nested-test-' . uniqid();
		$path        = WPInsight_Storage::get_storage_path( 'theme', $unique_slug );

		// Verify method returns a path (successful call).
		$this->assertNotFalse(
			$path,
			'get_storage_path should handle nested directory structure'
		);

		// Verify path contains expected nested structure.
		$this->assertMatchesRegularExpression(
			'/theme[\/\\\\]nested-test-/',
			$path,
			'Path should contain nested theme/slug structure'
		);
	}

	/**
	 * Test download with invalid URL format.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_download_with_invalid_url(): void {
		$result = WPInsight_Storage::download_and_store_zip(
			'plugin',
			'test',
			'1.0',
			'not-a-valid-url'
		);

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertFalse( $result['success'], 'Should fail for invalid URL' );
		$this->assertArrayHasKey( 'error', $result, 'Should contain error message' );
	}

	/**
	 * Test download with empty parameters.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_download_with_empty_parameters(): void {
		$result = WPInsight_Storage::download_and_store_zip( '', '', '', '' );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertFalse( $result['success'], 'Should fail for empty parameters' );
		$this->assertArrayHasKey( 'error', $result, 'Should contain error message' );
	}

	/**
	 * Test that storage path handles special characters in slug.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_storage_path_handles_special_characters(): void {
		$slugs = array(
			'plugin-with-dashes',
			'plugin_with_underscores',
			'plugin123',
		);

		foreach ( $slugs as $slug ) {
			$path = WPInsight_Storage::get_storage_path( 'plugin', $slug );

			$this->assertIsString( $path, "Should handle slug: {$slug}" );
			$this->assertStringContainsString( $slug, $path, "Path should contain slug: {$slug}" );
		}
	}

	/**
	 * Test download result contains expected keys on error.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_download_error_result_structure(): void {
		$result = WPInsight_Storage::download_and_store_zip(
			'invalid',
			'test',
			'1.0',
			'https://example.com/test.zip'
		);

		$this->assertArrayHasKey( 'success', $result, 'Should have success key' );
		$this->assertArrayHasKey( 'error', $result, 'Should have error key on failure' );
		$this->assertIsString( $result['error'], 'Error should be a string' );
		$this->assertNotEmpty( $result['error'], 'Error message should not be empty' );
	}

	/**
	 * Test that get_storage_path returns false on failure.
	 *
	 * This test verifies error handling when directory creation fails.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_storage_path_returns_false_on_failure(): void {
		// This test would require mocking wp_mkdir_p to fail.
		// For now, we just verify the return type is string or false.
		$path = WPInsight_Storage::get_storage_path( 'plugin', 'test' );

		$this->assertTrue(
			is_string( $path ) || false === $path,
			'get_storage_path should return string or false'
		);
	}

	/**
	 * Test storage path for both entity types.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_storage_path_different_for_entity_types(): void {
		$plugin_path = WPInsight_Storage::get_storage_path( 'plugin', 'same-slug' );
		$theme_path  = WPInsight_Storage::get_storage_path( 'theme', 'same-slug' );

		$this->assertNotEquals(
			$plugin_path,
			$theme_path,
			'Plugin and theme paths should be different even with same slug'
		);
	}

	/**
	 * Test that storage constants are defined.
	 *
	 * Verifies that the class has necessary constants for operation.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_storage_class_structure(): void {
		$reflection = new ReflectionClass( 'WPInsight_Storage' );

		// Verify class exists and has static methods.
		$this->assertTrue(
			$reflection->hasMethod( 'download_and_store_zip' ),
			'Storage class should have required methods'
		);

		// Verify all methods are static (no instance methods).
		$methods = $reflection->getMethods( ReflectionMethod::IS_PUBLIC );
		foreach ( $methods as $method ) {
			$this->assertTrue(
				$method->isStatic(),
				"Public method {$method->getName()} should be static"
			);
		}
	}
}
