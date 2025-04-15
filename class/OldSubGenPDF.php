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
        return $this->db->get_var("select count(*) from {$this->db->base_prefix}vxcf_leads");
    }

    public function getData($page = 1){
        return $this->db->get_results("
        SELECT id,url,created
        FROM {$this->db->base_prefix}vxcf_leads as leads 
        order by id desc
        limit {$this->count_per_page}
        ",ARRAY_A);
       // genpdf_vardie($test);
    }

    public function getShortDetail($lead_id){  
        return [
           "nome" => $this->db->get_results("SELECT value from {$this->db->base_prefix}vxcf_leads_detail where lead_id={$lead_id} and name='nome' limit 1 ",ARRAY_A)[0]['value'],
           "cognome" => $this->db->get_results("SELECT value from {$this->db->base_prefix}vxcf_leads_detail where lead_id={$lead_id} and name='cognome' limit 1 ",ARRAY_A)[0]['value']
        ];
    }
    public function getDetail($lead_id){
        return $this->db->get_results(" SELECT * from  {$this->db->base_prefix}vxcf_leads_detail where lead_id={$lead_id} order by id asc ",ARRAY_A);
    }
    
}

?>