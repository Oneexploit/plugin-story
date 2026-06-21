<?php
/**
 * Shortcode support.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register and render shortcodes.
 */
class Prime_Stories_Shortcode {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Shortcode|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Shortcode
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
		add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'prime_stories', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the [prime_stories] shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'group'      => '',
				'layout'     => prime_stories_get_setting( 'default_layout', 'circle' ),
				'limit'      => 10,
				'autoplay'   => 'true',
				'show_title' => 'true',
				'size'       => 'medium',
				'class'      => '',
				'open_mode'  => 'fullscreen',
				'fit_mode'   => prime_stories_get_setting( 'viewer_fit_mode', 'cover' ),
				'overlay'    => prime_stories_get_setting( 'overlay_opacity', 70 ),
			),
			$atts,
			'prime_stories'
		);

		return Prime_Stories_Public::get_instance()->render_stories(
			array(
				'group'      => sanitize_text_field( (string) $atts['group'] ),
				'layout'     => prime_stories_sanitize_select( $atts['layout'], array( 'circle', 'square', 'slider', 'floating' ), prime_stories_get_setting( 'default_layout', 'circle' ) ),
				'limit'      => absint( $atts['limit'] ),
				'autoplay'   => filter_var( $atts['autoplay'], FILTER_VALIDATE_BOOLEAN ),
				'show_title' => filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN ),
				'size'       => prime_stories_sanitize_select( $atts['size'], array( 'small', 'medium', 'large' ), 'medium' ),
				'class'      => prime_stories_sanitize_class_list( (string) $atts['class'] ),
				'open_mode'  => prime_stories_sanitize_select( $atts['open_mode'], array( 'fullscreen', 'popup' ), 'fullscreen' ),
				'fit_mode'   => prime_stories_sanitize_select( $atts['fit_mode'], array( 'cover', 'contain' ), prime_stories_get_setting( 'viewer_fit_mode', 'cover' ) ),
				'overlay_opacity' => max( 0, min( 100, absint( $atts['overlay'] ) ) ),
				'source'     => 'shortcode',
			)
		);
	}
}
