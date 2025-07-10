<?php

function DSCFW_save_image($base64_img, $title) {
    // Decodifica l'immagine base64
    $img = str_replace('data:image/png;base64,', '', $base64_img);
    $img = str_replace(' ', '+', $img);
    $decoded = base64_decode($img);

    // Directory di upload
    $upload_dir = wp_upload_dir();
    $genpdf_folder = apply_filters('genpdf_get_signature_folder', '');
    if (!empty($genpdf_folder['path']) && is_dir($genpdf_folder['path'])) {
        $upload_dir = $genpdf_folder; // Usa wp-content/signatures/
    }

    $upload_path = str_replace('/', DIRECTORY_SEPARATOR, $upload_dir['path']) . DIRECTORY_SEPARATOR;

    // Nome file unico
    $filename = $title . '.jpeg';
    $hashed_filename = md5($filename . microtime()) . '_' . $filename;

    // Salva il file
    $upload_file = file_put_contents($upload_path . $hashed_filename, $decoded);
    if ($upload_file === false) {
        return false; // Gestione errore
    }

    // Ritorna il percorso completo del file
    return $upload_path . $hashed_filename;
}
//add_action( 'woocommerce_admin_order_data_after_billing_address', 'DSCFW_checkout_field_display_admin_order_meta', 10, 1 );	
/*
if($signature_order_position == 'inside_order_detail'){
	add_action('woocommerce_order_details_after_order_table_items', 'DSCFW_order_details');
}else{
	add_action('woocommerce_order_details_after_customer_details', 'DSCFW_order_details');
}*/
function DSCFW_product_get_data($post) {
    $signature_filepath = false;
    if (is_string($_REQUEST['signpad']) && strpos($_REQUEST['signpad'], "data:image/png;base64") === 0) {
        $signature_filepath = DSCFW_save_image($_REQUEST['signpad'], 'fff');
    }
    if (isset($_REQUEST['signpad']) && $signature_filepath !== false) {
        // Salva il percorso del file come metadato
        update_post_meta($post, 'signpad', $signature_filepath);
    }
}
//disable signature on email
/* Display Signature Thankyou Page */
/*$signature_order_position = get_option('signature_order_position','inside_order_detail');

if($signature_order_position == 'inside_order_detail'){
	add_action('woocommerce_order_details_after_order_table_items', 'DSCFW_order_details');
}else{
	add_action('woocommerce_order_details_after_customer_details', 'DSCFW_order_details');
}
function DSCFW_order_details($order){
	$post_attach_file = get_post_meta($order->get_id(),'signpad',true);
	$signimage = wp_get_attachment_url($post_attach_file, true);
	if(!empty($signimage)){
	?>
	<section class="woocommerce-customer-details mycustomsection">
		<div class="woocommerce-order-details__title">
			<h2 class="signatureheading"><?php echo esc_html('Your Signature','digital-signature-checkout-for-woocommerce-pro'); ?></h2>
			<img src="<?Php echo esc_url($signimage); ?>" width="200" height="300">
		</div>
	</section>
	<?php
	}
}


add_action( 'woocommerce_admin_order_data_after_billing_address', 'DSCFW_checkout_field_display_admin_order_meta', 10, 1 );
function DSCFW_checkout_field_display_admin_order_meta($order){
	// $post_attach_file = $order->get_meta( 'signpad' );
	$post_attach_file = get_post_meta($order->get_id(),'signpad',true);
	$signimage = wp_get_attachment_url($post_attach_file, true);
	if(!empty($signimage)){
    ?>
    	<img src="<?Php echo esc_url($signimage); ?>" width="100" height="100">
    <?php
	}
}

add_action( 'woocommerce_email_order_details', 'DSCFW_action_wc_email_order_details', 50, 4 );
function DSCFW_action_wc_email_order_details( $order, $sent_to_admin, $plain_text, $email ){
	// $post_attach_file = $order->get_meta( 'signpad' );
	$post_attach_file = get_post_meta($order->get_id(),'signpad',true);
	$signimage = wp_get_attachment_url($post_attach_file, true);
	if(!empty($signimage)){
    ?>
        <h2 class="signatureheading"><?php echo esc_html('Your Signature','digital-signature-checkout-for-woocommerce-pro'); ?></h2>
    	<img src="<?Php echo esc_url($signimage); ?>" width="100" height="100">
    <?php
	}
}*/