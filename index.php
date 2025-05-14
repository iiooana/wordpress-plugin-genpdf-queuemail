<?php
/*
 * Plugin Name: Generate PDF
 * Description: Generete PDF from customer data and product data. Manage the queue of emails with attachments.
 * Version: 0.4.3
 * Author: Ioana
 * Text Domain: genpdf-woocommerce
 * Domain Path: /languages
 * Requires PHP: 8.3
 * Requires at least: 6.0 
 */


require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once plugin_dir_path(__FILE__) . 'activate.php';
require_once plugin_dir_path(__FILE__) . 'digital_signature.php';
require_once plugin_dir_path(__FILE__) . 'class/TemplateEmailGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/GenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OldSubGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OrderEmailGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/AdminGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/TemplateGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'class/OrderGenPDF.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'cron.php';

use GenPDF\GenPDF;
use GenPDF\OrderGenPDF;
use GenPDF\AdminGenPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use GenPDF\OrderEmailGenPDF;

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

function genpdf_assets()
{
    wp_enqueue_style('genpdf_css', plugin_dir_url(__FILE__) . "css/general.css", array(), '1.5');
}
add_action('admin_init', 'genpdf_assets');

function genpdf_register_post_type()
{
    AdminGenPDF::register_post_type();
}
add_action('init', 'genpdf_register_post_type');


add_filter('pre_trash_post', 'genpdf_prevent_delete_template', 10, 2);
function genpdf_prevent_delete_template($trash, $post) {
    if ($post->post_type === 'genpdf_template') {
        $genpdf = new GenPDF;
        if($post->ID == $genpdf->getOption('admin_email_template') || $post->ID == $genpdf->getOption('customer_email_template')){
            wp_redirect(add_query_arg('genpdf_error_delete_template', '1', admin_url('edit.php?post_type=genpdf_template')));
            exit;
        }        
    }
    return $trash;
}

add_action('admin_notices', 'genpdf_alerts');
function genpdf_alerts() {
    if (isset($_GET['genpdf_error_delete_template'])) { ?>
         <div class="notice notice-error is-dismissible"><p><?=__("You can't trash the template beacuse it is used, to change go into","genpdf-woocommerce")?> <em> <?=__('GenPDF settings', 'genpdf-woocommerce')?> </em>.</p></div>
    <? }
}

/**
 * Load the language, register new post type
 */
add_action('plugins_loaded', function () {
    //todo check
    load_plugin_textdomain('genpdf-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    new \GenPDF\AdminGenPDF();
});


/**
 * @param $order_id
 * Save on DB meta data that are needed to PDF generation.
 */
function genpdf_add_extra_order_meta($order_id)
{
    global $wpdb;
    $genpdf = new GenPDF();
    $order = wc_get_order($order_id);
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
                    $array_product_metadata['tabella_extra'] = get_field('tabella_extra', $id_product);

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
add_action('woocommerce_checkout_update_order_meta', 'genpdf_add_extra_order_meta', 10, 1);
/**
 * @return html of buttons for download pdf
 */
function genpdf_buttons_orders($actions, $order)
{
    $timestamp =  $order->date_created->getTimestamp();
    $array_status = OrderEmailGenPDF::getListAcceptsStatus();
    if (!empty($timestamp) && $timestamp > 1745843280) {
        $genpdf_order = new OrderGenPDF($order->id);
        $products = $genpdf_order->getProductsDetail();
        if (!empty($products)) {
            foreach ($products as $item) {
                if (!empty($item['meta_value']) && json_validate($item['meta_value'])) {
                    $product = json_decode($item['meta_value'], ARRAY_A);
                    $titolo_del_corso = $product['titolo_corso_pdf'];
                    if(strlen($titolo_del_corso) > 17){
                        $titolo_del_corso = substr($titolo_del_corso,0,17)."...";
                    }
                    if (!empty($product['titolo_corso_pdf']) && !empty($product['product_id'])) {
                        $actions[] = [
                            'url'    => admin_url('admin.php?page=genpdf_download_pdf&order_id=' . $order->id . "&product_id=" . $product['product_id']),
                            'name'   => 'Download PDF ' . $titolo_del_corso,
                            'action' => 'genpdf_btn_download'
                        ];
                    }
                }
            }
            //region button to sentemail customer
            if (!empty($order->status) && in_array($order->status, $array_status)) {
                $actions[] = [
                    "url" => admin_url('admin.php?page=genpdf_send_attachments&order_id=' . $order->id),
                    "name" => __("Sent attacchments to customer","genpdf-woocommerce"),
                    "action" => "genpdf_btn_send_attachments"
                ];
            }

            //endregion
        }
    }
    return $actions;
}
add_filter('woocommerce_admin_order_actions', 'genpdf_buttons_orders', 100, 2);

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
/**
 * Create new email to queue for order manually by user
 */
function genpdf_send_attachments()
{
    if (
        is_admin() && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'genpdf_send_attachments'
        && !empty($_REQUEST['order_id']) && is_numeric($_REQUEST['order_id'])
    ) {
        $add_info = ['message' => "Aggiunto alla coda manualmente dal id_utente_wp = " . get_current_user_id() ];
        $is_updated = OrderEmailGenPDF::addEmailQueueUser(intval($_REQUEST['order_id']),$add_info);
        if ($is_updated === false) {
            printf("<h1>%s.</h1>",__("An error occurs, contact the support","genpdf-woocommerce"));
        } else {
            printf("<h1>%s.</h1><a class='button' href='%s'>%s</a>",__("In a few minutes, the email will be sent","genpdf-woocommerce"),$_SERVER['HTTP_REFERER'],__("Go back","genpdf-woocommerce"));
            
        }
    }
}

/**
 * Create new email to queue for order manually by user
 */
function genpdf_add_queue_email($order_id,$status_from,$status_to){
    if(is_admin() && !empty($_REQUEST['action']) && in_array($_REQUEST['action'],['edit_order','woocommerce_mark_order_status']) && $status_to == 'completed'){
        $add_info = ['message' => "Email added at the queue, id_user_wp = " . get_current_user_id()." has changed the order status = ".$status_to ];
        OrderEmailGenPDF::addEmailQueueUser($order_id,$add_info);
    }
}
add_action("woocommerce_order_status_changed","genpdf_add_queue_email",40,3);