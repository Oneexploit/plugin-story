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
			<?php $this->render_slides_editor( $meta['slides'] ); ?>
			<?php $this->render_legacy_hidden_fields( $meta ); ?>
			<div class="prime-stories-admin-grid">
				<div class="prime-stories-admin-card">
					<h3><?php esc_html_e( 'Publishing', 'prime-stories' ); ?></h3>
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
					case 'slides':
						$value = $this->sanitize_slides_request( $_POST['prime_stories_slides'] ?? array(), $post_id );
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
	 * Sanitize slide repeater request.
	 *
	 * @param mixed $raw_slides Raw slide rows.
	 * @param int   $post_id Story post ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_slides_request( $raw_slides, $post_id ) {
		$raw_slides = is_array( $raw_slides ) ? $raw_slides : array();
		$slides     = array();

		foreach ( $raw_slides as $index => $raw_slide ) {
			if ( ! is_array( $raw_slide ) ) {
				continue;
			}

			$slide = array(
				'id'              => sanitize_key( (string) ( $raw_slide['id'] ?? 'slide-' . ( $index + 1 ) ) ),
				'media_type'      => prime_stories_sanitize_select( (string) ( $raw_slide['media_type'] ?? '' ), array( 'image', 'video' ), 'image' ),
				'image_id'        => $this->sanitize_attachment_id( $raw_slide['image_id'] ?? 0, 'image', 'slides.image_id', $post_id ),
				'video_id'        => $this->sanitize_attachment_id( $raw_slide['video_id'] ?? 0, 'video', 'slides.video_id', $post_id ),
				'mobile_media_id' => $this->sanitize_attachment_id( $raw_slide['mobile_media_id'] ?? 0, 'media', 'slides.mobile_media_id', $post_id ),
				'cover_image_id'  => $this->sanitize_attachment_id( $raw_slide['cover_image_id'] ?? 0, 'image', 'slides.cover_image_id', $post_id ),
				'title'           => sanitize_text_field( wp_unslash( (string) ( $raw_slide['title'] ?? '' ) ) ),
				'subtitle'        => sanitize_text_field( wp_unslash( (string) ( $raw_slide['subtitle'] ?? '' ) ) ),
				'caption'         => wp_kses_post( wp_unslash( (string) ( $raw_slide['caption'] ?? '' ) ) ),
				'button_text'     => sanitize_text_field( wp_unslash( (string) ( $raw_slide['button_text'] ?? '' ) ) ),
				'button_url'      => esc_url_raw( wp_unslash( (string) ( $raw_slide['button_url'] ?? '' ) ) ),
				'button_target'   => prime_stories_sanitize_select( (string) ( $raw_slide['button_target'] ?? '' ), array( 'same_tab', 'new_tab' ), 'same_tab' ),
				'open_on_click'   => prime_stories_sanitize_select( (string) ( $raw_slide['open_on_click'] ?? '' ), array( 'yes', 'no' ), 'no' ),
				'duration'        => max( 1, min( 60, absint( $raw_slide['duration'] ?? prime_stories_get_setting( 'default_duration', 5 ) ) ) ),
				'fit_mode'        => prime_stories_sanitize_select( (string) ( $raw_slide['fit_mode'] ?? '' ), array( 'global', 'cover', 'contain' ), 'global' ),
				'action_type'     => prime_stories_sanitize_select( (string) ( $raw_slide['action_type'] ?? '' ), array( 'none', 'reaction', 'poll', 'question', 'countdown' ), 'none' ),
				'action_payload'  => sanitize_textarea_field( wp_unslash( (string) ( $raw_slide['action_payload'] ?? '' ) ) ),
			);

			if ( prime_stories_slide_has_content( $slide ) ) {
				$slides[] = $slide;
			}
		}

		return prime_stories_normalize_slides( $slides, $post_id, array() );
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
	 * Render the multi-slide editor.
	 *
	 * @param array<int, array<string, mixed>> $slides Slides.
	 * @return void
	 */
	private function render_slides_editor( $slides ) {
		$slides = ! empty( $slides ) ? array_values( $slides ) : array( prime_stories_get_default_slide_meta() );
		?>
		<div class="prime-stories-admin-card prime-stories-slides-editor">
			<div class="prime-stories-admin-card-heading">
				<div>
					<h3><?php esc_html_e( 'Story Slides', 'prime-stories' ); ?></h3>
					<p><?php esc_html_e( 'Build the story frame by frame. Each slide owns its media, caption, CTA, timing, and interaction.', 'prime-stories' ); ?></p>
				</div>
				<button type="button" class="button button-primary" id="prime-stories-add-slide"><?php esc_html_e( 'Add slide', 'prime-stories' ); ?></button>
			</div>
			<div id="prime-stories-slide-rows">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php $this->render_slide_row( $index, wp_parse_args( $slide, prime_stories_get_default_slide_meta() ) ); ?>
				<?php endforeach; ?>
			</div>
			<script type="text/template" id="tmpl-prime-stories-slide-row">
				<?php $this->render_slide_row( '__INDEX__', prime_stories_get_default_slide_meta() ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render one slide row.
	 *
	 * @param int|string            $index Row index.
	 * @param array<string, mixed>  $slide Slide data.
	 * @return void
	 */
	private function render_slide_row( $index, $slide ) {
		$prefix = 'prime_stories_slides[' . $index . ']';
		$id     = $slide['id'] ? $slide['id'] : 'slide-' . ( is_numeric( $index ) ? ( (int) $index + 1 ) : '__INDEX__' );
		?>
		<div class="prime-stories-slide-row" data-slide-row>
			<div class="prime-stories-slide-row-head">
				<strong><?php esc_html_e( 'Slide', 'prime-stories' ); ?> <span data-slide-number><?php echo esc_html( is_numeric( $index ) ? (string) ( (int) $index + 1 ) : '__INDEX__' ); ?></span></strong>
				<div class="prime-stories-slide-row-actions">
					<button type="button" class="button prime-stories-move-slide-up" aria-label="<?php esc_attr_e( 'Move slide up', 'prime-stories' ); ?>"><?php esc_html_e( 'Up', 'prime-stories' ); ?></button>
					<button type="button" class="button prime-stories-move-slide-down" aria-label="<?php esc_attr_e( 'Move slide down', 'prime-stories' ); ?>"><?php esc_html_e( 'Down', 'prime-stories' ); ?></button>
					<button type="button" class="button-link-delete prime-stories-remove-slide"><?php esc_html_e( 'Remove', 'prime-stories' ); ?></button>
				</div>
			</div>
			<input type="hidden" name="<?php echo esc_attr( $prefix . '[id]' ); ?>" value="<?php echo esc_attr( (string) $id ); ?>">
			<div class="prime-stories-slide-layout">
				<div class="prime-stories-slide-preview" data-slide-preview>
					<div class="prime-stories-slide-preview-media">
						<?php $this->render_slide_preview_media( $slide ); ?>
					</div>
					<strong data-slide-preview-title><?php echo esc_html( $slide['title'] ? $slide['title'] : __( 'Untitled slide', 'prime-stories' ) ); ?></strong>
					<span data-slide-preview-meta><?php echo esc_html( sprintf( __( '%1$s seconds - %2$s', 'prime-stories' ), (int) $slide['duration'], ucfirst( (string) $slide['action_type'] ) ) ); ?></span>
				</div>
				<div>
					<div class="prime-stories-admin-grid">
						<?php $this->render_slide_select( $prefix, 'media_type', __( 'Media type', 'prime-stories' ), $slide['media_type'], array( 'image' => __( 'Image', 'prime-stories' ), 'video' => __( 'Video', 'prime-stories' ) ) ); ?>
						<?php $this->render_slide_media_field( $prefix, 'image_id', __( 'Image', 'prime-stories' ), $slide['image_id'], 'image' ); ?>
						<?php $this->render_slide_media_field( $prefix, 'video_id', __( 'Video', 'prime-stories' ), $slide['video_id'], 'video' ); ?>
						<?php $this->render_slide_media_field( $prefix, 'mobile_media_id', __( 'Mobile override', 'prime-stories' ), $slide['mobile_media_id'], 'media' ); ?>
						<?php $this->render_slide_media_field( $prefix, 'cover_image_id', __( 'Cover', 'prime-stories' ), $slide['cover_image_id'], 'image' ); ?>
						<?php $this->render_slide_input( $prefix, 'title', __( 'Slide title', 'prime-stories' ), $slide['title'] ); ?>
						<?php $this->render_slide_input( $prefix, 'subtitle', __( 'Subtitle', 'prime-stories' ), $slide['subtitle'] ); ?>
						<?php $this->render_slide_input( $prefix, 'button_text', __( 'Button text', 'prime-stories' ), $slide['button_text'] ); ?>
						<?php $this->render_slide_input( $prefix, 'button_url', __( 'Button URL', 'prime-stories' ), $slide['button_url'], 'url' ); ?>
						<?php $this->render_slide_select( $prefix, 'button_target', __( 'Button target', 'prime-stories' ), $slide['button_target'], array( 'same_tab' => __( 'Same tab', 'prime-stories' ), 'new_tab' => __( 'New tab', 'prime-stories' ) ) ); ?>
						<?php $this->render_slide_select( $prefix, 'open_on_click', __( 'Open on media click', 'prime-stories' ), $slide['open_on_click'], array( 'no' => __( 'No', 'prime-stories' ), 'yes' => __( 'Yes', 'prime-stories' ) ) ); ?>
						<?php $this->render_slide_input( $prefix, 'duration', __( 'Duration', 'prime-stories' ), (string) $slide['duration'], 'number' ); ?>
						<?php $this->render_slide_select( $prefix, 'fit_mode', __( 'Fit mode', 'prime-stories' ), $slide['fit_mode'], array( 'global' => __( 'Global', 'prime-stories' ), 'cover' => __( 'Fill frame', 'prime-stories' ), 'contain' => __( 'Show full media', 'prime-stories' ) ) ); ?>
						<?php $this->render_slide_select( $prefix, 'action_type', __( 'Story action', 'prime-stories' ), $slide['action_type'], array( 'none' => __( 'None', 'prime-stories' ), 'reaction' => __( 'Reactions', 'prime-stories' ), 'poll' => __( 'Poll', 'prime-stories' ), 'question' => __( 'Question', 'prime-stories' ), 'countdown' => __( 'Countdown', 'prime-stories' ) ) ); ?>
					</div>
					<label class="prime-stories-admin-field">
						<span><?php esc_html_e( 'Caption', 'prime-stories' ); ?></span>
						<textarea rows="3" name="<?php echo esc_attr( $prefix . '[caption]' ); ?>"><?php echo esc_textarea( (string) $slide['caption'] ); ?></textarea>
					</label>
					<label class="prime-stories-admin-field" data-slide-action-payload-field>
						<span><?php esc_html_e( 'Action text / payload', 'prime-stories' ); ?></span>
						<textarea rows="2" name="<?php echo esc_attr( $prefix . '[action_payload]' ); ?>"><?php echo esc_textarea( (string) $slide['action_payload'] ); ?></textarea>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_legacy_hidden_fields( $meta ) {
		foreach ( array( 'media_type', 'image_id', 'video_id', 'mobile_media_id', 'cover_image_id', 'caption', 'subtitle', 'button_text', 'button_url', 'button_target', 'open_on_click', 'duration', 'fit_mode' ) as $key ) {
			$value = $meta[ $key ] ?? '';
			?>
			<input type="hidden" name="<?php echo esc_attr( 'prime_stories_' . $key ); ?>" value="<?php echo esc_attr( is_scalar( $value ) ? (string) $value : '' ); ?>">
			<?php
		}
	}

	private function render_slide_preview_media( $slide ) {
		$attachment_id = ! empty( $slide['cover_image_id'] ) ? absint( $slide['cover_image_id'] ) : absint( $slide['image_id'] );
		$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

		if ( $preview_url ) {
			?>
			<img src="<?php echo esc_url( $preview_url ); ?>" alt="">
			<?php
			return;
		}

		?>
		<span><?php esc_html_e( 'Preview', 'prime-stories' ); ?></span>
		<?php
	}

	private function render_slide_input( $prefix, $key, $label, $value, $type = 'text' ) {
		?>
		<label class="prime-stories-admin-field">
			<span><?php echo esc_html( $label ); ?></span>
			<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $prefix . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
		</label>
		<?php
	}

	private function render_slide_select( $prefix, $key, $label, $value, $options ) {
		?>
		<label class="prime-stories-admin-field">
			<span><?php echo esc_html( $label ); ?></span>
			<select name="<?php echo esc_attr( $prefix . '[' . $key . ']' ); ?>">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	private function render_slide_media_field( $prefix, $key, $label, $attachment_id, $library_type ) {
		$field_id       = sanitize_html_class( str_replace( array( '[', ']' ), '-', $prefix . '-' . $key ) );
		$attachment_id = absint( $attachment_id );
		$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
		$file_url      = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
		?>
		<div class="prime-stories-admin-field prime-stories-media-field">
			<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="hidden" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $prefix . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
			<div class="prime-stories-media-controls">
				<button type="button" class="button prime-stories-media-select" data-target="<?php echo esc_attr( $field_id ); ?>" data-library-type="<?php echo esc_attr( $library_type ); ?>"><?php esc_html_e( 'Choose', 'prime-stories' ); ?></button>
				<button type="button" class="button-link-delete prime-stories-media-remove" data-target="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Remove', 'prime-stories' ); ?></button>
			</div>
			<div class="prime-stories-media-preview">
				<?php if ( $preview_url ) : ?>
					<img src="<?php echo esc_url( $preview_url ); ?>" alt="">
				<?php elseif ( $file_url ) : ?>
					<span><?php echo esc_html( basename( (string) $file_url ) ); ?></span>
				<?php else : ?>
					<span><?php esc_html_e( 'No media selected', 'prime-stories' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
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
