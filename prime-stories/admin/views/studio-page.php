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
		__( 'Story Studio', 'prime-stories' ),
		__( 'Manage stories, highlights, schedules, and slide counts from one focused workspace.', 'prime-stories' )
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

	<table class="widefat striped prime-stories-studio-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Story', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Groups', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Status', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Schedule', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Slides', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Updated', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'prime-stories' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $stories ) ) : ?>
				<tr>
					<td colspan="7"><?php esc_html_e( 'No stories found. Create your first story to start building a highlight experience.', 'prime-stories' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $stories as $story ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $story['title'] ? $story['title'] : __( '(no title)', 'prime-stories' ) ); ?></strong>
							<small>#<?php echo esc_html( (string) $story['id'] ); ?></small>
						</td>
						<td><?php echo esc_html( ! empty( $story['groups'] ) && is_array( $story['groups'] ) ? implode( ', ', $story['groups'] ) : __( 'No group', 'prime-stories' ) ); ?></td>
						<td><span class="prime-stories-status-pill prime-stories-status-<?php echo esc_attr( sanitize_html_class( $story['status'] ) ); ?>"><?php echo esc_html( ucfirst( $story['status'] ) ); ?></span></td>
						<td>
							<?php if ( $story['start_datetime'] || $story['end_datetime'] ) : ?>
								<?php echo esc_html( trim( $story['start_datetime'] . ' - ' . $story['end_datetime'], ' -' ) ); ?>
							<?php else : ?>
								<?php esc_html_e( 'Always active', 'prime-stories' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['slides'] ) ); ?></td>
						<td><?php echo esc_html( (string) $story['modified'] ); ?></td>
						<td>
							<a class="button button-small" href="<?php echo esc_url( $story['edit_link'] ); ?>"><?php esc_html_e( 'Edit', 'prime-stories' ); ?></a>
							<a class="button button-small" href="<?php echo esc_url( admin_url( 'edit.php?post_type=prime_story&page=prime-stories-analytics' ) ); ?>"><?php esc_html_e( 'Analytics', 'prime-stories' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
