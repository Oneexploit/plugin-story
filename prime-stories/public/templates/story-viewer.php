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
			<button
				type="button"
				class="prime-stories-item <?php echo $is_seen ? 'prime-stories-item-seen' : 'prime-stories-item-unseen'; ?>"
				data-story-trigger
				data-story-id="<?php echo esc_attr( (string) $story['id'] ); ?>"
				data-story-index="<?php echo esc_attr( (string) $index ); ?>"
				role="listitem"
				aria-label="<?php echo esc_attr( sprintf( __( 'Open story %s', 'prime-stories' ), $story['title'] ) ); ?>"
			>
				<span class="prime-stories-item-media">
					<img src="<?php echo esc_url( $story['preview_image'] ); ?>" alt="<?php echo esc_attr( $story['title'] ); ?>" loading="lazy" />
				</span>
				<?php if ( $args['show_title'] ) : ?>
					<span class="prime-stories-item-title"><?php echo esc_html( $story['title'] ); ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="prime-stories-viewer" hidden aria-hidden="true">
		<div class="prime-stories-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Story viewer', 'prime-stories' ); ?>" tabindex="-1">
			<div class="prime-stories-progress">
				<?php foreach ( $stories as $story ) : ?>
					<span class="prime-stories-progress-track" data-story-progress="<?php echo esc_attr( (string) $story['id'] ); ?>">
						<span class="prime-stories-progress-bar"></span>
					</span>
				<?php endforeach; ?>
			</div>

			<div class="prime-stories-toolbar">
				<button type="button" class="prime-stories-icon-button prime-stories-mute-button" data-story-mute hidden aria-label="<?php esc_attr_e( 'Toggle video sound', 'prime-stories' ); ?>">
					<?php esc_html_e( 'Mute', 'prime-stories' ); ?>
				</button>
				<button type="button" class="prime-stories-icon-button prime-stories-close-button" data-story-close aria-label="<?php esc_attr_e( 'Close story viewer', 'prime-stories' ); ?>">
					<?php esc_html_e( 'Close', 'prime-stories' ); ?>
				</button>
			</div>

			<div class="prime-stories-slides">
				<?php foreach ( $stories as $index => $story ) : ?>
					<article
						class="prime-stories-slide <?php echo ! empty( $story['custom_css_class'] ) ? esc_attr( prime_stories_sanitize_class_list( $story['custom_css_class'] ) ) : ''; ?>"
						data-story-slide
						data-story-id="<?php echo esc_attr( (string) $story['id'] ); ?>"
						data-story-index="<?php echo esc_attr( (string) $index ); ?>"
						data-media-type="<?php echo esc_attr( $story['media_type'] ); ?>"
						data-duration="<?php echo esc_attr( (string) $story['duration'] ); ?>"
						data-button-url="<?php echo esc_url( $story['button_url'] ); ?>"
						data-button-target="<?php echo esc_attr( $story['button_target'] ); ?>"
						data-open-on-click="<?php echo $story['open_on_click'] ? 'true' : 'false'; ?>"
						hidden
					>
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
								></video>
							<?php else : ?>
								<img
									class="prime-stories-media prime-stories-image"
									alt="<?php echo esc_attr( $story['title'] ); ?>"
									data-desktop-src="<?php echo esc_url( $story['image_url'] ? $story['image_url'] : $story['preview_image'] ); ?>"
									data-mobile-src="<?php echo esc_url( $story['mobile_media_url'] ); ?>"
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
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<button type="button" class="prime-stories-nav prime-stories-nav-prev" data-story-prev aria-label="<?php esc_attr_e( 'Previous story', 'prime-stories' ); ?>"></button>
			<button type="button" class="prime-stories-nav prime-stories-nav-next" data-story-next aria-label="<?php esc_attr_e( 'Next story', 'prime-stories' ); ?>"></button>
		</div>
	</div>
</div>
