<?php
/**
 * Logger Class Tests
 *
 * Unit tests for the WPInsight_Logger class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      1.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Logger class.
 *
 * @since 1.1.0
 */
class LoggerTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load dependencies.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-db.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-settings.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-logger.php';
	}

	/**
	 * Test that logger class exists.
	 *
	 * Verifies that the WPInsight_Logger class is loaded and can be
	 * referenced.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_logger_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_Logger' ),
			'WPInsight_Logger class should exist'
		);
	}

	/**
	 * Test that logger class is final.
	 *
	 * Verifies that the logger class is declared as final to prevent
	 * inheritance.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_logger_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_Logger' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_Logger class should be final'
		);
	}

	/**
	 * Test that logger class uses static methods only.
	 *
	 * Verifies that all public methods are static since this is a utility
	 * class that should never be instantiated.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_logger_methods_are_static(): void {
		$reflection = new ReflectionClass( 'WPInsight_Logger' );
		$methods    = $reflection->getMethods( ReflectionMethod::IS_PUBLIC );

		foreach ( $methods as $method ) {
			$this->assertTrue(
				$method->isStatic(),
				"Method {$method->getName()} should be static"
			);
		}
	}

	/**
	 * Test severity level methods exist.
	 *
	 * Verifies that all expected severity level logging methods exist.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_severity_level_methods_exist(): void {
		$expected_methods = array( 'emergency', 'error', 'warning', 'info', 'debug' );

		foreach ( $expected_methods as $method ) {
			$this->assertTrue(
				method_exists( 'WPInsight_Logger', $method ),
				"Logger should have {$method}() method"
			);
		}
	}

	/**
	 * Test emergency method exists.
	 *
	 * Verifies that the emergency() method exists and is callable.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_emergency_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Logger', 'emergency' ),
			'emergency() method should exist'
		);
	}

	/**
	 * Test error method exists.
	 *
	 * Verifies that the error() method exists and is callable.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_error_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Logger', 'error' ),
			'error() method should exist'
		);
	}

	/**
	 * Test warning method exists.
	 *
	 * Verifies that the warning() method exists and is callable.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_warning_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Logger', 'warning' ),
			'warning() method should exist'
		);
	}

	/**
	 * Test info method exists.
	 *
	 * Verifies that the info() method exists and is callable.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_info_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Logger', 'info' ),
			'info() method should exist'
		);
	}

	/**
	 * Test debug method exists.
	 *
	 * Verifies that the debug() method exists and is callable.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_debug_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Logger', 'debug' ),
			'debug() method should exist'
		);
	}

	/**
	 * Test get_recent_errors method exists.
	 *
	 * Verifies that the get_recent_errors() method exists and is callable.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_get_recent_errors_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Logger', 'get_recent_errors' ),
			'get_recent_errors() method should exist'
		);
	}

	/**
	 * Test clear_old_logs method exists.
	 *
	 * Verifies that the clear_old_logs() method exists and is callable.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_clear_old_logs_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Logger', 'clear_old_logs' ),
			'clear_old_logs() method should exist'
		);
	}

	/**
	 * Test log method signatures accept required parameters.
	 *
	 * Verifies that logging methods accept message and optional context.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_log_method_signatures(): void {
		$methods = array( 'emergency', 'error', 'warning', 'info', 'debug' );

		foreach ( $methods as $method_name ) {
			$reflection = new ReflectionMethod( 'WPInsight_Logger', $method_name );
			$params     = $reflection->getParameters();

			// Should have exactly 2 parameters: message and context.
			$this->assertCount(
				2,
				$params,
				"{$method_name}() should have 2 parameters"
			);

			// First parameter should be $message (string).
			$this->assertEquals(
				'message',
				$params[0]->getName(),
				"First parameter should be 'message'"
			);

			$this->assertTrue(
				$params[0]->hasType(),
				"Message parameter should be typed"
			);

			// Second parameter should be $context (array, optional).
			$this->assertEquals(
				'context',
				$params[1]->getName(),
				"Second parameter should be 'context'"
			);

			$this->assertTrue(
				$params[1]->isOptional(),
				"Context parameter should be optional"
			);
		}
	}

	/**
	 * Test get_recent_errors returns array.
	 *
	 * Verifies that get_recent_errors() returns an array even when
	 * no errors exist.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_get_recent_errors_returns_array(): void {
		$errors = WPInsight_Logger::get_recent_errors();

		$this->assertIsArray(
			$errors,
			'get_recent_errors() should return an array'
		);
	}

	/**
	 * Test clear_old_logs returns integer.
	 *
	 * Verifies that clear_old_logs() returns an integer count of
	 * deleted logs.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function test_clear_old_logs_returns_integer(): void {
		$deleted = WPInsight_Logger::clear_old_logs( 30 );

		$this->assertIsInt(
			$deleted,
			'clear_old_logs() should return an integer'
		);

		$this->assertGreaterThanOrEqual(
			0,
			$deleted,
			'Deleted count should be >= 0'
		);
	}
}
