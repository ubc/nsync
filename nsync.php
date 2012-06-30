<?php
/*
Plugin Name: nsync
Plugin URI: 
Description: 
Version: 0.1
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

add_action( 'init',       array( 'Nsync', 'init' ) );
add_action( 'admin_init',       array( 'Nsync', 'admin_init' ) );