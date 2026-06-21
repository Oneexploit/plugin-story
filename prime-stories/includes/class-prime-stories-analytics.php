<?php
/**
 * Analytics storage and reporting.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Analytics manager.
 */
class Prime_Stories_Analytics {

	/**
	 * Current schema version.
	 */
	private const DB_VERSION = '1.2.0';

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Analytics|null
	 */
	private static $instance = null;

	/**
	 * Table existence cache.
	 *
	 * @var bool|null
	 */
	private $table_exists = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );
		add_action( 'rest_api_init', array( $this, 'maybe_upgrade' ) );
		add_action( 'prime_stories_daily_analytics_cleanup', array( $this, 'cleanup_old_events' ) );
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Analytics
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get analytics table name.
	 *
	 * @return string
	 */
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'prime_stories_analytics';
	}

	/**
	 * Create analytics table.
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = $this->get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			story_id BIGINT UNSIGNED NOT NULL,
			event_type VARCHAR(50) NOT NULL,
			event_value VARCHAR(100) NULL,
			user_id BIGINT UNSIGNED NULL,
			session_id VARCHAR(191) NULL,
			source VARCHAR(100) NULL,
			device_type VARCHAR(20) NOT NULL DEFAULT 'desktop',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY story_id (story_id),
			KEY story_event (story_id, event_type),
			KEY event_type (event_type),
			KEY event_value (event_value),
			KEY session_id (session_id),
			KEY source (source),
			KEY device_type (device_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
		$this->table_exists = null;

		if ( ! $this->table_exists() ) {
			prime_stories_log( 'error', 'Analytics table creation did not complete successfully.', array( 'table' => $table_name ), 'analytics.install' );
			return;
		}

		prime_stories_log( 'info', 'Analytics table is ready.', array( 'table' => $table_name ), 'analytics.install' );
	}

	/**
	 * Install or upgrade analytics storage.
	 *
	 * @return void
	 */
	public function install() {
		$this->create_table();

		if ( $this->table_exists() ) {
			update_option( 'prime_stories_db_version', self::DB_VERSION );
			$this->cleanup_old_events();
		}
	}

	/**
	 * Upgrade storage when the schema version changes.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		if ( get_option( 'prime_stories_db_version', '' ) === self::DB_VERSION ) {
			return;
		}

		$this->install();
	}

	/**
	 * Determine whether the analytics table exists.
	 *
	 * @return bool
	 */
	public function table_exists() {
		global $wpdb;

		if ( null !== $this->table_exists ) {
			return $this->table_exists;
		}

		$table_name         = $this->get_table_name();
		$prepared_statement = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		$this->table_exists = $table_name === $wpdb->get_var( $prepared_statement ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $this->table_exists;
	}

	/**
	 * Track a story event.
	 *
	 * @param int    $story_id Story ID.
	 * @param string $event_type Event type.
	 * @param string $session_id Session identifier.
	 * @param int    $user_id User ID.
	 * @return bool
	 */
	public function track_event( $story_id, $event_type, $session_id = '', $user_id = 0, $source = '', $event_value = '' ) {
		global $wpdb;

		if ( ! prime_stories_is_enabled( prime_stories_get_setting( 'enable_analytics', 'yes' ) ) ) {
			return false;
		}

		if ( ! $this->table_exists() ) {
			prime_stories_log( 'error', 'Analytics event could not be stored because the analytics table is missing.', array( 'story_id' => $story_id, 'event_type' => $event_type ), 'analytics.track_event' );
			return false;
		}

		$story_id   = absint( $story_id );
		$event_type = sanitize_key( $event_type );
		$user_id    = absint( $user_id );
		$session_id = prime_stories_sanitize_session_id( $session_id );
		$source     = sanitize_key( (string) $source );
		$event_value = sanitize_key( (string) $event_value );

		if ( 'prime_story' !== get_post_type( $story_id ) || 'publish' !== get_post_status( $story_id ) || ! in_array( $event_type, array( 'impression', 'open', 'complete', 'click', 'reaction', 'reply' ), true ) ) {
			prime_stories_log( 'warning', 'Analytics event rejected because the story or event type was invalid.', array( 'story_id' => $story_id, 'event_type' => $event_type ), 'analytics.track_event' );
			return false;
		}

		$rate_key = 'prime_stories_' . md5( $story_id . '|' . $event_type . '|' . $session_id . '|' . $user_id . '|' . $this->get_request_fingerprint() );

		if ( get_transient( $rate_key ) ) {
			return false;
		}

		set_transient( $rate_key, 1, 10 );

		$data = array(
			'story_id'    => $story_id,
			'event_type'  => $event_type,
			'event_value' => $event_value ? substr( $event_value, 0, 100 ) : null,
			'user_id'     => $user_id ? $user_id : null,
			'session_id'  => $session_id ? $session_id : null,
			'source'      => $source ? substr( $source, 0, 100 ) : null,
			'device_type' => prime_stories_is_mobile_request() ? 'mobile' : 'desktop',
			'created_at'  => current_time( 'mysql' ),
		);

		$format = array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' );

		$inserted = $wpdb->insert( $this->get_table_name(), $data, $format );

		if ( false === $inserted ) {
			prime_stories_log(
				'error',
				'Database insert failed while recording an analytics event.',
				array(
					'story_id'   => $story_id,
					'event_type' => $event_type,
					'db_error'   => $wpdb->last_error,
				),
				'analytics.track_event'
			);
		}

		return false !== $inserted;
	}

	/**
	 * Get aggregate analytics summary.
	 *
	 * @return array<string, float|int>
	 */
	public function get_summary() {
		global $wpdb;

		if ( ! $this->table_exists() ) {
			return array(
				'impression'      => 0,
				'open'            => 0,
				'complete'        => 0,
				'click'           => 0,
				'reaction'        => 0,
				'reply'           => 0,
				'unique_sessions' => 0,
				'ctr'             => 0,
				'completion_rate' => 0,
			);
		}

		$table   = $this->get_table_name();
		$results = $wpdb->get_results( "SELECT event_type, COUNT(*) AS total FROM {$table} GROUP BY event_type", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( null === $results && ! empty( $wpdb->last_error ) ) {
			prime_stories_log( 'error', 'Analytics summary query failed.', array( 'db_error' => $wpdb->last_error ), 'analytics.summary' );
		}

		$summary = array(
			'impression'      => 0,
			'open'            => 0,
			'complete'        => 0,
			'click'           => 0,
			'reaction'        => 0,
			'reply'           => 0,
			'unique_sessions' => 0,
			'ctr'             => 0,
			'completion_rate' => 0,
		);

		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				$event_type = $row['event_type'];

				if ( isset( $summary[ $event_type ] ) ) {
					$summary[ $event_type ] = (int) $row['total'];
				}
			}
		}

		$unique_sessions = $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE session_id IS NOT NULL AND session_id != ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$summary['unique_sessions'] = absint( $unique_sessions );

		if ( $summary['open'] > 0 ) {
			$summary['completion_rate'] = ( $summary['complete'] / $summary['open'] ) * 100;
		}

		if ( $summary['impression'] > 0 ) {
			$summary['ctr'] = ( $summary['click'] / $summary['impression'] ) * 100;
		}

		return $summary;
	}

	/**
	 * Get top stories.
	 *
	 * @param int $limit Row limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_top_stories( $limit = 10 ) {
		global $wpdb;

		if ( ! $this->table_exists() ) {
			return array();
		}

		$table = $this->get_table_name();
		$limit = max( 1, absint( $limit ) );

		$sql = $wpdb->prepare(
			"SELECT story_id,
				SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END) AS impression_count,
				SUM(CASE WHEN event_type = 'open' THEN 1 ELSE 0 END) AS open_count,
				SUM(CASE WHEN event_type = 'complete' THEN 1 ELSE 0 END) AS complete_count,
				SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) AS click_count,
				SUM(CASE WHEN event_type = 'reaction' THEN 1 ELSE 0 END) AS reaction_count,
				SUM(CASE WHEN event_type = 'reply' THEN 1 ELSE 0 END) AS reply_count,
				COUNT(DISTINCT session_id) AS unique_sessions
			FROM {$table}
			GROUP BY story_id
			ORDER BY open_count DESC, impression_count DESC
			LIMIT %d",
			$limit
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! is_array( $results ) ) {
			if ( ! empty( $wpdb->last_error ) ) {
				prime_stories_log( 'error', 'Top stories analytics query failed.', array( 'db_error' => $wpdb->last_error ), 'analytics.top_stories' );
			}

			return array();
		}

		foreach ( $results as &$row ) {
			$row['story_id'] = (int) $row['story_id'];
			$row['title']    = get_the_title( $row['story_id'] );
		}
		unset( $row );

		return $results;
	}

	/**
	 * Get a lightweight request fingerprint.
	 *
	 * @return string
	 */
	private function get_request_fingerprint() {
		$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return md5( $ip . '|' . $user_agent );
	}

	/**
	 * Remove analytics events older than the configured retention window.
	 *
	 * @return void
	 */
	public function cleanup_old_events() {
		global $wpdb;

		if ( ! $this->table_exists() ) {
			return;
		}

		$retention_days = max( 1, absint( prime_stories_get_setting( 'analytics_retention_days', 180 ) ) );
		$cutoff         = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );

		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $this->get_table_name() . ' WHERE created_at < %s',
				$cutoff
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
