<?

namespace GenPDF;

use GenPDF\GenPDF;
use GenPDF\TemplateGenPDF\TemplateGenPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use GenPDF\OrderEmailGenPDF;

class OrderGenPDF
{

    private $order_id;
    private $template_id;
    private $created_at;
    private $updated_at;
    private $order;

    function __construct($order_id)
    {
        global $wpdb;
        //region check if order exist
        $table = $wpdb->base_prefix . "wc_orders";
        $query = $wpdb->prepare("SELECT 
            *
            FROM {$table}
            where id =  %d limit 1", [$order_id]);
        $row =  $wpdb->get_row($query, ARRAY_A);
        if (empty($row) || empty($row['id'])) {
            $message = var_export(["message" => "[ERROR-GENPDF002]The order_id does not exists.", "order_id" => $order_id], true);
            error_log($message);
            throw new \Exception($message, 1003);
        }
        $this->order = $row;
        //endregion
        $table = self::getTableName();

        $query = $wpdb->prepare("SELECT * FROM {$table} where order_id=%d limit 1", [$order_id]);
        $row = $wpdb->get_row($query, ARRAY_A);
        if (empty($row) || empty($row['order_id'])) {
            $message = var_export(["message" => "[ERROR-GENPDF003]Template of order_id not found.", "order_id" => $order_id], true);
            error_log($message);
            //throw new \Exception($message, 1002);
        } else {
            $this->order_id = $row['order_id'];
            $this->template_id =  $row['template_id'];
            $this->created_at =  $row['created_at'];
            $this->updated_at = $row['updated_at'];
        }
    }
    /**
     * @return array to the customer data for the pdf
     */
    private function getArrayData()
    {
        $gen_pdf = new GenPDF();

        $order_data = [];
        $order_data['logo_pdf'] = $gen_pdf->getLogo() ?? '';
        $order_data['ragione_sociale'] = '';
        $order_data['azienda_provincia'] = '';

        //region customer address
        $customer_address = $this->getCustomerAddress();
        if (!empty($customer_address)) {
            $order_data['nome'] = !empty($customer_address['first_name']) ? ucfirst($customer_address['first_name']) : '';
            $order_data['cognome'] = !empty($customer_address['last_name']) ? ucfirst($customer_address['last_name']) : '';
            $order_data['title'] = "#{$this->order_id} " . $order_data['nome'] . " " . $order_data['cognome'];
            $order_data['residente_in_via'] = $customer_address['residente_in_via'];
            $order_data['residenza_civico'] = $customer_address['residenza_civico'];
            $order_data['provincia_residenza'] = $customer_address['state'];
            $order_data['citta_residenza'] = $customer_address['citta_residenza'] . (!empty($customer_address['state']) ? ', ' . $customer_address['state'] : '');
            $order_data['cap_residenza'] = $customer_address['cap_residenza'];
            $order_data['email'] = $customer_address['email'];
            $order_data['cell'] = $customer_address['phone'];
            //region company
            if (!empty($customer_address['company'])  && strlen(trim($customer_address['company'])) > 0) {
                $order_data['ragione_sociale'] = $customer_address['company'];
            }
            //endregion           
        }
        //endregion

        //region order meta datas
        $order_meta_datas =  self::getOrderMetaData(['_billing_luogo_nascita', '_billing_data_nascita', '_billing_cf', '_billing_professione', '_billing_piva', '_billing_company_address', '_billing_company_address_number', '_billing_company_citty', '_billing_company_cap', '_billing_pec', '_billing_sdi', '_billing_company_provincia']);
        if (!empty($order_meta_datas)) {
            foreach ($order_meta_datas as $item) {
                match ($item['meta_key']) {
                    "_billing_luogo_nascita" => $order_data['luogo_nascita'] = $item['meta_value'],
                    "_billing_data_nascita" => $order_data['data_nascita'] = (!empty($item['meta_value']) ? date('d/m/Y', strtotime($item['meta_value'])) : ''),
                    "_billing_cf" => $order_data['codice_fiscale'] = $item['meta_value'],
                    "_billing_professione" => $order_data['professione'] = $item['meta_value'],
                    //region company
                    "_billing_piva" => $order_data['piva'] = $item['meta_value'],
                    "_billing_company_address" => $order_data['azienda_via'] = $item['meta_value'],
                    "_billing_company_address_number" => $order_data['æzienda_civico'] = $item['meta_value'],
                    "_billing_company_citty" => $order_data['azienda_citta'] = $item['meta_value'],
                    "_billing_company_cap" => $order_data['azienda_cap'] = $item['meta_value'],
                    "_billing_pec" => $order_data['azienda_pec'] = $item['meta_value'],
                    "_billing_sdi" => $order_data['azienda_sdi'] = $item['meta_value'],
                    "_billing_company_provincia" => $order_data['azienda_provincia'] = $item['meta_value'],
                    //endregion
                };
            }
        }
        //endregion
        $order_data['note'] = $this->order['customer_note'];
        $order_data['metodo_pagamento'] = $this->order['payment_method_title'];


        $order_data['data'] = $this->order['date_created_gmt'] ? date('d/m/Y', strtotime($this->order['date_created_gmt'])) : '';
        $signed_path = $this->getbase64Signature();
        if (!empty($signed_path)  && boolval($signed_path)) {
            $order_data['firma'] = '<img stlye="margin-left: 15px" src="data:image/png;base64, ' . $signed_path . '" width="240px">';
        } else {
            $order_data['firma'] = '___________________';
        }

        //region checkbox
        $checkbox_newsletter = $this->getOrderMetaData(['billing_newsletter']);
        if (!empty($checkbox_newsletter) && !empty($checkbox_newsletter[0]) && !empty($checkbox_newsletter[0]['meta_value'])) {
            $order_data['checked_newsletter_si'] = 'checked';
        } else {
            $order_data['checked_newsletter_no'] = 'checked';
        }

        $checkbox_riprese = $this->getOrderMetaData(['billing_shooting']);
        if (!empty($checkbox_riprese) && !empty($checkbox_riprese[0]) && !empty($checkbox_riprese[0]['meta_value'])) {
            $order_data['checked_riprese_si'] = 'checked';
        } else {
            $order_data['checked_riprese_no'] = 'checked';
        }

        $checkbox_gruppo_cell = $this->getOrderMetaData(['billing_gruppo_cell']);
        if (!empty($checkbox_gruppo_cell) && !empty($checkbox_gruppo_cell[0]) && !empty($checkbox_gruppo_cell[0]['meta_value'])) {
            $order_data['checked_gruppo_cell_si'] = 'checked';
        } else {
            $order_data['checked_gruppo_cell_no'] = 'checked';
        }

        //endregion

        return $order_data;
    }
    /**
     * @return array with product metadata linked to the order
     */
    private function getOrderProductData(int $product_id)
    {
        $order_data = [];

        $products = self::getProductsDetail();
        if (!empty($products)) {
            foreach ($products as $item) {
                if (!empty($item['meta_value']) && json_validate($item['meta_value'])) {
                    $product = json_decode($item['meta_value'], ARRAY_A);
                    if (intval($product['product_id']) == $product_id) {
                        //region tabella extra
                        $order_data['tabella_extra'] = self::getHTMLTabellaExtra($product);
                        //genpdf_vardie($order_data);
                        //endregion
                        //genpdf_vardie($product);
                        $order_data['titolo_corso_pdf'] = $product['titolo_corso_pdf'];
                        $order_data['giorno_generico_settimana'] = $product['giorno_generico_settimana'];
                        $order_data['anno_accademico'] = $product['anno_accademico'];
                        $order_data['luogo_del_corso'] = !empty($product['luogo_del_corso']) ?  $order_data['luogo_del_corso'] = $order_data['luogo'] = ucfirst($product['luogo_del_corso']) : '';

                        //region crediti ecm
                        $crediti_richiesti = self::getOrderMetaData(['creditiecm']);
                        if (!empty($crediti_richiesti[0]) && !empty($crediti_richiesti[0]['meta_value']) && !empty($product['crediti_ecm'])) {
                            $order_data['checked_crediti'] = 'checked';
                        }
                        //endregion

                        //region acconto or totale
                        if (!empty($product['acconto_o_totale']) && strpos($product['acconto_o_totale'], 'acconto') !== false) {
                            $order_data['importo_acconto']  = number_format($product['importo_totale'], 2, ",");
                            $totale = floatval($product['importo_totale']);
                            //region months
                            if (!empty($product['importo_mese'])) {
                                $order_data['td_mesi_nome'] = '';
                                $order_data['td_mesi_importi'] = '';
                                foreach ($product['importo_mese'] as $nome_mese => $price) {
                                    if (is_float($price) || is_numeric($price)) {
                                        $totale += floatval($price);
                                        $order_data['td_mesi_nome'] .= '<td class="destra">' . strtoupper($nome_mese) . '</td>';
                                        $order_data['td_mesi_importi'] .= '<td class="destra">€ ' . number_format($price, 2, ",") . '</td>';
                                    }
                                }
                            }
                            //endregion
                            $order_data['importo_totale'] = number_format($totale, 2, ",");
                        } else {
                            $order_data['importo_acconto'] = 0;
                            $order_data['importo_totale'] = number_format($product['importo_totale'], 2, ",");
                        }
                        //endregion
                        break;
                    }
                }
            }
        }

        return $order_data;
    }

    /**
     * @return an array with metadata of the order
     */
    private function getOrderMetaData(array|null $param_array = [])
    {
        global $wpdb;
        $table = $wpdb->base_prefix . "wc_orders_meta";
        if (empty($param_array)) {
            $query = $wpdb->prepare("SELECT  * FROM {$table} where  order_id = %d", [$this->order_id]);
        } else {
            $placeholders = implode(',', array_fill(0, count($param_array), '%s'));
            $query = $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d AND meta_key in ($placeholders) ", array_merge([$this->order_id], $param_array));
        }
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * @return an array with customer info address
     */
    private function getCustomerAddress()
    {
        global $wpdb;
        $table = $wpdb->base_prefix . "wc_order_addresses";
        $query = $wpdb->prepare("SELECT 
        first_name,
        last_name,
        address_1 as residente_in_via,
        address_2 as residenza_civico,
        city as citta_residenza,
        state,
        postcode as cap_residenza,
        email,
        phone,
        company        
        FROM {$table} WHERE order_id = %d LIMIT 1", [$this->order_id]);
        return $wpdb->get_row($query, ARRAY_A);
    }

    /**
     * @return base64 of the signature
     */
    private function getbase64Signature()
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT ID FROM  wp_posts where ID = ( SELECT meta_value FROM `wp_postmeta` WHERE post_id=%d and meta_key=%s limit 1 ) AND post_type=%s limit 1 ", [$this->order_id, 'signpad', 'attachment']);
        $row = $wpdb->get_row($query, ARRAY_A);
        if (!empty($row) && !empty($row['ID'])) {
            $meta = get_post_meta($row['ID'], '_wp_attached_file');
            $genpdf_folder = apply_filters('genpdf_get_signature_folder', '');
            //genpdf_vardie($genpdf_folder['basedir'].$meta[0]);
            if (!empty($meta[0]) && file_exists($genpdf_folder['basedir'] . $meta[0])) {
                return base64_encode(file_get_contents($genpdf_folder['basedir'] . $meta[0]));
            }
        }
        return null;
    }
    /**
     * TABLE DOVE - QUANDO - CREDITI
     * @return html of this table built it dynamically
     */
    private function getHTMLTabellaExtra($product)
    {
        $tmp['header'] = [];
        $tmp['body'] = [];
        if (!empty($product['tabella_extra']) && is_array($product['tabella_extra'])) {
            foreach ($product['tabella_extra'] as $column_name => $value) {
                if (!empty($value[0])) {
                    switch ($column_name) {
                        case "has_column_where":
                            $tmp['header'][] = '<th style="color: #fff;text-align: center;font-size:14pt;">DOVE</th>';
                            $tmp['body'][] = '<td class="destra">
                                <div style="display: flex; vertical-align:middle;">
                                    <input type="checkbox" checked>[luogo_del_corso]
                                </div>
                            </td>';
                            break;
                        case "has_column_when":
                            $tmp['header'][] = '<th style="color: #fff;text-align: center;font-size:14pt;">QUANDO</th>';
                            $tmp['body'][] = '<td class="destra">
                                <div style="display: flex; vertical-align:middle;">
                                    <input type="checkbox" checked>[giorno_generico_settimana]
                                </div>
                            </td>';
                            break;
                        case "has_column_ecm":
                            $tmp['header'][] = '<th style="color: #fff;text-align: center;font-size:14pt;">CREDITI</th>';
                            $tmp['body'][] = '<td class="destra">
                                <div style="display: flex; vertical-align:middle;">
                                   <input type="checkbox" [checked_crediti]>CREDITI ECM
                                </div>
                            </td>';
                            break;
                    }
                }
            }
        }
      
        if(empty($tmp['header']) || empty($tmp['body'])){
            return '';
        }
        return '<table style="margin-top:5px">
                <thead>
                    <tr style="background-color: #1e87ac;font-size:14pt;">
                        '.implode("",$tmp['header']).'
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        '.implode("",$tmp['body']).'
                    </tr>
                </tbody>
            </table>';

    }

    /**
     * @return table name of the model
     */
    private function getTableName()
    {
        return GenPDF::getFullPrefix() . "_orders_template";
    }

    /**
     * magic method 
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    /**
     * @return the html of the order
     */
    public function getPDF(int $product_id)
    {
        $html = null;
        $template = new TemplateGenPDF($this->template_id);
        $order_data =  array_merge(self::getArrayData(), self::getOrderProductData($product_id));
        $html = $template->html;
        if (!empty($order_data) && count($order_data) > 0) {
            foreach ($order_data  as $key => $value) {
                $html = str_replace("[$key]", $value, $html);
            }
        }
        return $html;
    }

    /**
     * TODO: improve by filterd with json format in query
     * @return products info
     */
    public function getProductsDetail()
    {
        global $wpdb;
        $table = $wpdb->prefix . "wc_orders_meta";
        $query = $wpdb->prepare("select meta_value from {$table} where order_id =  %d and meta_key =  %s ", [$this->order_id, 'product_detail']);
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * @return if the payment menthod is bank trasnfer
     */
    public function isBonifico()
    {
        return (!empty($this->order['payment_method_title']) &&
            is_string($this->order['payment_method_title']) &&
            strpos(strtolower($this->order['payment_method_title']), 'bonifico') !== false) ? true : false;
    }

    /**
     * Generate the attachment on dir_pdf.
     * @return $array with files path
     */
    public function getAttachmentsPDF(string $dir_pdf)
    {
        $attachments =  [];
        $options_dompdf = new Options();
        $options_dompdf->set('defaultFont', 'helvetica');
        $options_dompdf->set('isRemoteEnabled', true);
        $products = $this->getProductsDetail();

        if (!empty($products)) {
            foreach ($products as $product) {
                if (!empty($product['meta_value']) && json_validate($product['meta_value'])) {
                    $product_json = json_decode($product['meta_value'], true);
                   
                    ob_clean();
                    $dompdf = new Dompdf($options_dompdf);
                   
                    $dompdf->loadHtml($this->getPDF($product_json['product_id']));
                    $dompdf->render();
                    $output = $dompdf->output();
                    $tmp_path = tempnam($dir_pdf, "ordine#" . $this->order_id . "_") . '.pdf';
                    file_put_contents($tmp_path, $output);
                    $attachments[] = $tmp_path;
                    ob_end_clean();
                }
            }
        } else {
            OrderEmailGenPDF::UpdateOrderEmail($this->order_id, [
                "status" => "error",
                "message" => "There aren't any products into the order."
            ]);
        }
        return $attachments;
    }

    /**
     * delete the attachments
     */
    public function deleteAttachments(array $attachments)
    {
        if (!empty($attachments) && is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $file) {
                unlink($file);
            }
        }
    }

    /**
     * @return the customer address info filtered by order
     */
    public function getCustomerInfo()
    {
        global $wpdb;
        $table = $wpdb->base_prefix . "wc_order_addresses";
        $query = $wpdb->prepare("SELECT * from {$table} where order_id =  %d limit 1", [$this->order_id]);
        return $wpdb->get_row($query, ARRAY_A);
    }
}
