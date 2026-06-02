<?php
/**
 * Diagnostics logger for Prime Stories.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * File-based logger with request correlation.
 */
class Prime_Stories_Logger {

	/**
	 * Maximum log file size before rotation.
	 */
	private const MAX_LOG_SIZE = 2097152;

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Logger|null
	 */
	private static $instance = null;

	/**
	 * Per-request correlation ID.
	 *
	 * @var string
	 */
	private $request_id = '';

	/**
	 * Whether shutdown fatal capture has already run.
	 *
	 * @var bool
	 */
	private $handled_shutdown = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Logger
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->request_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'prime-stories-', true );

		add_action( 'shutdown', array( $this, 'capture_fatal_error' ), 1 );
	}

	/**
	 * Write a log entry.
	 *
	 * @param string               $level Log level.
	 * @param string               $message Human-readable message.
	 * @param array<string, mixed> $context Additional context.
	 * @param string               $source Source identifier.
	 * @return bool
	 */
	public function log( $level, $message, $context = array(), $source = 'general' ) {
		$level = $this->sanitize_level( $level );

		if ( ! $this->is_enabled() ) {
			return false;
		}

		$entry = array(
			'timestamp'   => current_time( 'mysql' ),
			'level'       => $level,
			'source'      => $this->sanitize_source( $source ),
			'message'     => $this->sanitize_message( $message ),
			'context'     => $this->sanitize_context( $context ),
			'request_id'  => $this->request_id,
			'request_uri' => $this->get_request_uri(),
			'user_id'     => is_user_logged_in() ? get_current_user_id() : 0,
			'is_admin'    => is_admin(),
		);

		return $this->write_entry( $entry );
	}

	/**
	 * Write an informational log entry.
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @param string               $source Source identifier.
	 * @return bool
	 */
	public function info( $message, $context = array(), $source = 'general' ) {
		return $this->log( 'info', $message, $context, $source );
	}

	/**
	 * Write a debug log entry.
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @param string               $source Source identifier.
	 * @return bool
	 */
	public function debug( $message, $context = array(), $source = 'general' ) {
		return $this->log( 'debug', $message, $context, $source );
	}

	/**
	 * Write a warning log entry.
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @param string               $source Source identifier.
	 * @return bool
	 */
	public function warning( $message, $context = array(), $source = 'general' ) {
		return $this->log( 'warning', $message, $context, $source );
	}

	/**
	 * Write an error log entry.
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @param string               $source Source identifier.
	 * @return bool
	 */
	public function error( $message, $context = array(), $source = 'general' ) {
		return $this->log( 'error', $message, $context, $source );
	}

	/**
	 * Write a critical log entry.
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 * @param string               $source Source identifier.
	 * @return bool
	 */
	public function critical( $message, $context = array(), $source = 'general' ) {
		return $this->log( 'critical', $message, $context, $source );
	}

	/**
	 * Log a WP_Error instance.
	 *
	 * @param string   $message Message.
	 * @param WP_Error $error Error object.
	 * @param array    $context Context.
	 * @param string   $source Source identifier.
	 * @return bool
	 */
	public function wp_error( $message, WP_Error $error, $context = array(), $source = 'wp_error' ) {
		$context['wp_error'] = array(
			'code'    => $error->get_error_code(),
			'message' => $error->get_error_message(),
			'data'    => $error->get_error_data(),
		);

		return $this->error( $message, $context, $source );
	}

	/**
	 * Log an exception or throwable.
	 *
	 * @param Throwable            $exception Exception instance.
	 * @param array<string, mixed> $context Context.
	 * @param string               $source Source identifier.
	 * @return bool
	 */
	public function exception( Throwable $exception, $context = array(), $source = 'exception' ) {
		$context['exception'] = array(
			'class'   => get_class( $exception ),
			'message' => $exception->getMessage(),
			'file'    => $exception->getFile(),
			'line'    => $exception->getLine(),
			'code'    => $exception->getCode(),
		);

		return $this->critical( 'Unhandled exception captured.', $context, $source );
	}

	/**
	 * Capture fatal PHP errors on shutdown.
	 *
	 * @return void
	 */
	public function capture_fatal_error() {
		if ( $this->handled_shutdown ) {
			return;
		}

		$this->handled_shutdown = true;
		$error                  = error_get_last();

		if ( empty( $error['type'] ) || empty( $error['message'] ) ) {
			return;
		}

		if ( ! in_array( (int) $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			return;
		}

		$file = isset( $error['file'] ) ? wp_normalize_path( (string) $error['file'] ) : '';

		if ( $file && false === strpos( $file, wp_normalize_path( PRIME_STORIES_DIR ) ) ) {
			return;
		}

		$this->critical(
			'Fatal PHP error detected.',
			array(
				'php_error' => array(
					'type'    => (int) $error['type'],
					'message' => (string) $error['message'],
					'file'    => (string) $error['file'],
					'line'    => (int) $error['line'],
				),
			),
			'php.shutdown'
		);
	}

	/**
	 * Get log file information for admin screens.
	 *
	 * @return array<string, mixed>
	 */
	public function get_status() {
		$path = $this->get_log_file_path();

		return array(
			'enabled'     => $this->is_enabled(),
			'path'        => $path,
			'directory'   => $this->get_log_directory(),
			'exists'      => file_exists( $path ),
			'is_writable' => $this->is_directory_writable(),
			'size'        => file_exists( $path ) ? (int) filesize( $path ) : 0,
			'updated_at'  => file_exists( $path ) ? gmdate( 'Y-m-d H:i:s', (int) filemtime( $path ) ) : '',
			'request_id'  => $this->request_id,
		);
	}

	/**
	 * Get the correlation ID for the current request.
	 *
	 * @return string
	 */
	public function get_request_id() {
		return $this->request_id;
	}

	/**
	 * Read recent entries from the log file.
	 *
	 * @param int    $limit Maximum entry count.
	 * @param string $level Optional level filter.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_recent_entries( $limit = 200, $level = '' ) {
		$path = $this->get_log_file_path();

		if ( ! file_exists( $path ) ) {
			return array();
		}

		$lines = @file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! is_array( $lines ) || empty( $lines ) ) {
			return array();
		}

		$entries = array();
		$level   = $this->sanitize_level( $level );

		foreach ( array_reverse( $lines ) as $line ) {
			$entry = json_decode( (string) $line, true );

			if ( ! is_array( $entry ) || empty( $entry['message'] ) ) {
				continue;
			}

			if ( $level && $entry['level'] !== $level ) {
				continue;
			}

			$entries[] = $entry;

			if ( count( $entries ) >= max( 1, absint( $limit ) ) ) {
				break;
			}
		}

		return $entries;
	}

	/**
	 * Clear current and rotated log files.
	 *
	 * @return bool
	 */
	public function clear_logs() {
		$deleted  = true;
		$log_file = $this->get_log_file_path();
		$files    = array(
			$log_file,
			$log_file . '.1',
		);

		foreach ( $files as $file ) {
			if ( file_exists( $file ) && ! @unlink( $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$deleted = false;
			}
		}

		return $deleted;
	}

	/**
	 * Get the log directory path.
	 *
	 * @return string
	 */
	public function get_log_directory() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'prime-stories/logs';
	}

	/**
	 * Get the log file path.
	 *
	 * @return string
	 */
	public function get_log_file_path() {
		return trailingslashit( $this->get_log_directory() ) . 'prime-stories.log';
	}

	/**
	 * Determine whether logging is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return prime_stories_is_enabled( prime_stories_get_setting( 'enable_debug_logging', 'yes' ) );
	}

	/**
	 * Write an entry to the log file.
	 *
	 * @param array<string, mixed> $entry Entry payload.
	 * @return bool
	 */
	private function write_entry( $entry ) {
		$directory = $this->get_log_directory();

		if ( ! $this->ensure_directory( $directory ) ) {
			error_log( '[Prime Stories] ' . $entry['message'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		$this->maybe_rotate_log();

		$payload = wp_json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( ! $payload ) {
			return false;
		}

		return false !== file_put_contents( $this->get_log_file_path(), $payload . PHP_EOL, FILE_APPEND | LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Ensure the log directory exists.
	 *
	 * @param string $directory Directory path.
	 * @return bool
	 */
	private function ensure_directory( $directory ) {
		if ( is_dir( $directory ) ) {
			return is_writable( $directory );
		}

		if ( ! wp_mkdir_p( $directory ) ) {
			return false;
		}

		return is_writable( $directory );
	}

	/**
	 * Determine whether the log directory is writable.
	 *
	 * @return bool
	 */
	private function is_directory_writable() {
		$directory = $this->get_log_directory();

		if ( is_dir( $directory ) ) {
			return is_writable( $directory );
		}

		$parent = dirname( $directory );

		return is_dir( $parent ) && is_writable( $parent );
	}

	/**
	 * Rotate the log when it grows too large.
	 *
	 * @return void
	 */
	private function maybe_rotate_log() {
		$log_file = $this->get_log_file_path();

		if ( ! file_exists( $log_file ) || (int) filesize( $log_file ) < self::MAX_LOG_SIZE ) {
			return;
		}

		$rotated_file = $log_file . '.1';

		if ( file_exists( $rotated_file ) ) {
			@unlink( $rotated_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		@rename( $log_file, $rotated_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Sanitize a source identifier while preserving dots.
	 *
	 * @param string $source Source.
	 * @return string
	 */
	private function sanitize_source( $source ) {
		$source = strtolower( preg_replace( '/[^a-zA-Z0-9_\-\.]/', '', (string) $source ) );

		return $source ? $source : 'general';
	}

	/**
	 * Sanitize a log message.
	 *
	 * @param string $message Message.
	 * @return string
	 */
	private function sanitize_message( $message ) {
		$message = trim( wp_strip_all_tags( (string) $message ) );

		if ( strlen( $message ) > 500 ) {
			$message = substr( $message, 0, 497 ) . '...';
		}

		return $message;
	}

	/**
	 * Sanitize a log level.
	 *
	 * @param string $level Raw level.
	 * @return string
	 */
	private function sanitize_level( $level ) {
		$level = sanitize_key( (string) $level );

		return in_array( $level, array( 'debug', 'info', 'warning', 'error', 'critical' ), true ) ? $level : 'info';
	}

	/**
	 * Sanitize context recursively.
	 *
	 * @param mixed $value Value.
	 * @param int   $depth Current recursion depth.
	 * @return mixed
	 */
	private function sanitize_context( $value, $depth = 0 ) {
		if ( $depth > 4 ) {
			return '[max-depth]';
		}

		if ( is_wp_error( $value ) ) {
			return array(
				'code'    => $value->get_error_code(),
				'message' => $value->get_error_message(),
				'data'    => $this->sanitize_context( $value->get_error_data(), $depth + 1 ),
			);
		}

		if ( is_array( $value ) ) {
			$sanitized = array();

			foreach ( array_slice( $value, 0, 25, true ) as $key => $item ) {
				$sanitized_key               = is_string( $key ) ? preg_replace( '/[^a-zA-Z0-9_\-\.]/', '_', $key ) : (string) $key;
				$sanitized[ $sanitized_key ] = $this->sanitize_context( $item, $depth + 1 );
			}

			return $sanitized;
		}

		if ( is_object( $value ) ) {
			if ( $value instanceof Throwable ) {
				return array(
					'class'   => get_class( $value ),
					'message' => $this->sanitize_message( $value->getMessage() ),
					'file'    => $value->getFile(),
					'line'    => $value->getLine(),
				);
			}

			return '[object:' . get_class( $value ) . ']';
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		$string = (string) $value;

		if ( strlen( $string ) > 1000 ) {
			$string = substr( $string, 0, 997 ) . '...';
		}

		return sanitize_textarea_field( $string );
	}

	/**
	 * Get the current request URI.
	 *
	 * @return string
	 */
	private function get_request_uri() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );
	}
}
