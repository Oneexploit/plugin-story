<?php
/**
 * Shared helper functions for Prime Stories.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the plugin default settings.
 *
 * @return array<string, mixed>
 */
function prime_stories_get_default_settings() {
	return array(
		'frontend_enabled'         => 'yes',
		'default_duration'         => 5,
		'default_layout'           => 'circle',
		'enable_analytics'         => 'yes',
		'enable_seen_state'        => 'yes',
		'enable_guest_seen_state'  => 'yes',
		'active_border_color'      => '#ff6b35',
		'seen_border_color'        => '#c7cad1',
		'title_color'              => '#0f172a',
		'viewer_background_color'  => '#05070d',
		'button_background_color'  => '#ffffff',
		'button_text_color'        => '#05070d',
		'default_circle_size'      => 88,
		'viewer_fit_mode'          => 'cover',
		'overlay_opacity'          => 70,
		'load_assets_globally'     => 'no',
		'lazy_load_media'          => 'yes',
		'analytics_retention_days' => 180,
		'guest_seen_retention_days'=> 30,
		'respect_do_not_track'     => 'yes',
		'enable_debug_logging'     => 'yes',
		'enable_client_logging'    => 'yes',
		'custom_css'               => '',
		'remove_data_on_uninstall' => 'no',
	);
}

/**
 * Get the company name behind the plugin.
 *
 * @return string
 */
function prime_stories_get_company_name() {
	return 'Webiitor';
}

/**
 * Get the company website.
 *
 * @return string
 */
function prime_stories_get_company_url() {
	return 'https://webiitor.ir/';
}

/**
 * Get the plugin logo URL.
 *
 * @return string
 */
function prime_stories_get_logo_url() {
	return PRIME_STORIES_URL . 'assets/logo.svg';
}

/**
 * Get the plugin raster logo URL.
 *
 * @return string
 */
function prime_stories_get_logo_raster_url() {
	return PRIME_STORIES_URL . 'assets/logo.png';
}

/**
 * Get the product summary used in admin screens and docs.
 *
 * @return string
 */
function prime_stories_get_product_summary() {
	return __(
		'Prime Stories is a professional story manager for WordPress that helps you publish Instagram-style image and video stories with Elementor placement, shortcode support, scheduling, display rules, and basic analytics.',
		'prime-stories'
	);
}

/**
 * Render a shared admin page header.
 *
 * @param string $title Admin page title.
 * @param string $description Optional description.
 * @return void
 */
