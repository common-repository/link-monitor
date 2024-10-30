<?php

if( !defined( 'ABSPATH' ) ) die();

class link_monitor_admin {
    function init() {
        /* Display links on this page/post */
        add_action( 'add_meta_boxes', array( $this, 'show_links' ), 1 );
        /* Display settings on this page/post */
        add_action( 'add_meta_boxes', array( $this, 'links_settings' ), 1 );
        /* Save settings on this page/post */
        add_action( 'save_post', array( $this, 'links_settings_save' ), 1, 2 );
        /* Enqueue styles for admin panel */
        add_action( 'admin_print_styles', array( $this, 'add_style' ), 1 );
        /* Enqueue scripts for admin panel */
        add_action( 'admin_print_scripts', array( $this, 'add_scripts' ), 1 );
        /* Add in admin panel page for general settings */
        add_action( 'admin_menu', array( $this, 'settings_page' ) );
        /* Create page for general settings */
        add_action( 'admin_init', array( $this, 'settings_page_init') );
        /* Admin get stats for a link - ajax */
        add_action( 'wp_ajax_link_get_stats', array( $this, 'link_get_stats' ) );
        /* Admin get stats for a post - ajax */
        add_action( 'wp_ajax_post_get_stats', array( $this, 'post_get_stats' ) );
    }

    public function add_style() {
        wp_register_style( 'link-monitor', plugins_url( '/assets/link-monitor.css', LINK_MONITOR_FILE ) );
        wp_enqueue_style( 'link-monitor' );
    }

