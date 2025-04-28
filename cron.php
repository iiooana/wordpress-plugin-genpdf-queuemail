<?php

use GenPDF\OrderGenPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
use GenPDF\OrderEmailGenPDF;

//add every minute wordpress cron
add_filter('cron_schedules', function ($schedules) {
	$schedules['every_minute'] = [
		'interval' => 60,
		'display'  => esc_html__('GenPDF every minute')
	];
	return $schedules;
});

add_action('init', function() {
    // Log per verificare se il cron è stato registrato
    if (!wp_next_scheduled('genpdf_cron')) {
        wp_schedule_event(time(), 'every_minute', 'genpdf_cron');
        error_log('genpdf_cron scheduled.');
    }
});

// 3. Hook your genpdf_cron to run
add_action('genpdf_cron', function () {
	global $wpdb;
	//region genpdf cron
	$orders = OrderEmailGenPDF::getOrdersToSendEmails();

	if (!empty($orders) && is_array($orders) && count($orders) > 0) {
		//region dompdf
		$options_dompdf = new Options();
		$options_dompdf->set('defaultFont', 'helvetica');
		$options_dompdf->set('isRemoteEnabled', true);
		//endregion
		$table = $wpdb->base_prefix . "wc_orders";
		foreach ($orders as $order_email) {
			$result = $wpdb->get_row($wpdb->prepare("select billing_email from {$table} where id =  %d limit 1", [$order_email['order_id']]), ARRAY_A);
			if (!empty($result['billing_email'])) {
				$email_to =  $result['billing_email'];
				$email_to =  'ioana2502@yahoo.it';
				$cc = 'ioanaudia7@gmail.com';
				$subject = 'Ordine #' . $order_email['order_id'];

				$genpdf_order = new OrderGenPDF($order_email['order_id']);
				$products = $genpdf_order->getProductsDetail();
				//region generate pdf files
				if (!empty($products)) {
					$attachments =  [];
					foreach ($products as $product) {
						if (!empty($product['meta_value']) && json_validate($product['meta_value'])) {
							$product_json = json_decode($product['meta_value'], true);
							// genpdf_vardie($customer_email, $product, $product_json);
							ob_clean();
							$dompdf = new Dompdf($options_dompdf);
							// genpdf_vardie($genpdf_order,$genpdf_order->getPDF($product_json['product_id']));
							$dompdf->loadHtml($genpdf_order->getPDF($product_json['product_id']));
							$dompdf->render();
							$output = $dompdf->output();
							$temp_file = tmpfile(); //create temp file      
							fwrite($temp_file, $output); //add the content
							$file_metadata = stream_get_meta_data($temp_file);
							$attachments[] = $file_metadata['uri'];
							// genpdf_vardie($file_metadata);
							ob_end_clean();
						}
					}
					if (!empty($attachments) && is_array($attachments) && count($attachments) > 0) {
						$message = 'Ciao, in allegato trovi i moduli dei corsi.';
						$headers = array('Content-Type: text/html; charset=UTF-8');
						// Invio l'email
						if (wp_mail($email_to, $subject, $message, $headers, array_values($attachments))) {
							OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
								"status" => "success",
							], true);
						} else {
							OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
								"status" => "error",
								"message" => "The is a problem with wp_emai."
							]);
						}
					} else {
						OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
							"status" => "error",
							"message" => "There aren't any attachments for the email."
						]);
					}
				} else {
					OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
						"status" => "error",
						"message" => "There aren't any products into the order."
					]);
				}
				//endregion
			} else {
				OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
					"status" => "error",
					"message" => "I don't find the the billing_email to send the email."
				]);
			}
		}
	}
	//endregion
});
