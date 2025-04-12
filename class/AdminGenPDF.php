<?php

namespace GenPDF\Admin;

use GenPDF\SubscriptionGenPDF;
use WP_Query;

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
            __('Subscription old website','genpdf-woocommerce'),
            __('Subs. old website','genpdf-woocommerce'),
            'manage_options',
            'genpdf_data',
            [$this, 'old_subscriptions'],
            $this->icon(),
            10
        );
       
    }

    public function old_subscriptions()
    {        
        $forms_query = new WP_Query([
            'post_type' => 'wpcf7_contact_form',
            'post_per_page' => -1
        ]);
        //genpdf_vardie($the_query);
        $form_id_selected = null;
        if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'genpdf_data' && !empty($_REQUEST['contact_form_id']) && is_numeric($_REQUEST['contact_form_id'])) {
            //check CSRF
            if(!isset($_REQUEST['genpdf_wpnonce']) || !wp_verify_nonce($_REQUEST['genpdf_wpnonce'],'genpdf_select_form')){
                echo "Your wpnonce is not valid.";
                die();               
            }
            //genpdf_vardie($_REQUEST);
            $form_id_selected =  $_REQUEST['contact_form_id'];
        }
?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Iscrizioni ricevute</h1>
            <? if ($forms_query->have_posts()) { ?>
                <form id="form_genpdf_data_page" method="get">
                    <input type="hidden" name="page" value="genpdf_data">
                    <?= wp_nonce_field( 'genpdf_select_form','genpdf_wpnonce') ?>
                    <select name="contact_form_id" onchange="document.getElementById('form_genpdf_data_page').submit()" required>
                        <option value=""></option>
                        <? while ($forms_query->have_posts()) {
                            $forms_query->the_post() ?>
                            <option value="<?= get_the_ID() ?>" <?= (!empty($form_id_selected) && $form_id_selected == get_the_ID() ? 'selected' : '') ?>><?= esc_html(get_the_title()) ?></option>
                            <? wp_reset_postdata(); ?>
                        <? } ?>
                    </select>
                </form>
            <? } ?>
            <? if (!empty($form_id_selected) && is_numeric($form_id_selected)) {
                $form_selected_query = new WP_Query([
                    'p' => $form_id_selected,
                    'post_type' => 'wpcf7_contact_form'
                ]);
                //genpdf_vardie($form_selected_query);
                if ($form_selected_query->have_posts()) {
                    $form_selected_query->the_post(); ?>
                    <hr>
                    <h2>Iscrizioni ricevute dal form: <?= esc_html(get_the_title()) ?></h2>
                    <? wp_reset_postdata(); ?>
                <? } ?>
            <? } ?>
        </div>
<?
    }

    public function genpdf_test(){
        //_genpdf_course_id
        if(!empty($_REQUEST['id_lead'])){
            var_dump($_REQUEST['id_lead']);
            
        }
        ?>
        <div class="wrap">
            <h1 class="wp-heading-line">Test iscrizione</h1>    
        </div>
        <?
    }
}
