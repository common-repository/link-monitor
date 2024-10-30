<?php

if( !defined( 'ABSPATH' ) ) die();

class link_monitor_update {
    public static function update_visit( $link = '', $post = 0 ) {
    	global $wpdb;

        $my_ip = link_monitor_utils::getMyIP();

        if( link_monitor_query::link_visited_by_ip( $post, $link, $my_ip ) > 0 ) {
            $info = link_monitor_query::link_info_by_ip( $post, $link, $my_ip );

        	return $wpdb->update(
        		$wpdb->prefix . 'link_monitor_clicks',
        		array(
                    'clicks'    => $info->clicks+1,
        			'last_click'=> current_time( 'mysql' )
        		),
                array(
                    'post'  => (int) $post,
                    'url'   => $link,
                    'ip'    => $my_ip
                )
        	);
        } else {
        	return $wpdb->insert(
        		$wpdb->prefix . 'link_monitor_clicks',
        		array(
                    'post'  => (int) $post,
                    'url'   => $link,
                    'ip'    => $my_ip,
                    'last_click' => current_time( 'mysql' ),
        			'date'  => current_time( 'mysql' )
        		)
        	);
        }
    }
}