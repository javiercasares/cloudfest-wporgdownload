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

if ( ! function_exists( 'register_post_type' ) ) {
	/**
	 * Stub for register_post_type() WordPress function.
	 *
	 * @param string $post_type Post type name.
	 * @param array  $args      Post type arguments.
	 * @return bool True.
	 */
	function register_post_type( $post_type, $args = array() ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $post_type, $args );
		return true;
	}
}

if ( ! function_exists( '_x' ) ) {
	/**
	 * Stub for _x() WordPress function.
	 *
	 * @param string $text    Text to translate.
	 * @param string $context Context information.
	 * @param string $domain  Text domain.
	 * @return string Translated text.
	 */
	function _x( $text, $context, $domain = 'default' ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $context, $domain );
		return $text;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stub for __() WordPress function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string Translated text.
	 */
	function __( $text, $domain = 'default' ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	/**
	 * Stub for get_posts() WordPress function.
	 *
	 * @param array $args Query arguments.
	 * @return array Empty array.
	 */
	function get_posts( $args = array() ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $args );
		return array();
	}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	/**
	 * Stub for wp_insert_post() WordPress function.
	 *
	 * @param array $postarr Post data.
	 * @return int Fake post ID.
	 */
	function wp_insert_post( $postarr ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $postarr );
		return 123; // Return fake post ID.
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Stub for is_wp_error() WordPress function.
	 *
	 * @param mixed $thing Thing to check.
	 * @return bool True if WP_Error, false otherwise.
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	/**
	 * Stub for update_post_meta() WordPress function.
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool True.
	 */
	function update_post_meta( $post_id, $meta_key, $meta_value ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $post_id, $meta_key, $meta_value );
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Stub for get_post_meta() WordPress function.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Return single value.
	 * @return mixed Empty string or array.
	 */
	function get_post_meta( $post_id, $key = '', $single = false ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $post_id, $key );
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'wp_delete_post' ) ) {
	/**
	 * Stub for wp_delete_post() WordPress function.
	 *
	 * @param int  $post_id      Post ID.
	 * @param bool $force_delete Force delete.
	 * @return bool True.
	 */
	function wp_delete_post( $post_id, $force_delete = false ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $post_id, $force_delete );
		return true;
	}
}

if ( ! function_exists( 'get_post_field' ) ) {
	/**
	 * Stub for get_post_field() WordPress function.
	 *
	 * @param string $field   Field name.
	 * @param int    $post_id Post ID.
	 * @return string Empty string.
	 */
	function get_post_field( $field, $post_id ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $field, $post_id );
		return 'test-slug';
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	/**
	 * Stub for number_format_i18n() WordPress function.
	 *
	 * @param int $number Number to format.
	 * @return string Formatted number.
	 */
	function number_format_i18n( $number ) {
		return number_format( $number );
	}
}

if ( ! function_exists( 'size_format' ) ) {
	/**
	 * Stub for size_format() WordPress function.
	 *
	 * @param int $bytes    Number of bytes.
	 * @param int $decimals Number of decimal places.
	 * @return string Formatted size string.
	 */
	function size_format( $bytes, $decimals = 0 ) {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
		$bytes = max( $bytes, 0 );
		$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow   = min( $pow, count( $units ) - 1 );
		$bytes /= pow( 1024, $pow );
		return round( $bytes, $decimals ) . ' ' . $units[ $pow ];
	}
}

