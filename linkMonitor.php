<?php

if( !defined( 'ABSPATH' ) ) die();

/*
Plugin Name: Link Monitor WP
Plugin URI: http://ddweb.eu/link-hide-monitor/
Description: Hide original URLs and get click statistics
Version: 1.0
Author: DDWeb.eu
Author URI: http://ddweb.eu
*/

if( !defined( 'LINK_MONITOR_FILE' ) ) {
    define( 'LINK_MONITOR_FILE', __FILE__ );
}

spl_autoload_register(function ( $cn ) {
    if( file_exists( ( $file = plugin_dir_path( LINK_MONITOR_FILE ) . '/inc/' . str_replace( 'link_monitor_', '', $cn ) . '.php' ) ) )
    include_once $file;
});

$link_monitor = new link_monitor_init;

register_activation_hook( LINK_MONITOR_FILE, array( $link_monitor, 'install' ) );