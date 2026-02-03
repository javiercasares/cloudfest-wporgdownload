<?php
/**
 * Database Class Tests
 *
 * Unit tests for the WPInsight_DB class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Database class.
 *
 * @since 0.1.0
 */
class DBTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load the database class.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-db.php';
	}

	/**
	 * Test that database class exists.
	 *
	 * Verifies that the WPInsight_DB class is loaded and can be referenced.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_db_class_exists(): void {
		$this->assertTrue(
			class_exists( 'WPInsight_DB' ),
			'WPInsight_DB class should exist'
		);
	}

	/**
	 * Test that database class is final.
	 *
	 * Verifies that the database class is declared as final to prevent
	 * inheritance.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_db_class_is_final(): void {
		$reflection = new ReflectionClass( 'WPInsight_DB' );
		$this->assertTrue(
			$reflection->isFinal(),
			'WPInsight_DB class should be final'
		);
	}

	/**
	 * Test that get_schema_version returns a string.
	 *
	 * Verifies that the schema version is a non-empty string in semantic
	 * versioning format.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_schema_version_returns_string(): void {
		$version = WPInsight_DB::get_schema_version();

		$this->assertIsString(
			$version,
			'Schema version should be a string'
		);

		$this->assertNotEmpty(
			$version,
			'Schema version should not be empty'
		);

		// Should match semantic versioning format (e.g., 1.0.0).
		$this->assertMatchesRegularExpression(
			'/^\d+\.\d+\.\d+$/',
			$version,
			'Schema version should match semantic versioning format'
		);
	}

	/**
	 * Test that get_table_name returns prefixed table name.
	 *
	 * Verifies that table names are properly prefixed and formatted.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_get_table_name_returns_prefixed_name(): void {
		// Mock global $wpdb.
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			$wpdb         = new stdClass();
			$wpdb->prefix = 'wp_';
		}

		$table_name = WPInsight_DB::get_table_name( 'sync_state' );

		$this->assertIsString(
			$table_name,
			'Table name should be a string'
		);

		$this->assertStringContainsString(
			'wpinsight_sync_state',
			$table_name,
			'Table name should contain wpinsight_sync_state'
		);

		$this->assertStringContainsString(
			$wpdb->prefix,
			$table_name,
			'Table name should contain WordPress prefix'
		);
	}

	/**
	 * Test that install method exists and is static.
	 *
	 * Verifies that the install() method exists, is public, and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_install_method_exists_and_is_static(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_DB', 'install' ),
			'install() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_DB', 'install' );
		$this->assertTrue(
			$reflection->isStatic(),
			'install() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'install() method should be public'
		);
	}

	/**
	 * Test that maybe_upgrade method exists and is static.
	 *
	 * Verifies that the maybe_upgrade() method exists, is public, and is static.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_maybe_upgrade_method_exists_and_is_static(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_DB', 'maybe_upgrade' ),
			'maybe_upgrade() method should exist'
		);

		$reflection = new ReflectionMethod( 'WPInsight_DB', 'maybe_upgrade' );
		$this->assertTrue(
			$reflection->isStatic(),
			'maybe_upgrade() method should be static'
		);
		$this->assertTrue(
			$reflection->isPublic(),
			'maybe_upgrade() method should be public'
		);
	}

	/**
	 * Test all expected table names can be generated.
	 *
	 * Verifies that all three required tables can have their names generated
	 * correctly.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function test_all_table_names_can_be_generated(): void {
		// Mock global $wpdb.
		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			$wpdb         = new stdClass();
			$wpdb->prefix = 'wp_';
		}

		$tables = array( 'sync_state', 'zip_queue', 'artifacts' );

		foreach ( $tables as $table ) {
			$table_name = WPInsight_DB::get_table_name( $table );

			$this->assertStringContainsString(
				'wpinsight_' . $table,
				$table_name,
				"Table name should contain wpinsight_{$table}"
			);
		}
	}
}
