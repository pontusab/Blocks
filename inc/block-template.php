<?php

/**
 * This is the default block-template
 * If you want to override the output
 * you can add a file called 
 * blocks-{templatename}.php to your
 * theme-root and in that file add:
 *
 * Block Template: My block template
 *
 * @since 0.1
 * @param string $area, get the block in this area
 * @param string $id, for shortcode to get a single block
 * @return the block layout and content
 */

function get_blocks( $area = null, $id = null, $class = null ) {
	global $post;

	// Get all the settings
	$settings = get_option( 'blocks' );

	$blocks_id = array();

	foreach ($settings['areas'] as $definedAreaKey => $definedArea)
	{
		// Get the blocks id:s
		$blocks_id[$definedAreaKey] = get_post_meta( $post->ID, '_blocks_areas[' . $definedAreaKey . ']', true );
	}

	if( (isset( $area ) && !empty( $blocks_id[$area] ) && count( $blocks_id[$area] ) > 0) || isset( $id ) )
	{
		$output = '';

		$args = array(
			'post_type' 	 => 'blocks',
			'posts_per_page' => '-1',
			'orderby'		 => 'post__in',
		);

		if( !$area )
		{
			$args['p'] = $id;
		}
		else
		{
			$args['post__in'] = $blocks_id[$area];
		}

		$blocks = get_posts( $args );
		
		foreach ( $blocks as $block ) 
		{
			$blocks_link   = get_post_meta( $block->ID, '_blocks_link', true );
			$blocks_templ  = get_post_meta( $block->ID, '_blocks_template', true );

			$template 	   = isset( $blocks_templ ) ? $blocks_templ : false; 

			if( $template && file_exists( TEMPLATEPATH . '/blocks/' . $blocks_templ ) ) 
			{
				include( TEMPLATEPATH . '/blocks/' . $template );
			}
			elseif( file_exists( TEMPLATEPATH . '/blocks/default.php' ) ) 
			{
				include( TEMPLATEPATH . '/blocks/default.php' );
			}
			else
			{
				// Hook into blocks_template if you want to modify the template output
				do_action('blocks_template');

				$output .= '<div class="block'. ( ! empty( $settings['class'] ) ? $settings['class'] : '' ) .''. ( ! empty( $blocks_meta['class'] ) ? $blocks_meta['class'] : '' ) .'">';
					
					$output .= '<div class="block-holder">';

						if( has_post_thumbnail( $block->ID ) ) {
							$output .= '<div class="block-img">';
								$output .= get_the_post_thumbnail( $block->ID, 'medium' );
							$output .= '</div>';
						}

						if( ! empty( $blocks_link ) ) {
							$output .= '<a href="'. $blocks_link .'" title="'. esc_attr( get_the_title( $block->ID ) ) .'">';
							$output .= get_the_title( $block->ID );
							$output .= '</a>';
						} else {
							$output .= '<div class="block-title">';
								$output .= get_the_title( $block->ID );
							$output .= '</div>';
						}

						$output .= '<div class="block-content">';
							$output .= apply_filters( 'the_content', $block->post_content );
						$output .= '</div>';

					$output .= '</div>';

					if( is_user_logged_in() && isset( $settings['edit'] ) ) {
						$output .= '<a href="'. get_edit_post_link( $block->ID ) .'" class="block-edit-link" title="'. __('Edit block', 'blocks') .'">'. __( 'Edit', 'blocks' ) .'</a>';
					}

				$output .= '</div>';

				echo $output;
			}
		}
	}
}
