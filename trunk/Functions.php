<?php
/*
 Created by TwinCitiesTech.com
 (website: twincitiestech.com       email : support@twincitiestech.com)

 Modified by S H Mohanjith
 (website: mohanjith.com       email : support@mohanjith.com)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; version 3 of the License, with the
 exception of the JQuery JavaScript framework which is released
 under it's own license.  You may view the details of that license in
 the prototype.js file.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

setlocale(LC_MONETARY, 'en_US');

function web_invoice_number_of_invoices()
{
	global $wpdb;
	$query = "SELECT COUNT(*) FROM ".Web_Invoice::tablename('main')."";
	$count = $wpdb->get_var($query);
	return $count;
}

function web_invoice_does_invoice_exist($invoice_id) {
	global $wpdb;
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	return $invoice->id;
}

function web_invoice_validate_cc_number($cc_number) {
	/* Validate; return value is card type if valid. */
	$false = false;
	$card_type = "";
	$card_regexes = array(
      "/^4\d{12}(\d\d\d){0,1}$/" => "visa",
      "/^5[12345]\d{14}$/"       => "mastercard",
      "/^3[47]\d{13}$/"          => "amex",
      "/^6011\d{12}$/"           => "discover",
      "/^30[012345]\d{11}$/"     => "diners",
      "/^3[68]\d{12}$/"          => "diners",
	);

	foreach ($card_regexes as $regex => $type) {
		if (preg_match($regex, $cc_number)) {
			$card_type = $type;
			break;
		}
	}

	if (!$card_type) {
		return $false;
	}

	/*  mod 10 checksum algorithm  */
	$revcode = strrev($cc_number);
	$checksum = 0;

	for ($i = 0; $i < strlen($revcode); $i++) {
		$current_num = intval($revcode[$i]);
		if($i & 1) {  /* Odd  position */
			$current_num *= 2;
		}
		/* Split digits and add. */
		$checksum += $current_num % 10; if
		($current_num >  9) {
			$checksum += 1;
		}
	}

	if ($checksum % 10 == 0) {
		return $card_type;
	} else {
		return $false;
	}
}

function web_invoice_update_log($invoice_id,$action_type,$value)
{
	global $wpdb;
	if(isset($invoice_id))
	{
		$time_stamp = date("Y-m-d h-i-s");
		$wpdb->query("INSERT INTO ".Web_Invoice::tablename('log')."
	(invoice_id , action_type , value, time_stamp)
	VALUES ('$invoice_id', '$action_type', '$value', '$time_stamp');");
	}
}

function web_invoice_query_log($invoice_id,$action_type) {
	global $wpdb;
	if($results = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('log')." WHERE invoice_id = '$invoice_id' AND action_type = '$action_type' ORDER BY 'time_stamp' DESC")) return $results;
}

function web_invoice_meta($invoice_id,$meta_key)
{
	global $wpdb;
	global $_web_invoice_meta_cache;

	if (!isset($_web_invoice_meta_cache[$invoice_id][$meta_key]) || !$_web_invoice_meta_cache[$invoice_id][$meta_key]) {
		$_web_invoice_meta_cache[$invoice_id][$meta_key] = $wpdb->get_var("SELECT meta_value FROM `".Web_Invoice::tablename('meta')."` WHERE meta_key = '$meta_key' AND invoice_id = '$invoice_id'");
	}

	return $_web_invoice_meta_cache[$invoice_id][$meta_key];
}

function web_invoice_payment_register($invoice_id, $amount, $trx_id = "", $status = 0) {
	global $wpdb;
	global $_web_invoice_payment_cache;
	
	if (empty($trx_id)) {
		$trx_id = uniqid('web_invoice_');
	}
	$wpdb->query("INSERT INTO `".Web_Invoice::tablename('payment')."` (invoice_id, trx_id, amount, status) VALUES ('$invoice_id', '$trx_id', '$amount', '$status')");
	$_web_invoice_payment_cache[$trx_id] = mysql_insert_id();
	
	return $_web_invoice_payment_cache[$trx_id];
}

function web_invoice_payment_update_status($trx_id, $status = 0) {
	global $wpdb;
	global $_web_invoice_payment_cache;
	
	$wpdb->query("UPDATE `".Web_Invoice::tablename('payment')."` SET status = '$status' WHERE trx_id = '$trx_id';");
	$_web_invoice_payment_cache[$trx_id] = $trx_id;
	
	return $_web_invoice_payment_cache[$trx_id];
}

function web_invoice_sum_payments($invoice_id) {
	global $wpdb;
	
	$sum = $wpdb->get_var("SELECT SUM(amount) FROM `".Web_Invoice::tablename('payment')."` WHERE invoice_id = '$invoice_id'");
	
	return $sum;
}

function web_invoice_payments($invoice_id, $status = 0) {
	global $wpdb;
	
	$rows = $wpdb->get_results("SELECT * FROM `".Web_Invoice::tablename('payment')."` WHERE invoice_id = '$invoice_id' AND status = '$status'");
	
	return $rows;
}

function web_invoice_get_invoice_id_by_payment($trx_id) {
	global $wpdb;
	
	$invoice_id = $wpdb->get_var("SELECT invoice_id FROM `".Web_Invoice::tablename('payment')."` WHERE trx_id = '$trx_id'");
	
	return $invoice_id;
}

function web_invoice_payment_meta($payment_id, $meta_key)
{
	global $wpdb;
	global $_web_invoice_payment_meta_cache;

	if (!isset($_web_invoice_payment_meta_cache[$payment_id][$meta_key]) || !$_web_invoice_payment_meta_cache[$payment_id][$meta_key]) {
		$_web_invoice_payment_meta_cache[$payment_id][$meta_key] = $wpdb->get_var("SELECT meta_value FROM `".Web_Invoice::tablename('payment_meta')."` WHERE meta_key = '$meta_key' AND payment_id = '$payment_id'");
	}

	return $_web_invoice_payment_meta_cache[$payment_id][$meta_key];
}

function web_invoice_update_invoice_meta($invoice_id,$meta_key,$meta_value)
{
	global $wpdb;
	global $_web_invoice_meta_cache;
	
	if(empty($meta_value)) {
		// Delete meta_key if no value is set
		$wpdb->query("DELETE FROM ".Web_Invoice::tablename('meta')." WHERE  invoice_id = '$invoice_id' AND meta_key = '$meta_key'");
	}
	else
	{
		// Check if meta key already exists, then we replace it Web_Invoice::tablename('meta')
		if($wpdb->get_var("SELECT meta_key 	FROM `".Web_Invoice::tablename('meta')."` WHERE meta_key = '$meta_key' AND invoice_id = '$invoice_id'"))
		{ $wpdb->query("UPDATE `".Web_Invoice::tablename('meta')."` SET meta_value = '$meta_value' WHERE meta_key = '$meta_key' AND invoice_id = '$invoice_id'"); }
		else
		{ $wpdb->query("INSERT INTO `".Web_Invoice::tablename('meta')."` (invoice_id, meta_key, meta_value) VALUES ('$invoice_id','$meta_key','$meta_value')"); }
	}

	if (isset($_web_invoice_meta_cache[$invoice_id][$meta_key])) {
		$_web_invoice_meta_cache[$invoice_id][$meta_key] = $meta_value;
	}
}

function web_invoice_update_payment_meta($payment_id,$meta_key,$meta_value)
{
	global $wpdb;
	global $_web_invoice_payment_meta_cache;
	
	if(empty($meta_value)) {
		// Delete meta_key if no value is set
		$wpdb->query("DELETE FROM ".Web_Invoice::tablename('payment_meta')." WHERE  payment_id = '$payment_id' AND meta_key = '$meta_key'");
	}
	else
	{
		// Check if meta key already exists, then we replace it Web_Invoice::tablename('payment_meta')
		if($wpdb->get_var("SELECT meta_key 	FROM `".Web_Invoice::tablename('payment_meta')."` WHERE meta_key = '$meta_key' AND payment_id = '$payment_id'"))
		{ $wpdb->query("UPDATE `".Web_Invoice::tablename('payment_meta')."` SET meta_value = '$meta_value' WHERE meta_key = '$meta_key' AND payment_id = '$payment_id'"); }
		else
		{ $wpdb->query("INSERT INTO `".Web_Invoice::tablename('payment_meta')."` (payment_id, meta_key, meta_value) VALUES ('$payment_id','$meta_key','$meta_value')"); }
	}
	
	if (isset($_web_invoice_payment_meta_cache[$payment_id][$meta_key])) {
		$_web_invoice_payment_meta_cache[$payment_id][$meta_key] = $meta_value;
	}
}

function web_invoice_delete_invoice_meta($invoice_id,$meta_key='')
{
	global $wpdb;
	global $_web_invoice_meta_cache;

	
	if(empty($meta_key))
	{ $wpdb->query("DELETE FROM `".Web_Invoice::tablename('meta')."` WHERE invoice_id = '$invoice_id' ");}
	else
	{ $wpdb->query("DELETE FROM `".Web_Invoice::tablename('meta')."` WHERE invoice_id = '$invoice_id' AND meta_key = '$meta_key'");}
	
	if (isset($_web_invoice_meta_cache[$invoice_id][$meta_key])) {
		$_web_invoice_meta_cache[$invoice_id][$meta_key] = false;
	}
}

function web_invoice_delete_payment_meta($payment_id,$meta_key='')
{
	global $wpdb;
	global $_web_invoice_payment_meta_cache;
	
	if(empty($meta_key))
	{ $wpdb->query("DELETE FROM `".Web_Invoice::tablename('payment_meta')."` WHERE invoice_id = '$payment_id' ");}
	else
	{ $wpdb->query("DELETE FROM `".Web_Invoice::tablename('payment_meta')."` WHERE invoice_id = '$payment_id' AND meta_key = '$meta_key'");}

	if (isset($_web_invoice_payment_meta_cache[$payment_id][$meta_key])) {
		unset($_web_invoice_payment_meta_cache[$payment_id][$meta_key]);
	}
}

function web_invoice_delete($invoice_id) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		$counter=0;
		foreach ($invoice_id as $single_invoice_id) {
			$counter++;
			
			if (web_invoice_meta($single_invoice_id, 'subscription_id') &&	web_invoice_meta($single_invoice_id, 'recurring_transaction_id')) {
				require_once('gateways/payflowpro.class.php');
				
				$pfp = new Web_Invoice_PayflowProRecurring();
				$pfp->deleteProfile(web_invoice_meta($single_invoice_id, 'subscription_id'));
			}
			
			$wpdb->query("DELETE FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '$single_invoice_id'");

			do_action('web_invoice_delete', $single_invoice_id);
			web_invoice_update_log($single_invoice_id, "deleted", "Deleted on ");

			// Get all meta keys for this invoice, then delete them

			$all_invoice_meta_values = $wpdb->get_col("SELECT invoice_id FROM ".Web_Invoice::tablename('meta')." WHERE invoice_id = '$single_invoice_id'");

			foreach ($all_invoice_meta_values as $meta_key) {
				web_invoice_delete_invoice_meta($single_invoice_id);
			}
		}
		return $counter . " invoice(s) successfully deleted.";
	} else {	
		if (web_invoice_meta($single_invoice_id, 'subscription_id') &&	web_invoice_meta($single_invoice_id, 'recurring_transaction_id')) {
			require_once('gateways/payflowpro.class.php');
				
			$pfp = new Web_Invoice_PayflowProRecurring();
			if ($pfp->deleteProfile(web_invoice_meta($single_invoice_id, 'subscription_id'))) {
				web_invoice_delete_invoice_meta($single_invoice_id, 'subscription_id');
				web_invoice_update_log($invoice_id, 'pfp_subscription_update', "Subscription cancelled. REF: ".$pfp->getRef());
			}
		}
			
		// Delete Single
		$wpdb->query("DELETE FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '$invoice_id'");
		// Make log entry
		
		do_action('web_invoice_delete', $invoice_id);
		web_invoice_update_log($invoice_id, "deleted", "Deleted on ");
		
		$all_invoice_meta_values = $wpdb->get_col("SELECT invoice_id FROM ".Web_Invoice::tablename('meta')." WHERE invoice_id = '$single_invoice_id'");

		foreach ($all_invoice_meta_values as $meta_key) {
			web_invoice_delete_invoice_meta($single_invoice_id);
		}
		
		return "Invoice successfully deleted.";
	}
}

function web_invoice_archive($invoice_id) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		$counter=0;
		foreach ($invoice_id as $single_invoice_id) {
			$counter++;
			web_invoice_update_invoice_meta($single_invoice_id, "archive_status", "archived");
		}
		return $counter . " invoice(s) archived.";

	}
	else
	{
		web_invoice_update_invoice_meta($invoice_id, "archive_status", "archived");
		return "Invoice successfully archived.";
	}
}

function web_invoice_mark_as_paid($invoice_id) {
	global $wpdb;

	$counter=0;
	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		foreach ($invoice_id as $single_invoice_id) {
			$counter++;
			web_invoice_update_invoice_meta($single_invoice_id,'paid_status','paid');
			web_invoice_update_log($single_invoice_id,'paid',"Invoice marked as paid");
			if(get_option('web_invoice_send_thank_you_email') == 'yes') web_invoice_send_email_receipt($single_invoice_id);
			
			do_action('web_invoice_mark_as_paid', $single_invoice_id);
		}

		if(get_option('web_invoice_send_thank_you_email') == 'yes') {
			return $counter . " invoice(s) marked as paid, and thank you email sent to customer.";
		}
		else{
			return $counter . " invoice(s) marked as paid.";
		}
	}
	else
	{
		$counter++;
		web_invoice_update_invoice_meta($invoice_id,'paid_status','paid');
		web_invoice_update_log($invoice_id,'paid',"Invoice marked as paid");
		if(get_option('web_invoice_send_thank_you_email') == 'yes') web_invoice_send_email_receipt($invoice_id);
		do_action('web_invoice_mark_as_paid', $invoice_id);
			
		if(get_option('web_invoice_send_thank_you_email') == 'yes') {
			return $counter . " invoice marked as paid, and thank you email sent to customer.";
		}
		else{
			return $counter . " invoice marked as paid.";
		}
	}
}

function web_invoice_mark_as_cancelled($invoice_id) {
	global $wpdb;

	$counter=0;
	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		foreach ($invoice_id as $single_invoice_id) {
			if (!web_invoice_paid_status($single_invoice_id)) continue; 
			$counter++;
			web_invoice_update_invoice_meta($single_invoice_id,'paid_status','cancelled');
			web_invoice_update_log($single_invoice_id,'paid',"Invoice marked as cancelled");
			
			do_action('web_invoice_mark_as_cancel', $single_invoice_id);
		}

		return $counter . " invoice(s) marked as cancelled.";
	}
	else
	{
		if (web_invoice_paid_status($single_invoice_id)) {
			$counter++;
			web_invoice_update_invoice_meta($invoice_id,'paid_status','cancelled');
			web_invoice_update_log($invoice_id,'paid',"Invoice marked as cancelled");
			
			do_action('web_invoice_mark_as_cancel', $invoice_id);
			
			return $counter . " invoice marked as cancelled.";
		} else {
			return "No invoices marked as cancelled.";
		}
	}
}

function web_invoice_unarchive($invoice_id) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		$counter=0;
		foreach ($invoice_id as $single_invoice_id) {
			$counter++;
			web_invoice_delete_invoice_meta($single_invoice_id, "archive_status");
		}
		return $counter . " invoice(s) unarchived.";

	}
	else
	{
		web_invoice_delete_invoice_meta($invoice_id, "archive_status");
		return "Invoice successfully unarchived.";
	}
}

function web_invoice_mark_as_sent($invoice_id) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		$counter=0;
		foreach ($invoice_id as $single_invoice_id) {
			$counter++;
			web_invoice_update_invoice_meta($single_invoice_id, "sent_date", date("Y-m-d", time()));
			web_invoice_update_log($single_invoice_id,'contact','Invoice Maked as eMailed'); //make sent entry

		}
		return $counter . " invoice(s) marked as sent.";

	}
	else
	{
		web_invoice_update_invoice_meta($invoice_id, "sent_date", date("Y-m-d", time()));
		web_invoice_update_log($invoice_id,'contact','Invoice Maked as eMailed'); //make sent entry

		return "Invoice market as sent.";
	}
}

function web_invoice_get_invoice_attrib($invoice_id,$attribute)
{
	global $wpdb;
	$query = "SELECT $attribute FROM ".Web_Invoice::tablename('main')." WHERE invoice_num=".$invoice_id."";
	return $wpdb->get_var($query);
}

