<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

Class RDP_Main_Screen {

    function __construct() {
        add_action( 'admin_menu', array($this,'rdp_add_admin_menu') );
        add_action( 'wp_ajax_rdp_ajax_process', array($this,'rdp_ajax_process') );
        add_filter( "plugin_action_links_".RDP_PLUGIN_BASENAME, array($this,'rdp_settling_link') );
    }

    /**
     * Main Screen To Select Which Duplicate Post Check
     * @since 1.0.0
     */
    function rdp_add_admin_menu(){
        add_submenu_page(
            'tools.php',
            __( 'Remove Duplicate Posts', 'rdp_textdomain' ),
            'Remove Duplicate Posts',
            'manage_options',
            'remove-duplicate-posts',
            array($this,'rdp_main_screen_content')
        );
    }

    function rdp_settling_link( $links ) {
        $settings_link = '<a href="tools.php?page=remove-duplicate-posts">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    /**
     * Main Screen Content
     * @since 1.0.0
     */
    function rdp_main_screen_content(){
        $html = '<div class="rdp-main-screen-wrapper">
                <div class="rdp-main-screen-content">
                    <div class="rdp-title">'. __('Select Post Type','rdp_domain') .'</div>
                    <div class="rdp-select-post">
                        <select class="rdp-post-types">';

        foreach( $this->rdp_get_all_posts() as $post_type ) {
            $html .= '<option value="'.$post_type.'">'.$post_type.'</option>';
        }

        $html .= '</select>
                        <input type="button" class="rdp-btn search-duplicates" value="'. __('Search Duplicates','rdp_domain') .'">
                        
                        <div class="rdp-adv-setting">Advanced Setting</div>
                        <div class="rdp-adv-setting-content">
                            <div class="rdp-adv-opt">
                                <label for="rdp-title-contains" class="rdp-title-contains rdp-lbl">'.__('Title Contains','rdp_domain').'</label>
                                <input type="text" class="rdp-title-contains" id="rdp-title-contains">
                                <label for="rdp-post-status" class="rdp-post-status rdp-lbl">'.__('Post Status','rdp_domain').'</label>
                                <select class="rdp-post-status" id="rdp-post-status">
                                    <option value="publish">Publish</option>
                                    <option value="draft">Draft</option>
                                    <option value="any">Any</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rdp-progess">
                        <div class="rdp-progress-bar">
                            <div class="rdp-progress-fill"></div>
                        </div>
                    </div>
                    
                    <div class="rdp-result"></div>
                    
                    <div class="rdp-loader"><img src="'. RDP_PLUGIN_URL .'assets/img/loader.gif" width="40"/> </div>
                    
                    <div class="rdp-message"></div>
                    
                    <div class="rdp-actions">
                        <!-- <div class="rdp-delt-permanently rdp-btn"><span class="dashicons dashicons-trash"></span> </div> -->
                        <div class="rdp-delt-permanently rdp-btn"><span class="dashicons dashicons-trash"></span> '. __('DELETE PERMANENTLY','rdp_domain') .'</div>
                        <div class="rdp-notice">'. __('Note: Please take backup before proceeding','rdp_domain') .'</div>
                    </div>
                </div>
            </div>';

        echo $html;
    }

    /**
     * Ajax Call Back Function
     * Performing Ajax Queries
     * @since 1.0.0
     */
    function rdp_ajax_process() {
        global $wpdb;

        if( $_POST['target'] == 'remove_duplicates_posts' ) {
            $dp_ids = $_POST['duplicate_ids_to_remove'];
            $duplicate_ids = explode(',',$dp_ids);
            foreach( $duplicate_ids as $dp_id ) {
                if( !empty($dp_id) ) {
                    wp_delete_post( $dp_id );
                    echo $dp_id.'-';
                }
            }
        }

        $query_status = ''; $query_title = '';
        if( isset( $_POST['status'] ) && $_POST['status'] != '' ) {
            $query_status = " AND $wpdb->posts.post_status = '".$_POST['status']."' ";
        }

        if( $_POST['target'] == 'search_duplicates' ) { // Setup WP_Query as per search
            $selected_post_type = $_POST['selected_post_type'];
            $title_s = array();
            if( isset( $_POST['title_contains'] ) && !empty( $_POST['title_contains'] ) ) {
                $title_s['s'] = $_POST['title_contains'];
                $args = array(
                    'post_type' => $selected_post_type,
                    's' => $_POST['title_contains'],
                    'posts_per_page' => -1
                );
            } else {
                $args = array(
                    'post_type' => $selected_post_type,
                    'posts_per_page' => -1
                );
            }

            $getting_all_posts = new WP_Query( $args );
            $total_posts = 0;
            if ( $getting_all_posts->have_posts() ) {

                $found_duplicates_posts = array(); $found_duplicates = array();
                $html = '';
                while ( $getting_all_posts->have_posts() ) {
                    $getting_all_posts->the_post();
                    $title = get_the_title();

                    // Clear Array
                    unset($found_duplicates);

                    // Query to get all duplicate posts
                    $querystr = "
                        SELECT $wpdb->posts.ID
                        FROM $wpdb->posts
                        WHERE $wpdb->posts.post_title = '".$title."'                        
                        AND $wpdb->posts.post_type = '".$selected_post_type."'
                        ". $query_status ."
                        ORDER BY $wpdb->posts.post_date DESC";

                    $posts_found = $wpdb->get_results($querystr, OBJECT);

                    // Getting Duplicate Ids
                    foreach( $posts_found as $post_ids ) {
                        $found_duplicates[] = $post_ids->ID;
                    }

                    // Make new array to store all the duplicate items
                    if( count( $found_duplicates ) > 1 ) {
                        $found_duplicates_posts[$title] = $found_duplicates;
                    }
                }

                wp_reset_postdata();
            }

            $has_post = 0; $rdp_duplicate_ids = ''; $dp_msg = '';

            // If Duplicate Posts Exists
            foreach( $found_duplicates_posts as $dp_posts_key => $dp_posts_value ) {
                $counter = 0;

                $html .= '<div class="rdp-dpl-product-title">'.$dp_posts_key.'</div>';
                foreach( $dp_posts_value as $dp_posts_ids ) {
                    $counter++;
                    if( count( $dp_posts_value ) == $counter )
                        continue;

                    $total_posts++;
                    $rdp_duplicate_ids .= $dp_posts_ids.',';
                    $html .= '<div class="rdp-duplicate-link"><a href="'. get_permalink( $dp_posts_ids ) .'">'.get_permalink( $dp_posts_ids ).'</a></div>';
                }

                $has_post++;
            }

            $rdp_duplicate_ids = rtrim($rdp_duplicate_ids,',');

            // Put Duplicate Ids in Hidden value to get it form Ajax/jQuery
            if( !empty($rdp_duplicate_ids) ) {
                $html .= '<input type="hidden" id="rdp-duplicate-ids" value="'.$rdp_duplicate_ids.'" />';
                $dp_msg = '<h2>'. ( $total_posts ) . __(' Duplicate Posts Found','rdp_domain') .'</h2>';
            } else
                $html .= '<div class="drp-response">'.__('No Duplicate Post Found <span class="dashicons dashicons-smiley"></span>','rdp_domain').'</div>';

            $html = $dp_msg . $html;
            echo $html;
        }
        wp_die();
    }

    /**
     * Getting Registered Post Types
     * @since: 1.0.0
     * @return available post types
     */
    function rdp_get_all_posts() {
        $args = array(
            'public'   => true,
            '_builtin' => false
        );

        $builtin = array(
            'post',
            'page',
            'attachment'
        );

        $output = 'names'; // names or objects, note names is the default
        $operator = 'and'; // 'and' or 'or'

        $post_types = get_post_types( $args, $output, $operator );
        $post_types = array_merge($builtin, $post_types);

        return $post_types;
    }
}