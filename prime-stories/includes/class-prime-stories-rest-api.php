<?php
/**
 * REST API endpoints.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API manager.
 */
class Prime_Stories_REST_API {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_REST_API|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_REST_API
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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'prime-stories/v1',
			'/track',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'track_event' ),
				'permission_callback' => array( $this, 'check_public_write_permission' ),
				'args'                => array(
					'story_id'   => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_story_id' ),
					),
					'event_type' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => array( $this, 'validate_event_type' ),
					),
					'session_id' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'source'     => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'meta'       => array(
						'type' => 'object',
					),
				),
			)
		);

		register_rest_route(
			'prime-stories/v1',
			'/seen',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'mark_seen' ),
				'permission_callback' => array( $this, 'check_public_write_permission' ),
				'args'                => array(
					'story_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_story_id' ),
					),
					'session_id' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'prime-stories/v1',
			'/stories',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_stories' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'group' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'prime-stories/v1',
			'/results',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_results' ),
				'permission_callback' => array( $this, 'check_public_write_permission' ),
				'args'                => array(
					'story_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'validate_story_id' ),
					),
					'slide_id' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'event_type' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			'prime-stories/v1',
			'/log',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'log_client_event' ),
				'permission_callback' => array( $this, 'check_public_write_permission' ),
				'args'                => array(
					'level'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'source'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'message' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);
	}

	/**
	 * Track analytics events.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function track_event( WP_REST_Request $request ) {
		$story_id   = absint( $request->get_param( 'story_id' ) );
		$event_type = sanitize_key( (string) $request->get_param( 'event_type' ) );
		$session_id = sanitize_text_field( (string) $request->get_param( 'session_id' ) );
		$source     = sanitize_key( (string) $request->get_param( 'source' ) );
		$meta       = $request->get_param( 'meta' );
		$event_value = '';
		$slide_id   = '';
		$action_payload = '';
		$user_id    = get_current_user_id();

		if ( is_array( $meta ) ) {
			if ( ! empty( $meta['slide'] ) ) {
				$slide_id = sanitize_key( (string) $meta['slide'] );
			}

			if ( ! empty( $meta['reaction'] ) ) {
				$event_value = sanitize_key( (string) $meta['reaction'] );
			}

			if ( 'reply' === $event_type && ! empty( $meta['reply'] ) ) {
				$event_value    = 'reply';
				$action_payload = sanitize_textarea_field( (string) $meta['reply'] );
			}
		}

		if ( ! $this->validate_story_id( $story_id ) ) {
			prime_stories_log(
				'warning',
				'Analytics tracking rejected because the story ID was invalid.',
				array(
					'story_id'   => $story_id,
					'event_type' => $event_type,
				),
				'rest.track_event'
			);

			return new WP_Error( 'prime_stories_invalid_story', __( 'Invalid story.', 'prime-stories' ), array( 'status' => 400 ) );
		}

		if ( ! $this->validate_event_type( $event_type ) ) {
			prime_stories_log(
				'warning',
				'Analytics tracking rejected because the event type was invalid.',
				array(
					'story_id'   => $story_id,
					'event_type' => $event_type,
				),
				'rest.track_event'
			);

			return new WP_Error( 'prime_stories_invalid_event', __( 'Invalid analytics event.', 'prime-stories' ), array( 'status' => 400 ) );
		}

		if ( $slide_id && ! $this->validate_slide_id( $story_id, $slide_id ) ) {
			return new WP_Error( 'prime_stories_invalid_slide', __( 'Invalid story slide.', 'prime-stories' ), array( 'status' => 400 ) );
		}

		$tracked = Prime_Stories_Analytics::get_instance()->track_event( $story_id, $event_type, $session_id, $user_id, $source, $event_value, $slide_id, $action_payload );

		if ( ! $tracked ) {
			prime_stories_log(
				'warning',
				'Analytics event was not recorded.',
				array(
					'story_id'   => $story_id,
					'event_type' => $event_type,
					'user_id'    => $user_id,
				),
				'rest.track_event'
			);
		}

		return rest_ensure_response(
			array(
				'success' => (bool) $tracked,
			)
		);
	}

	/**
	 * Mark a story as seen.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function mark_seen( WP_REST_Request $request ) {
		$story_id = absint( $request->get_param( 'story_id' ) );

		if ( ! $this->validate_story_id( $story_id ) ) {
			prime_stories_log(
				'warning',
				'Seen-state request rejected because the story ID was invalid.',
				array(
					'story_id' => $story_id,
				),
				'rest.mark_seen'
			);

			return new WP_Error( 'prime_stories_invalid_story', __( 'Invalid story.', 'prime-stories' ), array( 'status' => 400 ) );
		}

		if ( ! prime_stories_is_enabled( prime_stories_get_setting( 'enable_seen_state', 'yes' ) ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
				)
			);
		}

		if ( is_user_logged_in() ) {
			prime_stories_mark_story_seen( $story_id, get_current_user_id() );
		} else {
			prime_stories_mark_guest_story_seen( $story_id, sanitize_text_field( (string) $request->get_param( 'session_id' ) ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Return active stories for a group.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_stories( WP_REST_Request $request ) {
		$group = sanitize_text_field( (string) $request->get_param( 'group' ) );
		$limit = absint( $request->get_param( 'limit' ) );

		if ( ! prime_stories_is_frontend_enabled() ) {
			return rest_ensure_response(
				array(
					'stories' => array(),
				)
			);
		}

		return rest_ensure_response(
			array(
				'stories' => prime_stories_query_stories(
					array(
						'group' => $group,
						'limit' => max( 1, min( $limit ? $limit : 10, 50 ) ),
					)
				),
			)
		);
	}

	/**
	 * Return aggregate interaction results for a slide.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_results( WP_REST_Request $request ) {
		$story_id   = absint( $request->get_param( 'story_id' ) );
		$slide_id   = sanitize_key( (string) $request->get_param( 'slide_id' ) );
		$event_type = sanitize_key( (string) $request->get_param( 'event_type' ) );
		$event_type = $event_type ? $event_type : 'reaction';

		if ( ! $this->validate_story_id( $story_id ) ) {
			return new WP_Error( 'prime_stories_invalid_story', __( 'Invalid story.', 'prime-stories' ), array( 'status' => 400 ) );
		}

		if ( $slide_id && ! $this->validate_slide_id( $story_id, $slide_id ) ) {
			return new WP_Error( 'prime_stories_invalid_slide', __( 'Invalid story slide.', 'prime-stories' ), array( 'status' => 400 ) );
		}

		$counts = Prime_Stories_Analytics::get_instance()->get_event_value_counts( $story_id, $slide_id, $event_type );

		return rest_ensure_response(
			array(
				'success' => true,
				'counts'  => $counts,
				'total'   => array_sum( $counts ),
			)
		);
	}

	/**
	 * Store a frontend/client diagnostic event.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function log_client_event( WP_REST_Request $request ) {
		if ( ! prime_stories_is_enabled( prime_stories_get_setting( 'enable_debug_logging', 'yes' ) ) || ! prime_stories_is_enabled( prime_stories_get_setting( 'enable_client_logging', 'yes' ) ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
				)
			);
		}

		$level      = sanitize_key( (string) $request->get_param( 'level' ) );
		$source     = sanitize_text_field( (string) $request->get_param( 'source' ) );
		$message    = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
		$context    = $request->get_param( 'context' );
		$session_id = sanitize_text_field( (string) $request->get_param( 'session_id' ) );

		if ( empty( $message ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
				)
			);
		}

		if ( $this->is_client_log_rate_limited( $message, $source, $session_id ) ) {
			return rest_ensure_response(
				array(
					'success' => true,
				)
			);
		}

		$log_context = array(
			'client'     => 'story-viewer',
			'session_id' => $session_id,
			'context'    => is_array( $context ) ? $context : array(),
		);

		prime_stories_log( $level ? $level : 'error', $message, $log_context, $source ? $source : 'rest.client' );

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Verify public POST permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function check_public_write_permission( WP_REST_Request $request ) {
		if ( $this->verify_nonce( $request ) ) {
			return true;
		}

		$nonce_failure_key = 'prime_stories_nonce_' . md5( $request->get_route() . '|' . $request->get_method() . '|' . $this->get_request_fingerprint() );

		if ( ! get_transient( $nonce_failure_key ) ) {
			set_transient( $nonce_failure_key, 1, MINUTE_IN_SECONDS );

			prime_stories_log(
				'warning',
				'REST request rejected because nonce verification failed.',
				array(
					'route'  => $request->get_route(),
					'method' => $request->get_method(),
				),
				'rest.permission'
			);
		}

		return new WP_Error( 'prime_stories_invalid_nonce', __( 'Invalid request.', 'prime-stories' ), array( 'status' => 403 ) );
	}

	/**
	 * Validate a story ID.
	 *
	 * @param mixed $story_id Story ID.
	 * @return bool
	 */
	public function validate_story_id( $story_id ) {
		$story_id = absint( $story_id );

		return $story_id > 0 && prime_stories_is_story_visible( $story_id );
	}

	/**
	 * Validate an analytics event type.
	 *
	 * @param mixed $event_type Event type.
	 * @return bool
	 */
	public function validate_event_type( $event_type ) {
		return in_array( sanitize_key( (string) $event_type ), array( 'impression', 'open', 'complete', 'click', 'reaction', 'reply' ), true );
	}

	/**
	 * Verify that a slide belongs to the story payload.
	 *
	 * @param int    $story_id Story ID.
	 * @param string $slide_id Slide ID.
	 * @return bool
	 */
	private function validate_slide_id( $story_id, $slide_id ) {
		$payload = prime_stories_get_story_payload( absint( $story_id ) );
		$slide_id = sanitize_key( (string) $slide_id );

		if ( empty( $payload['slides'] ) || ! is_array( $payload['slides'] ) ) {
			return false;
		}

		foreach ( $payload['slides'] as $slide ) {
			if ( ! empty( $slide['id'] ) && sanitize_key( (string) $slide['id'] ) === $slide_id ) {
				return true;
			}

			if ( ! empty( $slide['slide_id'] ) && sanitize_key( (string) $slide['slide_id'] ) === $slide_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Verify frontend REST nonces.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	private function verify_nonce( WP_REST_Request $request ) {
		$public_nonce = $request->get_header( 'x-prime-stories-nonce' );
		$rest_nonce   = $request->get_header( 'x-wp-nonce' );

		if ( $public_nonce && wp_verify_nonce( $public_nonce, 'prime_stories_public' ) ) {
			return true;
		}

		return (bool) ( $rest_nonce && wp_verify_nonce( $rest_nonce, 'wp_rest' ) );
	}

	/**
	 * Rate-limit repeated client log events.
	 *
	 * @param string $message Message.
	 * @param string $source Source identifier.
	 * @param string $session_id Session ID.
	 * @return bool
	 */
	private function is_client_log_rate_limited( $message, $source, $session_id ) {
		$fingerprint = md5( sanitize_text_field( $message ) . '|' . sanitize_text_field( $source ) . '|' . sanitize_text_field( $session_id ) );
		$key         = 'prime_stories_log_' . $fingerprint;

		if ( get_transient( $key ) ) {
			return true;
		}

		set_transient( $key, 1, MINUTE_IN_SECONDS );

		return false;
	}

	/**
	 * Build a lightweight request fingerprint for rate limiting.
	 *
	 * @return string
	 */
	private function get_request_fingerprint() {
		$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return md5( $ip . '|' . $user_agent );
	}
}