function web_invoice_get_invoice_status($invoice_id,$count='1')
{
	if($invoice_id != '') {
		global $wpdb;
		$query = "SELECT * FROM ".Web_Invoice::tablename('log')."
	WHERE invoice_id = $invoice_id
	ORDER BY time_stamp DESC
	LIMIT 0 , $count";

		$status_update = $wpdb->get_results($query);

		foreach ($status_update as $single_status)
		{
			$message .= "<li>" . $single_status->value . " on <span class='web_invoice_tamp_stamp'>" . date(__('Y-m-d H:i:s'), strtotime($single_status->time_stamp)) . "</span></li>";
		}

		return $message;
	}
}

function web_invoice_clear_invoice_status($invoice_id)
{
	global $wpdb;
	if(isset($invoice_id)) {
		if($wpdb->query("DELETE FROM ".Web_Invoice::tablename('log')." WHERE invoice_id = $invoice_id"))
		return "Logs for invoice #$invoice_id cleared.";
	}
}

function web_invoice_get_single_invoice_status($invoice_id)
{
	// in class
	global $wpdb;
	if($status_update = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('log')." WHERE invoice_id = $invoice_id ORDER BY `".Web_Invoice::tablename('log')."`.`time_stamp` DESC LIMIT 0 , 1"))
	return $status_update->value . " - " . web_invoice_Date::convert($status_update->time_stamp, 'Y-m-d H', __('M d Y'));
}


function web_invoice_currency_format($amount) {
	return number_format($amount, 2, __('.', WEB_INVOICE_TRANS_DOMAIN), __(',', WEB_INVOICE_TRANS_DOMAIN));
}

function web_invoice_paid($invoice_id) {
	global $wpdb;
	//$wpdb->query("UPDATE  ".Web_Invoice::tablename('main')." SET status = 1 WHERE  invoice_num = '$invoice_id'");
	web_invoice_update_invoice_meta($invoice_id,'paid_status','paid');
	web_invoice_update_log($invoice_id,'paid',"Invoice successfully processed by ". $_SERVER['REMOTE_ADDR']);

}

function web_invoice_email_variables($invoice_id) {
	global $web_invoices_email_variables;

	$invoice_info = new Web_Invoice_GetInfo($invoice_id);
	$recipient = new Web_Invoice_GetInfo($invoice_id);

	$web_invoices_email_variables = array(
		'call_sign' => $recipient->recipient('callsign'),
		'streetaddress' => $recipient->recipient('streetaddress'), 
		'city' => $recipient->recipient('city'), 
		'zip' => $recipient->recipient('zip'), 
		'state' => $recipient->recipient('state'), 
		'country' => $recipient->recipient('country'), 
		'business_name' => stripslashes(get_option("web_invoice_business_name")),
		'recurring' => (web_invoice_recurring($invoice_id) ? " recurring " : ""),
		'amount' => $invoice_info->display('display_amount'),
		'link' => $invoice_info->display('link'),
		'business_email' => get_option("web_invoice_email_address"),
		'subject' => $invoice_info->display('subject'),
		'invoice_id' => $invoice_info->display('display_id'),
		'invoice_hash' => $invoice_info->display('invoice_hash'),
		'invoice_date' => $invoice_info->display('invoice_date'),
	);

	if($invoice_info->display('description')) {
		$web_invoices_email_variables['description'] = $invoice_info->display('description').".";
	} else {
		$web_invoices_email_variables['description'] = "";
	}
}

function web_invoice_pdf_variables($invoice_id) {
	global $web_invoices_pdf_variables;

	$invoice_info = new Web_Invoice_GetInfo($invoice_id);
	$recipient = new Web_Invoice_GetInfo($invoice_id);

	$web_invoices_pdf_variables = array(
		'call_sign' => $recipient->recipient('callsign'),
		'streetaddress' => $recipient->recipient('streetaddress'), 
		'city' => $recipient->recipient('city'), 
		'zip' => $recipient->recipient('zip'), 
		'state' => $recipient->recipient('state'), 
		'country' => $recipient->recipient('country'), 
		'business_name' => stripslashes(get_option("web_invoice_business_name")),
		'recurring' => (web_invoice_recurring($invoice_id) ? " recurring " : ""),
		'amount' => $invoice_info->display('display_amount'),
		'link' => $invoice_info->display('link'),
		'business_email' => get_option("web_invoice_email_address"),
		'subject' => $invoice_info->display('subject'),
		'invoice_id' => $invoice_info->display('display_id'),
		'invoice_hash' => $invoice_info->display('invoice_hash'),
		'content' => web_invoice_generate_pdf_content($invoice_id),
		'invoice_date' => $invoice_info->display('invoice_date'),
	);

	if($invoice_info->display('description')) {
		$web_invoices_pdf_variables['description'] = $invoice_info->display('description').".";
	} else {
		$web_invoices_pdf_variables['description'] = "";
	}
}

