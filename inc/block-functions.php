<?php
/**
 * Get all post types and exclude blocks
 * @return array of post types
 */

function blocks_post_types() {
	$post_types = get_post_types( array( 'show_ui' => true ), 'names' );

	$key = array_search( 'blocks', $post_types );

	if( $key !== false ) {
		unset( $post_types[$key] );
	}
	return $post_types;
}

function blocks_get_template_by_type( $type ) {
	$template = '';

	// Check single-templates, single-{post_type}.php -> single.php order
	if( file_exists( TEMPLATEPATH .'/single-'. $type .'.php' ) ) {
		$template = 'single-'. $type .'.php';
	}
	elseif( ( $type != 'post' && ( $type == 'attachment' || $type == 'page' ) ) && file_exists( TEMPLATEPATH .'/'. $type .'.php' ) ) {
		$template = $type .'.php';
	}
	else {
		$template = 'single.php';
	}

	return $template;
}

function blocks_get_template_by_page_id( $page_id ) {
	$template = get_post_meta( $page_id, '_wp_page_template', true );

	if( ( empty( $template ) || 'default' == $template ) ) {
		$template = 'page.php';
	}

	return $template;
}

/**
 * Save function for post and pages
 * Saves the Blocks in Right area on AJAX-call
 * @since 0.1
 */

function blocks_save_metadata($post_id) {
	// check if user has the rights
	if ( ! current_user_can('edit_page', $post_id ) )
	{
		die( __("You don't have permission to edit or create blocks", 'blocks') );
	}

	// set post ID if is a revision
	if ( wp_is_post_revision( $post_id ) )
	{
	    $post_id = wp_is_post_revision( $post_id );
	}

	if( isset( $post_id ) )
	{
		//$data 	  = get_post_meta( $post_id, '_blocks', true );
	    $areas	  = isset($_POST['blocks']) ? $_POST['blocks'] : '';

	    // if( empty($data) )
	    // {
	    // 	$data = array();
	    // }

	    if( !empty($areas) )
	    {
	    	// Get all the settings
			$settings = get_option( 'blocks' );

		    foreach ($areas as $areaKey => $area)
		    {
		    	$metaKey = '_blocks_areas[' . $areaKey . ']';

		    	delete_post_meta( $post_id, $metaKey );

		    	$order    = trim( $area['data'] ); // we check on empty array but explode always return one item
		   		$blocks   = explode( ',', $order );

		   		if( !empty( $order ) )
		   		{
					update_post_meta( $post_id, $metaKey , array_unique( $blocks ) );
				}
		    }

			// if( !empty($data) && count($data) > 0 )
			// {
			// 	foreach( $data as $key => $value )
			// 	{
		 //    		if( empty( $value ) || ( is_array( $value ) && count( $value ) == 0 ) )
		 //    		{
		 //       			unset( $data[$key] );
		 //    		}
			// 	}
			// }
		}

		// if( count($data) > 0 )
		// {
		// 	update_post_meta( $post_id,'_blocks', $data );
		// }
		// else
		// {
		// 	delete_post_meta( $post_id,'_blocks' );
		// }
	}

	return true;
}
add_action('save_post','blocks_save_metadata');
//add_action('wp_ajax_save_blocks', 'blocks_save_metadata');


/**
 * Save metadata for blocks as an Array
 * @since 0.1
 * @param string $post_id
 */
     
function blocks_save_blocks_meta( $post_id ) 
{
	// check if user has the rights
	if ( isset( $_POST['post_type'] ) == 'blocks' && ! current_user_can('edit_page', $post_id ) ) 
	{
		die( __("You don't have premission to edit or create blocks", 'blocks') );
	}

	// set post ID if is a revision
	if ( wp_is_post_revision( $post_id ) ) 
	{
	    $post_id = wp_is_post_revision( $post_id );
	}

	// Security-check
	if ( isset( $_POST['_blocks_link'] ) || isset( $_POST['_blocks_template'] ) ) 
	{
		// Get the posted data
		$blocks_link  = isset($_POST['_blocks_link']) ? $_POST['_blocks_link'] : '';
		$blocks_templ = isset($_POST['_blocks_template']) ? $_POST['_blocks_template'] : '';

		if( empty( $blocks_link ) )
		{
			delete_post_meta( $post_id, '_blocks_link' );
		}
		else
		{
			update_post_meta( $post_id, '_blocks_link', $blocks_link );
		}

		if( empty( $blocks_templ ) )
		{
			delete_post_meta( $post_id, '_blocks_template' );
		}
		else
		{
			update_post_meta( $post_id, '_blocks_template', $blocks_templ );
		}
	}
}
add_action('save_post', 'blocks_save_blocks_meta');


/**
 * Save new block-areas
 * Must have a valid key, name and descripton
 * @since 0.1
 * @param string $area KEY of the area
 * @param string $name NAME of the area
 * @param string $desc DESCRIPTION of the area
 * @return validated areas
 */

function blocks_check_area_values( $area, $name, $desc ) 
{
	if ( ! empty( $area ) && ! empty( $name ) && ! empty( $desc ) ) 
	{
		return true;
	}
	return false;
}

/** 
 * Simple function to find areas in templates
 * @since 0.1
 * @param string $template Path to the file
 * @return defined areas
*/

function blocks_find_areas( $template ) 
{
	$defined_areas = get_file_data( TEMPLATEPATH .'/' . $template, array( 'area' => 'Block Areas' ) );

	// have to look this over
	$defined_areas = array_filter( array_map( 'trim', explode( ',', strtolower( $defined_areas['area'] ) ) ) );

	return $defined_areas;
}		

/** 
 * Find defined areas on certain post_id
 * @since 0.1
 * @param int $post_id The ID of the post
 * @return An array of defined areas
*/
function blocks_get_defined_areas_by_post_id( $post_id )
{
	$template = blocks_get_template_by_page_id( $post_id );
	return blocks_find_areas( $template );
}

/** 
 * Remove blocks data from db
 * When uninstalling
 * @since 0.1
*/

function blocks_uninstall_settings() 
{
	global $wpdb;

	if( ! defined('WP_UNINSTALL_PLUGIN') )  
	{
		exit();
	} 

	// Delete all data
	delete_option('blocks');

	// Delete postmeta contains _block
	$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = "_blocks" ' );

	// Delete transient from _options
	$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE ("%_blocks_cache_%") ' );

	// Delete post_type block from _posts
	$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'posts WHERE post_type LIKE ("%_blocks%") ' );
}