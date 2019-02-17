<?php
/**
 * Plugin Name: Remove Duplicate Posts
 * Version: 1.0.3
 * Description: You can easily remove duplicate posts of your site with one click. Just you need to select a post type which you want to remove the duplicates, and hit the delete button. This will remove all your duplicate posts with clean way.
 * Author: Muhammad Rehman
 * Author URI: http://muhammadrehman.com/
 * License: GPLv2 or later
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

define('RDP_PLUGIN_URL',plugin_dir_url( __FILE__ ));
define('RDP_PLUGIN_BASENAME',plugin_basename( __FILE__ ));

include 'inc/class-rdp-main-screen.php';
$rdp_main_screen = new RDP_Main_Screen();

function rdp_admin_scripts_styles() {
    wp_enqueue_style( 'rdp-admin-style', RDP_PLUGIN_URL . 'assets/css/style.css' );
    wp_enqueue_script( 'rdp-admin-script', RDP_PLUGIN_URL . 'assets/js/rdp-script.js' );
}
add_action( 'admin_enqueue_scripts', 'rdp_admin_scripts_styles' );

// Create a helper function for easy SDK access.
function rdp_fs() {
    global $rdp_fs;

    if ( ! isset( $rdp_fs ) ) {
        // Include Freemius SDK.
        require_once dirname(__FILE__) . '/freemius/start.php';

        $rdp_fs = fs_dynamic_init( array(
            'id'                  => '2260',
            'slug'                => 'remove-duplicate-posts',
            'type'                => 'plugin',
            'public_key'          => 'pk_e391250b5c216d4c1c2b716efb988',
            'is_premium'          => false,
            'has_addons'          => false,
            'has_paid_plans'      => false,
            'menu'                => array(
                'account'        => false,
                'contact'        => false,
                'support'        => false,
            ),
        ) );
    }

    return $rdp_fs;
}

// Init Freemius.
rdp_fs();
// Signal that SDK was initiated.
do_action( 'rdp_fs_loaded' );