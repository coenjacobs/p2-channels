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
		wp_enqueue_script( 'p2-channels', plugins_url( '/js/p2-channels.js', __FILE__), array( 'jquery' ), '1.0b1', false );
	}

	/**
	 * Shows the channels a post can be added to on the post form by checkboxes
	 * @uses $this->get_available_channels()
	 */
	public function post_form() {
		$terms = $this->get_available_channels();

		echo '<div id="p2_channels_terms" style="padding-top: 1em;">';

			echo '<div style="float: left;"><strong>' . __( 'Channels:', 'p2-channels' ) . '</strong></div>';

			foreach ( $terms as $term ) {
				echo '<label style="padding-left: 1em; font-size: 1em;" for="p2_channels_term-' . $term->term_id . '">';
				echo '<input type="checkbox" id="p2_channels_term-' . $term->term_id . '" value="' . $term->term_id . '" class="p2_channels_term" name="p2_channels_terms[]">';
				echo ' ' . $term->name . '</label>';
			}

		echo '</div>';
	}

	/**
	 * Returns the channels a user is allowed to post in
	 * TODO: Make it check the user to see what channels to return
	 * @uses wp_get_post_terms()
	 */
	private function get_available_channels() {
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
	 * @uses $this->temp_add_channels
	 * @uses add_action()
	 */
	private function do_ajax_p2_add_channels() {
		if ( isset( $_POST['channels'] ) ) {
			// Temporary save the channel ids as the actual submit call will not contain them
			// TODO: P2 should provide a way to add custom values in the new_post Ajax call
			$this->temp_add_channels = explode( ',', $_POST['channels'] );

			add_action( 'wp_insert_post', array( &$this, 'save_channels' ), 10, 1 );
		}
	}

	/**
	 * Actually add the terms (channels) to the newly created post
	 * @var int $post_id the id of the newly created post
	 * @uses $this->temp_add_channels
	 * @uses get_term()
	 * @uses wp_set_post_terms()
	 * @uses remove_action()
	 */
	public function save_channels( $post_id ) {
		if ( ! empty( $this->temp_add_channels ) ) {
			$p2_channels_terms = array();

			foreach ( $this->temp_add_channels as $term ) {
				$term_object = get_term( $term, 'p2_channel' );
				array_push( $p2_channels_terms, $term_object->slug );
			}

			wp_set_post_terms( $post_id, $p2_channels_terms, 'p2_channel' );

			$this->temp_add_channels = NULL;
		}

		// Only process this function once, we don't want to add the terms again until a new set is posted
		remove_action( 'wp_insert_post', array( &$this, 'save_channels' ), 10, 1 );
	}
}

// Let's go!
global $p2_channels;
$p2_channels = new P2_Channels();

?>