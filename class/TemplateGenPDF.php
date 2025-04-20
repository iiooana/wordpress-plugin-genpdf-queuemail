<?

namespace GenPDF\TemplateGenPDF;

use GenPDF\GenPDF;

class TemplateGenPDF
{

    private $id;
    private $name;
    private $html;
    private $created_at;
    private $update_at;

    function __construct($id)
    {
        global $wpdb;
        $table = self::getTableName();
        $query = $wpdb->prepare("SELECT * FROM {$table} where id=%d limit 1", [$id]);
        $row = $wpdb->get_row($query, ARRAY_A);
        if (empty($row) || empty($row['id'])) {
            $message = var_export(["message" => "Template not found", "id" => $id], true);
            error_log($message);
            throw new \Exception($message, 1001);
        }
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->html =  $row['html'];
        $this->created_at = $row['created_at'];
        $this->update_at = $row['updated_at'];
    }


    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    /**
     * @param $product_it the id of the post where post_type='prodict'
     * @return template_id of the product_id
     */
    public static function getIdTemplateByProduct( int $product_id){
        global $wpdb;
        $table = $wpdb->base_prefix."genpdf_products_template";
        $query = $wpdb->prepare("SELECT template_id FROM {$table} where product_id = %d limit 1",[$product_id]);
        $value = $wpdb->get_col($query,0);
        return isset($value[0]) && is_numeric($value[0]) ? intval($value[0]) : null;
    }

    /**
     * @return table name of the model
     */
    private function getTableName()
    {
        return GenPDF::getFullPrefix() . "_templates";
    }

}
