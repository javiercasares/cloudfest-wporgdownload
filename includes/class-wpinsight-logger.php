<?php
/**
 * Centralized Logging System
 *
 * Provides enterprise-grade error tracking, monitoring, and alerting for the
 * CloudFest WPOrg Download plugin.
 *
 * Features:
 * - 5 severity levels (EMERGENCY, ERROR, WARNING, INFO, DEBUG)
 * - Database persistence in wpinsight_error_log table
 * - Email notifications for critical errors (opt-in, rate-limited)
 * - Admin notice integration
 * - Auto-cleanup of logs older than 30 days
 *
 * @package    CloudFest_WPOrgDownload
 * @subpackage Includes
 * @since      1.1.0
 * @author     CloudFest Team
 * @license    GPL-3.0-or-later
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized logging class.
 *
 * This class uses static methods only and is never instantiated.
 * It provides PSR-3 style logging with WordPress integration.
 *
 * @since 1.1.0
 */
final class WPInsight_Logger {

	/**
	 * Severity levels.
	 *
	 * @since 1.1.0
	 * @var string EMERGENCY System is unusable (sends email).
	 */
	public const EMERGENCY = 'emergency';

	/**
	 * Severity level ERROR.
	 *
	 * @since 1.1.0
	 * @var string ERROR Error conditions (sends email).
	 */
	public const ERROR = 'error';

	/**
	 * Severity level WARNING.
	 *
	 * @since 1.1.0
	 * @var string WARNING Warning conditions.
	 */
	public const WARNING = 'warning';

	/**
	 * Severity level INFO.
	 *
	 * @since 1.1.0
	 * @var string INFO Informational messages.
	 */
	public const INFO = 'info';

	/**
	 * Severity level DEBUG.
	 *
	 * @since 1.1.0
	 * @var string DEBUG Debug-level messages.
	 */
	public const DEBUG = 'debug';

	/**
	 * Email notification rate limit (seconds).
	 *
	 * @since 1.1.0
	 * @var int RATE_LIMIT_SECONDS Rate limit for email notifications (1 hour).
	 */
	private const RATE_LIMIT_SECONDS = 3600;

	/**
	 * Recursion prevention flag.
	 *
	 * @since 1.1.0
	 * @var bool $is_logging Flag to prevent infinite recursion.
	 */
	private static bool $is_logging = false;

	/**
	 * Initialize logger.
	 *
	 * Sets up hooks for admin notices and scheduled cleanup.
	 * Call this on plugins_loaded hook.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function init(): void {
		// Display admin notices for recent errors.
		add_action( 'admin_notices', [ self::class, 'display_admin_notices' ] );
	}

	/**
	 * Log an EMERGENCY message.
	 *
	 * System is unusable. Sends email notification if enabled.
	 *
	 * @since 1.1.0
	 * @param string               $message The log message.
	 * @param array<string, mixed> $context Optional. Additional context data.
	 * @return void
	 */
	public static function emergency( string $message, array $context = [] ): void {
		self::log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Log an ERROR message.
	 *
	 * Error conditions. Sends email notification if enabled.
	 *
	 * @since 1.1.0
	 * @param string               $message The log message.
	 * @param array<string, mixed> $context Optional. Additional context data.
	 * @return void
	 */
	public static function error( string $message, array $context = [] ): void {
		self::log( self::ERROR, $message, $context );
	}

	/**
	 * Log a WARNING message.
	 *
	 * Warning conditions. Does not send email.
	 *
	 * @since 1.1.0
	 * @param string               $message The log message.
	 * @param array<string, mixed> $context Optional. Additional context data.
	 * @return void
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::log( self::WARNING, $message, $context );
	}

	/**
	 * Log an INFO message.
	 *
	 * Informational messages. Does not send email.
	 *
	 * @since 1.1.0
	 * @param string               $message The log message.
	 * @param array<string, mixed> $context Optional. Additional context data.
	 * @return void
	 */
	public static function info( string $message, array $context = [] ): void {
		self::log( self::INFO, $message, $context );
	}

	/**
	 * Log a DEBUG message.
	 *
	 * Debug-level messages. Only logged when WP_DEBUG is true.
	 *
	 * @since 1.1.0
	 * @param string               $message The log message.
	 * @param array<string, mixed> $context Optional. Additional context data.
	 * @return void
	 */
	public static function debug( string $message, array $context = [] ): void {
		// Only log debug messages when WP_DEBUG is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		self::log( self::DEBUG, $message, $context );
	}

	/**
	 * Get recent error logs.
	 *
	 * Retrieves the most recent log entries from the database.
	 *
	 * @since 1.1.0
	 * @param int         $limit    Optional. Maximum number of logs to retrieve. Default 50.
	 * @param string|null $severity Optional. Filter by severity level. Default null (all).
	 * @return array<int, array<string, mixed>> Array of log entries with id, severity, message, context, created_at.
	 */
	public static function get_recent_errors( int $limit = 50, ?string $severity = null ): array {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'error_log' );

		// Safety check: Verify table exists to prevent infinite loops.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $table_exists ) {
			return []; // Table doesn't exist yet, return empty array.
		}

