<?php
/**
 * Import/Export Functionality Tests
 *
 * Unit tests for the import/export functionality in WPInsight_Admin class.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @since      1.2.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

use PHPUnit\Framework\TestCase;

/**
 * Test case for Import/Export functionality.
 *
 * @since 1.2.0
 */
class ImportExportTest extends TestCase {

	/**
	 * Set up before each test.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load dependencies.
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-db.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-settings.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-logger.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-cpt.php';
		require_once dirname( __DIR__ ) . '/includes/class-wpinsight-admin.php';
	}

	/**
	 * Test that handle_export_download method exists.
	 *
	 * Verifies that the handle_export_download() method exists in the admin class.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function test_handle_export_download_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Admin', 'handle_export_download' ),
			'handle_export_download() method should exist'
		);
	}

	/**
	 * Test that render_import_export_page method exists.
	 *
	 * Verifies that the render_import_export_page() method exists in the admin class.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function test_render_import_export_page_method_exists(): void {
		$this->assertTrue(
			method_exists( 'WPInsight_Admin', 'render_import_export_page' ),
			'render_import_export_page() method should exist'
		);
	}

	/**
	 * Test CPT types exist for import/export.
	 *
	 * Verifies that plugin and theme CPTs are defined for export functionality.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function test_cpt_types_exist_for_export(): void {
		$plugin_type = WPInsight_CPT::get_plugin_post_type();
		$theme_type  = WPInsight_CPT::get_theme_post_type();

		$this->assertNotEmpty( $plugin_type, 'Plugin CPT should be defined' );
		$this->assertNotEmpty( $theme_type, 'Theme CPT should be defined' );
		$this->assertNotEquals( $plugin_type, $theme_type, 'CPTs should be different' );
	}

	/**
	 * Test plugin version constant exists.
	 *
	 * Verifies that WPINSIGHT_VERSION constant is defined for export.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function test_plugin_version_constant_exists(): void {
		$this->assertTrue(
			defined( 'WPINSIGHT_VERSION' ),
			'WPINSIGHT_VERSION constant should be defined'
		);

		$this->assertMatchesRegularExpression(
			'/^\d+\.\d+\.\d+$/',
			WPINSIGHT_VERSION,
			'Version should follow semantic versioning'
		);
	}

	/**
	 * Test JSON encoding basic structure.
	 *
	 * Verifies that basic export structure can be JSON encoded.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function test_export_structure_json_encodable(): void {
		$export = array(
			'version'        => '1.2.0',
			'exported_at'    => gmdate( 'c' ),
			'site_url'       => 'http://example.com',
			'plugin_version' => WPINSIGHT_VERSION,
			'data'           => array(
				'plugins' => array(),
				'themes'  => array(),
			),
		);

		$json = wp_json_encode( $export );

		$this->assertNotFalse( $json, 'Export structure should be JSON encodable' );
		$this->assertIsString( $json, 'Encoded JSON should be a string' );
	}

	/**
	 * Test JSON roundtrip encoding/decoding.
	 *
	 * Verifies round-trip JSON encoding/decoding preserves data.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function test_export_json_roundtrip(): void {
		$export = array(
			'version'        => '1.2.0',
			'exported_at'    => gmdate( 'c' ),
			'site_url'       => 'http://example.com',
			'plugin_version' => WPINSIGHT_VERSION,
			'data'           => array(
				'plugins' => array(),
				'themes'  => array(),
			),
		);

		$json    = wp_json_encode( $export );
		$decoded = json_decode( $json, true );

		$this->assertIsArray( $decoded, 'Decoded JSON should be an array' );
		$this->assertEquals(
			$export['version'],
			$decoded['version'],
			'Version should match after roundtrip'
		);
		$this->assertEquals(
			$export['plugin_version'],
			$decoded['plugin_version'],
			'Plugin version should match after roundtrip'
		);
	}

	/**
	 * Test gzip compression is available.
	 *
	 * Verifies that gzencode function is available for export compression.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	public function test_gzip_compression_available(): void {
		$this->assertTrue(
			function_exists( 'gzencode' ),
			'gzencode() should be available for export compression'
		);

		$data       = 'test data';
		$compressed = gzencode( $data );

		$this->assertNotFalse( $compressed, 'gzencode() should compress data' );
		$this->assertIsString( $compressed, 'Compressed data should be a string' );
		$this->assertGreaterThan( 0, strlen( $compressed ), 'Compressed data should have length' );
	}
}
