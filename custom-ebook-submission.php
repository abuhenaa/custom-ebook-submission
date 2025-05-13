<?php
/*
Plugin Name: Custom Ebook Submission
Description: A custom plugin to handle ebook submissions.
Version: 1.0
Author: Abu Hena
Author URI: https://www.example.com
License: GPL2
Text Domain: custom-ebook-submission
*/
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//define constants
define( 'CES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

//Autoload classes
spl_autoload_register( function ( $class_name ) {
     if( strpos( $class_name, 'CES_' ) === 0 ){
        $filename = CES_PLUGIN_DIR . 'includes/class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
        if( file_exists( $filename ) ){
            include $filename;
        }
     }
} );

// Include helpers
require_once CES_PLUGIN_DIR . 'includes/helpers.php';

//init classes
function ces_init_plugin(){
    new CES_Form_Renderer();
    new CES_Form_Handler();
    //new CES_Form_Blacklist();
}
add_action( 'plugins_loaded', 'ces_init_plugin' );