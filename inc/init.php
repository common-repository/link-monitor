<?php

if( !defined( 'ABSPATH' ) ) die();

class link_monitor_init extends link_monitor_setup {

    private $links  = array();

    function __construct() {
        $this->init();
    }

    private function init() {
        $find_in = array();
        $find_in['the_content'] = 'content';
        if( (boolean) get_option( 'link_monitor_in_excerpt' ) ) {
            $find_in['the_excerpt'] = 'excerpt';
        }
        if( (boolean) get_option( 'link_monitor_in_comments' ) ) {
            $find_in['comment_text'] = 'comment';
        }

        if( (boolean) get_option( 'link_monitor_in_feed' ) ) {
            $find_in['the_content_feed'] = 'feed';
            $find_in['the_content_rss'] = 'feed';
            $find_in['comment_text_rss'] = 'feed';
        }

        foreach( $find_in as $filter => $type ) {
            add_filter( $filter, function( $content ) use ( $type ) {
                return $this->extract_links( $content, $type );
            }, 99 );
        }

        load_plugin_textdomain( 'link-monitor', false, plugins_url( '/lang', LINK_MONITOR_FILE ) );

        $redirect = new link_monitor_redirect;
        $redirect->init();

        $admin_interface = new link_monitor_admin;
        $admin_interface->init();
    }

    private function getContentWithLinks( $post_id = 0, $links = array(), $content = '' ) {
        foreach( $links as $link ) {
            $list = array();
            $list['link'] = $link['url'];
            $list['post_id'] = $post_id ;

            $goto = get_option( 'link_monitor_get' );
            $goto = ( !empty( $goto ) ? esc_html( $goto ) : 'goto' );

            $content = str_replace( $link['url'], home_url( '/' ) . '?' . esc_html( $goto ) . '=' . base64_encode( json_encode( $list ) ), $content );
        }
        return $content;
    }

    public function extract_links( $content, $type ) {
        global $post;
        if( $type == 'content' || $type == 'excerpt' ) {
        if( isset( $post->post_type ) ) {
            $allow_in_post_type = array();
            if( (boolean) get_option( 'link_monitor_in_pages' ) ) $allow_in_post_type['page'] = true;
            if( (boolean) get_option( 'link_monitor_in_posts' ) ) $allow_in_post_type['post'] = true;
            foreach( link_monitor_query::custom_posts() as $custom_post_type ) {
                $allow_in_post_type[$custom_post_type] = true;
            }

            if( !( $post_in_list = in_array( $post->post_type, array_keys( $allow_in_post_type ) ) ) || ( $post_in_list && (boolean) get_post_meta( $post->ID, 'show_links', true ) ) ) {
                return $content;
            }
        }
        }

        $links = link_monitor_utils::getLinks( $content );
        $content = $this->getContentWithLinks( $post->ID, array_values( $links ), $content );
        return $content;
    }

}