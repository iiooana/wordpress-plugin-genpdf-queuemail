<?php
/*
 * Plugin Name: Generate PDF
 * Description: Generate a PDF compiled of the order's data. View the history data of the "Contact Form Entries" plugin. 
 * Version: 0.3.6
 * Author: Ioana
 * Text Domain: genpdf-woocommerce
 * Domain Path: /languages
 * Requires PHP: 8.3
 * Requires at least: 6.0 
 */

use GenPDF\GenPDF;
use GenPDF\OrderGenPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use GenPDF\OrderEmailGenPDF;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once plugin_dir_path(__FILE__) . 'activate.php';
require_once plugin_dir_path(__FILE__) . 'digital_signature.php';
require_once plugin_dir_path(__FILE__) . 'class/GenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OldSubGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OrderEmailGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/AdminGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/TemplateGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OrderGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'cron.php';

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

function genpdf_css() {
	wp_enqueue_style('genpdf_css', plugin_dir_url(__FILE__) . "css/general.css", array(), '1.2');
}
add_action( 'wp_enqueue_scripts', 'genpdf_css' );


/**
 * Load the language, register new post type
 */
add_action('plugins_loaded', function () {
    //todo check
    load_plugin_textdomain('genpdf-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    new \GenPDF\AdminGenPDF();
});

add_action('woocommerce_checkout_update_order_meta', 'genpdf_add_extra_order_meta', 10, 1);
/**
 * @param $order_id
 * Save on DB meta data that are needed to PDF generation.
 */
function genpdf_add_extra_order_meta($order_id)
{
    global $wpdb;
    $genpdf = new GenPDF();
    //get the order
    $order = wc_get_order($order_id);
    //get the products of the order
    $products = $order->get_items();

    //region Save order_metadata
    if (!empty($products)) {
        //region add order email to queue
        new OrderEmailGenPDF($order_id);
        //endregion
        foreach ($products as $item) {
            $product = $item->get_product(); //this is a product variantion
            if (!empty($product) && !empty($product->get_parent_id())) {
                $array_product_metadata = [];
                $array_product_metadata['product_id'] = $product->get_id();
                $array_product_metadata['importo_totale'] = floatval($item->get_total()) + floatval($item->get_total_tax());

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
                    $template_id = $genpdf->getOption('template');
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

add_filter('woocommerce_admin_order_actions', 'genpdf_buttons_orders', 100, 2);

/**
 * @return html of buttons for download pdf
 */
function genpdf_buttons_orders($actions, $order)
{
    $timestamp =  $order->date_created->getTimestamp();
    //echo "order ".$order->id." -time".$timestamp;
    if (!empty($timestamp) && $timestamp >= 1745758835) {
        $genpdf_order = new OrderGenPDF($order->id);
        $products = $genpdf_order->getProductsDetail();
        // genpdf_vardie($products);
        if (!empty($products)) {
            foreach ($products as $item) {
                if (!empty($item['meta_value']) && json_validate($item['meta_value'])) {
                    $product = json_decode($item['meta_value'], ARRAY_A);
                    //genpdf_vardie($product);
                    if (!empty($product['titolo_corso_pdf']) && !empty($product['product_id'])) {
                        $actions[] = [
                            'url'    => admin_url('admin.php?page=genpdf_download_pdf&order_id=' . $order->id . "&product_id=" . $product['product_id']),
                            'name'   => 'Download PDF ' . $product['titolo_corso_pdf'],
                            'action' => 'genpdf_btn_download'
                        ];
                    }
                }
            }
        }
    }
    return $actions;
}
/**
 * Add a plugin page.
 */
function genpdf_add_pages()
{
    add_plugins_page(
        __('Download PDF', 'genpdf-woocommerce'),
        __('Download PDF', 'genpdf-woocommerce'),
        'manage_options',
        'genpdf_download_pdf',
        'genpdf_download_pdf'
    );
    add_plugins_page(
        __('TEST', 'genpdf-woocommerce'),
        __('TEST', 'genpdf-woocommerce'),
        'manage_options',
        'genpdf_test',
        'genpdf_test'
    );
}
add_action('admin_menu', 'genpdf_add_pages');
function genpdf_test(){
    //genpdf_vardie("ok");
    do_action('genpdf_cron');
}

function genpdf_download_pdf()
{
    if (
        is_admin() && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'genpdf_download_pdf'
        && !empty($_REQUEST['order_id']) && is_numeric($_REQUEST['order_id'])
        && !empty($_REQUEST['product_id']) && is_numeric($_REQUEST['product_id'])
    ) {
        ob_clean();
        $product_id = $_REQUEST['product_id'];
        $order = new OrderGenPDF(intval($_REQUEST['order_id']));
        $filename = "#" . $order->order_id . "_" . $product_id . "_modulo.pdf";

        $options_dompdf = new Options();
        $options_dompdf->set('defaultFont', 'helvetica');
        $options_dompdf->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options_dompdf);
        $dompdf->loadHtml($order->getPDF($product_id));
        $dompdf->render();
        $output = $dompdf->output();

        $temp_file = tmpfile(); //create temp file      
        fwrite($temp_file, $output); //add the content
        $file_metadata = stream_get_meta_data($temp_file);
        rewind($temp_file); //reset the pointer to the start of the file       
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Lenght: ' . filesize($file_metadata['uri']));
        fpassthru($temp_file); //output of the file
        fclose($temp_file); //close and delete
    }
}
