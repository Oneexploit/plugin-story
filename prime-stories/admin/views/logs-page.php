<?php
/**
 * Diagnostics logs page view.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

$clear_status = isset( $_GET['cleared'] ) ? sanitize_text_field( wp_unslash( $_GET['cleared'] ) ) : '';
?>
<div class="wrap prime-stories-admin-wrap">
	<?php
	prime_stories_render_admin_header(
		__( 'Prime Stories Logs', 'prime-stories' ),
		__( 'Review diagnostic events with timestamps, source locations, and request context so it is easier to pinpoint exactly where a problem started.', 'prime-stories' )
	);
	?>

	<?php if ( 'true' === $clear_status ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Plugin logs were cleared.', 'prime-stories' ); ?></p></div>
	<?php elseif ( 'false' === $clear_status ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The log files could not be cleared. Please verify filesystem permissions.', 'prime-stories' ); ?></p></div>
	<?php endif; ?>

	<div class="prime-stories-stats-grid">
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Logging', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( $log_status['enabled'] ? __( 'Enabled', 'prime-stories' ) : __( 'Disabled', 'prime-stories' ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Writable', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( $log_status['is_writable'] ? __( 'Yes', 'prime-stories' ) : __( 'No', 'prime-stories' ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Log size', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( size_format( (int) $log_status['size'] ) ); ?></strong>
		</div>
		<div class="prime-stories-stat-card">
			<span><?php esc_html_e( 'Last update (UTC)', 'prime-stories' ); ?></span>
			<strong><?php echo esc_html( $log_status['updated_at'] ? $log_status['updated_at'] : __( 'No entries yet', 'prime-stories' ) ); ?></strong>
		</div>
	</div>

	<div class="prime-stories-admin-card prime-stories-log-meta-card">
		<p><strong><?php esc_html_e( 'Log file', 'prime-stories' ); ?>:</strong> <code><?php echo esc_html( (string) $log_status['path'] ); ?></code></p>
		<p><strong><?php esc_html_e( 'Request ID for this page load', 'prime-stories' ); ?>:</strong> <code><?php echo esc_html( (string) $log_status['request_id'] ); ?></code></p>
		<p class="description"><?php esc_html_e( 'Use the source, request ID, and context columns together to trace one failing flow across PHP and frontend diagnostics.', 'prime-stories' ); ?></p>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="prime_stories_clear_logs">
			<?php wp_nonce_field( 'prime_stories_clear_logs' ); ?>
			<?php submit_button( __( 'Clear Log Files', 'prime-stories' ), 'delete', 'submit', false ); ?>
		</form>
	</div>

	<h2><?php esc_html_e( 'Recent Entries', 'prime-stories' ); ?></h2>

	<table class="widefat striped prime-stories-logs-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Time', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Level', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Source', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Message', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Request', 'prime-stories' ); ?></th>
				<th><?php esc_html_e( 'Context', 'prime-stories' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $entries ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No diagnostic entries yet.', 'prime-stories' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $entries as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( (string) ( $entry['timestamp'] ?? '' ) ); ?></td>
						<td>
							<span class="prime-stories-log-level prime-stories-log-level-<?php echo esc_attr( (string) ( $entry['level'] ?? 'info' ) ); ?>">
								<?php echo esc_html( strtoupper( (string) ( $entry['level'] ?? 'info' ) ) ); ?>
							</span>
						</td>
						<td><code><?php echo esc_html( (string) ( $entry['source'] ?? '' ) ); ?></code></td>
						<td><?php echo esc_html( (string) ( $entry['message'] ?? '' ) ); ?></td>
						<td>
							<?php if ( ! empty( $entry['request_id'] ) ) : ?>
								<code><?php echo esc_html( (string) $entry['request_id'] ); ?></code>
							<?php endif; ?>
							<?php if ( ! empty( $entry['request_uri'] ) ) : ?>
								<div><small><?php echo esc_html( (string) $entry['request_uri'] ); ?></small></div>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $entry['context'] ) && is_array( $entry['context'] ) ) : ?>
								<details>
									<summary><?php esc_html_e( 'View context', 'prime-stories' ); ?></summary>
									<pre><?php echo esc_html( wp_json_encode( $entry['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre>
								</details>
							<?php else : ?>
								<span>-</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