function web_invoice_html_variables($invoice_id) {
	global $web_invoices_html_variables;

	$invoice_info = new Web_Invoice_GetInfo($invoice_id);
	$recipient = new Web_Invoice_GetInfo($invoice_id);

	$web_invoices_html_variables = array(
		'call_sign' => $recipient->recipient('callsign'),
		'streetaddress' => $recipient->recipient('streetaddress'), 
		'city' => $recipient->recipient('city'), 
		'zip' => $recipient->recipient('zip'), 
		'state' => $recipient->recipient('state'), 
		'country' => $recipient->recipient('country'), 
		'business_name' => stripslashes(get_option("web_invoice_business_name")),
		'recurring' => (web_invoice_recurring($invoice_id) ? " recurring " : ""),
		'amount' => $invoice_info->display('display_amount'),
		'link' => $invoice_info->display('link'),
		'business_email' => get_option("web_invoice_email_address"),
		'subject' => $invoice_info->display('subject'),
		'invoice_id' => $invoice_info->display('display_id'),
		'invoice_hash' => $invoice_info->display('invoice_hash'),
		'content' => web_invoice_generate_html_content($invoice_id),
		'print_message' => sprintf(__("You can download a %s or print a copy of this invoice for your records; just 
select the 'Print' item under the 'File' menu in your browser, or use the 
&lt;CTRL&gt; + 'P' key combination to print a hard-copy in a more traditional, 
neatly laid-out format. <em>Thank you</em> for your business <em>and</em> your prompt 
payment!", WEB_INVOICE_TRANS_DOMAIN), '<a href="'.$invoice_info->display('print_link').'" class="web_invoice_pdf_link">PDF</a>'),
		'pdf_link' =>  $invoice_info->display('print_link'),
		'invoice_date' => $invoice_info->display('invoice_date'),
	);

	if($invoice_info->display('description')) {
		$web_invoices_html_variables['description'] = $invoice_info->display('description').".";
	} else {
		$web_invoices_html_variables['description'] = "";
	}
}

function web_invoice_web_variables($invoice_id) {
	global $web_invoices_web_variables;

	$invoice_info = new Web_Invoice_GetInfo($invoice_id);
	$recipient = new Web_Invoice_GetInfo($invoice_id);

	$web_invoices_web_variables = array(
		'call_sign' => $recipient->recipient('callsign'),
		'streetaddress' => $recipient->recipient('streetaddress'), 
		'city' => $recipient->recipient('city'), 
		'zip' => $recipient->recipient('zip'), 
		'state' => $recipient->recipient('state'), 
		'country' => $recipient->recipient('country'), 
		'business_name' => stripslashes(get_option("web_invoice_business_name")),
		'recurring' => (web_invoice_recurring($invoice_id) ? " recurring " : ""),
		'amount' => $invoice_info->display('display_amount'),
		'link' => $invoice_info->display('link'),
		'business_email' => get_option("web_invoice_email_address"),
		'subject' => $invoice_info->display('subject'),
		'invoice_id' => $invoice_info->display('display_id'),
		'invoice_hash' => $invoice_info->display('invoice_hash'),
		'invoice_date' => $invoice_info->display('invoice_date'),
	);

	if($invoice_info->display('description')) {
		$web_invoices_web_variables['description'] = $invoice_info->display('description').".";
	} else {
		$web_invoices_web_variables['description'] = "";
	}
}

function web_invoice_email_apply_variables($matches) {
	global $web_invoices_email_variables;

	if (isset($web_invoices_email_variables[$matches[2]])) {
		return $web_invoices_email_variables[$matches[2]];
	}
	return $matches[2];
}

function web_invoice_web_apply_variables($matches) {
	global $web_invoices_web_variables;

	if (isset($web_invoices_web_variables[$matches[2]])) {
		return $web_invoices_web_variables[$matches[2]];
	}
	return $matches[2];
}

function web_invoice_pdf_apply_variables($matches) {
	global $web_invoices_pdf_variables;

	if (isset($web_invoices_pdf_variables[$matches[2]])) {
		return $web_invoices_pdf_variables[$matches[2]];
	}
	return $matches[2];
}

function web_invoice_html_apply_variables($matches) {
	global $web_invoices_html_variables;

	if (isset($web_invoices_html_variables[$matches[2]])) {
		return $web_invoices_html_variables[$matches[2]];
	}
	return $matches[2];
}

function web_invoice_show_email($invoice_id) {
	apply_filters('web_invoice_email_variables', $invoice_id);

	return preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_invoice_content'));
}

function web_invoice_show_reminder_email($invoice_id) {

	apply_filters('web_invoice_email_variables', $invoice_id);

	return preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_reminder_content'));
}

function web_invoice_show_receipt_email($invoice_id) {

	apply_filters('web_invoice_email_variables', $invoice_id);

	return preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_receipt_content'));
}

function web_invoice_generate_pdf($invoice_id) {
	global $web_invoice;
	
	apply_filters('web_invoice_pdf_variables', $invoice_id);

	return preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_pdf_apply_variables', 
		stripslashes(get_option('web_invoice_pdf_content', "<html>
	<head>
		<title>Invoice</title>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
	</head>
	<body>
		<div id='invoice_page' class='clearfix'>
			<img style='float: right;' src='".$web_invoice->the_path."/images/web-invoice.png' style='width:101px; height: 128px;' />
			<h1>Invoice</h1>
			%content
		</div>
	</body>
</html>")));
}

function web_invoice_generate_html($invoice_id) {

	apply_filters('web_invoice_html_variables', $invoice_id);

	return preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_html_apply_variables', 
		stripslashes(get_option('web_invoice_html_content', '<div id="invoice_page" class="clearfix"><div class="noprint"><p>%print_message</p></div>%content</div>')));
}

function web_invoice_send_email_receipt($invoice_id) {
	global $wpdb;

	$invoice_info = new Web_Invoice_GetInfo($invoice_id);

	$message = web_invoice_show_receipt_email($invoice_id);

	$from = strip_tags(stripslashes(get_option("web_invoice_email_address")));
	$from_name = strip_tags(stripslashes(get_option("web_invoice_business_name")));
	$headers = "From: {$from_name} <{$from}>\r\n";
	if (get_option('web_invoice_cc_thank_you_email') == 'yes') {
		$headers .= "Bcc: {$from}\r\n";
	}

	$message = web_invoice_show_receipt_email($invoice_id);
	$subject = strip_tags(preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_receipt_subject')));

	if(wp_mail($invoice_info->recipient('email_address'), $subject, $message, $headers))
	{ web_invoice_update_log($invoice_id,'contact','Receipt eMailed'); }

	return $message;
}

function web_invoice_recurring($invoice_id) {
	global $wpdb;
	if(web_invoice_meta($invoice_id,'web_invoice_recurring_billing')) return true;
}

function web_invoice_recurring_started($invoice_id) {
	global $wpdb;
	if(web_invoice_meta($invoice_id,'subscription_id')) return true;
}

function web_invoice_paid_status($invoice_id) {
	//Merged with paid_status in class
	global $wpdb;
	$invoice_info = new Web_Invoice_GetInfo($invoice_id);
	if(!empty($invoice_id) && web_invoice_meta($invoice_id,'paid_status')) return web_invoice_meta($invoice_id,'paid_status');
	if ($invoice_info && $invoice_info->display('status')) return $invoice_info->display('status');
}

function web_invoice_paid_date($invoice_id) {
	// in invoice class
	global $wpdb;
	return $wpdb->get_var("SELECT time_stamp FROM  ".Web_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$invoice_id."' ORDER BY time_stamp DESC LIMIT 0, 1");

}


function web_invoice_build_invoice_link($invoice_id) {
	// in invoice class
	global $wpdb;

	$link_to_page = get_permalink(get_option('web_invoice_web_invoice_page'));

	$hashed_invoice_id = md5($invoice_id);
	if(get_option("permalink_structure")) { $link = $link_to_page . "?invoice_id=" .$hashed_invoice_id; }
	else { $link =  $link_to_page . "&invoice_id=" . $hashed_invoice_id; }

	return $link;
}

function web_invoice_build_invoice_link_paypal($invoice_id) {
	// in invoice class
	global $wpdb;

	$link_to_page = get_permalink(get_option('web_invoice_web_invoice_page'));

	if(get_option("permalink_structure")) {
		$link = $link_to_page . "?paypal_ipn=1";
	} else {
		$link =  $link_to_page . "&paypal_ipn=1";
	}

	return $link;
}


function web_invoice_draw_inputfield($name,$value,$special = '') {

	return "<input id='$name' class='$name'  name='$name' value='$value' $special />";
}

function web_invoice_draw_select($name,$values,$current_value = '', $id=null) {
	if ($id == null) {
		$id = $name;
	}
	$output = "<select id='$id' name='$name' class='$name'>";
	$output .= "<option></option>";
	foreach($values as $key => $value) {
		$output .=  "<option value='$key'";
		if($key == $current_value) $output .= " selected='selected'";
		$output .= ">$value</option>";
	}
	$output .= "</select>";

	return $output;
}

function web_invoice_format_phone($phone)
{
	$phone = preg_replace("/[^0-9]/", "", $phone);

	if(strlen($phone) == 7)
	return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
	elseif(strlen($phone) == 10)
	return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
	else
	return $phone;
}

function web_invoice_complete_removal()
{
	// Run regular deactivation, but also delete the main table - all invoice data is gone
	global $wpdb;

	$web_invoice = new Web_Invoice();
	$web_invoice->uninstall();

	$wpdb->query("DROP TABLE " . Web_Invoice::tablename('log') .";");
	$wpdb->query("DROP TABLE " . Web_Invoice::tablename('main') .";");
	$wpdb->query("DROP TABLE " . Web_Invoice::tablename('meta') .";");
	$wpdb->query("DROP TABLE " . Web_Invoice::tablename('payment') .";");
	$wpdb->query("DROP TABLE " . Web_Invoice::tablename('payment_meta') .";");

	delete_option('web_invoice_version');
	delete_option('web_invoice_payment_link');
	delete_option('web_invoice_payment_method');
	delete_option('web_invoice_protocol');
	delete_option('web_invoice_email_address');
	delete_option('web_invoice_business_name');
	delete_option('web_invoice_business_address');
	delete_option('web_invoice_business_phone');
	delete_option('web_invoice_business_tax_id');
	delete_option('web_invoice_default_currency_code');
	delete_option('web_invoice_web_invoice_page');
	delete_option('web_invoice_redirect_after_user_add');
	delete_option('web_invoice_tax_count');
	delete_option('web_invoice_tax_name');
	delete_option('web_invoice_self_generate_from_template');
	delete_option('web_invoice_partial_payments');
	delete_option('web_invoice_billing_meta');
	delete_option('web_invoice_show_billing_address');
	delete_option('web_invoice_show_quantities');
	delete_option('web_invoice_show_invoice_date');
	delete_option('web_invoice_use_css');
	delete_option('web_invoice_hide_page_title');
	delete_option('web_invoice_send_thank_you_email');
	delete_option('web_invoice_cc_thank_you_email');
	delete_option('web_invoice_reminder_message');

	//Gateway Settings
	delete_option('web_invoice_gateway_username');
	delete_option('web_invoice_gateway_tran_key');
	delete_option('web_invoice_gateway_delim_char');
	delete_option('web_invoice_gateway_encap_char');
	delete_option('web_invoice_gateway_merchant_email');
	delete_option('web_invoice_gateway_header_email_receipt');
	delete_option('web_invoice_gateway_url');
	delete_option('web_invoice_recurring_gateway_url');
	delete_option('web_invoice_gateway_MD5Hash');
	delete_option('web_invoice_gateway_test_mode');
	delete_option('web_invoice_gateway_delim_data');
	delete_option('web_invoice_gateway_relay_response');
	delete_option('web_invoice_gateway_email_customer');

	// PayPal
	delete_option('web_invoice_paypal_address');
	delete_option('web_invoice_paypal_only_button');
	delete_option('web_invoice_paypal_sandbox');
	
	// Payflow
	delete_option('web_invoice_payflow_login');
	delete_option('web_invoice_payflow_partner');
	delete_option('web_invoice_payflow_only_button');
	delete_option('web_invoice_payflow_shipping_details');
	delete_option('web_invoice_payflow_silent_post');
	
	// Payflow Pro
	delete_option('web_invoice_pfp_partner');
	delete_option('web_invoice_pfp_env');
	delete_option('web_invoice_pfp_authentication');
	delete_option('web_invoice_pfp_username');
	delete_option('web_invoice_pfp_password');
	delete_option('web_invoice_pfp_signature');
	delete_option('web_invoice_pfp_wpppe_vendor');
	delete_option('web_invoice_pfp_wpppe_username');
	delete_option('web_invoice_pfp_wpppe_password');
	delete_option('web_invoice_pfp_3rdparty_email');
	delete_option('web_invoice_pfp_shipping_details');
	
	// PayPal
	delete_option('web_invoice_other_details');

	// Moneybookers
	delete_option('web_invoice_moneybookers_address');
	delete_option('web_invoice_moneybookers_recurring_address');
	delete_option('web_invoice_moneybookers_merchant');
	delete_option('web_invoice_moneybookers_secret');
	delete_option('web_invoice_moneybookers_ip');

	// AlertPay
	delete_option('web_invoice_alertpay_address');
	delete_option('web_invoice_alertpay_merchant');
	delete_option('web_invoice_alertpay_secret');
	delete_option('web_invoice_alertpay_test_mode');
	delete_option('web_invoice_alertpay_ip');
	
	// 2CO
	delete_option('web_invoice_2co_sid');
	delete_option('web_invoice_2co_secret_word');
	delete_option('web_invoice_2co_demo_mode');
	
	// Google Checkout
	delete_option('web_invoice_google_checkout_env');
	delete_option('web_invoice_google_checkout_merchant_id');
	delete_option('web_invoice_google_checkout_level2');
	delete_option('web_invoice_google_checkout_merchant_key');
	delete_option('web_invoice_google_checkout_tax_state');
	
	// Sage Pay
	delete_option('web_invoice_sagepay_env');
	delete_option('web_invoice_sagepay_vendor_name');
	delete_option('web_invoice_sagepay_vendor_key');
	delete_option('web_invoice_sagepay_shipping_details');

	// Send invoice
	delete_option('web_invoice_email_send_invoice_subject');
	delete_option('web_invoice_email_send_invoice_content');

	// Send reminder
	delete_option('web_invoice_email_send_reminder_subject');
	delete_option('web_invoice_email_send_reminder_content');

	// Send receipt
	delete_option('web_invoice_email_send_receipt_subject');
	delete_option('web_invoice_email_send_receipt_content');
	
	// PDF
	delete_option('web_invoice_pdf_content');
	
	// HTML
	delete_option('web_invoice_html_content');

	return "All settings and databased removed.";
}

function get_web_invoice_user_id($invoice_id) {
	// in class
	global $wpdb;
	$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
	return $invoice_info->user_id;
}

function web_invoice_send_email($invoice_array, $reminder = false)
{
	global $wpdb;

	if(is_array($invoice_array))
	{
		$counter=0;
		foreach ($invoice_array as $invoice_id)
		{

			$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");

			$profileuser = get_user_to_edit($invoice_info->user_id);

			if ($reminder) {
				$message = strip_tags(web_invoice_show_reminder_email($invoice_id));
				$subject = strip_tags(preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_reminder_subject')));
			} else {
				$message = strip_tags(web_invoice_show_email($invoice_id));
				$subject = strip_tags(preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_invoice_subject')));
			}

			$from = strip_tags(stripslashes(get_option("web_invoice_email_address")));
			$from_name = strip_tags(stripslashes(get_option("web_invoice_business_name")));
			$headers = "From: {$from_name} <{$from}>";

			$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
			
			$attachments = array(web_invoice_pdf_file($invoice_id));

			if(wp_mail($profileuser->user_email, $subject, $message, $headers, $attachments))
			{
				$counter++; // Success in sending quantified.
				unlink($attachments[0]);
				web_invoice_update_log($invoice_id,'contact','Invoice eMailed'); //make sent entry
				web_invoice_update_invoice_meta($invoice_id, "sent_date", date("Y-m-d", time()));
			}
		}
		return "Successfully sent $counter Web Invoices(s).";
	}
	else
	{
		$invoice_id = $invoice_array;
		$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_array."'");

		$profileuser = get_userdata($invoice_info->user_id);

		if ($reminder) {
			$message = strip_tags(web_invoice_show_reminder_email($invoice_id));
			$subject = strip_tags(preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_reminder_subject')));
		} else {
			$message = strip_tags(web_invoice_show_email($invoice_id));
			$subject = strip_tags(preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', get_option('web_invoice_email_send_invoice_subject')));
		}

		$from = strip_tags(stripslashes(get_option("web_invoice_email_address")));
		$from_name = strip_tags(stripslashes(get_option("web_invoice_business_name")));
		$headers = "From: {$from_name} <{$from}>";

		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		$attachments = array(web_invoice_pdf_file($invoice_id));
		
		if(wp_mail($profileuser->user_email, $subject, $message, $headers, $attachments))
		{
			unlink($attachments[0]);
			web_invoice_update_invoice_meta($invoice_id, "sent_date", date("Y-m-d", time()));
			web_invoice_update_log($invoice_id,'contact','Invoice eMailed'); return "Web invoice sent successfully."; 
		} else { 
			return "There was a problem sending the invoice.";
		}
	}
}

function web_invoice_pdf_get($invoice_id) {
	global $web_invoice;
	
	$content = preg_replace(array('/  /', '/\n\n/i', '/&euro;/i'), array(" ", "\n", "&#0128;"), web_invoice_generate_pdf($invoice_id));
	ob_start();
	
	ob_clean();
	
	require_once "lib/dompdf_config.inc.php";
	spl_autoload_register('DOMPDF_autoload');
	
	$url_parts = parse_url($web_invoice->the_path);
	
	$dompdf = new DOMPDF();
	$dompdf->load_html($content);
	$dompdf->set_paper("a4", "portrait");
	$dompdf->render();
	ob_clean();
	
	return $dompdf;
}

function web_invoice_pdf_file($invoice_id) {
	$dompdf = web_invoice_pdf_get($invoice_id);
	
	$tmpfname = tempnam(sys_get_temp_dir(), "web_invoice_").".pdf";

	$handle = file_put_contents($tmpfname, $dompdf->output());
	
	return $tmpfname;
}

function web_invoice_array_stripslashes($slash_array = array())
{
	if($slash_array)
	{
		foreach($slash_array as $key=>$value)
		{
			if(is_array($value))
			{
				$slash_array[$key] = web_invoice_array_stripslashes($value);
			}
			else
			{
				$slash_array[$key] = stripslashes($value);
			}
		}
	}
	return($slash_array);
}

function web_invoice_profile_update() {
	global $wpdb;
	$user_id =  $_REQUEST['user_id'];

	if(isset($_POST['company_name'])) update_usermeta($user_id, 'company_name', $_POST['company_name']);
	if(isset($_POST['tax_id'])) update_usermeta($user_id, 'tax_id', $_POST['tax_id']);
	if(isset($_POST['streetaddress'])) update_usermeta($user_id, 'streetaddress', $_POST['streetaddress']);
	if(isset($_POST['zip']))  update_usermeta($user_id, 'zip', $_POST['zip']);
	if(isset($_POST['state'])) update_usermeta($user_id, 'state', $_POST['state']);
	if(isset($_POST['city'])) update_usermeta($user_id, 'city', $_POST['city']);
	if(isset($_POST['phonenumber'])) update_usermeta($user_id, 'phonenumber', $_POST['phonenumber']);
	if(isset($_POST['country'])) update_usermeta($user_id, 'country', $_POST['country']);
	
	if(isset($_POST['shipto_company_name'])) update_usermeta($user_id, 'shipto_company_name', $_POST['shipto_company_name']);
	if(isset($_POST['shipto_streetaddress'])) update_usermeta($user_id, 'shipto_streetaddress', $_POST['shipto_streetaddress']);
	if(isset($_POST['shipto_zip']))  update_usermeta($user_id, 'shipto_zip', $_POST['shipto_zip']);
	if(isset($_POST['shipto_state'])) update_usermeta($user_id, 'shipto_state', $_POST['shipto_state']);
	if(isset($_POST['shipto_city'])) update_usermeta($user_id, 'shipto_city', $_POST['shipto_city']);
	if(isset($_POST['shipto_phonenumber'])) update_usermeta($user_id, 'shipto_phonenumber', $_POST['shipto_phonenumber']);
	if(isset($_POST['shipto_country'])) update_usermeta($user_id, 'shipto_country', $_POST['shipto_country']);
}

class web_invoice_Date
{

	static function convert($string, $from_mask, $to_mask='', $return_unix=false)
	{
		// define the valid values that we will use to check
		// value => length
		$all = array(
			's' => 'ss',
			'i' => 'ii',
			'H' => 'HH',
			'y' => 'yy',
			'Y' => 'YYYY',
			'm' => 'mm',
			'd' => 'dd'
			);

		// this will give us a mask with full length fields
		$from_mask = str_replace(array_keys($all), $all, $from_mask);

		$vals = array();
		foreach($all as $type => $chars)
		{
			// get the position of the current character
			if(($pos = strpos($from_mask, $chars)) === false)
				continue;

			// find the value in the original string
			$val = substr($string, $pos, strlen($chars));

			// store it for later processing
			$vals[$type] = $val;
		}
		
		$hours = 0;
		$minutes = 0;
		$seconds = 0;
		$month = 0;
		$day = 0;
		$year = 0;

		foreach($vals as $type => $val)
		{
			switch($type)
			{
				case 's' :
					$seconds = $val;
					break;
				case 'i' :
					$minutes = $val;
					break;
				case 'H':
					$hours = $val;
					break;
				case 'y':
					$year = '20'.$val; // Year 3k bug right here
					break;
				case 'Y':
					$year = $val;
					break;
				case 'm':
					$month = $val;
					break;
				case 'd':
					$day = $val;
					break;
			}
		}

		$unix_time = mktime(
		(int)$hours, (int)$minutes, (int)$seconds,
		(int)$month, (int)$day, (int)$year);

		if($return_unix)
			return $unix_time;

		return date($to_mask, $unix_time);
	}
}


function web_invoice_fix_billing_meta_array($arr){
	$narr = array();
	$counter = 1;
	while(list($key, $val) = each($arr)){
		if (is_array($val)){
			$val = array_remove_empty($val);
			if (count($val)!=0){
				$narr[$counter] = $val;$counter++;
			}
		}
		else {
			if (trim($val) != ""){
				$narr[$counter] = $val;$counter++;
			}
		}

	}
	unset($arr);
	return $narr;
}

function web_invoice_printYearDropdown($sel='', $pfp = false)
{
	$localDate=getdate();
	$minYear = $localDate["year"];
	$maxYear = $minYear + 15;

	$output =  "<option value=''>--</option>";
	for($i=$minYear; $i<$maxYear; $i++) {
		if ($pfp) {
			$output .= "<option value='". substr($i, 0, 4) ."'".($sel==(substr($i, 0, 4))?' selected':'').
			">". $i ."</option>";
		} else {
			$output .= "<option value='". substr($i, 2, 2) ."'".($sel==(substr($i, 2, 2))?' selected':'').
		">". $i ."</option>";
		}
	}
	return($output);
}

function web_invoice_printMonthDropdown($sel='')
{
	$output =  "<option value=''>--</option>";
	$output .=  "<option " . ($sel==1?' selected':'') . " value='01'>01 - Jan</option>";
	$output .=  "<option " . ($sel==2?' selected':'') . "  value='02'>02 - Feb</option>";
	$output .=  "<option " . ($sel==3?' selected':'') . "  value='03'>03 - Mar</option>";
	$output .=  "<option " . ($sel==4?' selected':'') . "  value='04'>04 - Apr</option>";
	$output .=  "<option " . ($sel==5?' selected':'') . "  value='05'>05 - May</option>";
	$output .=  "<option " . ($sel==6?' selected':'') . "  value='06'>06 - Jun</option>";
	$output .=  "<option " . ($sel==7?' selected':'') . "  value='07'>07 - Jul</option>";
	$output .=  "<option " . ($sel==8?' selected':'') . "  value='08'>08 - Aug</option>";
	$output .=  "<option " . ($sel==9?' selected':'') . "  value='09'>09 - Sep</option>";
	$output .=  "<option " . ($sel==10?' selected':'') . "  value='10'>10 - Oct</option>";
	$output .=  "<option " . ($sel==11?' selected':'') . "  value='11'>11 - Nov</option>";
	$output .=  "<option " . ($sel==12?' selected':'') . "  value='12'>12 - Doc</option>";

	return($output);
}



function web_invoice_state_array($sel='')
{
	$StateProvinceTwoToFull = array(
   'AL' => 'Alabama',
   'AK' => 'Alaska',
   'AS' => 'American Samoa',
   'AZ' => 'Arizona',
   'AR' => 'Arkansas',
   'CA' => 'California',
   'CO' => 'Colorado',
   'CT' => 'Connecticut',
   'DE' => 'Delaware',
   'DC' => 'District of Columbia',
   'FM' => 'Federated States of Micronesia',
   'FL' => 'Florida',
   'GA' => 'Georgia',
   'GU' => 'Guam',
   'HI' => 'Hawaii',
   'ID' => 'Idaho',
   'IL' => 'Illinois',
   'IN' => 'Indiana',
   'IA' => 'Iowa',
   'KS' => 'Kansas',
   'KY' => 'Kentucky',
   'LA' => 'Louisiana',
   'ME' => 'Maine',
   'MH' => 'Marshall Islands',
   'MD' => 'Maryland',
   'MA' => 'Massachusetts',
   'MI' => 'Michigan',
   'MN' => 'Minnesota',
   'MS' => 'Mississippi',
   'MO' => 'Missouri',
   'MT' => 'Montana',
   'NE' => 'Nebraska',
   'NV' => 'Nevada',
   'NH' => 'New Hampshire',
   'NJ' => 'New Jersey',
   'NM' => 'New Mexico',
   'NY' => 'New York',
   'NC' => 'North Carolina',
   'ND' => 'North Dakota',
   'MP' => 'Northern Mariana Islands',
   'OH' => 'Ohio',
   'OK' => 'Oklahoma',
   'OR' => 'Oregon',
   'PW' => 'Palau',
   'PA' => 'Pennsylvania',
   'PR' => 'Puerto Rico',
   'RI' => 'Rhode Island',
   'SC' => 'South Carolina',
   'SD' => 'South Dakota',
   'TN' => 'Tennessee',
   'TX' => 'Texas',
   'UT' => 'Utah',
   'VT' => 'Vermont',
   'VI' => 'Virgin Islands',
   'VA' => 'Virginia',
   'WA' => 'Washington',
   'WV' => 'West Virginia',
   'WI' => 'Wisconsin',
   'WY' => 'Wyoming',
   'AB' => 'Alberta',
   'BC' => 'British Columbia',
   'MB' => 'Manitoba',
   'NB' => 'New Brunswick',
   'NF' => 'Newfoundland',
   'NW' => 'Northwest Territory',
   'NS' => 'Nova Scotia',
   'ON' => 'Ontario',
   'PE' => 'Prince Edward Island',
   'QU' => 'Quebec',
   'SK' => 'Saskatchewan',
   'YT' => 'Yukon Territory',
	);

	return($StateProvinceTwoToFull);
}

function web_invoice_country_array() {
	return array("US"=> "United States","AL"=> "Albania","DZ"=> "Algeria","AD"=> "Andorra","AO"=> "Angola","AI"=> "Anguilla","AG"=> "Antigua and Barbuda","AR"=> "Argentina","AM"=> "Armenia","AW"=> "Aruba","AU"=> "Australia","AT"=> "Austria","AZ"=> "Azerbaijan Republic","BS"=> "Bahamas","BH"=> "Bahrain","BB"=> "Barbados","BE"=> "Belgium","BZ"=> "Belize","BJ"=> "Benin","BM"=> "Bermuda","BT"=> "Bhutan","BO"=> "Bolivia","BA"=> "Bosnia and Herzegovina","BW"=> "Botswana","BR"=> "Brazil","VG"=> "British Virgin Islands","BN"=> "Brunei","BG"=> "Bulgaria","BF"=> "Burkina Faso","BI"=> "Burundi","KH"=> "Cambodia","CA"=> "Canada","CV"=> "Cape Verde","KY"=> "Cayman Islands","TD"=> "Chad","CL"=> "Chile","C2"=> "China","CO"=> "Colombia","KM"=> "Comoros","CK"=> "Cook Islands","CR"=> "Costa Rica","HR"=> "Croatia","CY"=> "Cyprus","CZ"=> "Czech Republic","CD"=> "Democratic Republic of the Congo","DK"=> "Denmark","DJ"=> "Djibouti","DM"=> "Dominica","DO"=> "Dominican Republic","EC"=> "Ecuador","SV"=> "El Salvador","ER"=> "Eritrea","EE"=> "Estonia","ET"=> "Ethiopia","FK"=> "Falkland Islands","FO"=> "Faroe Islands","FM"=> "Federated States of Micronesia","FJ"=> "Fiji","FI"=> "Finland","FR"=> "France","GF"=> "French Guiana","PF"=> "French Polynesia","GA"=> "Gabon Republic","GM"=> "Gambia","DE"=> "Germany","GI"=> "Gibraltar","GR"=> "Greece","GL"=> "Greenland","GD"=> "Grenada","GP"=> "Guadeloupe","GT"=> "Guatemala","GN"=> "Guinea","GW"=> "Guinea Bissau","GY"=> "Guyana","HN"=> "Honduras","HK"=> "Hong Kong","HU"=> "Hungary","IS"=> "Iceland","IN"=> "India","ID"=> "Indonesia","IE"=> "Ireland","IL"=> "Israel","IT"=> "Italy","JM"=> "Jamaica","JP"=> "Japan","JO"=> "Jordan","KZ"=> "Kazakhstan","KE"=> "Kenya","KI"=> "Kiribati","KW"=> "Kuwait","KG"=> "Kyrgyzstan","LA"=> "Laos","LV"=> "Latvia","LS"=> "Lesotho","LI"=> "Liechtenstein","LT"=> "Lithuania","LU"=> "Luxembourg","MG"=> "Madagascar","MW"=> "Malawi","MY"=> "Malaysia","MV"=> "Maldives","ML"=> "Mali","MT"=> "Malta","MH"=> "Marshall Islands","MQ"=> "Martinique","MR"=> "Mauritania","MU"=> "Mauritius","YT"=> "Mayotte","MX"=> "Mexico","MN"=> "Mongolia","MS"=> "Montserrat","MA"=> "Morocco","MZ"=> "Mozambique","NA"=> "Namibia","NR"=> "Nauru","NP"=> "Nepal","NL"=> "Netherlands","AN"=> "Netherlands Antilles","NC"=> "New Caledonia","NZ"=> "New Zealand","NI"=> "Nicaragua","NE"=> "Niger","NU"=> "Niue","NF"=> "Norfolk Island","NO"=> "Norway","OM"=> "Oman","PW"=> "Palau","PA"=> "Panama","PG"=> "Papua New Guinea","PE"=> "Peru","PH"=> "Philippines","PN"=> "Pitcairn Islands","PL"=> "Poland","PT"=> "Portugal","QA"=> "Qatar","CG"=> "Republic of the Congo","RE"=> "Reunion","RO"=> "Romania","RU"=> "Russia","RW"=> "Rwanda","VC"=> "Saint Vincent and the Grenadines","WS"=> "Samoa","SM"=> "San Marino","ST"=> "São Tomé and Príncipe","SA"=> "Saudi Arabia","SN"=> "Senegal","SC"=> "Seychelles","SL"=> "Sierra Leone","SG"=> "Singapore","SK"=> "Slovakia","SI"=> "Slovenia","SB"=> "Solomon Islands","SO"=> "Somalia","ZA"=> "South Africa","KR"=> "South Korea","ES"=> "Spain","LK"=> "Sri Lanka","SH"=> "St. Helena","KN"=> "St. Kitts and Nevis","LC"=> "St. Lucia","PM"=> "St. Pierre and Miquelon","SR"=> "Suriname","SJ"=> "Svalbard and Jan Mayen Islands","SZ"=> "Swaziland","SE"=> "Sweden","CH"=> "Switzerland","TW"=> "Taiwan","TJ"=> "Tajikistan","TZ"=> "Tanzania","TH"=> "Thailand","TG"=> "Togo","TO"=> "Tonga","TT"=> "Trinidad and Tobago","TN"=> "Tunisia","TR"=> "Turkey","TM"=> "Turkmenistan","TC"=> "Turks and Caicos Islands","TV"=> "Tuvalu","UG"=> "Uganda","UA"=> "Ukraine","AE"=> "United Arab Emirates","GB"=> "United Kingdom","UY"=> "Uruguay","VU"=> "Vanuatu","VA"=> "Vatican City State","VE"=> "Venezuela","VN"=> "Vietnam","WF"=> "Wallis and Futuna Islands","YE"=> "Yemen","ZM"=> "Zambia");
}

function web_invoice_country3_array() {
	return array("AFG" => "AF","ALA" => "AX","ALB" => "AL","DZA" => "DZ","ASM" => "AS","AND" => "AD","AGO" => "AO","AIA" => "AI","ATA" => "AQ","ATG" => "AG","ARG" => "AR","ARM" => "AM","ABW" => "AW","AUS" => "AU","AUT" => "AT","AZE" => "AZ","BHS" => "BS","BHR" => "BH","BGD" => "BD","BRB" => "BB","BLR" => "BY","BEL" => "BE","BLZ" => "BZ","BEN" => "BJ","BMU" => "BM","BTN" => "BT","BOL" => "BO","BIH" => "BA","BWA" => "BW","BVT" => "BV","BRA" => "BR","IOT" => "IO","BRN" => "BN","BGR" => "BG","BFA" => "BF","BDI" => "BI","KHM" => "KH","CMR" => "CM","CAN" => "CA","CPV" => "CV","CYM" => "KY","CAF" => "CF","TCD" => "TD","CHL" => "CL","CHN" => "CN","CXR" => "CX","CCK" => "CC","COL" => "CO","COM" => "KM","COG" => "CG","COD" => "CD","COK" => "CK","CRI" => "CR","CIV" => "CI","HRV" => "HR","CUB" => "CU","CYP" => "CY","CZE" => "CZ","DNK" => "DK","DJI" => "DJ","DMA" => "DM","DOM" => "DO","ECU" => "EC","EGY" => "EG","SLV" => "SV","GNQ" => "GQ","ERI" => "ER","EST" => "EE","ETH" => "ET","FLK" => "FK","FRO" => "FO","FJI" => "FJ","FIN" => "FI","FRA" => "FR","GUF" => "GF","PYF" => "PF","ATF" => "TF","GAB" => "GA","GMB" => "GM","GEO" => "GE","DEU" => "DE","GHA" => "GH","GIB" => "GI","GRC" => "GR","GRL" => "GL","GRD" => "GD","GLP" => "GP","GUM" => "GU","GTM" => "GT","GGY" => "GG","GIN" => "GN","GNB" => "GW","GUY" => "GY","HTI" => "HT","HMD" => "HM","VAT" => "VA","HND" => "HN","HKG" => "HK","HUN" => "HU","ISL" => "IS","IND" => "IN","IDN" => "ID","IRN" => "IR","IRQ" => "IQ","IRL" => "IE","IMN" => "IM","ISR" => "IL","ITA" => "IT","JAM" => "JM","JPN" => "JP","JEY" => "JE","JOR" => "JO","KAZ" => "KZ","KEN" => "KE","KIR" => "KI","PRK" => "KP","KOR" => "KR","KWT" => "KW","KGZ" => "KG","LAO" => "LA","LVA" => "LV","LBN" => "LB","LSO" => "LS","LBR" => "LR","LBY" => "LY","LIE" => "LI","LTU" => "LT","LUX" => "LU","MAC" => "MO","MKD" => "MK","MDG" => "MG","MWI" => "MW","MYS" => "MY","MDV" => "MV","MLI" => "ML","MLT" => "MT","MHL" => "MH","MTQ" => "MQ","MRT" => "MR","MUS" => "MU","MYT" => "YT","MEX" => "MX","FSM" => "FM","MDA" => "MD","MCO" => "MC","MNG" => "MN","MNE" => "ME","MSR" => "MS","MAR" => "MA","MOZ" => "MZ","MMR" => "MM","NAM" => "NA","NRU" => "NR","NPL" => "NP","NLD" => "NL","ANT" => "AN","NCL" => "NC","NZL" => "NZ","NIC" => "NI","NER" => "NE","NGA" => "NG","NIU" => "NU","NFK" => "NF","MNP" => "MP","NOR" => "NO","OMN" => "OM","PAK" => "PK","PLW" => "PW","PSE" => "PS","PAN" => "PA","PNG" => "PG","PRY" => "PY","PER" => "PE","PHL" => "PH","PCN" => "PN","POL" => "PL","PRT" => "PT","PRI" => "PR","QAT" => "QA","REU" => "RE","ROU" => "RO","RUS" => "RU","RWA" => "RW","BLM" => "BL","SHN" => "SH","KNA" => "KN","LCA" => "LC","MAF" => "MF","SPM" => "PM","VCT" => "VC","WSM" => "WS","SMR" => "SM","STP" => "ST","SAU" => "SA","SEN" => "SN","SRB" => "RS","SYC" => "SC","SLE" => "SL","SGP" => "SG","SVK" => "SK","SVN" => "SI","SLB" => "SB","SOM" => "SO","ZAF" => "ZA","SGS" => "GS","ESP" => "ES","LKA" => "LK","SDN" => "SD","SUR" => "SR","SJM" => "SJ","SWZ" => "SZ","SWE" => "SE","CHE" => "CH","SYR" => "SY","TWN" => "TW","TJK" => "TJ","TZA" => "TZ","THA" => "TH","TLS" => "TL","TGO" => "TG","TKL" => "TK","TON" => "TO","TTO" => "TT","TUN" => "TN","TUR" => "TR","TKM" => "TM","TCA" => "TC","TUV" => "TV","UGA" => "UG","UKR" => "UA","ARE" => "AE","GBR" => "GB","USA" => "US","UMI" => "UM","URY" => "UY","UZB" => "UZ","VUT" => "VU","VEN" => "VE","VNM" => "VN","VGB" => "VG","VIR" => "VI","WLF" => "WF","ESH" => "EH","YEM" => "YE","ZMB" => "ZM","ZWE" => "ZW");
}

function web_invoice_countrynum_array() {
	return array(
		"TD" => "148",
		"CL" => "152",
		"CN" => "156",
		"CX" => "162",
		"CC" => "166",
		"CO" => "170",
		"KM" => "174",
		"CD" => "180",
		"CG" => "178",
		"CK" => "184",
		"CR" => "188",
		"CI" => "384",
		"HR" => "191",
		"CU" => "192",
		"CY" => "196",
		"CZ" => "203",
		"DK" => "208",
		"DJ" => "262",
		"DM" => "212",
		"DO" => "214",
		"EC" => "218",
		"EG" => "818",
		"SV" => "222",
		"GQ" => "226",
		"ER" => "232",
		"EE" => "233",
		"ET" => "231",
		"FK" => "238",
		"FO" => "234",
		"FJ" => "242",
		"FI" => "246",
		"FR" => "250",
		"GF" => "254",
		"PF" => "258",
		"TF" => "260",
		"GA" => "266",
		"GM" => "270",
		"GE" => "268",
		"DE" => "276",
		"GH" => "288",
		"GI" => "292",
		"GR" => "300",
		"GL" => "304",
		"GD" => "308",
		"GP" => "312",
		"GU" => "316",
		"GT" => "320",
		"GN" => "324",
		"GW" => "624",
		"GY" => "328",
		"HT" => "332",
		"HM" => "334",
		"HN" => "340",
		"HK" => "344",
		"HU" => "348",
		"IS" => "352",
		"IN" => "356",
		"ID" => "360",
		"IR" => "364",
		"IQ" => "368",
		"IE" => "372",
		"IL" => "376",
		"IT" => "380",
		"JM" => "388",
		"JP" => "392",
		"JO" => "400",
		"KZ" => "398",
		"KE" => "404",
		"KI" => "296",
		"KP" => "408",
		"KR" => "410",
		"KW" => "414",
		"KG" => "417",
		"LA" => "418",
		"LV" => "428",
		"LB" => "422",
		"LS" => "426",
		"LR" => "430",
		"LY" => "434",
		"LI" => "438",
		"LT" => "440",
		"LU" => "442",
		"MO" => "446",
		"MK" => "807",
		"MG" => "450",
		"MW" => "454",
		"MY" => "458",
		"MV" => "462",
		"ML" => "466",
		"MT" => "470",
		"MH" => "584",
		"MQ" => "474",
		"MR" => "478",
		"MU" => "480",
		"YT" => "175",
		"MX" => "484",
		"FM" => "583",
		"MD" => "498",
		"MC" => "492",
		"MN" => "496",
		"MS" => "500",
		"MA" => "504",
		"MZ" => "508",
		"MM" => "104",
		"NA" => "516",
		"NR" => "520",
		"NP" => "524",
		"NL" => "528",
		"AN" => "530",
		"NC" => "540",
		"NZ" => "554",
		"NI" => "558",
		"NE" => "562",
		"NG" => "566",
		"NU" => "570",
		"NF" => "574",
		"MP" => "580",
		"NO" => "578",
		"OM" => "512",
		"PK" => "586",
		"PW" => "585",
		"PS" => "275",
		"PA" => "591",
		"PG" => "598",
		"PY" => "600",
		"PE" => "604",
		"PH" => "608",
		"PN" => "612",
		"PL" => "616",
		"PT" => "620",
		"PR" => "630",
		"QA" => "634",
		"RE" => "638",
		"RO" => "642",
		"RU" => "643",
		"RW" => "646",
		"SH" => "654",
		"KN" => "659",
		"LC" => "662",
		"PM" => "666",
		"VC" => "670",
		"WS" => "882",
		"SM" => "674",
		"ST" => "678",
		"SA" => "682",
		"SN" => "686",
		"CS" => "891",
		"SC" => "690",
		"SL" => "694",
		"SG" => "702",
		"SK" => "703",
		"SI" => "705",
		"SB" => "090",
		"SO" => "706",
		"ZA" => "710",
		"GS" => "239",
		"ES" => "724",
		"LK" => "144",
		"SD" => "736",
		"SR" => "740",
		"SJ" => "744",
		"SZ" => "748",
		"SE" => "752",
		"CH" => "756",
		"SY" => "760",
		"TW" => "158",
		"TJ" => "762",
		"TZ" => "834",
		"TH" => "764",
		"TL" => "626",
		"TG" => "768",
		"TK" => "772",
		"TO" => "776",
		"TT" => "780",
		"TN" => "788",
		"TR" => "792",
		"TM" => "795",
		"TC" => "796",
		"TV" => "798",
		"UG" => "800",
		"UA" => "804",
		"AE" => "784",
		"GB" => "826",
		"US" => "840",
		"UM" => "581",
		"UY" => "858",
		"UZ" => "860",
		"VU" => "548",
		"VA" => "336",
		"VE" => "862",
		"VN" => "704",
		"VG" => "092",
		"VI" => "850",
		"WF" => "876",
		"EH" => "732",
		"YE" => "887",
		"ZM" => "894",
		"ZW" => "716");
}

function web_invoice_map_country3_to_country($country3) {
	$country_map = web_invoice_country3_array();
	if (isset($country_map[$country3])) {
		$country2 = $country_map[$country3];
	} else {
		$country2 = $country3; // Cheating ;)
	}
	unset($country_map);
	return $country2;
}

function web_invoice_month_array() {
	return array(
		"01" => __("Jan", WEB_INVOICE_TRANS_DOMAIN),
		"02" => __("Feb", WEB_INVOICE_TRANS_DOMAIN),
		"03" => __("Mar", WEB_INVOICE_TRANS_DOMAIN),
		"04" => __("Apr", WEB_INVOICE_TRANS_DOMAIN),
		"05" => __("May", WEB_INVOICE_TRANS_DOMAIN),
		"06" => __("Jun", WEB_INVOICE_TRANS_DOMAIN),
		"07" => __("Jul", WEB_INVOICE_TRANS_DOMAIN),
		"08" => __("Aug", WEB_INVOICE_TRANS_DOMAIN),
		"09" => __("Sep", WEB_INVOICE_TRANS_DOMAIN),
		"10" => __("Oct", WEB_INVOICE_TRANS_DOMAIN),
		"11" => __("Nov", WEB_INVOICE_TRANS_DOMAIN),
		"12" => __("Dec", WEB_INVOICE_TRANS_DOMAIN));
}

function web_invoice_go_secure($destination) {
	$reload = 'Location: ' . $destination;
	header($reload);
}

function web_invoice_process_cc_transaction($cc_data) {
	$errors = array ();
	$errors_msg = null;
	$_POST['processing_problem'] = '';
	$stop_transaction = false;
	$invoice_id = preg_replace("/[^0-9]/","", $_POST['invoice_num']); /* this is the real invoice id */

	if(web_invoice_recurring($invoice_id)) $recurring = true;

	$invoice = new Web_Invoice_GetInfo($invoice_id);

	// Accomodate Custom Invoice IDs by changing the post value, this is passed to Authorize.net account
	$web_invoice_custom_invoice_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id');
	// If there is a custom invoice id, we're setting the $_POST['invoice_num'] to the custom id, because that is what's getting passed to authorize.net
	if($web_invoice_custom_invoice_id) { $_POST['invoice_num'] = $web_invoice_custom_invoice_id; }

	$wp_users_id = get_web_invoice_user_id($invoice_id);

	if(empty($_POST['first_name'])){$errors [ 'first_name' ] [] = "Please enter your first name under billing details.";$stop_transaction = true;}
	if(empty($_POST['last_name'])){$errors [ 'last_name' ] [] = "Please enter your last name under billing details.";$stop_transaction = true;}
	if(empty($_POST['email_address'])){$errors [ 'email_address' ] [] = "Please provide an email address under billing details.";$stop_transaction = true;}
	if(empty($_POST['phonenumber'])){$errors [ 'phonenumber' ] [] = "Please enter your phone number under billing details.";$stop_transaction = true;}
	if(empty($_POST['address'])){$errors [ 'address' ] [] = "Please enter your address under billing details.";$stop_transaction = true;}
	if(empty($_POST['city'])){$errors [ 'city' ] [] = "Please enter your city under billing details.";$stop_transaction = true;}
	if(empty($_POST['zip'])){$errors [ 'zip' ] [] = "Please enter your ZIP code under billing details.";$stop_transaction = true;}
	if(empty($_POST['country'])){$errors [ 'country' ] [] = "Please enter your country under billing details.";$stop_transaction = true;}
	if(empty($_POST['state']) && $_POST['country'] == 'US'){$errors [ 'state' ] [] = "Please select your state under billing details.";$stop_transaction = true;}
	
	
	if (!isset($_POST['processor']) || $_POST['processor'] != 'sagepay') {
		if(empty($_POST['card_num'])) {	$errors [ 'card_num' ] []  = "Please enter your credit card number under billing details.";	$stop_transaction = true;} else { if (!web_invoice_validate_cc_number($_POST['card_num'])){$errors [ 'card_num' ] [] = "Please enter a valid credit card number."; $stop_transaction = true; } }
		if(empty($_POST['exp_month'])){$errors [ 'exp_month' ] [] = "Please enter your credit card's expiration month under billing details.";$stop_transaction = true;}
		if(empty($_POST['exp_year'])){$errors [ 'exp_year' ] [] = "Please enter your credit card's expiration year under billing details.";$stop_transaction = true;}
		if(empty($_POST['card_code'])){$errors [ 'card_code' ] [] = "The <b>Security Code</b> is the code on the back of your card under billing details.";$stop_transaction = true;}
	}
	
	if ($_POST['processor'] == 'pfp' && get_option('web_invoice_pfp_shipping_details') == 'True' || $_POST['processor'] == 'sagepay' && get_option('web_invoice_sagepay_shipping_details') == 'True') {
		if(empty($_POST['shipto_first_name'])){$errors [ 'shipto_first_name' ] [] = "Please enter your first name under shipping details.";$stop_transaction = true;}
		if(empty($_POST['shipto_last_name'])){$errors [ 'shipto_last_name' ] [] = "Please enter your last name under shipping details. ";$stop_transaction = true;}
		if(empty($_POST['shipto_email_address'])){$errors [ 'shipto_email_address' ] [] = "Please provide an email address under shipping details.";$stop_transaction = true;}
		if(empty($_POST['shipto_phonenumber'])){$errors [ 'shipto_phonenumber' ] [] = "Please enter your phone number under shipping details.";$stop_transaction = true;}
		if(empty($_POST['shipto_address'])){$errors [ 'shipto_address' ] [] = "Please enter your address under shipping details.";$stop_transaction = true;}
		if(empty($_POST['shipto_city'])){$errors [ 'shipto_city' ] [] = "Please enter your city under shipping details.";$stop_transaction = true;}
		if(empty($_POST['shipto_zip'])){$errors [ 'shipto_zip' ] [] = "Please enter your ZIP code under shipping details.";$stop_transaction = true;}
		if(empty($_POST['shipto_country'])){$errors [ 'shipto_country' ] [] = "Please enter your country under shipping details.";$stop_transaction = true;}
		if(empty($_POST['shipto_state']) && $_POST['shipto_country'] == 'US'){$errors [ 'shipto_state' ] [] = "Please select your state under shipping details.";$stop_transaction = true;}
	}
	
	// Charge Card
	if(!$stop_transaction) {

		if (isset($_POST['processor']) && $_POST['processor'] == 'sagepay') {
			$data_arr = array();
			$data_arr['VendorTxCode'] = $invoice->display('trx_id');
			$data_arr['VendorEMail'] = get_option("web_invoice_email_address");
			$data_arr['Amount'] = $invoice->display('amount');
			$data_arr['Currency'] = $invoice->display('currency');
			$data_arr['Description'] = $invoice->display('subject');
			$data_arr['SuccessURL'] = web_invoice_build_invoice_link($invoice_id);
			$data_arr['FailureURL'] = web_invoice_build_invoice_link($invoice_id);
			$data_arr['CustomerName'] = "{$_POST['first_name']} {$_POST['last_name']}";
			$data_arr['CustomerEMail'] = $_POST['email_address'];
		
			$data_arr['BillingFirstnames'] = $_POST['first_name'];
			$data_arr['BillingSurname'] = $_POST['last_name'];
			$data_arr['BillingAddress1'] = $_POST['address'];
			$data_arr['BillingCity'] = $_POST['city'];
			$data_arr['BillingPostCode'] = $_POST['zip'];
			$data_arr['BillingCountry'] = $_POST['country'];
			if (!empty($_POST['state'])) {
				$data_arr['BillingState'] = substr($_POST['state'], 0, 2);
			}
			$data_arr['BillingPhone'] = $_POST['phonenumber'];
			 
			if (get_option('web_invoice_sagepay_shipping_details') == 'True') {
				$data_arr['DeliveryFirstnames'] = $_POST['shipto_first_name'];
				$data_arr['DeliverySurname'] = $_POST['shipto_last_name'];
				$data_arr['DeliveryAddress1'] = $_POST['shipto_address'];
				$data_arr['DeliveryCity'] = $_POST['shipto_city'];
				$data_arr['DeliveryPostCode'] = $_POST['shipto_zip'];
				$data_arr['DeliveryCountry'] = $_POST['shipto_country'];
				if (!empty($_POST['shipto_state'])) {
					$data_arr['DeliveryState'] = substr($_POST['shipto_state'], 0, 2);
				}
				$data_arr['DeliveryPhone'] = $_POST['shipto_phonenumber'];
			} else {
				$data_arr['DeliveryFirstnames'] = $_POST['first_name'];
				$data_arr['DeliverySurname'] = $_POST['last_name'];
				$data_arr['DeliveryAddress1'] = $_POST['address'];
				$data_arr['DeliveryCity'] = $_POST['city'];
				$data_arr['DeliveryPostCode'] = $_POST['zip'];
				if (!empty($_POST['state'])) {
					$data_arr['DeliveryState'] = substr($_POST['state'], 0, 2);
				}
				$data_arr['DeliveryCountry'] = $_POST['country'];
				$data_arr['DeliveryPhone'] = $_POST['phonenumber'];
			}
			
			$itemized_array = $invoice->display('itemized');
			$basket = count($itemized_array);
			
			foreach($itemized_array as $itemized_item) {
				$basket .= 	":".$itemized_item[name].":".$itemized_item[quantity].":".number_format($itemized_item[price],2).":".
							number_format(($itemized_item[price]*($tax / 100)),2).":".number_format((($itemized_item[price]*($tax / 100))+$itemized_item[price]),2).
							":".number_format(((($itemized_item[price]*($tax / 100))+$itemized_item[price])*$itemized_item[quantity]),2);
			}
			
			$data_arr['Basket'] = $basket;
			
			$datas_arr = array();
			foreach ($data_arr as $key=>$_val) {
				$datas_arr[] = "{$key}={$_val}";
			}
			
			$datas = join('&', $datas_arr);
			$enc_data = web_invoice_xor_encrypt($datas, get_option('web_invoice_sagepay_vendor_key'));
			
			print $enc_data;
			
		} else if (isset($_POST['processor']) && $_POST['processor'] == 'pfp') {
			require_once('gateways/payflowpro.class.php');
			
			if($recurring) {
	
				$arb = new Web_Invoice_PayflowProRecurring();
					
				$arb->transaction($_POST['card_num']);
				$arb->setTransactionType('R');

				// Billing Info
				$arb->setParameter("CVV2", $_POST['card_code']);
				$arb->setParameter("EXPDATE ", $_POST['exp_month'] . substr($_POST['exp_year'], 2));
				$arb->setParameter("AMT", $invoice->display('amount'));
				$arb->setParameter("CURRENCYCODE", $invoice->display('currency'));
				if($recurring) {
					$arb->setParameter("RECURRING", 'Y');	
				}
					
				//Subscription Info
				$arb->setParameter('BILLINGFREQUENCY', $invoice->display('interval_length'));
				
				if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
					$arb->setParameter('DESC', $invoice->display('subscription_name'));
					$arb->setParameter('BILLINGPERIOD', web_invoice_pfp_convert_interval($invoice->display('interval_length'), $invoice->display('interval_unit')));
					$arb->setParameter('PROFILESTARTDATE', date('c', strtotime($invoice->display('startDate'))));
					$arb->setParameter('TOTALBILLINGCYCLES', $invoice->display('totalOccurrences'));
				} else {
					$arb->setParameter('PROFILENAME', $invoice->display('subscription_name'));
					$arb->setParameter('START', date('mdY', strtotime($invoice->display('startDate'))+3600*24));
					$arb->setParameter('TERM', $invoice->display('totalOccurrences'));
					$arb->setParameter('PAYPERIOD', web_invoice_pfp_wpppe_convert_interval($invoice->display('interval_length'), $invoice->display('interval_unit')));
				}
				
				$arb->setParameter('ACTION', 'A');
					
				$arb->setParameter("CUSTBROWSER", $_SERVER['HTTP_USER_AGENT']);
				$arb->setParameter("CUSTHOSTNAME", $_SERVER['HTTP_HOST']);
				$arb->setParameter("CUSTIP ", $_SERVER['REMOTE_ADDR']);
				
				//Customer Info
				$arb->setParameter("FIRSTNAME", $_POST['first_name']);
				$arb->setParameter("LASTNAME", $_POST['last_name']);
				$arb->setParameter("STREET", $_POST['address']);
				$arb->setParameter("CITY", $_POST['city']);
				$arb->setParameter("STATE", $_POST['state']);
				$arb->setParameter("COUNTRYCODE", $_POST['country']);
				$arb->setParameter("ZIP", $_POST['zip']);
				$arb->setParameter("PHONENUM", $_POST['phonenumber']);
				$arb->setParameter("EMAIL", $_POST['email_address']);
				$arb->setParameter("COMMENT1", "{$_POST['first_name']} {$_POST['last_name']} ".$invoice->display('subscription_name')." Recurring");
				
				if (get_option('web_invoice_pfp_shipping_details') == 'True') {
					//Shipping Info
					$arb->setParameter("SHIPTONAME", "{$_POST['shipto_first_name']} {$_POST['shipto_last_name']}");
					$arb->setParameter("SHIPTOSTREET", $_POST['shipto_address']);
					$arb->setParameter("SHIPTOCITY", $_POST['shipto_city']);
					$arb->setParameter("SHIPTOSTATE", $_POST['shipto_state']);
					$arb->setParameter("SHIPTOCOUNTRY", $_POST['shipto_country']);
					$arb->setParameter("SHIPTOZIP", $_POST['shipto_zip']);
					$arb->setParameter("SHIPTOPHONENUM", $_POST['shipto_phonenumber']);
				}
				
				// Order Info
				$arb->setParameter("COMMENT2", $invoice->display('subject'));
				$arb->setParameter("CUSTREF",  $invoice->display('display_id'));
	
				$arb->createAccount();
				
				if ($arb->isSuccessful()) {
					echo "Transaction okay.";
					
					update_usermeta($wp_users_id,'last_name',$_POST['last_name']);
					update_usermeta($wp_users_id,'first_name',$_POST['first_name']);
					update_usermeta($wp_users_id,'city',$_POST['city']);
					update_usermeta($wp_users_id,'state',$_POST['state']);
					update_usermeta($wp_users_id,'zip',$_POST['zip']);
					update_usermeta($wp_users_id,'tax_id',$_POST['tax_id']);
					update_usermeta($wp_users_id,'company_name',$_POST['company_name']);
					update_usermeta($wp_users_id,'streetaddress',$_POST['address']);
					update_usermeta($wp_users_id,'phonenumber',$_POST['phonenumber']);
					update_usermeta($wp_users_id,'country',$_POST['country']);
					
					if (get_option('web_invoice_pfp_shipping_details') == 'True') {
						update_usermeta($wp_users_id,'shipto_last_name',$_POST['shipto_last_name']);
						update_usermeta($wp_users_id,'shipto_first_name',$_POST['shipto_first_name']);
						update_usermeta($wp_users_id,'shipto_streetaddress',$_POST['shipto_address']);
						update_usermeta($wp_users_id,'shipto_city',$_POST['shipto_city']);
						update_usermeta($wp_users_id,'shipto_state',$_POST['shipto_state']);
						update_usermeta($wp_users_id,'shipto_zip',$_POST['shipto_zip']);
						update_usermeta($wp_users_id,'shipto_phonenumber',$_POST['shipto_phonenumber']);
						update_usermeta($wp_users_id,'shipto_country',$_POST['shipto_country']);
					}
					
					web_invoice_update_recurring_start_date($invoice_id, strtotime(date('Y-m-d')));
					
					web_invoice_update_invoice_meta($invoice_id, 'subscription_id', $arb->getSubscriberID());
					web_invoice_update_invoice_meta($invoice_id, 'recurring_transaction_id', $arb->getTransactionID());
					web_invoice_update_invoice_meta($invoice_id, 'pfp_status', 'active');
					
					web_invoice_update_log($invoice_id, 'subscription', ' Subscription initiated, Subcription ID - ' . $arb->getSubscriberID());

                    web_invoice_paid($invoice_id);
					web_invoice_mark_as_paid($invoice_id);
				}
	
				if($arb->isError()) {
					$errors ['processing_problem'][] .=  "One-time credit card payment is processed successfully. However, recurring billing setup failed."; $stop_transaction = true;
					web_invoice_update_log($invoice_id, 'subscription_error', 'Response Code: ' . $arb->getResponseCode() . ' | Subscription error - ' . $arb->getResponseText());
					web_invoice_update_log($invoice_id, 'pfp_failure', "Failed PFP payment. REF: ".serialize($payment));
				}
			} else {
				$payment = new Web_Invoice_PayflowPro(true);
				
				$payment->transaction($_POST['card_num']);
	
				// Billing Info
				$payment->setParameter("CVV2", $_POST['card_code']);
				$payment->setParameter("EXPDATE ", $_POST['exp_month'] . substr($_POST['exp_year'], 2));
				$payment->setParameter("AMT", $invoice->display('amount'));
				$payment->setParameter("CURRENCYCODE", $invoice->display('currency'));
				if($recurring) {
					$payment->setParameter("RECURRING", 'Y');	
				}
				
				$payment->setParameter("CUSTBROWSER", $_SERVER['HTTP_USER_AGENT']);
				$payment->setParameter("CUSTHOSTNAME", $_SERVER['HTTP_HOST']);
				$payment->setParameter("CUSTIP ", $_SERVER['REMOTE_ADDR']);
				
				//Customer Info
				$payment->setParameter("FIRSTNAME", $_POST['first_name']);
				$payment->setParameter("LASTNAME", $_POST['last_name']);
				$payment->setParameter("STREET", $_POST['address']);
				$payment->setParameter("CITY", $_POST['city']);
				$payment->setParameter("STATE", $_POST['state']);
				$payment->setParameter("COUNTRYCODE", $_POST['country']);
				$payment->setParameter("ZIP", $_POST['zip']);
				$payment->setParameter("PHONENUM", $_POST['phonenumber']);
				$payment->setParameter("EMAIL", $_POST['email_address']);
				$payment->setParameter("COMMENT1", "WP User - " . $invoice->recipient('user_id'));
				
				if (get_option('web_invoice_pfp_shipping_details') == 'True') {
					//Shipping Info
					$payment->setParameter("SHIPTONAME", "{$_POST['shipto_first_name']} {$_POST['shipto_last_name']}");
					$payment->setParameter("SHIPTOSTREET", $_POST['shipto_address']);
					$payment->setParameter("SHIPTOCITY", $_POST['shipto_city']);
					$payment->setParameter("SHIPTOSTATE", $_POST['shipto_state']);
					$payment->setParameter("SHIPTOCOUNTRY", $_POST['shipto_country']);
					$payment->setParameter("SHIPTOZIP", $_POST['shipto_zip']);
					$payment->setParameter("SHIPTOPHONENUM", $_POST['shipto_phonenumber']);
				}
				
				// Order Info
				$payment->setParameter("COMMENT2", $invoice->display('subject'));
				$payment->setParameter("CUSTREF",  $invoice->display('display_id'));
		
				$payment->process();
				
				if ($payment->isApproved()) {
					echo "Transaction okay.";
		
					update_usermeta($wp_users_id,'last_name',$_POST['last_name']);
					update_usermeta($wp_users_id,'first_name',$_POST['first_name']);
					update_usermeta($wp_users_id,'city',$_POST['city']);
					update_usermeta($wp_users_id,'state',$_POST['state']);
					update_usermeta($wp_users_id,'zip',$_POST['zip']);
					update_usermeta($wp_users_id,'tax_id',$_POST['tax_id']);
					update_usermeta($wp_users_id,'company_name',$_POST['company_name']);
					update_usermeta($wp_users_id,'streetaddress',$_POST['address']);
					update_usermeta($wp_users_id,'phonenumber',$_POST['phonenumber']);
					update_usermeta($wp_users_id,'country',$_POST['country']);
					
					if (get_option('web_invoice_pfp_shipping_details') == 'True') {
						update_usermeta($wp_users_id,'shipto_last_name',$_POST['shipto_last_name']);
						update_usermeta($wp_users_id,'shipto_first_name',$_POST['shipto_first_name']);
						update_usermeta($wp_users_id,'shipto_streetaddress',$_POST['shipto_address']);
						update_usermeta($wp_users_id,'shipto_city',$_POST['shipto_city']);
						update_usermeta($wp_users_id,'shipto_state',$_POST['shipto_state']);
						update_usermeta($wp_users_id,'shipto_zip',$_POST['shipto_zip']);
						update_usermeta($wp_users_id,'shipto_phonenumber',$_POST['shipto_phonenumber']);
						update_usermeta($wp_users_id,'shipto_country',$_POST['shipto_country']);
					}
		
					//Mark invoice as paid
					web_invoice_paid($invoice_id);
					web_invoice_update_log($invoice_id, 'pfp_success', "Successful payment. REF: {$payment->getTransactionID()}");
					web_invoice_update_invoice_meta($invoice_id, 'transaction_id', $payment->getTransactionID());
					
					web_invoice_mark_as_paid($invoice_id);
					// if(get_option('web_invoice_send_thank_you_email') == 'yes') web_invoice_send_email_receipt($invoice_id);
		
				} else {
					$errors [ 'processing_problem' ] [] .= $payment->getResponseText();$stop_transaction = true;
					web_invoice_update_log($invoice_id, 'pfp_failure', "Failed PFP payment. REF: ".$payment->getTransactionID()." ".serialize($payment));
				}
			}
		} else {
			require_once('gateways/authnet.class.php');
			require_once('gateways/authnetARB.class.php');
			
			$payment = new Web_Invoice_Authnet(true);
			
			$payment->transaction($_POST['card_num']);

			// Billing Info
			$payment->setParameter("x_card_code", $_POST['card_code']);
			$payment->setParameter("x_exp_date ", $_POST['exp_month'] . $_POST['exp_year']);
			$payment->setParameter("x_amount", $invoice->display('amount'));
			if($recurring) $payment->setParameter("x_web_invoice_recurring_billing", true);
	
			// Order Info
			$payment->setParameter("x_description", $invoice->display('subject'));
			$payment->setParameter("x_invoice_num",  $invoice->display('display_id'));
			$payment->setParameter("x_test_request", false);
			$payment->setParameter("x_duplicate_window", 30);
	
			//Customer Info
			$payment->setParameter("x_first_name", $_POST['first_name']);
			$payment->setParameter("x_last_name", $_POST['last_name']);
			$payment->setParameter("x_address", $_POST['address']);
			$payment->setParameter("x_city", $_POST['city']);
			$payment->setParameter("x_state", $_POST['state']);
			$payment->setParameter("x_country", $_POST['country']);
			$payment->setParameter("x_zip", $_POST['zip']);
			$payment->setParameter("x_phone", $_POST['phonenumber']);
			$payment->setParameter("x_email", $_POST['email_address']);
			$payment->setParameter("x_cust_id", "WP User - " . $invoice->recipient('user_id'));
			$payment->setParameter("x_customer_ip ", $_SERVER['REMOTE_ADDR']);
	
			$payment->process();
			
			if($payment->isApproved()) {
				echo "Transaction okay.";
	
				update_usermeta($wp_users_id,'last_name',$_POST['last_name']);
				update_usermeta($wp_users_id,'first_name',$_POST['first_name']);
				update_usermeta($wp_users_id,'city',$_POST['city']);
				update_usermeta($wp_users_id,'state',$_POST['state']);
				update_usermeta($wp_users_id,'zip',$_POST['zip']);
				update_usermeta($wp_users_id,'tax_id',$_POST['tax_id']);
				update_usermeta($wp_users_id,'company_name',$_POST['company_name']);
				update_usermeta($wp_users_id,'streetaddress',$_POST['address']);
				update_usermeta($wp_users_id,'phonenumber',$_POST['phonenumber']);
				update_usermeta($wp_users_id,'country',$_POST['country']);
	
				//Mark invoice as paid
				web_invoice_paid($invoice_id);
				web_invoice_mark_as_paid($invoice_id);
				// if(get_option('web_invoice_send_thank_you_email') == 'yes') web_invoice_send_email_receipt($invoice_id);
	
				if($recurring) {
	
					$arb = new Web_Invoice_AuthnetARB();
					// Customer Info
					$arb->setParameter('customerId', "WP User - " . $invoice->recipient('user_id'));
					$arb->setParameter('firstName', $_POST['first_name']);
					$arb->setParameter('lastName', $_POST['last_name']);
					$arb->setParameter('address', $_POST['address']);
					$arb->setParameter('city', $_POST['city']);
					$arb->setParameter('state', $_POST['state']);
					$arb->setParameter('zip', $_POST['zip']);
					$arb->setParameter('country', $_POST['country']);
					$arb->setParameter('customerEmail', $_POST['email_address']);
					$arb->setParameter('customerPhoneNumber', $_POST['phonenumber']);
	
					// Billing Info
					$arb->setParameter('amount', $invoice->display('amount'));
					$arb->setParameter('cardNumber', $_POST['card_num']);
					$arb->setParameter('expirationDate', $_POST['exp_month'].$_POST['exp_year']);
	
					//Subscription Info
					$arb->setParameter('refID',  $invoice->display('display_id'));
					$arb->setParameter('subscrName', $invoice->display('subscription_name'));
					$arb->setParameter('interval_length', $invoice->display('interval_length'));
					$arb->setParameter('interval_unit', $invoice->display('interval_unit'));
					$arb->setParameter('startDate', $invoice->display('startDate'));
					$arb->setParameter('totalOccurrences', $invoice->display('totalOccurrences'));
	
					// First billing cycle is taken care off with initial payment
					$arb->setParameter('trialOccurrences', '1');
					$arb->setParameter('trialAmount', '0.00');
	
					$arb->setParameter('orderInvoiceNumber',  $invoice->display('display_id'));
					$arb->setParameter('orderDescription', $invoice->display('subject'));
	
					$arb->createAccount();
	
					if ($arb->isSuccessful()) {
						web_invoice_update_recurring_start_date($invoice_id, strtotime(date('Y-m-d')));
						web_invoice_update_invoice_meta($invoice_id, 'subscription_id',$arb->getSubscriberID());
						web_invoice_update_log($invoice_id, 'subscription', ' Subscription initiated, Subcription ID - ' . $arb->getSubscriberID());
					}
	
					if($arb->isError()) {
						$errors [ 'processing_problem' ] [] .=  "One-time credit card payment is processed successfully.  However, recurring billing setup failed." . $arb->getResponse(); $stop_transaction = true;;
						web_invoice_update_log($invoice_id, 'subscription_error', 'Response Code: ' . $arb->getResponseCode() . ' | Subscription error - ' . $arb->getResponse());
	
					}
				}
			} else {
				$errors [ 'processing_problem' ] [] .= $payment->getResponseText();$stop_transaction = true;
	
			}
		}

		// Uncomment these to troubleshoot.  You will need FireBug to view the response of the AJAX post.
		//echo $arb->xml;
		//echo $arb->response;
		//echo $arb->getResponse();

		// echo $payment->getResponseText();
		// echo $payment->getTransactionID();
		// echo $payment->getAVSResponse();
		// echo $payment->getAuthCode();
	}


	if ($stop_transaction && is_array($_POST))
	{
		foreach ( $_POST as $key => $value )
		{
			if ( array_key_exists ( $key, $errors ) )
			{
				foreach ( $errors [ $key ] as $k => $v )
				{
					$errors_msg .= "error|$key|$v\n";
				}
			}
			else {
				$errors_msg .= "ok|$key\n";
			}
		}
	}


	echo $errors_msg;
}

function web_invoice_currency_array() {
	$currency_list = array(
		"AUD"=> __("Australian Dollars", WEB_INVOICE_TRANS_DOMAIN),
		"CAD"=> __("Canadian Dollars", WEB_INVOICE_TRANS_DOMAIN),
		"EUR"=> __("Euros", WEB_INVOICE_TRANS_DOMAIN),
		"GBP"=> __("Pounds Sterling", WEB_INVOICE_TRANS_DOMAIN),
		"JPY"=> __("Yen", WEB_INVOICE_TRANS_DOMAIN),
		"USD"=> __("U.S. Dollars", WEB_INVOICE_TRANS_DOMAIN),
		"NZD"=> __("New Zealand Dollar", WEB_INVOICE_TRANS_DOMAIN),
		"CHF"=> __("Swiss Franc", WEB_INVOICE_TRANS_DOMAIN),
		"HKD"=> __("Hong Kong Dollar", WEB_INVOICE_TRANS_DOMAIN),
		"SGD"=> __("Singapore Dollar", WEB_INVOICE_TRANS_DOMAIN),
		"SEK"=> __("Swedish Krona", WEB_INVOICE_TRANS_DOMAIN),
		"DKK"=> __("Danish Krone", WEB_INVOICE_TRANS_DOMAIN),
		"PLN"=> __("Polish Zloty", WEB_INVOICE_TRANS_DOMAIN),
		"NOK"=> __("Norwegian Krone", WEB_INVOICE_TRANS_DOMAIN),
		"HUF"=> __("Hungarian Forint", WEB_INVOICE_TRANS_DOMAIN),
		"CZK"=> __("Czech Koruna", WEB_INVOICE_TRANS_DOMAIN),
		"ILS"=> __("Israeli Shekel", WEB_INVOICE_TRANS_DOMAIN),
		"MXN"=> __("Mexican Peso", WEB_INVOICE_TRANS_DOMAIN),
		"BRL"=> __("Brazilian Real", WEB_INVOICE_TRANS_DOMAIN),
		"MYR"=> __("Malaysian Ringgit", WEB_INVOICE_TRANS_DOMAIN),
		"ZAR"=> __("South African Rand", WEB_INVOICE_TRANS_DOMAIN),
		"COP"=> __("Colombian Pesos", WEB_INVOICE_TRANS_DOMAIN),
		"RON"=> __("Romanian New Leu", WEB_INVOICE_TRANS_DOMAIN),
		"PHP"=> __("Philippine Peso", WEB_INVOICE_TRANS_DOMAIN),
		"IDR"=> __("Indonesian Rupiah", WEB_INVOICE_TRANS_DOMAIN),
	);

	return $currency_list;
}

function web_invoice_currency_symbol($currency = "USD" )
{
	$currency_list = array(
		'CAD' => '$',
		'EUR' => '&euro;',
		'GBP' => '&pound;',
		'JPY' => '&yen;',
		'USD' => '$',
		'BRL' => 'R$',
		'MYR' => 'RM',
		'AUD' => '$',
		'ZAR' => 'R',
		'COP' => '$',
		'IDR' => 'Rp',
	);

	foreach($currency_list as $value => $display)
	{
		if($currency == $value) { return $display; $success = true; break;}
	}
	if(!$success) return $currency;
}

function web_invoice_currency_symbol_format($currency = "USD" )
{
	$currency_list = array(
		'CAD' => __('$%s', WEB_INVOICE_TRANS_DOMAIN),
		'EUR' => __('&euro;%s', WEB_INVOICE_TRANS_DOMAIN),
		'GBP' => __('&pound;%s', WEB_INVOICE_TRANS_DOMAIN),
		'JPY' => __('&yen;%s', WEB_INVOICE_TRANS_DOMAIN),
		'USD' => __('$%s', WEB_INVOICE_TRANS_DOMAIN),
		'BRL' => __('R$%s', WEB_INVOICE_TRANS_DOMAIN),
		'ZAR' => __('R%s', WEB_INVOICE_TRANS_DOMAIN),
		'AUD' => __('$%s', WEB_INVOICE_TRANS_DOMAIN),
		'COP' => __('$%s', WEB_INVOICE_TRANS_DOMAIN),
		'IDR' => __('Rp %s', WEB_INVOICE_TRANS_DOMAIN),
	);


	foreach($currency_list as $value => $display)
	{
		if($currency == $value) { return $display; $success = true; break;}
	}
	if(!$success) return __("{$currency}%s", WEB_INVOICE_TRANS_DOMAIN);
}

function web_invoice_contextual_help_list($content, $screen_id, $screen) {
	if (strstr($screen_id, 'web-invoice')) {
		$content = '<h2>WordPress</h2>'.$content;
		$content .= '<h2>Web Invoice</h2>'.
		'<a href="http://mohanjith.com/forum/" target="_blank">Support Forums</a><br/>'.
		'<a href="http://code.google.com/p/web-invoice/issues/list" target"_blank">Issue tracker</a><br/>'.
		'<a href="http://code.google.com/p/web-invoice/w/list" target"_blank">Wiki</a>';
		
	}
	if ($screen_id == 'web-invoice_page_web_invoice_templates') {
		$content .= '<h3>Template variables</h3>'.
		'<ul>'.
		'<li><code>call_sign</code> - Customer name</li>'.
		'<li><code>streetaddress</code> - Customer street address</li>'.
		'<li><code>city</code> - Customer city</li>'.
		'<li><code>zip</code> - Customer zip/postal code</li>'.
		'<li><code>state</code> - Customer state</li>'.
		'<li><code>country</code> - Customer country code</li>'.
		'<li><code>business_name</code> - Business name</li>'.
		'<li><code>recurring</code> - Will be either recurring or blank ("")</li>'.
		'<li><code>amount</code> - Amount</li>'.
		'<li><code>link</code> - Invoice link</li>'.
		'<li><code>business_email</code> - Business e-mail address</li>'.
		'<li><code>subject</code> - Invoice subject</li>'.
		'<li><code>invoice_id</code> - Invoice ID</li>'.
		'<li><code>invoice_hash</code> - MD5 hash of Invoice ID (Used in the invoice link)</li>'.
		'<li><code>content</code> - Invoice table, only in PDF and web template</li>'.
		'<li><code>print_message</code> - Instructions on how to print, only in web template</li>'.
		'<li><code>pdf_link</code> - Link to the invoice PDF, only in web template</li>'.
		'<li><code>invoice_date</code> - Invoice date or the date the invoice was created</li>'.
		'</ul>';
	}
	// Will add help and FAQ here eventually
	return $content;
}

function web_invoice_self_generate_from_template($template_invoice_id, $user_id) {
	global $wpdb;
	
	$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$template_invoice_id."'");
	
	$_POST['user_id'] = $user_id;
	$_REQUEST['user_id'] = $user_id;
	$_REQUEST['amount'] = $invoice_info->amount;
	$_REQUEST['subject'] = $invoice_info->subject;
	$_REQUEST['description'] = $invoice_info->description;
	
	$itemized = $invoice_info->itemized;
	$_REQUEST['itemized_list'] = unserialize(urldecode($itemized));
	
	$_REQUEST['web_invoice_tax'] = unserialize(web_invoice_meta($template_invoice_id,'tax_value'));
	$_REQUEST['web_invoice_payment_methods'] = web_invoice_meta($template_invoice_id,'web_invoice_payment_methods');
	
	$_REQUEST['web_invoice_currency_code'] = web_invoice_meta($template_invoice_id,'web_invoice_currency_code');

	$_REQUEST['web_invoice_date_day'] = web_invoice_meta($template_invoice_id,'web_invoice_date_day');
	$_REQUEST['web_invoice_date_month'] = web_invoice_meta($template_invoice_id,'web_invoice_date_month');
	$_REQUEST['web_invoice_date_year'] = web_invoice_meta($template_invoice_id,'web_invoice_date_year');
	
	$_REQUEST['web_invoice_date_day'] = date('d', strtotime($invoice_info->invoice_date));
	$_REQUEST['web_invoice_date_month'] = date('m', strtotime($invoice_info->invoice_date));
	$_REQUEST['web_invoice_date_year'] = date('Y', strtotime($invoice_info->invoice_date));

	$_REQUEST['web_invoice_due_date_day'] = web_invoice_meta($template_invoice_id,'web_invoice_due_date_day');
	$_REQUEST['web_invoice_due_date_month'] = web_invoice_meta($template_invoice_id,'web_invoice_due_date_month');
	$_REQUEST['web_invoice_due_date_year'] = web_invoice_meta($template_invoice_id,'web_invoice_due_date_year');

	$_REQUEST['web_invoice_subscription_name'] = web_invoice_meta($template_invoice_id,'web_invoice_subscription_name');
	$_REQUEST['web_invoice_subscription_unit'] = web_invoice_meta($template_invoice_id,'web_invoice_subscription_unit');
	$_REQUEST['web_invoice_subscription_length'] = web_invoice_meta($template_invoice_id,'web_invoice_subscription_length');
	$_REQUEST['web_invoice_subscription_start_month'] = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_month');
	$_REQUEST['web_invoice_subscription_start_day'] = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_day');
	$_REQUEST['web_invoice_subscription_start_year'] = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_year');
	$_REQUEST['web_invoice_subscription_total_occurances'] = web_invoice_meta($template_invoice_id,'web_invoice_subscription_total_occurances');

	// $web_invoice_recurring_billing = web_invoice_meta($template_invoice_id,'web_invoice_recurring_billing');
	$invoice_id = rand(10000000, 90000000);

	web_invoice_process_invoice_update($invoice_id, true);
	
	return $invoice_id;
}

function web_invoice_process_invoice_update($invoice_id, $unprivileged = false) {

	global $wpdb;
	
	if ($unprivileged) {
		$profileuser = get_currentuserinfo();
	} else {
		$profileuser = get_userdata($_POST['user_id']);
	}
	
	$description = $_REQUEST['description'];
	$subject = $_REQUEST['subject'];
	$amount = $_REQUEST['amount'];
	$user_id = $_REQUEST['user_id'];
	$web_invoice_tax = serialize($_REQUEST['web_invoice_tax']);
	
	$itemized_list = $_REQUEST['itemized_list'];
	$web_invoice_custom_invoice_id = $_REQUEST['web_invoice_custom_invoice_id'];
	
	$web_invoice_date = "{$_REQUEST['web_invoice_date_year']}-{$_REQUEST['web_invoice_date_month']}-{$_REQUEST['web_invoice_date_day']}";
	
	$web_invoice_due_date_month = $_REQUEST['web_invoice_due_date_month'];
	$web_invoice_due_date_day = $_REQUEST['web_invoice_due_date_day'];
	$web_invoice_due_date_year = $_REQUEST['web_invoice_due_date_year'];

	$web_invoice_first_name = $_REQUEST['web_invoice_first_name'];
	$web_invoice_last_name = $_REQUEST['web_invoice_last_name'];
	$web_invoice_tax_id = $_REQUEST['web_invoice_tax_id'];
	$web_invoice_company_name = $_REQUEST['web_invoice_company_name'];
	$web_invoice_streetaddress = $_REQUEST['web_invoice_streetaddress'];
	$web_invoice_city = $_REQUEST['web_invoice_city'];
	$web_invoice_state = $_REQUEST['web_invoice_state'];
	$web_invoice_zip = $_REQUEST['web_invoice_zip'];
	$web_invoice_country = $_REQUEST['web_invoice_country'];

	$web_invoice_currency_code = $_REQUEST['web_invoice_currency_code'];

	$web_invoice_subscription_name = $_REQUEST['web_invoice_subscription_name'];
	$web_invoice_subscription_unit = $_REQUEST['web_invoice_subscription_unit'];
	$web_invoice_subscription_length = $_REQUEST['web_invoice_subscription_length'];
	$web_invoice_subscription_start_month = $_REQUEST['web_invoice_subscription_start_month'];
	$web_invoice_subscription_start_day = $_REQUEST['web_invoice_subscription_start_day'];
	$web_invoice_subscription_start_year = $_REQUEST['web_invoice_subscription_start_year'];
	$web_invoice_subscription_total_occurances = $_REQUEST['web_invoice_subscription_total_occurances'];
	
	$web_invoice_payment_methods = join(',', $_REQUEST['web_invoice_payment_methods']);
	
	$web_invoice_tax_names = unserialize(get_option('web_invoice_tax_name'));
	if (!is_array($web_invoice_tax_names)) {
		$web_invoice_tax_names = array();
	}
	for ($_txc=0; $_txc<get_option('web_invoice_tax_count'); $_txc++) {
		if (!isset($_REQUEST['web_invoice_tax_name_'.$_txc])) continue;
		$web_invoice_tax_names[$_txc] = $_REQUEST['web_invoice_tax_name_'.$_txc];
	}

	//remove items from itemized list that are missing a title, they are most likely deleted
	if(is_array($itemized_list)) {
		$counter = 1;
		foreach($itemized_list as $itemized_item){
			if(empty($itemized_item[name])) {
				unset($itemized_list[$counter]);
			}
			$counter++;
		}
		array_values($itemized_list);
	}
	$itemized = urlencode(serialize($itemized_list));


	// Check if this is new invoice creation, or an update

	if(web_invoice_does_invoice_exist($invoice_id)) {
		// Updating Old Invoice

		if(web_invoice_get_invoice_attrib($invoice_id,'subject') != $subject) { 
			$wpdb->query("UPDATE ".Web_Invoice::tablename('main')." SET subject = '$subject' WHERE invoice_num = $invoice_id"); 
			web_invoice_update_log($invoice_id, 'updated', ' Subject Updated '); 
			$message .= "Subject updated. ";
			web_invoice_clear_cache();
		}
		if(web_invoice_get_invoice_attrib($invoice_id,'description') != $description) { 
			$wpdb->query("UPDATE ".Web_Invoice::tablename('main')." SET description = '$description' WHERE invoice_num = $invoice_id"); 
			web_invoice_update_log($invoice_id, 'updated', ' Description Updated '); 
			$message .= "Description updated. ";
			web_invoice_clear_cache();
		}
		if(web_invoice_get_invoice_attrib($invoice_id,'amount') != $amount) { 
			$wpdb->query("UPDATE ".Web_Invoice::tablename('main')." SET amount = '$amount' WHERE invoice_num = $invoice_id"); 
			web_invoice_update_log($invoice_id, 'updated', ' Amount Updated '); 
			$message .= "Amount updated. ";
			web_invoice_clear_cache();
		}
		if(web_invoice_get_invoice_attrib($invoice_id,'invoice_date') != $web_invoice_date) { 
			$wpdb->query("UPDATE ".Web_Invoice::tablename('main')." SET invoice_date = '$web_invoice_date' WHERE invoice_num = $invoice_id"); 
			web_invoice_update_log($invoice_id, 'updated', ' Invoice Date Updated '); 
			$message .= "Invoice date updated. ";
			web_invoice_clear_cache();
		}
		if(web_invoice_get_invoice_attrib($invoice_id,'itemized') != $itemized) { 
			$wpdb->query("UPDATE ".Web_Invoice::tablename('main')." SET itemized = '$itemized' WHERE invoice_num = $invoice_id"); 
			web_invoice_update_log($invoice_id, 'updated', ' Itemized List Updated '); 
			$message .= "Itemized List updated. ";
			web_invoice_clear_cache();
		}
	}
	else {
		// Create New Invoice
		if($wpdb->query("INSERT INTO ".Web_Invoice::tablename('main')." (amount,invoice_date,description,invoice_num,user_id,subject,itemized,status)	VALUES ('$amount','$web_invoice_date','$description','$invoice_id','$user_id','$subject','$itemized','0')")) {
			$message = "New Invoice saved.";
			web_invoice_update_log($invoice_id, 'created', ' Created ');;
		}
		else {
			$error = true; $message = "There was a problem saving invoice. Try deactivating and reactivating plugin. REF: ".mysql_errno();
		}
	}

	// See if invoice is recurring
	if(!empty($web_invoice_subscription_name) &&	!empty($web_invoice_subscription_unit) && !empty($web_invoice_subscription_total_occurances)) {
		$web_invoice_recurring_status = true;
		web_invoice_update_invoice_meta($invoice_id, "web_invoice_recurring_billing", true);
		$message .= " Recurring invoice saved.  This invoice may be viewed under \"Recurring Billing\". ";

	}

	// See if invoice is recurring
	if(empty($web_invoice_subscription_name) &&	empty($web_invoice_subscription_unit) && empty($web_invoice_subscription_total_occurances)) {
		$web_invoice_recurring_status = false;
		web_invoice_update_invoice_meta($invoice_id, "web_invoice_recurring_billing", false);
	}

	// Update Invoice Meta
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_custom_invoice_id", $web_invoice_custom_invoice_id);
	web_invoice_update_invoice_meta($invoice_id, "tax_value", $web_invoice_tax);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_currency_code", $web_invoice_currency_code);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_due_date_day", $web_invoice_due_date_day);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_due_date_month", $web_invoice_due_date_month);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_due_date_year", $web_invoice_due_date_year);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_payment_methods", $web_invoice_payment_methods);
	
	// Update Invoice Recurring Meta
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_subscription_name", $web_invoice_subscription_name);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_subscription_unit", $web_invoice_subscription_unit);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_subscription_length", $web_invoice_subscription_length);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_subscription_start_month", $web_invoice_subscription_start_month);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_subscription_start_day", $web_invoice_subscription_start_day);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_subscription_start_year", $web_invoice_subscription_start_year);
	web_invoice_update_invoice_meta($invoice_id, "web_invoice_subscription_total_occurances", $web_invoice_subscription_total_occurances);

	//Update User Information
	if(!empty($web_invoice_first_name)) update_usermeta($user_id, 'first_name', $web_invoice_first_name);
	if(!empty($web_invoice_last_name)) update_usermeta($user_id, 'last_name', $web_invoice_last_name);
	if(!empty($web_invoice_company_name)) update_usermeta($user_id, 'company_name', $web_invoice_company_name);
	if(!empty($web_invoice_tax_id)) update_usermeta($user_id, 'tax_id', $web_invoice_tax_id);
	if(!empty($web_invoice_streetaddress)) update_usermeta($user_id, 'streetaddress', $web_invoice_streetaddress);
	if(!empty($web_invoice_city)) update_usermeta($user_id, 'city', $web_invoice_city);
	if(!empty($web_invoice_state)) update_usermeta($user_id, 'state', $web_invoice_state);
	if(!empty($web_invoice_zip)) update_usermeta($user_id, 'zip', $web_invoice_zip);
	if(!empty($web_invoice_country)) update_usermeta($user_id, 'country', $web_invoice_country);
	
	if(is_array($web_invoice_tax_names)) update_option('web_invoice_tax_name', serialize($web_invoice_tax_names));

	//If there is a message, append it with the web invoice link
	if($message && $invoice_id) {
		$invoice_info = new Web_Invoice_GetInfo($invoice_id);
		$message .= " <a href='".$invoice_info->display('link')."'>View Web Invoice</a>.";
	}


	if(!$error) return $message;
	if($error) return "An error occured: $message.";
}