function prime_stories_render_admin_header( $title, $description = '' ) {
	$description = $description ? $description : prime_stories_get_product_summary();
	?>
	<div class="prime-stories-admin-hero">
		<div class="prime-stories-admin-hero-mark">
			<img src="<?php echo esc_url( prime_stories_get_logo_url() ); ?>" alt="<?php echo esc_attr__( 'Prime Stories logo', 'prime-stories' ); ?>">
		</div>
		<div class="prime-stories-admin-hero-copy">
			<h1><?php echo esc_html( $title ); ?></h1>
			<p><?php echo esc_html( $description ); ?></p>
			<div class="prime-stories-admin-hero-meta">
				<span><?php echo esc_html__( 'Crafted by', 'prime-stories' ); ?> <?php echo esc_html( prime_stories_get_company_name() ); ?></span>
				<a href="<?php echo esc_url( prime_stories_get_company_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( preg_replace( '#^https?://#', '', prime_stories_get_company_url() ) ); ?></a>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Get merged plugin settings.
 *
 * @return array<string, mixed>
 */
function prime_stories_get_settings() {
	$settings = get_option( 'prime_stories_settings', array() );

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args( $settings, prime_stories_get_default_settings() );
}

/**
 * Get a single setting.
 *
 * @param string $key Setting key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function prime_stories_get_setting( $key, $default = null ) {
	$settings = prime_stories_get_settings();

	return $settings[ $key ] ?? $default;
}

/**
 * Get the shared plugin logger when available.
 *
 * @return Prime_Stories_Logger|null
 */
function prime_stories_get_logger() {
	if ( class_exists( 'Prime_Stories_Logger' ) ) {
		return Prime_Stories_Logger::get_instance();
	}

	return null;
}

/**
 * Write a diagnostic log entry when the logger is available.
 *
 * @param string               $level Log level.
 * @param string               $message Message.
 * @param array<string, mixed> $context Context.
 * @param string               $source Source identifier.
 * @return bool
 */
function prime_stories_log( $level, $message, $context = array(), $source = 'general' ) {
	$logger = prime_stories_get_logger();

	if ( ! $logger ) {
		return false;
	}

	return $logger->log( $level, $message, $context, $source );
}

/**
 * Get default story meta values.
 *
 * @return array<string, mixed>
 */
function prime_stories_get_default_story_meta() {
	return array(
		'media_type'       => 'image',
		'image_id'         => 0,
		'video_id'         => 0,
		'mobile_media_id'  => 0,
		'cover_image_id'   => 0,
		'caption'          => '',
		'subtitle'         => '',
		'button_text'      => '',
		'button_url'       => '',
		'button_target'    => 'same_tab',
		'duration'         => (int) prime_stories_get_setting( 'default_duration', 5 ),
		'priority'         => 0,
		'story_status'     => 'active',
		'start_datetime'   => '',
		'end_datetime'     => '',
		'show_devices'     => 'all',
		'show_users'       => 'everyone',
		'custom_css_class' => '',
		'open_on_click'    => 'no',
		'fit_mode'         => 'global',
		'slides'           => array(),
	);
}

/**
 * Get default values for one story slide.
 *
 * @return array<string, mixed>
 */
function prime_stories_get_default_slide_meta() {
	return array(
		'id'              => '',
		'media_type'      => 'image',
		'image_id'        => 0,
		'video_id'        => 0,
		'mobile_media_id' => 0,
		'cover_image_id'  => 0,
		'focal_x'         => 50,
		'focal_y'         => 50,
		'title'           => '',
		'subtitle'        => '',
		'caption'         => '',
		'button_text'     => '',
		'button_url'      => '',
		'button_target'   => 'same_tab',
		'open_on_click'   => 'no',
		'duration'        => (int) prime_stories_get_setting( 'default_duration', 5 ),
		'fit_mode'        => 'global',
		'action_type'     => 'none',
		'action_payload'  => '',
		'poll_options'    => '',
		'poll_show_results' => 'yes',
		'poll_vote_once'  => 'yes',
		'reply_placeholder' => '',
		'question_success_message' => '',
		'question_helper_text' => '',
		'allow_multiple_replies' => 'no',
		'countdown_datetime' => '',
	);
}

/**
 * Determine whether a normalized slide contains meaningful content.
 *
 * @param array<string, mixed> $slide Slide data.
 * @return bool
 */
function prime_stories_slide_has_content( $slide ) {
	foreach ( array( 'image_id', 'video_id', 'mobile_media_id', 'cover_image_id' ) as $media_key ) {
		if ( ! empty( $slide[ $media_key ] ) ) {
			return true;
		}
	}

	foreach ( array( 'title', 'subtitle', 'caption', 'button_text', 'button_url', 'action_payload', 'poll_options', 'reply_placeholder', 'question_success_message', 'question_helper_text', 'countdown_datetime' ) as $content_key ) {
		if ( ! empty( $slide[ $content_key ] ) ) {
			return true;
		}
	}

	return ! empty( $slide['action_type'] ) && 'none' !== $slide['action_type'];
}

/**
 * Sanitize an enum-like value.
 *
 * @param string $value Raw value.
 * @param array  $allowed Allowed values.
 * @param string $default Default value.
 * @return string
 */
function prime_stories_sanitize_select( $value, $allowed, $default ) {
	$value = is_string( $value ) ? sanitize_key( $value ) : '';

	return in_array( $value, $allowed, true ) ? $value : $default;
}

/**
 * Determine whether a yes/no setting resolves to yes.
 *
 * @param mixed $value Value to inspect.
 * @return bool
 */
function prime_stories_is_enabled( $value ) {
	return 'yes' === $value || true === $value || '1' === $value || 1 === $value;
}

/**
 * Get all stored story meta for a story.
 *
 * @param int $post_id Story ID.
 * @return array<string, mixed>
 */
function prime_stories_get_story_meta( $post_id ) {
	$defaults = prime_stories_get_default_story_meta();
	$meta     = array();

	foreach ( $defaults as $key => $default ) {
		$meta_key     = 'prime_stories_' . $key;
		$stored_value = get_post_meta( $post_id, $meta_key, true );
		$meta[ $key ] = '' === $stored_value ? $default : $stored_value;
	}

	$meta['image_id']        = absint( $meta['image_id'] );
	$meta['video_id']        = absint( $meta['video_id'] );
	$meta['mobile_media_id'] = absint( $meta['mobile_media_id'] );
	$meta['cover_image_id']  = absint( $meta['cover_image_id'] );
	$meta['duration']        = max( 1, min( 60, absint( $meta['duration'] ) ) );
	$meta['priority']        = (int) $meta['priority'];
	$meta['fit_mode']        = prime_stories_sanitize_select( (string) $meta['fit_mode'], array( 'global', 'cover', 'contain' ), 'global' );
	$meta['slides']          = prime_stories_normalize_slides( $meta['slides'], $post_id, $meta );

	return $meta;
}

/**
 * Normalize stored slide rows with a legacy single-slide fallback.
 *
 * @param mixed                $slides Stored slides.
 * @param int                  $post_id Story post ID.
 * @param array<string, mixed> $legacy_meta Legacy story-level meta.
 * @return array<int, array<string, mixed>>
 */
function prime_stories_normalize_slides( $slides, $post_id = 0, $legacy_meta = array() ) {
	$normalized = array();
	$defaults   = prime_stories_get_default_slide_meta();

	if ( is_string( $slides ) ) {
		$decoded = json_decode( $slides, true );
		$slides  = is_array( $decoded ) ? $decoded : array();
	}

	if ( is_array( $slides ) ) {
		foreach ( $slides as $index => $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}

			$slide = wp_parse_args( $slide, $defaults );
			$slide['id']              = sanitize_key( (string) ( $slide['id'] ? $slide['id'] : 'slide-' . ( $index + 1 ) ) );
			$slide['media_type']      = prime_stories_sanitize_select( (string) $slide['media_type'], array( 'image', 'video' ), 'image' );
			$slide['image_id']        = absint( $slide['image_id'] );
			$slide['video_id']        = absint( $slide['video_id'] );
			$slide['mobile_media_id'] = absint( $slide['mobile_media_id'] );
			$slide['cover_image_id']  = absint( $slide['cover_image_id'] );
			$slide['focal_x']         = max( 0, min( 100, absint( $slide['focal_x'] ) ) );
			$slide['focal_y']         = max( 0, min( 100, absint( $slide['focal_y'] ) ) );
			$slide['title']           = sanitize_text_field( (string) $slide['title'] );
			$slide['subtitle']        = sanitize_text_field( (string) $slide['subtitle'] );
			$slide['caption']         = wp_kses_post( (string) $slide['caption'] );
			$slide['button_text']     = sanitize_text_field( (string) $slide['button_text'] );
			$slide['button_url']      = esc_url_raw( (string) $slide['button_url'] );
			$slide['button_target']   = prime_stories_sanitize_select( (string) $slide['button_target'], array( 'same_tab', 'new_tab' ), 'same_tab' );
			$slide['open_on_click']   = prime_stories_sanitize_select( (string) $slide['open_on_click'], array( 'yes', 'no' ), 'no' );
			$slide['duration']        = max( 1, min( 60, absint( $slide['duration'] ) ) );
			$slide['fit_mode']        = prime_stories_sanitize_select( (string) $slide['fit_mode'], array( 'global', 'cover', 'contain' ), 'global' );
			$slide['action_type']     = prime_stories_sanitize_select( (string) $slide['action_type'], array( 'none', 'reaction', 'poll', 'question', 'countdown' ), 'none' );
			$slide['action_payload']  = sanitize_textarea_field( (string) $slide['action_payload'] );
			$slide['poll_options']    = sanitize_textarea_field( (string) $slide['poll_options'] );
			$slide['poll_show_results'] = prime_stories_sanitize_select( (string) $slide['poll_show_results'], array( 'yes', 'no' ), 'yes' );
			$slide['poll_vote_once']  = prime_stories_sanitize_select( (string) $slide['poll_vote_once'], array( 'yes', 'no' ), 'yes' );
			$slide['reply_placeholder'] = sanitize_text_field( (string) $slide['reply_placeholder'] );
			$slide['question_success_message'] = sanitize_text_field( (string) $slide['question_success_message'] );
			$slide['question_helper_text'] = sanitize_textarea_field( (string) $slide['question_helper_text'] );
			$slide['allow_multiple_replies'] = prime_stories_sanitize_select( (string) $slide['allow_multiple_replies'], array( 'yes', 'no' ), 'no' );
			$slide['countdown_datetime'] = sanitize_text_field( (string) $slide['countdown_datetime'] );

			if ( prime_stories_slide_has_content( $slide ) ) {
				$normalized[] = $slide;
			}
		}
	}

	if ( empty( $normalized ) && ! empty( $legacy_meta ) ) {
		$legacy = wp_parse_args(
			array(
				'id'              => 'legacy-' . absint( $post_id ),
				'media_type'      => $legacy_meta['media_type'] ?? 'image',
				'image_id'        => $legacy_meta['image_id'] ?? 0,
				'video_id'        => $legacy_meta['video_id'] ?? 0,
				'mobile_media_id' => $legacy_meta['mobile_media_id'] ?? 0,
				'cover_image_id'  => $legacy_meta['cover_image_id'] ?? 0,
				'title'           => get_the_title( $post_id ),
				'subtitle'        => $legacy_meta['subtitle'] ?? '',
				'caption'         => $legacy_meta['caption'] ?? '',
				'button_text'     => $legacy_meta['button_text'] ?? '',
				'button_url'      => $legacy_meta['button_url'] ?? '',
				'button_target'   => $legacy_meta['button_target'] ?? 'same_tab',
				'open_on_click'   => $legacy_meta['open_on_click'] ?? 'no',
				'duration'        => $legacy_meta['duration'] ?? prime_stories_get_setting( 'default_duration', 5 ),
				'fit_mode'        => $legacy_meta['fit_mode'] ?? 'global',
			),
			$defaults
		);

		$normalized[] = $legacy;
	}

	return $normalized;
}

