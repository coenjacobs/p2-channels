<?php

class P2_Channels_Widget extends WP_Widget {
	/**
	 * Fire a new WP_Widget
	 * @uses WP_Widget
	 */
	function __construct() {
		$args = array(
			'classname' => 'p2_channels_list',
			'description' => __( 'Widget showing the channels a user can see.', 'p2-channels' ),
		);

		$this->WP_Widget( 'p2-channels', __( 'P2 Channels Widget', 'p2-channels' ), $args );
	}

	/**
	 * Fire a new WP_Widget
	 * @var array $args contains the settings of the widget
	 * @var array $instance contains the current instance of the widget
	 * @uses $p2_channels->get_allowed_channels()
	 * @uses get_term_link()
	 */
	function widget( $args, $instance ) {
		global $p2_channels;
		$channels = $p2_channels->get_allowed_channels();

		if ( ! empty( $channels ) ) {
			extract( $args, EXTR_SKIP );
			echo $before_widget;
			
			$title = ( isset( $instance[ 'title' ] ) ) ? esc_attr( $instance['title'] ) : '';
			
			if ( ! empty( $title ) )
				echo $before_title . $title . $after_title;

			echo '<ul>';

			foreach ( $channels as $channel ) {
				$link = get_term_link( $channel->slug, 'p2_channel' );
				echo '<li><a href="' . $link . '">' . $channel->name . '</a></li>';
			}

			echo '</ul>';

			echo $after_widget;
		}
	}

	/**
	 * Update the settings of the widget instance
	 * @var array $new_instance contains current state of the widget
	 * @var array $old_instance contains previous state of the widget
	 */
	function update( $new_instance, $old_instance ) {
		$this->title = esc_attr( $instance[ 'title' ] );
		
		$updated_instance = $new_instance;
		
		return $updated_instance;
	}

	/**
	 * The settings form of the widget instance
	 * @var array $instance contains current state of the widget
	 * @uses $this->get_field_id()
	 */
	function form( $instance ) {
		$title = ( isset( $instance[ 'title' ] ) ) ? esc_attr( $instance['title'] ) : '';
		
		echo '<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'p2-channels' );
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $title . '" /></label>';
	}
}

?>