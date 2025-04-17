<?

namespace GenPDF;

use GenPDF\GenPDF;
use GenPDF\TemplateGenPDF\TemplateGenPDF;

class OrderGenPDF {

    private $order_id;
    private $template_id;
    private $created_at;
    private $updated_at;

    function __construct($order_id)
    {
        global $wpdb;
        $table = self::getTableName();
        $query = $wpdb->prepare("SELECT * FROM {$table} where order_id=%d limit 1", [ $order_id ]);
        $row = $wpdb->get_row($query, ARRAY_A);
        if(empty($row) || empty($row['order_id'])){
            $message = var_export(["message" => "Template of order_id not found.","order_id" => $order_id], true );
            error_log($message);
            throw new \Exception($message,1002);
        }
        $this->order_id = $row['order_id'];
        $this->template_id =  $row['template_id'];
        $this->created_at =  $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    public function __get($name)
    {
        if( isset($this->{$name})){
            return $this->{$name};
        }
        return null;
    }    

    public function getPDF(){
        $html = null;
        $template = new TemplateGenPDF($this->template_id);
        $html = $template->html;
        return $html;
    }

    private function getTableName(){
        return GenPDF::getFullPrefix()."_orders_template";
    }
}
