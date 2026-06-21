<?php
/**
 * Asset registration and enqueue logic.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asset manager.
 */
class Prime_Stories_Assets {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Assets|null
	 */
	private static $instance = null;

	/**
	 * Whether frontend assets are required for the request.
	 *
	 * @var bool
	 */
	private $requires_public_assets = false;

	/**
	 * Whether public assets have been registered.
	 *
	 * @var bool
	 */
	private $public_assets_registered = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Assets
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
		add_filter( 'the_posts', array( $this, 'detect_shortcode_assets_in_posts' ), 10, 2 );
		add_action( 'wp', array( $this, 'detect_shortcode_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_public_assets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Mark frontend assets as required.
	 *
	 * @return void
	 */
	public function require_public_assets() {
		$this->requires_public_assets = true;
	}

	/**
	 * Detect shortcodes in queried posts.
	 *
	 * @return void
	 */
	public function detect_shortcode_assets() {
		if ( ! prime_stories_is_frontend_enabled() ) {
			return;
		}

		if ( prime_stories_is_enabled( prime_stories_get_setting( 'load_assets_globally', 'no' ) ) ) {
			$this->requires_public_assets = true;
			return;
		}

		$queried_object = get_queried_object();

		if ( isset( $queried_object->post_content ) && has_shortcode( $queried_object->post_content, 'prime_stories' ) ) {
			$this->requires_public_assets = true;
		}
	}

	/**
	 * Detect shortcode usage in queried posts.
	 *
	 * @param array<int, WP_Post> $posts Post objects.
	 * @param WP_Query            $query Query object.
	 * @return array<int, WP_Post>
	 */
	public function detect_shortcode_assets_in_posts( $posts, $query ) {
		if ( is_admin() || ! ( $query instanceof WP_Query ) || ! $query->is_main_query() || empty( $posts ) || prime_stories_is_enabled( prime_stories_get_setting( 'load_assets_globally', 'no' ) ) ) {
			return $posts;
		}

		foreach ( $posts as $post ) {
			if ( ! empty( $post->post_content ) && has_shortcode( $post->post_content, 'prime_stories' ) ) {
				$this->requires_public_assets = true;
				break;
			}
		}

		return $posts;
	}

	/**
	 * Register and optionally enqueue public assets.
	 *
	 * @return void
	 */
	public function register_public_assets() {
		if ( ! $this->public_assets_registered ) {
			wp_register_style(
				'prime-stories-public',
				PRIME_STORIES_URL . 'public/css/prime-stories-public.css',
				array(),
				PRIME_STORIES_VERSION
			);

			wp_register_script(
				'prime-stories-public',
				PRIME_STORIES_URL . 'public/js/prime-stories-public.js',
				array(),
				PRIME_STORIES_VERSION,
				true
			);

			wp_localize_script(
				'prime-stories-public',
				'primeStoriesConfig',
				array(
					'restUrl'         => esc_url_raw( rest_url( 'prime-stories/v1' ) ),
					'restNonce'       => wp_create_nonce( 'wp_rest' ),
					'publicNonce'     => wp_create_nonce( 'prime_stories_public' ),
					'requestId'       => Prime_Stories_Logger::get_instance()->get_request_id(),
					'isUserLoggedIn'  => is_user_logged_in(),
					'enableAnalytics' => prime_stories_is_enabled( prime_stories_get_setting( 'enable_analytics', 'yes' ) ),
					'enableSeenState' => prime_stories_is_enabled( prime_stories_get_setting( 'enable_seen_state', 'yes' ) ),
					'enableGuestSeen' => prime_stories_is_enabled( prime_stories_get_setting( 'enable_guest_seen_state', 'yes' ) ),
					'lazyLoadMedia'   => prime_stories_is_enabled( prime_stories_get_setting( 'lazy_load_media', 'yes' ) ),
					'guestSeenDays'   => max( 1, absint( prime_stories_get_setting( 'guest_seen_retention_days', 30 ) ) ),
					'enableLogging'   => prime_stories_is_enabled( prime_stories_get_setting( 'enable_debug_logging', 'yes' ) ),
					'enableClientLog' => prime_stories_is_enabled( prime_stories_get_setting( 'enable_client_logging', 'yes' ) ),
					'respectDnt'      => prime_stories_is_enabled( prime_stories_get_setting( 'respect_do_not_track', 'yes' ) ),
					'i18n'            => array(
						'close'  => __( 'Close story viewer', 'prime-stories' ),
						'mute'   => __( 'Mute video', 'prime-stories' ),
						'unmute' => __( 'Unmute video', 'prime-stories' ),
					),
				)
			);

			$custom_css = trim( (string) prime_stories_get_setting( 'custom_css', '' ) );

			if ( $custom_css ) {
				wp_add_inline_style( 'prime-stories-public', $custom_css );
			}

			$this->public_assets_registered = true;
		}

		if ( ! $this->requires_public_assets ) {
			return;
		}

		wp_enqueue_style( 'prime-stories-public' );
		wp_enqueue_script( 'prime-stories-public' );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$is_story_screen = 'prime_story' === $screen->post_type;
		$is_plugin_page  = false !== strpos( $hook_suffix, 'prime-stories' ) || false !== strpos( $hook_suffix, 'prime_story_page_' );

		if ( ! $is_story_screen && ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );

		wp_register_style(
			'prime-stories-admin',
			PRIME_STORIES_URL . 'assets/admin.css',
			array(),
			PRIME_STORIES_VERSION
		);

		wp_enqueue_script(
			'prime-stories-admin',
			PRIME_STORIES_URL . 'assets/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			PRIME_STORIES_VERSION,
			true
		);

		wp_enqueue_style( 'prime-stories-admin' );

		wp_localize_script(
			'prime-stories-admin',
			'primeStoriesAdminConfig',
			array(
				'mediaTitle'  => __( 'Choose media', 'prime-stories' ),
				'mediaButton' => __( 'Use this media', 'prime-stories' ),
				'emptyMedia'  => __( 'No media selected', 'prime-stories' ),
				'mediaChosen' => __( 'Media selected', 'prime-stories' ),
				'groupOptions'=> array_map(
					static function ( $term ) {
						return array(
							'id'   => (int) $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						);
					},
					prime_stories_get_story_groups()
				),
			)
		);
	}
}
