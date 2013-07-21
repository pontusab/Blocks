<?php
/**
 * Plugin Name: Blocks
 * Plugin URI:  https://github.com/pontusab/blocks
 * Text Domain: blocks
 * Domain Path: /lang/
 * Description: Let's you add dynamic content areas.
 * Version:     2012.12.10
 * Author:      Pontus Abrahamsson <pontus.abrahamsson@netrelations.se>
 * Author URI:  http://pontusab.se
 * License:     MIT
 * License URI: http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright (c) 2013 Pontus Abrahamsson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

/**
 * Definitions 
 * @since 0.1
 * @return Handy constants
 */

function blocks_define_constants() {
	if ( ! defined( 'BLOCKS_URL' ) ) {
    	define( 'BLOCKS_URL', plugin_dir_url( __FILE__ ) );
	}
	if ( ! defined( 'BLOCKS_DIR' ) ) {
    	define( 'BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
    }
}
add_action('plugins_loaded', 'blocks_define_constants');


/**
 * Localization 
 * @since 0.1
 */

load_plugin_textdomain( 'blocks', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );


/**
 * Add default settings on plugin activation
 * @since 0.1
 */

register_activation_hook( __FILE__, 'blocks_activate_settings' );


/**
 * Remove settings on plugin uninstall
 * @since 0.1
 */

register_uninstall_hook( __FILE__, 'blocks_uninstall_settings' );

/**
 * Include necessary files
 * @since 0.1
 */

require_once('inc/block-register.php'); 		 // Register Custom Post Types and styles
require_once('inc/block-settings.php'); 		 // Settings for blocks
require_once('inc/block-functions.php'); 		 // Needed functions for post_types, Query
require_once('inc/block-types-metaboxes.php');   // Inlude all the metaboxes on post_types
require_once('inc/block-metaboxes.php'); 		 // Register metaboxes on blocks page
require_once('inc/block-template.php'); 		 // Output the blocks default style