function web_invoice_show_message($content,$type="updated fade") {
	if($content) echo "<div id=\"message\" class='$type' ><p>".$content."</p></div>";
}

function web_invoice_process_settings() {
	global $wpdb;

	// Save General Settings
	if(isset($_POST['web_invoice_business_name'])) update_option('web_invoice_business_name', $_POST['web_invoice_business_name']);
	if(isset($_POST['web_invoice_business_phone'])) update_option('web_invoice_business_phone', $_POST['web_invoice_business_phone']);
	if(isset($_POST['web_invoice_business_tax_id'])) update_option('web_invoice_business_tax_id', $_POST['web_invoice_business_tax_id']);
	if(isset($_POST['web_invoice_business_address'])) update_option('web_invoice_business_address', $_POST['web_invoice_business_address']);
	if(isset($_POST['web_invoice_default_currency_code'])) update_option('web_invoice_default_currency_code', $_POST['web_invoice_default_currency_code']);
	if(isset($_POST['web_invoice_using_godaddy'])) update_option('web_invoice_using_godaddy', $_POST['web_invoice_using_godaddy']);
	if(isset($_POST['web_invoice_email_address'])) update_option('web_invoice_email_address', $_POST['web_invoice_email_address']);
	if(isset($_POST['web_invoice_force_https'])) update_option('web_invoice_force_https', $_POST['web_invoice_force_https']);
	if(isset($_POST['web_invoice_payment_link'])) update_option('web_invoice_payment_link', $_POST['web_invoice_payment_link']);
	if(isset($_POST['web_invoice_payment_method'])) update_option('web_invoice_payment_method', join($_POST['web_invoice_payment_method'],','));
	if(isset($_POST['web_invoice_protocol'])) update_option('web_invoice_protocol', $_POST['web_invoice_protocol']);
	if(isset($_POST['web_invoice_send_thank_you_email'])) update_option('web_invoice_send_thank_you_email', $_POST['web_invoice_send_thank_you_email']);
	if(isset($_POST['web_invoice_cc_thank_you_email'])) update_option('web_invoice_cc_thank_you_email', $_POST['web_invoice_cc_thank_you_email']);
	if(isset($_POST['web_invoice_redirect_after_user_add'])) update_option('web_invoice_redirect_after_user_add', $_POST['web_invoice_redirect_after_user_add']);
	if(isset($_POST['web_invoice_self_generate_from_template'])) update_option('web_invoice_self_generate_from_template', $_POST['web_invoice_self_generate_from_template']);
	if(isset($_POST['web_invoice_partial_payments'])) update_option('web_invoice_partial_payments', $_POST['web_invoice_partial_payments']);
	if(isset($_POST['web_invoice_show_business_address'])) update_option('web_invoice_show_business_address', $_POST['web_invoice_show_business_address']);
	if(isset($_POST['web_invoice_show_billing_address'])) update_option('web_invoice_show_billing_address', $_POST['web_invoice_show_billing_address']);
	if(isset($_POST['web_invoice_show_quantities'])) update_option('web_invoice_show_quantities', $_POST['web_invoice_show_quantities']);
	if(isset($_POST['web_invoice_show_invoice_date'])) update_option('web_invoice_show_invoice_date', $_POST['web_invoice_show_invoice_date']);
	if(isset($_POST['web_invoice_use_css'])) update_option('web_invoice_use_css', $_POST['web_invoice_use_css']);
	if(isset($_POST['web_invoice_user_level'])) {
		if (is_array($_POST['web_invoice_user_level']) && count($_POST['web_invoice_user_level']) > 0) {
			$ro = new WP_Roles();
			foreach ($ro->role_objects as $role) {
				if ($role->has_cap('manage_web_invoice') && !in_array($role->name, $_POST['web_invoice_user_level'])) {
					$role->remove_cap('manage_web_invoice');
				}
				if (!$role->has_cap('manage_web_invoice') && in_array($role->name, $_POST['web_invoice_user_level'])) {
					$role->add_cap('manage_web_invoice', true);
				}
			}
		}
		update_option('web_invoice_user_level', $_POST['web_invoice_user_level']);	
	}
	if(isset($_POST['web_invoice_tax_count'])) update_option('web_invoice_tax_count', $_POST['web_invoice_tax_count']);
	if(isset($_POST['web_invoice_web_invoice_page'])) update_option('web_invoice_web_invoice_page', $_POST['web_invoice_web_invoice_page']);
	if(isset($_POST['web_invoice_reminder_message'])) update_option('web_invoice_reminder_message', $_POST['web_invoice_reminder_message']);

	if(isset($_POST['web_invoice_business_name']) || $_POST['web_invoice_business_address']|| $_POST['web_invoice_email_address'] || isset($_POST['web_invoice_business_phone']) || isset($_POST['web_invoice_tax_count']) || isset($_POST['web_invoice_business_tax_id']) || isset($_POST['web_invoice_payment_link'])) $message = "Information saved.";

	// Save Gateway Settings
	if(isset($_POST['web_invoice_recurring_gateway_url'])) update_option('web_invoice_recurring_gateway_url', $_POST['web_invoice_recurring_gateway_url']);
	if(isset($_POST['web_invoice_gateway_url'])) update_option('web_invoice_gateway_url', $_POST['web_invoice_gateway_url']);
	if(isset($_POST['web_invoice_gateway_username'])) update_option('web_invoice_gateway_username', $_POST['web_invoice_gateway_username']);
	if(isset($_POST['web_invoice_gateway_tran_key'])) update_option('web_invoice_gateway_tran_key', $_POST['web_invoice_gateway_tran_key']);
	if(isset($_POST['web_invoice_gateway_merchant_email'])) update_option('web_invoice_gateway_merchant_email', $_POST['web_invoice_gateway_merchant_email']);
	if(isset($_POST['web_invoice_gateway_delim_data'])) update_option('web_invoice_gateway_delim_data', $_POST['web_invoice_gateway_delim_data']);
	if(isset($_POST['web_invoice_gateway_delim_char'])) update_option('web_invoice_gateway_delim_char', $_POST['web_invoice_gateway_delim_char']);
	if(isset($_POST['web_invoice_gateway_encap_char'])) update_option('web_invoice_gateway_encap_char', $_POST['web_invoice_gateway_encap_char']);
	if(isset($_POST['web_invoice_gateway_header_email_receipt'])) update_option('web_invoice_gateway_header_email_receipt', $_POST['web_invoice_gateway_header_email_receipt']);
	if(isset($_POST['web_invoice_gateway_MD5Hash'])) update_option('web_invoice_gateway_MD5Hash', $_POST['web_invoice_gateway_MD5Hash']);
	if(isset($_POST['web_invoice_gateway_test_mode'])) update_option('web_invoice_gateway_test_mode', $_POST['web_invoice_gateway_test_mode']);
	if(isset($_POST['web_invoice_gateway_relay_response'])) update_option('web_invoice_gateway_relay_response', $_POST['web_invoice_gateway_relay_response']);
	if(isset($_POST['web_invoice_gateway_email_customer'])) update_option('web_invoice_gateway_email_customer', $_POST['web_invoice_gateway_email_customer']);

	// PayPal
	if(isset($_POST['web_invoice_paypal_address'])) update_option('web_invoice_paypal_address', $_POST['web_invoice_paypal_address']);
	if(isset($_POST['web_invoice_paypal_only_button'])) update_option('web_invoice_paypal_only_button', $_POST['web_invoice_paypal_only_button']);
	if(isset($_POST['web_invoice_paypal_sandbox'])) update_option('web_invoice_paypal_sandbox', $_POST['web_invoice_paypal_sandbox']);

	// Payflow
	if(isset($_POST['web_invoice_payflow_login'])) update_option('web_invoice_payflow_login', $_POST['web_invoice_payflow_login']);
	if(isset($_POST['web_invoice_payflow_partner'])) update_option('web_invoice_payflow_partner', $_POST['web_invoice_payflow_partner']);
	if(isset($_POST['web_invoice_payflow_only_button'])) update_option('web_invoice_payflow_only_button', $_POST['web_invoice_payflow_only_button']);
	if(isset($_POST['web_invoice_payflow_shipping_details'])) update_option('web_invoice_payflow_shipping_details', $_POST['web_invoice_payflow_shipping_details']);
	if(isset($_POST['web_invoice_payflow_silent_post'])) update_option('web_invoice_payflow_silent_post', $_POST['web_invoice_payflow_silent_post']);
	
	// Payflow Pro
	if(isset($_POST['web_invoice_pfp_partner'])) update_option('web_invoice_pfp_partner', $_POST['web_invoice_pfp_partner']);
	if(isset($_POST['web_invoice_pfp_env'])) update_option('web_invoice_pfp_env', $_POST['web_invoice_pfp_env']);
	if(isset($_POST['web_invoice_pfp_authentication'])) update_option('web_invoice_pfp_authentication', $_POST['web_invoice_pfp_authentication']);
	if(isset($_POST['web_invoice_pfp_username'])) update_option('web_invoice_pfp_username', $_POST['web_invoice_pfp_username']);
	if(isset($_POST['web_invoice_pfp_password'])) update_option('web_invoice_pfp_password', $_POST['web_invoice_pfp_password']);
	if(isset($_POST['web_invoice_pfp_signature'])) update_option('web_invoice_pfp_signature', $_POST['web_invoice_pfp_signature']);
	if(isset($_POST['web_invoice_pfp_wpppe_vendor'])) update_option('web_invoice_pfp_wpppe_vendor', $_POST['web_invoice_pfp_wpppe_vendor']);
	if(isset($_POST['web_invoice_pfp_wpppe_username'])) update_option('web_invoice_pfp_wpppe_username', $_POST['web_invoice_pfp_wpppe_username']);
	if(isset($_POST['web_invoice_pfp_wpppe_password'])) update_option('web_invoice_pfp_wpppe_password', $_POST['web_invoice_pfp_wpppe_password']);
	if(isset($_POST['web_invoice_pfp_3rdparty_email'])) update_option('web_invoice_pfp_3rdparty_email', $_POST['web_invoice_pfp_3rdparty_email']);
	if(isset($_POST['web_invoice_pfp_shipping_details'])) update_option('web_invoice_pfp_shipping_details', $_POST['web_invoice_pfp_shipping_details']);
	
	// Other/Bank
	if(isset($_POST['web_invoice_other_details'])) update_option('web_invoice_other_details', $_POST['web_invoice_other_details']);
	
	// Moneybookers
	if(isset($_POST['web_invoice_moneybookers_address'])) update_option('web_invoice_moneybookers_address', $_POST['web_invoice_moneybookers_address']);
	if(isset($_POST['web_invoice_moneybookers_recurring_address'])) update_option('web_invoice_moneybookers_recurring_address', $_POST['web_invoice_moneybookers_recurring_address']);
	if(isset($_POST['web_invoice_moneybookers_merchant'])) update_option('web_invoice_moneybookers_merchant', $_POST['web_invoice_moneybookers_merchant']);
	if(isset($_POST['web_invoice_moneybookers_secret'])) update_option('web_invoice_moneybookers_secret', $_POST['web_invoice_moneybookers_secret']);
	if(isset($_POST['web_invoice_moneybookers_ip'])) update_option('web_invoice_moneybookers_ip', $_POST['web_invoice_moneybookers_ip']);

	// AlertPay
	if(isset($_POST['web_invoice_alertpay_address'])) update_option('web_invoice_alertpay_address', $_POST['web_invoice_alertpay_address']);
	if(isset($_POST['web_invoice_alertpay_merchant'])) update_option('web_invoice_alertpay_merchant', $_POST['web_invoice_alertpay_merchant']);
	if(isset($_POST['web_invoice_alertpay_secret'])) update_option('web_invoice_alertpay_secret', $_POST['web_invoice_alertpay_secret']);
	if(isset($_POST['web_invoice_alertpay_test_mode'])) update_option('web_invoice_alertpay_test_mode', $_POST['web_invoice_alertpay_test_mode']);
	if(isset($_POST['web_invoice_alertpay_ip'])) update_option('web_invoice_alertpay_ip', $_POST['web_invoice_alertpay_ip']);
	
	// 2CO
	if(isset($_POST['web_invoice_2co_sid'])) update_option('web_invoice_2co_sid', $_POST['web_invoice_2co_sid']);
	if(isset($_POST['web_invoice_2co_secret_word'])) update_option('web_invoice_2co_secret_word', $_POST['web_invoice_2co_secret_word']);
	if(isset($_POST['web_invoice_2co_demo_mode'])) update_option('web_invoice_2co_demo_mode', $_POST['web_invoice_2co_demo_mode']);
	
	// Google Checkout
	if(isset($_POST['web_invoice_google_checkout_env'])) update_option('web_invoice_google_checkout_env', $_POST['web_invoice_google_checkout_env']);
	if(isset($_POST['web_invoice_google_checkout_merchant_id'])) update_option('web_invoice_google_checkout_merchant_id', $_POST['web_invoice_google_checkout_merchant_id']);
	if(isset($_POST['web_invoice_google_checkout_level2'])) update_option('web_invoice_google_checkout_level2', $_POST['web_invoice_google_checkout_level2']);
	if(isset($_POST['web_invoice_google_checkout_merchant_key'])) update_option('web_invoice_google_checkout_merchant_key', $_POST['web_invoice_google_checkout_merchant_key']);
	if(isset($_POST['web_invoice_google_checkout_tax_state'])) update_option('web_invoice_google_checkout_tax_state', $_POST['web_invoice_google_checkout_tax_state']);
	
	// Sage Pay
	if(isset($_POST['web_invoice_sagepay_env'])) update_option('web_invoice_sagepay_env', $_POST['web_invoice_sagepay_env']);
	if(isset($_POST['web_invoice_sagepay_vendor_name'])) update_option('web_invoice_sagepay_vendor_name', $_POST['web_invoice_sagepay_vendor_name']);
	if(isset($_POST['web_invoice_sagepay_vendor_key'])) update_option('web_invoice_sagepay_vendor_key', $_POST['web_invoice_sagepay_vendor_key']);
	if(isset($_POST['web_invoice_sagepay_shipping_details'])) update_option('web_invoice_sagepay_shipping_details', $_POST['web_invoice_sagepay_shipping_details']);
	
	do_action('web_invoice_process_settings');
}

