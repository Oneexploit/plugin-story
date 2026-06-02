<?php
/**
 * Frontend rendering.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public renderer for story bars and viewers.
 */
class Prime_Stories_Public {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Public|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Public
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Render a story collection.
	 *
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function render_stories( $args = array() ) {
		if ( ! prime_stories_is_frontend_enabled() ) {
			return '';
		}

		$defaults = array(
			'group'                => '',
			'layout'               => prime_stories_get_setting( 'default_layout', 'circle' ),
			'limit'                => 10,
			'autoplay'             => true,
			'show_title'           => true,
			'size'                 => 'medium',
			'class'                => '',
			'open_mode'            => 'fullscreen',
			'source'               => 'shortcode',
			'position'             => '',
			'circle_size'          => 0,
			'spacing'              => 14,
			'border_width'         => 3,
			'active_border_color'  => '',
			'seen_border_color'    => '',
			'title_color'          => '',
			'viewer_background'    => '',
			'button_background'    => '',
			'button_text_color'    => '',
			'border_radius'        => 22,
			'hide_desktop'         => false,
			'hide_tablet'          => false,
			'hide_mobile'          => false,
			'title_typography'     => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$args['layout']    = prime_stories_sanitize_select( (string) $args['layout'], array( 'circle', 'square', 'slider', 'floating' ), prime_stories_get_setting( 'default_layout', 'circle' ) );
		$args['size']      = prime_stories_sanitize_select( (string) $args['size'], array( 'small', 'medium', 'large' ), 'medium' );
		$args['open_mode'] = prime_stories_sanitize_select( (string) $args['open_mode'], array( 'fullscreen', 'popup' ), 'fullscreen' );
		$args['limit']     = max( 1, absint( $args['limit'] ) );
		$args['autoplay']  = (bool) $args['autoplay'];
		$args['show_title'] = (bool) $args['show_title'];
		$args['class']      = prime_stories_sanitize_class_list( (string) $args['class'] );

		if ( ! empty( $args['position'] ) && 0 === strpos( (string) $args['position'], 'floating_' ) ) {
			$args['layout'] = 'floating';
		}

		$stories = prime_stories_query_stories(
			array(
				'group' => $args['group'],
				'limit' => $args['limit'],
			)
		);

		if ( empty( $stories ) ) {
			return '';
		}

		Prime_Stories_Assets::get_instance()->require_public_assets();
		Prime_Stories_Assets::get_instance()->register_public_assets();

		if ( wp_style_is( 'prime-stories-public', 'registered' ) ) {
			wp_enqueue_style( 'prime-stories-public' );
		}

		if ( wp_script_is( 'prime-stories-public', 'registered' ) ) {
			wp_enqueue_script( 'prime-stories-public' );
		}

		$instance_id     = 'prime-stories-' . wp_unique_id();
		$settings        = prime_stories_get_settings();
		$wrapper_classes = $this->get_wrapper_classes( $args );
		$inline_style    = $this->get_wrapper_style( $args, $settings );
		$seen_story_ids  = is_user_logged_in() ? prime_stories_get_seen_story_ids( get_current_user_id() ) : array();

		ob_start();
		include PRIME_STORIES_DIR . 'public/templates/story-viewer.php';
		return ob_get_clean();
	}

	/**
	 * Build wrapper class list.
	 *
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	private function get_wrapper_classes( $args ) {
		$classes   = array(
			'prime-stories-wrapper',
			'prime-stories-layout-' . $args['layout'],
			'prime-stories-size-' . $args['size'],
			'prime-stories-mode-' . $args['open_mode'],
		);
		$class_map = array(
			'hide_desktop' => 'prime-stories-hide-desktop',
			'hide_tablet'  => 'prime-stories-hide-tablet',
			'hide_mobile'  => 'prime-stories-hide-mobile',
		);

		foreach ( $class_map as $key => $class_name ) {
			if ( ! empty( $args[ $key ] ) ) {
				$classes[] = $class_name;
			}
		}

		if ( ! empty( $args['class'] ) ) {
			foreach ( preg_split( '/\s+/', prime_stories_sanitize_class_list( (string) $args['class'] ) ) as $custom_class ) {

				if ( $custom_class ) {
					$classes[] = $custom_class;
				}
			}
		}

		return implode( ' ', array_unique( array_filter( $classes ) ) );
	}

	/**
	 * Build wrapper CSS custom properties.
	 *
	 * @param array<string, mixed> $args Render args.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return string
	 */
	private function get_wrapper_style( $args, $settings ) {
		$size_map = array(
			'small'  => 72,
			'medium' => (int) $settings['default_circle_size'],
			'large'  => 112,
		);

		$circle_size = absint( $args['circle_size'] ) ?: ( $size_map[ $args['size'] ] ?? (int) $settings['default_circle_size'] );
		$style_vars  = array(
			'--prime-stories-item-size'         => $circle_size . 'px',
			'--prime-stories-gap'               => max( 4, absint( $args['spacing'] ) ) . 'px',
			'--prime-stories-border-width'      => max( 1, absint( $args['border_width'] ) ) . 'px',
			'--prime-stories-active-border'     => sanitize_hex_color( (string) ( $args['active_border_color'] ?: $settings['active_border_color'] ) ) ?: $settings['active_border_color'],
			'--prime-stories-seen-border'       => sanitize_hex_color( (string) ( $args['seen_border_color'] ?: $settings['seen_border_color'] ) ) ?: $settings['seen_border_color'],
			'--prime-stories-title-color'       => sanitize_hex_color( (string) ( $args['title_color'] ?: $settings['title_color'] ) ) ?: $settings['title_color'],
			'--prime-stories-viewer-background' => sanitize_hex_color( (string) ( $args['viewer_background'] ?: $settings['viewer_background_color'] ) ) ?: $settings['viewer_background_color'],
			'--prime-stories-button-bg'         => sanitize_hex_color( (string) ( $args['button_background'] ?: $settings['button_background_color'] ) ) ?: $settings['button_background_color'],
			'--prime-stories-button-color'      => sanitize_hex_color( (string) ( $args['button_text_color'] ?: $settings['button_text_color'] ) ) ?: $settings['button_text_color'],
			'--prime-stories-radius'            => max( 0, absint( $args['border_radius'] ) ) . 'px',
		);

		$style = '';

		foreach ( $style_vars as $property => $value ) {
			$style .= $property . ':' . $value . ';';
		}

		return $style;
	}
}
