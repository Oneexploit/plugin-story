<?php
/**
 * Plugin Name: Prime Stories
 * Plugin URI: https://webiitor.ir/
 * Description: Prime Stories by Webiitor adds a professional Instagram-style story system to WordPress with Elementor support, shortcode placement, scheduling, smart display rules, and basic analytics.
 * Version: 1.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Webiitor
 * Author URI: https://webiitor.ir/
 * Text Domain: prime-stories
 * Domain Path: /languages
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'PRIME_STORIES_VERSION' ) ) {
	define( 'PRIME_STORIES_VERSION', '1.1.0' );
}

if ( ! defined( 'PRIME_STORIES_FILE' ) ) {
	define( 'PRIME_STORIES_FILE', __FILE__ );
}

if ( ! defined( 'PRIME_STORIES_DIR' ) ) {
	define( 'PRIME_STORIES_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'PRIME_STORIES_URL' ) ) {
	define( 'PRIME_STORIES_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'PRIME_STORIES_BASENAME' ) ) {
	define( 'PRIME_STORIES_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Determine whether the current environment is compatible.
 *
 * @return bool
 */
function prime_stories_is_compatible() {
	return version_compare( PHP_VERSION, '8.0', '>=' );
}

/**
 * Output an admin notice when PHP is unsupported.
 *
 * @return void
 */
function prime_stories_render_php_notice() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Prime Stories requires PHP 8.0 or newer.', 'prime-stories' )
	);
}

/**
 * Activation callback wrapper.
 *
 * @return void
 */
function prime_stories_activate() {
	if ( ! prime_stories_is_compatible() ) {
		deactivate_plugins( PRIME_STORIES_BASENAME );
		wp_die( esc_html__( 'Prime Stories requires PHP 8.0 or newer.', 'prime-stories' ) );
	}

	require_once PRIME_STORIES_DIR . 'includes/helpers.php';
	require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-logger.php';
	require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-loader.php';

	Prime_Stories_Logger::get_instance()->info( 'Plugin activation started.', array(), 'bootstrap.activate' );

	try {
		Prime_Stories_Loader::activate();
		Prime_Stories_Logger::get_instance()->info( 'Plugin activation completed.', array(), 'bootstrap.activate' );
	} catch ( Throwable $exception ) {
		Prime_Stories_Logger::get_instance()->exception( $exception, array(), 'bootstrap.activate' );
		wp_die( esc_html__( 'Prime Stories could not be activated. Check the plugin logs for more details.', 'prime-stories' ) );
	}
}

/**
 * Deactivation callback wrapper.
 *
 * @return void
 */
function prime_stories_deactivate() {
	if ( class_exists( 'Prime_Stories_Logger' ) ) {
		Prime_Stories_Logger::get_instance()->info( 'Plugin deactivation started.', array(), 'bootstrap.deactivate' );
	}

	if ( class_exists( 'Prime_Stories_Loader' ) ) {
		Prime_Stories_Loader::deactivate();
	}
}

require_once PRIME_STORIES_DIR . 'includes/helpers.php';
require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-logger.php';
require_once PRIME_STORIES_DIR . 'includes/class-prime-stories-loader.php';

register_activation_hook( PRIME_STORIES_FILE, 'prime_stories_activate' );
register_deactivation_hook( PRIME_STORIES_FILE, 'prime_stories_deactivate' );

if ( ! prime_stories_is_compatible() ) {
	add_action( 'admin_notices', 'prime_stories_render_php_notice' );
	return;
}

/**
 * Bootstrap Prime Stories.
 *
 * @return Prime_Stories_Loader
 */
if ( ! function_exists( 'prime_stories' ) ) {
	function prime_stories() {
		return Prime_Stories_Loader::get_instance();
	}
}

try {
	prime_stories()->run();
} catch ( Throwable $exception ) {
	Prime_Stories_Logger::get_instance()->exception( $exception, array(), 'bootstrap.run' );

	if ( is_admin() ) {
		add_action(
			'admin_notices',
			static function () {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'Prime Stories hit a bootstrap error. Open Stories > Logs to inspect the exact failure details.', 'prime-stories' )
				);
			}
		);
	}
}
