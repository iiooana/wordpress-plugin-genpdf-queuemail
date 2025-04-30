<?php

namespace GenPDF;


use GenPDF\OldSubGenPDF;


class AdminGenPDF
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function icon()
    {
        return 'dashicons-id-alt';
    }
    public function addMenu()
    {     

        add_menu_page(
            __('GenPDF settings','genpdf-woocommerce'),
            __('GenPDF settings','genpdf-woocommerce'),
            'manage_options',
            'genpdf_settings',
            [$this,'genpdf_settings'],
            $this->icon(),
            40,
        );
       
    }

    /**
     * include the view
     */
    public function old_subscriptions()
    {        
        if( is_admin()){
            $subs = new OldSubGenPDF();
            include_once(genpdf_getPath().'/views/old_subscritions.php');
        }      
    }
    
    public function genpdf_settings(){
        if(is_admin()){
            include_once(genpdf_getPath().'/views/settings_page.php');
        }
    }
}
