<?php
/**
 * Plugin Name: Remove Duplicate Posts
 * Version: 1.0.0
 * Description: You can easily remove duplicate posts of your site with one click. Just you need to select a post type which you want to remove the duplicates, and hit the delete button. This will remove all your duplicate posts with clean way.
 * Author: Muhammad Rehman
 * Author URI: http://muhammadrehman.com/
 * License: GPLv2 or later
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

define('RDP_PLUGIN_URL',plugin_dir_url( __FILE__ ));

include 'inc/class-rdp-main-screen.php';
$rdp_main_screen = new RDP_Main_Screen();

function rdp_admin_scripts_styles() {
    wp_enqueue_style( 'rdp-admin-style', RDP_PLUGIN_URL . 'assets/css/style.css' );
    wp_enqueue_script( 'rdp-admin-script', RDP_PLUGIN_URL . 'assets/js/rdp-script.js' );
}
add_action( 'admin_enqueue_scripts', 'rdp_admin_scripts_styles' );