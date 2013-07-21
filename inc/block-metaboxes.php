<?php

/**
 * Register the metaboxes
 * @since 0.1
 * @return Block metaboxes
 */

function blocks_metadata() {
	add_meta_box( 'blocks_single_settings', __('Block Settings', 'blocks'), 'blocks_single_settings', 'blocks', 'side', 'low' );
	add_meta_box( 'blocks_single_pages', __('Add block', 'blocks'), 'blocks_single_pages', 'blocks', 'normal' );
}
add_action('add_meta_boxes', 'blocks_metadata');


/**
 * Register the metaboxes
 * @since 0.1
 * @return list of all pages and areas
 */

function blocks_single_pages() 
{
	global $post, $wpdb;

	$settings 	   = get_option( 'blocks' );
	$defined_areas = $settings['areas'];
	$types 	       = blocks_post_types();
	$postobject    = $post; // Make a clone of the Post-Object
	
	if( isset( $settings['exclude'] ) && count( $settings['exclude'] ) > 0 )
	{
		$types = array_diff( $types, $settings['exclude'] );
	}

	// Get all post_id:s that have this post_id(block_id) and area
	// Remove when WP_Query adds support for REGEX in meta_query
	// http://core.trac.wordpress.org/ticket/18736
	$sql = 'SELECT post_id, meta_key FROM '. $wpdb->prefix .'postmeta WHERE meta_key REGEXP \'_blocks_areas[[[:alpha:]]+]\' AND meta_value REGEXP \'a:[[:digit:]]+:{[^}]*"'. $postobject->ID .'"\'';

	// Get the SQL-result
	$results = $wpdb->get_results( $sql, ARRAY_A );

	$post_ids       = array();
	$areas_on_page  = array();

	foreach ( $results as $result )
	{
		$post_ids[] = $result['post_id'];
		
		if ( preg_match( '/^_blocks_areas\[([^\]]+)\]$/', $result['meta_key'], $area_match ) )
		{

			if (!isset($areas_on_page[$result['post_id']]))
			{
				$areas_on_page[$result['post_id']] = array();
			}

			$areas_on_page[$result['post_id']][] = $area_match[1];
		}
	}

	$pages = get_posts( array(
	    'post_type' 	 => $types,
	    'post__not_in'   => $post_ids,
		'posts_per_page' => -1
	));

	foreach( $pages as $page ) 
	{
		$pages_and_areas[$page->ID]   = blocks_get_defined_areas_by_post_id( $page->ID );
	}


	$output = '<div id="blocks-area-control">';

		$output .= '<div class="blocks-tabs">';

			$output .= '<div class="search-head">';
				$output .= '<div class="inner">';
					$output .= '<input class="block-pages-search" type="text" placeholder="' . __('Search page', 'blocks') . '...">';
				$output .= '</div>';
			$output .= '</div>';

			$output .= '<div class="posts-holder">';

				$output .= '<ul class="list list-pages" data-action="update">';
					foreach( $pages_and_areas as $page_id => $areas ) 
					{

						if( count( $areas ) > 0 )
						{
							$title = get_the_title( $page_id );
							$output .= '<li class="block" data-id="'. $page_id .'" data-area="left"><p>'.( !empty( $title ) ? $title : __( 'No title', 'blocks' ) ).'</p><span title="Add this page" class="add"></span><span title="'. __( 'Add on areas', 'blocks' ) .'" class="add-areas">'. __( 'Add on areas', 'blocks' ) .'</span><span title="'. __( 'Remove', 'blocks ') .'" class="delete"></span>';
								
								$output .= '<ul class="areas">';

									foreach( $areas as $area ) 
									{
										$output .= '<span></span>';
										$output .= '<li data-area="' . $area . '" title="Add on '. $defined_areas[$area]['name'] .'"><span></span>'. $defined_areas[$area]['name'] .'</li>';
									}

								$output .= '</ul>';

							$output .= '</li>';
						}
					}

				$output .= '</ul>';

			$output .= '</div>';


			$saved_pages = array();

			if( count( $post_ids ) > 0 )
			{
				$saved_pages = get_posts( array(
				    'post_type' 	 => $types,
				    'post__in'   	 => $post_ids,
					'posts_per_page' => -1
				));

				foreach( $saved_pages as $saved ) 
				{
					$template = blocks_get_template_by_page_id( $saved->ID );
					$saved_pages['areas'][$saved->ID] = blocks_find_areas( $template );
				}
			}


			// Saved block area
			$output .= '<div class="posts-holder">';
				$output .= '<ul class="list save-block" data-action="delete">';

					if( !empty( $saved_pages ) )
					{
						foreach( $saved_pages['areas'] as $save_id => $areas ) 
						{	
							if( count( $areas ) > 0 )
							{
								$output .= '<li class="block" data-id="'. $save_id .'"><p>'. get_the_title( $save_id ) .'</p><span title="'. __( 'Add this', 'blocks ') .'" class="add"></span><span title="'. __( 'Add on areas', 'blocks' ) .'" class="add-areas">'. __( 'Add on areas', 'blocks' ) .'</span><span title="'. __( 'Remove', 'blocks ') .'" class="delete"></span>';
									
									$output .= '<ul class="areas">';

										foreach( $areas as $area ) 
										{
											$output .= '<span></span>';
											$output .= '<li data-area="' . $area . '" title="Add on '. $defined_areas[$area]['name'] .'">';
									
											if( isset($areas_on_page[$save_id]) && in_array($area, $areas_on_page[$save_id]) )
											{
												$output .= '<span class="saved"></span>';
											}
											else 
											{
												$output .= '<span></span>';
											}

											$output .= $defined_areas[$area]['name'];
											$output .= '</li>';
										}

									$output .= '</ul>';

								$output .= '</li>';
							}
						}
					}

				$output .= '</ul>';
			$output .= '</div>';

		$output .= '</div>';

	$output .= '</div>';

	echo $output;				

}

