<?php
/**
 * Story Studio admin page.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap prime-stories-admin-wrap">
	<?php
	prime_stories_render_admin_header(
		__( 'Story Manager', 'prime-stories' ),
		__( 'Create and manage clean image and video stories from one place.', 'prime-stories' )
	);
	?>

	<form class="prime-stories-studio-search" method="get">
		<input type="hidden" name="post_type" value="prime_story">
		<input type="hidden" name="page" value="prime-stories-studio">
		<label class="screen-reader-text" for="prime-stories-studio-search"><?php esc_html_e( 'Search stories', 'prime-stories' ); ?></label>
		<input type="search" id="prime-stories-studio-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search stories...', 'prime-stories' ); ?>">
		<button type="submit" class="button"><?php esc_html_e( 'Search', 'prime-stories' ); ?></button>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=prime_story' ) ); ?>"><?php esc_html_e( 'Create Story', 'prime-stories' ); ?></a>
	</form>

	<div class="prime-stories-studio-grid">
			<?php if ( empty( $stories ) ) : ?>
				<div class="prime-stories-empty"><span class="dashicons dashicons-format-gallery"></span><h2><?php esc_html_e( 'No stories yet', 'prime-stories' ); ?></h2><p><?php esc_html_e( 'Create your first image or video story.', 'prime-stories' ); ?></p></div>
			<?php else : ?>
				<?php foreach ( $stories as $story ) : ?>
					<a class="prime-stories-story-card" href="<?php echo esc_url( $story['edit_link'] ); ?>">
						<div class="prime-stories-story-card-icon"><span class="dashicons dashicons-format-video"></span></div>
						<div class="prime-stories-story-card-body"><h2><?php echo esc_html( $story['title'] ? $story['title'] : __( 'Untitled story', 'prime-stories' ) ); ?></h2><p><?php printf( esc_html__( '%1$d slides · Updated %2$s', 'prime-stories' ), (int) $story['slides'], esc_html( (string) $story['modified'] ) ); ?></p></div>
						<span class="prime-stories-status-pill prime-stories-status-<?php echo esc_attr( sanitize_html_class( $story['status'] ) ); ?>"><?php echo esc_html( 'active' === $story['status'] ? __( 'Active', 'prime-stories' ) : __( 'Inactive', 'prime-stories' ) ); ?></span>
						<span class="dashicons dashicons-arrow-left-alt2"></span>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>
	</div>
</div>