/**
 * Sanitize one or more CSS classes from free text.
 *
 * @param string $classes Freeform class string.
 * @return string
 */
function prime_stories_sanitize_class_list( $classes ) {
	$sanitized = array();

	foreach ( preg_split( '/\s+/', (string) $classes ) as $class_name ) {
		$class_name = sanitize_html_class( $class_name );

		if ( $class_name ) {
			$sanitized[] = $class_name;
		}
	}

	return implode( ' ', array_unique( $sanitized ) );
}

/**
 * Determine whether frontend output is enabled.
 *
 * @return bool
 */
function prime_stories_is_frontend_enabled() {
	return prime_stories_is_enabled( prime_stories_get_setting( 'frontend_enabled', 'yes' ) );
}

/**
 * Determine whether the request should be treated as mobile.
 *
 * @return bool
 */
function prime_stories_is_mobile_request() {
	return function_exists( 'wp_is_mobile' ) ? wp_is_mobile() : false;
}

/**
 * Determine whether the current visitor matches the user audience rules.
 *
 * @param array<string, mixed> $meta Story meta.
 * @return bool
 */
function prime_stories_story_matches_audience( $meta ) {
	$user_rule = $meta['show_users'] ?? 'everyone';

	if ( 'guests_only' === $user_rule && is_user_logged_in() ) {
		return false;
	}

	if ( 'logged_in_only' === $user_rule && ! is_user_logged_in() ) {
		return false;
	}

	$device_rule = $meta['show_devices'] ?? 'all';
	$is_mobile   = prime_stories_is_mobile_request();

	if ( 'desktop_only' === $device_rule && $is_mobile ) {
		return false;
	}

	if ( 'mobile_only' === $device_rule && ! $is_mobile ) {
		return false;
	}

	return true;
}

