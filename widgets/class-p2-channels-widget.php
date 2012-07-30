<?php

class P2_Channels_Widget extends WP_Widget {
	function __construct() {
		$args = array(
			'classname' => 'p2_channels_list',
			'description' => __( 'Widget showing the channels a user can see.', 'p2-channels' ),
		);

		$this->WP_Widget( 'p2-channels', __( 'P2 Channels Widget', 'p2-channels' ), $args );
	}

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

	function update( $new_instance, $old_instance ) {
		$this->title = esc_attr( $instance[ 'title' ] );
		
		$updated_instance = $new_instance;
		
		return $updated_instance;
	}

	function form( $instance ) {
		$title = ( isset( $instance[ 'title' ] ) ) ? esc_attr( $instance['title'] ) : '';
		
		echo '<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'p2-channels' );
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $title . '" /></label>';
	}
}

?>