if ( ! function_exists( 'human_time_diff' ) ) {
	/**
	 * Stub for human_time_diff() WordPress function.
	 *
	 * @param int $from From timestamp.
	 * @param int $to   To timestamp.
	 * @return string Time difference.
	 */
	function human_time_diff( $from, $to ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $from, $to );
		return '2 days';
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stub for add_filter() WordPress function.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @param int      $args     Number of arguments.
	 * @return void
	 */
	function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $hook, $callback, $priority, $args );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Stub for esc_html() WordPress function.
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Stub for esc_html__() WordPress function.
	 *
	 * @param string $text   Text to translate and escape.
	 * @param string $domain Text domain.
	 * @return string Escaped translated text.
	 */
	function esc_html__( $text, $domain = 'default' ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $domain );
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_count_posts' ) ) {
	/**
	 * Stub for wp_count_posts() WordPress function.
	 *
	 * @param string $type Post type.
	 * @return object Post counts.
	 */
	function wp_count_posts( $type = 'post' ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $type );
		return (object) [
			'publish' => 0,
			'draft'   => 0,
			'pending' => 0,
		];
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	/**
	 * Stub for wp_remote_post() WordPress function.
	 *
	 * @param string $url  Request URL.
	 * @param array  $args Request arguments.
	 * @return WP_Error Fake error for test environment.
	 */
	function wp_remote_post( $url, $args = array() ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $url, $args );
		return new WP_Error( 'http_request_failed', 'Test stub: wp_remote_post not available' );
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * Stub for wp_remote_get() WordPress function.
	 *
	 * @param string $url  Request URL.
	 * @param array  $args Request arguments.
	 * @return WP_Error Fake error for test environment.
	 */
	function wp_remote_get( $url, $args = array() ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $url, $args );
		return new WP_Error( 'http_request_failed', 'Test stub: wp_remote_get not available' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * Stub for wp_remote_retrieve_response_code() WordPress function.
	 *
	 * @param array|WP_Error $response Response array or WP_Error.
	 * @return int Response code.
	 */
	function wp_remote_retrieve_response_code( $response ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $response );
		return 200;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * Stub for wp_remote_retrieve_body() WordPress function.
	 *
	 * @param array|WP_Error $response Response array or WP_Error.
	 * @return string Response body.
	 */
	function wp_remote_retrieve_body( $response ) {
		// Suppress unused parameter warnings in test stubs.
		unset( $response );
		return '{}';
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Stub for wp_parse_args() WordPress function.
	 *
	 * @param string|array $args     Value to merge with defaults.
	 * @param array        $defaults Default array.
	 * @return array Merged array.
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args = $args;
		} else {
			parse_str( (string) $args, $parsed_args );
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $parsed_args );
		}
		return $parsed_args;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Stub for wp_json_encode() WordPress function.
	 *
	 * @param mixed $data    Data to encode.
	 * @param int   $options Optional. JSON encode options.
	 * @param int   $depth   Optional. Maximum depth.
	 * @return string|false JSON string or false on failure.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Stub for WP_Error class.
	 *
	 * @since 0.1.0
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/**
		 * Get error message.
		 *
		 * @return string Error message.
		 */
		public function get_error_message(): string {
			return $this->message;
		}
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

if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
	/**
	 * Stub for as_schedule_recurring_action() Action Scheduler function.
	 *
	 * @param int    $timestamp Timestamp.
	 * @param int    $interval  Interval in seconds.
	 * @param string $hook      Hook name.
	 * @param array  $args      Arguments.
	 * @param string $group     Group name.
	 * @return int Action ID.
	 */
	function as_schedule_recurring_action( $timestamp, $interval, $hook, $args = [], $group = '' ) {
		return 1;
	}
}

if ( ! function_exists( 'as_next_scheduled_action' ) ) {
	/**
	 * Stub for as_next_scheduled_action() Action Scheduler function.
	 *
	 * @param string $hook  Hook name.
	 * @param array  $args  Arguments.
	 * @param string $group Group name.
	 * @return int|false Next scheduled timestamp or false.
	 */
	function as_next_scheduled_action( $hook, $args = [], $group = '' ) {
		return false;
	}
}

