<?php
if (!is_admin()) {
    wp_die("Access denied.");
}

// Gestione del salvataggio delle impostazioni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['genpdf_settings_value'])) {
    // Verifica il nonce per sicurezza
    if (!check_admin_referer('genpdf_settings', 'genpdf_settings_value')) {
        add_settings_error(
            'genpdf_settings',
            'genpdf_settings_error',
            __('Nonce verification failed.', 'genpdf-woocommerce'),
            'error'
        );
        error_log('GenPDF: Nonce verification failed.');
    } else {
        $errors = [];
        $genpdf = new \GenPDF\GenPDF();

        // Valida e salva il logo
        if (!empty($_POST['logo']) && filter_var($_POST['logo'], FILTER_VALIDATE_URL) && preg_match('/^https:\/\/.*/', $_POST['logo'])) {
            $genpdf->updateOption('logo', esc_url_raw($_POST['logo']));
        } else {
            $errors[] = __('Invalid logo URL. It must be a valid HTTPS URL.', 'genpdf-woocommerce');
        }

        // Valida e salva le email
        if (!empty($_POST['admin_emails'])) {
            $emails = array_map('trim', explode(',', sanitize_text_field($_POST['admin_emails'])));
            $valid_emails = [];
            foreach ($emails as $email) {
                if (is_email($email)) {
                    $valid_emails[] = $email;
                } else {
                    $errors[] = sprintf(__('Invalid email address: %s', 'genpdf-woocommerce'), $email);
                }
            }
            if (!empty($valid_emails)) {
                $genpdf->updateOption('emails_cc', implode(',', $valid_emails));
            } else {
                $errors[] = __('No valid email addresses provided.', 'genpdf-woocommerce');
            }
        } else {
            $errors[] = __('Admin emails field is required.', 'genpdf-woocommerce');
        }

        // Valida e salva il template email cliente
        if (!empty($_POST['customer_email_template'])) {
            $genpdf->updateOption('customer_email_template', absint($_POST['customer_email_template']));
        } else {
            $errors[] = __('Customer email template is required.', 'genpdf-woocommerce');
        }

        // Valida e salva il template email admin
        if (!empty($_POST['admin_email_template'])) {
            $genpdf->updateOption('admin_email_template', absint($_POST['admin_email_template']));
        } else {
            $errors[] = __('Admin email template is required.', 'genpdf-woocommerce');
        }

        // Aggiungi notifica di successo o errore
        if (empty($errors)) {
            add_settings_error(
                'genpdf_settings',
                'genpdf_settings_success',
                __('Settings saved successfully.', 'genpdf-woocommerce'),
                'success'
            );
            // Salva un transient per garantire che il messaggio persista dopo il redirect
            set_transient('genpdf_settings_notice', 'success', 30);
        } else {
            add_settings_error(
                'genpdf_settings',
                'genpdf_settings_error',
                implode('<br>', $errors),
                'error'
            );
            set_transient('genpdf_settings_notice', 'error', 30);
        }

        // Log per debug
        //error_log('GenPDF: Impostazioni inviate: ' . print_r($_POST, true));
        //error_log('GenPDF: Errori: ' . print_r($errors, true));
        error_log('GenPDF: Notifica impostata: ' . (empty($errors) ? 'success' : 'error'));
    }
}

// Funzione per mostrare i messaggi di notifica
function genpdf_admin_notices() {
    settings_errors('genpdf_settings');
}
add_action('admin_notices', 'genpdf_admin_notices');
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?= esc_html__('GenPDF settings', 'genpdf-woocommerce') ?></h1>
    <form action="?page=genpdf_menu" method="post">
        <?php wp_nonce_field('genpdf_settings', 'genpdf_settings_value'); ?>
        <table class="form-table indent-children" role="presentation" width="100%" id="genpdf_table">
            <tbody>
                <tr>
                    <td><strong>Logo - <?= esc_html__('Set the link of the logo to insert into the pdf', 'genpdf-woocommerce') ?></strong></td>
                    <td>
                        <div style="max-height: 200px; max-width: 400px;">
                            <?php
                            // Usa $genpdf passato da AdminGenPDF.php o crea una nuova istanza
                            if (!isset($genpdf)) {
                                $genpdf = new \GenPDF\GenPDF();
                            }
                            echo $genpdf->getLogo() ?: esc_html__('No logo set', 'genpdf-woocommerce');
                            ?>
                        </div>
                        <input type="url" name="logo" value="<?= esc_attr($genpdf->getOption('logo')) ?>" pattern="https://.*" style="width:100%" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong><?= esc_html__('List of emails separated by ","', 'genpdf-woocommerce') ?></strong></p>
                        <p><?= esc_html__('Add one or more to receive the email order with attachments.', 'genpdf-woocommerce') ?></p>
                    </td>
                    <td>
                        <input type="text" name="admin_emails" value="<?= esc_attr($genpdf->getOption('emails_cc')) ?>" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong><?= esc_html__('Template email customer', 'genpdf-woocommerce') ?></strong></p>
                    </td>
                    <td>
                        <?php $template_id = $genpdf->getOption('customer_email_template'); ?>
                        <select name="customer_email_template" required>
                            <option value="" <?= empty($template_id) ? 'selected' : '' ?>><?= esc_html__('Select a template', 'genpdf-woocommerce') ?></option>
                            <?php if (!empty($list_template)) {
                                foreach ($list_template as $template) { ?>
                                    <option value="<?= esc_attr($template->ID) ?>" <?= selected($template->ID, $template_id, false) ?>>
                                        <?= esc_html($template->post_title) ?>
                                    </option>
                                <?php }
                            } else { ?>
                                <option value="" disabled><?= esc_html__('No templates available', 'genpdf-woocommerce') ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong><?= esc_html__('Template email admin', 'genpdf-woocommerce') ?></strong></p>
                    </td>
                    <td>
                        <?php $template_id = $genpdf->getOption('admin_email_template'); ?>
                        <select name="admin_email_template" required>
                            <option value="" <?= empty($template_id) ? 'selected' : '' ?>><?= esc_html__('Select a template', 'genpdf-woocommerce') ?></option>
                            <?php if (!empty($list_template)) {
                                foreach ($list_template as $template) { ?>
                                    <option value="<?= esc_attr($template->ID) ?>" <?= selected($template->ID, $template_id, false) ?>>
                                        <?= esc_html($template->post_title) ?>
                                    </option>
                                <?php }
                            } else { ?>
                                <option value="" disabled><?= esc_html__('No templates available', 'genpdf-woocommerce') ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button class="button button-primary"><?= esc_html__('Save', 'genpdf-woocommerce') ?></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>