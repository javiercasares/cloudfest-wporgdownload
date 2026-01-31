<?php
/**
 * Tests for WPInsight_CLI class.
 *
 * @package    CloudFest_WPOrg_Download
 * @subpackage Tests
 * @since      0.1.0
 */

use PHPUnit\Framework\TestCase;

/**
 * CLI test case.
 *
 * @since 0.1.0
 */
class CLITest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load dependencies.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-settings.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-db.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-cpt.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-wporg-client.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-sync.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-zip-queue.php';

		// Load the CLI class.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-cli.php';
	}

	/**
	 * Test that CLI class exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_cli_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_CLI' ),
			'WPInsight_CLI class should exist'
		);
	}

	/**
	 * Test that CLI class is final.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_cli_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_CLI' );

		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_CLI class should be final'
		);
	}

	/**
	 * Test that register method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_register_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_CLI', 'register' ),
			'register() method should exist'
		);
	}

	/**
	 * Test that sync method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_sync_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_CLI', 'sync' ),
			'sync() method should exist'
		);
	}

	/**
	 * Test that zip method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_zip_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_CLI', 'zip' ),
			'zip() method should exist'
		);
	}

	/**
	 * Test that stats method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_stats_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_CLI', 'stats' ),
			'stats() method should exist'
		);
	}

	/**
	 * Test that queue method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_queue_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_CLI', 'queue' ),
			'queue() method should exist'
		);
	}

	/**
	 * Test that reset method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_reset_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_CLI', 'reset' ),
			'reset() method should exist'
		);
	}

	/**
	 * Test that register method can be called.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_register_can_be_called(): void {
		// Should not throw exception when WP_CLI is not defined.
		WPInsight_CLI::register();

		$this->assertTrue( true, 'register() should be callable' );
	}

	/**
	 * Test that all methods exist and are static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_all_methods_exist_and_are_static(): void {
		$reflection = new ReflectionClass( 'WPInsight_CLI' );

		$required_methods = [
			'register',
			'sync',
			'zip',
			'stats',
			'queue',
			'reset',
		];

		foreach ( $required_methods as $method_name ) {
			$this->assertTrue(
				$reflection->hasMethod( $method_name ),
				sprintf( '%s() method should exist', $method_name )
			);

			$method = $reflection->getMethod( $method_name );
			$this->assertTrue(
				$method->isStatic(),
				sprintf( '%s() method should be static', $method_name )
			);
		}
	}
}
