<?php
/*
Plugin Name: Nsync
Plugin URI: http://github.com/ubc/nsync
Description: Allows you to setup what other sites can also publish posts to your site.
Version: 1
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

add_action( 'init',       		array( 'Nsync', 'init' ) );
add_action( 'admin_init',       array( 'Nsync', 'admin_init' ) );

add_action( 'admin_print_styles-options-writing.php', array( 'Nsync', 'writing_script_n_style' ) );
add_action( 'wp_ajax_nsync_lookup_site',   array( 'Nsync', 'ajax_lookup_site' ) );