/**
 * Determine whether a story matches its status and scheduling.
 *
 * @param array<string, mixed> $meta Story meta.
 * @return bool
 */
function prime_stories_story_matches_schedule( $meta ) {
	$status = $meta['story_status'] ?? 'active';

	if ( in_array( $status, array( 'inactive', 'expired' ), true ) ) {
		return false;
	}

	$current_timestamp = current_time( 'timestamp' );
	$start_timestamp   = ! empty( $meta['start_datetime'] ) ? strtotime( (string) $meta['start_datetime'] ) : false;
	$end_timestamp     = ! empty( $meta['end_datetime'] ) ? strtotime( (string) $meta['end_datetime'] ) : false;

	if ( $start_timestamp && $current_timestamp < $start_timestamp ) {
		return false;
	}

	if ( $end_timestamp && $current_timestamp > $end_timestamp ) {
		return false;
	}

	if ( 'scheduled' === $status && ! $start_timestamp && ! $end_timestamp ) {
		return false;
	}

	return true;
}

/**
 * Determine whether a story should be visible on the frontend.
 *
 * @param int $post_id Story ID.
 * @return bool
 */
function prime_stories_is_story_visible( $post_id ) {
	if ( 'prime_story' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
		return false;
	}

	$meta = prime_stories_get_story_meta( $post_id );
	$group = prime_stories_get_primary_group_payload( $post_id );

	if ( ! empty( $group ) && empty( $group['active'] ) ) {
		return false;
	}

	return prime_stories_story_matches_schedule( $meta ) && prime_stories_story_matches_audience( $meta );
}

