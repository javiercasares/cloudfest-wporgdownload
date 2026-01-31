<?php
/**
 * Bootstrap Class Tests
 *
 * Unit tests for the WPInsight_Bootstrap class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Bootstrap class.
 *
 * @since 0.1.0
 */
class BootstrapTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load the bootstrap class.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-bootstrap.php';
	}

	/**
	 * Test that bootstrap class exists.
	 *
	 * Verifies that the WPInsight_Bootstrap class is loaded and can be
	 * referenced. Since the class is final and uses only static methods,
	 * we don't instantiate it.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_bootstrap_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_Bootstrap' ),
			'WPInsight_Bootstrap class should exist'
		);
	}

	/**
	 * Test that bootstrap class is final.
	 *
	 * Verifies that the bootstrap class is declared as final to prevent
	 * inheritance and ensure the singleton pattern.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_bootstrap_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_Bootstrap' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_Bootstrap class should be final'
		);
	}

	/**
	 * Test that init method exists and is static.
	 *
	 * Verifies that the init() method exists, is public, and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_init_method_exists_and_is_static(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Bootstrap', 'init' ),
			'init() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Bootstrap', 'init' );
		$this->assertTrue(
			$reflection->isStatic(),
			'init() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'init() method should be public'
		);
	}

	/**
	 * Test that activate method exists and is static.
	 *
	 * Verifies that the activate() method exists, is public, and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_activate_method_exists_and_is_static(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Bootstrap', 'activate' ),
			'activate() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Bootstrap', 'activate' );
		$this->assertTrue(
			$reflection->isStatic(),
			'activate() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'activate() method should be public'
		);
	}

	/**
	 * Test that deactivate method exists and is static.
	 *
	 * Verifies that the deactivate() method exists, is public, and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_deactivate_method_exists_and_is_static(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Bootstrap', 'deactivate' ),
			'deactivate() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Bootstrap', 'deactivate' );
		$this->assertTrue(
			$reflection->isStatic(),
			'deactivate() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'deactivate() method should be public'
		);
	}

	/**
	 * Test that init method can be called without errors.
	 *
	 * Verifies that calling init() doesn't throw any exceptions.
	 * Since init() has TODOs and doesn't do anything yet, it should
	 * complete silently.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_init_method_can_be_called(): void {
		// Should not throw any exceptions.
		$this->expectNotToPerformAssertions();
		WPInsight_Bootstrap::init();
	}

	/**
	 * Test that deactivate method can be called without errors.
	 *
	 * Verifies that calling deactivate() doesn't throw any exceptions
	 * even when WordPress functions are not available (stubs will be used).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_deactivate_method_can_be_called(): void {
		// Mock flush_rewrite_rules if not available.
		if ( ! function_exists( 'flush_rewrite_rules' ) ) {
			/**
			 * Stub for flush_rewrite_rules().
			 *
			 * @return void
			 */
			function flush_rewrite_rules() {
				// Stub - does nothing.
			}
		}

		// Should not throw any exceptions.
		$this->expectNotToPerformAssertions();
		WPInsight_Bootstrap::deactivate();
	}
}
