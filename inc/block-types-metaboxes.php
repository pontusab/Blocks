<?php

/**
 * Register metaboxes
 * On wanted pages 
 * @since 0.1
 * @return The Block metabox
 */

function blocks_register_metaboxes() {
		
	$all_types = blocks_post_types();
	$settings  = get_option( 'blocks' );
	$types = null;

	if ( isset( $settings['exclude'] ) )
	{
		$types = array_diff( $all_types, $settings['exclude'] );
	}

	if( $types ) 
	{
		foreach( $types as $type ) {
			add_meta_box( 'blocks_save', __('Your Areas', 'blocks'), 'blocks_save_blocks', $type, 'normal', 'high' );
		}
	}
}
add_action('add_meta_boxes', 'blocks_register_metaboxes');


/**
 * List all the blocks
 * @since 0.1
 * @param string $post
 * @return the Block-saving-areas and available Blocks 
 */

function blocks_save_blocks( $post ) {

	$settings = get_option('blocks');

	$areas = $settings['areas'];

	$postType = get_post_type( $post->ID );
	$templateFile = '';
	
	if ($postType == 'page')
	{
		$template = blocks_get_template_by_page_id( $post->ID );

		$templateFile = $template;
	}
	else
	{
		$templateFile = blocks_get_template_by_type( $postType ) ;
	}

	$defined_areas = blocks_find_areas( $templateFile );

	// Hook into blocks_types_metabox
	// If you want to modify the output of metaboxes
	do_action('blocks_types_metabox');

	if( $defined_areas )
	{
		$output = '';
		$blocks_id = array();

		// Output the hidden divs for each area defined on page
		foreach ( $areas as $areaKey => $area )
		{
			// Get the Blocks id:s
			$block_area_ids = get_post_meta( $post->ID, '_blocks_areas[' . $areaKey . ']', true );

			if( in_array( $areaKey, $defined_areas ) )
			{
				$data = '';

				if( isset($block_area_ids) && !empty($block_area_ids) )
				{
					$data = join(',', $block_area_ids);
					$blocks_id[$areaKey] = $block_area_ids;
				}

				$output .= '<input type="hidden" class="tags-input" name="blocks[' . $areaKey . '][data]" value="' . $data . '" />';
				//$output .= '<input type="hidden" class="tags-input" name="blocks[' . $areaKey . '][order]" value="' . $data . '" />';
			}
		}

		$output .= '<div class="header">';
			$output .= '<span>'. __('Blocks', 'blocks'). '</span>';
			$output .='<input class="search" placeholder="' . __('Search blocks','blocks') . '.." />';
		$output .= '</div>';

		$args = array(
			'post_type'		 =>	'blocks',
			'posts_per_page' => '-1'
		);

		$blocks = get_posts( $args );

		$output .='<div class="inner-list"><ul class="list">';

			foreach( $blocks as $block )
			{
					$excerpt  = wp_trim_words( $block->post_content, '10', false );

					$output .= '<li data-id="' . $block->ID . '" class="block"><span title="'. __('Remove Block', 'block') .'" class="remove-block">x</span>';
						$output .= '<div class="block-title">' . ( get_the_post_thumbnail( $block->ID ) || ! empty( $excerpt ) ? '<div class="sidebar-name-arrow"><br></div>' : '') .'';
							$output .= $block->post_title;
						$output .= '</div>';

						if( ! empty( $excerpt ) || get_the_post_thumbnail( $block->ID ) ) {
							$output .= '<div class="block-info">';

								if( get_the_post_thumbnail( $block->ID, 'medium' ) ) 
								{
									$output .= get_the_post_thumbnail( $block->ID, 'medium' );
								}	

								if( ! empty( $excerpt ) )
								{
									$output .= '<div class="block-excerpt">';

									 $output .= '<p>' . $excerpt . '</p>';

									$output .= '</div>';
								}
						
							$output .= '</div>';
						}

					$output .= '</li>';

			}

		$output .= '</ul></div>';	

		$output .= '<div class="paging-holder"><ul class="paging"></ul></div>';

		$output .= '<div class="blocks-wrap">';

			foreach ( $areas as $areaKey => $area ) 
			{
				if( in_array( $areaKey, $defined_areas ) ) 
				{
					$output .= '<div class="blocks-holder">';

						$output .= '<div class="blocks-title">';
							$output .= '<div class="blocks-inner">';
								$output .= '<h2>'. $area['name'] .'</h2>';
								$output .= '<p class="description">' . $area['desc'] . '</p>';
							$output .= '</div>';
						$output .= '</div>';
						
						$output .= '<ul class="blocks-area blocks-' . $areaKey . '" data-area="' . $areaKey . '">';

							if( ! empty( $blocks_id[ $areaKey ] ) ) {
								$args = array(
									'post_type'		 =>	'blocks',
									'posts_per_page' => '-1',
									'orderby'		 => 'post__in',
									'post__in'		 => $blocks_id[ $areaKey ]
								);

								$query = get_posts( $args );

								foreach ( $query as $post ) 
								{
									$block_id = $post->ID;

									$excerpt  = wp_trim_words( $block->post_content, '10', false );

									$output .= '<li data-id="' . $block_id . '" class="block"><span title="'. __('Remove Block', 'block') .'" class="remove-block">x</span>';
										$output .= '<div class="block-title">';
											$output .= get_the_title($post->ID);
										$output .= '</div>';

										if( ! empty( $excerpt ) ) {
											$output .= '<div class="block-info">';

												$output .= '<div class="block-excerpt">';

												 $output .= '<p>' . $excerpt . '</p>';

												$output .= '</div>';
										
											$output .= '</div>';
										}

									$output .= '</li>';
								}
							}

						$output .= '</ul>';
					$output .= '</div>';
				}
			}	

		$output .= '</div>';

		echo $output;
	}
}
