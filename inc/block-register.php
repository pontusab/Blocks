<?php

/**
 * Register the
 * Custom post type  
 * @since 0.1
 * @return the post_type Blocks
 */

function blocks_register_custom_post() {
	
	$args = array(
		'labels' => array(
			'name' 				 => __('Blocks', 'blocks'),
			'singular_name' 	 => __('Block', 'blocks'),
			'add_new' 			 => __('Add Block', 'blocks'),
			'add_new_item' 		 => __('Add Block', 'blocks'),
			'edit_item' 		 => __('Edit Block', 'blocks'),
			'new_item' 			 => __('New Block', 'blocks'),
			'view_item' 		 => __('View Block', 'blocks'), 
			'search_items' 		 => __('Search Block', 'blocks'), 
			'not_found' 		 => __('No Blocks Found', 'blocks'), 
			'not_found_in_trash' => __('No Blocks found in the trash.', 'blocks'), 
			'parent_item_colon'  => __('Block parent', 'blocks') 
		), 
		'exclude_from_search'    => true,
		'publicly_queryable'     => true,
		'show_ui' 			     => true,
		'capability_type'        => 'page',
		'hierarchical'		     => false,
		'show_in_nav_menus'      => false,
		'rewrite'			     => false,
		'register_meta_block_cb' => 'blocks_metadata',
		'supports' => array(
			'title',
			'editor',
			'thumbnail',
		)
	);
	register_post_type('blocks', $args);
}
add_action('init', 'blocks_register_custom_post');


/**
 * Add blocks default settings
 * On plugin activation
 * @since 0.1
 * @return default settings on activation
 */

function blocks_activate_settings() {

	$settings = get_option('blocks');

	if( ! $settings ) {

		$settings = array(
			'areas'    => array(
				'left' => array(
					'name' 	=> __('Left column', 'blocks'),
					'desc'	=> __('This is the left sidebar', 'blocks')
				),
				'right' => array(
					'name' 	=> __('Right column', 'blocks'),
					'desc'	=> __('This is the right sidebar', 'blocks')
				),
			),
		);
		add_option( 'blocks', $settings, '', 'yes' );
	}
}


/**
 * Add new update messages
 * For when saving or update  
 * @since 0.1
 * @param string $messages 
 * @return messages on update of block
 */

function blocks_update_messages( $messages ) {
	global $post;

	$messages['blocks'] = array(
		0  => '',
		1  => __( 'Block updated. See the result on the pages you added the block on.', 'blocks' ),
	);

  return $messages;
}
add_filter('post_updated_messages', 'blocks_update_messages');


/**
 * Register javascripts
 * And stylesheets
 * @since 0.1
 * @return Scripts and Styles
 */

function blocks_wp_admin_style() 
{
	global $typenow;

	$types = blocks_post_types();
	$settings  = get_option( 'blocks' );

	if( isset( $settings['exclude'] ) && count( $settings['exclude'] ) > 0 )
	{
		$types = array_diff( $types, $settings['exclude'] );
	}

	if( in_array( $typenow, $types ) || get_current_screen()->post_type == 'blocks' )
	{
		$debug = isset( $_GET['debug'] ) ? $_GET['debug'] : false;
		
		if( $debug )
		{
		    wp_register_script( 'blocks_list_js', BLOCKS_URL . 'assets/js/vendors/list.js', false, '0.1', true );
		    wp_register_script( 'blocks_list_paging_js', BLOCKS_URL . 'assets/js/vendors/list.paging.js', false, '0.1', true );
		    wp_register_script( 'blocks_admin_js', BLOCKS_URL . 'assets/js/blocks.js', false, '0.1', true );

		    wp_enqueue_script( 'blocks_list_js' );
		    wp_enqueue_script( 'blocks_list_paging_js' );
		}
		else
		{
		    wp_register_script( 'blocks_admin_js', BLOCKS_URL . 'assets/js/blocks-min.js', false, '0.1', true );

		}
		wp_enqueue_script( 'blocks_admin_js' );

		wp_localize_script(
	    	'blocks_admin_js',
	    	'BlocksConstants',
	    	array(
	    		'AjaxNounce' => wp_create_nonce( 'Blocks' )
	    	)
	    );
	}

	wp_register_style( 'blocks_admin_css', BLOCKS_URL . '/assets/css/blocks.css', false, '0.1' );
	wp_enqueue_style( 'blocks_admin_css' );	

}
 add_action( 'admin_enqueue_scripts', 'blocks_wp_admin_style' );

