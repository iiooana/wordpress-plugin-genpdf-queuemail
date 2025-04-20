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
        global $wpdb;
        $order_data = [];

        //region TODO font url and title
        //endregion

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
        $row = $wpdb->get_row($query, ARRAY_A);
        if (!empty($row)) {
            $order_data['nome'] = ucfirst($row['first_name']);
            $order_data['cognome'] = ucfirst($row['last_name']);
            $order_data['residente_in_via'] = $row['residente_in_via'];
            $order_data['residenza_civico'] = $row['residenza_civico'];
            $order_data['citta_residenza'] = $row['citta_residenza'] . (!empty($row['state']) ? ', ' . $row['state'] : '');
            $order_data['cap_residenza'] = $row['cap_residenza'];
            $order_data['email'] = $row['email'];
            $order_data['cell'] = $row['phone'];

            //region company
            if (!empty($row['company'])  && strlen(trim($row['company'])) > 0) {
                $order_data['ragione_sociale'] = $row['company'];
            }
            //endregion           

        }
        $table = $wpdb->base_prefix . "wc_orders_meta";
        $query = $wpdb->prepare("SELECT 
        *
        FROM {$table}
        where 
        order_id = %d
        AND 
        meta_key in (
        '_billing_luogo_nascita',
        '_billing_data_nascita',
        '_billing_cf',
        '_billing_professione',
        '_billing_piva',
        '_billing_company_address',
        '_billing_company_address_number',
        '_billing_company_citty',
        '_billing_company_cap',
        '_billing_pec',
        '_billing_sdi'  
        )
        ", [$this->order_id]);
        //genpdf_vardie($query);
        $rows =  $wpdb->get_results($query, ARRAY_A);
        //genpdf_vardie($rows);
        if (!empty($rows)) {
            foreach ($rows as $item) {
                // genpdf_vardie($item['meta_key']);
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
                    "_billing_sdi" => $order_data['azienda_sdi'] = $item['meta_value']
                    //endregion
                };
            }
        }

        //genpdf_vardie($order_data);
        $order_data['note'] = $this->order['customer_note'];
        $order_data['metodo_pagamento'] = $this->order['payment_method_title'];
        //genpdf_vardie($this->order);        
        if (!empty($this->order['date_created_gmt'])) {
            //TODO ADD FIELD as metadata and after to db
            $year = intval(date('Y', strtotime($this->order['date_created_gmt'])));
            $order_data['anno_accademico'] =  $year . "/" . ($year + 1);
        }


        //todo  html_tabella_import
        /*
<table>
		<tr>
			<td style="width: 16%;" class="destra">SCELGO IL CORSO:</td>
			<td style="width: 16%;" class="destra">ACCONTO</td>
			<td style="width: 16%;" class="destra">SETTEMBRE</td>
			<td style="width: 16%;" class="destra">NOVEMBRE</td>
			<td style="width: 16%;" class="destra">FEBBRAIO</td>
			<td style="width: 16%;" class="destra">TOTALE</td>
		</tr>
		<tr>
			<td style="width: 16%;" class="destra"><input type="checkbox" style="margin-top: 5px;padding: 0px;" checked>
				BIOLOGIA UMANA</td>
			<td style="width: 16%;" class="destra">€ 488,00</td>
			<td style="width: 16%;" class="destra">€ 488,00</td>
			<td style="width: 16%;" class="destra">€ 488,00</td>
			<td style="width: 16%;" class="destra">€ 366,00</td>
			<td style="width: 16%;">€ 1.830,00</td>
		</tr>
	</table>
        */

        //region acconto o totale
        //$order_data['import_acconto'] = $row['company'];
        //$order_data['importo_totale'] = $row['company'];
        //endregion

        //region checked Padova - Verona
        //luogo_del_corso
        
        $order_data['checked_padova'] = '';
        $order_data['checked_verona'] = '';
        $order_data['luogo'] = '';
        $order_data['data'] = '';
        $order_data['firma'] = '';
        //endregion

        $order_data['checked_weekend'] = '';
        $order_data['checked_lunedi'] = '';

        $order_data['checked_crediti'] = '';
        $order_data['checked_contati'] = '';
        $order_data['checked_bonifico'] = '';

        $order_data['luogo'] = '';
        $order_data['data'] = '';
        $order_data['firma'] = '';

        //region checkbox
        $order_data['checked_newsletter_si'] = '';
        $order_data['checked_newsletter_no'] = '';

        $order_data['checked_riprese_si'] = '';
        $order_data['checked_riprese_no'] = '';

        $order_data['cheched_gruppo_cell_si'] = '';
        $order_data['cheched_gruppo_cell_no'] = '';
        //endregion


        //genpdf_vardie($query, $row, $order_data);
        return $order_data;
    }

    private function getTableName()
    {
        return GenPDF::getFullPrefix() . "_orders_template";
    }
}
