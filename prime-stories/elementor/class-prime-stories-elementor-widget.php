<?php
/**
 * Elementor widget.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prime Stories Elementor widget.
 */
class Prime_Stories_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'prime-stories';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Prime Stories', 'prime-stories' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array<int, string>
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array<int, string>
	 */
	public function get_style_depends() {
		return array( 'prime-stories-public' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @return array<int, string>
	 */
	public function get_script_depends() {
		return array( 'prime-stories-public' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$groups = array( '' => __( 'All groups', 'prime-stories' ) );

		foreach ( prime_stories_get_story_groups() as $group ) {
			$groups[ $group->slug ] = $group->name;
		}

		$this->start_controls_section(
			'prime_stories_content',
			array(
				'label' => __( 'Content', 'prime-stories' ),
			)
		);

		$this->add_control(
			'group',
			array(
				'label'   => __( 'Select Story Group', 'prime-stories' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $groups,
				'default' => '',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'prime-stories' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'circle'   => __( 'Circle', 'prime-stories' ),
					'square'   => __( 'Square', 'prime-stories' ),
					'slider'   => __( 'Slider', 'prime-stories' ),
					'floating' => __( 'Floating', 'prime-stories' ),
				),
				'default' => prime_stories_get_setting( 'default_layout', 'circle' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Number of stories', 'prime-stories' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => __( 'Show story title', 'prime-stories' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'prime-stories' ),
				'label_off'    => __( 'Hide', 'prime-stories' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'prime-stories' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'prime-stories' ),
				'label_off'    => __( 'No', 'prime-stories' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'open_mode',
			array(
				'label'   => __( 'Open mode', 'prime-stories' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'fullscreen' => __( 'Fullscreen', 'prime-stories' ),
					'popup'      => __( 'Popup', 'prime-stories' ),
				),
				'default' => 'fullscreen',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'prime_stories_style',
			array(
				'label' => __( 'Style', 'prime-stories' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'circle_size',
			array(
				'label'      => __( 'Circle/card size', 'prime-stories' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 48,
						'max' => 180,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-item-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'spacing',
			array(
				'label'      => __( 'Spacing', 'prime-stories' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'border_width',
			array(
				'label'      => __( 'Border width', 'prime-stories' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 1,
						'max' => 10,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'active_border_color',
			array(
				'label'     => __( 'Active border color', 'prime-stories' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-active-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'seen_border_color',
			array(
				'label'     => __( 'Seen border color', 'prime-stories' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-seen-border: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .prime-stories-item-title, {{WRAPPER}} .prime-stories-slide-title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title color', 'prime-stories' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-title-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'viewer_background',
			array(
				'label'     => __( 'Viewer background color', 'prime-stories' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-viewer-background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'fit_mode',
			array(
				'label'   => __( 'Media fit mode', 'prime-stories' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'cover'   => __( 'Fill frame', 'prime-stories' ),
					'contain' => __( 'Show full media', 'prime-stories' ),
				),
				'default' => prime_stories_get_setting( 'viewer_fit_mode', 'cover' ),
			)
		);

		$this->add_control(
			'overlay_opacity',
			array(
				'label'      => __( 'Overlay strength', 'prime-stories' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-overlay-opacity: {{SIZE}}%;',
				),
			)
		);

		$this->add_control(
			'button_background',
			array(
				'label'     => __( 'Button color', 'prime-stories' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-button-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Button text color', 'prime-stories' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-button-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'border_radius',
			array(
				'label'      => __( 'Border radius', 'prime-stories' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .prime-stories-wrapper' => '--prime-stories-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'prime_stories_advanced',
			array(
				'label' => __( 'Advanced', 'prime-stories' ),
			)
		);

		$this->add_control(
			'custom_class',
			array(
				'label'   => __( 'Custom CSS class', 'prime-stories' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'hide_desktop',
			array(
				'label'        => __( 'Hide on desktop', 'prime-stories' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'hide_tablet',
			array(
				'label'        => __( 'Hide on tablet', 'prime-stories' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'hide_mobile',
			array(
				'label'        => __( 'Hide on mobile', 'prime-stories' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		echo Prime_Stories_Public::get_instance()->render_stories( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'group'         => sanitize_text_field( (string) $settings['group'] ),
				'layout'        => prime_stories_sanitize_select( (string) $settings['layout'], array( 'circle', 'square', 'slider', 'floating' ), prime_stories_get_setting( 'default_layout', 'circle' ) ),
				'limit'         => absint( $settings['limit'] ),
				'autoplay'      => 'yes' === $settings['autoplay'],
				'show_title'    => 'yes' === $settings['show_title'],
				'open_mode'     => prime_stories_sanitize_select( (string) $settings['open_mode'], array( 'fullscreen', 'popup' ), 'fullscreen' ),
				'class'         => prime_stories_sanitize_class_list( (string) $settings['custom_class'] ),
				'fit_mode'      => prime_stories_sanitize_select( (string) $settings['fit_mode'], array( 'cover', 'contain' ), prime_stories_get_setting( 'viewer_fit_mode', 'cover' ) ),
				'overlay_opacity' => isset( $settings['overlay_opacity']['size'] ) ? absint( $settings['overlay_opacity']['size'] ) : prime_stories_get_setting( 'overlay_opacity', 70 ),
				'hide_desktop'  => 'yes' === $settings['hide_desktop'],
				'hide_tablet'   => 'yes' === $settings['hide_tablet'],
				'hide_mobile'   => 'yes' === $settings['hide_mobile'],
				'source'        => 'elementor',
			)
		);
	}
}
