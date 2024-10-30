<?php

if( !defined( 'ABSPATH' ) ) die();

class link_monitor_setup {
    public function install() {
    	global $wpdb;

        // create table for monitoring clicks
    	$sql = 'CREATE TABLE ' . $wpdb->prefix . 'link_monitor_clicks (
    		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
    		`post` mediumint(10) DEFAULT 0 NOT NULL,
    		`url` varchar(255) DEFAULT "" NOT NULL,
            `ip` varchar(50) DEFAULT "" NOT NULL,
    		`clicks` mediumint(10) DEFAULT 1 NOT NULL,
    		`last_click` datetime DEFAULT "0000-00-00 00:00:00" NOT NULL,
    		`date` datetime DEFAULT "0000-00-00 00:00:00" NOT NULL,
    		PRIMARY KEY  (id)
    	) ' . $wpdb->get_charset_collate() . ';';

    	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    	dbDelta( $sql );

        // add default options
        add_option( 'link_monitor_get', 'goto' );
        add_option( 'link_monitor_in_posts', 1 );
        add_option( 'link_monitor_in_pages', 1 );
        add_option( 'link_monitor_in_excerpt', 1 );
        add_option( 'link_monitor_in_comments', 1 );
        add_option( 'link_monitor_in_feed', 1 );
        add_option( 'link_monitor_in_custom_posts', 1 );
    }
}