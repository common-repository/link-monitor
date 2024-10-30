<?php

if( !defined( 'ABSPATH' ) ) die();

class link_monitor_redirect {
    public function init() {
        $goto = get_option( 'link_monitor_get' );
        $goto = ( !empty( $goto ) ? esc_html( $goto ) : 'goto' );
        if( !empty( $_GET[$goto] ) ) {

            $list = @json_decode( base64_decode( $_GET[$goto] ), true );

            if( isset( $list['link'] ) && filter_var( $list['link'], FILTER_VALIDATE_URL ) ) {
                if( link_monitor_update::update_visit( $list['link'], $list['post_id'] ) ) {
                    header( 'Location: ' . $list['link'], true );
                    die;
                }
            }
        }
    }
}