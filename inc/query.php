<?php

if( !defined( 'ABSPATH' ) ) die();

class link_monitor_query {
    public static function link_visited_by_ip( $post = 0, $link = '', $ip = '' ) {
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'link_monitor_clicks WHERE post = %s AND url = %s AND ip = %s;', array( $post, $link, $ip ) ) );
        return $count;
    }

    public static function link_info_by_ip( $post = 0, $link = '', $ip = '' ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'link_monitor_clicks WHERE post = %s AND url = %s AND ip = %s;', array( $post, $link, $ip ) ) );
        return $row;
    }

    public static function link_visitors( $post = 0, $link = '' ) {
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'link_monitor_clicks WHERE post = %s AND url = %s;', array( $post, $link ) ) );
        return $count;
    }

    public static function post_visitors( $post = 0 ) {
        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'link_monitor_clicks WHERE post = %s;', array( $post ) ) );
        return $count;
    }

    public static function view_post_link_visitors( $post = 0, $link = '' ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'link_monitor_clicks WHERE post = %s AND url = %s;', array( $post, $link ) ) );
        return $results;
    }

    public static function view_post_visitors( $post = 0 ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'link_monitor_clicks WHERE post = %s;', array( $post ) ) );
        return $results;
    }

    public static function custom_posts( $link_monitor = true ) {
        $custom_posts = array();
        $option = get_option( 'link_monitor_in_custom_posts' );
        foreach ( get_post_types( array( 'public'   => true, '_builtin' => false ), 'names' ) as $post_type ) {
            if( !$link_monitor || ( isset( $option[$post_type] ) && (boolean) $option[$post_type] ) )
            $custom_posts[] =  $post_type;
        }
        return $custom_posts;
    }
}