/**
 * Prepare story data for rendering or REST output.
 *
 * @param int $post_id Story ID.
 * @return array<string, mixed>
 */
function prime_stories_get_story_payload( $post_id ) {
	$meta          = prime_stories_get_story_meta( $post_id );
	$slides        = prime_stories_prepare_slide_payloads( $post_id, $meta['slides'] );
	$first_slide   = ! empty( $slides[0] ) ? $slides[0] : array();
	$image_url     = $first_slide['image_url'] ?? '';
	$video_url     = $first_slide['video_url'] ?? '';
	$mobile_url    = $first_slide['mobile_media_url'] ?? '';
	$cover_url     = $meta['cover_image_id'] ? wp_get_attachment_image_url( $meta['cover_image_id'], 'medium' ) : ( $first_slide['cover_image_url'] ?? '' );
	$preview_image = $cover_url ? $cover_url : ( $first_slide['preview_image'] ?? prime_stories_get_logo_raster_url() );

	$groups = wp_get_post_terms( $post_id, 'prime_story_group', array( 'fields' => 'slugs' ) );
	$group_payload = prime_stories_get_primary_group_payload( $post_id );

	if ( is_wp_error( $groups ) ) {
		$groups = array();
	}

	return array(
		'id'               => $post_id,
		'title'            => get_the_title( $post_id ),
		'media_type'       => $meta['media_type'],
		'image_url'        => $image_url,
		'video_url'        => $video_url,
		'mobile_media_url' => $mobile_url,
		'cover_image_url'  => $cover_url,
		'preview_image'    => $preview_image,
		'caption'          => $meta['caption'],
		'subtitle'         => $meta['subtitle'],
		'button_text'      => $meta['button_text'],
		'button_url'       => $meta['button_url'],
		'button_target'    => $meta['button_target'],
		'duration'         => (int) $meta['duration'],
		'priority'         => (int) $meta['priority'],
		'custom_css_class' => prime_stories_sanitize_class_list( $meta['custom_css_class'] ),
		'open_on_click'    => 'yes' === $meta['open_on_click'],
		'fit_mode'         => $meta['fit_mode'],
		'slides'           => $slides,
		'groups'           => $groups,
		'primary_group'    => $group_payload,
	);
}

