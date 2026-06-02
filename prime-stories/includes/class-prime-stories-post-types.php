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
}