    public function add_scripts() {
        wp_register_script( 'link-monitor-js', plugins_url( '/assets/link-monitor.js', LINK_MONITOR_FILE ), array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script( 'link-monitor-js' );
    }

    public function show_links() {
        global $pagenow;
        if( $pagenow == 'post-new.php' ) return ;
        $meta_on_pages = array();
        if( (boolean) get_option( 'link_monitor_in_posts' ) ) {
            $meta_on_pages[] = 'post';
        }
        if( (boolean) get_option( 'link_monitor_in_pages' ) ) {
            $meta_on_pages[] = 'page';
        }
        $post_types = array_merge( $meta_on_pages, link_monitor_query::custom_posts() );
        if( !empty( $post_types ) )
        add_meta_box( 'link-monitor', __( 'Links On This Page', 'link-monitor' ), array( $this, 'show_links_markup' ), $post_types, 'side', 'high', null );
    }

    public static function show_links_markup( $object ) {
        $links_content = link_monitor_utils::getLinks( $object->post_content );
        $links_excerpt = array();
        if( (boolean) get_option( 'link_monitor_in_excerpt' ) ) {
            $links_excerpt = link_monitor_utils::getLinks( htmlspecialchars_decode( $object->post_excerpt ) );
        }
        $links = array_merge( $links_content, $links_excerpt );

        $all_clicks = link_monitor_query::post_visitors( $object->ID );

        echo '<ul class="link-monitor-post-links">';
        foreach( $links as $link ) {
            $visits = (int) link_monitor_query::link_visitors( $object->ID, $link['url'] );
            echo '<li>' . ( $visits > 0 ? '<a href="#" data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-ajax-action="link_get_stats" data-ajax-nonce="' . wp_create_nonce( 'link-monitor-link-stats' ) . '"data-post-id="' . $object->ID . '" data-link="' . $link['url'] . '" data-link-monitor-stats="">' . esc_html( $link['title'] ) . '</a>' : esc_html( $link['title'] ) ) . '<span>' . $visits . '</span></li>';
        }

        if( $all_clicks > 0 ) {
            echo '<li><a href="#" data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-ajax-action="post_get_stats" data-ajax-nonce="' . wp_create_nonce( 'link-monitor-post-stats' ) . '"data-post-id="' . $object->ID . '" data-link-monitor-stats="">' . sprintf( __( 'View All Clicks (%s)', 'link-monitor' ), $all_clicks ) . '</a></li>';
        }
        echo '</ul>';
    }

    public function links_settings() {
        $meta_on_pages = array();
        if( (boolean) get_option( 'link_monitor_in_posts' ) ) {
            $meta_on_pages[] = 'post';
        }
        if( (boolean) get_option( 'link_monitor_in_pages' ) ) {
            $meta_on_pages[] = 'page';
        }
        $post_types = array_merge( $meta_on_pages, link_monitor_query::custom_posts() );
        if( !empty( $post_types ) )
        add_meta_box( 'link-monitor-settings', __( 'Links Monitor Settings', 'link-monitor' ), array( $this, 'links_settings_markup' ), $post_types, 'side', 'high', null );
    }

    public static function links_settings_markup( $object ) {
        wp_nonce_field( basename( __FILE__ ), 'link-monitor-nonce' );

        echo '<ul class="link-monitor-post-settings">';
            echo '<li><input type="checkbox" name="link-monitor-show" value="1" id="link-monitor-show"' . ( (boolean) get_post_meta( $object->ID, 'show_links', true ) ? '' : ' checked' ) . ' /><label for="link-monitor-show"><span class="lm-check"></span> ' . __( 'Hide links on this page', 'link-monitor' ) . '</label></li>';
        echo '</ul>';
    }

    public static function links_settings_save( $post_id, $post ) {
        if( !isset( $_POST['link-monitor-nonce'] ) || !wp_verify_nonce( $_POST['link-monitor-nonce'], basename( __FILE__ ) ) ) return false;
        if( !current_user_can( 'edit_post', $post_id ) ) return false;
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return false;

        if( isset( $_POST['link-monitor-show'] ) ) {
            update_post_meta( $post_id, 'show_links', 0 );
        } else {
            update_post_meta( $post_id, 'show_links', 1 );
        }
    }

    public function settings_page() {
        add_options_page( __( 'Link Monitor Settings Admin', 'link-monitor' ), __( 'Link Monitor', 'link-monitor' ), 'manage_options', 'my-setting-admin', array( $this, 'create_settings_page' ) );
    }

    public function create_settings_page() {
        $this->link_monitor_get = get_option( 'link_monitor_get' );
        $this->link_monitor_in_posts = get_option( 'link_monitor_in_posts' );
        $this->link_monitor_in_pages = get_option( 'link_monitor_in_pages' );
        $this->link_monitor_in_excerpt = get_option( 'link_monitor_in_excerpt' );
        $this->link_monitor_in_comments = get_option( 'link_monitor_in_comments' );
        $this->link_monitor_in_feed = get_option( 'link_monitor_in_feed' );
        $this->link_monitor_in_custom_posts = get_option( 'link_monitor_in_custom_posts' );
        echo '<div class="wrap">
            <h1>' . __( 'Link Monitor Settings', 'link-monitor' ) . '</h1>
            <form method="post" action="options.php">';
                echo settings_fields( 'link_monitor_options' );
                echo do_settings_sections( 'link-monitor-setting-admin' );
                echo submit_button();
        echo '</form>
        </div>';
    }

    public function settings_page_init() {
        register_setting( 'link_monitor_options', 'link_monitor_get', array( $this, 'text_sanitize' ) );
        register_setting( 'link_monitor_options', 'link_monitor_in_posts', array( $this, 'checkbox_sanitize' ) );
        register_setting( 'link_monitor_options', 'link_monitor_in_pages', array( $this, 'checkbox_sanitize' ) );
        register_setting( 'link_monitor_options', 'link_monitor_in_excerpt', array( $this, 'checkbox_sanitize' ) );
        register_setting( 'link_monitor_options', 'link_monitor_in_comments', array( $this, 'checkbox_sanitize' ) );
        register_setting( 'link_monitor_options', 'link_monitor_in_feed', array( $this, 'checkbox_sanitize' ) );
        register_setting( 'link_monitor_options', 'link_monitor_in_custom_posts', array( $this, 'array_sanitize' ) );

        add_settings_section( 'setting_section_id', __( 'Modify Your General Link Monitor Settings', 'link-monitor' ), array( $this, 'link_monitor_settings_info' ), 'link-monitor-setting-admin' );

        add_settings_field( 'link_monitor_get', __( 'GET Parameter', 'link-monitor' ), array( $this, 'link_monitor_get' ), 'link-monitor-setting-admin', 'setting_section_id' );
        add_settings_field( 'link_monitor_in_posts', __( 'In Posts', 'link-monitor' ), array( $this, 'link_monitor_in_posts' ), 'link-monitor-setting-admin', 'setting_section_id' );
        add_settings_field( 'link_monitor_in_pages', __( 'In Pages', 'link-monitor' ), array( $this, 'link_monitor_in_pages' ), 'link-monitor-setting-admin', 'setting_section_id' );
        add_settings_field( 'link_monitor_in_excerpt', __( 'In Excerpts', 'link-monitor' ), array( $this, 'link_monitor_in_excerpt' ), 'link-monitor-setting-admin', 'setting_section_id' );
        add_settings_field( 'link_monitor_in_comments', __( 'In Comments', 'link-monitor' ), array( $this, 'link_monitor_in_comments' ), 'link-monitor-setting-admin', 'setting_section_id' );
        add_settings_field( 'link_monitor_in_feed', __( 'In Feed/RSS', 'link-monitor' ), array( $this, 'link_monitor_in_feed' ), 'link-monitor-setting-admin', 'setting_section_id' );
        add_settings_field( 'link_monitor_in_custom_posts', __( 'In Custom Posts', 'link-monitor' ), array( $this, 'link_monitor_in_custom_posts' ), 'link-monitor-setting-admin', 'setting_section_id' );

    }

    public function text_sanitize( $input ) {
        $new_input = preg_replace( '/[^a-z0-9_]/i', '', $input );
        return ( !empty( $new_input ) ? $new_input : 'goto' );
    }

    public function checkbox_sanitize( $input ) {
        return isset( $input ) ? 1 : 0;
    }

    public function array_sanitize( $input ) {
        $settings = array();
        foreach( $input as $key => $value ) {
            $settings[$key] = (boolean) $value;
        }
        return $settings;
    }

    public function link_monitor_settings_info() {
        _e( 'This is the settings page for Link Monitor, here you can globally activate or deactivate links monitoring.', 'link-monitor' );
    }

    public function link_monitor_get() {
        echo '<input type="text" id="link_monitor_get" name="link_monitor_get" value="' . esc_html( $this->link_monitor_get ) . '" />';
    }

    public function link_monitor_in_posts() {
        echo '<ul class="link-monitor-post-settings lm-settings-page">
            <li><input type="checkbox" id="link_monitor_in_posts" name="link_monitor_in_posts" value="1"' . ( (boolean) $this->link_monitor_in_posts ? ' checked' : '' ) . ' /><label for="link_monitor_in_posts"><span class="lm-check"></span></label></li>
        </ul>';
    }

    public function link_monitor_in_pages() {
        echo '<ul class="link-monitor-post-settings lm-settings-page">
            <li><input type="checkbox" id="link_monitor_in_pages" name="link_monitor_in_pages" value="1"' . ( (boolean) $this->link_monitor_in_pages ? ' checked' : '' ) . ' /><label for="link_monitor_in_pages"><span class="lm-check"></span></label></li>
        </ul>';    }

    public function link_monitor_in_excerpt() {
        echo '<ul class="link-monitor-post-settings lm-settings-page">
            <li><input type="checkbox" id="link_monitor_in_excerpt" name="link_monitor_in_excerpt" value="1"' . ( (boolean) $this->link_monitor_in_excerpt ? ' checked' : '' ) . ' /><label for="link_monitor_in_excerpt"><span class="lm-check"></span></label></li>
        </ul>';    }

    public function link_monitor_in_comments() {
        echo '<ul class="link-monitor-post-settings lm-settings-page">
            <li><input type="checkbox" id="link_monitor_in_comments" name="link_monitor_in_comments" value="1"' . ( (boolean) $this->link_monitor_in_comments ? ' checked' : '' ) . ' /><label for="link_monitor_in_comments"><span class="lm-check"></span></label></li>
        </ul>';
    }

    public function link_monitor_in_feed() {
        echo '<ul class="link-monitor-post-settings lm-settings-page">
            <li><input type="checkbox" id="link_monitor_in_feed" name="link_monitor_in_feed" value="1"' . ( (boolean) $this->link_monitor_in_feed ? ' checked' : '' ) . ' /><label for="link_monitor_in_feed"><span class="lm-check"></span></label></li>
        </ul>';
    }

    public function link_monitor_in_custom_posts() {
        $custom_posts = link_monitor_query::custom_posts( false );
        if( !empty( $custom_posts ) ) {
            echo '<ul class="link-monitor-post-settings lm-settings-page">';
            foreach( $custom_posts as $custom_post ) {
                echo '<li>
                <input type="checkbox" id="link_monitor_in_custom_posts[' . $custom_post . ']" name="link_monitor_in_custom_posts[' . $custom_post . ']" value="1"' . ( isset( $this->link_monitor_in_custom_posts[$custom_post] ) && (boolean) $this->link_monitor_in_custom_posts ? ' checked' : '' ) . ' /> <label for="link_monitor_in_custom_posts[' . $custom_post . ']"><span class="lm-check"></span> ' . $custom_post . '</label>
                </li>';
            }
            echo '</ul>';
        } else {
            echo '-';
        }
    }

    public function link_get_stats() {
        check_ajax_referer( 'link-monitor-link-stats', 'security' );

        $links = link_monitor_query::view_post_link_visitors( $_POST['post_id'], $_POST['link'] );
        if( !empty( $links ) ) {
        echo '<ul class="link-monitor-stats-table">';

        echo '<li>
                <span class="lm-ip">' . __( 'IP', 'link-monitor' ) . '</span>
                <span class="lm-lk">' . __( 'URL', 'link-monitor' ) . '</span>
                <span class="lm-fc">' . __( 'First Click', 'link-monitor' ) . '</span>
                <span class="lm-clicks">' . __( 'Clicks', 'link-monitor' ) . '</span>
                <span class="lm-lc">' . __( 'Last Click', 'link-monitor' ) . '</span>
            </li>';

        foreach( $links as $link ) {
            echo '<li>
                    <span class="lm-ip">' . esc_html( $link->ip ) . '</span>
                    <span class="lm-lk">' . esc_html( $link->url ) . '</span>
                    <span class="lm-fc">' . $link->date . '</span>
                    <span class="lm-clicks">' . $link->clicks . '</span>
                    <span class="lm-lc">' . $link->last_click . '</span>
                </li>';
        }
        echo '</ul>';
        }

        wp_die();
    }

    public function post_get_stats() {
        check_ajax_referer( 'link-monitor-post-stats', 'security' );

        $links = link_monitor_query::view_post_visitors( $_POST['post_id'] );
        if( !empty( $links ) ) {
        echo '<ul class="link-monitor-stats-table">';

        echo '<li>
                <span class="lm-ip">' . __( 'IP', 'link-monitor' ) . '</span>
                <span class="lm-lk">' . __( 'URL', 'link-monitor' ) . '</span>
                <span class="lm-fc">' . __( 'First Click', 'link-monitor' ) . '</span>
                <span class="lm-clicks">' . __( 'Clicks', 'link-monitor' ) . '</span>
                <span class="lm-lc">' . __( 'Last Click', 'link-monitor' ) . '</span>
            </li>';

        foreach( $links as $link ) {
            echo '<li>
                    <span class="lm-ip">' . esc_html( $link->ip ) . '</span>
                    <span class="lm-lk">' . esc_html( $link->url ) . '</span>
                    <span class="lm-fc">' . $link->date . '</span>
                    <span class="lm-clicks">' . $link->clicks . '</span>
                    <span class="lm-lc">' . $link->last_click . '</span>
                </li>';
        }
        echo '</ul>';
        }

        wp_die();
    }

}