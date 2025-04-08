<?php
/*
 * Plugin Name: Generate PDF
 * Description: Generate PDF from orders and send emails in a queue.
 * Version: 0.1.0
 * Author: Ioana
 */

 //TODO ADD * Requires at least: 6.0 * Requires PHP: 8.3

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once plugin_dir_path(__FILE__) .'activate.php';
require_once plugin_dir_path(__FILE__) .'class/GenPDF.php';


function vardie(){
    echo "<pre>";
    var_dump(func_get_args());
    die();
}

register_activation_hook(__FILE__ ,'genpdf_active');