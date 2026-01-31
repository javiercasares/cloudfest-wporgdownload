<?php
/**
 * WordPress.org API Client Class
 *
 * Handles all HTTP communication with the WordPress.org Plugins/Themes API.
 * Provides methods for querying plugins/themes and fetching detailed information
 * including historical versions.
 *
 * API Documentation:
 * - https://codex.wordpress.org/WordPress.org_API
 * - https://codex.wordpress.org/WordPress.org_API#Plugins
 * - https://codex.wordpress.org/WordPress.org_API#Themes
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Includes
 * @since      0.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress.org API client class.
 *
 * This class uses static methods only and is never instantiated.
 * It provides a clean interface to the WordPress.org API endpoints.
 *
 * @since 0.1.0
 */
final class WPInsight_WPOrg_Client {

	/**
	 * API base URL for plugins.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const PLUGINS_API_URL = 'https://api.wordpress.org/plugins/info/1.2/';

	/**
	 * API base URL for themes.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const THEMES_API_URL = 'https://api.wordpress.org/themes/info/1.2/';

	/**
	 * HTTP timeout in seconds.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const HTTP_TIMEOUT = 30;

	/**
	 * Maximum number of retries for failed requests.
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private const MAX_RETRIES = 3;

	/**
	 * Query plugins from WordPress.org API.
	 *
	 * Fetches a paginated list of plugins using the browse endpoint.
	 * This is used for the initial sync to discover all plugins.
	 *
	 * @since 0.1.0
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type string $browse  Browse type (updated, popular, new, favorites). Default 'updated'.
	 *     @type int    $page    Page number (1-indexed). Default 1.
	 *     @type int    $per_page Number of results per page. Default 100.
	 * }
	 * @return array|false {
	 *     API response data or false on failure.
	 *
	 *     @type array  $plugins List of plugin objects.
	 *     @type object $info {
	 *         Pagination information.
	 *
	 *         @type int $page    Current page number.
	 *         @type int $pages   Total number of pages.
	 *         @type int $results Total number of results.
	 *     }
	 * }
	 */
	public static function query_plugins( array $args = [] ): array|false {
		$defaults = [
			'browse'   => 'updated',
			'page'     => 1,
			'per_page' => 100,
		];

		$args = wp_parse_args( $args, $defaults );

		$request_args = [
			'action'  => 'query_plugins',
			'request' => [
				'browse'   => $args['browse'],
				'page'     => $args['page'],
				'per_page' => $args['per_page'],
			],
		];

		return self::make_request( self::PLUGINS_API_URL, $request_args );
	}

	/**
	 * Get detailed plugin information.
	 *
	 * Fetches complete plugin metadata including all historical versions.
	 * The 'versions' field contains an object mapping version numbers to
	 * download URLs.
	 *
	 * @since 0.1.0
	 * @param string $slug Plugin slug.
	 * @return array|false Plugin information object or false on failure.
	 */
	public static function get_plugin_info( string $slug ): array|false {
		if ( empty( $slug ) ) {
			return false;
		}

		$request_args = [
			'action'  => 'plugin_information',
			'request' => [
				'slug'   => $slug,
				'fields' => [
					'versions'                 => true,
					'downloaded'               => true,
					'active_installs'          => true,
					'description'              => true,
					'short_description'        => true,
					'sections'                 => false,
					'compatibility'            => false,
					'screenshots'              => false,
					'tags'                     => true,
					'rating'                   => true,
					'ratings'                  => true,
					'num_ratings'              => true,
					'support_threads'          => false,
					'support_threads_resolved' => false,
					'homepage'                 => true,
					'donate_link'              => true,
				],
			],
		];

		return self::make_request( self::PLUGINS_API_URL, $request_args );
	}

	/**
	 * Query themes from WordPress.org API.
	 *
	 * Fetches a paginated list of themes using the browse endpoint.
	 * This is used for the initial sync to discover all themes.
	 *
	 * @since 0.1.0
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type string $browse  Browse type (updated, popular, new, featured). Default 'updated'.
	 *     @type int    $page    Page number (1-indexed). Default 1.
	 *     @type int    $per_page Number of results per page. Default 100.
	 * }
	 * @return array|false {
	 *     API response data or false on failure.
	 *
	 *     @type array  $themes List of theme objects.
	 *     @type object $info {
	 *         Pagination information.
	 *
	 *         @type int $page    Current page number.
	 *         @type int $pages   Total number of pages.
	 *         @type int $results Total number of results.
	 *     }
	 * }
	 */
	public static function query_themes( array $args = [] ): array|false {
		$defaults = [
			'browse'   => 'updated',
			'page'     => 1,
			'per_page' => 100,
		];

		$args = wp_parse_args( $args, $defaults );

		$request_args = [
			'action'  => 'query_themes',
			'request' => [
				'browse'   => $args['browse'],
				'page'     => $args['page'],
				'per_page' => $args['per_page'],
			],
		];

		return self::make_request( self::THEMES_API_URL, $request_args );
	}

