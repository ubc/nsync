<?php
/*
Plugin Name: nsync
Plugin URI: 
Description: 
Version: 0.9
Author: 
Author URI: 
License: GPLv2 or later.
*/
if ( !defined('ABSPATH') )
	die('-1');

define( 'NSYNC_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'NSYNC_BASENAME', plugin_basename(__FILE__) );
define( 'NSYNC_DIR_URL',  plugins_url( ''  , NSYNC_BASENAME ) );

require( 'lib/class.nsync.php' );
require( 'lib/class.nsync-posts.php' );

add_action( 'init',       		array( 'Nsync', 'init' ) );
add_action( 'admin_init',       array( 'Nsync', 'admin_init' ) );

add_action( 'admin_print_styles-options-writing.php', array( 'Nsync', 'writing_script_n_style' ) );
add_action( 'admin_print_styles-post-new.php', array( 'Nsync', 'post_to_script_n_style' ) );
add_action( 'admin_print_styles-post.php', array( 'Nsync', 'post_to_script_n_style' ) );

add_action( 'wp_ajax_nsync_lookup_site',   array( 'Nsync', 'ajax_lookup_site' ) );

add_action( 'post_submitbox_misc_actions', array( 'Nsync', 'user_select_site') );
add_action( 'post_submitbox_misc_actions', array( 'Nsync', 'post_from_site'), 9 );
add_action( 'save_post', array( 'Nsync_Posts', 'save_postdata') , 10, 2 );
add_action( 'wp_trash_post', array( 'Nsync_Posts', 'trash_or_untrash_post'), 10, 1 );
add_action( 'untrash_post', array( 'Nsync_Posts', 'trash_or_untrash_post'), 10, 1 );

add_filter( 'post_row_actions' , array( 'Nsync_Posts', 'display_sync'), 10, 2);
add_filter( 'post_updated_messages', array( 'Nsync_Posts', 'update_message'));