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
     * @version 1.1
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
                        
                        <div class="rdp-adv-setting">Advanced Settings</div>
                        <div class="rdp-adv-setting-content">
                            <div class="rdp-adv-opt">
                                <label for="rdp-match-title" class="rdp-match-title rdp-lbl">'.__('Match exact title','rdp_domain').'</label>
                                <input type="checkbox" class="rdp-match-title" id="rdp-match-title">
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
     * @version 1.2
     */
    function rdp_ajax_process() {
        global $wpdb;

        if( $_POST['target'] == 'remove_duplicates_posts' ) {
            $dp_ids = $_POST['duplicate_ids_to_remove'];
            $duplicate_ids = explode(',',$dp_ids);
            foreach( $duplicate_ids as $dp_id ) {
                if( !empty($dp_id) ) {
                    wp_delete_post( $dp_id, false );
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

            if( !empty( $_POST['title_contains'] ) ) {
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

            $args = array(
                'post_type' => $selected_post_type,
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order'   => 'ASC',
            );            

            $getting_all_posts = new WP_Query( $args );

            $total_posts = 0; 
            if ( $getting_all_posts->have_posts() ) {

                $found_duplicates = array();
                $html = ''; $skip = false;
                while ( $getting_all_posts->have_posts() ) {   
                    $getting_all_posts->the_post();

                    $operator = '=';
                    if( !empty( $_POST['title_contains'] ) ) {                        
                        $title = '%'.$_POST['title_contains'].'%';
                        $operator = 'LIKE';
                    } else if( $_POST['match_title'] == 'yes' ) {
                        $title = get_the_title();
                    } else {
                        $title = '%'.get_the_title().'%';
                        $operator = 'LIKE';
                    }

                    $current_post_ID = get_the_ID();
                    $skip = false;                    
                    
                    foreach( $found_duplicates as $duplicate_ids => $duplicates) {                        
                        if( $current_post_ID == $duplicate_ids ) {                            
                            $skip = true;
                            break;
                        }
                    }

                    if( $skip == true ) {                        
                        continue;
                    }

                    // Query to get all duplicate posts
                    $querystr = "
                        SELECT $wpdb->posts.ID
                        FROM $wpdb->posts
                        WHERE $wpdb->posts.post_title $operator '".$title."'
                        AND $wpdb->posts.post_type = '".$selected_post_type."'
                        ". $query_status ."
                        ORDER BY $wpdb->posts.post_date ASC";
                    
                    $posts_found = $wpdb->get_results($querystr, OBJECT);                                        
                    $post_count = 0;          
                    foreach( $posts_found as $post_ids ) {
                        $post_count++;
                        // Don't remove originla post
                        if( $post_count == 1 && empty( $_POST['title_contains'] ) ) {
                            continue;
                        }

                        $found_duplicates[$post_ids->ID] = get_the_title( $post_ids->ID );                       
                    }                                        
                }
                
                wp_reset_postdata();
            }

            // Make new array to store all the duplicate items            
            $has_post = 0; $rdp_duplicate_ids = ''; $dp_msg = '';

            if( count( $found_duplicates ) >= 1 ) {
                // If Duplicate Posts Exists
                foreach( $found_duplicates as $dup_post_id => $dp_posts_value ) {
                    
                    $html .= '<div class="rdp-dpl-product-title">'.$dp_posts_value.'</div>';            
                        $total_posts++; 
                        $rdp_duplicate_ids .= $dup_post_id.',';

                        $html .= '<div class="rdp-duplicate-link"><a href="'. get_permalink( $dup_post_id ) .'">'.get_permalink( $dup_post_id ).'</a></div>';
                        
                    $has_post++;
                }
                
                $rdp_duplicate_ids = rtrim($rdp_duplicate_ids,',');
            }
            
            // Put Duplicate Ids in Hidden value to get it form Ajax/jQuery
            if( !empty($rdp_duplicate_ids) && count( $found_duplicates ) >= 1 ) {
                $html .= '<input type="hidden" id="rdp-duplicate-ids" value="'.$rdp_duplicate_ids.'" />';
                $dp_msg = '<h2>'. ( $total_posts ) . __(' Duplicate Posts Found','rdp_domain') .'</h2>';
            } else {
                $html .= '<div class="drp-response">'.__('No Duplicate Posts Found <span class="dashicons dashicons-smiley"></span>','rdp_domain').'</div>';
            }
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