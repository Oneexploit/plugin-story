<?php
/**
 * Story meta boxes.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage story meta boxes.
 */
class Prime_Stories_Meta_Boxes {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Meta_Boxes|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Meta_Boxes
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_prime_story', array( $this, 'save_meta_boxes' ) );
	}

	/**
	 * Register story meta box.
	 *
	 * @return void
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'prime-stories-details',
			__( 'Story Details', 'prime-stories' ),
			array( $this, 'render_story_meta_box' ),
			'prime_story',
			'normal',
			'high'
		);
	}

	/**
	 * Render story meta box fields.
	 *
	 * @param WP_Post $post Current story post.
	 * @return void
	 */
	public function render_story_meta_box( $post ) {
		$meta = prime_stories_get_story_meta( $post->ID );

		wp_nonce_field( 'prime_stories_save_story', 'prime_stories_story_nonce' );
		?>
		<div class="prime-stories-admin-panel">
			<div class="prime-stories-admin-grid">
				<div class="prime-stories-admin-card">
					<h3><?php esc_html_e( 'Media', 'prime-stories' ); ?></h3>
					<?php $this->render_select_field( 'media_type', __( 'Story media type', 'prime-stories' ), $meta['media_type'], array( 'image' => __( 'Image', 'prime-stories' ), 'video' => __( 'Video', 'prime-stories' ) ) ); ?>
					<?php $this->render_media_field( 'image_id', __( 'Story image', 'prime-stories' ), $meta['image_id'] ); ?>
					<?php $this->render_media_field( 'video_id', __( 'Story video', 'prime-stories' ), $meta['video_id'] ); ?>
					<?php $this->render_media_field( 'mobile_media_id', __( 'Mobile image/video override', 'prime-stories' ), $meta['mobile_media_id'] ); ?>
					<?php $this->render_media_field( 'cover_image_id', __( 'Cover image', 'prime-stories' ), $meta['cover_image_id'] ); ?>
					<?php $this->render_select_field( 'fit_mode', __( 'Media fit mode', 'prime-stories' ), $meta['fit_mode'], array( 'global' => __( 'Use global setting', 'prime-stories' ), 'cover' => __( 'Fill frame', 'prime-stories' ), 'contain' => __( 'Show full media', 'prime-stories' ) ) ); ?>
				</div>

				<div class="prime-stories-admin-card">
					<h3><?php esc_html_e( 'Content', 'prime-stories' ); ?></h3>
					<?php $this->render_textarea_field( 'caption', __( 'Story caption', 'prime-stories' ), $meta['caption'] ); ?>
					<?php $this->render_text_field( 'subtitle', __( 'Story subtitle', 'prime-stories' ), $meta['subtitle'] ); ?>
					<?php $this->render_text_field( 'button_text', __( 'Button text', 'prime-stories' ), $meta['button_text'] ); ?>
					<?php $this->render_url_field( 'button_url', __( 'Button URL', 'prime-stories' ), $meta['button_url'] ); ?>
					<?php $this->render_select_field( 'button_target', __( 'Button target', 'prime-stories' ), $meta['button_target'], array( 'same_tab' => __( 'Same tab', 'prime-stories' ), 'new_tab' => __( 'New tab', 'prime-stories' ) ) ); ?>
					<?php $this->render_select_field( 'open_on_click', __( 'Open link on story click', 'prime-stories' ), $meta['open_on_click'], array( 'no' => __( 'No', 'prime-stories' ), 'yes' => __( 'Yes', 'prime-stories' ) ) ); ?>
				</div>

				<div class="prime-stories-admin-card">
					<h3><?php esc_html_e( 'Behavior', 'prime-stories' ); ?></h3>
					<?php $this->render_number_field( 'duration', __( 'Duration in seconds', 'prime-stories' ), (int) $meta['duration'], 1, 60 ); ?>
					<?php $this->render_number_field( 'priority', __( 'Priority / order', 'prime-stories' ), (int) $meta['priority'] ); ?>
					<?php $this->render_select_field( 'story_status', __( 'Story status', 'prime-stories' ), $meta['story_status'], array( 'active' => __( 'Active', 'prime-stories' ), 'inactive' => __( 'Inactive', 'prime-stories' ), 'scheduled' => __( 'Scheduled', 'prime-stories' ), 'expired' => __( 'Expired', 'prime-stories' ) ) ); ?>
					<?php $this->render_datetime_field( 'start_datetime', __( 'Start datetime', 'prime-stories' ), $meta['start_datetime'] ); ?>
					<?php $this->render_datetime_field( 'end_datetime', __( 'End datetime', 'prime-stories' ), $meta['end_datetime'] ); ?>
				</div>

				<div class="prime-stories-admin-card">
					<h3><?php esc_html_e( 'Targeting', 'prime-stories' ); ?></h3>
					<?php $this->render_select_field( 'show_devices', __( 'Show on devices', 'prime-stories' ), $meta['show_devices'], array( 'all' => __( 'All devices', 'prime-stories' ), 'desktop_only' => __( 'Desktop only', 'prime-stories' ), 'mobile_only' => __( 'Mobile only', 'prime-stories' ) ) ); ?>
					<?php $this->render_select_field( 'show_users', __( 'Show to users', 'prime-stories' ), $meta['show_users'], array( 'everyone' => __( 'Everyone', 'prime-stories' ), 'guests_only' => __( 'Guests only', 'prime-stories' ), 'logged_in_only' => __( 'Logged-in users only', 'prime-stories' ) ) ); ?>
					<?php $this->render_text_field( 'custom_css_class', __( 'Custom CSS class', 'prime-stories' ), $meta['custom_css_class'] ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save story meta securely.
	 *
	 * @param int $post_id Story ID.
	 * @return void
	 */
	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['prime_stories_story_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prime_stories_story_nonce'] ) ), 'prime_stories_save_story' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		try {
			$defaults = prime_stories_get_default_story_meta();

			foreach ( $defaults as $key => $default ) {
				$raw_value = $_POST[ 'prime_stories_' . $key ] ?? null;

				switch ( $key ) {
					case 'media_type':
						$value = prime_stories_sanitize_select( $raw_value, array( 'image', 'video' ), 'image' );
						break;
					case 'image_id':
						$value = $this->sanitize_attachment_id( $raw_value, 'image', $key, $post_id );
						break;
					case 'video_id':
						$value = $this->sanitize_attachment_id( $raw_value, 'video', $key, $post_id );
						break;
					case 'mobile_media_id':
						$value = $this->sanitize_attachment_id( $raw_value, 'media', $key, $post_id );
						break;
					case 'cover_image_id':
						$value = $this->sanitize_attachment_id( $raw_value, 'image', $key, $post_id );
						break;
					case 'duration':
						$value = max( 1, min( 60, absint( $raw_value ) ) );
						break;
					case 'priority':
						$value = (int) $raw_value;
						break;
					case 'story_status':
						$value = prime_stories_sanitize_select( $raw_value, array( 'active', 'inactive', 'scheduled', 'expired' ), 'active' );
						break;
					case 'show_devices':
						$value = prime_stories_sanitize_select( $raw_value, array( 'all', 'desktop_only', 'mobile_only' ), 'all' );
						break;
					case 'show_users':
						$value = prime_stories_sanitize_select( $raw_value, array( 'everyone', 'guests_only', 'logged_in_only' ), 'everyone' );
						break;
					case 'button_target':
						$value = prime_stories_sanitize_select( $raw_value, array( 'same_tab', 'new_tab' ), 'same_tab' );
						break;
					case 'open_on_click':
						$value = prime_stories_sanitize_select( $raw_value, array( 'yes', 'no' ), 'no' );
						break;
					case 'fit_mode':
						$value = prime_stories_sanitize_select( $raw_value, array( 'global', 'cover', 'contain' ), 'global' );
						break;
					case 'button_url':
						$value = esc_url_raw( wp_unslash( (string) $raw_value ) );
						break;
					case 'caption':
						$value = wp_kses_post( wp_unslash( (string) $raw_value ) );
						break;
					case 'start_datetime':
					case 'end_datetime':
						$value = $this->sanitize_datetime_value( $raw_value, $key, $post_id );
						break;
					case 'subtitle':
					case 'button_text':
					case 'custom_css_class':
						$value = 'custom_css_class' === $key ? prime_stories_sanitize_class_list( wp_unslash( (string) $raw_value ) ) : sanitize_text_field( wp_unslash( (string) $raw_value ) );
						break;
					default:
						$value = is_scalar( $raw_value ) ? sanitize_text_field( wp_unslash( (string) $raw_value ) ) : $default;
				}

				update_post_meta( $post_id, 'prime_stories_' . $key, $value );
			}
		} catch ( Throwable $exception ) {
			Prime_Stories_Logger::get_instance()->exception(
				$exception,
				array(
					'post_id' => $post_id,
				),
				'meta_boxes.save'
			);
		}
	}

	/**
	 * Sanitize datetime-local input into MySQL datetime.
	 *
	 * @param mixed  $raw_value Raw request value.
	 * @param string $field_key Field key for diagnostics.
	 * @param int    $post_id Story ID for diagnostics.
	 * @return string
	 */
	private function sanitize_datetime_value( $raw_value, $field_key = '', $post_id = 0 ) {
		if ( empty( $raw_value ) || ! is_scalar( $raw_value ) ) {
			return '';
		}

		$timestamp = strtotime( sanitize_text_field( wp_unslash( (string) $raw_value ) ) );

		if ( ! $timestamp ) {
			prime_stories_log(
				'warning',
				'An invalid story datetime value was rejected.',
				array(
					'post_id' => absint( $post_id ),
					'field'   => sanitize_key( $field_key ),
					'value'   => (string) $raw_value,
				),
				'meta_boxes.datetime'
			);

			return '';
		}

		return wp_date( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Sanitize attachment IDs by expected media type.
	 *
	 * @param mixed  $raw_value Raw attachment ID.
	 * @param string $expected_type Expected type.
	 * @param string $field_key Field key for diagnostics.
	 * @param int    $post_id Story ID for diagnostics.
	 * @return int
	 */
	private function sanitize_attachment_id( $raw_value, $expected_type, $field_key = '', $post_id = 0 ) {
		$attachment_id = absint( $raw_value );

		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			if ( $attachment_id ) {
				prime_stories_log(
					'warning',
					'A story media attachment was rejected because it is not a valid attachment.',
					array(
						'post_id'       => absint( $post_id ),
						'field'         => sanitize_key( $field_key ),
						'attachment_id' => $attachment_id,
					),
					'meta_boxes.attachment'
				);
			}

			return 0;
		}

		if ( 'image' === $expected_type && ! wp_attachment_is_image( $attachment_id ) ) {
			prime_stories_log(
				'warning',
				'A story media attachment was rejected because an image was expected.',
				array(
					'post_id'       => absint( $post_id ),
					'field'         => sanitize_key( $field_key ),
					'attachment_id' => $attachment_id,
				),
				'meta_boxes.attachment'
			);

			return 0;
		}

		if ( 'video' === $expected_type ) {
			$mime_type = (string) get_post_mime_type( $attachment_id );

			if ( 0 !== strpos( $mime_type, 'video/' ) ) {
				prime_stories_log(
					'warning',
					'A story media attachment was rejected because a video was expected.',
					array(
						'post_id'       => absint( $post_id ),
						'field'         => sanitize_key( $field_key ),
						'attachment_id' => $attachment_id,
						'mime_type'     => $mime_type,
					),
					'meta_boxes.attachment'
				);

				return 0;
			}
		}

		if ( 'media' === $expected_type ) {
			$mime_type = (string) get_post_mime_type( $attachment_id );

			if ( ! wp_attachment_is_image( $attachment_id ) && 0 !== strpos( $mime_type, 'video/' ) ) {
				prime_stories_log(
					'warning',
					'A story media attachment was rejected because only image or video files are allowed.',
					array(
						'post_id'       => absint( $post_id ),
						'field'         => sanitize_key( $field_key ),
						'attachment_id' => $attachment_id,
						'mime_type'     => $mime_type,
					),
					'meta_boxes.attachment'
				);

				return 0;
			}
		}

		return $attachment_id;
	}

	/**
	 * Render a text field.
	 *
	 * @param string $key Field key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private function render_text_field( $key, $label, $value ) {
		?>
		<p class="prime-stories-admin-field">
			<label for="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input class="regular-text" type="text" id="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
		</p>
		<?php
	}

	/**
	 * Render a textarea field.
	 *
	 * @param string $key Field key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private function render_textarea_field( $key, $label, $value ) {
		?>
		<p class="prime-stories-admin-field">
			<label for="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<textarea class="large-text" rows="4" id="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Render a URL field.
	 *
	 * @param string $key Field key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private function render_url_field( $key, $label, $value ) {
		?>
		<p class="prime-stories-admin-field">
			<label for="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input class="regular-text" type="url" id="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" value="<?php echo esc_url( $value ); ?>" placeholder="https://">
		</p>
		<?php
	}

	/**
	 * Render a number field.
	 *
	 * @param string   $key Field key.
	 * @param string   $label Field label.
	 * @param int      $value Field value.
	 * @param int|null $min Min value.
	 * @param int|null $max Max value.
	 * @return void
	 */
	private function render_number_field( $key, $label, $value, $min = null, $max = null ) {
		?>
		<p class="prime-stories-admin-field">
			<label for="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input class="small-text" type="number" id="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" <?php echo null !== $min ? 'min="' . esc_attr( (string) $min ) . '"' : ''; ?> <?php echo null !== $max ? 'max="' . esc_attr( (string) $max ) . '"' : ''; ?>>
		</p>
		<?php
	}

	/**
	 * Render a select field.
	 *
	 * @param string               $key Field key.
	 * @param string               $label Field label.
	 * @param string               $value Field value.
	 * @param array<string,string> $options Options.
	 * @return void
	 */
	private function render_select_field( $key, $label, $value, $options ) {
		?>
		<p class="prime-stories-admin-field">
			<label for="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<select id="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Render a datetime field.
	 *
	 * @param string $key Field key.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private function render_datetime_field( $key, $label, $value ) {
		$formatted_value = '';

		if ( ! empty( $value ) ) {
			$timestamp = strtotime( $value );

			if ( $timestamp ) {
				$formatted_value = wp_date( 'Y-m-d\TH:i', $timestamp );
			}
		}
		?>
		<p class="prime-stories-admin-field">
			<label for="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="datetime-local" id="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" value="<?php echo esc_attr( $formatted_value ); ?>">
		</p>
		<?php
	}

	/**
	 * Render an attachment field.
	 *
	 * @param string $key Field key.
	 * @param string $label Field label.
	 * @param int    $attachment_id Attachment ID.
	 * @return void
	 */
	private function render_media_field( $key, $label, $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$file_url      = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
		$preview_url   = $preview_url ? $preview_url : $file_url;
		$mime_type     = $attachment_id ? (string) get_post_mime_type( $attachment_id ) : '';
		$library_type  = 'image_id' === $key || 'cover_image_id' === $key ? 'image' : ( 'video_id' === $key ? 'video' : 'media' );
		?>
		<div class="prime-stories-admin-field prime-stories-media-field prime-stories-media-field-<?php echo esc_attr( $key ); ?>">
			<label for="<?php echo esc_attr( 'prime_stories_' . $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="hidden" id="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
			<div class="prime-stories-media-controls">
				<button type="button" class="button prime-stories-media-select" data-target="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" data-library-type="<?php echo esc_attr( $library_type ); ?>">
					<?php esc_html_e( 'Choose media', 'prime-stories' ); ?>
				</button>
				<button type="button" class="button-link-delete prime-stories-media-remove" data-target="<?php echo esc_attr( 'prime_stories_' . $key ); ?>">
					<?php esc_html_e( 'Remove', 'prime-stories' ); ?>
				</button>
			</div>
			<div class="prime-stories-media-preview">
				<?php if ( $preview_url ) : ?>
					<?php if ( wp_attachment_is_image( $attachment_id ) ) : ?>
						<img src="<?php echo esc_url( $preview_url ); ?>" alt="" />
					<?php elseif ( 0 === strpos( $mime_type, 'video/' ) ) : ?>
						<span><?php echo esc_html( basename( (string) $file_url ) ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( basename( (string) $file_url ) ); ?></a>
					<?php endif; ?>
				<?php else : ?>
					<span><?php esc_html_e( 'No media selected', 'prime-stories' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
