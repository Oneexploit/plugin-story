<?php
/**
 * Analytics page view.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap prime-stories-admin-wrap">
	<?php
	prime_stories_render_admin_header(
		__( 'Prime Stories Analytics', 'prime-stories' ),
		__( 'Review impressions, opens, completions, and CTA clicks to understand how your story groups are performing.', 'prime-stories' )
	);
	?>

	<div class="prime-stories-stats-grid">
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Impressions', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $summary['impression'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Opens', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $summary['open'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Completions', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $summary['complete'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Clicks', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $summary['click'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Reactions', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $summary['reaction'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Replies', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $summary['reply'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Unique Sessions', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (int) $summary['unique_sessions'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'CTR', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (float) $summary['ctr'], 2 ) ); ?>%</strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Completion Rate', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( number_format_i18n( (float) $summary['completion_rate'], 2 ) ); ?>%</strong>
		</div>
	</div>

	<h2><?php esc_html_e( 'Top Stories', 'prime-stories' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Story', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Impressions', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Opens', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Completions', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Clicks', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Reactions', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Replies', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Unique Sessions', 'prime-stories' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $top_stories ) ) : ?>
				<tr>
					<td colspan="8"><?php esc_html_e( 'No analytics data yet.', 'prime-stories' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $top_stories as $story ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( (int) $story['story_id'] ) ?: '' ); ?>">
								<?php echo esc_html( $story['title'] ); ?>
							</a>
						</td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['impression_count'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['open_count'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['complete_count'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['click_count'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['reaction_count'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['reply_count'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $story['unique_sessions'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Story Interactions', 'prime-stories' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Story', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Slide', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Type', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Value', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Total', 'prime-stories' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $interactions ) ) : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No reactions, poll votes, or replies yet.', 'prime-stories' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $interactions as $interaction ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( (int) $interaction['story_id'] ) ?: '' ); ?>">
								<?php echo esc_html( $interaction['title'] ); ?>
							</a>
						</td>
						<td><code><?php echo esc_html( (string) $interaction['slide_id'] ); ?></code></td>
						<td><?php echo esc_html( ucfirst( (string) $interaction['event_type'] ) ); ?></td>
						<td><?php echo esc_html( str_replace( '_', ' ', (string) $interaction['event_value'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $interaction['total'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Reply Inbox', 'prime-stories' ); ?></h2>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Story', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Slide', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Reply', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Created', 'prime-stories' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $recent_replies ) ) : ?>
				<tr>
					<td colspan="4"><?php esc_html_e( 'No text replies yet.', 'prime-stories' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $recent_replies as $reply ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( (int) $reply['story_id'] ) ?: '' ); ?>">
								<?php echo esc_html( $reply['title'] ); ?>
							</a>
						</td>
						<td><code><?php echo esc_html( (string) $reply['slide_id'] ); ?></code></td>
						<td><?php echo esc_html( (string) $reply['action_payload'] ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (string) $reply['created_at'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
