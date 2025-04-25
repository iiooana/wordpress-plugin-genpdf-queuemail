<?php
/*
 * Plugin Name: Generate PDF
 * Description: Generate a PDF compiled of the order's data. View the history data of the "Contact Form Entries" plugin. 
 * Version: 0.3.0
 * Author: Ioana
 * Text Domain: genpdf-woocommerce
 * Domain Path: /languages
 * Requires PHP: 8.3
 * Requires at least: 6.0 
 */

use GenPDF\TemplateGenPDF\TemplateGenPDF;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once plugin_dir_path(__FILE__) . 'activate.php';
require_once plugin_dir_path(__FILE__) . 'class/GenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OldSubGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/AdminGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/TemplateGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OrderGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

function genpdf_vardie()
{
    echo "<pre>";
    var_dump(func_get_args());
    die();
}
function genpdf_getPath()
{
    return  plugin_dir_path(__FILE__);
}

register_activation_hook(__FILE__, 'genpdf_active');

/**
 * Load the language, register new post type
 */
add_action('plugins_loaded', function () {
    load_plugin_textdomain('genpdf-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    new \GenPDF\AdminGenPDF();
});

add_action('woocommerce_checkout_update_order_meta', 'add_extra_order_meta', 10, 1);
/**
 * @param $order_id
 * Save on DB meta data that are needed to PDF generation.
 */
function add_extra_order_meta($order_id)
{
    global $wpdb;
    //get the order
    $order = wc_get_order($order_id);
    //get the products of the order
    $products = $order->get_items();

    //region Save order_metadata
    if (!empty($products)) {
        foreach ($products as $item) {
            $product = $item->get_product(); //this is a product variantion
            if (!empty($product) && !empty($product->get_parent_id())) {
                $array_product_metadata = [];
                $array_product_metadata['product_id'] = $product->get_id();               

                //region giorno_generico_settimana
                if (!empty($product->attributes['pa_data'])) {
                    $array_product_metadata['giorno_generico_settimana'] = $product->attributes['pa_data'];
                }
                //endregion
                //region acconto or totale
                if (!empty($product->attributes['pa_pagamento'])) {
                    $array_product_metadata['acconto_o_totale'] = $product->attributes['pa_pagamento'];
                }
                //endregion

                $id_product = $product->get_parent_id();
                if (!empty($id_product)) {
                    $array_product_metadata['parent_product_id'] = $id_product;
                    //region set the template
                    $template_id = TemplateGenPDF::getIdTemplateByProduct($id_product);
                    if (!empty($template_id)) {
                        $wpdb->insert(
                            $wpdb->base_prefix . "genpdf_orders_template",
                            [
                                "order_id" => $order_id,
                                "template_id" => $template_id
                            ],
                            ['%d', '%d']
                        );
                    } else {
                        $message = var_export(['message' => 'Errore non trovo il template per product_id.', 'product_id' => $id_product], true);
                        error_log($message);
                    }
                    //endregion

                    //region product field
                    $array_product_metadata['crediti_ecm'] = get_field('crediti_ecm', $id_product);
                    $array_product_metadata['luogo_del_corso'] = get_field('luogoluoghi_corso', $id_product);
                    $array_product_metadata['anno_accademico'] = get_field('anno_accademico', $id_product);
                    $array_product_metadata['titolo_corso_pdf'] = get_field('titolo_corso_pdf', $id_product);
                        //region importi mensili
                        $tabella_importi_prodotto = get_field('tabella_importo_mese', $id_product);
                        if (!empty($tabella_importi_prodotto)) {
                            foreach ($tabella_importi_prodotto as $key => $value) {
                                if (!empty($value) && floatval($value) > 0) {
                                    match ($key) {
                                        "importo_gennaio" => $array_product_metadata['importo_mese']['gennaio'] = $value,
                                        "importo_febbrario" => $array_product_metadata['importo_mese']['febbraio'] = $value,
                                        "importo_marzo" => $array_product_metadata['importo_mese']['marzo'] = $value,
                                        "importo_aprile" => $array_product_metadata['importo_mese']['aprile'] = $value,
                                        "importo_maggio" => $array_product_metadata['importo_mese']['maggio'] = $value,
                                        "importo_giugno" => $array_product_metadata['importo_mese']['giugno'] = $value,
                                        "importo_luglio" => $array_product_metadata['importo_mese']['luglio'] = $value,
                                        "importo_agosto" => $array_product_metadata['importo_mese']['agosto'] = $value,
                                        "importo_settembre" => $array_product_metadata['importo_mese']['settembre'] = $value,
                                        "importo_ottobre" => $array_product_metadata['importo_mese']['ottobre'] = $value,
                                        "importo_novembre" => $array_product_metadata['importo_mese']['novembre'] = $value,
                                        "importo_dicembre" => $array_product_metadata['importo_mese']['dicembre'] = $value
                                    };
                                }
                            }                        
                        }                    
                        //endregion
                    //endregion                
                    
                    //region insert to db
                    $wpdb->insert(
                        $wpdb->base_prefix . 'wc_orders_meta',
                        [
                            "order_id" => $order_id,
                            "meta_key" => "product_detail",
                            "meta_value" => json_encode($array_product_metadata)
                        ],
                        ['%d', '%s', '%s']
                    );
                    //endregion
                }
            }
        }
    }
    //endregion
}
