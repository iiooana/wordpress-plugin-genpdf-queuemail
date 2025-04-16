<?php
/*
 * Plugin Name: Generate PDF
 * Description: Generate PDF from orders and send emails in a queue. This plugin is customized to be compatible with "Contact Form Entries". 
 * Version: 0.2.0
 * Author: Ioana
 * Text Domain: genpdf-woocommerce
 * Domain Path: /languages
 */

 //TODO ADD * Requires at least: 6.0 * Requires PHP: 8.3

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once plugin_dir_path(__FILE__) .'activate.php';
require_once plugin_dir_path(__FILE__) .'class/GenPDF.php';
require_once plugin_dir_path(__FILE__) .'class/OldSubGenPDF.php';
require_once plugin_dir_path(__FILE__) .'class/AdminGenPDF.php';

function genpdf_vardie(){
    echo "<pre>";
    var_dump(func_get_args());
    die();
    
}
function genpdf_getPath(){
    return  plugin_dir_path(__FILE__);
}

define('WP_DEBUG', true);
register_activation_hook(__FILE__ ,'genpdf_active');

add_action('plugins_loaded', function() {    
    load_plugin_textdomain('genpdf-woocommerce',false, dirname(plugin_basename(__FILE__)). '/languages');
    new \GenPDF\AdminGenPDF();
});