function web_invoice_process_email_templates() {
	global $wpdb;

	// Save General Settings
	if(isset($_POST['web_invoice_email_send_invoice_subject'])) { update_option('web_invoice_email_send_invoice_subject', $_POST['web_invoice_email_send_invoice_subject']); }
	if(isset($_POST['web_invoice_email_send_invoice_content'])) update_option('web_invoice_email_send_invoice_content', $_POST['web_invoice_email_send_invoice_content']);
	if(isset($_POST['web_invoice_email_send_reminder_subject'])) update_option('web_invoice_email_send_reminder_subject', $_POST['web_invoice_email_send_reminder_subject']);
	if(isset($_POST['web_invoice_email_send_reminder_content'])) update_option('web_invoice_email_send_reminder_content', $_POST['web_invoice_email_send_reminder_content']);
	if(isset($_POST['web_invoice_email_send_receipt_subject'])) update_option('web_invoice_email_send_receipt_subject', $_POST['web_invoice_email_send_receipt_subject']);
	if(isset($_POST['web_invoice_email_send_receipt_content'])) update_option('web_invoice_email_send_receipt_content', $_POST['web_invoice_email_send_receipt_content']);

	// PDF
	if(isset($_POST['web_invoice_pdf_content'])) update_option('web_invoice_pdf_content', $_POST['web_invoice_pdf_content']);
	
	// HTML
	if(isset($_POST['web_invoice_html_content'])) update_option('web_invoice_html_content', $_POST['web_invoice_html_content']);
}