/**
 * Get display metadata for a story's first group/highlight.
 *
 * @param int $post_id Story ID.
 * @return array<string, mixed>
 */
function prime_stories_get_primary_group_payload( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'prime_story_group' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	usort(
		$terms,
		static function ( $left, $right ) {
			$left_order  = (int) get_term_meta( $left->term_id, 'prime_stories_group_order', true );
			$right_order = (int) get_term_meta( $right->term_id, 'prime_stories_group_order', true );

			return $left_order <=> $right_order;
		}
	);

	$term      = $terms[0];
	$active    = get_term_meta( $term->term_id, 'prime_stories_group_active', true );
	$avatar_id = absint( get_term_meta( $term->term_id, 'prime_stories_group_avatar_id', true ) );
	$avatar    = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'medium' ) : '';

	return array(
		'id'      => (int) $term->term_id,
		'slug'    => $term->slug,
		'title'   => $term->name,
		'active'  => 'no' !== $active,
		'order'   => (int) get_term_meta( $term->term_id, 'prime_stories_group_order', true ),
		'avatar'  => $avatar ? $avatar : '',
		'count'   => (int) $term->count,
	);
}

/**
 * Prepare all slide payloads for a story bubble.
 *
 * @param int                         $post_id Story post ID.
 * @param array<int, array<string,mixed>> $slides Normalized slides.
 * @return array<int, array<string, mixed>>
 */
function prime_stories_prepare_slide_payloads( $post_id, $slides ) {
	$payloads = array();

	foreach ( $slides as $index => $slide ) {
		$image_url     = ! empty( $slide['image_id'] ) ? wp_get_attachment_url( $slide['image_id'] ) : '';
		$video_url     = ! empty( $slide['video_id'] ) ? wp_get_attachment_url( $slide['video_id'] ) : '';
		$mobile_url    = ! empty( $slide['mobile_media_id'] ) ? wp_get_attachment_url( $slide['mobile_media_id'] ) : '';
		$mobile_type   = ! empty( $slide['mobile_media_id'] ) ? (string) get_post_mime_type( $slide['mobile_media_id'] ) : '';
		$cover_url     = ! empty( $slide['cover_image_id'] ) ? wp_get_attachment_image_url( $slide['cover_image_id'], 'medium' ) : '';
		$preview_image = $cover_url ? $cover_url : ( 'image' === $slide['media_type'] ? $image_url : wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'medium' ) );

		if ( empty( $preview_image ) ) {
			$preview_image = prime_stories_get_logo_raster_url();
		}

		if ( 'image' === $slide['media_type'] && empty( $image_url ) ) {
			$image_url = $mobile_url ? $mobile_url : $preview_image;
		}

		if ( 'video' === $slide['media_type'] && $mobile_url && 0 !== strpos( $mobile_type, 'video/' ) ) {
			$mobile_url = '';
		}

		if ( 'image' === $slide['media_type'] && $mobile_url && ! wp_attachment_is_image( $slide['mobile_media_id'] ) ) {
			$mobile_url = '';
		}

		$payloads[] = array(
			'id'               => absint( $post_id ) . '-' . sanitize_key( (string) $slide['id'] ),
			'story_id'         => absint( $post_id ),
			'slide_id'         => sanitize_key( (string) $slide['id'] ),
			'slide_index'      => $index,
			'title'            => $slide['title'] ? $slide['title'] : get_the_title( $post_id ),
			'media_type'       => $slide['media_type'],
			'image_url'        => $image_url,
			'video_url'        => $video_url,
			'mobile_media_url' => $mobile_url,
			'cover_image_url'  => $cover_url,
			'preview_image'    => $preview_image,
			'focal_x'          => (int) $slide['focal_x'],
			'focal_y'          => (int) $slide['focal_y'],
			'caption'          => $slide['caption'],
			'subtitle'         => $slide['subtitle'],
			'button_text'      => $slide['button_text'],
			'button_url'       => $slide['button_url'],
			'button_target'    => $slide['button_target'],
			'duration'         => (int) $slide['duration'],
			'fit_mode'         => $slide['fit_mode'],
			'open_on_click'    => 'yes' === $slide['open_on_click'],
			'action_type'      => $slide['action_type'],
			'action_payload'   => $slide['action_payload'],
			'poll_options'     => $slide['poll_options'],
			'poll_show_results' => 'yes' === $slide['poll_show_results'],
			'poll_vote_once'   => 'yes' === $slide['poll_vote_once'],
			'reply_placeholder' => $slide['reply_placeholder'],
			'question_success_message' => $slide['question_success_message'],
			'question_helper_text' => $slide['question_helper_text'],
			'allow_multiple_replies' => 'yes' === $slide['allow_multiple_replies'],
			'countdown_datetime' => $slide['countdown_datetime'],
		);
	}

	return $payloads;
}

