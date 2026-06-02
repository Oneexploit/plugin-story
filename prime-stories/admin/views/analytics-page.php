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
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $top_stories ) ) : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No analytics data yet.', 'prime-stories' ); ?></td>
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
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
