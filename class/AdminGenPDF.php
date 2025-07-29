<?php

namespace GenPDF;


use GenPDF\OldSubGenPDF;
use GenPDF\GenPDF;
use GenPDF\TemplateEmailGenPDF;

class AdminGenPDF
{


    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function icon()
    {
        return 'dashicons-pdf';
    }
    public function addMenu()
    {

        add_menu_page(
            __('GenPDF settings', 'genpdf-woocommerce'),
            __('GenPDF settings', 'genpdf-woocommerce'),
            'manage_options',
            'genpdf_menu',
            [$this, 'genpdf_settings'],
            $this->icon(),
            40,
        );

        add_submenu_page(
            'genpdf_menu',                              
            __('GenPDF Templates', 'genpdf-woocommerce'), 
            __('Templates', 'genpdf-woocommerce'),      
            'manage_options',                            
            'edit.php?post_type=genpdf_template',        
            '',                                          
            1                                           
        );
        add_submenu_page(
            'genpdf_menu',                              
            __('Add new template', 'genpdf-woocommerce'), 
            __('Add new template', 'genpdf-woocommerce'),      
            'manage_options',                            
            'post-new.php?post_type=genpdf_template',        
            '',                                          
            2                                          
        );


        add_submenu_page(
            null,
            __('Download PDF', 'genpdf-woocommerce'),
            __('Download PDF', 'genpdf-woocommerce'),
            'manage_woocommerce',
            'genpdf_download_pdf',
            'genpdf_download_pdf'
        );
        add_submenu_page(
            null,
            __('Send attachments', 'genpdf-woocommerce'),
            __('Send attachments', 'genpdf-woocommerce'),
            'manage_woocommerce',
            'genpdf_send_attachments',
            'genpdf_send_attachments',
        );

    }
    public static function register_post_type()
    {
        if (is_admin()) {
            register_post_type(
                'genpdf_template',
                [
                    "label" => __('GenPDF Template Email', 'genpdf-woocommerce'),
                    'labels' => [
                        'name' => __('GenPDF Templates'),
                        'singular_name' => __('GenPDF Template Email'),
                        'add_new' => __('Add Template Email'),
                        'add_new_item' => __('Add New Template'),
                        'edit' => __('Edit Template'),
                        'edit_item' => __('Edit Template'),
                        'new_item' => __('Add New Template'),
                        'view' => __('View Template'),
                        'view_item' => __('View Template'),
                        'search_items' => __('Search Template'),
                        'not_found' => __('No Template Found'),
                        'not_found_in_trash' => __('No Template found in Trash'),
                    ],
                    "description" => __("Template email used by GenPDF plugin", "genpdf-woocommerce"),
                    "exclude_from_search" => true,
                    "publicly_queryable" => false,
                    "show_ui" => true,
                    "show_in_menu" => false,
                    "public" => true,
                    "show_in_rest" => false,
                    'menu_position' => 40,
                    "menu_icon" => "dashicons-email",
                    "supports" => ['title', 'editor', 'revisions', 'author'],
                ]
            );
        }
    }

    /**
     * include the view
     */
    public function old_subscriptions()
    {
        if (is_admin()) {
            $subs = new OldSubGenPDF();
            include_once genpdf_getPath() . '/views/old_subscritions.php';
        }
    }

    /**
     * include the view
     */
    public function genpdf_settings()
    {
        if (is_admin()) {
            $genpdf = new GenPDF;
            if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'genpdf_menu' && $_SERVER['REQUEST_METHOD'] == 'POST') {
                if (wp_verify_nonce($_REQUEST['genpdf_settings_value'], 'genpdf_settings') === false) {
                    wp_die("Your token nonce is not valid");
                }
                if (isset($_POST['logo']) && !empty($_POST['logo'])) {
                    $genpdf->updateOption("logo", $_POST['logo']);
                }
                if (isset($_POST['admin_emails']) && !empty($_POST['admin_emails'])) {
                    $is_valid = true;
                    $array_email = $genpdf->fromStringEmailsToArray($_POST['admin_emails']);
                    foreach ($array_email as $item) {
                        if (filter_var($item, FILTER_VALIDATE_EMAIL) === FALSE) {
                            $is_valid = false; ?>
                            <div class="notice notice-error is-dismissible">
                                ERROR: The email is not valid <?= $item ?>
                            </div>
<? }
                    }
                    if ($is_valid === true) {
                        $genpdf->updateOption('emails_cc', $_POST['admin_emails']) === false;
                    }
                }
                if (isset($_POST['customer_email_template']) && !empty($_POST['customer_email_template'])) {
                    $genpdf->updateOption("customer_email_template", $_POST['customer_email_template']);
                }
                if (isset($_POST['admin_email_template']) && !empty($_POST['admin_email_template'])) {
                    $genpdf->updateOption("admin_email_template", $_POST['admin_email_template']);
                }
            }

            $list_template = TemplateEmailGenPDF::getList();
            include_once genpdf_getPath() . '/views/settings_page.php';
        }
    }
}
