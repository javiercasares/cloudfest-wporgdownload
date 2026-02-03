<?php
/**
 * Sync Engine Tests
 *
 * Unit tests for the WPInsight_Sync class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Sync Engine class.
 *
 * @since 0.1.0
 */
class SyncTest extends TestCase {

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
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-cpt.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-wporg-client.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-sync.php';
	}

	/**
	 * Test that Sync class exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_sync_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_Sync' ),
			'WPInsight_Sync class should exist'
		);
	}

	/**
	 * Test that Sync class is final.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_sync_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_Sync' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_Sync class should be final'
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
			method_exists( 'WPInsight_Sync', 'init' ),
			'init() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'init' );
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
			method_exists( 'WPInsight_Sync', 'ensure_scheduled' ),
			'ensure_scheduled() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'ensure_scheduled' );
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
	 * Test that sync_tick method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_sync_tick_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Sync', 'sync_tick' ),
			'sync_tick() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'sync_tick' );
		$this->assertTrue(
			$reflection->isStatic(),
			'sync_tick() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'sync_tick() method should be public'
		);
	}

	/**
	 * Test that sync_plugins method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_sync_plugins_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Sync', 'sync_plugins' ),
			'sync_plugins() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'sync_plugins' );
		$this->assertTrue(
			$reflection->isStatic(),
			'sync_plugins() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'sync_plugins() method should be public'
		);
	}

	/**
	 * Test that sync_themes method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_sync_themes_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Sync', 'sync_themes' ),
			'sync_themes() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'sync_themes' );
		$this->assertTrue(
			$reflection->isStatic(),
			'sync_themes() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'sync_themes() method should be public'
		);
	}

	/**
	 * Test that get_sync_state method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_sync_state_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Sync', 'get_sync_state' ),
			'get_sync_state() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'get_sync_state' );
		$this->assertTrue(
			$reflection->isStatic(),
			'get_sync_state() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'get_sync_state() method should be public'
		);
	}

	/**
	 * Test that update_sync_state method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_update_sync_state_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Sync', 'update_sync_state' ),
			'update_sync_state() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'update_sync_state' );
		$this->assertTrue(
			$reflection->isStatic(),
			'update_sync_state() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'update_sync_state() method should be public'
		);
	}

	/**
	 * Test that reset_sync_state method exists.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_reset_sync_state_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Sync', 'reset_sync_state' ),
			'reset_sync_state() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_Sync', 'reset_sync_state' );
		$this->assertTrue(
			$reflection->isStatic(),
			'reset_sync_state() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'reset_sync_state() method should be public'
		);
	}

	/**
	 * Test that init can be called.
	 *
	 * Verifies that calling init() doesn't throw exceptions.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_init_can_be_called(): void {
		$this->expectNotToPerformAssertions();
		WPInsight_Sync::init();
	}

	/**
	 * Test that ensure_scheduled can be called.
	 *
	 * Verifies that calling ensure_scheduled() doesn't throw exceptions.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_ensure_scheduled_can_be_called(): void {
		$this->expectNotToPerformAssertions();
		WPInsight_Sync::ensure_scheduled();
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
			'sync_tick',
			'sync_plugins',
			'sync_themes',
			'get_sync_state',
			'update_sync_state',
			'reset_sync_state',
		];

		foreach ( $methods as $method ) {
			$this->assertTrue(
				method_exists( 'WPInsight_Sync', $method ),
				"{$method}() method should exist"
			);

			$reflection = new ReflectionMethod( 'WPInsight_Sync', $method );
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
