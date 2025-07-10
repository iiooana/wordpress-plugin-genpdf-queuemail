<?php

function DSCFW_save_image( $base64_img, $title ) {

	// Upload dir.
	$upload_dir  = wp_upload_dir();
	$genpdf_folder = apply_filters('genpdf_get_signature_folder','');
	$genpdf_ok = false;
	if( !empty($genpdf_folder['path']) && is_dir($genpdf_folder['path'])){
		$upload_dir  = $genpdf_folder;
		$genpdf_ok = true;
	}

	$upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

	$img             = str_replace( 'data:image/png;base64,', '', $base64_img );
	$img             = str_replace( ' ', '+', $img );
	$decoded         = base64_decode( $img );
	$filename        = $title.'.jpeg';
	$file_type       = 'image/jpeg';
	$hashed_filename = md5( $filename . microtime() ) . '_' . $filename;

	// Save the image in the uploads directory.
	$upload_file = file_put_contents( $upload_path . $hashed_filename, $decoded);
	$attachment = array(
		'post_mime_type' => $file_type,
		'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $hashed_filename ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
		'guid'           => $upload_dir['url'] . '/' . basename( $hashed_filename )
	);

	$attach_id = wp_insert_attachment( $attachment,$genpdf_ok === false ? $upload_dir['path'] : $upload_dir['subdir'] . '/' . $hashed_filename );
	require_once ABSPATH . 'wp-admin/includes/image.php' ;
	// $attach_data = wp_generate_attachment_metadata( $attach_id, $hashed_filename );
	// wp_update_attachment_metadata( $attach_id, $attach_data );
return $attach_id;
}
//add_action( 'woocommerce_admin_order_data_after_billing_address', 'DSCFW_checkout_field_display_admin_order_meta', 10, 1 );	
/*
if($signature_order_position == 'inside_order_detail'){
	add_action('woocommerce_order_details_after_order_table_items', 'DSCFW_order_details');
}else{
	add_action('woocommerce_order_details_after_customer_details', 'DSCFW_order_details');
}*/
function DSCFW_product_get_data($post){
	// print_r($_REQUEST['signpad']);
	// exit();       
	$signature_imgid = false;
	if (is_string($_REQUEST['signpad']) && strrpos($_REQUEST['signpad'], "data:image/png;base64", -strlen($_REQUEST['signpad'])) !== FALSE){

		$signature_imgid = DSCFW_save_image(  $_REQUEST['signpad'],'fff' );
                 
	}
	if (isset($_REQUEST['signpad']) && $signature_imgid !== false){
		update_post_meta($post, 'signpad', $signature_imgid);
		update_post_meta($signature_imgid, '_wp_attachment_image_alt', "Firma ordine #".$post);
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