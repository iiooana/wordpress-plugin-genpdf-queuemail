<?php
/*
 * Plugin Name: Generate PDF
 * Description: Generate a PDF compiled of the order's data. View the history data of the "Contact Form Entries" plugin. 
 * Version: 0.2.1
 * Author: Ioana
 * Text Domain: genpdf-woocommerce
 * Domain Path: /languages
 * Requires PHP: 8.3
 * Requires at least: 6.0 
 */

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once plugin_dir_path(__FILE__) .'activate.php';
require_once plugin_dir_path(__FILE__) .'class/GenPDF.php';
require_once plugin_dir_path(__FILE__) .'class/OldSubGenPDF.php';
require_once plugin_dir_path(__FILE__) .'class/AdminGenPDF.php';
require_once plugin_dir_path(__FILE__) .'class/TemplateGenPDF.php';
require_once plugin_dir_path(__FILE__) .'class/OrderGenPDF.php';

function genpdf_vardie(){
    echo "<pre>";
    var_dump(func_get_args());
    die();
    
}
function genpdf_getPath(){
    return  plugin_dir_path(__FILE__);
}

register_activation_hook(__FILE__ ,'genpdf_active');

add_action('plugins_loaded', function() {    
    load_plugin_textdomain('genpdf-woocommerce',false, dirname(plugin_basename(__FILE__)). '/languages');
    new \GenPDF\AdminGenPDF();
});
