<?php

use GenPDF\OrderGenPDF;
use Dompdf\Dompdf;
use Dompdf\Options;
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
	error_log("genpdf_cron START");
	global $wpdb;
	$genpdf = new GenPDF();
	$array_settings = $genpdf->getArraySettings();

	//region genpdf cron
	$orders = OrderEmailGenPDF::getOrdersToSendEmails();
	if (!empty($orders) && is_array($orders) && count($orders) > 0) {

		foreach ($orders as $order_email) {
			//todo lock access row db
			genpdf_vardie($order_email);
			$genpdf_order = new OrderGenPDF($order_email['order_id']);
			$customer_info = $genpdf_order->getCustomerInfo();
			$customer_info['email'] = 'ioana2502@yahoo.it';//todo remove and update the option on db
			genpdf_vardie($customer_info);
			//genpdf_vardie($genpdf_order->order,$genpdf_order->isBonifico());
			$attachments = $genpdf_order->getAttachmentsPDF($array_settings['temp_dir']);
			if (empty($attachments)) {
				OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
					"status" => "error",
					"message" => "There aren't any attachments for the email."
				]);
				break;
			}
			if ($genpdf_order->isBonifico()) {
				//genpdf_vardie($order_email);
				if (empty($order_email['has_sent_email_admin'])) {
					//email only to admin
					if (!empty($array_settings['cc']) && filter_var($array_settings['cc'], FILTER_VALIDATE_EMAIL)) {
						$message =  $array_settings['templates']['admin'];
						$message = str_replace('[numero_ordine]', $genpdf_order->order_id, $message);
						$message = str_replace('[nome_cliente]', $customer_info['first_name'], $message);
						$message = str_replace('[cognome_nome]', $customer_info['last_name'], $message);
						$message = str_replace('[blogname]', $genpdf->getOption('blogname'), $message);
						//genpdf_vardie($message);
						$headers = array('Content-Type: text/html; charset=UTF-8');
						if (wp_mail($array_settings['cc'], "Ordine #" . $genpdf_order->order_id . " - Bonifico", $message, $headers, $attachments)) {
							OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
								"status" => "success_email_admin",
								"email_to" => $array_settings['cc'],
							], true);
							//update has_sent_email admin
							OrderEmailGenPDF::setHasSentEmailAdmin($genpdf_order->order_id);
							//endregion
						} else {
							OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
								"status" => "error",
								"message" => "I can't send email to admin " . $array_settings['cc'] . "."
							]);
						}
					} else {
						OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
							"status" => "error",
							"message" => "There is not email for option _genpdf_email_cc " . $array_settings['cc'] . "."
						]);
					}

				}
			} else {
				//region check if the payment is ok or not
				if ($genpdf_order->order['status'] == 'wc-processing') {
					if (!empty($customer_info['email']) && filter_var($customer_info['email'], FILTER_VALIDATE_EMAIL)) {
						$message =  $array_settings['templates']['customer'];
						$message = str_replace('[numero_ordine]', $genpdf_order->order_id, $message);
						$message = str_replace('[nome_cliente]', $customer_info['first_name'], $message);
						$message = str_replace('[blogname]', $genpdf->getOption('blogname'), $message);
						$headers = array('Content-Type: text/html; charset=UTF-8');
						if (wp_mail($customer_info['email'], "Ordine #" . $genpdf_order->order_id . " - Bonifico", $message, $headers, $attachments)) {
							OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
								"status" => "success_email_customer",
								"email_to" => $customer_info['email'],
							], true);
						} else {
							OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
								"status" => "error",
								"message" => "I can't send email to customer " . $customer_info['email'] . "."
							]);
						}
					} else {
						OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
							"status" => "error",
							"message" => "The email " . $customer_info['email'] . " is not valid " . $array_settings['cc'] . "."
						]);
					}
				} else {
					OrderEmailGenPDF::UpdateOrderEmail($order_email['order_id'], [
						"status" => "error",
						"message" => "The status order is not wc-processing, is: " . $genpdf_order->order['status'] . "."
					]);
				}
				//endregion
			}

			$genpdf_order->deleteAttachments($attachments);
		}
	}
	//endregion
	error_log("genpdf_cron END");
});
