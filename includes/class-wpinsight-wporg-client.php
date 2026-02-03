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
	 * @param array<string, mixed> $args {
	 *     Query arguments.
	 *
	 *     @type string $browse  Browse type (updated, popular, new, favorites). Default 'updated'.
	 *     @type int    $page    Page number (1-indexed). Default 1.
	 *     @type int    $per_page Number of results per page. Default 250.
	 * }
	 * @return array<string, mixed>|false {
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
			'per_page' => 250,
		];

		$args = wp_parse_args( $args, $defaults );

		// Build query string URL (GET method).
		$url = add_query_arg(
			[
				'action'                    => 'query_plugins',
				'request[browse]'           => $args['browse'],
				'request[page]'             => $args['page'],
				'request[per_page]'         => $args['per_page'],
				'request[fields][versions]' => '1',
			],
			self::PLUGINS_API_URL
		);

		return self::make_get_request( $url );
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
	 * @return array<string, mixed>|false Plugin information object or false on failure.
	 */
	public static function get_plugin_info( string $slug ): array|false {
		if ( empty( $slug ) ) {
			return false;
		}

		// Build query string URL (GET method).
		$url = add_query_arg(
			[
				'action'                             => 'plugin_information',
				'request[slug]'                      => $slug,
				'request[fields][versions]'          => '1',
				'request[fields][downloaded]'        => '1',
				'request[fields][active_installs]'   => '1',
				'request[fields][description]'       => '1',
				'request[fields][short_description]' => '1',
				'request[fields][sections]'          => '0',
				'request[fields][compatibility]'     => '0',
				'request[fields][screenshots]'       => '0',
				'request[fields][tags]'              => '1',
				'request[fields][rating]'            => '1',
				'request[fields][ratings]'           => '1',
				'request[fields][num_ratings]'       => '1',
				'request[fields][support_threads]'   => '0',
				'request[fields][support_threads_resolved]' => '0',
				'request[fields][homepage]'          => '1',
				'request[fields][donate_link]'       => '1',
			],
			self::PLUGINS_API_URL
		);

		return self::make_get_request( $url );
	}

	/**
	 * Query themes from WordPress.org API.
	 *
	 * Fetches a paginated list of themes using the browse endpoint.
	 * This is used for the initial sync to discover all themes.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $args {
	 *     Query arguments.
	 *
	 *     @type string $browse  Browse type (updated, popular, new, featured). Default 'updated'.
	 *     @type int    $page    Page number (1-indexed). Default 1.
	 *     @type int    $per_page Number of results per page. Default 250.
	 * }
	 * @return array<string, mixed>|false {
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
			'per_page' => 250,
		];

		$args = wp_parse_args( $args, $defaults );

		// Build query string URL (GET method).
		$url = add_query_arg(
			[
				'action'                    => 'query_themes',
				'request[browse]'           => $args['browse'],
				'request[page]'             => $args['page'],
				'request[per_page]'         => $args['per_page'],
				'request[fields][versions]' => '1',
			],
			self::THEMES_API_URL
		);

		return self::make_get_request( $url );
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
	 * @return array<string, mixed>|false Theme information object or false on failure.
	 */
	public static function get_theme_info( string $slug ): array|false {
		if ( empty( $slug ) ) {
			return false;
		}

		// Build query string URL (GET method).
		$url = add_query_arg(
			[
				'action'                           => 'theme_information',
				'request[slug]'                    => $slug,
				'request[fields][versions]'        => '1',
				'request[fields][downloaded]'      => '1',
				'request[fields][active_installs]' => '1',
				'request[fields][description]'     => '1',
				'request[fields][sections]'        => '0',
				'request[fields][screenshot_url]'  => '0',
				'request[fields][screenshots]'     => '0',
				'request[fields][tags]'            => '1',
				'request[fields][rating]'          => '1',
				'request[fields][ratings]'         => '1',
				'request[fields][num_ratings]'     => '1',
				'request[fields][homepage]'        => '1',
			],
			self::THEMES_API_URL
		);

		return self::make_get_request( $url );
	}

	/**
	 * Make HTTP GET request to WordPress.org API.
	 *
	 * Handles the low-level HTTP communication with retry logic and error handling.
	 * All API methods should use this method to make requests.
	 *
	 * @since 0.1.0
	 * @param string $url Complete API URL with query string parameters.
	 * @return array<string, mixed>|false Response data or false on failure.
	 */
	private static function make_get_request( string $url ): array|false {
		$attempt = 0;

		while ( $attempt < self::MAX_RETRIES ) {
			++$attempt;

			$response = wp_remote_get(
				$url,
				[
					'timeout' => self::HTTP_TIMEOUT,
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
	 * Check API health.
	 *
	 * Performs a lightweight health check on the WordPress.org API.
	 * Returns response data or WP_Error on failure.
	 *
	 * @since 1.5.0
	 * @return array<string, mixed>|WP_Error Response data or error.
	 */
	public static function check_api_health(): array|WP_Error {
		// Make a minimal API request to check health.
		$response = wp_remote_get(
			self::PLUGINS_API_URL,
			[
				'timeout' => 10,
				'headers' => [
					'User-Agent' => 'WPInsight/' . WPINSIGHT_VERSION,
				],
				'body'    => [
					'action'  => 'query_plugins',
					'request' => wp_json_encode(
						[
							'browse'   => 'updated',
							'page'     => 1,
							'per_page' => 1,
						]
					),
				],
			]
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check HTTP status.
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return new WP_Error(
				'api_error',
				sprintf( 'HTTP %d', $status_code )
			);
		}

		// Try to decode JSON.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( null === $data ) {
			return new WP_Error(
				'json_error',
				'Invalid JSON response'
			);
		}

		return $data;
	}

	/**
	 * Log an error message.
	 *
	 * Only logs when WP_DEBUG is enabled to avoid polluting production logs.
	 * Uses centralized logger (v1.1.0+).
	 *
	 * @since 0.1.0
	 * @param string $message Error message to log.
	 * @return void
	 */
	private static function log_error( string $message ): void {
		WPInsight_Logger::error( $message );
	}
}
