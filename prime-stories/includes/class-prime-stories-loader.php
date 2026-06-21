<?php
/**
 * Main plugin loader.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prime Stories loader class.
 */
class Prime_Stories_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Loader|null
	 */
	private static $instance = null;

	/**
	 * Whether the plugin has been booted.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Loader
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
		$this->load_dependencies();
	}

	/**
	 * Load plugin dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-logger.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-post-types.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-meta-boxes.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-shortcode.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-assets.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-display-rules.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-analytics.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-rest-api.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-elementor.php';
		require_once PRIME_STORIES_DIR . 'admin/class-prime-stories-admin.php';
		require_once PRIME_STORIES_DIR . 'public/class-prime-stories-public.php';
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function run() {
		if ( $this->booted ) {
			return;
		}

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_privacy_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_privacy_eraser' ) );
		add_action( 'prime_stories_daily_maintenance', array( $this, 'expire_finished_stories' ) );

		Prime_Stories_Logger::get_instance();
		Prime_Stories_Post_Types::get_instance();
		Prime_Stories_Assets::get_instance();
		Prime_Stories_Admin::get_instance();
		Prime_Stories_Meta_Boxes::get_instance();
		Prime_Stories_Public::get_instance();
		Prime_Stories_Shortcode::get_instance();
		Prime_Stories_Display_Rules::get_instance();
		Prime_Stories_Analytics::get_instance();
		Prime_Stories_REST_API::get_instance();
		Prime_Stories_Elementor::get_instance();
		$this->ensure_scheduled_events();

		$this->booted = true;
	}

	/**
	 * Ensure recurring maintenance events exist.
	 *
	 * @return void
	 */
	private function ensure_scheduled_events() {
		if ( ! wp_next_scheduled( 'prime_stories_daily_analytics_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'prime_stories_daily_analytics_cleanup' );
		}

		if ( ! wp_next_scheduled( 'prime_stories_daily_maintenance' ) ) {
			wp_schedule_event( time() + ( 2 * HOUR_IN_SECONDS ), 'daily', 'prime_stories_daily_maintenance' );
		}
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'prime-stories', false, dirname( PRIME_STORIES_BASENAME ) . '/languages' );
	}

	/**
	 * Register privacy exporter.
	 *
	 * @param array<string, mixed> $exporters Exporters.
	 * @return array<string, mixed>
	 */
	public function register_privacy_exporter( $exporters ) {
		$exporters['prime-stories'] = array(
			'exporter_friendly_name' => __( 'Prime Stories', 'prime-stories' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Register privacy eraser.
	 *
	 * @param array<string, mixed> $erasers Erasers.
	 * @return array<string, mixed>
	 */
	public function register_privacy_eraser( $erasers ) {
		$erasers['prime-stories'] = array(
			'eraser_friendly_name' => __( 'Prime Stories', 'prime-stories' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Export Prime Stories personal data.
	 *
	 * @param string $email_address Request email.
	 * @return array<string, mixed>
	 */
	public function export_personal_data( $email_address ) {
		global $wpdb;

		$user = get_user_by( 'email', $email_address );
		$data = array();

		if ( $user ) {
			$seen = prime_stories_get_seen_story_ids( (int) $user->ID );

			if ( ! empty( $seen ) ) {
				$data[] = array(
					'group_id'    => 'prime-stories-seen',
					'group_label' => __( 'Prime Stories seen state', 'prime-stories' ),
					'item_id'     => 'prime-stories-seen-' . (int) $user->ID,
					'data'        => array(
						array(
							'name'  => __( 'Seen story IDs', 'prime-stories' ),
							'value' => implode( ', ', array_map( 'absint', $seen ) ),
						),
					),
				);
			}

			$analytics = Prime_Stories_Analytics::get_instance();
			if ( $analytics->table_exists() ) {
				$table = $analytics->get_table_name();
				$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT story_id, slide_id, event_type, event_value, action_payload, created_at FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 200", (int) $user->ID ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				foreach ( is_array( $rows ) ? $rows : array() as $index => $row ) {
					$data[] = array(
						'group_id'    => 'prime-stories-analytics',
						'group_label' => __( 'Prime Stories analytics', 'prime-stories' ),
						'item_id'     => 'prime-stories-analytics-' . (int) $user->ID . '-' . $index,
						'data'        => array(
							array( 'name' => __( 'Story ID', 'prime-stories' ), 'value' => (string) $row['story_id'] ),
							array( 'name' => __( 'Slide ID', 'prime-stories' ), 'value' => (string) $row['slide_id'] ),
							array( 'name' => __( 'Event', 'prime-stories' ), 'value' => (string) $row['event_type'] ),
							array( 'name' => __( 'Value', 'prime-stories' ), 'value' => (string) $row['event_value'] ),
							array( 'name' => __( 'Reply', 'prime-stories' ), 'value' => (string) $row['action_payload'] ),
							array( 'name' => __( 'Created', 'prime-stories' ), 'value' => (string) $row['created_at'] ),
						),
					);
				}
			}
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * Erase Prime Stories personal data.
	 *
	 * @param string $email_address Request email.
	 * @return array<string, mixed>
	 */
	public function erase_personal_data( $email_address ) {
		global $wpdb;

		$user = get_user_by( 'email', $email_address );
		$items_removed = false;
		$items_retained = false;

		if ( $user ) {
			delete_user_meta( (int) $user->ID, 'prime_stories_seen_story_ids' );
			$items_removed = true;

			$analytics = Prime_Stories_Analytics::get_instance();
			if ( $analytics->table_exists() ) {
				$table = $analytics->get_table_name();
				$updated = $wpdb->update(
					$table,
					array(
						'user_id'        => null,
						'action_payload' => null,
					),
					array( 'user_id' => (int) $user->ID ),
					array( '%d', '%s' ),
					array( '%d' )
				);

				if ( false !== $updated ) {
					$items_removed = true;
				} else {
					$items_retained = true;
				}
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Move finished stories to expired status.
	 *
	 * @return void
	 */
	public function expire_finished_stories() {
		$query = new WP_Query(
			array(
				'post_type'              => 'prime_story',
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'meta_query'             => array(
					'relation' => 'AND',
					array(
						'key'     => 'prime_stories_end_datetime',
						'value'   => current_time( 'mysql' ),
						'compare' => '<',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => 'prime_stories_story_status',
						'value'   => array( 'active', 'scheduled' ),
						'compare' => 'IN',
					),
				),
			)
		);

		foreach ( $query->posts as $story_id ) {
			update_post_meta( (int) $story_id, 'prime_stories_story_status', 'expired' );
		}
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		$settings = get_option( 'prime_stories_settings', array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		update_option( 'prime_stories_settings', wp_parse_args( $settings, prime_stories_get_default_settings() ) );

		if ( ! is_array( get_option( 'prime_stories_display_rules', array() ) ) ) {
			update_option( 'prime_stories_display_rules', array() );
		}

		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-post-types.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-analytics.php';
		require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-logger.php';

		Prime_Stories_Post_Types::get_instance()->register();
		Prime_Stories_Analytics::get_instance()->install();

		self::get_instance()->ensure_scheduled_events();

		update_option( 'prime_stories_version', PRIME_STORIES_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'prime_stories_daily_analytics_cleanup' );
		wp_clear_scheduled_hook( 'prime_stories_daily_maintenance' );
		flush_rewrite_rules();
	}
}
