<?php
/**
 * PHPUnit Bootstrap File
 *
 * Initializes the testing environment for the CloudFest WPOrg Download plugin.
 * This file is loaded before any tests are run.
 *
 * For WordPress plugin testing, we would normally load the WordPress test suite,
 * but for now we'll keep it simple and just load the plugin files directly.
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Tests
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

// Define ABSPATH constant for plugin security checks.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Define WordPress constants needed by plugin code.
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define WordPress function stubs for testing without WordPress core.
if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Stub for plugin_dir_path() WordPress function.
	 *
	 * @param string $file Plugin file path.
	 * @return string Directory path with trailing slash.
	 */
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	/**
	 * Stub for plugin_dir_url() WordPress function.
	 *
	 * @param string $file Plugin file path.
	 * @return string Directory URL with trailing slash.
	 */
	function plugin_dir_url( $file ) {
		return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * Stub for trailingslashit() WordPress function.
	 *
	 * @param string $path Path or URL.
	 * @return string Path or URL with trailing slash.
	 */
	function trailingslashit( $path ) {
		return rtrim( $path, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	/**
	 * Stub for register_activation_hook() WordPress function.
	 *
	 * @param string   $file     Plugin file path.
	 * @param callable $callback Activation callback.
	 * @return void
	 */
	function register_activation_hook( $file, $callback ) {
		// Stub - does nothing in tests.
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	/**
	 * Stub for register_deactivation_hook() WordPress function.
	 *
	 * @param string   $file     Plugin file path.
	 * @param callable $callback Deactivation callback.
	 * @return void
	 */
	function register_deactivation_hook( $file, $callback ) {
		// Stub - does nothing in tests.
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Stub for add_action() WordPress function.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority (optional).
	 * @param int      $args     Number of arguments (optional).
	 * @return void
	 */
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
		// Stub - does nothing in tests.
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Stub for get_option() WordPress function.
	 *
	 * @param string $option         Option name.
	 * @param mixed  $default_value  Default value.
	 * @return mixed Option value or default.
	 */
	function get_option( $option, $default_value = false ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $option );
		return $default_value;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Stub for update_option() WordPress function.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool True.
	 */
	function update_option( $option, $value ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $option, $value );
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Stub for delete_option() WordPress function.
	 *
	 * @param string $option Option name.
	 * @return bool True.
	 */
	function delete_option( $option ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $option );
		return true;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub for sanitize_text_field() WordPress function.
	 *
	 * @param string $text Text to sanitize.
	 * @return string Sanitized text.
	 */
	function sanitize_text_field( $text ) {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	/**
	 * Stub for sanitize_file_name() WordPress function.
	 *
	 * @param string $filename Filename to sanitize.
	 * @return string Sanitized filename.
	 */
	function sanitize_file_name( $filename ) {
		return preg_replace( '/[^a-zA-Z0-9_\-.]/', '', $filename );
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	/**
	 * Stub for ABSPATH constant.
	 *
	 * @var string ABSPATH WordPress root path.
	 */
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Stub for current_time() WordPress function.
	 *
	 * @param string $type Type of time to retrieve (mysql or timestamp).
	 * @return string|int Current time.
	 */
	function current_time( $type ) {
		if ( 'mysql' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		return time();
	}
}

// Load plugin main file (defines constants).
require_once dirname( __DIR__ ) . '/cloudfest-wporgdownload.php';

/*
 * For full WordPress integration testing, we would load the WordPress test suite:
 *
 * // Load WordPress test environment.
 * $wp_tests_dir = getenv( 'WP_TESTS_DIR' );
 * if ( ! $wp_tests_dir ) {
 *     $wp_tests_dir = '/tmp/wordpress-tests-lib';
 * }
 *
 * require_once $wp_tests_dir . '/includes/functions.php';
 *
 * function _manually_load_plugin() {
 *     require dirname( __DIR__ ) . '/cloudfest-wporgdownload.php';
 * }
 * tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
 *
 * require $wp_tests_dir . '/includes/bootstrap.php';
 */

// Bootstrap complete.
echo "PHPUnit bootstrap loaded.\n";
