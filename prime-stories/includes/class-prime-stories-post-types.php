<?php
/**
 * Register post types and taxonomies.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;

/**
 * Story post type registration.
 */
class Prime_Stories_Post_Types {

	/**
	 * Singleton instance.
	 *
	 * @var Prime_Stories_Post_Types|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Prime_Stories_Post_Types
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
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'prime_story_group_add_form_fields', array( $this, 'render_group_add_fields' ) );
		add_action( 'prime_story_group_edit_form_fields', array( $this, 'render_group_edit_fields' ) );
		add_action( 'created_prime_story_group', array( $this, 'save_group_meta' ) );
		add_action( 'edited_prime_story_group', array( $this, 'save_group_meta' ) );
		add_filter( 'manage_edit-prime_story_group_columns', array( $this, 'add_group_columns' ) );
		add_filter( 'manage_prime_story_group_custom_column', array( $this, 'render_group_column' ), 10, 3 );
	}

	/**
	 * Register the story post type and groups taxonomy.
	 *
	 * @return void
	 */
	public function register() {
		$this->register_post_type();
		$this->register_taxonomy();
	}

	/**
	 * Register prime_story.
	 *
	 * @return void
	 */
	private function register_post_type() {
		$labels = array(
			'name'                  => __( 'Stories', 'prime-stories' ),
			'singular_name'         => __( 'Story', 'prime-stories' ),
			'menu_name'             => __( 'Stories', 'prime-stories' ),
			'name_admin_bar'        => __( 'Story', 'prime-stories' ),
			'add_new'               => __( 'Add New', 'prime-stories' ),
			'add_new_item'          => __( 'Add New Story', 'prime-stories' ),
			'new_item'              => __( 'New Story', 'prime-stories' ),
			'edit_item'             => __( 'Edit Story', 'prime-stories' ),
			'view_item'             => __( 'View Story', 'prime-stories' ),
			'all_items'             => __( 'All Stories', 'prime-stories' ),
			'search_items'          => __( 'Search Stories', 'prime-stories' ),
			'not_found'             => __( 'No stories found.', 'prime-stories' ),
			'not_found_in_trash'    => __( 'No stories found in Trash.', 'prime-stories' ),
			'featured_image'        => __( 'Featured Image', 'prime-stories' ),
			'set_featured_image'    => __( 'Set featured image', 'prime-stories' ),
			'remove_featured_image' => __( 'Remove featured image', 'prime-stories' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'map_meta_cap'        => true,
			'menu_icon'           => 'dashicons-images-alt2',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'thumbnail' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
		);

		register_post_type( 'prime_story', $args );
	}

	/**
	 * Register prime_story_group.
	 *
	 * @return void
	 */
	private function register_taxonomy() {
		$labels = array(
			'name'              => __( 'Story Groups', 'prime-stories' ),
			'singular_name'     => __( 'Story Group', 'prime-stories' ),
			'search_items'      => __( 'Search Story Groups', 'prime-stories' ),
			'all_items'         => __( 'All Story Groups', 'prime-stories' ),
			'edit_item'         => __( 'Edit Story Group', 'prime-stories' ),
			'update_item'       => __( 'Update Story Group', 'prime-stories' ),
			'add_new_item'      => __( 'Add New Story Group', 'prime-stories' ),
			'new_item_name'     => __( 'New Story Group Name', 'prime-stories' ),
			'menu_name'         => __( 'Story Groups', 'prime-stories' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_tagcloud'     => false,
			'hierarchical'      => true,
			'rewrite'           => false,
		);

		register_taxonomy( 'prime_story_group', array( 'prime_story' ), $args );
	}

	/**
	 * Render extra fields when adding a story group.
	 *
	 * @return void
	 */
	public function render_group_add_fields() {
		?>
		<div class="form-field term-prime-stories-avatar-wrap">
			<label for="prime_stories_group_avatar_id"><?php esc_html_e( 'Highlight avatar', 'prime-stories' ); ?></label>
			<input type="hidden" id="prime_stories_group_avatar_id" name="prime_stories_group_avatar_id" value="">
			<div class="prime-stories-media-controls">
				<button type="button" class="button prime-stories-media-select" data-target="prime_stories_group_avatar_id" data-library-type="image"><?php esc_html_e( 'Choose image', 'prime-stories' ); ?></button>
				<button type="button" class="button-link-delete prime-stories-media-remove" data-target="prime_stories_group_avatar_id"><?php esc_html_e( 'Remove', 'prime-stories' ); ?></button>
			</div>
			<div class="prime-stories-media-preview"><span><?php esc_html_e( 'No media selected', 'prime-stories' ); ?></span></div>
		</div>
		<div class="form-field">
			<label for="prime_stories_group_order"><?php esc_html_e( 'Sort order', 'prime-stories' ); ?></label>
			<input type="number" id="prime_stories_group_order" name="prime_stories_group_order" value="0">
		</div>
		<div class="form-field">
			<label>
				<input type="checkbox" name="prime_stories_group_active" value="yes" checked>
				<?php esc_html_e( 'Active highlight', 'prime-stories' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Render extra fields when editing a story group.
	 *
	 * @param WP_Term $term Term.
	 * @return void
	 */
	public function render_group_edit_fields( $term ) {
		$avatar_id = absint( get_term_meta( $term->term_id, 'prime_stories_group_avatar_id', true ) );
		$order     = (int) get_term_meta( $term->term_id, 'prime_stories_group_order', true );
		$active    = get_term_meta( $term->term_id, 'prime_stories_group_active', true );
		$active    = '' === $active ? 'yes' : $active;
		$preview   = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
		?>
		<tr class="form-field">
			<th scope="row"><label for="prime_stories_group_avatar_id"><?php esc_html_e( 'Highlight avatar', 'prime-stories' ); ?></label></th>
			<td>
				<input type="hidden" id="prime_stories_group_avatar_id" name="prime_stories_group_avatar_id" value="<?php echo esc_attr( (string) $avatar_id ); ?>">
				<div class="prime-stories-media-controls">
					<button type="button" class="button prime-stories-media-select" data-target="prime_stories_group_avatar_id" data-library-type="image"><?php esc_html_e( 'Choose image', 'prime-stories' ); ?></button>
					<button type="button" class="button-link-delete prime-stories-media-remove" data-target="prime_stories_group_avatar_id"><?php esc_html_e( 'Remove', 'prime-stories' ); ?></button>
				</div>
				<div class="prime-stories-media-preview">
					<?php if ( $preview ) : ?>
						<img src="<?php echo esc_url( $preview ); ?>" alt="">
					<?php else : ?>
						<span><?php esc_html_e( 'No media selected', 'prime-stories' ); ?></span>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="prime_stories_group_order"><?php esc_html_e( 'Sort order', 'prime-stories' ); ?></label></th>
			<td><input type="number" id="prime_stories_group_order" name="prime_stories_group_order" value="<?php echo esc_attr( (string) $order ); ?>"></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Status', 'prime-stories' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="prime_stories_group_active" value="yes" <?php checked( 'yes', $active ); ?>>
					<?php esc_html_e( 'Active highlight', 'prime-stories' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save story group metadata.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_group_meta( $term_id ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$avatar_id = isset( $_POST['prime_stories_group_avatar_id'] ) ? absint( $_POST['prime_stories_group_avatar_id'] ) : 0;
		$order     = isset( $_POST['prime_stories_group_order'] ) ? (int) $_POST['prime_stories_group_order'] : 0;
		$active    = isset( $_POST['prime_stories_group_active'] ) ? 'yes' : 'no';

		update_term_meta( $term_id, 'prime_stories_group_avatar_id', $avatar_id );
		update_term_meta( $term_id, 'prime_stories_group_order', $order );
		update_term_meta( $term_id, 'prime_stories_group_active', $active );
	}

	/**
	 * Add taxonomy list columns.
	 *
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public function add_group_columns( $columns ) {
		$columns['prime_stories_avatar'] = __( 'Avatar', 'prime-stories' );
		$columns['prime_stories_order']  = __( 'Order', 'prime-stories' );
		$columns['prime_stories_active'] = __( 'Active', 'prime-stories' );

		return $columns;
	}

	/**
	 * Render taxonomy list column.
	 *
	 * @param string $content Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id Term ID.
	 * @return string
	 */
	public function render_group_column( $content, $column_name, $term_id ) {
		if ( 'prime_stories_avatar' === $column_name ) {
			$avatar_id = absint( get_term_meta( $term_id, 'prime_stories_group_avatar_id', true ) );
			$url       = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';

			return $url ? '<img class="prime-stories-group-column-avatar" src="' . esc_url( $url ) . '" alt="">' : '&mdash;';
		}

		if ( 'prime_stories_order' === $column_name ) {
			return esc_html( (string) (int) get_term_meta( $term_id, 'prime_stories_group_order', true ) );
		}

		if ( 'prime_stories_active' === $column_name ) {
			return 'no' === get_term_meta( $term_id, 'prime_stories_group_active', true ) ? esc_html__( 'Inactive', 'prime-stories' ) : esc_html__( 'Active', 'prime-stories' );
		}

		return $content;
	}
}
