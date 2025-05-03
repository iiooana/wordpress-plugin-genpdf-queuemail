<?php
if (!is_admin()) {
    wp_die("Access denied.");
} ?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?= __('GenPDF settings', 'genpdf-woocommerce') ?></h1>
    <form action="?page=genpdf_menu" method="post">
        <? wp_nonce_field('genpdf_settings', 'genpdf_settings_value') ?>
        <table class="form-table indent-children" role="presentation" width="100%" id="genpdf_table">
            <tbody>
                <tr>
                    <td><strong>Logo - <?=__('Set the link of the logo to insert into the pdf','genpdf-woocommerce')?></strong></td>
                    <td>
                        <div style="max-height: 200px; max-width: 400px;">
                            <?= $genpdf->getLogo() ?>
                        </div>
                        <input type="url" name="logo" value="<?= $genpdf->getOption('logo') ?>" pattern="https://.*" style="width:100%" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong><?=__('List of emails separated by ","','genpdf-woocommerce') ?></strong></p>
                        <p><?=__('Add one or more to receive the email order with attachments.','genpdf-woocommerce')?></p>
                    </td>
                    <td>
                        <input type="text" name="admin_emails" value="<?= $genpdf->getOption('emails_cc') ?>" requiredd>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong><?=__('Template email customer','genpdf-woocommerce')?></strong></p>
                    </td>
                    <td>
                        <? $template_id =$genpdf->getOption('customer_email_template');?>
                        <select name="customer_email_template" required>
                            <option value="" selected></option>
                            <? if (!empty($list_template)) {
                                foreach ($list_template as $template) { ?>
                                    <option value="<?= $template->ID ?>" <?= $template->ID == $template_id ? 'selected' : '' ?>><?= $template->post_title ?></option>
                            <? }
                            } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong><?=__('Template email admin','genpdf-woocommerce')?></strong></p>
                    </td>
                    <td>
                    <? $template_id =$genpdf->getOption('admin_email_template');?>
                        <select name="admin_email_template" required>
                            <option value="" selected></option>
                            <? if (!empty($list_template)) {
                                foreach ($list_template as $template) { ?>
                                    <option value="<?= $template->ID ?>" <?= $template->ID == $template_id ? 'selected' : '' ?>><?= $template->post_title ?></option>
                            <? }
                            } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button class="button"><?= __('Save', 'genpdf-woocommerce') ?></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>