/**
 * Query stories for the given context.
 *
 * @param array<string, mixed> $args Query args.
 * @return array<int, array<string, mixed>>
 */
function prime_stories_query_stories( $args = array() ) {
	static $cache = array();

	$args = wp_parse_args(
		$args,
		array(
			'group' => '',
			'limit' => 10,
		)
	);

	$args['limit'] = max( 1, absint( $args['limit'] ) );
	$args['group'] = is_scalar( $args['group'] ) ? sanitize_text_field( (string) $args['group'] ) : '';

	$cache_key = md5(
		wp_json_encode(
			array(
				'args'         => $args,
				'user'         => get_current_user_id(),
				'is_logged_in' => is_user_logged_in(),
				'is_mobile'    => prime_stories_is_mobile_request(),
			)
		)
	);

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$query_args = array(
		'post_type'              => 'prime_story',
		'post_status'            => 'publish',
		'posts_per_page'         => min( 200, max( $args['limit'] * 8, 50 ) ),
		'orderby'                => array(
			'title' => 'ASC',
		),
		'meta_query'             => array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => 'prime_stories_story_status',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'prime_stories_story_status',
					'value'   => array( 'active', 'scheduled' ),
					'compare' => 'IN',
				),
			),
		),
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => false,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	);

	if ( ! empty( $args['group'] ) ) {
		$field = is_numeric( $args['group'] ) ? 'term_id' : 'slug';

		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'prime_story_group',
				'field'    => $field,
				'terms'    => $args['group'],
			),
		);
	}

	$query_args['paged'] = 1;
	$query               = new WP_Query( $query_args );
	$stories = array();
	$max_pages = min( 20, max( 1, (int) $query->max_num_pages ) );

	while ( ! empty( $query->posts ) && count( $stories ) < $args['limit'] && $query_args['paged'] <= $max_pages ) {
		foreach ( $query->posts as $post ) {
			if ( ! prime_stories_is_story_visible( $post->ID ) ) {
				continue;
			}

			$stories[] = prime_stories_get_story_payload( $post->ID );

			if ( count( $stories ) >= $args['limit'] ) {
				break;
			}
		}

		if ( count( $stories ) >= $args['limit'] || $query_args['paged'] >= $max_pages ) {
			break;
		}

		$query_args['paged']++;
		$query = new WP_Query( $query_args );
	}

	usort(
		$stories,
		static function ( $left, $right ) {
			if ( $left['priority'] === $right['priority'] ) {
				return strcmp( $left['title'], $right['title'] );
			}

			return $left['priority'] <=> $right['priority'];
		}
	);

	$limit = max( 1, absint( $args['limit'] ) );

	$cache[ $cache_key ] = array_slice( $stories, 0, $limit );

	return $cache[ $cache_key ];
}