/**
 * Save a block on a certain page and area
 * @since 0.1
 * @return Success on success
 */

function blocks_save_post_on_block()
{
	if ( !isset($_POST['nounce']) || !wp_verify_nonce( $_POST['nounce'], 'Blocks' ) ) 
	{
		die( 'Nounce failed' );
	}

	if( !isset($_POST['post_id']) || !isset($_POST['block_id']) || !isset($_POST['area']) )
	{
		die( 'Malformed data' );
	}

	$post_id   = $_POST['post_id'];
	$block_id  = $_POST['block_id'];
	$area      = $_POST['area'];
	$remove    = isset($_POST['remove']) && $_POST['remove'] == 'true';

	$template_areas = blocks_get_defined_areas_by_post_id($post_id);

	foreach ($template_areas as $template_area)
	{
		if( $area == $template_area )
		{
			$meta_key = '_blocks_areas[' . $template_area . ']';

			$defined_blocks = get_post_meta( $post_id, $meta_key, true );

			if( !isset($defined_blocks) )
			{
				$defined_blocks = array();
			}

			if( $remove )
			{
				$key = array_search( $block_id, $defined_blocks );

				if( $key !== false )
				{
				    unset( $defined_blocks[$key] );
				}
			}
			else
			{
				$defined_blocks[] = $block_id;
			}

			update_post_meta( $post_id, $meta_key , array_unique( $defined_blocks ) );
		}
	}

	exit();
}
add_action('wp_ajax_save_post_on_block', 'blocks_save_post_on_block');

/**
 * Save a block on a certain page and area
 * @since 0.1
 * @return Success on success
 */

function blocks_remove_post_from_block()
{
	if ( !isset($_POST['nounce']) || !wp_verify_nonce( $_POST['nounce'], 'Blocks' ) ) 
	{
		die( 'Nounce failed' );
	}

	if( !isset($_POST['post_id']) || !isset($_POST['block_id']) )
	{
		die( 'Malformed data' );
	}

	$post_id   = $_POST['post_id'];
	$block_id  = $_POST['block_id'];

	$template_areas = blocks_get_defined_areas_by_post_id($post_id);

	foreach ($template_areas as $template_area)
	{
		$meta_key = '_blocks_areas[' . $template_area . ']';

		delete_post_meta($post_id, $meta_key);
	}

	exit();
}
add_action('wp_ajax_remove_post_from_block', 'blocks_remove_post_from_block');

/**
 * Add settings for single Block
 * @since 0.1
 * @return Settings for single block
 */

function blocks_single_settings() {
	global $post;

	$blocks_link   = get_post_meta( $post->ID, '_blocks_link', true );
	$blocks_templ  = get_post_meta( $post->ID, '_blocks_template', true );

	$output = '<p class="description">' . __('Here you can add specific settings for the block', 'blocks') . '.</p>';

	/*
	 * Go through all template files for files that start with "blocks"
	 * then check if they have a template name
	 */

	$root = STYLESHEETPATH . '/blocks';

	if( file_exists( $root ) )
	{
		$dir 		= dir( $root );
		$templates  = array();

		while ( ( $file = $dir->read() ) ) 
		{
			$template_data = implode( '', file( $root . '/' . $file ) );
			$name = '';
			
			if ( preg_match( '|Block Template:(.*)$|mi', $template_data, $name ) )
				$name = _cleanup_header_comment( $name[1] );
			
			if ( ! empty( $name ) ) {
				$templates[ trim( $name ) ] = $file;
			}
		}
	}

	// Creates a select list of the blocks-templates
	if( isset( $templates ) && count( $templates ) > 0 ) :
		$template = ( isset( $blocks_templ ) && ! empty( $blocks_templ ) ) ? $blocks_templ : '';

		$output .= '<p>';
			$output .= '<div>';
			
				$output .= '<select name="_blocks_template" id="blocks-template">';
					$output .= '<option value="">' . __( 'Default Template', 'blocks' ) . '</option>';
					foreach( $templates as $t_name => $t_file ): 
						$output .= '<option' . selected( $template, $t_file, false ) . ' value="' . $t_file . '">' . $t_name . '</option>';
					endforeach;
				$output .= '</select>';
			
			$output .= '</div>';
		$output .= '</p>';
	endif; 

	// Link-field
	$output .= '<p><input type="text" name="_blocks_link" class="widefat" id="blocks-link" placeholder="' . __('Add a link to the title','blocks') . '" value="' . ( ! empty( $blocks_link ) ? $blocks_link : '' ) . '" /></p>';

	echo $output;
}