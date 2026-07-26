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
			__( 'Story Manager', 'prime-stories' ),
			__( 'Story Manager', 'prime-stories' ),
			'edit_posts',
			'prime-stories-studio',
			array( $this, 'render_story_studio_page' )
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
	 * Render Story Studio page.
	 *
	 * @return void
	 */
	public function render_story_studio_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
		$query  = new WP_Query(
			array(
				'post_type'              => 'prime_story',
				'post_status'            => array( 'publish', 'draft', 'pending', 'future' ),
				'posts_per_page'         => 50,
				's'                      => $search,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);

		$stories = array();
		foreach ( $query->posts as $post ) {
			$meta = prime_stories_get_story_meta( $post->ID );
			$stories[] = array(
				'id'             => (int) $post->ID,
				'title'          => get_the_title( $post ),
				'status'         => (string) ( $meta['story_status'] ?? 'active' ),
				'post_status'    => get_post_status( $post ),
				'slides'         => is_array( $meta['slides'] ) ? count( $meta['slides'] ) : 0,
				'start_datetime' => (string) ( $meta['start_datetime'] ?? '' ),
				'end_datetime'   => (string) ( $meta['end_datetime'] ?? '' ),
				'modified'       => get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $post ),
				'groups'         => wp_get_post_terms( $post->ID, 'prime_story_group', array( 'fields' => 'names' ) ),
				'edit_link'      => get_edit_post_link( $post->ID ),
			);
		}

		include PRIME_STORIES_DIR . 'admin/views/studio-page.php';
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
		$interactions = $analytics->get_interaction_breakdown();
		$recent_replies = $analytics->get_recent_replies();

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
			'enable_guest_seen_state' => array(
				'label'       => __( 'Sync guest seen state', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'general',
				'default'     => 'yes',
				'description' => __( 'Persist guest seen-state with an anonymous session ID so initial rendering can reflect seen stories.', 'prime-stories' ),
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
			'viewer_fit_mode' => array(
				'label'   => __( 'Default media fit mode', 'prime-stories' ),
				'type'    => 'select',
				'section' => 'style',
				'default' => 'cover',
				'options' => array(
					'cover'   => __( 'Fill frame', 'prime-stories' ),
					'contain' => __( 'Show full media', 'prime-stories' ),
				),
			),
			'overlay_opacity' => array(
				'label'   => __( 'Viewer overlay strength', 'prime-stories' ),
				'type'    => 'number',
				'section' => 'style',
				'default' => 70,
				'min'     => 0,
				'max'     => 100,
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
			'analytics_retention_days' => array(
				'label'   => __( 'Analytics retention days', 'prime-stories' ),
				'type'    => 'number',
				'section' => 'performance',
				'default' => 180,
				'min'     => 1,
				'max'     => 1095,
			),
			'guest_seen_retention_days' => array(
				'label'   => __( 'Guest seen-state retention days', 'prime-stories' ),
				'type'    => 'number',
				'section' => 'performance',
				'default' => 30,
				'min'     => 1,
				'max'     => 365,
			),
			'respect_do_not_track' => array(
				'label'       => __( 'Respect Do Not Track', 'prime-stories' ),
				'type'        => 'toggle',
				'section'     => 'advanced',
				'default'     => 'yes',
				'description' => __( 'Disable frontend analytics and guest seen-state when the browser sends a Do Not Track signal.', 'prime-stories' ),
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
