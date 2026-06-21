<?php
/**
 * Display rules management.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Display rules.
 */
class Prime_Stories_Display_Rules {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Display_Rules|null
	 */
	private static $instance = null;

	/**
	 * Active rules for current request.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $active_rules = array();

	/**
	 * Whether content positions were already injected.
	 *
	 * @var bool
	 */
	private $content_injected = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Display_Rules
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
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_post_prime_stories_save_rules', array( $this, 'handle_save' ) );
		add_action( 'wp', array( $this, 'bootstrap_frontend_rules' ) );
	}

	/**
	 * Register the display rules admin page.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		add_submenu_page(
			'edit.php?post_type=prime_story',
			__( 'Display Rules', 'prime-stories' ),
			__( 'Display Rules', 'prime-stories' ),
			'manage_options',
			'prime-stories-display-rules',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Save display rules.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage display rules.', 'prime-stories' ) );
		}

		check_admin_referer( 'prime_stories_save_rules', 'prime_stories_rules_nonce' );

		$raw_rules = $_POST['prime_stories_rules'] ?? array();
		$raw_rules = is_array( $raw_rules ) ? $raw_rules : array();
		$rules     = array();

		foreach ( $raw_rules as $raw_rule ) {
			if ( ! is_array( $raw_rule ) ) {
				continue;
			}

			$rule = array(
				'id'                 => sanitize_text_field( wp_unslash( (string) ( $raw_rule['id'] ?? wp_generate_uuid4() ) ) ),
				'name'               => sanitize_text_field( wp_unslash( (string) ( $raw_rule['name'] ?? '' ) ) ),
				'group'              => sanitize_title( wp_unslash( (string) ( $raw_rule['group'] ?? '' ) ) ),
				'position'           => prime_stories_sanitize_select( (string) ( $raw_rule['position'] ?? '' ), array_keys( $this->get_position_options() ), 'before_content' ),
				'condition'          => prime_stories_sanitize_select( (string) ( $raw_rule['condition'] ?? '' ), array_keys( $this->get_condition_options() ), 'entire_website' ),
				'condition_targets'  => sanitize_text_field( wp_unslash( (string) ( $raw_rule['condition_targets'] ?? '' ) ) ),
				'device_condition'   => prime_stories_sanitize_select( (string) ( $raw_rule['device_condition'] ?? '' ), array( 'all', 'desktop_only', 'mobile_only' ), 'all' ),
				'user_condition'     => prime_stories_sanitize_select( (string) ( $raw_rule['user_condition'] ?? '' ), array( 'everyone', 'guests_only', 'logged_in_only' ), 'everyone' ),
				'status'             => prime_stories_sanitize_select( (string) ( $raw_rule['status'] ?? '' ), array( 'active', 'inactive' ), 'active' ),
			);

			if ( empty( $rule['name'] ) || empty( $rule['group'] ) ) {
				continue;
			}

			if ( ! term_exists( $rule['group'], 'prime_story_group' ) ) {
				continue;
			}

			$rules[] = $rule;
		}

		update_option( 'prime_stories_display_rules', $rules );
		prime_stories_log(
			'info',
			'Display rules were updated.',
			array(
				'rule_count' => count( $rules ),
			),
			'display_rules.save'
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => 'prime_story',
					'page'      => 'prime-stories-display-rules',
					'updated'   => 'true',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rules             = prime_stories_get_display_rules();
		$groups            = prime_stories_get_story_groups();
		$position_options  = $this->get_position_options();
		$condition_options = $this->get_condition_options();
		?>
		<div class="wrap prime-stories-admin-wrap">
			<?php
			prime_stories_render_admin_header(
				__( 'Prime Stories Display Rules', 'prime-stories' ),
				__( 'Automatically place story groups around your site based on location, device type, audience, and page conditions.', 'prime-stories' )
			);
			?>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Display rules updated.', 'prime-stories' ); ?></p></div>
			<?php endif; ?>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="prime_stories_save_rules">
				<?php wp_nonce_field( 'prime_stories_save_rules', 'prime_stories_rules_nonce' ); ?>

				<table class="widefat striped prime-stories-rules-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Rule name', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'Story group', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'Display position', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'Display condition', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'Target IDs / terms', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'Device', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'User', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'Status', 'prime-stories' ); ?></th>
							<th><?php esc_html_e( 'Remove', 'prime-stories' ); ?></th>
						</tr>
					</thead>
					<tbody id="prime-stories-rules-rows">
						<?php if ( empty( $rules ) ) : ?>
							<?php $rules = array( array( 'id' => wp_generate_uuid4(), 'name' => '', 'group' => '', 'position' => 'before_content', 'condition' => 'entire_website', 'condition_targets' => '', 'device_condition' => 'all', 'user_condition' => 'everyone', 'status' => 'active' ) ); ?>
						<?php endif; ?>
						<?php foreach ( $rules as $index => $rule ) : ?>
							<?php $this->render_rule_row( $index, $this->normalize_rule( $rule ), $groups, $position_options, $condition_options ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<button type="button" class="button" id="prime-stories-add-rule"><?php esc_html_e( 'Add rule', 'prime-stories' ); ?></button>
				</p>

				<p class="description">
					<?php esc_html_e( 'Use comma-separated IDs or term IDs for specific conditions. Product conditions remain safe even when WooCommerce is inactive.', 'prime-stories' ); ?>
				</p>

				<?php submit_button( __( 'Save Display Rules', 'prime-stories' ) ); ?>
			</form>

			<script type="text/template" id="tmpl-prime-stories-rule-row">
				<?php
				$this->render_rule_row(
					'__INDEX__',
					array(
						'id'                => '__ID__',
						'name'              => '',
						'group'             => '',
						'position'          => 'before_content',
						'condition'         => 'entire_website',
						'condition_targets' => '',
						'device_condition'  => 'all',
						'user_condition'    => 'everyone',
						'status'            => 'active',
					),
					$groups,
					$position_options,
					$condition_options
				);
				?>
			</script>
		</div>
		<?php
	}

	/**
	 * Bootstrap frontend display rules.
	 *
	 * @return void
	 */
	public function bootstrap_frontend_rules() {
		if ( is_admin() || ! prime_stories_is_frontend_enabled() ) {
			return;
		}

		$this->active_rules = array_values(
			array_filter(
				prime_stories_get_display_rules(),
				array( $this, 'rule_applies' )
			)
		);

		if ( empty( $this->active_rules ) ) {
			return;
		}

		Prime_Stories_Assets::get_instance()->require_public_assets();

		add_action( 'wp_body_open', array( $this, 'output_body_open_positions' ), 5 );
		add_filter( 'the_content', array( $this, 'inject_content_positions' ) );
		add_action( 'wp_footer', array( $this, 'output_footer_positions' ), 5 );
	}

	/**
	 * Inject before/after content rules.
	 *
	 * @param string $content Original content.
	 * @return string
	 */
	public function inject_content_positions( $content ) {
		if ( $this->content_injected || ! is_main_query() || ! in_the_loop() || ( ! is_singular() && ! is_front_page() && ! is_home() ) ) {
			return $content;
		}

		$before_output = $this->render_rules_for_position( 'before_content' );
		$after_output  = $this->render_rules_for_position( 'after_content' );

		if ( ! $before_output && ! $after_output ) {
			return $content;
		}

		$this->content_injected = true;

		return $before_output . $content . $after_output;
	}

	/**
	 * Output top-of-body positions.
	 *
	 * @return void
	 */
	public function output_body_open_positions() {
		echo $this->render_rules_for_position( 'before_header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_rules_for_position( 'after_header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Output footer and floating positions.
	 *
	 * @return void
	 */
	public function output_footer_positions() {
		echo $this->render_rules_for_position( 'before_footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_rules_for_position( 'after_footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_rules_for_position( 'floating_bottom_left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_rules_for_position( 'floating_bottom_right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_rules_for_position( 'floating_top_left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->render_rules_for_position( 'floating_top_right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render rules for a position.
	 *
	 * @param string $position Position key.
	 * @return string
	 */
	private function render_rules_for_position( $position ) {
		$output = '';

		foreach ( $this->active_rules as $rule ) {
			$rule = $this->normalize_rule( $rule );

			if ( $position !== $rule['position'] ) {
				continue;
			}

			$output .= Prime_Stories_Public::get_instance()->render_stories(
				array(
					'group'     => $rule['group'],
					'layout'    => 0 === strpos( $position, 'floating_' ) ? 'floating' : prime_stories_get_setting( 'default_layout', 'circle' ),
					'limit'     => 10,
					'autoplay'  => true,
					'show_title'=> true,
					'size'      => 'medium',
					'class'     => 'prime-stories-auto-placement prime-stories-position-' . sanitize_html_class( $position ),
					'position'  => $position,
					'source'    => 'display-rule',
				)
			);
		}

		return $output;
	}

	/**
	 * Determine whether a rule applies on the current request.
	 *
	 * @param array<string, mixed> $rule Rule config.
	 * @return bool
	 */
	private function rule_applies( $rule ) {
		$rule = $this->normalize_rule( $rule );

		if ( ! is_array( $rule ) || 'active' !== ( $rule['status'] ?? 'inactive' ) ) {
			return false;
		}

		if ( empty( $rule['group'] ) || ! term_exists( (string) $rule['group'], 'prime_story_group' ) || ! $this->matches_device_rule( (string) $rule['device_condition'] ) || ! $this->matches_user_rule( (string) $rule['user_condition'] ) ) {
			return false;
		}

		return $this->matches_display_condition( (string) $rule['condition'], (string) $rule['condition_targets'] );
	}

	/**
	 * Determine whether the rule matches the device audience.
	 *
	 * @param string $device_condition Device condition.
	 * @return bool
	 */
	private function matches_device_rule( $device_condition ) {
		$is_mobile = prime_stories_is_mobile_request();

		if ( 'desktop_only' === $device_condition && $is_mobile ) {
			return false;
		}

		if ( 'mobile_only' === $device_condition && ! $is_mobile ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether the rule matches the user audience.
	 *
	 * @param string $user_condition User condition.
	 * @return bool
	 */
	private function matches_user_rule( $user_condition ) {
		if ( 'guests_only' === $user_condition && is_user_logged_in() ) {
			return false;
		}

		if ( 'logged_in_only' === $user_condition && ! is_user_logged_in() ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine whether the rule matches the current page condition.
	 *
	 * @param string $condition Condition key.
	 * @param string $targets Comma-separated target IDs.
	 * @return bool
	 */
	private function matches_display_condition( $condition, $targets ) {
		$target_ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $targets ) ) ) );

		switch ( $condition ) {
			case 'homepage_only':
				return is_front_page() || is_home();
			case 'specific_pages':
				return ! empty( $target_ids ) && is_page( $target_ids );
			case 'specific_posts':
				return ! empty( $target_ids ) && is_single( $target_ids );
			case 'specific_post_categories':
				return is_single() && ! empty( $target_ids ) && has_category( $target_ids );
			case 'specific_products':
				return class_exists( 'WooCommerce' ) && ! empty( $target_ids ) && is_singular( 'product' ) && in_array( get_queried_object_id(), $target_ids, true );
			case 'specific_product_categories':
				return class_exists( 'WooCommerce' ) && is_singular( 'product' ) && ! empty( $target_ids ) && has_term( $target_ids, 'product_cat', get_queried_object_id() );
			case 'blog_archive':
				return is_home() || is_category() || is_tag() || is_author() || is_date();
			case 'search_page':
				return is_search();
			case 'error_404':
				return is_404();
			case 'entire_website':
			default:
				return true;
		}
	}

	/**
	 * Render a single rule row.
	 *
	 * @param int|string                 $index Row index.
	 * @param array<string, string>      $rule Rule data.
	 * @param array<int, WP_Term>        $groups Story groups.
	 * @param array<string, string>      $position_options Position options.
	 * @param array<string, string>      $condition_options Condition options.
	 * @return void
	 */
	private function render_rule_row( $index, $rule, $groups, $position_options, $condition_options ) {
		?>
		<tr class="prime-stories-rule-row">
			<td>
				<input type="hidden" name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][id]" value="<?php echo esc_attr( (string) $rule['id'] ); ?>">
				<input type="text" class="regular-text" name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][name]" value="<?php echo esc_attr( (string) $rule['name'] ); ?>" required>
			</td>
			<td>
				<select name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][group]" required>
					<option value=""><?php esc_html_e( 'Select group', 'prime-stories' ); ?></option>
					<?php foreach ( $groups as $group ) : ?>
						<option value="<?php echo esc_attr( $group->slug ); ?>" <?php selected( $rule['group'], $group->slug ); ?>><?php echo esc_html( $group->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][position]">
					<?php foreach ( $position_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $rule['position'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][condition]">
					<?php foreach ( $condition_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $rule['condition'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<input type="text" class="regular-text" name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][condition_targets]" value="<?php echo esc_attr( (string) $rule['condition_targets'] ); ?>">
			</td>
			<td>
				<select name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][device_condition]">
					<option value="all" <?php selected( $rule['device_condition'], 'all' ); ?>><?php esc_html_e( 'All devices', 'prime-stories' ); ?></option>
					<option value="desktop_only" <?php selected( $rule['device_condition'], 'desktop_only' ); ?>><?php esc_html_e( 'Desktop only', 'prime-stories' ); ?></option>
					<option value="mobile_only" <?php selected( $rule['device_condition'], 'mobile_only' ); ?>><?php esc_html_e( 'Mobile only', 'prime-stories' ); ?></option>
				</select>
			</td>
			<td>
				<select name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][user_condition]">
					<option value="everyone" <?php selected( $rule['user_condition'], 'everyone' ); ?>><?php esc_html_e( 'Everyone', 'prime-stories' ); ?></option>
					<option value="guests_only" <?php selected( $rule['user_condition'], 'guests_only' ); ?>><?php esc_html_e( 'Guests only', 'prime-stories' ); ?></option>
					<option value="logged_in_only" <?php selected( $rule['user_condition'], 'logged_in_only' ); ?>><?php esc_html_e( 'Logged-in users only', 'prime-stories' ); ?></option>
				</select>
			</td>
			<td>
				<select name="prime_stories_rules[<?php echo esc_attr( (string) $index ); ?>][status]">
					<option value="active" <?php selected( $rule['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'prime-stories' ); ?></option>
					<option value="inactive" <?php selected( $rule['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'prime-stories' ); ?></option>
				</select>
			</td>
			<td>
				<button type="button" class="button-link-delete prime-stories-remove-rule"><?php esc_html_e( 'Remove', 'prime-stories' ); ?></button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Available position options.
	 *
	 * @return array<string, string>
	 */
	private function get_position_options() {
		return array(
			'before_header'         => __( 'Before header', 'prime-stories' ),
			'after_header'          => __( 'After header', 'prime-stories' ),
			'before_content'        => __( 'Before content', 'prime-stories' ),
			'after_content'         => __( 'After content', 'prime-stories' ),
			'before_footer'         => __( 'Before footer', 'prime-stories' ),
			'after_footer'          => __( 'After footer', 'prime-stories' ),
			'floating_bottom_left'  => __( 'Floating bottom left', 'prime-stories' ),
			'floating_bottom_right' => __( 'Floating bottom right', 'prime-stories' ),
			'floating_top_left'     => __( 'Floating top left', 'prime-stories' ),
			'floating_top_right'    => __( 'Floating top right', 'prime-stories' ),
		);
	}

	/**
	 * Available condition options.
	 *
	 * @return array<string, string>
	 */
	private function get_condition_options() {
		return array(
			'entire_website'             => __( 'Entire website', 'prime-stories' ),
			'homepage_only'              => __( 'Homepage only', 'prime-stories' ),
			'specific_pages'             => __( 'Specific pages', 'prime-stories' ),
			'specific_posts'             => __( 'Specific posts', 'prime-stories' ),
			'specific_post_categories'   => __( 'Specific post categories', 'prime-stories' ),
			'specific_products'          => __( 'Specific WooCommerce products', 'prime-stories' ),
			'specific_product_categories'=> __( 'Specific WooCommerce product categories', 'prime-stories' ),
			'blog_archive'               => __( 'Blog archive', 'prime-stories' ),
			'search_page'                => __( 'Search page', 'prime-stories' ),
			'error_404'                  => __( '404 page', 'prime-stories' ),
		);
	}

	/**
	 * Get default rule values.
	 *
	 * @return array<string, string>
	 */
	private function get_rule_defaults() {
		return array(
			'id'                => '',
			'name'              => '',
			'group'             => '',
			'position'          => 'before_content',
			'condition'         => 'entire_website',
			'condition_targets' => '',
			'device_condition'  => 'all',
			'user_condition'    => 'everyone',
			'status'            => 'active',
		);
	}

	/**
	 * Normalize stored rule data before use.
	 *
	 * @param array<string, mixed> $rule Rule data.
	 * @return array<string, string>
	 */
	private function normalize_rule( $rule ) {
		return wp_parse_args( is_array( $rule ) ? $rule : array(), $this->get_rule_defaults() );
	}
}
