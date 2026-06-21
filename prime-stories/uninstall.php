<?php
/**
 * Uninstall Prime Stories.
 *
 * @package PrimeStories
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'prime_stories_settings', array() );

if ( ! is_array( $settings ) || empty( $settings['remove_data_on_uninstall'] ) || 'yes' !== $settings['remove_data_on_uninstall'] ) {
	return;
}

global $wpdb;

wp_clear_scheduled_hook( 'prime_stories_daily_analytics_cleanup' );

register_post_type(
	'prime_story',
	array(
		'public' => false,
	)
);

register_taxonomy(
	'prime_story_group',
	array( 'prime_story' ),
	array(
		'public'       => false,
		'hierarchical' => true,
	)
);

delete_option( 'prime_stories_settings' );
delete_option( 'prime_stories_display_rules' );
delete_option( 'prime_stories_db_version' );
delete_option( 'prime_stories_version' );

$meta_keys = array(
	'prime_stories_media_type',
	'prime_stories_image_id',
	'prime_stories_video_id',
	'prime_stories_mobile_media_id',
	'prime_stories_cover_image_id',
	'prime_stories_caption',
	'prime_stories_subtitle',
	'prime_stories_button_text',
	'prime_stories_button_url',
	'prime_stories_button_target',
	'prime_stories_duration',
	'prime_stories_priority',
	'prime_stories_story_status',
	'prime_stories_start_datetime',
	'prime_stories_end_datetime',
	'prime_stories_show_devices',
	'prime_stories_show_users',
	'prime_stories_custom_css_class',
	'prime_stories_open_on_click',
	'prime_stories_fit_mode',
);

foreach ( $meta_keys as $meta_key ) {
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ), array( '%s' ) );
}

$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'prime_stories_seen_story_ids' ), array( '%s' ) );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'prime_stories_analytics' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$guest_seen_like         = $wpdb->esc_like( '_transient_prime_stories_guest_seen_' ) . '%';
$guest_seen_timeout_like = $wpdb->esc_like( '_transient_timeout_prime_stories_guest_seen_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $guest_seen_like, $guest_seen_timeout_like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

$story_ids = get_posts(
	array(
		'post_type'      => 'prime_story',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $story_ids as $story_id ) {
	wp_delete_post( (int) $story_id, true );
}

$terms = get_terms(
	array(
		'taxonomy'   => 'prime_story_group',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( is_array( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( (int) $term_id, 'prime_story_group' );
	}
}

$upload_dir = wp_upload_dir();
$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'prime-stories/logs';
$base_dir   = trailingslashit( $upload_dir['basedir'] ) . 'prime-stories';

foreach ( array( trailingslashit( $log_dir ) . 'prime-stories.log', trailingslashit( $log_dir ) . 'prime-stories.log.1' ) as $log_file ) {
	if ( file_exists( $log_file ) ) {
		wp_delete_file( $log_file );
	}
}

if ( is_dir( $log_dir ) ) {
	@rmdir( $log_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}

if ( is_dir( $base_dir ) ) {
	@rmdir( $base_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}