if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
	/**
	 * Stub for as_get_scheduled_actions() Action Scheduler function.
	 *
	 * @param array  $args        Query arguments.
	 * @param string $return_type Return type.
	 * @return array Scheduled actions.
	 */
	function as_get_scheduled_actions( $args = [], $return_type = 'OBJECT' ) {
		return [];
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	/**
	 * Stub for wp_next_scheduled() WordPress function.
	 *
	 * @param string $hook Hook name.
	 * @param array  $args Arguments.
	 * @return int|false Next scheduled timestamp or false.
	 */
	function wp_next_scheduled( $hook, $args = [] ) {
		return false;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	/**
	 * Stub for wp_schedule_event() WordPress function.
	 *
	 * @param int    $timestamp  Timestamp.
	 * @param string $recurrence Recurrence interval.
	 * @param string $hook       Hook name.
	 * @param array  $args       Arguments.
	 * @param bool   $wp_error   Return WP_Error on failure.
	 * @return bool|WP_Error True on success, false or WP_Error on failure.
	 */
	function wp_schedule_event( $timestamp, $recurrence, $hook, $args = [], $wp_error = false ) {
		return true;
	}
}

if ( ! function_exists( 'wp_remote_head' ) ) {
	/**
	 * Stub for wp_remote_head() WordPress function.
	 *
	 * @param string $url  Request URL.
	 * @param array  $args Request arguments.
	 * @return array|WP_Error Response array or WP_Error.
	 */
	function wp_remote_head( $url, $args = [] ) {
		return new WP_Error( 'http_request_failed', 'Test stub: wp_remote_head not available' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_header' ) ) {
	/**
	 * Stub for wp_remote_retrieve_header() WordPress function.
	 *
	 * @param array|WP_Error $response Response array or WP_Error.
	 * @param string         $header   Header name.
	 * @return string Header value or empty string.
	 */
	function wp_remote_retrieve_header( $response, $header ) {
		return '';
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Stub for add_query_arg() WordPress function.
	 *
	 * @param mixed  $param1 Parameter name or array of query parameters.
	 * @param mixed  $param2 Parameter value or URL.
	 * @param string $param3 Optional URL.
	 * @return string URL with query parameters added.
	 */
	function add_query_arg( $param1, $param2 = '', $param3 = '' ) {
		if ( is_array( $param1 ) ) {
			$url    = $param2;
			$params = $param1;
		} else {
			$url                = $param3 ? $param3 : $param2;
			$params             = [];
			$params[ $param1 ]  = $param2;
		}

		if ( empty( $url ) ) {
			$url = 'http://example.com';
		}

		$query_string = http_build_query( $params );
		return $url . ( strpos( $url, '?' ) !== false ? '&' : '?' ) . $query_string;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * Stub for get_transient() WordPress function.
	 *
	 * @param string $transient Transient name.
	 * @return mixed Transient value or false.
	 */
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Stub for set_transient() WordPress function.
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Transient value.
	 * @param int    $expiration Time until expiration in seconds.
	 * @return bool True on success, false on failure.
	 */
	function set_transient( $transient, $value, $expiration = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * Stub for delete_transient() WordPress function.
	 *
	 * @param string $transient Transient name.
	 * @return bool True on success, false on failure.
	 */
	function delete_transient( $transient ) {
		return true;
	}
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
	/**
	 * Stub for wp_upload_dir() WordPress function.
	 *
	 * @return array Upload directory info.
	 */
	function wp_upload_dir() {
		return [
			'path'    => '/tmp/uploads',
			'url'     => 'http://example.com/wp-content/uploads',
			'subdir'  => '',
			'basedir' => '/tmp/uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
			'error'   => false,
		];
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	/**
	 * Stub for wp_mkdir_p() WordPress function.
	 *
	 * @param string $target Directory path.
	 * @return bool True on success.
	 */
	function wp_mkdir_p( $target ) {
		return true;
	}
}

if ( ! function_exists( 'wp_delete_file' ) ) {
	/**
	 * Stub for wp_delete_file() WordPress function.
	 *
	 * @param string $file File path.
	 * @return bool True on success.
	 */
	function wp_delete_file( $file ) {
		return true;
	}
}

// Create global $wpdb mock.
if ( ! isset( $GLOBALS['wpdb'] ) ) {
	$GLOBALS['wpdb'] = new class() {
		public $prefix = 'wp_';

		public function get_var( $query ) {
			return 0;
		}

		public function get_row( $query, $output = OBJECT, $offset = 0 ) {
			return null;
		}

		public function get_results( $query, $output = OBJECT ) {
			return [];
		}

		public function query( $query ) {
			return 0;
		}

		public function insert( $table, $data, $format = null ) {
			return 1;
		}

		public function update( $table, $data, $where, $format = null, $where_format = null ) {
			return 1;
		}

		public function prepare( $query, ...$args ) {
			return $query;
		}
	};
}

// Define WordPress database result type constants.
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}
