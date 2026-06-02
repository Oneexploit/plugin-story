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
);

foreach ( $meta_keys as $meta_key ) {
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ), array( '%s' ) );
}

$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'prime_stories_seen_story_ids' ), array( '%s' ) );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'prime_stories_analytics' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

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