		$limit = max( 1, min( $limit, 1000 ) ); // Clamp between 1 and 1000.

		if ( null !== $severity ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, severity, message, context, created_at
					FROM %i
					WHERE severity = %s
					ORDER BY created_at DESC
					LIMIT %d',
					$table,
					$severity,
					$limit
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, severity, message, context, created_at
					FROM %i
					ORDER BY created_at DESC
					LIMIT %d',
					$table,
					$limit
				),
				ARRAY_A
			);
		}

		// Decode context JSON.
		if ( is_array( $results ) ) {
			foreach ( $results as &$row ) {
				if ( ! empty( $row['context'] ) ) {
					$decoded        = json_decode( $row['context'], true );
					$row['context'] = is_array( $decoded ) ? $decoded : [];
				} else {
					$row['context'] = [];
				}
			}
		}

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Clear old log entries.
	 *
	 * Deletes log entries older than the specified number of days.
	 *
	 * @since 1.1.0
	 * @param int $days Optional. Delete logs older than this many days. Default 30.
	 * @return int Number of rows deleted.
	 */
	public static function clear_old_logs( int $days = 30 ): int {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'error_log' );

		// Safety check: Verify table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $table_exists ) {
			return 0; // Table doesn't exist yet.
		}

		$days = max( 1, $days ); // At least 1 day.

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)',
				$table,
				$days
			)
		);

		return is_numeric( $deleted ) ? (int) $deleted : 0;
	}

	/**
	 * Internal log method.
	 *
	 * Handles the actual logging to database and triggers email notifications.
	 *
	 * @since 1.1.0
	 * @param string               $severity The severity level.
	 * @param string               $message  The log message.
	 * @param array<string, mixed> $context  Optional. Additional context data.
	 * @return void
	 */
	private static function log( string $severity, string $message, array $context = [] ): void {
		global $wpdb;

		// Prevent infinite recursion: if we're already logging, bail out.
		if ( self::$is_logging ) {
			return;
		}

		self::$is_logging = true;

		// Validate severity.
		$valid_severities = [ self::EMERGENCY, self::ERROR, self::WARNING, self::INFO, self::DEBUG ];
		if ( ! in_array( $severity, $valid_severities, true ) ) {
			$severity = self::ERROR;
		}

		// Prepare context as JSON.
		$context_json = ! empty( $context ) ? wp_json_encode( $context ) : null;

		// Insert into database.
		$table = WPInsight_DB::get_table_name( 'error_log' );

		// Safety check: Verify table exists before attempting insert.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table,
				[
					'severity'   => $severity,
					'message'    => $message,
					'context'    => $context_json,
					'created_at' => current_time( 'mysql' ),
				],
				[ '%s', '%s', '%s', '%s' ]
			);
		}

		// Also log to PHP error_log for immediate debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$context_str = ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '';
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[WPInsight:%s] %s%s', strtoupper( $severity ), $message, $context_str ) );
		}

		// Send email notification for critical errors.
		if ( in_array( $severity, [ self::EMERGENCY, self::ERROR ], true ) ) {
			self::maybe_send_email_notification( $severity, $message, $context );
		}

		// Reset recursion flag.
		self::$is_logging = false;
	}

	/**
	 * Send email notification for critical errors.
	 *
	 * Rate-limited to 1 email per hour per error type.
	 *
	 * @since 1.1.0
	 * @param string               $severity The severity level.
	 * @param string               $message  The log message.
	 * @param array<string, mixed> $context  Optional. Additional context data.
	 * @return void
	 */
	private static function maybe_send_email_notification( string $severity, string $message, array $context = [] ): void {
		// Check if email notifications are enabled.
		$settings = get_option( WPINSIGHT_SETTINGS_OPTION, [] );

		if ( empty( $settings['admin_email_notifications_enabled'] ) ) {
			return;
		}

		// Rate limiting: check transient.
		$transient_key = 'wpinsight_email_sent_' . md5( $severity . $message );
		if ( get_transient( $transient_key ) ) {
			return; // Email already sent recently for this error.
		}

		// Get recipient email.
		$to = ! empty( $settings['admin_notification_email'] )
			? $settings['admin_notification_email']
			: get_option( 'admin_email' );

		// Prepare email.
		$subject = sprintf(
			'[%s] WPInsight %s: %s',
			wp_parse_url( home_url(), PHP_URL_HOST ),
			strtoupper( $severity ),
			wp_trim_words( $message, 10 )
		);

		$body = sprintf(
			"A critical error occurred in WPInsight:\n\n" .
			"Severity: %s\n" .
			"Time: %s\n" .
			"Message: %s\n\n",
			strtoupper( $severity ),
			current_time( 'mysql' ),
			$message
		);

		if ( ! empty( $context ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Used for email formatting, not debug output.
			$body .= "Context:\n" . print_r( $context, true ) . "\n\n";
		}

		$body .= sprintf(
			"View error logs: %s\n",
			admin_url( 'admin.php?page=wpinsight' )
		);

		// Send email.
		wp_mail( $to, $subject, $body );

		// Set rate limit transient.
		set_transient( $transient_key, true, self::RATE_LIMIT_SECONDS );
	}

	/**
	 * Display admin notices for recent critical errors.
	 *
	 * Shows a dismissible notice if there are recent EMERGENCY or ERROR logs.
	 * The notice stays dismissed until NEW errors occur.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function display_admin_notices(): void {
		// Only show on WPInsight admin pages.
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'wpinsight' ) ) {
			return;
		}

		// Get latest error timestamp.
		$latest_error_time = self::get_latest_error_timestamp();
		if ( ! $latest_error_time ) {
			return; // No errors.
		}

		// Check if user dismissed errors before this latest error.
		$user_id             = get_current_user_id();
		$dismissed_at        = get_user_meta( $user_id, 'wpinsight_errors_dismissed_at', true );
		$dismissed_timestamp = $dismissed_at ? strtotime( $dismissed_at ) : 0;

		// Only show if latest error is newer than dismiss action.
		if ( $dismissed_timestamp && $latest_error_time <= $dismissed_timestamp ) {
			return; // User already dismissed these errors.
		}

		// Count recent critical errors (last 1 hour).
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'error_log' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$error_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i
				WHERE severity IN (%s, %s)
				AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
				$table,
				self::EMERGENCY,
				self::ERROR
			)
		);

		if ( $error_count > 0 ) {
			printf(
				'<div class="notice notice-error is-dismissible" data-wpinsight-notice="errors" data-latest-error="%s"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
				esc_attr( gmdate( 'Y-m-d H:i:s', $latest_error_time ) ),
				esc_html__( 'WPInsight Error:', 'cloudfest-wporgdownload' ),
				esc_html(
					sprintf(
						// translators: %d is the number of errors.
						_n(
							'%d critical error occurred in the last hour.',
							'%d critical errors occurred in the last hour.',
							$error_count,
							'cloudfest-wporgdownload'
						),
						$error_count
					)
				),
				esc_url( admin_url( 'admin.php?page=wpinsight-error-log' ) ),
				esc_html__( 'View Error Log', 'cloudfest-wporgdownload' )
			);
		}
	}

	/**
	 * Get the timestamp of the latest critical error.
	 *
	 * Returns the created_at timestamp of the most recent EMERGENCY or ERROR log entry.
	 *
	 * @since 1.1.0
	 * @return int|false Unix timestamp of latest error, or false if none.
	 */
	private static function get_latest_error_timestamp(): int|false {
		global $wpdb;

		$table = WPInsight_DB::get_table_name( 'error_log' );

		// Safety check: Verify table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $table_exists ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$latest = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT UNIX_TIMESTAMP(created_at) FROM %i
				WHERE severity IN (%s, %s)
				ORDER BY created_at DESC
				LIMIT 1',
				$table,
				self::EMERGENCY,
				self::ERROR
			)
		);

		return $latest ? (int) $latest : false;
	}

	/**
	 * Handle AJAX request to dismiss error notice.
	 *
	 * Stores the current timestamp in user meta so the notice won't appear
	 * again until NEW errors occur.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function ajax_dismiss_errors(): void {
		// Verify nonce.
		check_ajax_referer( 'wpinsight_dismiss_errors', 'nonce' );

		// Store current timestamp as dismiss time.
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'wpinsight_errors_dismissed_at', gmdate( 'Y-m-d H:i:s' ) );

		wp_send_json_success();
	}
}
