<?php
/*
Plugin Name: Nsync
Plugin URI: http://github.com/ubc/nsync
Description: Allows you to setup what other sites can also publish posts to your site.
Version: 1.1
Author: ctlt
Author URI: 
License: GPLv2 or later.
*/
if ( !defined('ABSPATH') )
	die('-1');

define( 'NSYNC_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'NSYNC_BASENAME', plugin_basename(__FILE__) );
define( 'NSYNC_DIR_URL',  plugins_url( ''  , NSYNC_BASENAME ) );

require( 'lib/class.nsync.php' );

add_action( 'admin_init',       			array( 'Nsync', 'admin_init' ) );

add_action( 'admin_print_styles-options-writing.php', array( 'Nsync', 'writing_script_n_style' ) );
add_action( 'wp_ajax_nsync_lookup_site',   	array( 'Nsync', 'ajax_lookup_site' ) );

add_action( 'post_submitbox_misc_actions', 	array( 'Nsync', 'post_display_from') );
add_filter( 'post_row_actions' , 			array( 'Nsync', 'posts_display_sync'), 11, 2);


register_deactivation_hook( __FILE__, 'nsync_uninstall');
register_uninstall_hook( __FILE__, 'nsync_uninstall');


/**
 * Unistall the plugin
 *
 **/    
function nsync_uninstall() {
	// lets uninstall the plugin
	$to_remove = get_option( 'nsync_options' );
	Nsync::remove_sites( $to_remove['active'], get_current_blog_id() );
	delete_option( 'nsync_options' );
}
