<?
namespace GenPDF;
class OldSubGenPDF {

    private $count_per_page;
    private $current_page;
    private $search;
    private $db;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->current_page = 1;
        $this->count_per_page = 15;
    }

    //region GET

    public function getSearch(){
        return $this->search;
    }

    public function getCount(){
        $query_prep = $this->db->prepare(
            "SELECT count(*) as n FROM {$this->db->base_prefix}vxcf_leads"
        );
        if( !empty($this->search) ){
            $like = "%".$this->db->esc_like($this->search)."%";
            //var_dump($this->search, $like);
            $query_prep = $this->db->prepare(
                "SELECT count(DISTINCT(leads.id )) as n FROM {$this->db->base_prefix}vxcf_leads as leads
                INNER JOIN {$this->db->base_prefix}vxcf_leads_detail as ld on ld.lead_id=leads.id and ld.value like %s
                ", [ $like ]);
        }
        return $this->db->get_var($query_prep);
    }

    public function getMaxNumberPages(){
        $count = $this->getCount();
        $max_page = $count > 0 ? ceil( $count/$this->count_per_page) : 0;
        return $max_page > 0 ?  $max_page : 1 ;
    }
    public function getCurrentPage(){
        return $this->current_page;
    }

    public function getData(){
        $query_prep = $this->db->prepare(
            "SELECT id,url,created
            FROM {$this->db->base_prefix}vxcf_leads as leads 
            ORDER BY id DESC
            LIMIT %d
            OFFSET %d", 
            [$this->count_per_page, $this->count_per_page * ($this->current_page-1)]
            );
        if( !empty($this->search) ){
            $like = "%".$this->db->esc_like($this->search)."%";
            $query_prep = $this->db->prepare(
                "SELECT DISTINCT (leads.id), leads.url, leads.created
                FROM {$this->db->base_prefix}vxcf_leads as leads
                INNER JOIN {$this->db->base_prefix}vxcf_leads_detail as ld on ld.lead_id=leads.id and ld.value like %s
                ORDER BY leads.id DESC
                LIMIT %d
                OFFSET %d
                ",  [$like, $this->count_per_page, $this->count_per_page * ($this->current_page-1)]
            );
        }   
        return $this->db->get_results($query_prep, ARRAY_A);

    }
    public function getLead(int $lead_id){
        $query_prep = $this->db->prepare("
        SELECT * FROM
         {$this->db->base_prefix}vxcf_leads 
        WHERE id=%d
        ",[$lead_id]);
        return $this->db->get_row($query_prep,ARRAY_A);
    }

    public function getShortDetail(int $lead_id){  
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

    //endregion

    //region SET
    public function setCurrentPage(int $n){
        $this->current_page = $n;
    }
    public function setSearch(string $search = null){
        $this->search = null;
        if( !empty($search) && !empty(trim($search)) ){
            $this->search = $search;
        }
    }
    //endregion

}

?>