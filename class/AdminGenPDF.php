<?php

namespace GenPDF;

use GenPDF\OrderGenPDF;
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
     /*   add_menu_page(
            __('Subscriptions from old website','genpdf-woocommerce'),
            __('Subs. old website','genpdf-woocommerce'),
            'manage_options',
            'genpdf_data',
            [$this, 'old_subscriptions'],
            $this->icon(),
            10
        );*/
        add_menu_page(
            'Test',
            'test',
            'manage_options',
            'genpdf_test',
            [$this,'test_page'],
            $this->icon(),
            10
        ) ;
       
    }

    public function old_subscriptions()
    {        
        if( is_admin()){
            $subs = new OldSubGenPDF();
            include_once(genpdf_getPath().'/views/old_subscritions.php');
        }      
    }  
    public function test_page(){
        global $wpdb;
   
        $order = new OrderGenPDF(18825);
        genpdf_vardie($order->getPDF());
    }  
}
