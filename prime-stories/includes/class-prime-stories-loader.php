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
		flush_rewrite_rules();
	}
}
