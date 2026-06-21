<?php
/**
 * Story viewer template.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	class="<?php echo esc_attr( $wrapper_classes ); ?>"
	style="<?php echo esc_attr( $inline_style ); ?>"
	data-prime-stories
	data-instance-id="<?php echo esc_attr( $instance_id ); ?>"
	data-autoplay="<?php echo esc_attr( $args['autoplay'] ? 'true' : 'false' ); ?>"
	data-open-mode="<?php echo esc_attr( $args['open_mode'] ); ?>"
	data-layout="<?php echo esc_attr( $args['layout'] ); ?>"
	dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>"
>
	<div class="prime-stories-track" role="list" aria-label="<?php esc_attr_e( 'Story list', 'prime-stories' ); ?>">
		<?php foreach ( $stories as $index => $story ) : ?>
			<?php $is_seen = in_array( (int) $story['id'], $seen_story_ids, true ); ?>
			<?php $group = ! empty( $story['primary_group'] ) && is_array( $story['primary_group'] ) ? $story['primary_group'] : array(); ?>
			<button
				type="button"
				class="prime-stories-item <?php echo $is_seen ? 'prime-stories-item-seen' : 'prime-stories-item-unseen'; ?>"
				data-story-trigger
				data-story-id="<?php echo esc_attr( (string) $story['id'] ); ?>"
				data-story-index="<?php echo esc_attr( (string) ( $story['start_index'] ?? $index ) ); ?>"
				data-story-slide-count="<?php echo esc_attr( (string) ( $story['slide_count'] ?? 1 ) ); ?>"
				role="listitem"
				aria-label="<?php echo esc_attr( sprintf( __( 'Open story %s', 'prime-stories' ), $story['title'] ) ); ?>"
			>
				<span class="prime-stories-item-media">
					<img src="<?php echo esc_url( ! empty( $group['avatar'] ) ? $group['avatar'] : $story['preview_image'] ); ?>" alt="<?php echo esc_attr( ! empty( $group['title'] ) ? $group['title'] : $story['title'] ); ?>" loading="lazy" />
					<?php if ( ! empty( $group['count'] ) && (int) $group['count'] > 1 ) : ?>
						<small class="prime-stories-item-count"><?php echo esc_html( number_format_i18n( (int) $group['count'] ) ); ?></small>
					<?php endif; ?>
				</span>
				<?php if ( $args['show_title'] ) : ?>
					<span class="prime-stories-item-title"><?php echo esc_html( ! empty( $group['title'] ) ? $group['title'] : $story['title'] ); ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="prime-stories-viewer" hidden aria-hidden="true">
		<div class="prime-stories-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Story viewer', 'prime-stories' ); ?>" tabindex="-1">
			<div class="prime-stories-progress">
				<?php foreach ( $flat_slides as $slide ) : ?>
					<span class="prime-stories-progress-track" data-story-progress="<?php echo esc_attr( (string) $slide['id'] ); ?>">
						<span class="prime-stories-progress-bar"></span>
					</span>
				<?php endforeach; ?>
			</div>

			<div class="prime-stories-toolbar">
				<button type="button" class="prime-stories-icon-button prime-stories-mute-button" data-story-mute hidden aria-label="<?php esc_attr_e( 'Toggle video sound', 'prime-stories' ); ?>">
					<span class="prime-stories-mute-glyph" aria-hidden="true"></span>
				</button>
				<button type="button" class="prime-stories-icon-button prime-stories-close-button" data-story-close aria-label="<?php esc_attr_e( 'Close story viewer', 'prime-stories' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="prime-stories-slides">
				<?php foreach ( $flat_slides as $index => $story ) : ?>
					<article
						class="prime-stories-slide <?php echo ! empty( $story['custom_css_class'] ) ? esc_attr( prime_stories_sanitize_class_list( $story['custom_css_class'] ) ) : ''; ?>"
						data-story-slide
						data-story-id="<?php echo esc_attr( (string) $story['parent_story_id'] ); ?>"
						data-slide-id="<?php echo esc_attr( (string) $story['id'] ); ?>"
						data-story-index="<?php echo esc_attr( (string) $index ); ?>"
						data-media-type="<?php echo esc_attr( $story['media_type'] ); ?>"
						data-duration="<?php echo esc_attr( (string) $story['duration'] ); ?>"
						data-button-url="<?php echo esc_url( $story['button_url'] ); ?>"
						data-button-target="<?php echo esc_attr( $story['button_target'] ); ?>"
						data-open-on-click="<?php echo $story['open_on_click'] ? 'true' : 'false'; ?>"
						data-fit-mode="<?php echo esc_attr( 'global' === $story['fit_mode'] ? $args['fit_mode'] : $story['fit_mode'] ); ?>"
						data-focal-x="<?php echo esc_attr( (string) ( $story['focal_x'] ?? 50 ) ); ?>"
						data-focal-y="<?php echo esc_attr( (string) ( $story['focal_y'] ?? 50 ) ); ?>"
						data-story-group-start="<?php echo esc_attr( (string) ( $story['parent_start_index'] ?? 0 ) ); ?>"
						data-story-group-count="<?php echo esc_attr( (string) ( $story['parent_slide_count'] ?? 1 ) ); ?>"
						data-error-label="<?php esc_attr_e( 'Media could not be loaded.', 'prime-stories' ); ?>"
						hidden
					>
						<div class="prime-stories-slide-header">
							<img src="<?php echo esc_url( $story['parent_preview'] ); ?>" alt="" />
							<span><?php echo esc_html( $story['parent_story_title'] ); ?></span>
						</div>
						<div class="prime-stories-slide-media" data-story-clickable>
							<?php if ( 'video' === $story['media_type'] ) : ?>
								<video
									class="prime-stories-media prime-stories-video"
									playsinline
									preload="metadata"
									muted
									poster="<?php echo esc_url( $story['preview_image'] ); ?>"
									data-desktop-src="<?php echo esc_url( $story['video_url'] ); ?>"
									data-mobile-src="<?php echo esc_url( $story['mobile_media_url'] ); ?>"
									style="object-position: <?php echo esc_attr( (string) ( $story['focal_x'] ?? 50 ) ); ?>% <?php echo esc_attr( (string) ( $story['focal_y'] ?? 50 ) ); ?>%;"
								></video>
							<?php else : ?>
								<img
									class="prime-stories-media prime-stories-image"
									alt="<?php echo esc_attr( $story['title'] ); ?>"
									data-desktop-src="<?php echo esc_url( $story['image_url'] ? $story['image_url'] : $story['preview_image'] ); ?>"
									data-mobile-src="<?php echo esc_url( $story['mobile_media_url'] ); ?>"
									style="object-position: <?php echo esc_attr( (string) ( $story['focal_x'] ?? 50 ) ); ?>% <?php echo esc_attr( (string) ( $story['focal_y'] ?? 50 ) ); ?>%;"
								/>
							<?php endif; ?>
						</div>

						<div class="prime-stories-overlay"></div>

						<div class="prime-stories-slide-content">
							<div class="prime-stories-slide-copy">
								<?php if ( ! empty( $story['title'] ) ) : ?>
									<h3 class="prime-stories-slide-title"><?php echo esc_html( $story['title'] ); ?></h3>
								<?php endif; ?>
								<?php if ( ! empty( $story['subtitle'] ) ) : ?>
									<p class="prime-stories-slide-subtitle"><?php echo esc_html( $story['subtitle'] ); ?></p>
								<?php endif; ?>
								<?php if ( ! empty( $story['caption'] ) ) : ?>
									<div class="prime-stories-slide-caption"><?php echo wp_kses_post( wpautop( $story['caption'] ) ); ?></div>
								<?php endif; ?>
							</div>

							<?php if ( ! empty( $story['button_text'] ) && ! empty( $story['button_url'] ) ) : ?>
								<a
									class="prime-stories-cta"
									href="<?php echo esc_url( $story['button_url'] ); ?>"
									data-story-cta
									target="<?php echo 'new_tab' === $story['button_target'] ? '_blank' : '_self'; ?>"
									rel="<?php echo 'new_tab' === $story['button_target'] ? 'noopener noreferrer' : ''; ?>"
								>
									<?php echo esc_html( $story['button_text'] ); ?>
								</a>
							<?php endif; ?>

							<?php if ( ! empty( $story['action_type'] ) && 'none' !== $story['action_type'] ) : ?>
								<div
									class="prime-stories-action prime-stories-action-<?php echo esc_attr( $story['action_type'] ); ?>"
									data-story-action="<?php echo esc_attr( $story['action_type'] ); ?>"
									data-poll-show-results="<?php echo ! empty( $story['poll_show_results'] ) ? 'true' : 'false'; ?>"
									data-poll-vote-once="<?php echo ! empty( $story['poll_vote_once'] ) ? 'true' : 'false'; ?>"
									data-success-message="<?php echo esc_attr( ! empty( $story['question_success_message'] ) ? $story['question_success_message'] : __( 'Sent', 'prime-stories' ) ); ?>"
									data-allow-multiple-replies="<?php echo ! empty( $story['allow_multiple_replies'] ) ? 'true' : 'false'; ?>"
								>
									<?php if ( 'reaction' === $story['action_type'] ) : ?>
										<button type="button" data-story-reaction="like"><?php esc_html_e( 'Like', 'prime-stories' ); ?></button>
										<button type="button" data-story-reaction="love"><?php esc_html_e( 'Love', 'prime-stories' ); ?></button>
										<button type="button" data-story-reaction="wow"><?php esc_html_e( 'Wow', 'prime-stories' ); ?></button>
									<?php elseif ( 'poll' === $story['action_type'] ) : ?>
										<?php
										$poll_options = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $story['poll_options'] ?: "Yes\nNo" ) ) ) );
										?>
										<p><?php echo esc_html( $story['action_payload'] ? $story['action_payload'] : __( 'What do you think?', 'prime-stories' ) ); ?></p>
										<div class="prime-stories-poll-options">
											<?php foreach ( $poll_options as $poll_index => $poll_option ) : ?>
												<?php
												$poll_parts = array_map( 'trim', explode( '|', $poll_option ) );
												$poll_label = $poll_parts[0] ?? '';
												$poll_color = ! empty( $poll_parts[1] ) ? sanitize_hex_color( $poll_parts[1] ) : '';
												?>
												<button type="button" data-story-reaction="<?php echo esc_attr( 'poll_' . sanitize_key( $poll_label ) ); ?>" data-poll-option style="<?php echo $poll_color ? '--poll-color:' . esc_attr( $poll_color ) . ';' : ''; ?>">
													<span><?php echo esc_html( $poll_label ); ?></span>
													<small data-poll-result hidden></small>
												</button>
											<?php endforeach; ?>
										</div>
									<?php elseif ( 'question' === $story['action_type'] ) : ?>
										<label>
											<span><?php echo esc_html( $story['action_payload'] ? $story['action_payload'] : __( 'Send a reply', 'prime-stories' ) ); ?></span>
											<input type="text" data-story-reply maxlength="160" placeholder="<?php echo esc_attr( $story['reply_placeholder'] ? $story['reply_placeholder'] : __( 'Write a reply...', 'prime-stories' ) ); ?>">
										</label>
										<?php if ( ! empty( $story['question_helper_text'] ) ) : ?>
											<small class="prime-stories-action-help"><?php echo esc_html( $story['question_helper_text'] ); ?></small>
										<?php endif; ?>
										<small class="prime-stories-reply-status" data-story-reply-status aria-live="polite"></small>
										<button type="button" data-story-reply-submit><?php esc_html_e( 'Send', 'prime-stories' ); ?></button>
									<?php elseif ( 'countdown' === $story['action_type'] ) : ?>
										<p><?php echo esc_html( $story['action_payload'] ? $story['action_payload'] : __( 'Countdown', 'prime-stories' ) ); ?></p>
										<strong data-story-countdown="<?php echo esc_attr( $story['countdown_datetime'] ); ?>"><?php echo esc_html( $story['countdown_datetime'] ); ?></strong>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<button type="button" class="prime-stories-nav prime-stories-nav-prev" data-story-prev aria-label="<?php esc_attr_e( 'Previous story', 'prime-stories' ); ?>"></button>
			<button type="button" class="prime-stories-nav prime-stories-nav-next" data-story-next aria-label="<?php esc_attr_e( 'Next story', 'prime-stories' ); ?>"></button>
		</div>
	</div>
</div>
