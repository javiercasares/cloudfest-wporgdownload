<?php
/**
 * Size Detection Tests
 *
 * Unit tests for ZIP size detection functionality in WPInsight_Zip_Queue.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      1.4.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Size Detection functionality.
 *
 * @since 1.4.0
 */
class SizeDetectionTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load dependencies.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-db.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-settings.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-logger.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-storage.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-zip-queue.php';
	}

	/**
	 * Test that detect_zip_sizes method exists.
	 *
	 * Verifies that the detect_zip_sizes() method exists in the queue class.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_detect_zip_sizes_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'detect_zip_sizes' ),
			'detect_zip_sizes() method should exist'
		);
	}

	/**
	 * Test that size_detection_tick method exists.
	 *
	 * Verifies that the size_detection_tick() method exists.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_size_detection_tick_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'size_detection_tick' ),
			'size_detection_tick() method should exist'
		);
	}

	/**
	 * Test that ensure_size_detection_scheduled method exists.
	 *
	 * Verifies that the ensure_size_detection_scheduled() method exists.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_ensure_size_detection_scheduled_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'ensure_size_detection_scheduled' ),
			'ensure_size_detection_scheduled() method should exist'
		);
	}

	/**
	 * Test that get_size_statistics method exists.
	 *
	 * Verifies that the get_size_statistics() method exists.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_get_size_statistics_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Zip_Queue', 'get_size_statistics' ),
			'get_size_statistics() method should exist'
		);
	}

	/**
	 * Test detect_zip_sizes accepts limit parameter.
	 *
	 * Verifies that detect_zip_sizes() accepts a limit parameter.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_detect_zip_sizes_accepts_limit(): void {
		$reflection = new ReflectionMethod( 'WPInsight_Zip_Queue', 'detect_zip_sizes' );
		$params     = $reflection->getParameters();

		$this->assertCount( 1, $params, 'detect_zip_sizes() should have 1 parameter' );
		$this->assertEquals( 'limit', $params[0]->getName(), 'Parameter should be named limit' );
		$this->assertTrue( $params[0]->isOptional(), 'Limit parameter should be optional' );
	}

	/**
	 * Test detect_zip_sizes returns array.
	 *
	 * Verifies that detect_zip_sizes() returns an array with statistics.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_detect_zip_sizes_returns_array(): void {
		$result = WPInsight_Zip_Queue::detect_zip_sizes( 10 );

		$this->assertIsArray( $result, 'detect_zip_sizes() should return an array' );
	}

	/**
	 * Test detect_zip_sizes returns expected keys.
	 *
	 * Verifies that the result array has expected statistics keys.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_detect_zip_sizes_returns_expected_keys(): void {
		$result = WPInsight_Zip_Queue::detect_zip_sizes( 10 );

		$this->assertArrayHasKey( 'checked', $result, 'Result should have checked count' );
		$this->assertArrayHasKey( 'updated', $result, 'Result should have updated count' );
		$this->assertArrayHasKey( 'failed', $result, 'Result should have failed count' );
	}

	/**
	 * Test detect_zip_sizes statistics are integers.
	 *
	 * Verifies that all statistics values are integers.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_detect_zip_sizes_statistics_are_integers(): void {
		$result = WPInsight_Zip_Queue::detect_zip_sizes( 10 );

		$this->assertIsInt( $result['checked'], 'Checked count should be an integer' );
		$this->assertIsInt( $result['updated'], 'Updated count should be an integer' );
		$this->assertIsInt( $result['failed'], 'Failed count should be an integer' );
	}

	/**
	 * Test detect_zip_sizes statistics are non-negative.
	 *
	 * Verifies that statistics cannot be negative.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_detect_zip_sizes_statistics_non_negative(): void {
		$result = WPInsight_Zip_Queue::detect_zip_sizes( 10 );

		$this->assertGreaterThanOrEqual( 0, $result['checked'], 'Checked count should be >= 0' );
		$this->assertGreaterThanOrEqual( 0, $result['updated'], 'Updated count should be >= 0' );
		$this->assertGreaterThanOrEqual( 0, $result['failed'], 'Failed count should be >= 0' );
	}

	/**
	 * Test detect_zip_sizes respects limit parameter.
	 *
	 * Verifies that the limit parameter constrains the number of checked items.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_detect_zip_sizes_respects_limit(): void {
		$result = WPInsight_Zip_Queue::detect_zip_sizes( 5 );

		// Checked count should not exceed limit.
		$this->assertLessThanOrEqual(
			5,
			$result['checked'],
			'Checked count should not exceed limit'
		);
	}

	/**
	 * Test get_size_statistics returns array.
	 *
	 * Verifies that get_size_statistics() returns an array.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_get_size_statistics_returns_array(): void {
		$stats = WPInsight_Zip_Queue::get_size_statistics();

		$this->assertIsArray( $stats, 'get_size_statistics() should return an array' );
	}

	/**
	 * Test get_size_statistics structure for plugins.
	 *
	 * Verifies plugin statistics structure.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_get_size_statistics_plugin_structure(): void {
		$stats = WPInsight_Zip_Queue::get_size_statistics();

		$this->assertArrayHasKey( 'plugins', $stats, 'Stats should have plugins key' );
		$this->assertIsArray( $stats['plugins'], 'Plugins stats should be an array' );
		$this->assertNotEmpty( $stats['plugins'], 'Plugins stats should not be empty' );

		// Check that at least some expected keys exist.
		$has_size_keys = isset( $stats['plugins']['downloaded_size'] ) ||
						isset( $stats['plugins']['total_size'] ) ||
						isset( $stats['plugins']['pending_size'] );

		$this->assertTrue( $has_size_keys, 'Plugins stats should have size-related keys' );
	}

	/**
	 * Test get_size_statistics structure for themes.
	 *
	 * Verifies theme statistics structure.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_get_size_statistics_theme_structure(): void {
		$stats = WPInsight_Zip_Queue::get_size_statistics();

		$this->assertArrayHasKey( 'themes', $stats, 'Stats should have themes key' );
		$this->assertIsArray( $stats['themes'], 'Themes stats should be an array' );
		$this->assertNotEmpty( $stats['themes'], 'Themes stats should not be empty' );

		// Check that at least some expected keys exist.
		$has_size_keys = isset( $stats['themes']['downloaded_size'] ) ||
						isset( $stats['themes']['total_size'] ) ||
						isset( $stats['themes']['pending_size'] );

		$this->assertTrue( $has_size_keys, 'Themes stats should have size-related keys' );
	}

	/**
	 * Test get_size_statistics size values are integers.
	 *
	 * Verifies that size values are integers.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_get_size_statistics_sizes_are_integers(): void {
		$stats = WPInsight_Zip_Queue::get_size_statistics();

		$this->assertIsInt( $stats['plugins']['downloaded_size'] );
		$this->assertIsInt( $stats['plugins']['pending_size'] );
		$this->assertIsInt( $stats['plugins']['total_size'] );
		$this->assertIsInt( $stats['themes']['downloaded_size'] );
		$this->assertIsInt( $stats['themes']['pending_size'] );
		$this->assertIsInt( $stats['themes']['total_size'] );
	}

	/**
	 * Test get_size_statistics progress is percentage.
	 *
	 * Verifies that detection_progress is between 0 and 100.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_get_size_statistics_progress_is_percentage(): void {
		$stats = WPInsight_Zip_Queue::get_size_statistics();

		$this->assertGreaterThanOrEqual(
			0,
			$stats['plugins']['detection_progress'],
			'Plugin detection progress should be >= 0'
		);

		$this->assertLessThanOrEqual(
			100,
			$stats['plugins']['detection_progress'],
			'Plugin detection progress should be <= 100'
		);

		$this->assertGreaterThanOrEqual(
			0,
			$stats['themes']['detection_progress'],
			'Theme detection progress should be >= 0'
		);

		$this->assertLessThanOrEqual(
			100,
			$stats['themes']['detection_progress'],
			'Theme detection progress should be <= 100'
		);
	}

	/**
	 * Test get_size_statistics total calculation.
	 *
	 * Verifies that total_size equals downloaded + pending.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_get_size_statistics_total_calculation(): void {
		$stats = WPInsight_Zip_Queue::get_size_statistics();

		// For plugins.
		$expected_plugin_total = $stats['plugins']['downloaded_size'] + $stats['plugins']['pending_size'];
		$this->assertEquals(
			$expected_plugin_total,
			$stats['plugins']['total_size'],
			'Plugin total size should equal downloaded + pending'
		);

		// For themes.
		$expected_theme_total = $stats['themes']['downloaded_size'] + $stats['themes']['pending_size'];
		$this->assertEquals(
			$expected_theme_total,
			$stats['themes']['total_size'],
			'Theme total size should equal downloaded + pending'
		);
	}

	/**
	 * Test size detection rate setting exists.
	 *
	 * Verifies that max_size_detection_rate setting is defined.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_size_detection_rate_setting_exists(): void {
		$defaults = WPInsight_Settings::get_defaults();

		$this->assertArrayHasKey(
			'max_size_detection_rate',
			$defaults,
			'max_size_detection_rate setting should exist'
		);
	}

	/**
	 * Test size detection rate default value.
	 *
	 * Verifies that the default rate is 3 req/sec.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_size_detection_rate_default_value(): void {
		$defaults = WPInsight_Settings::get_defaults();

		$this->assertEquals(
			3,
			$defaults['max_size_detection_rate'],
			'Default size detection rate should be 3 req/sec'
		);
	}

	/**
	 * Test size detection rate is retrieved correctly.
	 *
	 * Verifies that the rate setting can be retrieved.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function test_size_detection_rate_retrieval(): void {
		$rate = WPInsight_Settings::get( 'max_size_detection_rate', 3 );

		$this->assertIsInt( $rate, 'Size detection rate should be an integer' );
		$this->assertGreaterThanOrEqual( 1, $rate, 'Rate should be >= 1' );
		$this->assertLessThanOrEqual( 10, $rate, 'Rate should be <= 10' );
	}
}
