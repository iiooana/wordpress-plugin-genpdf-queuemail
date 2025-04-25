<?php
namespace GenPDF;
class GenPDF {
    CONST PREFIX_TABLE ='genpdf';

    public function getOption(string $type){        
        return match($type){
            "logo" => get_option('_genpdf_logo_pdf'),
            "template" => get_option('_genpdf_id_current_template_pdf')
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

}
