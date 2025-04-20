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

use GenPDF\TemplateGenPDF\TemplateGenPDF;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once plugin_dir_path(__FILE__) . 'activate.php';
require_once plugin_dir_path(__FILE__) . 'class/GenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OldSubGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/AdminGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/TemplateGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OrderGenPDF.php';

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

add_action('plugins_loaded', function () {
    load_plugin_textdomain('genpdf-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    new \GenPDF\AdminGenPDF();
});

add_action('woocommerce_checkout_update_order_meta', 'add_extra_order_meta', 10, 1);
/**
 * @param $order_id
 */
function add_extra_order_meta($order_id)
{
    global $wpdb;
    //get the order
    $order = wc_get_order($order_id);
    //get the products of the order
    $products = $order->get_items();
    //on the website it is possbile to buy only one product at time.
    if (count($products) > 1) {
        $message = var_export(['message' => 'The order has more than one product, this is a logical error.', 'order_id' => $order_id], true);
        error_log($message);
    }

    //region Save order_metadata
    if (!empty($products)) {
        foreach ($products as $item) {
            //genpdf_vardie($item);
            $product = $item->get_product();
            if (!empty($product) && !empty($product->get_parent_id())) {
                //region giorno_generico_settimana
                if( !empty($product->attributes['pa_data']) ){
                    $wpdb->insert(
                        $wpdb->base_prefix . 'wc_orders_meta',
                        [
                            "order_id" => $order_id,
                            "meta_key" => "giorno_generico_settimana",
                            "meta_value" => $product->attributes['pa_data']
                        ],
                        ['%d', '%s', '%s']);
                }
                //endregion

                $id_product = $product->get_parent_id();

                if (!empty($id_product)) {
                    //region set the template
                    $template_id = TemplateGenPDF::getIdTemplateByProduct($id_product);
                    if(!empty($template_id)){
                      $wpdb->insert(
                            $wpdb->base_prefix."genpdf_orders_template",
                            [
                                "order_id" => $order_id,
                                "template_id" => $template_id
                            ],['%d','%d']);                            
                    }else{
                        $message = var_export(['message'=>'Errore non trovo il template per product_id.','product_id' => $id_product],true);
                        error_log($message);
                    }                  
                    //endregion

                    //region luogo
                    $meta_value = get_field('luogoluoghi_corso', $id_product);
                    $wpdb->insert(
                        $wpdb->base_prefix . 'wc_orders_meta',
                        [
                            "order_id" => $order_id,
                            "meta_key" => "luogo_del_corso",
                            "meta_value" => $meta_value
                        ],
                        ['%d', '%s', '%s']);
                    //endregion

                    //region anno accademico
                    $meta_value = get_field('anno_accademico', $id_product);
                    $wpdb->insert(
                        $wpdb->base_prefix . 'wc_orders_meta',
                        [
                            "order_id" => $order_id,
                            "meta_key" => "anno_accademico",
                            "meta_value" => $meta_value
                        ],
                        ['%d', '%s', '%s']);
                    //endregion

                    //region importi mensili
                    $tabella_importi_prodotto = get_field('tabella_importo_mese', $id_product);
                    $mesi = [];
                    if (!empty($tabella_importi_prodotto)) {
                        //genpdf_vardie($tabella_importi_prodotto);
                        foreach ($tabella_importi_prodotto as $key => $value) {
                            //genpdf_vardie($key);
                            if (!empty($value) && floatval($value) > 0) {
                                match ($key) {
                                    "importo_gennaio" => $mesi['gennaio'] = $value,
                                    "importo_febbrario" => $mesi['febbraio'] = $value,
                                    "importo_marzo" => $mesi['marzo'] = $value,
                                    "importo_aprile" => $mesi['aprile'] = $value,
                                    "importo_maggio" => $mesi['maggio'] = $value,
                                    "importo_giugno" => $mesi['giugno'] = $value,
                                    "importo_luglio" => $mesi['luglio'] = $value,
                                    "importo_agosto" => $mesi['agosto'] = $value,
                                    "importo_settembre" => $mesi['settembre'] = $value,
                                    "importo_ottobre" => $mesi['ottobre'] = $value,
                                    "importo_novembre" => $mesi['novembre'] = $value,
                                    "importo_dicembre" => $mesi['dicembre'] = $value
                                };
                            }
                        }
                    }

                    $wpdb->insert(
                        $wpdb->base_prefix . 'wc_orders_meta',
                        [
                            "order_id" => $order_id,
                            "meta_key" => "importo_mese",
                            "meta_value" => json_encode($mesi)
                        ],
                        ['%d', '%s', '%s']
                    );
                    //endregion

                }
            }
            break;
        }
    }
    //endregion
}
