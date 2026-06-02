<?php
/**
 * Elementor integration bootstrap.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Elementor assets and widgets when available.
 */
class Prime_Stories_Elementor {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Elementor|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Elementor
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
		add_action( 'plugins_loaded', array( $this, 'maybe_register_elementor' ), 20 );
	}

	/**
	 * Register Elementor hooks if Elementor is active.
	 *
	 * @return void
	 */
	public function maybe_register_elementor() {
		if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the Prime Stories widget.
	 *
	 * @param Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widget( $widgets_manager ) {
		try {
			require_once PRIME_STORIES_DIR . 'elementor/class-prime-stories-elementor-widget.php';
			$widgets_manager->register( new Prime_Stories_Elementor_Widget() );
		} catch ( Throwable $exception ) {
			Prime_Stories_Logger::get_instance()->exception( $exception, array(), 'elementor.register_widget' );
		}
	}
}
