<?
namespace GenPDF;
class OldSubGenPDF {

    private $count_per_page;
    private $db;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->count_per_page = 15;
    }

    public function getCount(){
        $query_prep = $this->db->prepare(
            "select count(*) from {$this->db->base_prefix}vxcf_leads"
        );
        return $this->db->get_var($query_prep);
    }

    public function getData($page = 1){
        $query_prep =  $this->db->prepare(
            "SELECT id,url,created
            FROM {$this->db->base_prefix}vxcf_leads as leads 
            order by id desc
            limit {$this->count_per_page}"
            );
        return $this->db->get_results($query_prep, ARRAY_A);
       // genpdf_vardie($test);
    }

    public function getShortDetail($lead_id){  
        $query_nome = $this->db->prepare(
            "SELECT value from {$this->db->base_prefix}vxcf_leads_detail where lead_id=%d and name=%s limit 1",
            [$lead_id,'nome']
        );
        $query_cognome = $this->db->prepare(
            "SELECT value FROM {$this->db->base_prefix}vxcf_leads_detail where lead_id=%d and name=%s limit 1",
            [$lead_id,'cognome']
        );
        return [
           "nome" => $this->db->get_row($query_nome,ARRAY_A)['value'],
           "cognome" => $this->db->get_row($query_cognome,ARRAY_A)['value']
        ];
    }
    public function getDetail($lead_id){
        $query = $this->db->prepare("SELECT * from {$this->db->base_prefix}vxcf_leads_detail where lead_id=%d order by id asc",[$lead_id]);
        return $this->db->get_results($query,ARRAY_A);
    }

}

?>