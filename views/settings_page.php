<?php
if (!is_admin()) {
    wp_die("Access denied.");
} ?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?= __('GenPDF settings', 'genpdf-woocommerce') ?></h1>
    <form action="?" method="post">
        <input type="hidden" name="page" value="genpdf_settings">
        <? wp_nonce_field('genpdf_settings', 'genpdf_settings_value') ?>
        <h2><?__("Logo","genpdf-woocommcerce")?></h2>
        <p><?__("Set the link of the logo to insert into the pdf","genpdf-woocommerce")?></p>
        

        <button class="button"><?= __('Save', 'genpdf-woocommerce') ?></button>
    </form>
</div>