<?php

use GenPDF\OrderGenPDF;
use GenPDF\GenPDF;
use GenPDF\OrderEmailGenPDF;

//add every minute wordpress cron
add_filter('cron_schedules', function ($schedules) {
	$schedules['every_minute'] = [
		'interval' => 60,
		'display'  => esc_html__('GenPDF every minute')
	];
	return $schedules;
});

add_action('wp_loaded', function () {
	if (!wp_next_scheduled('genpdf_cron')) {
		wp_schedule_event(time(), 'every_minute', 'genpdf_cron');
	}
});

add_action('genpdf_cron', function () {
	global $wpdb;
	$genpdf = new GenPDF();
	$array_settings = $genpdf->getArraySettings();
	$wpdb->query("START TRANSACTION");
	try {
		$order_queue = OrderEmailGenPDF::getOrderToSendEmail();	
		if (!empty($order_queue)) {
			$genpdf_order = new OrderGenPDF($order_queue['order_id']);
			$customer_info = $genpdf_order->getCustomerInfo();
		    //	genpdf_vardie($order_queue,$genpdf_order,$customer_info);
			$attachments = $genpdf_order->getAttachmentsPDF($array_settings['temp_dir']);
		
			if (empty($attachments)) {
				OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
					"status" => "error",
					"message" => "There aren't any attachments for the email."
				]);
				$wpdb->query("COMMIT");
				die("There aren't any attachments for the email.");
			}
			//genpdf_vardie("bonifico", $genpdf_order->order['status']);
			//region check if the payment is ok or not
			if (!empty($genpdf_order->order['status']) && in_array($genpdf_order->order['status'], $array_settings['ok_status_order'])) {
			    
				if (!empty($customer_info['email']) && filter_var($customer_info['email'], FILTER_VALIDATE_EMAIL)) {
					$message =  $array_settings['templates']['customer'];
					$message = str_replace('[numero_ordine]', $genpdf_order->order_id, $message);
					$message = str_replace('[nome_cliente]', $customer_info['first_name'], $message);
					$message = str_replace('[blogname]', $genpdf->getOption('blogname'), $message);
					$headers = array('Content-Type: text/html; charset=UTF-8');
					if (wp_mail($customer_info['email'], "Ordine #" . $genpdf_order->order_id . " - Bonifico", $message, $headers, $attachments)) {
						OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
							"status" => "success_email_customer",
							"email_to" => $customer_info['email'],
						], true);
					} else {
						OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
							"status" => "error",
							"message" => "I can't send email to customer " . $customer_info['email'] . "."
						]);
					}
				} else {
					OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
						"status" => "error",
						"message" => "The email " . $customer_info['email'] . " is not valid " . $array_settings['cc'] . "."
					]);
				}
				
			} else if ($genpdf_order->isBonifico()) {
				if (empty($order_queue['has_sent_email_admin'])) {
					//email only to admin
					if (!empty($array_settings['cc']) && filter_var($array_settings['cc'], FILTER_VALIDATE_EMAIL)) {
					
						$message =  $array_settings['templates']['admin'];
						$message = str_replace('[numero_ordine]', $genpdf_order->order_id, $message);
						$message = str_replace('[nome_cliente]', $customer_info['first_name'], $message);
						$message = str_replace('[cognome_nome]', $customer_info['last_name'], $message);
						$message = str_replace('[blogname]', $genpdf->getOption('blogname'), $message);
				
						$headers = array('Content-Type: text/html; charset=UTF-8');
						if (wp_mail($array_settings['cc'], "Ordine #" . $genpdf_order->order_id . " - Bonifico", $message, $headers, $attachments)) {
							
							OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
								"status" => "success_email_admin",
								"email_to" => $array_settings['cc'],
							], true);
							//update has_sent_email admin
							OrderEmailGenPDF::setHasSentEmailAdmin($genpdf_order->order_id);
							//endregion
						} else {
							OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
								"status" => "error",
								"message" => "I can't send email to admin " . $array_settings['cc'] . "."
							]);
						}
					} else {
						OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
							"status" => "error",
							"message" => "There is not email for option _genpdf_email_cc " . $array_settings['cc'] . "."
						]);
					}
				}
			} else {
				OrderEmailGenPDF::UpdateOrderEmail($order_queue['order_id'], [
					"status" => "error",
					"message" => "The status order is: " . $genpdf_order->order['status'] . "."
				]);
			}
			$genpdf_order->deleteAttachments($attachments);
		}
		$wpdb->query("COMMIT");
	} catch (\Exception $e) {
		//always commit
		var_dump("[ERROR-GENPDF-CRON]".$e->getMessage());
		$wpdb->query("COMMIT");
	}

});