function web_invoice_is_not_merchant() {
	if(get_option('web_invoice_gateway_username') == '' || get_option('web_invoice_gateway_tran_key') == '') return true;
}

function web_invoice_determine_currency($invoice_id) {
	//in class
	if(web_invoice_meta($invoice_id,'web_invoice_currency_code') != '')
	{ $currency_code = web_invoice_meta($invoice_id,'web_invoice_currency_code'); }
	elseif(get_option('web_invoice_default_currency_code') != '')
	{ $currency_code = get_option('web_invoice_default_currency_code'); }
	else { $currency_code = "USD"; }
	return $currency_code;
}

function web_invoice_gc_serial_to_invoice($serial_number)
{
	global $wpdb;

	$serial_number = mysql_real_escape_string($serial_number);
	$invoice_id = $wpdb->get_var("SELECT invoice_id FROM `".Web_Invoice::tablename('meta')."` WHERE meta_key = 'gc_serial_number' AND meta_value = '$serial_number'");

	return $invoice_id;
}

function web_invoice_gc_name_to_invoice($name)
{
	global $wpdb;

	$invoice_id = preg_replace('/.*#([0-9]+).*/', '$1', $name);

	return $invoice_id;
}

function web_invoice_md5_to_invoice($md5) {
	global $wpdb, $_web_invoice_md5_to_invoice_cache;

	if (isset($_web_invoice_md5_to_invoice_cache[$md5]) && $_web_invoice_md5_to_invoice_cache[$md5]) {
		return $_web_invoice_md5_to_invoice_cache[$md5];
	}

	$md5_escaped = mysql_escape_string($md5);
	$all_invoices = $wpdb->get_col("SELECT invoice_num FROM ".Web_Invoice::tablename('main')." WHERE MD5(invoice_num) = '{$md5_escaped}'");
	foreach ($all_invoices as $value) {
		if(md5($value) == $md5) {
			$_web_invoice_md5_to_invoice_cache[$md5] = $value;
			return $_web_invoice_md5_to_invoice_cache[$md5];
		}
	}
}

