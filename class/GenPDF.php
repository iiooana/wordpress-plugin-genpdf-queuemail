<?php
namespace GenPDF;
class GenPDF {
    CONST PREFIX_TABLE ='genpdf';

    public static function getFullPrefix(){
        global $wpdb;
        return $wpdb->base_prefix.GenPDF::PREFIX_TABLE;
    }

}
