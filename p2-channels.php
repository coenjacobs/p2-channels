<?php

/**
 * Plugin Name: P2 Channels
 * Author: Coen Jacobs
 * Author URI: http://coenjacobs.me/
 */

class P2_Channels {
	private $temp_add_channels;

	public function __construct() {
		load_plugin_textdomain( 'p2-channels', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . 'languages/' );

		add_action( 'init', array( &$this, 'register_taxonomy' ), 10, 0 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		add_action( 'p2_post_form', array( &$this, 'post_form' ), 10, 0 );
		add_action( 'p2_action_links', array( &$this, 'channels_display' ), 10, 0 );
		add_action( 'p2_ajax', array( &$this, 'handle_ajax_calls' ), 10, 1 );
	}

	/**
	 * Called on init to register the taxonomy which will be used for the channels
	 * @uses register_taxonomy()
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'                       => __( 'Channels', 'p2-channels' ),
			'singular_name'              => __( 'Channel', 'p2-channels' ),
			'search_items'               => __( 'Search Channels', 'p2-channels' ),
			'popular_items'              => __( 'Popular Channels', 'p2-channels' ),
			'all_items'                  => __( 'All Channels', 'p2-channels' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Channel', 'p2-channels' ), 
			'update_item'                => __( 'Update Channel', 'p2-channels' ),
			'add_new_item'               => __( 'Add New Channel', 'p2-channels' ),
			'new_item_name'              => __( 'New Channel Name', 'p2-channels' ),
			'separate_items_with_commas' => __( 'Separate channels with commas', 'p2-channels' ),
			'add_or_remove_items'        => __( 'Add or remove channels', 'p2-channels' ),
			'choose_from_most_used'      => __( 'Choose from the most used channels', 'p2-channels' ),
			'menu_name'                  => __( 'Channels', 'p2-channels' ),
		); 

		register_taxonomy( 'p2_channel', 'post', array(
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'channel' ),
		) );
	}

	/**
	 * Load required scripts for frontend
	 * @uses wp_enqueue_script()
	 * @uses plugins_url()
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'p2-channels', plugins_url( '/js/p2-channels.js', __FILE__), array( 'jquery' ), '1.0-beta1', false );
	}

	/**
	 * Shows the channels a post can be added to on the post form by checkboxes
	 * @uses $this->get_allowed_channels()
	 */
	public function post_form() {
		$terms = $this->get_allowed_channels();

		echo '<div id="p2_channels_terms" style="padding-top: 1em;">';

			echo '<div style="float: left;"><strong>' . __( 'Channels:', 'p2-channels' ) . '</strong></div>';

			foreach ( $terms as $term ) {
				echo '<label style="padding-left: 1em; font-size: 1em;" for="p2_channels_term-' . $term->slug . '">';
				echo '<input type="checkbox" id="p2_channels_term-' . $term->slug . '" value="' . $term->slug . '" class="p2_channels_term" name="p2_channels_terms[]">';
				echo ' ' . $term->name . '</label>';
			}

		echo '</div>';
	}

	/**
	 * Returns the channels a user is allowed to post in
	 * TODO: Make it check the user to see what channels to return
	 * @uses get_terms()
	 */
	private function get_allowed_channels() {
		return get_terms( 'p2_channel', array( 'hide_empty' => false ) );
	}

	/**
	 * Returns all channels to be able to filter them from the post
	 * @uses get_terms()
	 */
	private function get_all_channels() {
		return get_terms( 'p2_channel', array( 'hide_empty' => false ) );
	}

	/**
	 * Shows the channels a post is added to next to the edit link
	 * @uses wp_get_post_terms()
	 * @uses get_term_link()
	 */
	public function channels_display() {
		global $post;
		$terms = wp_get_post_terms( $post->ID, 'p2_channel' );
		
		if ( ! empty( $terms ) ) {
			$p2_channels_string = '';

			foreach ( $terms as $term ) {
				$link = get_term_link( $term, 'p2_channel' );
				$p2_channels_string .= '<a href="' . $link . '">' . $term->name . '</a>, ';
			}

			$p2_channels_string = rtrim( trim( $p2_channels_string ), ',' );

			echo ' | ' . __( 'Channels:', 'p2-channels' ) . ' '. $p2_channels_string;
		}
	}

	/**
	 * Handles all the P2 Ajax calls we want to hook into
	 * @var string $action contains the ajax action being performed
	 */
	public function handle_ajax_calls( $action ) {
		if ( method_exists( &$this, 'do_ajax_' . $action ) ) {
    		call_user_func( array( &$this, 'do_ajax_' . $action ) );
    	}
	}

	/**
	 * Add an action to save the terms if there are any posted terms (via checkboxes on frontend)
	 * @uses wp_list_pluck()
	 * @uses $this->get_allowed_channels()
	 * @uses $this->get_all_channels()
	 * @uses $this->temp_add_channels
	 * @uses add_action()
	 */
	private function do_ajax_new_post() {    
        $tags = $_POST['tags'];
        $tags = is_array( $tags ) ? $tags : explode( ',', trim( $tags, " \n\t\r\0\x0B," ) );

        $all_channel_slugs = wp_list_pluck( $this->get_all_channels(), 'slug' );
        $allowed_channel_slugs = wp_list_pluck( $this->get_allowed_channels(), 'slug' );

        $matches = array_intersect( $tags, $all_channel_slugs );

		if ( ! empty( $matches ) ) {
			foreach ( $matches as $match ) {
				array_push( $matches, $match );
				unset( $tags[ array_search( $match, $tags ) ] );
			}

			// Temporary save the channel slugs (the ones this user is allowed to use) for the next filter
			$this->temp_add_channels = array_intersect( $matches, $allowed_channel_slugs );

			$_POST['tags'] = implode( ',', $tags );
			add_action( 'wp_insert_post', array( &$this, 'save_channels' ), 10, 1 );
		}
	}

	/**
	 * Actually add the terms (channels) to the newly created post
	 * @var int $post_id the id of the newly created post
	 * @uses $this->temp_add_channels
	 * @uses get_term_by()
	 * @uses wp_set_post_terms()
	 * @uses remove_action()
	 */
	public function save_channels( $post_id ) {
		if ( ! empty( $this->temp_add_channels ) ) {
			$term_ids = array();

			foreach ( $this->temp_add_channels as $slug ) {
				$term = get_term_by( 'slug', $slug, 'p2_channel', OBJECT );
				array_push( $term_ids, $term->term_id );
			}

			wp_set_post_terms( $post_id, $term_ids, 'p2_channel' );

			$this->temp_add_channels = NULL;
		}

		// Only process this function once, we don't want to add the channels again until a new set is posted
		remove_action( 'wp_insert_post', array( &$this, 'save_channels' ), 10, 1 );
	}
}

// Let's go!
global $p2_channels;
$p2_channels = new P2_Channels();

?>