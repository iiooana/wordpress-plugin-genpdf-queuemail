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
        return 'dashicons-id-alt';
    }
    public function addMenu()
    {

        add_menu_page(
            __('GenPDF settings', 'genpdf-woocommerce'),
            __('GenPDF settings', 'genpdf-woocommerce'),
            'manage_options',
            'genpdf_settings',
            [$this, 'genpdf_settings'],
            $this->icon(),
            40,
        );
    }

    /**
     * include the view
     */
    public function old_subscriptions()
    {
        if (is_admin()) {
            $subs = new OldSubGenPDF();
            include_once(genpdf_getPath() . '/views/old_subscritions.php');
        }
    }

    /**
     * include the view
     */
    public function genpdf_settings()
    {
        if (is_admin()) {
            $genpdf = new GenPDF;
            if (!empty($_REQUEST['page']) && $_REQUEST['page'] == 'genpdf_settings' && $_SERVER['REQUEST_METHOD'] == 'POST') {
                if (wp_verify_nonce($_REQUEST['genpdf_settings_value'], 'genpdf_settings') === false) {
                    wp_die("Your token nonce is not valid");
                }
                if (isset($_REQUEST['logo']) && !empty($_REQUEST['logo'])) {
                    $genpdf->updateOption("logo", $_REQUEST['logo']);
                }
                if (isset($_REQUEST['admin_emails']) && !empty($_REQUEST['admin_emails'])) {
                    $is_valid = true;
                    $array_email = $genpdf->fromStringEmailsToArray($_REQUEST['admin_emails']);
                    foreach ($array_email as $item) {
                        if (filter_var($item, FILTER_VALIDATE_EMAIL) === FALSE) {
                            $is_valid = false; ?>
                            <div class="notice notice-error is-dismissible">
                                ERROR: The email is not valid <?= $item ?>
                            </div>
                        <? }
                    }
                    if ($is_valid === true) {
                        $genpdf->updateOption('emails_cc', $_REQUEST['admin_emails']) === false;
                    }
                }
                if (isset($_REQUEST['customer_email_template']) && !empty($_REQUEST['customer_email_template'])) {
                    $genpdf->updateOption("customer_email_template", $_REQUEST['customer_email_template']);
                }
                if (isset($_REQUEST['admin_email_template']) && !empty($_REQUEST['admin_email_template'])) {
                    $genpdf->updateOption("admin_email_template", $_REQUEST['admin_email_template']);
                }
            }

            $list_template = TemplateEmailGenPDF::getList();
            include_once(genpdf_getPath() . '/views/settings_page.php');
        }
    }
}
