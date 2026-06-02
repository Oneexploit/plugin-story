<?php
/**
 * Admin settings and analytics pages.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin manager.
 */
class Prime_Stories_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Admin|null
	 */
	private static $instance = null;

	/**
	 * Settings field definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $fields = array();

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Admin
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
		$this->fields = $this->get_fields();

		add_action( 'admin_menu', array( $this, 'register_admin_pages' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_prime_stories_clear_logs', array( $this, 'handle_clear_logs' ) );
	}

	/**
	 * Register plugin admin pages.
	 *
	 * @return void
	 */
	public function register_admin_pages() {
		add_submenu_page(
			'edit.php?post_type=prime_story',
			__( 'Settings', 'prime-stories' ),
			__( 'Settings', 'prime-stories' ),
			'manage_options',
			'prime-stories-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'edit.php?post_type=prime_story',
			__( 'Analytics', 'prime-stories' ),
			__( 'Analytics', 'prime-stories' ),
			'manage_options',
			'prime-stories-analytics',
			array( $this, 'render_analytics_page' )
		);

		add_submenu_page(
			'edit.php?post_type=prime_story',
			__( 'Logs', 'prime-stories' ),
			__( 'Logs', 'prime-stories' ),
			'manage_options',
			'prime-stories-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Register settings using the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'prime_stories_settings_group',
			'prime_stories_settings',
			array( $this, 'sanitize_settings' )
		);

		$sections = array(
			'general'     => __( 'General', 'prime-stories' ),
			'style'       => __( 'Style', 'prime-stories' ),
			'performance' => __( 'Performance', 'prime-stories' ),
			'advanced'    => __( 'Advanced', 'prime-stories' ),
		);

		foreach ( $sections as $section_id => $section_label ) {
			add_settings_section(
				'prime_stories_' . $section_id,
				$section_label,
				'__return_false',
				'prime-stories-settings'
			);
		}

		foreach ( $this->fields as $key => $field ) {
			add_settings_field(
				$key,
				$field['label'],
				array( $this, 'render_field' ),
				'prime-stories-settings',
				'prime_stories_' . $field['section'],
				array(
					'key'   => $key,
					'field' => $field,
				)
			);
		}
	}

	/**
	 * Sanitize all settings.
	 *
	 * @param array<string, mixed> $input Raw settings.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$settings = prime_stories_get_default_settings();

		foreach ( $this->fields as $key => $field ) {
			$raw_value = $input[ $key ] ?? null;

			switch ( $field['type'] ) {
				case 'toggle':
					$settings[ $key ] = prime_stories_is_enabled( $raw_value ) ? 'yes' : 'no';
					break;
				case 'number':
					$min = isset( $field['min'] ) ? (int) $field['min'] : 0;
					$max = isset( $field['max'] ) ? (int) $field['max'] : PHP_INT_MAX;
					$settings[ $key ] = max( $min, min( $max, absint( $raw_value ) ) );
					break;
				case 'select':
					$settings[ $key ] = prime_stories_sanitize_select( (string) $raw_value, array_keys( $field['options'] ), (string) $field['default'] );
					break;
				case 'color':
					$settings[ $key ] = sanitize_hex_color( (string) $raw_value ) ?: $field['default'];
					break;
				case 'textarea':
					$settings[ $key ] = sanitize_textarea_field( wp_unslash( (string) $raw_value ) );
					break;
			}
		}

		return $settings;
	}

	/**
	 * Render a settings field.
	 *
	 * @param array<string, mixed> $args Render args.
	 * @return void
	 */
	public function render_field( $args ) {
		$key      = $args['key'];
		$field    = $args['field'];
		$settings = prime_stories_get_settings();
		$value    = $settings[ $key ] ?? $field['default'];

		switch ( $field['type'] ) {
			case 'toggle':
				?>
				<label>
					<input type="checkbox" name="prime_stories_settings[<?php echo esc_attr( $key ); ?>]" value="yes" <?php checked( 'yes', $value ); ?>>
					<?php if ( ! empty( $field['description'] ) ) : ?>
						<span><?php echo esc_html( $field['description'] ); ?></span>
					<?php endif; ?>
				</label>
				<?php
				break;

			case 'number':
				?>
				<input class="small-text" type="number" name="prime_stories_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>" min="<?php echo esc_attr( (string) $field['min'] ); ?>" max="<?php echo esc_attr( (string) $field['max'] ); ?>">
				<?php
				break;

			case 'select':
				?>
				<select name="prime_stories_settings[<?php echo esc_attr( $key ); ?>]">
					<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
				break;

			case 'color':
				?>
				<input class="regular-text prime-stories-color-field" type="text" name="prime_stories_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>" placeholder="#000000">
				<?php
				break;

			case 'textarea':
				?>
				<textarea class="large-text code" rows="8" name="prime_stories_settings[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( (string) $value ); ?></textarea>
				<?php
				break;
		}

		if ( ! empty( $field['help'] ) ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include PRIME_STORIES_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Render analytics page.
	 *
	 * @return void
	 */
	public function render_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$analytics   = Prime_Stories_Analytics::get_instance();
		$summary     = $analytics->get_summary();
		$top_stories = $analytics->get_top_stories();

		include PRIME_STORIES_DIR . 'admin/views/analytics-page.php';
	}

	/**
	 * Render diagnostics log page.
	 *
	 * @return void
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$logger     = Prime_Stories_Logger::get_instance();
		$log_status = $logger->get_status();
		$entries    = $logger->get_recent_entries( 150 );

		include PRIME_STORIES_DIR . 'admin/views/logs-page.php';
	}

	/**
	 * Clear stored plugin logs.
	 *
	 * @return void
	 */
	public function handle_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear plugin logs.', 'prime-stories' ) );
		}

		check_admin_referer( 'prime_stories_clear_logs' );

		$cleared = Prime_Stories_Logger::get_instance()->clear_logs();

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'prime_story',
					'page'      => 'prime-stories-logs',
					'cleared'   => $cleared ? 'true' : 'false',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Settings field configuration.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_fields() {
		return array(
			'frontend_enabled' => array(
				'label'       => __( 'Enable frontend output', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'general',
				'default'     => 'yes',
				'description' => __( 'Allow stories to render on the frontend.', 'prime-stories' ),
			),
			'default_duration' => array(
				'label'   => __( 'Default story duration', 'prime-stories' ),
				'type'    => 'number',
				'section' => 'general',
				'default' => 5,
				'min'     => 1,
				'max'     => 60,
			),
			'default_layout' => array(
				'label'   => __( 'Default layout', 'prime-stories' ),
				'type'    => 'select',
				'section' => 'general',
				'default' => 'circle',
				'options' => array(
					'circle'   => __( 'Circle', 'prime-stories' ),
					'square'   => __( 'Square', 'prime-stories' ),
					'slider'   => __( 'Slider', 'prime-stories' ),
					'floating' => __( 'Floating', 'prime-stories' ),
				),
			),
			'enable_analytics' => array(
				'label'       => __( 'Enable analytics', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'general',
				'default'     => 'yes',
				'description' => __( 'Record basic story engagement events.', 'prime-stories' ),
			),
			'enable_seen_state' => array(
				'label'       => __( 'Enable seen state', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'general',
				'default'     => 'yes',
				'description' => __( 'Store seen stories for guests and logged-in visitors.', 'prime-stories' ),
			),
			'active_border_color' => array(
				'label'   => __( 'Active border color', 'prime-stories' ),
				'type'    => 'color',
				'section' => 'style',
				'default' => '#ff6b35',
			),
			'seen_border_color' => array(
				'label'   => __( 'Seen border color', 'prime-stories' ),
				'type'    => 'color',
				'section' => 'style',
				'default' => '#c7cad1',
			),
			'title_color' => array(
				'label'   => __( 'Title color', 'prime-stories' ),
				'type'    => 'color',
				'section' => 'style',
				'default' => '#0f172a',
			),
			'viewer_background_color' => array(
				'label'   => __( 'Viewer background color', 'prime-stories' ),
				'type'    => 'color',
				'section' => 'style',
				'default' => '#05070d',
			),
			'button_background_color' => array(
				'label'   => __( 'Button background color', 'prime-stories' ),
				'type'    => 'color',
				'section' => 'style',
				'default' => '#ffffff',
			),
			'button_text_color' => array(
				'label'   => __( 'Button text color', 'prime-stories' ),
				'type'    => 'color',
				'section' => 'style',
				'default' => '#05070d',
			),
			'default_circle_size' => array(
				'label'   => __( 'Default circle size', 'prime-stories' ),
				'type'    => 'number',
				'section' => 'style',
				'default' => 88,
				'min'     => 48,
				'max'     => 160,
			),
			'load_assets_globally' => array(
				'label'       => __( 'Load assets globally', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'performance',
				'default'     => 'no',
				'description' => __( 'Always enqueue frontend assets instead of waiting for detected usage.', 'prime-stories' ),
			),
			'lazy_load_media' => array(
				'label'       => __( 'Lazy load media', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'performance',
				'default'     => 'yes',
				'description' => __( 'Delay story media loading until it is needed.', 'prime-stories' ),
			),
			'enable_debug_logging' => array(
				'label'       => __( 'Enable diagnostic logging', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'advanced',
				'default'     => 'yes',
				'description' => __( 'Write plugin diagnostics to a log file so failures can be traced precisely.', 'prime-stories' ),
				'help'        => __( 'Logs are stored in wp-content/uploads/prime-stories/logs and can be viewed from Stories > Logs.', 'prime-stories' ),
			),
			'enable_client_logging' => array(
				'label'       => __( 'Enable frontend diagnostics', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'advanced',
				'default'     => 'yes',
				'description' => __( 'Allow the story viewer to report JavaScript/runtime issues back to the plugin log.', 'prime-stories' ),
			),
			'custom_css' => array(
				'label'   => __( 'Custom CSS', 'prime-stories' ),
				'type'    => 'textarea',
				'section' => 'advanced',
				'default' => '',
				'help'    => __( 'Optional CSS appended after the plugin stylesheet.', 'prime-stories' ),
			),
			'remove_data_on_uninstall' => array(
				'label'       => __( 'Remove plugin data on uninstall', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'advanced',
				'default'     => 'no',
				'description' => __( 'Delete plugin options, analytics data, and custom metadata when the plugin is uninstalled.', 'prime-stories' ),
			),
		);
	}
}
