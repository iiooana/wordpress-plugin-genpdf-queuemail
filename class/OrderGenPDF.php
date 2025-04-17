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

    function __construct($order_id)
    {
        global $wpdb;
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
            foreach ($order_data  as $key => $value ){
               $html = str_replace("[$key]",$value,$html);
            }
        }
      
        return $html;
    }

    private function getArrayData()
    {
        global $wpdb;
        $order_data = [];

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
            //$order_data['luogo_di_nascita'] = $row['last_name'];
            //$order_data['data_di_nascita'] = $row['last_name'];           
            $order_data['residente_in_via'] = $row['residente_in_via'];
            $order_data['residenza_civico'] = $row['residenza_civico'];
            $order_data['citta_residenza'] = $row['citta_residenza'].(!empty($row['state']) ? ', '.$row['state']: '');             
            $order_data['cap_residenza'] = $row['cap_residenza'];             
            //$order_data['codice_fiscale'] = $row['last_name'];             
            $order_data['email'] = $row['email'];             
            $order_data['cell'] = $row['phone'];             
            //$order_data['professione'] = $row['last_name'];             
            //$order_data['note'] = $row['last_name'];             

            //region company
            if (!empty($row['company'])  && strlen(trim($row['company'])) > 0) {
                $order_data['ragione_sociale'] = $row['company'];
                //$order_data['piva'] = $row['company'];
                //$order_data['azienda_via'] = $row['company'];
                //$order_data['azienda_civico'] = $row['company'];
                //$order_data['azienda_citta'] = $row['company'];
                //$order_data['azienda_cap'] = $row['company'];
                //$order_data['pec'] = $row['company'];
                //$order_data['codice_sdi'] = $row['company'];
            }
            //endregion

            //region acconto o totale
            //$order_data['import_acconto'] = $row['company'];
            //$order_data['importo_totale'] = $row['company'];
            //endregion

            //region checked Padova - Verona
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
           
        }
       // genpdf_vardie($query, $row, $order_data);
        return $order_data;
    }

    private function getTableName()
    {
        return GenPDF::getFullPrefix() . "_orders_template";
    }
}
