<?php
/**
 * ZIP Queue Worker Tests
 *
 * Unit tests for the WPInsight_Zip_Queue class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for ZIP Queue Worker class.
 *
 * @since 0.1.0
 */
class ZipQueueTest extends TestCase {

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
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-zip-queue.php';
	}

	/**
	 * Test that ZIP Queue class exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_zip_queue_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_Zip_Queue' ),
			'WPInsight_Zip_Queue class should exist'
		);
	}

	/**
	 * Test that ZIP Queue class is final.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_zip_queue_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_Zip_Queue' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_Zip_Queue class should be final'
		);
	}

	/**
	 * Test that init method exists and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'init' ),
			'init() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', 'init' );
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
	 * Test that ensure_scheduled method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_ensure_scheduled_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'ensure_scheduled' ),
			'ensure_scheduled() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', 'ensure_scheduled' );
		$this->assertTrue(
			$reflection->isStatic(),
			'ensure_scheduled() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'ensure_scheduled() method should be public'
		);
	}

	/**
	 * Test that worker_tick method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_worker_tick_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'worker_tick' ),
			'worker_tick() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', 'worker_tick' );
		$this->assertTrue(
			$reflection->isStatic(),
			'worker_tick() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'worker_tick() method should be public'
		);
	}

	/**
	 * Test that get_queue_stats method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_queue_stats_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'get_queue_stats' ),
			'get_queue_stats() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', 'get_queue_stats' );
		$this->assertTrue(
			$reflection->isStatic(),
			'get_queue_stats() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'get_queue_stats() method should be public'
		);
	}

	/**
	 * Test that retry_failed_jobs method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_retry_failed_jobs_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'retry_failed_jobs' ),
			'retry_failed_jobs() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', 'retry_failed_jobs' );
		$this->assertTrue(
			$reflection->isStatic(),
			'retry_failed_jobs() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'retry_failed_jobs() method should be public'
		);
	}

	/**
	 * Test that clear_completed_jobs method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_clear_completed_jobs_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'clear_completed_jobs' ),
			'clear_completed_jobs() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', 'clear_completed_jobs' );
		$this->assertTrue(
			$reflection->isStatic(),
			'clear_completed_jobs() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'clear_completed_jobs() method should be public'
		);
	}

	/**
	 * Test that init can be called.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_init_can_be_called(): void {
		$this->expectNotToPerformAssertions();
		WPInsight_Zip_Queue::init();
	}

	/**
	 * Test that ensure_scheduled can be called.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_ensure_scheduled_can_be_called(): void {
		$this->expectNotToPerformAssertions();
		WPInsight_Zip_Queue::ensure_scheduled();
	}

	/**
	 * Test that get_queue_stats returns an array.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_queue_stats_returns_array(): void {
		$stats = WPInsight_Zip_Queue::get_queue_stats();

		$this->assertIsArray(
			$stats,
			'get_queue_stats() should return an array'
		);

		// Check expected keys.
		$this->assertArrayHasKey( 'pending', $stats );
		$this->assertArrayHasKey( 'processing', $stats );
		$this->assertArrayHasKey( 'completed', $stats );
		$this->assertArrayHasKey( 'failed', $stats );
		$this->assertArrayHasKey( 'total', $stats );
	}

	/**
	 * Test that all required methods exist and are static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_all_methods_exist_and_are_static(): void {
		$methods = [
			'init',
			'ensure_scheduled',
			'worker_tick',
			'get_queue_stats',
			'retry_failed_jobs',
			'clear_completed_jobs',
		];

		foreach ( $methods as $method ) {
			$this->assertTrue(
				method_exists( 'WPInsight_Zip_Queue', $method ),
				"{$method}() method should exist"
			);

			$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', $method );
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
