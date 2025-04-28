<?php
namespace GenPDF;
class GenPDF {
    CONST PREFIX_TABLE ='genpdf';

    public function getOption(string $type){        
        return match($type){
            "logo" => get_option('_genpdf_logo_pdf'),
            "template" => get_option('_genpdf_id_current_template_pdf'),
            "email_cc" => get_option('_genpdf_email_cc'),
            "blogname" => get_option('blogname')
        };
    }

    /**
     * @return html logo
     */
    public function getLogo(){
        $logo_pdf = $this->getOption('logo');
        if(!empty($logo_pdf) && strlen($logo_pdf) !== false){
           return "<img src=".get_site_url(null, $logo_pdf)." style='width:100%;'>";
        }
        return null;
    }

    /**
     * @return the prefix of this plugin
     */
    public static function getFullPrefix(){
        global $wpdb;
        return $wpdb->base_prefix.GenPDF::PREFIX_TABLE;
    }

    /**
     * @return array of common settings
     */
    public function getArraySettings(){
        $array_settings = [];
        $array_settings['cc'] = $this->getOption("email_cc");
        $array_settings['cc']  = 'ioanaudia7@gmail.com'; //todo remove 
        $array_settings['temp_dir'] = sys_get_temp_dir();
        $array_settings['templates']['customer'] = file_get_contents(genpdf_getPath()."/templates/customer.html");
        $array_settings['templates']['admin'] = file_get_contents(genpdf_getPath()."/templates/admin.html");
        return $array_settings;
    }

}