	/**
	 * Get detailed theme information.
	 *
	 * Fetches complete theme metadata including all historical versions.
	 * The 'versions' field contains an object mapping version numbers to
	 * download URLs.
	 *
	 * @since 0.1.0
	 * @param string $slug Theme slug.
	 * @return array|false Theme information object or false on failure.
	 */
	public static function get_theme_info( string $slug ): array|false {
		if ( empty( $slug ) ) {
			return false;
		}

		$request_args = [
			'action'  => 'theme_information',
			'request' => [
				'slug'   => $slug,
				'fields' => [
					'versions'        => true,
					'downloaded'      => true,
					'active_installs' => true,
					'description'     => true,
					'sections'        => false,
					'screenshot_url'  => false,
					'screenshots'     => false,
					'tags'            => true,
					'rating'          => true,
					'ratings'         => true,
					'num_ratings'     => true,
					'homepage'        => true,
				],
			],
		];

		return self::make_request( self::THEMES_API_URL, $request_args );
	}

	/**
	 * Make HTTP request to WordPress.org API.
	 *
	 * Handles the low-level HTTP communication with retry logic and error handling.
	 * All API methods should use this method to make requests.
	 *
	 * @since 0.1.0
	 * @param string $url  API endpoint URL.
	 * @param array  $args Request arguments to be JSON-encoded.
	 * @return array|false Response data or false on failure.
	 */
	private static function make_request( string $url, array $args ): array|false {
		$attempt = 0;

		while ( $attempt < self::MAX_RETRIES ) {
			++$attempt;

			$response = wp_remote_post(
				$url,
				[
					'timeout' => self::HTTP_TIMEOUT,
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'body'    => wp_json_encode( $args ),
				]
			);

			// Check for HTTP errors.
			if ( is_wp_error( $response ) ) {
				// Log error and retry.
					self::log_error(
						sprintf(
							'WPInsight API Error (attempt %d/%d): %s',
							$attempt,
							self::MAX_RETRIES,
							$response->get_error_message()
						)
					);

				if ( $attempt < self::MAX_RETRIES ) {
					// Exponential backoff: 1s, 2s, 4s.
					sleep( 2 ** ( $attempt - 1 ) );
					continue;
				}

				return false;
			}

			// Check HTTP status code.
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $status_code ) {
					self::log_error(
						sprintf(
							'WPInsight API Error (attempt %d/%d): HTTP %d',
							$attempt,
							self::MAX_RETRIES,
							$status_code
						)
					);

				if ( $attempt < self::MAX_RETRIES ) {
					// Exponential backoff.
					sleep( 2 ** ( $attempt - 1 ) );
					continue;
				}

				return false;
			}

			// Decode response body.
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( null === $data ) {
					self::log_error(
						sprintf(
							'WPInsight API Error (attempt %d/%d): Invalid JSON response',
							$attempt,
							self::MAX_RETRIES
						)
					);

				if ( $attempt < self::MAX_RETRIES ) {
					// Exponential backoff.
					sleep( 2 ** ( $attempt - 1 ) );
					continue;
				}

				return false;
			}

			// Success!
			return $data;
		}

		return false;
	}

	/**
	 * Get download URL for a specific plugin version.
	 *
	 * Constructs the ZIP download URL for a given plugin and version.
	 * The URL follows the pattern: https://downloads.wordpress.org/plugin/{slug}.{version}.zip
	 *
	 * @since 0.1.0
	 * @param string $slug    Plugin slug.
	 * @param string $version Version number.
	 * @return string Download URL.
	 */
	public static function get_plugin_download_url( string $slug, string $version ): string {
		return sprintf(
			'https://downloads.wordpress.org/plugin/%s.%s.zip',
			sanitize_file_name( $slug ),
			sanitize_file_name( $version )
		);
	}

	/**
	 * Get download URL for a specific theme version.
	 *
	 * Constructs the ZIP download URL for a given theme and version.
	 * The URL follows the pattern: https://downloads.wordpress.org/theme/{slug}.{version}.zip
	 *
	 * @since 0.1.0
	 * @param string $slug    Theme slug.
	 * @param string $version Version number.
	 * @return string Download URL.
	 */
	public static function get_theme_download_url( string $slug, string $version ): string {
		return sprintf(
			'https://downloads.wordpress.org/theme/%s.%s.zip',
			sanitize_file_name( $slug ),
			sanitize_file_name( $version )
		);
	}

	/**
	 * Check if API is accessible.
	 *
	 * Makes a simple test request to verify that the WordPress.org API
	 * is reachable and responding. Useful for debugging and health checks.
	 *
	 * @since 0.1.0
	 * @return bool True if API is accessible, false otherwise.
	 */
	public static function is_api_accessible(): bool {
		// Try to query first page of plugins.
		$result = self::query_plugins(
			[
				'page'     => 1,
				'per_page' => 1,
			]
		);

		return false !== $result;
	}

	/**
	 * Log an error message.
	 *
	 * Only logs when WP_DEBUG is enabled to avoid polluting production logs.
	 * This is the WordPress-recommended way to handle debug logging.
	 *
	 * @since 0.1.0
	 * @param string $message Error message to log.
	 * @return void
	 */
	private static function log_error( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Conditional logging when WP_DEBUG is enabled.
			error_log( $message );
		}
	}
}