/**
 * Get available story groups.
 *
 * @return array<int, WP_Term>
 */
function prime_stories_get_story_groups() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'prime_story_group',
			'hide_empty' => false,
		)
	);

	return is_array( $terms ) ? $terms : array();
}

/**
 * Get stored display rules.
 *
 * @return array<int, array<string, mixed>>
 */
function prime_stories_get_display_rules() {
	$rules = get_option( 'prime_stories_display_rules', array() );

	return is_array( $rules ) ? $rules : array();
}

/**
 * Get seen story IDs for a logged-in user.
 *
 * @param int $user_id User ID.
 * @return array<int, int>
 */
function prime_stories_get_seen_story_ids( $user_id ) {
	$seen = get_user_meta( $user_id, 'prime_stories_seen_story_ids', true );

	if ( ! is_array( $seen ) ) {
		return array();
	}

	return array_map( 'absint', $seen );
}

/**
 * Mark a story as seen for a logged-in user.
 *
 * @param int $story_id Story ID.
 * @param int $user_id User ID.
 * @return void
 */
function prime_stories_mark_story_seen( $story_id, $user_id ) {
	$story_id = absint( $story_id );
	$user_id  = absint( $user_id );

	if ( ! $story_id || ! $user_id ) {
		return;
	}

	$seen = prime_stories_get_seen_story_ids( $user_id );

	if ( in_array( $story_id, $seen, true ) ) {
		return;
	}

	$seen[] = $story_id;
	update_user_meta( $user_id, 'prime_stories_seen_story_ids', array_values( array_unique( $seen ) ) );
}

/**
 * Sanitize a frontend session identifier.
 *
 * @param string $session_id Raw session ID.
 * @return string
 */
function prime_stories_sanitize_session_id( $session_id ) {
	$session_id = sanitize_text_field( (string) $session_id );
	$session_id = preg_replace( '/[^a-zA-Z0-9\-\_]/', '', $session_id );

	return is_string( $session_id ) ? substr( $session_id, 0, 191 ) : '';
}

/**
 * Get the guest seen-state transient key for a session.
 *
 * @param string $session_id Frontend session ID.
 * @return string
 */
function prime_stories_get_guest_seen_key( $session_id ) {
	return 'prime_stories_guest_seen_' . md5( prime_stories_sanitize_session_id( $session_id ) );
}

/**
 * Get seen story IDs for a guest session.
 *
 * @param string $session_id Frontend session ID.
 * @return array<int, int>
 */
function prime_stories_get_guest_seen_story_ids( $session_id ) {
	$session_id = prime_stories_sanitize_session_id( $session_id );

	if ( ! $session_id || ! prime_stories_is_enabled( prime_stories_get_setting( 'enable_guest_seen_state', 'yes' ) ) ) {
		return array();
	}

	$seen = get_transient( prime_stories_get_guest_seen_key( $session_id ) );

	if ( ! is_array( $seen ) ) {
		return array();
	}

	return array_values( array_unique( array_map( 'absint', $seen ) ) );
}

/**
 * Mark a story as seen for a guest session.
 *
 * @param int    $story_id Story ID.
 * @param string $session_id Frontend session ID.
 * @return void
 */
function prime_stories_mark_guest_story_seen( $story_id, $session_id ) {
	$story_id   = absint( $story_id );
	$session_id = prime_stories_sanitize_session_id( $session_id );

	if ( ! $story_id || ! $session_id || ! prime_stories_is_enabled( prime_stories_get_setting( 'enable_guest_seen_state', 'yes' ) ) ) {
		return;
	}

	$seen = prime_stories_get_guest_seen_story_ids( $session_id );

	if ( ! in_array( $story_id, $seen, true ) ) {
		$seen[] = $story_id;
	}

	$retention_days = max( 1, absint( prime_stories_get_setting( 'guest_seen_retention_days', 30 ) ) );
	set_transient( prime_stories_get_guest_seen_key( $session_id ), array_values( array_unique( $seen ) ), $retention_days * DAY_IN_SECONDS );
}
