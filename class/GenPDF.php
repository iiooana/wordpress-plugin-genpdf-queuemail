<?php
namespace GenPDF;
class GenPDF {
    CONST PREFIX_TABLE ='genpdf';

    /**
     * @return the option save on db
     */
    public function getOption(string $type){        
        return match($type){
            "logo" => get_option('_genpdf_logo_pdf'),
            "template" => get_option('_genpdf_id_current_template_pdf'),
            "emails_cc" => get_option('_genpdf_email_cc'),
            "admin_email_template" => get_option('_genpdf_id_admin_template_email'),
            "customer_email_template" => get_option('_genpdf_id_customer_template_email'),
        };
    }

    public function updateOption(string $type, string|int $value){
        return match($type){
            "logo" => update_option('_genpdf_logo_pdf',$value),
            "emails_cc" => update_option('_genpdf_email_cc',$value),
            "admin_email_template" => update_option('_genpdf_id_admin_template_email',$value),
            "customer_email_template" => update_option('_genpdf_id_customer_template_email',$value),
        };
    }

    /**
     * @return img tag of logo
     */
    public function getLogo(){
        $logo_pdf = $this->getOption('logo');
        if(!empty($logo_pdf) && strlen($logo_pdf) !== false){            
            if(strpos($logo_pdf,get_site_url())){
                $logo_pdf = get_site_url(null, $logo_pdf);
            }
           return "<img src=".$logo_pdf." style='width:100%;'>";
        }
        return null;
    }

    /**
     * @return the db prefix of this plugin
     */
    public static function getFullPrefix(){
        global $wpdb;
        return $wpdb->base_prefix.GenPDF::PREFIX_TABLE;
    }
    /**
     * @return array of emails
     */
    public function fromStringEmailsToArray(string $emails){
        $array_email = [];
        $array_email[]= $emails;
        if (strpos($emails, ",")) {
            $array_email = explode(",", $emails);
        }
        return $array_email; 
    }    
    /**
     * @return array of common settings
     */
    public function getArraySettings(){
        $array_settings = [];
        $array_settings['cc'] = $this->fromStringEmailsToArray( $this->getOption("emails_cc") );
        $array_settings['temp_dir'] = sys_get_temp_dir();

        $template_customer = new TemplateEmailGenPDF(intval($this->getOption('customer_email_template')));     
        $array_settings['templates']['customer'] = $template_customer->getHtml();
        
        $template_admin = new TemplateEmailGenPDF(intval($this->getOption('admin_email_template')));      
        $array_settings['templates']['admin'] = $template_admin->getHtml();
        
        $array_settings['ok_status_order'] = OrderEmailGenPDF::getListAcceptsStatus();
        return $array_settings;
    }

}
