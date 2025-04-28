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
            $message = var_export(["message" => "[ERROR-GENPDF001]Template not found", "id" => $id], true);
            error_log($message);
            //throw new \Exception($message, 1001);
        }else{
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->html =  $row['html'];
            $this->created_at = $row['created_at'];
            $this->update_at = $row['updated_at'];
        }       
    }


    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    /**
     * @return table name of the model
     */
    private function getTableName()
    {
        return GenPDF::getFullPrefix() . "_templates";
    }

}
