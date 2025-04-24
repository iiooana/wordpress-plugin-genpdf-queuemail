<?

namespace GenPDF;

use GenPDF\GenPDF;
use GenPDF\TemplateGenPDF\TemplateGenPDF;

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
            $message = var_export(["message" => "The order_id does not exists.", "order_id" => $order_id], true);
            error_log($message);
            throw new \Exception($message, 1003);
        }
        $this->order = $row;
        //endregion
        $table = self::getTableName();

        $query = $wpdb->prepare("SELECT * FROM {$table} where order_id=%d limit 1", [$order_id]);
        $row = $wpdb->get_row($query, ARRAY_A);
        if (empty($row) || empty($row['order_id'])) {
            $message = var_export(["message" => "Template of order_id not found.", "order_id" => $order_id], true);
            error_log($message);
            throw new \Exception($message, 1002);
        }
        $this->order_id = $row['order_id'];
        $this->template_id =  $row['template_id'];
        $this->created_at =  $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    public function getPDF()
    {
        $html = null;
        $template = new TemplateGenPDF($this->template_id);
        $order_data =  self::getArrayData();
        $html = $template->html;
        if (!empty($order_data) && count($order_data) > 0) {
            //  genpdf_vardie($order_data);
            foreach ($order_data  as $key => $value) {
                $html = str_replace("[$key]", $value, $html);
            }
        }
        return $html;
    }

    private function getArrayData()
    {
        $gen_pdf = new GenPDF();

        $order_data = [];
        $order_data['logo_pdf'] = $gen_pdf->getLogo() ?? '';
        //todo 
        $order_data['titolo_corso_pdf'] = "LA LOGICA DELLA VITA: Biologia Umana";

        //region customer address
        $customer_address = $this->getCustomerAddress();
        if (!empty($customer_address)) {
            $order_data['nome'] = !empty($customer_address['first_name']) ? ucfirst($customer_address['first_name']) : '';
            $order_data['cognome'] = !empty($customer_address['last_name']) ? ucfirst($customer_address['last_name']) : '';
            $order_data['title'] = "#{$this->order_id} " . $order_data['nome'] . " " . $order_data['cognome'];
            $order_data['residente_in_via'] = $customer_address['residente_in_via'];
            $order_data['residenza_civico'] = $customer_address['residenza_civico'];
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
        $order_meta_datas =  $this->getOrderMetaData([ '_billing_luogo_nascita', '_billing_data_nascita','_billing_cf','_billing_professione','_billing_piva','_billing_company_address','_billing_company_address_number','_billing_company_citty','_billing_company_cap','_billing_pec','_billing_sdi','anno_accademico','giorno_generico_settimana','luogo_del_corso','titolo_corso_pdf']);
       // genpdf_vardie($order_meta_datas);
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
                    //endregion
                    "anno_accademico" => $order_data['anno_accademico'] = $item['meta_value'],
                    "giorno_generico_settimana" => !empty($item['meta_value']) ?  $order_data['giorno_generico_settimana'] = ucfirst($item['meta_value']) : '',
                    "luogo_del_corso" =>  !empty($item['meta_value']) ?  $order_data['luogo_del_corso'] = $order_data['luogo'] = ucfirst($item['meta_value']) : '',
                    "titolo_corso_pdf" =>  $order_data['titolo_corso_pdf'] = $item['titolo_corso_pdf']
                };
            }
        }
        //endregion

        $order_data['note'] = $this->order['customer_note'];
        $order_data['metodo_pagamento'] = $this->order['payment_method_title'];

        //genpdf_vardie($this->order);
        //region acconto or totale
        if ($this->isAcconto()) {
            $order_data['importo_acconto'] = number_format($this->order['total_amount'],2,",");
            $totale = floatval($this->order['total_amount']);
            //region month
            $mesi_meta = $this->getOrderMetaData(['importo_mese']);    
            if(!empty($mesi_meta) && !empty($mesi_meta[0]) &&!empty($mesi_meta[0]['meta_value']) ){
                $mesi = json_decode($mesi_meta[0]['meta_value'],TRUE);       
                $order_data['td_mesi_nome'] = '';
                $order_data['td_mesi_importi'] = '';
                foreach($mesi as $nome_mese => $price ){
                    if( is_float($price) || is_numeric($price) ){
                        $totale+=floatval($price);
                        $order_data['td_mesi_nome'].= '<td class="destra">'.strtoupper($nome_mese).'</td>';
                        $order_data['td_mesi_importi'].= '<td class="destra">€ '.number_format($price,2,",").'</td>';
                    }
                }
            }
            //endregion
            $order_data['importo_totale'] = number_format($totale,2,",");
        }else{
            $order_data['importo_acconto'] = 0; 
            $order_data['importo_totale'] = number_format($this->order['total_amount'],2,",");
        }
        //endregion

        $order_data['data'] = $this->order['date_created_gmt'] ? date('d/m/Y',strtotime($this->order['date_created_gmt'] )) : '';
        $signed_path = $this->getSignedPath();
        if( !empty($signed_path)  && boolval($signed_path) ){
            $order_data['firma'] = '<img stlye="margin-left: 15px" src="'.$signed_path.'" width="240px">';
        }else{
            $order_data['firma'] = '___________________';
        }
             
        //region checkbox
        $checkbox_newsletter = $this->getOrderMetaData(['billing_newsletter']);
        if(!empty($checkbox_newsletter) && !empty($checkbox_newsletter[0]) && !empty($checkbox_newsletter[0]['meta_value']) ){
            $order_data['checked_newsletter_si'] = 'checked';
        }else{
            $order_data['checked_newsletter_no'] = 'checked';
        }     
     
        $checkbox_riprese = $this->getOrderMetaData(['billing_shooting']);
        if( !empty($checkbox_riprese) && !empty($checkbox_riprese[0]) && !empty($checkbox_riprese[0]['meta_value']) ){
            $order_data['checked_riprese_si'] = 'checked';
        }else{
            $order_data['checked_riprese_no'] = 'checked';
        }       

        $checkbox_gruppo_cell = $this->getOrderMetaData(['billing_gruppo_cell']);
        if( !empty($checkbox_gruppo_cell) && !empty($checkbox_gruppo_cell[0]) && !empty($checkbox_gruppo_cell[0]['meta_value']) ){
            $order_data['checked_gruppo_cell_si'] = 'checked';
        }else{
            $order_data['checked_gruppo_cell_no'] = 'checked';
        }
        
        //endregion
        
        return $order_data;
    }

    /**
     * @return an array with metadata of the order
     */
    private function getOrderMetaData(array|null $param_array = [] ){
        global $wpdb;
        $table = $wpdb->base_prefix . "wc_orders_meta";       
        if(empty($param_array)){
            $query = $wpdb->prepare("SELECT  * FROM {$table} where  order_id = %d", [$this->order_id]);
        }else{
            $placeholders = implode(',', array_fill(0,count($param_array),'%s'));
            $query = $wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %d AND meta_key in ($placeholders) ", array_merge([$this->order_id], $param_array) );
        }
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * @return an array with customer info address
     */
    private function getCustomerAddress(){
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
     * @return the url of the image path
     */
    private function getSignedPath(){
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM  wp_posts where ID = ( SELECT meta_value FROM `wp_postmeta` WHERE post_id=%d and meta_key=%s limit 1 ) AND post_type=%s limit 1 ",[$this->order_id,'signpad','attachment']);
        $row = $wpdb->get_row($query,ARRAY_A);
        if(!empty($row) && !empty($row['guid']) && boolval($row['guid']) ){
            return $row['guid'];
        }
        return null;
    }

    /**
     * @return true o false if the order and the only one product is "acconto" or not.
     */
    private function isAcconto()
    {
        global $wpdb;
        $table = $wpdb->base_prefix . "wc_orders_meta";
        $query = $wpdb->prepare("SELECT 
        * FROM {$table} where  order_id = %d AND meta_key = %s ", [$this->order_id, 'acconto_o_totale']);
        $row =  $wpdb->get_row($query, ARRAY_A);
        if (!empty($row) && !empty($row['meta_value']) && strpos($row['meta_value'], 'acconto') !== false) {
            return true;
        }
        return false;
    }

    /**
     * @return table name of the model
     */
    private function getTableName()
    {
        return GenPDF::getFullPrefix() . "_orders_template";
    }
    
}