function web_invoice_update_recurring_start_date($invoice_id, $date) {
	web_invoice_update_invoice_meta($invoice_id,'web_invoice_subscription_start_day', date('d', $date));
	web_invoice_update_invoice_meta($invoice_id,'web_invoice_subscription_start_year', date('Y', $date));
	web_invoice_update_invoice_meta($invoice_id,'web_invoice_subscription_start_month', date('m', $date));
}

function web_invoice_get_alertpay_api_url() {
	return get_permalink(get_option('web_invoice_web_invoice_page'));
}

function web_invoice_get_google_checkout_api_url() {
	return get_permalink(get_option('web_invoice_web_invoice_page'));
}

function web_invoice_get_payflow_silent_post_url() {
	return get_permalink(get_option('web_invoice_web_invoice_page'));
}

function web_invoice_clear_cache() {
	global $_web_invoice_clear_cache;
	
	$_web_invoice_clear_cache = true;
}

function web_invoice_get_all_payment_options() {
	return array(
		'2co' => array('text' => '2Checkout', 'secure' => false),
		'alertpay' => array('text' => 'AlertPay', 'secure' => false),
		'cc' => array('text' => 'Credit Card', 'secure' => true),
		'moneybookers' => array('text' => 'Moneybookers', 'secure' => false),
		'google_checkout' => array('text' => 'Google Checkout', 'secure' => false),
		'paypal' => array('text' => 'PayPal', 'secure' => false),
		'payflow' => array('text' => 'PayPal Payflow', 'secure' => false),
		'pfp' => array('text' => 'PayPal Payflow Pro', 'secure' => true),
		'other' => array('text' => 'Other/Bank details', 'secure' => false),
		'sagepay' => array('text' => 'Sage Pay', 'secure' => false),
	);
}

