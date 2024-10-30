<?php

if( !defined( 'ABSPATH' ) ) die();

class link_monitor_utils {
    public static function getMyIP() {
        if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if( isset($_SERVER['HTTP_FORWARDED_FOR'] ) ) $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if( isset( $_SERVER['HTTP_FORWARDED'] ) ) $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if( isset($_SERVER['REMOTE_ADDR'] ) ) $ipaddress = $_SERVER['REMOTE_ADDR'];
        else  $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public static function getLinks( $content = '' ) {
        preg_match_all( '/<a\s[^>]*href=\"([^\"]*)\"[^>]*>(.*)<\/a>/siU', $content, $output );
        if( !isset( $output[1] ) || !isset( $output[2] ) ) {
            return array();
        }
        $links = array();
        foreach( $output[1] as $k => $link ) {
            $links[] = array( 'title' => $output[2][$k], 'url' => $link );
        }
        return $links;
    }
}