function web_invoice_xor_encryption($input_string, $key_phrase) {
 
    $key_phrase_length = strlen($key_phrase);
 
    for ($i = 0; $i < strlen($input_string); $i++){
        $rPos = $i % $key_phrase_length;
        $r = ord($input_string[$i]) ^ ord($key_phrase[$rPos]);
        $input_string[$i] = chr($r);
    }
 
    return $input_string;
}
 
function web_invoice_xor_encrypt($input_string, $key_phrase) {
    $input_string = web_invoice_xor_encryption($input_string, $key_phrase);
    $input_string = base64_encode($input_string);
    return $input_string;
}
 
function web_invoice_xor_decrypt($input_string, $key_phrase) {
    $input_string = base64_decode($input_string);
    $input_string = web_invoice_xor_encryption($input_string, $key_phrase);
    return $input_string;
}

function web_invoice_return_bytes($val) {
	$val = trim($val);
	$last = strtolower($val[strlen($val)-1]);
	switch($last) {
		case 'g':
			$val *= 1000;
		case 'm':
			$val *= 1000;
		case 'k':
			$val *= 1000;
	}
	return $val;
}

function web_invoice_return_bytes_nice($bytes) {
	$units = array('B', 'K', 'M', 'G');
  
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1000));
	$pow = min($pow, count($units) - 1);
  
	$bytes /= pow(1000, $pow);
  
	return round($bytes, 0) . $units[$pow]; 
}

function web_invoice_display_payment($currency, $amount) {
	return sprintf(web_invoice_currency_symbol_format($currency), web_invoice_currency_format($amount));
}

if (!function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir() {
		if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
		if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
		if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
		$tempfile=tempnam(uniqid(rand(),TRUE),'');
		if (file_exists($tempfile)) {
			unlink($tempfile);
			return realpath(dirname($tempfile));
		}
	}
}
