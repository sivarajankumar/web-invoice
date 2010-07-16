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

function web_invoice_default($message='')
{
	global $wpdb;
	//Make sure tables exist

	// The error takes precedence over others being that nothing can be done w/o tables
	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) { $warning_message = ""; }

	if(get_option("web_invoice_web_invoice_page") == '') { $warning_message .= __('Invoice page not selected. ', WEB_INVOICE_TRANS_DOMAIN); }
	if(get_option("web_invoice_payment_method") == '') { $warning_message .= __('Payment method not set. ', WEB_INVOICE_TRANS_DOMAIN); }
	if(get_option("web_invoice_payment_method") == '' || get_option("web_invoice_web_invoice_page") == '') {
		$warning_message .= __("Visit ", WEB_INVOICE_TRANS_DOMAIN)."<a href='admin.php?page=web_invoice_settings'>settings page</a>".__(" to configure.", WEB_INVOICE_TRANS_DOMAIN);
	}

	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('meta')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) {
		$warning_message = __("The plugin database tables are gone, deactivate and reactivate plugin to re-create them.", WEB_INVOICE_TRANS_DOMAIN);
	}

	if(isset($warning_message) && $warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";
	if(isset($message) && $message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";

	$all_invoices = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num != '' ORDER BY id ASC");

	?>
<form id="invoices-filter" action="" method="post">
<h2><?php _e('Invoice Overview', WEB_INVOICE_TRANS_DOMAIN); ?> <a class="button add-new-h2" href="admin.php?page=new_web_invoice"><?php _e('Add New', WEB_INVOICE_TRANS_DOMAIN); ?></a></h2>
<div class="tablenav clearfix">

<div class="alignleft"><select name="web_invoice_action">
	<option value="-1" selected="selected"><?php _e('-- Actions --', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="send_invoice"><?php _e('Send Invoice(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="send_reminder"><?php _e('Send Reminder(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="archive_invoice"><?php _e('Archive Invoice(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="unarchive_invoice"><?php _e('Un-Archive Invoice(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="mark_as_sent"><?php _e('Mark as Sent', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="mark_as_paid"><?php _e('Mark as Paid', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="delete_invoice"><?php _e('Delete', WEB_INVOICE_TRANS_DOMAIN); ?></option>
</select> <input type="submit" value="Apply"
	class="button-secondary action" /></div>

<div class="alignright">
<ul class="subsubsub" style="margin: 0;">
	<li><?php _e('Filter:', WEB_INVOICE_TRANS_DOMAIN); ?></li>
	<li><a href='#' class="" id=""><?php _e('All Invoices', WEB_INVOICE_TRANS_DOMAIN); ?></a>
	|</li>
	<li><a href='#' class="paid" id=""><?php _e('Paid', WEB_INVOICE_TRANS_DOMAIN); ?></a>
	|</li>
	<li><a href='#' class="sent" id=""><?php _e('Unpaid', WEB_INVOICE_TRANS_DOMAIN); ?></a>
	|</li>
	<li><?php _e('Custom: ', WEB_INVOICE_TRANS_DOMAIN); ?><input
		type="text" id="FilterTextBox" class="search-input"
		name="FilterTextBox" /></li>
</ul>
</div>
</div>
<br class="clear" />

<table class="widefat" id="invoice_sorter_table">
	<thead>
		<tr>
			<th class="check-column"><input type="checkbox" id="CheckAll" /></th>
			<th class="invoice_id_col"><?php _e('Invoice Id', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Subject', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Due date', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Amount', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Status', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('User', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?php

	$x_counter = 0;
	
	foreach ($all_invoices as $invoice) {
		$class_settings = "";
		
		// Stop if this is a recurring bill
		if(!web_invoice_meta($invoice->invoice_num,'web_invoice_recurring_billing')) {
			if (isset($_REQUEST['archived']) && $_REQUEST['archived'] != 'true' && web_invoice_meta($invoice->invoice_num,'archive_status') == 'archived') continue;
			
			$x_counter++;
			unset($class_settings);

			//Basic Settings
			$invoice_id = $invoice->invoice_num;
			$invoice_info = new Web_Invoice_GetInfo($invoice_id);
			$due_date = $invoice_info->display('due_date');
			
			$subject = $invoice->subject;
			$invoice_link = web_invoice_build_invoice_link($invoice_id);
			$magic_link = preg_replace('/invoice_id/', 'generate_from', $invoice_link);
			$user_id = $invoice->user_id;

			//Determine if unique/custom id used
			$custom_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id');
			$display_id = ($custom_id ? $custom_id : $invoice_id);

			// Determine Currency
			$currency_code = web_invoice_determine_currency($invoice_id);
			$show_money = sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($invoice->amount));

			// Determine What to Call Recipient
			$profileuser = get_userdata($user_id);
			$first_name = (isset($profileuser->first_name))?$profileuser->first_name:'';
			$last_name = (isset($profileuser->last_name))?$profileuser->last_name:'';
			$user_nicename = $profileuser->user_nicename;
			if(empty($first_name) || empty($last_name)) $call_me_this = $user_nicename; else $call_me_this = $first_name . " " . $last_name;

			$class_settings = "";
			
			// Color coding
			if(web_invoice_paid_status($invoice_id)) $class_settings .= " alternate ";
			if(web_invoice_meta($invoice_id,'archive_status') == 'archived')  $class_settings .= " web_invoice_archived ";

			// Days Since Sent
			if(web_invoice_paid_status($invoice_id)) {
				$days_since = "<span style='display:none;'>-1</span>".__(' Paid', WEB_INVOICE_TRANS_DOMAIN);  
			} else {
				if(web_invoice_meta($invoice_id,'sent_date')) {
					$date1 = web_invoice_meta($invoice_id,'sent_date');
					$date2 = date("Y-m-d", time());
					$difference = abs(strtotime($date2) - strtotime($date1));
					$days = round(((($difference/60)/60)/24), 0);
					if($days == 0) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Today. ', WEB_INVOICE_TRANS_DOMAIN); }
					elseif($days == 1) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Yesterday. ', WEB_INVOICE_TRANS_DOMAIN); }
					elseif($days > 1) { $days_since = "<span style='display:none;'>$days</span>".sprintf(__('Sent %s days ago. ', WEB_INVOICE_TRANS_DOMAIN),$days); }
				} else {
					$days_since ="<span style='display:none;'>999</span>".__('Not Sent', WEB_INVOICE_TRANS_DOMAIN);	}
				}

				$output_row  = "<tr class='$class_settings'>\n";
				$output_row .= "	<th class='check-column'><input type='checkbox' name='multiple_invoices[]' value='$invoice_id'/></th>\n";
				$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$display_id</a></td>\n";
				$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$subject</a></td>\n";
				$output_row .= "	<td>$due_date</td>\n";
				$output_row .= "	<td>$show_money</td>\n";
				$output_row .= "	<td>$days_since</td>\n";
				$output_row .= "	<td> <a href='user-edit.php?user_id=$user_id'>$call_me_this</a></td>\n";
				$output_row .= "	<td><a href='$invoice_link'>".__('View Web Invoice', WEB_INVOICE_TRANS_DOMAIN)."</a>";
				if (get_option('web_invoice_self_generate_from_template') == 'yes') {
				$output_row .= "		| <a href='$magic_link' title='".__('Copy this link and add to a post/page/widget to allow unprivileged users create invoices for self.', WEB_INVOICE_TRANS_DOMAIN)."'".
											">".__('Self service template', WEB_INVOICE_TRANS_DOMAIN)."</a>";
				}
				$output_row .= "	</td>\n";
				$output_row .= "</tr>";

				echo $output_row;
		} /* Recurring Billing Stop */
	}
	if($x_counter == 0) {
		// No result
		?>
		<tr>
			<td colspan="6" align="center">
			<div style="padding: 20px;"><?php _e('You have not created any invoices yet, ', WEB_INVOICE_TRANS_DOMAIN); ?><a
				href="admin.php?page=new_web_invoice"><?php _e('create one now.', WEB_INVOICE_TRANS_DOMAIN); ?></a></div>
			</td>
		</tr>
		<?php

	}
	?>
	</tbody>
</table>
	<?php if($wpdb->query("SELECT meta_value FROM `".Web_Invoice::tablename('meta')."` WHERE meta_value = 'archived'")) { ?><a
	href="admin.php?page=web-invoice/web-invoice.php&archived=true" class="<?php print ($_REQUEST['archived'] == 'true')?'expanded':'collapsed';?>" id="web_invoice_show_archived" ><?php _e('Show / Hide Archived', WEB_INVOICE_TRANS_DOMAIN); ?></a><?php }?>
</form>
	<?php

	// web_invoice_options_manageInvoice();
	if(web_invoice_is_not_merchant()) web_invoice_cc_setup(false);
}

function web_invoice_user_default($message='')
{
	global $wpdb, $current_user;
	//Make sure tables exist

	// The error takes precedence over others being that nothing can be done w/o tables
	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) { $warning_message = ""; }

	if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";

	$all_invoices = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num != '' AND user_id = {$current_user->ID} ORDER BY id ASC");

	?>
<form id="invoices-filter" action="" method="post">
<h2><?php _e('Invoices', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<div class="tablenav clearfix">

<div class="alignleft"></div>

<div class="alignright">
<ul class="subsubsub" style="margin: 0;">
	<li><?php _e('Filter:', WEB_INVOICE_TRANS_DOMAIN); ?></li>
	<li><a href='#' class="" id=""><?php _e('All Invoices', WEB_INVOICE_TRANS_DOMAIN); ?></a>
	|</li>
	<li><a href='#' class="paid" id=""><?php _e('Paid', WEB_INVOICE_TRANS_DOMAIN); ?></a>
	|</li>
	<li><a href='#' class="sent" id=""><?php _e('Unpaid', WEB_INVOICE_TRANS_DOMAIN); ?></a>
	|</li>
	<li><?php _e('Custom: ', WEB_INVOICE_TRANS_DOMAIN); ?><input
		type="text" id="FilterTextBox" class="search-input"
		name="FilterTextBox" /></li>
</ul>
</div>
</div>
<br class="clear" />

<table class="widefat" id="invoice_sorter_table">
	<thead>
		<tr>
			<th></th>
			<th class="invoice_id_col"><?php _e('Invoice Id', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Subject', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Due date', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Amount', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Status', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?php

	$x_counter = 0;
	foreach ($all_invoices as $invoice) {
		// Stop if this is a recurring bill
		//if(!web_invoice_meta($invoice->invoice_num,'web_invoice_recurring_billing')) {
			if ($_REQUEST['archived'] != 'true' && web_invoice_meta($invoice->invoice_num,'archive_status') == 'archived') continue;
			
			$x_counter++;
			unset($class_settings);

			//Basic Settings
			$invoice_id = $invoice->invoice_num;
			$invoice_info = new Web_Invoice_GetInfo($invoice_id);
			$due_date = $invoice_info->display('due_date');
			
			$subject = $invoice->subject;
			$invoice_link = web_invoice_build_invoice_link($invoice_id);
			$user_id = $invoice->user_id;

			//Determine if unique/custom id used
			$custom_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id');
			$display_id = ($custom_id ? $custom_id : $invoice_id);

			// Determine Currency
			$currency_code = web_invoice_determine_currency($invoice_id);
			$show_money = sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($invoice->amount));

			// Determine What to Call Recipient
			$profileuser = get_userdata($user_id);
			$first_name = $profileuser->first_name;
			$last_name = $profileuser->last_name;
			$user_nicename = $profileuser->user_nicename;
			if(empty($first_name) || empty($last_name)) $call_me_this = $user_nicename; else $call_me_this = $first_name . " " . $last_name;

			// Color coding
			if(web_invoice_paid_status($invoice_id)) $class_settings .= " alternate ";
			if(web_invoice_meta($invoice_id,'archive_status') == 'archived')  $class_settings .= " web_invoice_archived ";

			//Days since sent

			// Days Since Sent
			if(web_invoice_paid_status($invoice_id)) {
				$days_since = "<span style='display:none;'>-1</span>".__(' Paid', WEB_INVOICE_TRANS_DOMAIN); }
				else {
					if(web_invoice_meta($invoice_id,'sent_date')) {

						$date1 = web_invoice_meta($invoice_id,'sent_date');
						$date2 = date("Y-m-d", time());
						$difference = abs(strtotime($date2) - strtotime($date1));
						$days = round(((($difference/60)/60)/24), 0);
						if($days == 0) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Today. ', WEB_INVOICE_TRANS_DOMAIN); }
						elseif($days == 1) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Yesterday. ', WEB_INVOICE_TRANS_DOMAIN); }
						elseif($days > 1) { $days_since = "<span style='display:none;'>$days</span>".sprintf(__('Sent %s days ago. ', WEB_INVOICE_TRANS_DOMAIN),$days); }
					}
					else {
						$days_since ="<span style='display:none;'>999</span>".__('Not Sent', WEB_INVOICE_TRANS_DOMAIN);	}
				}
				
				if (web_invoice_meta($invoice->invoice_num,'web_invoice_recurring_billing')) {
					$subject .= ' (Recurring)';
				}


				$output_row  = "<tr class='$class_settings'>\n";
				$output_row .= "	<th class='check-column'><input type='checkbox' class='hidden-check' name='multiple_invoices[]' value='$invoice_id'/></th>\n";
				$output_row .= "	<td>$display_id</td>\n";
				$output_row .= "	<td>$subject</td>\n";
				$output_row .= "	<td>$due_date</td>\n";
				$output_row .= "	<td>$show_money</td>\n";
				$output_row .= "	<td>$days_since</td>\n";
				$output_row .= "	<td><a href='$invoice_link'>".__('View Web Invoice', WEB_INVOICE_TRANS_DOMAIN)."</a></td>\n";
				$output_row .= "</tr>";

				echo $output_row;
		//} /* Recurring Billing Stop */
	}
	if($x_counter == 0) {
		// No result
		?>
		<tr>
			<td colspan="6" align="center">
			<div style="padding: 20px;"><?php _e('You do not have any invoices yet', WEB_INVOICE_TRANS_DOMAIN); ?></div>
			</td>
		</tr>
		<?php

	}
	?>
	</tbody>
</table>
	<?php if($wpdb->query("SELECT meta_value FROM `".Web_Invoice::tablename('meta')."` WHERE meta_value = 'archived'")) { ?><a
	href="users.php?page=user_invoice_overview&archived=true" class="<?php print ($_REQUEST['archived'] == 'true')?'expanded':'collapsed';?>" id="web_invoice_show_archived"><?php _e('Show / Hide Archived', WEB_INVOICE_TRANS_DOMAIN); ?></a><?php }?>
</form>
	<?php

	// web_invoice_options_manageInvoice();
	if(web_invoice_is_not_merchant()) web_invoice_cc_setup(false);
}

function web_invoice_recurring_overview($message='')
{
	global $wpdb;
	// Make sure tables exist
	// The error takes precedence over others being that nothing can be done w/o tables
	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) { $warning_message = ""; }

	if(isset($warning_message) && $warning_message) echo "<div id='message' class='error' ><p>$warning_message</p></div>";
	if(isset($message) && $message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";

	$all_invoices = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num != '' ORDER BY id ASC");

	?>
<form id="invoices-filter" action="" method="post"><input type="hidden"
	name="web_invoice_recurring_billing" value="true" />
<h2><?php _e('Recurring Billing Overview', WEB_INVOICE_TRANS_DOMAIN); ?> <a class="button add-new-h2" href="admin.php?page=new_web_invoice"><?php _e('Add New', WEB_INVOICE_TRANS_DOMAIN); ?></a></h2>

	<?php if(web_invoice_is_not_merchant() && (get_option('web_invoice_moneybookers_merchant') == 'False')) { ?>
<div class="web_invoice_rounded_box">
<p><?php printf(__('You need a %4$s account with Merchant status, %7$s Business account, %5$s account, %6$s account or a credit card processing account to use recurring billing. You may get an ARB (Automated Recurring Billing) account from %1$s (800-546-1997), %2$s (888-845-9457) or %3$s (866-400-9706).', WEB_INVOICE_TRANS_DOMAIN), 
		'<a href="http://keti.ws/37281">MerchantPlus</a>', '<a href="http://keti.ws/37282">MerchantExpress.com</a>', 
		'<a href="http://keti.ws/36282">MerchantWarehouse</a>', 
		'<a href="http://keti.ws/27481" alt="moneybookers.com">Moneybookers</a>', 
		'<a href="https://www.paypal.com/lk/mrb/pal=TW8P6LGF47FM4">PayPal Business</a>',
		'<a href="https://www.paypal.com/lk/mrb/pal=TW8P6LGF47FM4">PayPal Payflow Pro</a>',
		'<a href="http://keti.ws/36283" alt="alertpay.com">AlertPay</a>'); ?></p>
<p><?php _e('Once you have an account, enter in your username and transaction key into the ', WEB_INVOICE_TRANS_DOMAIN); ?><a
	href="admin.php?page=web_invoice_settings"><?php _e('settings page', WEB_INVOICE_TRANS_DOMAIN); ?></a>.</p>
</div>
	<?php } ?>

<div class="tablenav clearfix">

<div class="alignleft"><select name="web_invoice_action">
	<option value="-1" selected="selected"><?php _e('-- Actions --', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="send_invoice"><?php _e('Send Invoice(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="send_reminder"><?php _e('Send Reminder(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="mark_as_paid"><?php _e('Mark as Paid', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="archive_invoice"><?php _e('Archive Invoice(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="unarchive_invoice"><?php _e('Un-Archive Invoice(s)', WEB_INVOICE_TRANS_DOMAIN); ?></option>
	<option value="delete_invoice"
		onClick="if(confirm('<?php _e('If you delete a recurring invoice, the subscription will be cancelled.', WEB_INVOICE_TRANS_DOMAIN); ?>')) {return true;} return false;"><?php _e('Delete', WEB_INVOICE_TRANS_DOMAIN); ?></option>
</select> <input type="submit"
	value="<?php _e('Apply', WEB_INVOICE_TRANS_DOMAIN); ?>"
	class="button-secondary action" /></div>

<div class="alignright">
<ul class="subsubsub" style="margin: 0;">
	<li><?php _e('Filter: ', WEB_INVOICE_TRANS_DOMAIN); ?><input
		type="text" id="FilterTextBox" class="search-input"
		name="FilterTextBox" /></li>
</ul>
</div>
</div>
<br class="clear" />



<table class="widefat" id="invoice_sorter_table">
	<thead>
		<tr>
			<th class="check-column"><input type="checkbox" id="CheckAll" /></th>
			<th class="invoice_id_col"><?php _e('Invoice Id', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Subject', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Due date', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Amount', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('Status', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th><?php _e('User', WEB_INVOICE_TRANS_DOMAIN); ?></th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
	<?php

	$web_invoice_payment_link = get_option("web_invoice_payment_link");
	if(!empty($web_invoice_payment_link)) { if(strpos('?',$web_invoice_payment_link)) { $web_invoice_payment_link = $web_invoice_payment_link . "&";} else {$web_invoice_payment_link = $web_invoice_payment_link . "?";} }

	$x_counter = 0;
	foreach ($all_invoices as $invoice) {
		if(web_invoice_meta($invoice->invoice_num,'web_invoice_recurring_billing')) {
			if ((!isset($_REQUEST['archived']) || $_REQUEST['archived'] != 'true') && web_invoice_meta($invoice->invoice_num,'archive_status') == 'archived') continue;
			
			$x_counter++;

			unset($class_settings);

			//Basic Settings
			$invoice_id = $invoice->invoice_num;
			$invoice_info = new Web_Invoice_GetInfo($invoice_id);
			$due_date = $invoice_info->display('due_date');

			if(web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id')) $custom_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id'); else $custom_id = $invoice_id;
						
			$subject = $invoice->subject;
			$invoice_link = web_invoice_build_invoice_link($invoice_id);
			$magic_link = preg_replace('/invoice_id/', 'generate_from', $invoice_link);
			$user_id = $invoice->user_id;
			// Determine Currency
			$currency_code = web_invoice_determine_currency($invoice_id);

			$show_money = sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($invoice->amount));

			// Determine What to Call Recipient
			$profileuser = get_userdata($user_id);
			$first_name = $profileuser->first_name;
			$last_name = $profileuser->last_name;
			$user_nicename = $profileuser->user_nicename;
			if(empty($first_name) || empty($last_name)) $call_me_this = $user_nicename; else $call_me_this = $first_name . " " . $last_name;

			$class_settings = "";
			
			// Color coding
			if(web_invoice_paid_status($invoice_id)) $class_settings .= " alternate ";
			if(web_invoice_meta($invoice_id,'archive_status') == 'archived')  $class_settings .= " web_invoice_archived ";

			//Days since sent

			// Days Since Sent
			if(web_invoice_paid_status($invoice_id) == 'cancelled') {
				$days_since = "<span style='display:none;'>-1</span>".__(' Cancelled', WEB_INVOICE_TRANS_DOMAIN); 
			} else if(web_invoice_paid_status($invoice_id)) {
				$days_since = "<span style='display:none;'>-2</span>".__(' Paid', WEB_INVOICE_TRANS_DOMAIN); 
			} else {
					if(web_invoice_meta($invoice_id,'sent_date')) {

						$date1 = web_invoice_meta($invoice_id,'sent_date');
						$date2 = date("Y-m-d", time());
						$difference = abs(strtotime($date2) - strtotime($date1));
						$days = round(((($difference/60)/60)/24), 0);
						if($days == 0) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Today. ', WEB_INVOICE_TRANS_DOMAIN); }
						elseif($days == 1) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Yesterday. ', WEB_INVOICE_TRANS_DOMAIN); }
						elseif($days > 1) { $days_since = "<span style='display:none;'>$days</span>".sprintf(__('Sent %s days ago. ', WEB_INVOICE_TRANS_DOMAIN), $days); }
					}
					else {
						$days_since ="<span style='display:none;'>999</span>".__('Not Sent', WEB_INVOICE_TRANS_DOMAIN);	}
				}

				if(web_invoice_recurring_started($invoice_id)) $days_since = "<span style='display:none;'>-1</span>".__('Active Recurring', WEB_INVOICE_TRANS_DOMAIN);

				$output_row  = "<tr class='$class_settings'>\n";
				$output_row .= "	<th class='check-column'><input type='checkbox' name='multiple_invoices[]' value='$invoice_id'/></th>\n";
				$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$custom_id</a></td>\n";
				$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$subject</a></td>\n";
				$output_row .= "	<td>$due_date</td>\n";
				$output_row .= "	<td>$show_money</td>\n";
				$output_row .= "	<td>$days_since</td>\n";
				$output_row .= "	<td> <a href='user-edit.php?user_id=$user_id'>$call_me_this</a></td>\n";
				$output_row .= "	<td><a href='$invoice_link'>".__('View Web Invoice', WEB_INVOICE_TRANS_DOMAIN)."</a>";
				if (get_option('web_invoice_self_generate_from_template') == 'yes') {
				$output_row .= "		| <a href='$magic_link' title='".__('Copy this link and add to a post/page/widget to allow unprivileged users create invoices for self.', WEB_INVOICE_TRANS_DOMAIN)."'".
											">".__('Self service template', WEB_INVOICE_TRANS_DOMAIN)."</a>";
				}
				$output_row .= "	</td>\n";
				$output_row .= "</tr>";

				echo $output_row;
		} /* Recurring Billing */
	}
	if($x_counter == 0) {
		// No result
		?>
		<tr>
			<td colspan="6" align="center">
			<div style="padding: 20px;"><?php _e('You have not created any recurring invoices yet, ', WEB_INVOICE_TRANS_DOMAIN); ?><a
				href="admin.php?page=new_web_invoice"><?php _e('create one now.', WEB_INVOICE_TRANS_DOMAIN); ?></a></div>
			</td>
		</tr>
		<?php

	}
	?>
	</tbody>
</table>
	<?php if($wpdb->query(
				"SELECT meta_value FROM `".Web_Invoice::tablename('meta')."` WHERE meta_value = 'archived' ".
				"AND invoice_id IN (SELECT invoice_id FROM `".Web_Invoice::tablename('meta')."` WHERE meta_key = 'web_invoice_recurring_billing')")) { ?><a
	href="admin.php?page=web_invoice_recurring_billing&archived=true" class="<?php print ($_REQUEST['archived'] == 'true')?'expanded':'collapsed';?>" id="web_invoice_show_archived"><?php _e('Show / Hide Archived', WEB_INVOICE_TRANS_DOMAIN); ?></a><?php }?>
</form>
	<?php
	// web_invoice_options_manageInvoice();
	if(web_invoice_is_not_merchant()) web_invoice_cc_setup(false);
}

function web_invoice_saved_preview($invoice_id)
{
	?>
<h2><?php _e('Save and Preview', WEB_INVOICE_TRANS_DOMAIN); ?></h2>

<p><?php _e('This is what your invoice will appear like in the email message. The recipient will see the itemized list after following their link to your website.', WEB_INVOICE_TRANS_DOMAIN); ?></p>

<div id="invoice_preview"><?php echo web_invoice_show_invoice($invoice_id); ?>
</div>

<div class="invoice_horizontal_buttons">
<form method="post" action="admin.php?page=web-invoice/web-invoice.php">
<input type="hidden" value="<?php echo $invoice_id; ?>"
	name="invoice_id" /> <input type="hidden" value="doInvoice"
	name="web_invoice_action" /> <input type="submit"
	value="Continue Editing" name="doInvoice" class="button-secondary" /></form>

<form method="post" action="admin.php?page=web-invoice/web-invoice.php">
<input type="hidden" value="<?php echo $invoice_id; ?>"
	name="invoice_id" /> <input type="hidden" value="send_now"
	name="web_invoice_action"> <input type="submit" value="Email To Client"
	class="button-secondary" /></form>

<form method="post" action="admin.php?page=web-invoice/web-invoice.php">
<input type="hidden" value="<?php echo $invoice_id; ?>"
	name="invoice_id" /> <input type="hidden" value="save_not_send"
	name="web_invoice_action" /> <input type="submit"
	value="Save for Later" name="save" class="button-secondary" /></form>

</div>
	<?php _e('Do not use the back button or you could have duplicates.', WEB_INVOICE_TRANS_DOMAIN);
}


function web_invoice_options_manageInvoice($invoice_id = '',$message='')
{
	global $wpdb;
	
	//Load Defaults
	$currency = get_option("web_invoice_default_currency_code");

	if(!empty($_REQUEST['user_id'])) $user_id = $_REQUEST['user_id'];

	// Need to unset these values
	if(empty($_POST['copy_from_template'])) {unset($_POST['copy_from_template']);}
	if($invoice_id == '') {unset($invoice_id);}

	$web_invoice_tax_names = unserialize(get_option('web_invoice_tax_name'));
	
	// New Invoice From Template
	if(isset($_POST['copy_from_template']) && $_POST['user_id']) {
		$template_invoice_id = $_POST['copy_from_template'];
		$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$template_invoice_id."'");
		$user_id = $_REQUEST['user_id'];
		$amount = $invoice_info->amount;
		$subject = $invoice_info->subject;
		$description = $invoice_info->description;
		$itemized = $invoice_info->itemized;
		$profileuser = get_user_to_edit($_POST['user_id']);
		$itemized_array = unserialize(urldecode($itemized));
		$web_invoice_tax = unserialize(web_invoice_meta($template_invoice_id,'tax_value'));
		$web_invoice_currency_code = web_invoice_meta($template_invoice_id,'web_invoice_currency_code');
		$web_invoice_due_date_day = web_invoice_meta($template_invoice_id,'web_invoice_due_date_day');
		$web_invoice_due_date_month = web_invoice_meta($template_invoice_id,'web_invoice_due_date_month');
		$web_invoice_due_date_year = web_invoice_meta($template_invoice_id,'web_invoice_due_date_year');

		$web_invoice_subscription_name = web_invoice_meta($template_invoice_id,'web_invoice_subscription_name');
		$web_invoice_subscription_unit = web_invoice_meta($template_invoice_id,'web_invoice_subscription_unit');
		$web_invoice_subscription_length = web_invoice_meta($template_invoice_id,'web_invoice_subscription_length');
		$web_invoice_subscription_start_month = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_month');
		$web_invoice_subscription_start_day = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_day');
		$web_invoice_subscription_start_year = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_year');
		$web_invoice_subscription_total_occurances = web_invoice_meta($template_invoice_id,'web_invoice_subscription_total_occurances');

		$web_invoice_recurring_billing = web_invoice_meta($template_invoice_id,'web_invoice_recurring_billing');
	}

	// Invoice Exists, we are modifying it
	if(isset($invoice_id)) {
		$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
		$user_id = $invoice_info->user_id;
		$amount = $invoice_info->amount;
		$subject = $invoice_info->subject;
		$description = $invoice_info->description;
		$itemized = $invoice_info->itemized;
		$profileuser = get_userdata($invoice_info->user_id);
		$itemized_array = unserialize(urldecode($itemized));
		$web_invoice_tax = unserialize(web_invoice_meta($invoice_id,'tax_value'));
		$web_invoice_custom_invoice_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id');
		$web_invoice_due_date_day = web_invoice_meta($invoice_id,'web_invoice_due_date_day');
		$web_invoice_due_date_month = web_invoice_meta($invoice_id,'web_invoice_due_date_month');
		$web_invoice_due_date_year = web_invoice_meta($invoice_id,'web_invoice_due_date_year');
		$web_invoice_currency_code = web_invoice_meta($invoice_id,'web_invoice_currency_code');
		$web_invoice_recurring_billing = web_invoice_meta($invoice_id,'web_invoice_recurring_billing');

		$web_invoice_subscription_name = web_invoice_meta($invoice_id,'web_invoice_subscription_name');
		$web_invoice_subscription_unit = web_invoice_meta($invoice_id,'web_invoice_subscription_unit');
		$web_invoice_subscription_length = web_invoice_meta($invoice_id,'web_invoice_subscription_length');
		$web_invoice_subscription_start_month = web_invoice_meta($invoice_id,'web_invoice_subscription_start_month');
		$web_invoice_subscription_start_day = web_invoice_meta($invoice_id,'web_invoice_subscription_start_day');
		$web_invoice_subscription_start_year = web_invoice_meta($invoice_id,'web_invoice_subscription_start_year');
		$web_invoice_subscription_total_occurances = web_invoice_meta($invoice_id,'web_invoice_subscription_total_occurances');
	}

	//Whether recurring bill will start when client pays, or a date is specified
	if($web_invoice_subscription_start_month && $web_invoice_subscription_start_year && $web_invoice_subscription_start_day) $recurring_auto_start = true; else $recurring_auto_start = false;

	// Brand New Invoice
	if(!isset($invoice_id) && isset($_REQUEST['user_id'])) {
		$profileuser = get_user_to_edit($_REQUEST['user_id']);
	}

	// Load Userdata
	$user_email = $profileuser->user_email;
	$first_name = $profileuser->first_name;
	$last_name = $profileuser->last_name;
	$company_name = $profileuser->company_name;
	$tax_id = $profileuser->tax_id;
	$streetaddress = $profileuser->streetaddress;
	$city = $profileuser->city;
	$state = $profileuser->state;
	$zip = $profileuser->zip;
	$country = $profileuser->country;

	//Load Invoice Specific Settings, and override default
	if(!empty($web_invoice_currency_code)) $currency = $web_invoice_currency_code;

	// Crreae two blank arrays for itemized list if none is set
	if (!is_array($itemized_array) || count($itemized_array) == 0) {
		$itemized_array[1] = "";
		$itemized_array[2] = "";
	}

	if(get_option("web_invoice_web_invoice_page") == '') { $warning_message .= __('Invoice page not selected. ', WEB_INVOICE_TRANS_DOMAIN); }
	if(get_option("web_invoice_payment_method") == '') { $warning_message .= __('Payment method not set. ', WEB_INVOICE_TRANS_DOMAIN); }
	if(get_option("web_invoice_payment_method") == '' || get_option("web_invoice_web_invoice_page") == '') {
		$warning_message .= __("Visit ", WEB_INVOICE_TRANS_DOMAIN)."<a href='admin.php?page=web_invoice_settings'>settings page</a>".__(" to configure.", WEB_INVOICE_TRANS_DOMAIN);
	}

	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('meta')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) {
		$warning_message = __("The plugin database tables are gone, deactivate and reactivate plugin to re-create them.", WEB_INVOICE_TRANS_DOMAIN);
	}

	if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";

	?>
	<?php if(!isset($invoice_id)) { ?>
<h2><?php _e('New Web Invoice', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
	<?php  web_invoice_draw_user_selection_form($user_id); 
		  } else {
			$_SESSION['last_new_invoice'] = false;
	      } ?>
	<?php if(isset($user_id) && isset($invoice_id)) { ?>
<h2><?php _e('Manage Invoice', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
	<?php } ?>

	<?php if(isset($invoice_id) && web_invoice_paid_status($invoice_id) || web_invoice_recurring_started($invoice_id) || web_invoice_query_log($invoice_id, 'subscription_error')) { ?>
<div class="updated web_invoice_status"><?php if(web_invoice_paid_status($invoice_id)) { ?>
<h2><?php _e('Invoice Paid', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
	<?php foreach(web_invoice_query_log($invoice_id, 'paid') as $info) {
		echo sprintf(__('%s on ', WEB_INVOICE_TRANS_DOMAIN), $info->value) . "<span class='web_invoice_tamp_stamp'>" . $info->time_stamp . "</span><br />";
	} ?> <?php } ?> <?php if(web_invoice_recurring_started($invoice_id)) { ?>
<h2><?php _e('Recurring Billing Initiated', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
	<?php foreach(web_invoice_query_log($invoice_id, 'subscription') as $info) {
		echo sprintf(__('%s on ', WEB_INVOICE_TRANS_DOMAIN), $info->value) . $info->time_stamp . "<br />";
	} } ?> <?php
	$subscription_errors = web_invoice_query_log($invoice_id, 'subscription_error');
	if($subscription_errors) { ?>
<h2><?php _e('Recurring Billing Problems', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<ol>
<?php
foreach($subscription_errors as $info) {
	echo "<li>" . sprintf(__('%s on ', WEB_INVOICE_TRANS_DOMAIN), $info->value). $info->time_stamp . "</li>";
} ?>
</ol>
<?php	}
	}  ?></div>

	<?php if(isset($user_id)) { ?>
<div id="poststuff" class="metabox-holder">
<form id="new_web_invoice_form"
	action="admin.php?page=new_web_invoice&amp;web_invoice_action=save_and_preview"
	method="POST"><input type="hidden" name="user_id"
	value="<?php echo $user_id; ?>" /> <input type="hidden"
	name="invoice_id"
	value="<?php if(isset($invoice_id)) { echo $invoice_id; } else { echo rand(10000000, 90000000);}  ?>" />
<input type="hidden" name="amount" id="total_amount"
	value="<?php echo $amount; ?>" />
<div class="postbox" id="web_invoice_client_info_div">
<h3><label for="link_name"><?php _e('Client Information', WEB_INVOICE_TRANS_DOMAIN); ?></label></h3>
<div class="inside">
<table class="form-table" id="add_new_web_invoice">
<?php
if(get_option('web_invoice_business_name') == '') 		echo "<tr><th colspan=\"2\">".__("Your business name isn't set, go to Settings page to set it.", WEB_INVOICE_TRANS_DOMAIN)."</a></th></tr>\n"; 	?>
	<tr>
		<th><?php _e("Email Address", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><?php echo $user_email; ?> <a class="web_invoice_click_me"
			href="user-edit.php?user_id=<?php echo $user_id; ?>#billing_info"><?php _e('Go to User Profile', WEB_INVOICE_TRANS_DOMAIN); ?></a></td>

	</tr>
	<tr style="height: 90px;">
		<th><?php _e("Billing Information", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td>
		<div id="web_invoice_edit_user_from_invoice"><span
			class="web_invoice_make_editable<?php if(!$first_name) echo " web_invoice_unset"; ?>"
			id="web_invoice_first_name"><?php if($first_name) echo $first_name; else echo __("Set First Name", WEB_INVOICE_TRANS_DOMAIN); ?></span>
		<span
			class="web_invoice_make_editable<?php if(!$last_name) echo " web_invoice_unset"; ?>"
			id="web_invoice_last_name"><?php if($last_name) echo $last_name; else echo __("Set Last Name", WEB_INVOICE_TRANS_DOMAIN); ?></span><br />
		<span
			class="web_invoice_make_editable<?php if(!$company_name) echo " web_invoice_unset"; ?>"
			id="web_invoice_company_name"><?php if($company_name) echo $company_name; else echo __("Set Company", WEB_INVOICE_TRANS_DOMAIN); ?></span><br/>
		<span
			class="web_invoice_make_editable<?php if(!$streetaddress) echo " web_invoice_unset"; ?>"
			id="web_invoice_streetaddress"><?php if($streetaddress) echo $streetaddress; else echo __("Set Street Address", WEB_INVOICE_TRANS_DOMAIN); ?></span><br />
		<span
			class="web_invoice_make_editable<?php if(!$city) echo " web_invoice_unset"; ?>"
			id="web_invoice_city"><?php if($city) echo $city; else echo __("Set City", WEB_INVOICE_TRANS_DOMAIN); ?></span><br/>
		<span
			class="web_invoice_make_editable<?php if(!$state) echo " web_invoice_unset"; ?>"
			id="web_invoice_state"><?php if($state) echo $state; else echo __("Set State", WEB_INVOICE_TRANS_DOMAIN); ?></span>
		<span
			class="web_invoice_make_editable<?php if(!$zip) echo " web_invoice_unset"; ?>"
			id="web_invoice_zip"><?php if($zip) echo $zip; else echo __("Set Zip Code", WEB_INVOICE_TRANS_DOMAIN); ?></span><br/>
		<span
			class="web_invoice_make_editable<?php if(!$country) echo " web_invoice_unset"; ?>"
			id="web_invoice_country"><?php if($country) echo $country; else echo __("Set Country", WEB_INVOICE_TRANS_DOMAIN); ?></span><br/>
		</div>
		</td>
	</tr>
	<tr>
		<th><?php _e("Tax ID", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td>
		<div id="web_invoice_edit_tax_form_invoice">
			<span
				class="web_invoice_make_editable<?php if(!$tax_id) echo " web_invoice_unset"; ?>"
				id="web_invoice_tax_id"><?php if($tax_id) echo $tax_id; else echo __("Set Tax ID", WEB_INVOICE_TRANS_DOMAIN); ?></span>
		</div>
		</td>
	</tr>
</table>
</div>

<div class="postbox" id="web_invoice_client_info_div">
<h3><label for="link_name"><?php _e("Recurring Billing", WEB_INVOICE_TRANS_DOMAIN) ?></label></h3>

<div id="web_invoice_enable_recurring_billing"
	class="web_invoice_click_me"
	<?php if($web_invoice_recurring_billing) { ?> style="display: none;"
	<?php } ?>><?php _e("Create a recurring billing schedule for this invoice.", WEB_INVOICE_TRANS_DOMAIN) ?>
</div>

<div class="web_invoice_enable_recurring_billing"
<?php if(!$web_invoice_recurring_billing) { ?> style="display: none;"
<?php } ?>>

<table class="form-table" id="add_new_web_invoice">
	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("A name to identify this subscription by in addition to the invoice id. (ex: 'standard hosting')", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Subscription Name", WEB_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><?php echo web_invoice_draw_inputfield('web_invoice_subscription_name',$web_invoice_subscription_name); ?></td>
	</tr>

	<tr>
		<th><?php _e("Start Date", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><span style="<?php if($recurring_auto_start) { ?>display:none;<?php } ?>" class="web_invoice_timestamp"><?php _e("Start automatically as soon as the customer enters their billing information. ", WEB_INVOICE_TRANS_DOMAIN) ?><span
			class="web_invoice_click_me"
			onclick="jQuery('.web_invoice_timestamp').toggle();"><?php _e("Specify Start Date", WEB_INVOICE_TRANS_DOMAIN) ?></span></span>
		<div style="<?php if(!$recurring_auto_start) { ?>display:none;<?php } ?>" class="web_invoice_timestamp">
		<?php echo web_invoice_draw_select('web_invoice_subscription_start_month', web_invoice_month_array(), $web_invoice_subscription_start_month); ?>
		<?php echo web_invoice_draw_inputfield('web_invoice_subscription_start_day', $web_invoice_subscription_start_day, ' size="2" maxlength="2" autocomplete="off" '); ?>,
		<?php echo web_invoice_draw_inputfield('web_invoice_subscription_start_year', $web_invoice_subscription_start_year, ' size="4" maxlength="4" autocomplete="off" '); ?>
		<span onclick="web_invoice_subscription_start_time(7);"
			class="web_invoice_click_me"><?php _e("In One Week", WEB_INVOICE_TRANS_DOMAIN) ?></span>
		| <span onclick="web_invoice_subscription_start_time(30);"
			class="web_invoice_click_me"><?php _e("In 30 Days", WEB_INVOICE_TRANS_DOMAIN) ?></span>
		| <span
			onclick="jQuery('.web_invoice_timestamp').toggle();web_invoice_subscription_start_time('clear');"
			class="web_invoice_click_me"><?php _e("Start automatically", WEB_INVOICE_TRANS_DOMAIN) ?></span>
		</div>
		</td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("This will be the number of times the client will be billed. (ex: 12)", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Bill Every", WEB_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><?php echo web_invoice_draw_inputfield('web_invoice_subscription_length', $web_invoice_subscription_length,' size="3" maxlength="3" autocomplete="off" '); ?>
		<?php echo web_invoice_draw_select('web_invoice_subscription_unit', array("years" => __("year(s)", WEB_INVOICE_TRANS_DOMAIN), "months" => __("month(s)", WEB_INVOICE_TRANS_DOMAIN), "days"=> __("days", WEB_INVOICE_TRANS_DOMAIN)), $web_invoice_subscription_unit); ?></td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Keep it under the maximum of 9999.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Total Billing Cycles", WEB_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><?php echo web_invoice_draw_inputfield('web_invoice_subscription_total_occurances', $web_invoice_subscription_total_occurances,' size="4" maxlength="4" autocomplete="off" '); ?></td>
	</tr>

	<tr>
		<th></th>
		<td><?php _e("All <b>recurring billing</b> fields must be filled out to activate recurring billing. ", WEB_INVOICE_TRANS_DOMAIN) ?>
		<?php if (web_invoice_meta($invoice_id, 'pfp_status') == 'active') { ?>
			<a href="admin.php?page=new_web_invoice&web_invoice_action=doPausePfp&invoice_id=<?php print $invoice_id; ?>" class="web_invoice_click_me"><?php _e("Pause Recurring Billing", WEB_INVOICE_TRANS_DOMAIN) ?></a> |
		<?php } else if (web_invoice_meta($invoice_id, 'pfp_status') == 'paused') { ?>
			<a href="admin.php?page=new_web_invoice&web_invoice_action=doRestartRecurringPfp&invoice_id=<?php print $invoice_id; ?>" class="web_invoice_click_me"><?php _e("Restart Recurring Billing", WEB_INVOICE_TRANS_DOMAIN) ?></a> |
		<?php } ?>
			<span onclick="web_invoice_cancel_recurring()" class="web_invoice_click_me"><?php _e("Cancel Recurring Billing", WEB_INVOICE_TRANS_DOMAIN) ?></span></td>
	</tr>
</table>
</div>
</div>

<div id="web_invoice_main_info" class="metabox-holder">
<div id="submitdiv" class="postbox" style="">
<h3 class="hndle"><span><?php _e("Invoice Details", WEB_INVOICE_TRANS_DOMAIN) ?></span></h3>
<div class="inside">
<table class="form-table">
	<tr class="invoice_main">
		<th><?php _e("Subject", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id="invoice_subject" class="subject" name='subject'
			value='<?php echo $subject; ?>' /></td>
	</tr>

	<tr class="invoice_main">
		<th><?php _e("Description / PO", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><textarea class="invoice_description_box" name='description'><?php echo $description; ?></textarea></td>
	</tr>

	<tr class="invoice_main">
		<th><?php _e("Itemized List", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td>
		<table id="invoice_list" class="itemized_list">
			<tr>
				<th class="id"><?php _e("ID", WEB_INVOICE_TRANS_DOMAIN) ?></th>
				<th class="name"><?php _e("Name", WEB_INVOICE_TRANS_DOMAIN) ?></th>
				<th class="description"><?php _e("Description", WEB_INVOICE_TRANS_DOMAIN) ?></th>
				<th class="quantity"><?php _e("Quantity", WEB_INVOICE_TRANS_DOMAIN) ?></th>
				<th class="price"><?php _e("Unit Price", WEB_INVOICE_TRANS_DOMAIN) ?></th>
				<th class="item_total"><?php _e("Total", WEB_INVOICE_TRANS_DOMAIN) ?></th>
			</tr>

			<?php
			$counter = 1;
			foreach($itemized_array as $itemized_item){	 ?>

			<tr valign="top">
				<td valign="top" class="id"><?php echo $counter; ?></td>
				<td valign="top" class="name"><input class="item_name"
					name="itemized_list[<?php echo $counter; ?>][name]"
					value="<?php echo stripslashes($itemized_item[name]); ?>" /></td>
				<td valign="top" class="description"><textarea style="height: 25px;"
					name="itemized_list[<?php echo $counter; ?>][description]"
					class="item_description autogrow"><?php echo stripslashes($itemized_item[description]); ?></textarea></td>
				<td valign="top" class="quantity"><input
					value="<?php echo stripslashes($itemized_item[quantity]); ?>"
					name="itemized_list[<?php echo $counter; ?>][quantity]"
					id="qty_item_<?php echo $counter; ?>" class="item_quantity noautocomplete" /></td>
				<td valign="top" class="price"><input
					value="<?php echo stripslashes($itemized_item[price]); ?>"
					name="itemized_list[<?php echo $counter; ?>][price]"
					id="price_item_<?php echo $counter; ?>" class="item_price noautocomplete" /></td>
				<td valign="top" class="item_total"
					id="total_item_<?php echo $counter; ?>"></td>
			</tr>
			<?php $counter++; } ?>
		</table>
		</td>
	</tr>

	<tr class="invoice_main">
		<th style='vertical-align: bottom; text-align: right;'>
		<p><a href="#" id="add_itemized_item"><?php _e("Add Another Item", WEB_INVOICE_TRANS_DOMAIN) ?></a><br />
		<span class='web_invoice_light_text'></span></p>
		</th>
		<td>
		<table class="itemized_list">

			<tr>
				<td align="right"><?php _e("Invoice Total", WEB_INVOICE_TRANS_DOMAIN) ?>:</td>
				<td class="item_total"><span id='amount'></span></td>
			</tr>

			<tr>
				<td align="right"><?php _e("Recurring Invoice Total", WEB_INVOICE_TRANS_DOMAIN) ?>:</td>
				<td class="item_total"><span id='recurring_total'></span></td>
			</tr>

		</table>
		</td>
	</tr>
</table>
</div>
</div>
</div>

<div id="submitdiv" class="postbox" style="">
<h3 class="hndle"><span><?php _e("Publish", WEB_INVOICE_TRANS_DOMAIN) ?></span></h3>
<div class="inside">
<div id="minor-publishing">

<div id="misc-publishing-actions">
<table class="form-table">
	<tr class="invoice_main">
		<th><?php _e("Invoice ID ", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td style="font-size: 1.1em; padding-top: 7px;"><input
			class="web_invoice_custom_invoice_id<?php if(empty($web_invoice_custom_invoice_id)) { echo " web_invoice_hidden"; } ?>"
			name="web_invoice_custom_invoice_id"
			value="<?php echo $web_invoice_custom_invoice_id;?>" /> <?php if(isset($invoice_id)) { echo $invoice_id; } else { echo rand(10000000, 90000000);}  ?>
		<a
			class="web_invoice_custom_invoice_id web_invoice_click_me <?php if(!empty($web_invoice_custom_invoice_id)) { echo " web_invoice_hidden"; } ?>"
			href="#"><?php _e("Custom Invoice ID", WEB_INVOICE_TRANS_DOMAIN) ?></a>

		</td>
	</tr>
	
	<?php if (is_array($web_invoice_tax) || empty($web_invoice_tax)) { ?>
		<?php for ($_txc=0; $_txc<get_option('web_invoice_tax_count'); $_txc++) { ?>
	<tr class="invoice_main">
		<th><span
			class="web_invoice_make_editable<?php if(!isset($web_invoice_tax_names[$_txc])) echo " web_invoice_unset"; ?>"
			id="web_invoice_tax_name_<?php print $_txc; ?>"><?php print isset($web_invoice_tax_names[$_txc])?$web_invoice_tax_names[$_txc]:sprintf(__("Set Tax %s Name", WEB_INVOICE_TRANS_DOMAIN), $_txc+1); ?></span></th>
		<td style="font-size: 1.1em; padding-top: 7px;"><input
			style="width: 35px;" name="web_invoice_tax[]" id="web_invoice_tax_<?php print $_txc; ?>"
			value="<?php echo $web_invoice_tax[$_txc]; ?>" class="noautocomplete web_invoice_tax" />%</td>
	</tr>
		<?php } ?>
	<?php } else { ?>
	<tr class="invoice_main">
		<th><?php _e("Tax", WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td style="font-size: 1.1em; padding-top: 7px;"><input
			style="width: 35px;" name="web_invoice_tax" id="web_invoice_tax"
			value="<?php echo $web_invoice_tax; ?>" class="noautocomplete web_invoice_tax" />%</td>
	</tr>
	<?php } ?>

	<tr class="">
		<th><?php _e("Currency", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select name="web_invoice_currency_code">
		<?php foreach(web_invoice_currency_array() as $value=>$currency_x) {
			echo "<option value='$value'"; if($currency == $value) echo " SELECTED"; echo ">$value - $currency_x</option>\n";
		}
		?>
		</select></td>
	</tr>

	<tr class="">
		<th><?php _e("Due Date", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td>
		<div id="timestampdiv" style="display: block;"><?php echo web_invoice_draw_select('web_invoice_due_date_month', web_invoice_month_array(), $web_invoice_due_date_month, 'mm'); ?>
		<input type="text" id="jj" name="web_invoice_due_date_day"
			value="<?php echo $web_invoice_due_date_day; ?>" size="2"
			maxlength="2" class="noautocomplete" />, <input type="text" id="aa"
			name="web_invoice_due_date_year"
			value="<?php echo $web_invoice_due_date_year; ?>" size="4"
			maxlength="5" class="noautocomplete" /> <span
			onclick="web_invoice_add_time(7);" class="web_invoice_click_me"><?php _e("In One Week", WEB_INVOICE_TRANS_DOMAIN) ?></span>
		| <span onclick="web_invoice_add_time(30);"
			class="web_invoice_click_me"><?php _e("In 30 Days", WEB_INVOICE_TRANS_DOMAIN) ?></span>
		| <span onclick="web_invoice_add_time('clear');"
			class="web_invoice_click_me"><?php _e("Clear", WEB_INVOICE_TRANS_DOMAIN) ?></span>
		</div>
		</td>
	</tr>

</table>
</div>
<div class="clear"></div>
</div>

<div id="major-publishing-actions">

<div id="publishing-action"><input type="submit" name="save"
	class="button-primary" value="Save and Preview" /></div>
<div class="clear"></div>
</div>

</div>
</div>

</div>
</form>
</div>
		<?php if(web_invoice_get_invoice_status($invoice_id,'100')) { ?>
<div class="web_invoice_status">
<h2><?php _e("This Invoice's History ", WEB_INVOICE_TRANS_DOMAIN) ?>(<a
	href="admin.php?page=new_web_invoice&invoice_id=<?php echo $invoice_id; ?>&web_invoice_action=clear_log"><?php _e("Clear Log", WEB_INVOICE_TRANS_DOMAIN) ?></a>)</h2>
<ul id="invoice_history_log">
<?php echo web_invoice_get_invoice_status($invoice_id,'100'); ?>
</ul>
</div>
<?php } else { ?> <?php }?> <br class="cb" />

<?php } ?> <?php
}

function web_invoice_show_email_templates()
{
	global $wpdb;

	?>
<h2><?php _e("E-mail templates", WEB_INVOICE_TRANS_DOMAIN) ?></h2>
<form method="POST"><iframe
	src="https://secure.mohanjith.com/wp/web-invoice.php"
	style="float: right; width: 187px; height: 220px;"></iframe>
<table class="form-table" id="settings_page_table" style="clear: none;">
	<tr>
		<th><?php _e("Invoice e-mail", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td></td>
	</tr>
	<tr>
		<th><?php _e("Subject", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><input size="60" type="text"
			name="web_invoice_email_send_invoice_subject"
			value="<?php echo get_option('web_invoice_email_send_invoice_subject'); ?>" /></td>
	</tr>
	<tr>
		<th><?php _e("Content", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><textarea name="web_invoice_email_send_invoice_content" cols="60"
			rows="15"><?php echo get_option('web_invoice_email_send_invoice_content'); ?></textarea></td>
	</tr>

	<tr>
		<th><?php _e("Invoice PDF", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td></td>
	</tr>
	<tr>
		<th><?php _e("Content", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><textarea name="web_invoice_pdf_content" cols="60"
			rows="15"><?php echo stripslashes(get_option('web_invoice_pdf_content')); ?></textarea></td>
	</tr>
	
	<tr>
		<th><?php _e("Reminder e-mail", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td></td>
	</tr>
	<tr>
		<th><?php _e("Subject", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><input size="60" type="text"
			name="web_invoice_email_send_reminder_subject"
			value="<?php echo get_option('web_invoice_email_send_reminder_subject'); ?>" /></td>
	</tr>
	<tr>
		<th><?php _e("Content", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><textarea name="web_invoice_email_send_reminder_content" cols="60"
			rows="15"><?php echo get_option('web_invoice_email_send_reminder_content'); ?></textarea></td>
	</tr>

	<tr>
		<th><?php _e("Receipt e-mail", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td></td>
	</tr>
	<tr>
		<th><?php _e("Subject", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><input size="60" type="text"
			name="web_invoice_email_send_receipt_subject"
			value="<?php echo get_option('web_invoice_email_send_receipt_subject'); ?>" /></td>
	</tr>
	<tr>
		<th><?php _e("Content", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><textarea name="web_invoice_email_send_receipt_content" cols="60"
			rows="15"><?php echo get_option('web_invoice_email_send_receipt_content'); ?></textarea></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit"
			value="<?php _e('Update', WEB_INVOICE_TRANS_DOMAIN); ?>"
			class="button" /></td>
	</tr>
</table>

</form>
</div>
	<?php
}

function web_invoice_show_settings()
{
	global $wpdb;

	if(isset($_POST['web_invoice_billing_meta'])) {
		$web_invoice_billing_meta = explode('
	',$_POST['web_invoice_billing_meta']);
		$web_invoice_billing_meta = web_invoice_fix_billing_meta_array($web_invoice_billing_meta);
		update_option('web_invoice_billing_meta', urlencode(serialize($web_invoice_billing_meta)));
	}

	if(get_option('web_invoice_billing_meta') != '') $web_invoice_billing_meta = unserialize(urldecode(get_option('web_invoice_billing_meta')));

	if(	!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('meta')."';") ||
	!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") ||
	!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';") ||
	!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('payment')."';") ||
	!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('payment_meta')."';")) {
		$warning_message = "The plugin database tables are gone, deactivate and reactivate plugin to re-create them.";
	}

	if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";
	?>
<h2><?php _e("Invoice Settings", WEB_INVOICE_TRANS_DOMAIN) ?></h2>
<form method="POST"><iframe
	src="https://secure.mohanjith.com/wp/web-invoice.php"
	style="float: right; width: 187px; height: 220px;"></iframe>
<table class="form-table" id="settings_page_table" style="clear: none;">

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Select the page where your invoices will be displayed. Clients must follow their secured link, simply opening the page will not show any invoices.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Page to Display Invoices", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name='web_invoice_web_invoice_page'>
			<option></option>
			<?php $list_pages = $wpdb->get_results("SELECT ID, post_title, post_name, guid FROM ". $wpdb->prefix ."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title");
			$web_invoice_web_invoice_page = get_option('web_invoice_web_invoice_page');
			foreach ($list_pages as $page)
			{
				echo "<option  style='padding-right: 10px;'";
				if(isset($web_invoice_web_invoice_page) && $web_invoice_web_invoice_page == $page->ID) echo " SELECTED ";
				echo " value=\"".$page->ID."\">". $page->post_title . "</option>\n";
			}
			?></select></td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("If your website has an SSL certificate and you want to use it, the link to the invoice will be formatted for https.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Protocol to Use for Invoice URLs", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_protocol">
			<option></option>
			<option style="padding-right: 10px;" value="https"
			<?php if(get_option('web_invoice_protocol') == 'https') echo 'selected="yes"';?>>https</option>
			<option style="padding-right: 10px;" value="http"
			<?php if(get_option('web_invoice_protocol') == 'http') echo 'selected="yes"';?>>http</option>
		</select></td>
	</tr>
	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("If enforced, WordPress will automatically reload the invoice page into HTTPS mode even if the user attemps to open it in non-secure mode.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Enforce HTTPS", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_force_https">
			<option></option>
			<option value="true" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_force_https') == 'true') echo 'selected="yes"';?>><?php _e("Yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="false" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_force_https') == 'false') echo 'selected="yes"';?>><?php _e("No", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select> <a href="http://mohanjith.com/ssl-certificates.html"
			class="web_invoice_click_me"><?php _e("Do you need an SSL Certificate?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>

	<tr>
		<th width="200"><?php _e("Business Name:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input name="web_invoice_business_name" type="text"
			class="input_field"
			value="<?php echo stripslashes(get_option('web_invoice_business_name')); ?>" />
		</td>
	</tr>
	<tr>
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e("This will display on the invoice page when printed for clients' records.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Business Address", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><textarea name="web_invoice_business_address"><?php echo stripslashes(get_option('web_invoice_business_address')); ?></textarea>
		</td>
	</tr>

	<tr>
		<th width="200"><?php _e("Business Phone", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input name="web_invoice_business_phone" type="text"
			class="input_field"
			value="<?php echo stripslashes(get_option('web_invoice_business_phone')); ?>" />
		</td>
	</tr>
	
	<tr>
		<th width="200"><?php _e("Business Tax ID", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input name="web_invoice_business_tax_id" type="text"
			class="input_field"
			value="<?php echo stripslashes(get_option('web_invoice_business_tax_id')); ?>" />
		</td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Address used to send out e-mail to client with web invoice link.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Return eMail Address", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><input name="web_invoice_email_address" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_email_address')); ?>" />
		</td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("An email will be sent automatically to client thanking them for their payment.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Send Payment Confirmation", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_send_thank_you_email">
			<option></option>
			<option style="padding-right: 10px;" value="yes"
			<?php if(get_option('web_invoice_send_thank_you_email') == 'yes') echo 'selected="yes"';?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option style="padding-right: 10px;"
			<?php if(get_option('web_invoice_send_thank_you_email') == 'no') echo 'selected="yes"';?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Send a copy of email sent to client thanking them to you.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("CC Payment Confirmation", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_cc_thank_you_email">
			<option></option>
			<option style="padding-right: 10px;" value="yes"
			<?php if(get_option('web_invoice_cc_thank_you_email') == 'yes') echo 'selected="yes"';?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option style="padding-right: 10px;" value="no"
			<?php if(get_option('web_invoice_cc_thank_you_email') == 'no') echo 'selected="yes"';?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	
	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Redirect to new invoice page after adding user.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Redirect after adding user", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_redirect_after_user_add">
			<option></option>
			<option style="padding-right: 10px;" value="yes"
			<?php if(get_option('web_invoice_redirect_after_user_add') == 'yes') echo 'selected="yes"';?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option style="padding-right: 10px;" value="no"
			<?php if(get_option('web_invoice_redirect_after_user_add') == 'no') echo 'selected="yes"';?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>

	<tr>
		<th><?php _e("User Level to Manage web-invoice", WEB_INVOICE_TRANS_DOMAIN) ?>:</th>
		<td><select name="web_invoice_user_level[]" id="web_invoice_user_level" size="3" multiple="multiple" >
		<?php
			foreach (get_editable_roles() as $role => $details) {
				$name = translate_user_role($details['name'] );
		?>
			<option value="<?php print $role; ?>" style="padding-right: 10px;"
			<?php if(in_array($role, get_option('web_invoice_user_level', array('administrator')))) echo 'selected="yes"';?>><?php _e($name, WEB_INVOICE_TRANS_DOMAIN) ?></option>
		<?php 
			}
		?>
		</select>
		</td>
	</tr>
	
	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Allow users not allowed to manage web-invoice to create invoices for self from templates with no access to the admin dashboard. Anonymous users are not allowed to create any invoices.", WEB_INVOICE_TRANS_DOMAIN) ?>"
			><?php _e("Allow users to create invoices for self", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td>
			<select name="web_invoice_self_generate_from_template" id="web_invoice_self_generate_from_template" >
				<option></option>
				<option style="padding-right: 10px;" value="yes"
				<?php if(get_option('web_invoice_self_generate_from_template') == 'yes') echo 'selected="yes"';?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
				<option style="padding-right: 10px;" value="no"
				<?php if(get_option('web_invoice_self_generate_from_template') == 'no') echo 'selected="yes"';?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			</select>
		</td>
	</tr>
	
	<tr>
		<th width="200"><?php _e("Number of taxes", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input name="web_invoice_tax_count" type="text"
			class="input_field"
			value="<?php echo stripslashes(get_option('web_invoice_tax_count')); ?>" size="3" />
		</td>
	</tr>

	<tr>
		<td colspan="2">
		<h2><?php _e("Invoice Page Display Settings:", WEB_INVOICE_TRANS_DOMAIN) ?></h2>
		</td>
	</tr>
	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Disable this if you want to use your own stylesheet.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php
			_e("Use CSS", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_use_css">
			<option></option>
			<option style="padding-right: 10px;" value="yes"
			<?php if(get_option('web_invoice_use_css') == 'yes') echo 'selected="yes"';?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option style="padding-right: 10px;" value="no"
			<?php if(get_option('web_invoice_use_css') == 'no') echo 'selected="yes"';?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Show your business name and address on invoice.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Show Address on Invoice", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_show_business_address">
			<option></option>
			<option style="padding-right: 10px;" value="yes"
			<?php if(get_option('web_invoice_show_business_address') == 'yes') echo 'selected="yes"';?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option style="padding-right: 10px;" value="no"
			<?php if(get_option('web_invoice_show_business_address') == 'no') echo 'selected="yes"';?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	
	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Show billing name and address on invoice.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Show Billing Address on Invoice", WEB_INVOICE_TRANS_DOMAIN) ?></a>:</th>
		<td><select name="web_invoice_show_billing_address">
			<option></option>
			<option style="padding-right: 10px;" value="yes"
			<?php if(get_option('web_invoice_show_billing_address') == 'yes') echo 'selected="yes"';?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option style="padding-right: 10px;" value="no"
			<?php if(get_option('web_invoice_show_billing_address') == 'no') echo 'selected="yes"';?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>

	<tr>
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e("Show quantity breakdowns in the itemized list on the front-end.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Quantities on Front End", WEB_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><select name="web_invoice_show_quantities">
			<option value="Show"
			<?php if(get_option('web_invoice_show_quantities') == 'Show') echo 'selected="yes"';?>><?php _e("Show", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="Hide"
			<?php if(get_option('web_invoice_show_quantities') == 'Hide') echo 'selected="yes"';?>><?php _e("Hide", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>

	<tr>
		<td colspan="2">
		<h2><?php _e("Payment Settings", WEB_INVOICE_TRANS_DOMAIN) ?></h2>
		</td>
	</tr>

	<tr>
		<th><?php _e("Default Currency:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><?php echo web_invoice_draw_select('web_invoice_default_currency_code',web_invoice_currency_array(),get_option('web_invoice_default_currency_code')); ?>
		</td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Special proxy must be used to process credit card transactions on GoDaddy servers.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Using Godaddy Hosting", WEB_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><?php echo web_invoice_draw_select('web_invoice_using_godaddy',array("yes" => __("Yes", WEB_INVOICE_TRANS_DOMAIN),"no" => __("No", WEB_INVOICE_TRANS_DOMAIN)),get_option('web_invoice_using_godaddy')); ?>
		</td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Some payment processors may not be available unless protocol is https and enforce https is selected.", WEB_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Payment Method:", WEB_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><select id="web_invoice_payment_method"
			name="web_invoice_payment_method[]" multiple="multiple" size="4">
			<option value="alertpay" style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'alertpay')) echo 'selected="yes"';?>><?php _e("AlertPay", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="cc" <?php print (!stristr(get_option('web_invoice_protocol'), 'https') || !stristr(get_option('web_invoice_force_https'), 'true'))?'disabled="disabled"':''; ?> style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'cc')) echo 'selected="yes"';?>><?php _e("Credit Card", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="moneybookers" style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'moneybookers')) echo 'selected="yes"';?>><?php _e("Moneybookers", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="google_checkout" style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'google_checkout')) echo 'selected="yes"';?>><?php _e("Google Checkout", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="paypal" style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'paypal')) echo 'selected="yes"';?>><?php _e("PayPal", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="payflow" style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'payflow')) echo 'selected="yes"';?>><?php _e("PayPal Payflow", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="pfp" <?php print (!stristr(get_option('web_invoice_protocol'), 'https') || !stristr(get_option('web_invoice_force_https'), 'true'))?'disabled="disabled"':''; ?> style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'pfp')) echo 'selected="yes"';?>><?php _e("PayPal Payflow Pro", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="other" style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'other')) echo 'selected="yes"';?>><?php _e("Other/Bank details", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="sagepay" style="padding-right: 10px;"
			<?php if(stristr(get_option('web_invoice_payment_method'), 'sagepay')) echo 'selected="yes"';?>><?php _e("Sage Pay", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	
	<tr class="alertpay_info">
		<th><?php _e("Your AlertPay username:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_alertpay_address'
			name="web_invoice_alertpay_address" class="search-input input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_alertpay_address')); ?>" />
		<a id="web_invoice_alertpay_register_link" href="http://keti.ws/36283"
			class="web_invoice_click_me"><?php _e("Do you need an AlertPay account?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>
	<tr class="alertpay_info">
		<th><?php _e("Enable AlertPay IPN:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_alertpay_merchant'
			name="web_invoice_alertpay_merchant">
			<option value="True"
			<?php echo (get_option('web_invoice_alertpay_merchant')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_alertpay_merchant')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select> <span class="web_invoice_alertpay_url web_invoice_info"><?php _e("Your alert URL is", WEB_INVOICE_TRANS_DOMAIN) ?>
		<a
			title="<?php _e("Copy this link", WEB_INVOICE_TRANS_DOMAIN) ?>"
			href="<?php echo web_invoice_get_alertpay_api_url(); ?>"><?php echo web_invoice_get_alertpay_api_url(); ?></a>.<br />
			<?php _e("Please note that AlertPay has issues with some SSL certificates. (Your milage may vary).", WEB_INVOICE_TRANS_DOMAIN) ?>
		</span></td>
	</tr>
	<tr class="alertpay_info alertpay_info_merchant">
		<th><?php _e("AlertPay IPN security code:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_alertpay_secret'
			name="web_invoice_alertpay_secret" class="search-input input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_alertpay_secret')); ?>" /></td>
	</tr>
	<tr class="alertpay_info alertpay_info_merchant">
		<th><?php _e("Test / Live Mode:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select name="web_invoice_alertpay_test_mode">
			<option value="TRUE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_alertpay_test_mode') == 'TRUE') echo 'selected="yes"';?>><?php _e("Test - Do Not Process Transactions", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="FALSE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_alertpay_test_mode') == 'FALSE') echo 'selected="yes"';?>><?php _e("Live - Process Transactions", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	<tr class="alertpay_info alertpay_info_merchant">
		<th><?php _e("AlertPay IPN IP:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_alertpay_ip' name="web_invoice_alertpay_ip"
			class="search-input input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_alertpay_ip')); ?>" /></td>
	</tr>

	<tr class="moneybookers_info">
		<th width="200"><?php _e("Moneybookers Username:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_moneybookers_address'
			name="web_invoice_moneybookers_address" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_moneybookers_address')); ?>" />
		<a id="web_invoice_moneybookers_register_link"
			href="http://keti.ws/27481"
			class="web_invoice_click_me"><?php _e("Do you need a Moneybookers account?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>
	
	<tr class="moneybookers_info">
		<th width="200"><?php _e("Moneybookers Username for recurring payments (optional):", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_moneybookers_recurring_address'
			name="web_invoice_moneybookers_recurring_address" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_moneybookers_recurring_address')); ?>" />
		</td>
	</tr>

	<tr class="moneybookers_info">
		<th width="200"><?php _e("Enable Moneybookers payment notifications:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_moneybookers_merchant'
			name="web_invoice_moneybookers_merchant">
			<option value="True"
			<?php echo (get_option('web_invoice_moneybookers_merchant')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_moneybookers_merchant')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	<tr class="moneybookers_info moneybookers_info_merchant">
		<th><?php _e("Moneybookers payment notification secret:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_moneybookers_secret'
			name="web_invoice_moneybookers_secret"
			class="search-input input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_moneybookers_secret')); ?>" /></td>
	</tr>
	<tr class="moneybookers_info moneybookers_info_merchant">
		<th><?php _e("Moneybookers payment notification IP:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_moneybookers_ip'
			name="web_invoice_moneybookers_ip" class="search-input input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_moneybookers_ip')); ?>" /></td>
	</tr>

	<tr class="google_checkout_info">
		<th width="200"><?php _e("Google Checkout Merchant Id:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_google_checkout_merchant_id'
			name="web_invoice_google_checkout_merchant_id" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_google_checkout_merchant_id')); ?>" />
		<a id="web_invoice_google_checkout_register_link" href="http://keti.ws/60282"
			class="web_invoice_click_me"><?php _e("Do you need a Google Checkout account?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>
	<tr class="google_checkout_info">
		<th><?php _e('Sandbox / Live Mode:', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><select name="web_invoice_google_checkout_env">
			<option value="sandbox" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_google_checkout_env') == 'sandbox') echo 'selected="yes"';?>><?php _e('Sandbox - Do Not Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="live" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_google_checkout_env') == 'live') echo 'selected="yes"';?>><?php _e('Live - Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
		</select></td>
	</tr>
	<tr class="google_checkout_info">
		<th width="200"><?php _e("Enable Google Checkout Level 2 integration:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_google_checkout_level2'
			name="web_invoice_google_checkout_level2">
			<option value="True"
			<?php echo (get_option('web_invoice_google_checkout_level2')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_google_checkout_level2')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select>
		<span class="web_invoice_google_checkout_url web_invoice_info"><?php _e("HTML API callback URL is", WEB_INVOICE_TRANS_DOMAIN) ?>
		<a
			title="<?php _e("Copy this link", WEB_INVOICE_TRANS_DOMAIN) ?>"
			href="<?php echo web_invoice_get_google_checkout_api_url(); ?>"><?php echo web_invoice_get_google_checkout_api_url(); ?></a></span>
			</td>
	</tr>
	<tr class="google_checkout_info google_checkout_info_merchant">
		<th><?php _e("Google Checkout merchant key:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_google_checkout_merchant_key'
			name="web_invoice_google_checkout_merchant_key"
			class="search-input input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_google_checkout_merchant_key')); ?>" /></td>
	</tr>
	<tr class="google_checkout_info google_checkout_info_merchant">
		<th><?php _e("Google Checkout tax country/state:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_google_checkout_tax_state' 
			name="web_invoice_google_checkout_tax_state" class="search-input input_field" >
				<option value="" ></option>
				<?php print  "<option value='UK'";
						if("UK" == get_option('web_invoice_google_checkout_tax_state')) print " selected";
						print ">United Kingdom</option>"; ?>
				<optgroup label="United States">
				<?php 
				foreach(web_invoice_state_array() as $_state_code => $_state_name) {
						print  "<option value='$_state_code'";
						if($_state_code == get_option('web_invoice_google_checkout_tax_state')) print " selected";
						print ">$_state_name</option>";
				}
				?>
				</optgroup>
			</select></td>
	</tr>
	
	<tr class="paypal_info">
		<th width="200"><?php _e("PayPal Username:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_paypal_address'
			name="web_invoice_paypal_address" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_paypal_address')); ?>" />
			<a id="web_invoice_paypal_register_link" href="http://keti.ws/87281"
				class="web_invoice_click_me"><?php _e("Do you need a PayPal account?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>
	<tr class="paypal_info">
		<th width="200"><?php _e("Just PayPal button (No form):", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_paypal_only_button'
			name="web_invoice_paypal_only_button">
			<option value="True"
			<?php echo (get_option('web_invoice_paypal_only_button')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_paypal_only_button')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	<tr class="paypal_info">
		<th width="200"><?php _e("PayPal Sandbox:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_paypal_sandbox'
			name="web_invoice_paypal_sandbox">
			<option value="True"
			<?php echo (get_option('web_invoice_paypal_sandbox')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_paypal_sandbox')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	<tr class="payflow_info">
		<th width="200"><?php _e("PayPal Payflow Username:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_payflow_login'
			name="web_invoice_payflow_login" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_payflow_login')); ?>" />
			<a id="web_invoice_payflow_register_link" href="http://keti.ws/87281"
				class="web_invoice_click_me"><?php _e("Do you need a PayPal account?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>
	<tr class="payflow_info">
		<th width="200"><?php _e("PayPal Payflow Partner name:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_payflow_partner'
			name="web_invoice_payflow_partner" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_payflow_partner')); ?>" />
		</td>
	</tr>
	<tr class="payflow_info">
		<th width="200"><?php _e("Just PayPal Payflow button (No form):", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_payflow_only_button'
			name="web_invoice_payflow_only_button">
			<option value="True"
			<?php echo (get_option('web_invoice_payflow_only_button')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_payflow_only_button')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	<tr class="payflow_info payflow_shipping">
		<th width="200"><?php _e("Display shipping details form:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_payflow_shipping_details'
			name="web_invoice_payflow_shipping_details">
			<option value="True"
			<?php echo (get_option('web_invoice_payflow_shipping_details')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_payflow_shipping_details')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	<tr class="payflow_info">
		<th width="200"><?php _e("Enable PayPal Payflow silent post integration:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_payflow_silent_post'
			name="web_invoice_payflow_silent_post">
			<option value="True"
			<?php echo (get_option('web_invoice_payflow_silent_post')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_payflow_silent_post')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select>
		<span class="web_invoice_payflow_silent_post_url web_invoice_info"><?php _e("Silent post URL is", WEB_INVOICE_TRANS_DOMAIN) ?>
		<a
			title="<?php _e("Copy this link", WEB_INVOICE_TRANS_DOMAIN) ?>"
			href="<?php echo web_invoice_get_payflow_silent_post_url(); ?>"><?php echo web_invoice_get_payflow_silent_post_url(); ?></a></span>
			</td>
	</tr>
	
	<tr class="pfp_info">
		<th width="200"><?php _e("PayPal Payflow Pro Partner name:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_partner'
			name="web_invoice_pfp_partner" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_partner')); ?>" />
			<a id="web_invoice_pfp_register_link" href="http://keti.ws/87281"
				class="web_invoice_click_me"><?php _e("Do you need a PayPal account?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>
	<tr class="pfp_info">
		<th><?php _e('Sandbox / Live Mode:', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><select name="web_invoice_pfp_env">
			<option value="sandbox" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_pfp_env') == 'sandbox') echo 'selected="yes"';?>><?php _e('Sandbox - Do Not Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="live" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_pfp_env') == 'live') echo 'selected="yes"';?>><?php _e('Live - Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
		</select></td>
	</tr>
	<tr class="pfp_info">
		<th width="200"><?php _e("PayPal Payflow Pro authentication mode:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id='web_invoice_pfp_authentication'
			name="web_invoice_pfp_authentication">
			<option value="3token"
			<?php echo (get_option('web_invoice_pfp_authentication')=='3token')?'selected="selected"':''; ?>><?php _e("3TOKEN", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="unipay"
			<?php echo (get_option('web_invoice_pfp_authentication')=='unipay')?'selected="selected"':''; ?>><?php _e("UNIPAY", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="wpppe"
			<?php echo (get_option('web_invoice_pfp_authentication')=='wpppe')?'selected="selected"':''; ?>><?php _e("Website Payments Pro", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	<tr class="pfp_info 3token">
		<th width="200"><?php _e("PayPal Payflow Pro Username:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_username'
			name="web_invoice_pfp_username" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_username')); ?>" />
		</td>
	</tr>
	<tr class="pfp_info 3token">
		<th width="200"><?php _e("PayPal Payflow Pro Password:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_password'
			name="web_invoice_pfp_password" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_password')); ?>" />
		</td>
	</tr>
	<tr class="pfp_info 3token">
		<th width="200"><?php _e("PayPal Payflow Pro Signature:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_signature'
			name="web_invoice_pfp_signature" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_signature')); ?>" />
		</td>
	</tr>
	<!-- Website Payments Pro -->
	<tr class="pfp_info wpppe">
		<th width="200"><?php _e("Website Payments Pro Vendor:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_wpppe_vendor'
			name="web_invoice_pfp_wpppe_vendor" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_wpppe_vendor')); ?>" />
		</td>
	</tr>
	<tr class="pfp_info wpppe">
		<th width="200"><?php _e("Website Payments Pro Username:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_wpppe_username'
			name="web_invoice_pfp_wpppe_username" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_wpppe_username')); ?>" />
		</td>
	</tr>
	<tr class="pfp_info wpppe">
		<th width="200"><?php _e("Website Payments Pro Password:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_wpppe_password'
			name="web_invoice_pfp_wpppe_password" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_wpppe_password')); ?>" />
		</td>
	</tr>
	<tr class="pfp_info unipay">
		<th width="200"><?php _e("PayPal Payflow Pro Third party e-mail address:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_pfp_3rdparty_email'
			name="web_invoice_pfp_3rdparty_email" class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_pfp_3rdparty_email')); ?>" />
		</td>
	</tr>
	<tr class="pfp_info">
		<th width="200"><?php _e("Display shipping details form:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id="web_invoice_pfp_shipping_details"
			name="web_invoice_pfp_shipping_details">
			<option value="True"
			<?php echo (get_option('web_invoice_pfp_shipping_details')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_pfp_shipping_details')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	
	<tr class="sagepay_info">
		<th width="200"><?php _e("Sage Pay vendor login name:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><input id='web_invoice_sagepay_vendor_name'
			name="web_invoice_sagepay_vendor_name" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_sagepay_vendor_name')); ?>" />
		<a id="web_invoice_sagepay_register_link" href="http://keti.ws/85282"
			class="web_invoice_click_me"><?php _e("Do you need a Sage Pay account?", WEB_INVOICE_TRANS_DOMAIN) ?></a>
		</td>
	</tr>
	<tr class="sagepay_info">
		<th><a class="web_invoice_tooltip"
			title="<?php _e("Sage Pay encryption password is different from your Sage Pay vendor password", WEB_INVOICE_TRANS_DOMAIN) ?>"
			><?php _e("Sage Pay encryption password:", WEB_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><input id='web_invoice_sagepay_vendor_key'
			name="web_invoice_sagepay_vendor_key"
			class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_sagepay_vendor_key')); ?>" /></td>
	</tr>
	<tr class="sagepay_info">
		<th><?php _e('Sandbox / Live Mode:', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><select name="web_invoice_sagepay_env">
			<option value="simulator" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_sagepay_env') == 'simulator') echo 'selected="yes"';?>><?php _e('Simulator - Do Not Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="test" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_sagepay_env') == 'test') echo 'selected="yes"';?>><?php _e('Sandbox - Do Not Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="live" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_sagepay_env') == 'live') echo 'selected="yes"';?>><?php _e('Live - Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
		</select></td>
	</tr>
	<tr class="sagepay_info">
		<th width="200"><?php _e("Display shipping details form:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><select id="web_invoice_sagepay_shipping_details"
			name="web_invoice_sagepay_shipping_details">
			<option value="True"
			<?php echo (get_option('web_invoice_sagepay_shipping_details')=='True')?'selected="selected"':''; ?>><?php _e("yes", WEB_INVOICE_TRANS_DOMAIN) ?></option>
			<option value="False"
			<?php echo (get_option('web_invoice_sagepay_shipping_details')=='False')?'selected="selected"':''; ?>><?php _e("no", WEB_INVOICE_TRANS_DOMAIN) ?></option>
		</select></td>
	</tr>
	
	<tr class="other_info">
		<th width="200"><?php _e("Other/Bank details:", WEB_INVOICE_TRANS_DOMAIN) ?></th>
		<td><textarea id='web_invoice_other_details'
			name="web_invoice_other_details"><?php echo get_option('web_invoice_other_details'); ?></textarea></td>
	</tr>

	<tr>
		<th colspan="2"><?php web_invoice_cc_setup(false); ?></th>
	</tr>
	
	<tr class="gateway_info">
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e('Your credit card processor will provide you with a gateway username.', WEB_INVOICE_TRANS_DOMAIN); ?>"><?php _e('Gateway Username', WEB_INVOICE_TRANS_DOMAIN); ?></a></th>
		<td><input name="web_invoice_gateway_username"
			class="input_field noautocomplete" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_username')); ?>" />
		</td>
	</tr>

	<tr class="gateway_info">
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e("You will be able to generate this in your credit card processor's control panel.", WEB_INVOICE_TRANS_DOMAIN); ?>"><?php _e('Gateway Transaction Key', WEB_INVOICE_TRANS_DOMAIN); ?></a></th>
		<td><input name="web_invoice_gateway_tran_key"
			class="input_field noautocomplete" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_tran_key')); ?>" />
		</td>
	</tr>


	<tr class="gateway_info">
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e('This is the URL provided to you by your credit card processing company.', WEB_INVOICE_TRANS_DOMAIN); ?>"><?php _e('Gateway URL', WEB_INVOICE_TRANS_DOMAIN); ?></a></th>
		<td><input name="web_invoice_gateway_url" id="web_invoice_gateway_url"
			class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_url')); ?>" />
		<br />
		<span class="web_invoice_click_me"
			onclick="jQuery('#web_invoice_gateway_url').val('https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi');"><?php _e('MerchantPlus', WEB_INVOICE_TRANS_DOMAIN); ?></span>
		| <span class="web_invoice_click_me"
			onclick="jQuery('#web_invoice_gateway_url').val('https://secure.authorize.net/gateway/transact.dll');"><?php _e('Authorize.Net', WEB_INVOICE_TRANS_DOMAIN); ?></span>
		| <span class="web_invoice_click_me"
			onclick="jQuery('#web_invoice_gateway_url').val('https://test.authorize.net/gateway/transact.dll');"><?php _e('Authorize.Net Developer', WEB_INVOICE_TRANS_DOMAIN); ?></span>
		</td>
	</tr>

	<tr class="gateway_info">
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e('Recurring billing gateway URL is most likely different from the Gateway URL, and will almost always be with Authorize.net. Be advised - test credit card numbers will be declined even when in test mode.', WEB_INVOICE_TRANS_DOMAIN); ?>"><?php _e('Recurring Billing Gateway URL', WEB_INVOICE_TRANS_DOMAIN); ?></a></th>
		<td><input name="web_invoice_recurring_gateway_url"
			id="web_invoice_recurring_gateway_url" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_recurring_gateway_url')); ?>" />
		<br />
		<span class="web_invoice_click_me"
			onclick="jQuery('#web_invoice_recurring_gateway_url').val('https://api.authorize.net/xml/v1/request.api');"><?php _e('Authorize.net ARB', WEB_INVOICE_TRANS_DOMAIN); ?></span>
		| <span class="web_invoice_click_me"
			onclick="jQuery('#web_invoice_recurring_gateway_url').val('https://apitest.authorize.net/xml/v1/request.api');"><?php _e('Authorize.Net ARB Testing', WEB_INVOICE_TRANS_DOMAIN); ?></span>
		</td>
	</tr>

	<tr class="gateway_info">
		<th><?php _e('Test / Live Mode:', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><select name="web_invoice_gateway_test_mode">
			<option value="TRUE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_gateway_test_mode') == 'TRUE') echo 'selected="yes"';?>><?php _e('Test - Do Not Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="FALSE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_gateway_test_mode') == 'FALSE') echo 'selected="yes"';?>><?php _e('Live - Process Transactions', WEB_INVOICE_TRANS_DOMAIN); ?></option>
		</select></td>
	</tr>

	<tr class="gateway_info">
		<td colspan="2">
		<h2><?php _e('Advanced Gateway Settings', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
		</td>
	</tr>

	<tr class="gateway_info">
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e('Get this from your credit card processor. If the transactions are not going through, this character is most likely wrong.', WEB_INVOICE_TRANS_DOMAIN); ?>"><?php _e('Delimiter Character', WEB_INVOICE_TRANS_DOMAIN); ?></a></th>
		<td><input name="web_invoice_gateway_delim_char" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_delim_char')); ?>" />
		</td>
	</tr>

	<tr class="gateway_info">
		<th width="200"><a class="web_invoice_tooltip"
			title="<?php _e('Authorize.net default is blank. Otherwise, get this from your credit card processor. If the transactions are going through, but getting strange responses, this character is most likely wrong.', WEB_INVOICE_TRANS_DOMAIN); ?>"><?php _e('Encapsulation Character', WEB_INVOICE_TRANS_DOMAIN); ?></a></th>
		<td><input name="web_invoice_gateway_encap_char" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_encap_char')); ?>" />
		</td>
	</tr>

	<tr class="gateway_info">
		<th width="200"><?php _e('Merchant Email', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><input name="web_invoice_gateway_merchant_email"
			class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_merchant_email')); ?>" />
		</td>
	</tr>

	<tr class="gateway_info">
		<th><?php _e('Email Customer (on success):', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><select name="web_invoice_gateway_email_customer">
			<option value="TRUE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_gateway_email_customer') == 'TRUE') echo 'selected="yes"';?>><?php _e('True', WEB_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="FALSE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_gateway_email_customer') == 'FALSE') echo 'selected="yes"';?>><?php _e('False', WEB_INVOICE_TRANS_DOMAIN); ?></option>
		</select></td>
	</tr>

	<tr class="gateway_info">
		<th width="200"><?php _e('Customer Receipt Email Header', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><input name="web_invoice_gateway_header_email_receipt"
			class="input_field" type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_header_email_receipt')); ?>" />
		</td>
	</tr>


	<tr class="gateway_info">
		<th width="200"><?php _e('Security: MD5 Hash', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><input name="web_invoice_gateway_MD5Hash" class="input_field"
			type="text"
			value="<?php echo stripslashes(get_option('web_invoice_gateway_MD5Hash')); ?>" />
		</td>
	</tr>

	<tr class="gateway_info">
		<th><?php _e('Delim Data:', WEB_INVOICE_TRANS_DOMAIN); ?></th>
		<td><select name="web_invoice_gateway_delim_data">
			<option value="TRUE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_gateway_delim_data') == 'TRUE') echo 'selected="yes"';?>><?php _e('True', WEB_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="FALSE" style="padding-right: 10px;"
			<?php if(get_option('web_invoice_gateway_delim_data') == 'FALSE') echo 'selected="yes"';?>><?php _e('False', WEB_INVOICE_TRANS_DOMAIN); ?></option>
		</select></td>
	</tr>
	<?php do_action('web_invoice_display_settings'); ?>
	<tr>
		<td></td>
		<td><input type="submit"
			value="<?php _e('Update', WEB_INVOICE_TRANS_DOMAIN); ?>"
			class="button" /></td>
	</tr>
</table>

<table class="form-table">
	<tr>
		<td>
		<h2><?php _e('Web Invoice Database Tables'); ?></h2>
		<p>Check to see if the database tables are installed properly. If not,
		try deactivating and reactivating the plugin, if that doesn't work, <a
			href="http://mohanjith.com/contact.html">contact us</a>.</p>
			<?php

			echo __("Main Table - ");  if($wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';")) {echo __("Good");} else {echo __("Not Found"); }
			echo "<br />".__("Meta Table - "); if($wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('meta')."';")) {echo __("Good");} else {echo __("Not Found"); }
			echo "<br />".__("Log Table - ");  if($wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) {echo __("Good");} else {echo __("Not Found"); }
			echo "<br />".__("Payment Table - "); if($wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('payment')."';")) {echo __("Good");} else {echo __("Not Found"); }
			echo "<br />".__("Payment Meta Table - ");  if($wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('payment_meta')."';")) {echo __("Good");} else {echo __("Not Found"); }
			?></td>
	</tr>
	<tr>
		<td colspan="2"><a id="delete_all_web_invoice_databases"
			href="admin.php?page=new_web_invoice&web_invoice_action=complete_removal"><?php _e('Remove All Web Invoice Databases', WEB_INVOICE_TRANS_DOMAIN); ?></a>
		- <?php _e('Only do this if you want to completely remove the plugin.  All invoices and logs will be gone... forever.', WEB_INVOICE_TRANS_DOMAIN); ?></td>
	</tr>
</table>
</form>
			<?php
}

function web_invoice_cc_setup($show_title = TRUE) {
	if($show_title) { ?>
<div id="web_invoice_need_mm" style="border-top: 1px solid #DFDFDF;"><?php _e('Do you need to accept credit cards?', WEB_INVOICE_TRANS_DOMAIN); ?></div>
	<?php
	}
}

function web_invoice_show_invoice($invoice_id) {
	apply_filters('web_invoice_email_variables', $invoice_id);

	echo preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_email_apply_variables', "<div class=\"subject\">Subject: <strong>".get_option('web_invoice_email_send_invoice_subject')."</strong></div>");
	echo "<div class=\"main_content\">";
	echo str_replace("\n", "<br />", web_invoice_show_email($invoice_id));
	echo "</div>";
}

function web_invoice_draw_itemized_table($invoice_id) {
	global $wpdb;

	$_invoice = new Web_Invoice_GetInfo($invoice_id);
	$invoice_info = $_invoice->_row_cache;
	$itemized = $invoice_info->itemized;
	$amount = $invoice_info->amount;
	$_tax_percents = unserialize(web_invoice_meta($invoice_id,'tax_value'));
	$_tax_names = unserialize(get_option('web_invoice_tax_name'));
	
	if (is_array($_tax_percents)) {
		$tax_percent = 0;

		foreach ($_tax_percents as $_x=>$_tax_percentx) {
			$tax_percent += $_tax_percentx;
			if (!isset($_tax_names[$_x])) {
				$_tax_names[$_x] = sprintf(__("Tax %s", WEB_INVOICE_TRANS_DOMAIN), $_x+1);
			}
		}
	} else {
		$tax_percent = $_tax_percents;
	}

	// Determine currency. First we check invoice-specific, then default code, and then we settle on USD
	$currency_code = web_invoice_determine_currency($invoice_id);

	if($tax_percent) {
		$tax_free_amount = $amount*(100/(100+(100*($tax_percent/100))));
		$tax_value = $amount - $tax_free_amount;
	}

	if(!strpos($amount,'.')) $amount = $amount . ".00";
	$itemized_array = unserialize(urldecode($itemized));


	if(is_array($itemized_array)) {
		$response .= "<p><table id=\"web_invoice_itemized_table\">
		<tr>\n";
		if(get_option('web_invoice_show_quantities') == "Show") {
			$response .= '<th style="width: 40px; text-align: right;">'.__('Quantity', WEB_INVOICE_TRANS_DOMAIN).'</th><th style=\"width: 50px; text-align: right;\">'.__('Unit price', WEB_INVOICE_TRANS_DOMAIN).'</th>';
		}
		$response .="<th>".__('Item', WEB_INVOICE_TRANS_DOMAIN)."</th><th style=\"width: 70px; text-align: right;\">".__('Cost', WEB_INVOICE_TRANS_DOMAIN)."</th>
		</tr> ";
		$i = 1;
		foreach($itemized_array as $itemized_item){
			//Show Quantites or not
			if(get_option('web_invoice_show_quantities') == '') $show_quantity = false;
			if(get_option('web_invoice_show_quantities') == 'Hide') $show_quantity = false;
			if(get_option('web_invoice_show_quantities') == 'Show') $show_quantity = true;


			if(!empty($itemized_item[name])) {
				if(!strpos($itemized_item[price],'.')) $itemized_item[price] = $itemized_item[price] . ".00";

				if($i % 2) { $response .= "<tr class='alt_row'>"; }
				else { $response .= "<tr >"; }

				//Quantities
				if($show_quantity) {
					$response .= "<td style=\"width: 70px; text-align: right;\">" . $itemized_item[quantity] . "</td>";
					$response .= "<td style=\"width: 50px; text-align: right;\">" . web_invoice_currency_format($itemized_item[price]) . "</td>";
				}

				//Item Name
				$response .= "<td>" . stripslashes($itemized_item[name]) . " <br /><span class='description_text'>" . stripslashes($itemized_item[description]) . "</span></td>";

				//Item Price
				$response .= "<td style=\"width: 70px; text-align: right;\">" . sprintf(web_invoice_currency_symbol_format($currency_code),  web_invoice_currency_format($itemized_item[quantity] * $itemized_item[price])) . "</td>";

				$response .="</tr>";
				$i++;
			}

		}
		if($tax_percent) {
			if (is_array($_tax_percents)) {
				foreach ($_tax_percents as $_x => $_tax_percentx) {
					if($i % 2) { $response .= "<tr class='alt_row'>"; }
					else { $response .= "<tr >"; }
					if(get_option('web_invoice_show_quantities') == "Show") {
						$response .= "<td></td><td></td>";
					}
					
					$_tax_value = $tax_free_amount*($_tax_percentx/100);
		
					$response .= "<td>".$_tax_names[$_x]." (". round($_tax_percentx,2). "%) </td>";
					if(get_option('web_invoice_show_quantities') == "Show") {
						$response .= "<td style='text-align:right;' colspan='2'>" . sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($_tax_value))."</td></tr>";
					} else {
						$response .= "<td style='text-align:right;'>" . sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($_tax_value))."</td></tr>";
					}
					$i++;
				}
			} else {
				if($i % 2) { $response .= "<tr class='alt_row'>"; }
					else { $response .= "<tr >"; }
				
				if(get_option('web_invoice_show_quantities') == "Show") {
					$response .= "<td></td><td></td>";
				}
				$response .= "<td>".__('Tax', WEB_INVOICE_TRANS_DOMAIN)." (". round($tax_percent,2). "%) </td>";
				if(get_option('web_invoice_show_quantities') == "Show") {
					$response .= "<td style='text-align:right;' colspan='2'>" . sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($tax_value))."</td></tr>";
				} else {
					$response .= "<td style='text-align:right;'>" . sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($tax_value))."</td></tr>";
				}
				$i++;
			}
		}
		
		if($i % 2) { $response .= "<tr class=\"web_invoice_bottom_line alt_row\">"; }
		else { $response .= "<tr  class='web_invoice_bottom_line'>"; }
		if(get_option('web_invoice_show_quantities') == "Show") {
			$response .="
			<td align=\"right\" colspan=\"2\">".__('Invoice Total', WEB_INVOICE_TRANS_DOMAIN).":</td>
			<td  colspan=\"2\" style=\"text-align: right;\" class=\"grand_total\">";
		} else {
			$response .="
			<td align=\"right\">".__('Invoice Total', WEB_INVOICE_TRANS_DOMAIN).":</td>
			<td style=\"text-align: right;\" class=\"grand_total\">";
		}

		$response .= sprintf(web_invoice_currency_symbol_format($currency_code), web_invoice_currency_format($amount));
		$response .= "</td></tr></table></p>";

		return $response;
	}

}


function web_invoice_draw_itemized_table_plaintext($invoice_id) {
	global $wpdb;
	$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
	$itemized = $invoice_info->itemized;
	$amount = $invoice_info->amount;
	if(!strpos($amount,'.')) $amount = $amount . ".00";

	$itemized_array = unserialize(urldecode($itemized));

	if(is_array($itemized_array)) {


		foreach($itemized_array as $itemized_item){
			if(!empty($itemized_item[name])) {
				$item_cost = $itemized_item[price] * $itemized_item[quantity];
				if(!strpos($item_cost,'.')) $item_cost = $item_cost . ".00";

				$response .= " $" . $item_cost . " \t - \t " . stripslashes($itemized_item[name]) . "\n";

			}
		}

		return $response;
	}

}

function web_invoice_user_profile_fields()
{
	global $wpdb, $web_invoice, $current_user;

	if (isset($_REQUEST['user_id'])) {
		$user_id = $_REQUEST['user_id'];
	} else {
		$user_id = $current_user->ID;
	}

	?>
<h3><?php _e('Billing / Invoicing Info', WEB_INVOICE_TRANS_DOMAIN); ?></h3>
<a name="billing_info"></a>
<table class="form-table">

	<tr>
		<th><label for="company_name"><?php _e('Company', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="company_name" id="company_name"
			value="<?php echo get_usermeta($user_id,'company_name'); ?>" /></td>
	</tr>
	
	<tr>
		<th><label for="streetaddress"><?php _e('Street Address', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="streetaddress" id="streetaddress"
			value="<?php echo get_usermeta($user_id,'streetaddress'); ?>" /></td>
	</tr>

	<tr>
		<th><label for="city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="city" id="city"
			value="<?php echo get_usermeta($user_id,'city'); ?>" /></td>
	</tr>

	<tr>
		<th><label for="state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="state" id="state"
			value="<?php echo get_usermeta($user_id,'state'); ?>" /><br />
		<p class="note"><?php _e('Use two-letter state codes for safe credit card processing.', WEB_INVOICE_TRANS_DOMAIN); ?></p>
		</td>
	</tr>

	<tr>
		<th><label for="streetaddress"><?php _e('ZIP Code', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="zip" id="zip"
			value="<?php echo get_usermeta($user_id,'zip'); ?>" /></td>
	</tr>

	<tr>
		<th><label for="phonenumber"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="phonenumber" id="phonenumber"
			value="<?php echo get_usermeta($user_id,'phonenumber'); ?>" />
		<p class="note"><?php _e('Enforce 555-555-5555 format if you are using PayPal.', WEB_INVOICE_TRANS_DOMAIN); ?></p>
		</td>
	</tr>

	<tr>
		<th><label for="country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><?php echo web_invoice_draw_select('country',web_invoice_country_array(),get_usermeta($user_id,'country')); ?></td>
	</tr>
	
	<tr>
		<th><label for="tax_id"><?php _e('Tax ID', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="tax_id" id="tax_id"
			value="<?php echo get_usermeta($user_id,'tax_id'); ?>" /></td>
	</tr>
	<?php 
		if ($current_user->allcaps[$web_invoice->web_invoice_user_level] == 1) {?>
	<tr>
		<th></th>
		<td><input type='button'
			onclick="window.location='admin.php?page=new_web_invoice&user_id=<?PHP echo $_REQUEST['user_id']; ?>';"
			class='button'
			value='<?php _e('Create New Invoice For This User', WEB_INVOICE_TRANS_DOMAIN); ?>' />
		</td>
	</tr>
	<?php } ?>
</table>
<h3><?php _e('Shipping Info', WEB_INVOICE_TRANS_DOMAIN); ?></h3>
<a name="shipping_info"></a>
<span class="invoice_action" ><a href="javascript:web_invoice_copy_billing('shipto');"><?php _e('Same as Billing', WEB_INVOICE_TRANS_DOMAIN); ?></a></span>
<table class="form-table">
	<tr>
		<th><label for="shipto_company_name"><?php _e('Company', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="shipto_company_name" id="shipto_company_name"
			value="<?php echo get_usermeta($user_id,'shipto_company_name'); ?>" /></td>
	</tr>

	<tr>
		<th><label for="shipto_city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="shipto_city" id="shipto_city"
			value="<?php echo get_usermeta($user_id,'shipto_city'); ?>" /></td>
	</tr>

	<tr>
		<th><label for="shipto_state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="shipto_state" id="shipto_state"
			value="<?php echo get_usermeta($user_id,'shipto_state'); ?>" /><br />
		<p class="note"><?php _e('Use two-letter state codes for safe credit card processing.', WEB_INVOICE_TRANS_DOMAIN); ?></p>
		</td>
	</tr>

	<tr>
		<th><label for="shipto_streetaddress"><?php _e('ZIP Code', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="shipto_zip" id="shipto_zip"
			value="<?php echo get_usermeta($user_id,'shipto_zip'); ?>" /></td>
	</tr>

	<tr>
		<th><label for="shipto_phonenumber"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><input type="text" name="shipto_phonenumber" id="shipto_phonenumber"
			value="<?php echo get_usermeta($user_id,'shipto_phonenumber'); ?>" />
		<p class="note"><?php _e('Enforce 555-555-5555 format if you are using PayPal.', WEB_INVOICE_TRANS_DOMAIN); ?></p>
		</td>
	</tr>

	<tr>
		<th><label for="shipto_country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label></th>
		<td><?php echo web_invoice_draw_select('shipto_country',web_invoice_country_array(),get_usermeta($user_id,'shipto_country')); ?></td>
	</tr>
</table>
	<?php
}

function web_invoice_show_paypal_receipt($invoice_id) {

	$invoice = new Web_Invoice_GetInfo($invoice_id);

	if(isset($_POST['first_name'])) update_usermeta($invoice->recipient('user_id'), 'first_name', $_POST['first_name']);
	if(isset($_POST['last_name'])) update_usermeta($invoice->recipient('user_id'), 'last_name', $_POST['last_name']);

	if(get_option('web_invoice_send_thank_you_email') == 'yes') web_invoice_send_email_receipt($invoice_id);

	web_invoice_paid($invoice_id);
	web_invoice_update_log($invoice_id,'paid', "PayPal Receipt: (" . $_REQUEST['receipt_id']. ")");
	if(isset($_REQUEST['payer_email'])) web_invoice_update_log($invoice_id,'paid',"PayPal payee user email: (" . $_REQUEST['payer_email']. ")");


	return '<div id="invoice_page" class="clearfix">
	<div id="invoice_overview" class="cleafix">
	<h2 class="invoice_page_subheading">'.sprintf(__('%s, thank you for your payment!',  WEB_INVOICE_TRANS_DOMAIN), $invoice->recipient("callsign")).'</h2>
	<p><strong>'.sprintf(__('Invoice %s has been paid.',  WEB_INVOICE_TRANS_DOMAIN), $invoice->display("display_id")).'</strong></p>
	</div>
	</div>';
}

function web_invoice_show_already_paid($invoice_id) {
	apply_filters('web_invoice_web_variables', $invoice_id);
	
	$invoice = new Web_Invoice_GetInfo($invoice_id);
?>
	<div id="invoice_paid" class="clearfix">
		<p><?php print preg_replace_callback('/(%([a-z_]+))/', 'web_invoice_web_apply_variables', get_option('web_invoice_web_already_paid_note', sprintf(__('This invoice was paid on %s.', WEB_INVOICE_TRANS_DOMAIN), $invoice->display('paid_date')))); ?></p>
	</div>
<?php 
}

function web_invoice_show_invoice_overview($invoice_id) {
	global $web_invoice_print;
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	
	?>
<div id="invoice_overview" class="clearfix">
<?php if (!$web_invoice_print) { ?><div class="noprint">
<h2 id="web_invoice_welcome_message" class="invoice_page_subheading"><?php printf(__('Welcome, %s', WEB_INVOICE_TRANS_DOMAIN), $invoice->recipient('callsign')); ?>!</h2></div>
<?php } ?>
<p class="web_invoice_main_description"><?php printf(__('We have sent you invoice <b>%1$s</b> with a total amount of %2$s', WEB_INVOICE_TRANS_DOMAIN), $invoice->display('display_id'), $invoice->display('display_amount')); ?>.</p>
	<?php if($invoice->display('due_date')) { ?>
<p class="web_invoice_due_date"><?php printf(__('Due Date: %s', WEB_INVOICE_TRANS_DOMAIN), $invoice->display('due_date')); } ?>
	<?php if($invoice->display('description')) { ?></p>
<p><?php echo stripcslashes($invoice->display('description'));  ?></p>
<?php  } ?> 
<?php 		
		//Billing Business Address
		if(get_option('web_invoice_show_business_address') == 'yes') web_invoice_show_business_address();
		if(get_option('web_invoice_show_billing_address') == 'yes') web_invoice_show_billing_address($invoice_id);
?>
<?php echo web_invoice_draw_itemized_table($invoice_id); ?>
<?php
	echo do_action('web_invoice_content_append', $invoice_id);
?>
</div>
<?php 
}

function web_invoice_show_business_address() {
	?>
<div id="invoice_business_info" class="clearfix">
<h2 class="invoice_page_subheading"><?php _e('Invoice From:', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<p class="web_invoice_business_name"><?php echo stripcslashes(get_option('web_invoice_business_name')); ?></p>
<p class="web_invoice_business_address"><?php echo stripcslashes(nl2br(get_option('web_invoice_business_address'))); ?></p>
<p class="web_invoice_business_phone"><?php echo get_option('web_invoice_business_phone'); ?></p>
<?php if (trim(get_option('web_invoice_business_tax_id')) !== '') { ?>
<p class="web_invoice_business_tax_id"><?php _e('Tax ID: ', WEB_INVOICE_TRANS_DOMAIN); ?><?php echo get_option('web_invoice_business_tax_id'); ?></p>
<?php } ?>
</div>

	<?php
}

function web_invoice_show_billing_address($invoice_id) {
	global $web_invoice_print;
	
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	?>
<div id="invoice_client_info" class="clearfix">
<h2 class="invoice_page_subheading"><?php _e('Invoice To:', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<p class="web_invoice_billing_name"><?php echo stripcslashes("{$invoice->recipient('first_name')} {$invoice->recipient('last_name')}"); ?></p>
<p class="web_invoice_billing_name"><?php echo stripcslashes($invoice->recipient('company_name')); ?></p>
<p class="web_invoice_billing_address"><?php echo stripcslashes(nl2br("{$invoice->recipient('streetaddress')}\n".
"{$invoice->recipient('city')}\n".
"{$invoice->recipient('state')} {$invoice->recipient('zip')}\n".
"{$invoice->recipient('country')}")); ?></p>
<p class="web_invoice_billing_phone"><?php echo $invoice->recipient('phonenumber'); ?></p>
<?php if ($invoice->recipient('tax_id') !== "") {?>
<p class="web_invoice_billing_tax_id"><?php _e('Tax ID: ', WEB_INVOICE_TRANS_DOMAIN); ?><?php echo $invoice->recipient('tax_id'); ?></p>
<?php } ?>
</div>
	<?php
}

function web_invoice_show_billing_information($invoice_id) {
	global $web_invoice_print;
	
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	$Web_Invoice = new Web_Invoice();
	$pp = false; $pf = false; $pfp = false; $cc = false; $sp = false; $mb = false; $alertpay = false; $gc = false;
	
	$method_count = 0;
	
	if(stristr(get_option('web_invoice_payment_method'), 'paypal')) { $pp = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'payflow')) { $pf = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'pfp')) { $pfp = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'sagepay') && !web_invoice_recurring($invoice_id)) { $sp = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'moneybookers')) { $mb = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'alertpay')) { $alertpay = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'cc')) { $cc = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'google_checkout')) { $gc = true; $method_count++; }
	if(stristr(get_option('web_invoice_payment_method'), 'other')) { $other = true; $method_count++; }

	if (!$web_invoice_print) {
	?>

<div id="billing_overview" class="clearfix noprint">
<div id="payment_methods">
<p><?php _e('Pay with:', WEB_INVOICE_TRANS_DOMAIN); ?> 
<?php if ($method_count > 1) { ?>
<br />
	<?php if ($cc) { ?> <a href="#cc_payment_form"
	title="<?php _e('Visa Master American Express', WEB_INVOICE_TRANS_DOMAIN); ?>" class="payment_select cc"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/cc_logo.png"
	alt="Visa Master American Express" width="265" height="45" /></a> <?php } ?>
	<?php if ($alertpay) { ?> <a href="#alertpay_payment_form"
	title="<?php _e('AlertPay', WEB_INVOICE_TRANS_DOMAIN); ?>" class="payment_select moneybookers"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/alertpay_logo.png"
	alt="AlertPay" width="81" height="45" /></a> <?php } ?> <?php if ($sp) { ?>
<a href="#sagepay_payment_form"
	title="<?php _e('Sage Pay', WEB_INVOICE_TRANS_DOMAIN); ?>"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/sage_pay_logo.gif"
	alt="Sage Pay" width="145" height="33" /></a> <?php } ?> <?php if ($mb) { ?>
<a href="#moneybookers_payment_form"
	title="<?php _e('Moneybookers', WEB_INVOICE_TRANS_DOMAIN); ?>"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/moneybookers_logo.png"
	alt="Moneybookers" width="75" height="42" /></a> <?php } ?> <?php if ($gc) { ?>
<a href="#google_checkout_payment_form"
	title="<?php _e('Google Checkout', WEB_INVOICE_TRANS_DOMAIN); ?>" class="payment_select google_checkout"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/google_checkout.gif"
	alt="Google Checkout" height="46" width="180" /></a> <?php } ?> <?php if ($pp) { ?>
<a href="#paypal_payment_form"
	title="<?php _e('PayPal', WEB_INVOICE_TRANS_DOMAIN); ?>" class="payment_select paypal"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/paypal_logo.png"
	alt="PayPal" width="80" height="45" /></a> <?php } ?> <?php if ($pfp) { ?> <a href="#pfp_payment_form"
	title="<?php _e('Visa Master American Express', WEB_INVOICE_TRANS_DOMAIN); ?>" class="payment_select pfp"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/cc_logo.png"
	alt="Visa Master American Express" width="265" height="45" /></a> <?php } ?> <?php if ($pf) { ?>
<a href="#payflow_payment_form"
	title="<?php _e('PayPal Payflow', WEB_INVOICE_TRANS_DOMAIN); ?>" class="payment_select payflow"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/payflow_logo.png"
	alt="PayPal Payflow" width="80" height="45" /></a> <?php } ?> <?php if ($other) { ?>
<a href="#other_payment_form"
	title="<?php _e('Other/Bank', WEB_INVOICE_TRANS_DOMAIN); ?>" class="payment_select other"><img
	src="<?php echo Web_Invoice::frontend_path(); ?>/images/bank_logo.png"
	alt="Other/Bank" width="80" height="45" /></a> <?php } ?>
<?php } ?>
</p>
</div>
	<?php

	if ($alertpay) web_invoice_show_alertpay_form($invoice_id, $invoice);
	if ($cc) web_invoice_show_cc_form($invoice_id, $invoice);
	if ($sp) web_invoice_show_sagepay_form($invoice_id, $invoice);
	if ($mb) web_invoice_show_moneybookers_form($invoice_id, $invoice);
	if ($gc) web_invoice_show_google_checkout_form($invoice_id, $invoice);
	if ($pp) web_invoice_show_paypal_form($invoice_id, $invoice);
	if ($pf) web_invoice_show_payflow_form($invoice_id, $invoice);
	if ($pfp) web_invoice_show_pfp_form($invoice_id, $invoice);
	if ($other) web_invoice_show_other_form($invoice_id, $invoice);

	?></div>
	<script type="text/javascript">
		_web_invoice_method_count = <?php echo $method_count; ?>;
	</script>
	<?php
	}
}

function web_invoice_show_alertpay_form($invoice_id, $invoice) {
	?>
<div id="alertpay_payment_form" class="payment_form">
<form action="https://www.alertpay.com/PayProcess.aspx" method="post"
	class="clearfix"><input type="hidden" name="ap_currency"
	value="<?php echo $invoice->display('currency'); ?>" /> <input
	type="hidden" name="ap_merchant"
	value="<?php echo get_option('web_invoice_alertpay_address'); ?>" /> <input
	type="hidden" name="ap_totalamount"
	value="<?php echo $invoice->display('amount'); ?>" /> <input
	type="hidden" name="ap_itemname" id="invoice_num"
	value="<?php echo  $invoice->display('display_id'); ?>" /> <input
	type="hidden" name="ap_returnurl"
	value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" /> <?php
	// Convert Itemized List into AlertPay Item List (Not supported, we just show an aggregated fields)
	if(is_array($invoice->display('itemized'))) {
		echo web_invoice_create_alertpay_itemized_list($invoice->display('itemized'),$invoice_id);
	} 
	if (web_invoice_recurring($invoice_id)) { ?>
	<input type="hidden" name="ap_purchasetype" value="Subscription" />
	<input type="hidden" name="ap_timeunit" value="<?php echo preg_replace('/s$/', '', $invoice->display('interval_unit')); ?>" />
	<input type="hidden" name="ap_periodlength" value="<?php echo $invoice->display('interval_length'); ?>" />		
<?php 
	} else { ?>
	<input type="hidden" name="ap_purchasetype" value="Service" />
<?php
	}?>
<fieldset id="credit_card_information">
<ol>
	<li><label for="submit">&nbsp;</label> <input type="image"
		src="https://www.alertpay.com/PayNow/4FF7280888FE4FD4AE1B4A286ED9B8D5a.gif"
		style="border: 0; width: 170px; height: 70px; padding: 0;"
		name="submit" alt="Pay now with AlertPay" class="pay_button alertpay" /></li>
</ol>
</fieldset>
</form>
</div>
	<?php
}

function web_invoice_show_google_checkout_form($invoice_id, $invoice) {
	if (get_option('web_invoice_google_checkout_env') == 'sandbox') {
		$env_base_url = "sandbox.google.com/checkout";
	} else {
		$env_base_url = "checkout.google.com";
	}
	?>
<div id="google_checkout_payment_form" class="payment_form">
<form action="https://<?php echo $env_base_url; ?>/api/checkout/v2/checkoutForm/Merchant/<?php echo get_option('web_invoice_google_checkout_merchant_id'); ?>" method="post"
	class="clearfix" accept-charset="utf-8"><input type="hidden" name="_charset_"/><?php
if ($invoice->display('tax_total')) {
?>
<p><?php _e('Tax may not be applied if you are from a different state', WEB_INVOICE_TRANS_DOMAIN); ?></p>
<?php 
}
	// Convert Itemized List into Google Checkout Item List
	if (is_array($invoice->display('itemized'))) {
		echo web_invoice_create_google_checkout_itemized_list($invoice->display('itemized'),$invoice_id, web_invoice_recurring($invoice_id));
	}
	?>
	<input name="checkout-flow-support.merchant-checkout-flow-support.continue-shopping-url" type="hidden" value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>"> 
<fieldset id="credit_card_information">
<ol>
	<li><label for="submit">&nbsp;</label> <input type="image" name="Google Checkout" 
		alt="Fast checkout through Google" height="46" width="180"
		style="border: 0; width: 180px; height: 46px; padding: 0;"  class="pay_button google_checkout"
		src="https://<?php echo $env_base_url; ?>/buttons/checkout.gif?merchant_id=<?php echo get_option('web_invoice_google_checkout_merchant_id'); ?>&w=180&h=46&style=white&variant=text&loc=en_US"/>
		</li>
</ol>
</fieldset>
</form>
</div>
	<?php
}

function web_invoice_show_moneybookers_form($invoice_id, $invoice) {
	?>
<div id="moneybookers_payment_form" class="payment_form">
<h2 class="invoice_page_subheading"><?php _e('Billing Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<form action="https://www.moneybookers.com/app/payment.pl" method="post"
	class="clearfix"><input type="hidden" name="currency"
	value="<?php echo $invoice->display('currency'); ?>" /> <input
	type="hidden" name="no_shipping" value="1" /> <input type="hidden"
	name="rid" value="5413099" /> <input type="hidden" name="return_url"
	value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" /> <input
	type="hidden" name="cancel_url"
	value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" /> <input
	type="hidden" name="status_url"
	value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" /> <input
	type="hidden" name="transaction_id" id="invoice_num"
	value="<?php echo  $invoice->display('display_id'); ?>" /> <?php
	if (web_invoice_recurring($invoice_id)) {
		?> <input type="hidden" name="pay_to_email"
	value="<?php echo get_option('web_invoice_moneybookers_recurring_address'); ?>" /> 
	<input type="hidden" name="rec_payment_id"
	value="<?php echo $invoice->display('display_id').date('YMD'); ?>" /> <input
	type="hidden" name="rec_payment_type" value="recurring" /> <input
	type="hidden" name="rec_status_url"
	value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" /> <input
	type="hidden" name="rec_cycle"
	value="<?php echo preg_replace('/s$/', '', $invoice->display('interval_unit')); ?>" />
<input type="hidden" name="rec_period"
	value="<?php echo $invoice->display('interval_length'); ?>" /> <input
	type="hidden" name="rec_start_date"
	value="<?php echo date('d/m/Y', strtotime($invoice->display('startDate'))); ?>" /> <input
	type="hidden" name="rec_end_date"
	value="<?php echo date('d/m/Y', strtotime($invoice->display('endDate'))); ?>" /> <input
	type="hidden" name="rec_amount"
	value="<?php echo $invoice->display('amount'); ?>" /> <?php
	} else {
		?> <input type="hidden" name="pay_to_email"
	value="<?php echo get_option('web_invoice_moneybookers_address'); ?>" /> 
	<input type="hidden" name="amount" value="<?php echo $invoice->display('amount'); ?>" /> <?php 
	// Convert Itemized List into Moneybookers Item List
	if(is_array($invoice->display('itemized'))) {
		echo web_invoice_create_moneybookers_itemized_list($invoice->display('itemized'),$invoice_id);
	}
	}
	?>
<fieldset id="credit_card_information">
<ol>
	<li><label for="firstname"><?php _e('First Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("firstname",$invoice->recipient('first_name')); ?>
	</li>

	<li><label for="lastname"><?php _e('Last Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("lastname",$invoice->recipient('last_name')); ?>
	</li>

	<li><label for="pay_from_email"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("pay_from_email",$invoice->recipient('email_address')); ?>
	</li>

	<li><label class="inputLabel" for="phone_number"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="phone_number" class="input_field" type="text"
		id="phone_number" size="40" maxlength="50"
		value="<?php print $invoice->recipient('phonenumber'); ?>" /></li>

	<li><label for="address"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
	</li>

	<li><label for="city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
	</li>

	<li><label for="state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('state',$invoice->recipient('state'));  ?>
	</li>

	<li><label for="postal_code"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("postal_code",$invoice->recipient('zip')); ?>
	</li>

	<li><label for="country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
	</li>

	<li><label for="submit">&nbsp;</label> <input type="image"
		src="https://www.moneybookers.com/images/logos/checkout_logos/checkoutlogo_CCs_240x80.gif"
		style="border: 0; width: 240px; height: 80px; padding: 0;" class="pay_button moneybookers"
		name="submit" alt="Moneybookers.com and money moves" /></li>
</ol>
<br class="cb" />
</fieldset>
</form>
</div>
	<?php
}

function web_invoice_paypal_convert_interval($val, $length) {
	switch ($val) {
		case 'months':
			if ($length == 12) {
				return 'Y';	
			} else {
				return 'M';
			}
		case 'weeks':
			return 'W';
		case 'years':
			return 'Y';
		case 'days':
		default: return 'D';
	}
}

function web_invoice_show_paypal_form($invoice_id, $invoice) {
if (get_option('web_invoice_paypal_sandbox') == 'True') {
	$_url = "https://www.sandbox.paypal.com/us/cgi-bin/webscr";
} else {
	$_url = "https://www.paypal.com/us/cgi-bin/webscr";
}
	?>
<div id="paypal_payment_form" class="payment_form"><?php if (get_option('web_invoice_paypal_only_button') == 'False') { ?>
<h2 class="invoice_page_subheading"><?php _e('Billing Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
	<?php } ?>
<form action="<?php print $_url; ?>" method="post"
	class="clearfix"><input type="hidden" name="currency_code"
	value="<?php echo $invoice->display('currency'); ?>" /> <input
	type="hidden" name="no_shipping" value="1" /> <input type="hidden"
	name="upload" value="1" /> <input type="hidden" name="business"
	value="<?php echo get_option('web_invoice_paypal_address'); ?>" /> <input
	type="hidden" name="return"
	value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" /> <input
	type="hidden" name="rm" value="2" /> <input
	type="hidden" name="invoice" id="invoice_num"
	value="<?php echo  $invoice->display('display_id'); ?>" /> <?php
	if (web_invoice_recurring($invoice_id)) { ?>
	<input type="hidden" name="cmd" value="_xclick-subscriptions" />
	<input type="hidden" name="t3" value="<?php echo web_invoice_paypal_convert_interval($invoice->display('interval_unit'), $invoice->display('interval_length')); ?>" />
	<input type="hidden" name="src" value="1" />
	<input type="hidden" name="srt" value="<?php echo $invoice->display('totalOccurrences'); ?>" />
	<input type="hidden" name="p3" value="<?php echo $invoice->display('interval_length'); ?>" />
	<input type="hidden" name="a3" value="<?php echo $invoice->display('amount'); ?>" />
	<input type="hidden" name="item_name" value="<?php echo $invoice->display('subscription_name'); ?>">
	<input type="hidden" name="item_number" value="<?php echo $invoice->display('display_id').date('YMD'); ?>">
	<?php 
	} else
	// Convert Itemized List into PayPal Item List
	if(is_array($invoice->display('itemized'))) echo web_invoice_create_paypal_itemized_list($invoice->display('itemized'),$invoice_id);
	?>
	<input type="hidden" name="amount" value="<?php echo $invoice->display('amount'); ?>" /> 
	<input type="hidden" name="notify_url" value="<?php echo web_invoice_build_invoice_link_paypal($invoice_id); ?>" />
<fieldset id="credit_card_information">
<ol>

<?php if (get_option('web_invoice_paypal_only_button') == 'False') { ?>
	<li><label for="first_name"><?php _e('First Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("first_name",$invoice->recipient('first_name')); ?>
	</li>

	<li><label for="last_name"><?php _e('Last Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("last_name",$invoice->recipient('last_name')); ?>
	</li>

	<li><label for="email"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("email_address",$invoice->recipient('email_address')); ?>
	</li>

	<?php
	list($day_phone_a, $day_phone_b, $day_phone_c) = split('[/.-]', $invoice->recipient('paypal_phonenumber'));
	?>
	<li><label for="day_phone_a"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("night_phone_a",$day_phone_a,' style="width:25px;" size="3" maxlength="3" '); ?>-
	<?php echo web_invoice_draw_inputfield("night_phone_b",$day_phone_b,' style="width:25px;" size="3" maxlength="3" '); ?>-
	<?php echo web_invoice_draw_inputfield("night_phone_c",$day_phone_c,' style="width:35px;" size="4" maxlength="4" '); ?>
	</li>

	<li><label for="address"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
	</li>

	<li><label for="city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
	</li>

	<li><label for="state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('state',$invoice->recipient('state'));  ?>
	</li>

	<li><label for="zip"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("zip",$invoice->recipient('zip')); ?>
	</li>

	<li><label for="country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
	</li>
	<?php }	?>
	<?php
	if (web_invoice_recurring($invoice_id)) { ?>
	<li><label for="submit">&nbsp;</label> <input type="image"
		src="https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif"
		style="border: 0; width: 107px; height: 26px; padding: 0;"
		name="submit" class="pay_button paypal_subscription"
		alt="Subscribe with PayPal - it's fast, free and secure!" /></li>
	<?php 
	} else { ?>
	<li><label for="submit">&nbsp;</label> <input type="image"
		src="https://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif"
		style="border: 0; width: 107px; height: 26px; padding: 0;"
		name="submit" class="pay_button paypal"
		alt="Make payments with PayPal - it's fast, free and secure!" /></li>
	<?php 
	} ?>
</ol>
<br class="cb" />
</fieldset>
</form>
</div>
	<?php
}

function web_invoice_show_payflow_form($invoice_id, $invoice) {
	?>
<div id="payflow_payment_form" class="payment_form"><?php if (get_option('web_invoice_payflow_only_button') == 'False') { ?>
	<?php } ?>
<form action="https://payflowlink.paypal.com" method="post"
	class="clearfix" id="payflow_form"><input type="hidden" name="currency_code"
	value="<?php echo $invoice->display('currency'); ?>" /> <input type="hidden" name="LOGIN"
	value="<?php echo get_option('web_invoice_payflow_login'); ?>" />
	<input type="hidden" name="PARTNER"
	value="<?php echo get_option('web_invoice_payflow_partner'); ?>" /> <input type="hidden" name="AMOUNT"
	value="<?php echo $invoice->display('amount'); ?>" /> <input type="hidden" name="ECHODATA"
	value="true" /> <input type="hidden" name="TYPE"
	value="S" /> <input type="hidden" name="METHOD"
	value="CC" /> <input type="hidden" name="CUSTID"
	value="<?php echo $invoice->display('display_id'); ?>" /> <?php
	?>

<fieldset id="credit_card_information">
<?php if (get_option('web_invoice_payflow_only_button') == 'False') { ?>
<h2 class="invoice_page_subheading"><?php _e('Billing Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<ol>
	<li><label for="NAME"><?php _e('Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("NAME","{$invoice->recipient('first_name')} {$invoice->recipient('last_name')}"); ?>
	</li>
	
	<li><label for="EMAIL"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("EMAIL",$invoice->recipient('email_address')); ?>
	</li>
	
	<li><label for="PHONE"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("PHONE", $invoice->recipient('paypal_phonenumber'),' style="width:85px;" size="10" maxlength="15" '); ?>
	</li>

	<li><label for="ADDRESS"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("ADDRESS",$invoice->recipient('streetaddress')); ?>
	</li>

	<li><label for="CITY"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("CITY",$invoice->recipient('city')); ?>
	</li>

	<li><label for="STATE"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('STATE',$invoice->recipient('state'));  ?>
	</li>

	<li><label for="ZIP"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("ZIP",$invoice->recipient('zip')); ?>
	</li>

	<li><label for="COUNTRY"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('COUNTRY',web_invoice_country_array(),$invoice->recipient('country')); ?>
	</li>
</ol>
<?php if (get_option('web_invoice_payflow_shipping_details') == 'True') { ?>
<span class="invoice_action" style="float: right"><a href="javascript:payflow_copy_billing('TOSHIP');"><?php _e('Same as Billing', WEB_INVOICE_TRANS_DOMAIN); ?></a></span>
<h2 class="invoice_page_subheading"><?php _e('Shipping Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<ol>
	<li><label for="NAMETOSHIP"><?php _e('Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("NAMETOSHIP","{$invoice->shipping('first_name')} {$invoice->shipping('last_name')}"); ?>
	</li>
	
	<li><label for="EMAILTOSHIP"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("EMAILTOSHIP",$invoice->shipping('email_address')); ?>
	</li>
	
	<li><label for="PHONETOSHIP"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("PHONETOSHIP", $invoice->shipping('paypal_phonenumber'),' style="width:85px;" size="10" maxlength="15" '); ?>
	</li>

	<li><label for="ADDRESSTOSHIP"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("ADDRESSTOSHIP",$invoice->shipping('streetaddress')); ?>
	</li>

	<li><label for="CITYTOSHIP"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("CITYTOSHIP",$invoice->shipping('city')); ?>
	</li>

	<li><label for="STATETOSHIP"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('STATETOSHIP',$invoice->shipping('state'));  ?>
	</li>

	<li><label for="ZIPTOSHIP"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("ZIPTOSHIP",$invoice->shipping('zip')); ?>
	</li>

	<li><label for="COUNTRYTOSHIP"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('COUNTRYTOSHIP',web_invoice_country_array(),$invoice->shipping('country')); ?>
	</li>
</ol>
<?php } ?>
<?php }	?>
<ol>
	<li><label for="submit">&nbsp;</label> <input type="submit"
		style="border: 0; width: 107px; height: 26px; padding: 0;"
		name="submit"
		value="Pay now!" class="pay_button payflow"
		alt="Make payments with PayPal Payflow" /></li>

</ol>
</fieldset>
<br class="cb" />
</form>
</div>
	<?php
}

function web_invoice_show_pfp_form($invoice_id, $invoice) {
	?>
<div id="pfp_payment_form" class="payment_form">
<form method="post" name="checkout_form" id="pfp_checkout_form"
	class="online_payment_form"
	onsubmit="process_cc_checkout('pfp'); return false;" class="clearfix"><input
	type="hidden" name="amount"
	value="<?php echo $invoice->display('amount'); ?>" /> <input
	type="hidden" name="processor"
	value="pfp" /> <input
	type="hidden" name="user_id"
	value="<?php echo $invoice->recipient('user_id'); ?>" /> <input
	type="hidden" name="email_address"
	value="<?php echo $invoice->recipient('email_address'); ?>" /> <input
	type="hidden" name="invoice_num" value="<?php echo  $invoice_id; ?>" />
<input type="hidden" name="currency_code" id="currency_code"
	value="<?php echo $invoice->display('currency'); ?>" /> <input
	type="hidden" name="web_invoice_id_hash"
	value="<?php echo $invoice->display('hash'); ?>" />
<h2 class="invoice_page_subheading"><?php _e('Billing Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<fieldset id="credit_card_information">
<ol>
	<li><label for="first_name"><?php _e('First Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("first_name",$invoice->recipient('first_name')); ?>
	</li>

	<li><label for="last_name"><?php _e('Last Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("last_name",$invoice->recipient('last_name')); ?>
	</li>

	<li><label for="email_address"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("email_address",$invoice->recipient('email_address')); ?>
	</li>

	<li><label class="inputLabel" for="phonenumber"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="phonenumber" class="input_field" type="text"
		id="phonenumber" size="40" maxlength="50"
		value="<?php print $invoice->recipient('phonenumber'); ?>" /></li>

	<li><label for="address"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
	</li>

	<li><label for="city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
	</li>

	<li><label for="state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('state',$invoice->recipient('state'));  ?>
	</li>

	<li><label for="zip"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("zip",$invoice->recipient('zip')); ?>
	</li>

	<li><label for="country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
	</li>
	
	<li class="hide_after_success"><label class="inputLabel" for="pf_card_num"><?php _e('Credit Card Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="card_num" onkeyup="cc_card_pick('#pfp_cardimage', '#pf_card_num');"
		id="pf_card_num" class="credit_card_number input_field noautocomplete" type="text"
		size="22" maxlength="22" /></li>

	<li class="hide_after_success nocard cardimage"  id="pfp_cardimage" style=" background: url(<?php echo Web_Invoice::frontend_path(); ?>/images/card_array.png) no-repeat;">
	</li>

	<li class="hide_after_success"><label class="inputLabel"
		for="exp_month"><?php _e('Expiration Date', WEB_INVOICE_TRANS_DOMAIN); ?></label>
		<?php _e('Month', WEB_INVOICE_TRANS_DOMAIN); ?> <select
		name="exp_month" id="exp_month">
		<?php print web_invoice_printMonthDropdown(); ?>
	</select> <?php _e('Year', WEB_INVOICE_TRANS_DOMAIN); ?> <select
		name="exp_year" id="exp_year">
		<?php print web_invoice_printYearDropdown('', true); ?>
	</select></li>

	<li class="hide_after_success"><label class="inputLabel"
		for="card_code"><?php _e('Security Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input id="card_code" name="card_code"
		class="input_field noautocomplete" style="width: 70px;" type="text" size="4"
		maxlength="4" /></li>
</ol>
<?php if (get_option('web_invoice_pfp_shipping_details') == 'True') { ?>
<span class="invoice_action" style="float: right"><a href="javascript:pfp_copy_billing('shipto');"><?php _e('Same as Billing', WEB_INVOICE_TRANS_DOMAIN); ?></a></span>
<h2 class="invoice_page_subheading"><?php _e('Shipping Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<ol>
	<li><label for="shipto_first_name"><?php _e('First Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_first_name",$invoice->shipping('first_name')); ?>
	</li>

	<li><label for="shipto_last_name"><?php _e('Last Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_last_name",$invoice->shipping('last_name')); ?>
	</li>

	<li><label for="shipto_email_address"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_email_address",$invoice->shipping('email_address')); ?>
	</li>

	<li><label class="inputLabel" for="shipto_phonenumber"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="shipto_phonenumber" class="input_field" type="text"
		id="shipto_phonenumber" size="40" maxlength="50"
		value="<?php print $invoice->shipping('phonenumber'); ?>" /></li>

	<li><label for="shipto_address"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_address",$invoice->shipping('streetaddress')); ?>
	</li>

	<li><label for="shipto_city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_city",$invoice->shipping('city')); ?>
	</li>

	<li><label for="shipto_state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('shipto_state',$invoice->shipping('state'));  ?>
	</li>

	<li><label for="shipto_zip"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_zip",$invoice->shipping('zip')); ?>
	</li>

	<li><label for="shipto_country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('shipto_country',web_invoice_country_array(),$invoice->shipping('country')); ?>
	</li>
</ol>
<?php } ?>
<ol>
	<li id="web_invoice_process_wait"><label for="submit"><span></span>&nbsp;</label>
	<button type="submit" id="cc_pay_button" class="pay_button pfp"
		class="hide_after_success submit_button"><?php printf(__('Pay %s', WEB_INVOICE_TRANS_DOMAIN), $invoice->display('display_amount')); ?></button>
	</li>
</ol>
</fieldset>
<br class="cb" />
&nbsp;
<div id="wp_pfp_response"></div>

</form>
</div>
		<?php
}

function web_invoice_show_sagepay_form($invoice_id, $invoice) {
	?>
<div id="sagepay_payment_form" class="payment_form">
<form method="post" name="checkout_form" id="sagepay_checkout_form"
	class="online_payment_form"
	onsubmit="process_sagepay_process(); return false;" class="clearfix"><input
	type="hidden" name="amount"
	value="<?php echo $invoice->display('amount'); ?>" /> <input
	type="hidden" name="processor"
	value="sagepay" /> <input
	type="hidden" name="user_id"
	value="<?php echo $invoice->recipient('user_id'); ?>" /> <input
	type="hidden" name="email_address"
	value="<?php echo $invoice->recipient('email_address'); ?>" /> <input
	type="hidden" name="invoice_num" value="<?php echo  $invoice_id; ?>" />
<input type="hidden" name="currency_code" id="currency_code"
	value="<?php echo $invoice->display('currency'); ?>" /> <input
	type="hidden" name="web_invoice_id_hash"
	value="<?php echo $invoice->display('hash'); ?>" />
<h2 class="invoice_page_subheading"><?php _e('Billing Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<fieldset id="credit_card_information">
<ol>
	<li><label for="first_name"><?php _e('First Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("first_name",$invoice->recipient('first_name')); ?>
	</li>

	<li><label for="last_name"><?php _e('Last Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("last_name",$invoice->recipient('last_name')); ?>
	</li>

	<li><label for="email_address"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("email_address",$invoice->recipient('email_address')); ?>
	</li>

	<li><label class="inputLabel" for="phonenumber"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="phonenumber" class="input_field" type="text"
		id="phonenumber" size="40" maxlength="50"
		value="<?php print $invoice->recipient('phonenumber'); ?>" /></li>

	<li><label for="address"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
	</li>

	<li><label for="city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
	</li>

	<li><label for="state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('state',$invoice->recipient('state'));  ?>
	</li>

	<li><label for="zip"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("zip",$invoice->recipient('zip')); ?>
	</li>

	<li><label for="country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
	</li>

</ol>
<?php if (get_option('web_invoice_sagepay_shipping_details') == 'True') { ?>
<span class="invoice_action" style="float: right"><a href="javascript:sagepay_copy_billing('shipto');"><?php _e('Same as Billing', WEB_INVOICE_TRANS_DOMAIN); ?></a></span>
<h2 class="invoice_page_subheading"><?php _e('Shipping Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<ol>
	<li><label for="shipto_first_name"><?php _e('First Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_first_name",$invoice->shipping('first_name')); ?>
	</li>

	<li><label for="shipto_last_name"><?php _e('Last Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_last_name",$invoice->shipping('last_name')); ?>
	</li>

	<li><label for="shipto_email_address"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_email_address",$invoice->shipping('email_address')); ?>
	</li>

	<li><label class="inputLabel" for="shipto_phonenumber"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="shipto_phonenumber" class="input_field" type="text"
		id="shipto_phonenumber" size="40" maxlength="50"
		value="<?php print $invoice->shipping('phonenumber'); ?>" /></li>

	<li><label for="shipto_address"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_address",$invoice->shipping('streetaddress')); ?>
	</li>

	<li><label for="shipto_city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_city",$invoice->shipping('city')); ?>
	</li>

	<li><label for="shipto_state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('shipto_state',$invoice->shipping('state'));  ?>
	</li>

	<li><label for="shipto_zip"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("shipto_zip",$invoice->shipping('zip')); ?>
	</li>

	<li><label for="shipto_country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('shipto_country',web_invoice_country_array(),$invoice->shipping('country')); ?>
	</li>
</ol>
<?php } ?>
<ol>
	<li id="web_invoice_process_wait"><label for="submit">&nbsp;</label> <input type="image"
		src="<?php echo Web_Invoice::frontend_path(); ?>/images/sage_pay_logo.gif"
		style="border: 0; width: 145px; height: 33px; padding: 0; background-color: white;" class="pay_button sagepay"
		name="submit" alt="Sage Pay" /> <span></span></li>
</ol>
</fieldset>
<br class="cb" />
&nbsp;
<div id="wp_sagepay_response"></div>

</form>

<?php 

if (get_option('web_invoice_sagepay_env') == 'live') {
	$form_action = "https://live.sagepay.com/gateway/service/vspform-register.vsp";
} else if (get_option('web_invoice_sagepay_env') == 'test') {
	$form_action = "https://test.sagepay.com/gateway/service/vspform-register.vsp";
} else {
	$form_action = "https://test.sagepay.com/Simulator/VSPFormGateway.asp";
}

?>
<form method="post" name="submit_form" id="sagepay_submit_form" class="clearfix" action="<?php print $form_action; ?>">
	<input type="hidden" name="VPSProtocol" value="2.23" />
	<input type="hidden" name="TxType" value="PAYMENT" />
	<input type="hidden" name="Vendor" value="<?php print get_option('web_invoice_sagepay_vendor_name'); ?>" />
	<input type="hidden" name="Crypt" id="sagepay_crypt" value="" />
</form>

</div>
		<?php
}

function web_invoice_show_other_form($invoice_id, $invoice) {
	?>
<div id="other_payment_form" class="payment_form"><?php print nl2br(get_option('web_invoice_other_details')); ?></div>
	<?php
}

function web_invoice_show_cc_form($invoice_id, $invoice) {
	?>
<div id="cc_payment_form" class="payment_form">
<h2 class="invoice_page_subheading"><?php _e('Billing Information', WEB_INVOICE_TRANS_DOMAIN); ?></h2>
<form method="post" name="checkout_form" id="checkout_form"
	class="online_payment_form"
	onsubmit="process_cc_checkout(); return false;" class="clearfix"><input
	type="hidden" name="amount"
	value="<?php echo $invoice->display('amount'); ?>" /> <input
	type="hidden" name="user_id"
	value="<?php echo $invoice->recipient('user_id'); ?>" /> <input
	type="hidden" name="invoice_num" value="<?php echo  $invoice_id; ?>" />
<input type="hidden" name="currency_code" id="currency_code"
	value="<?php echo $invoice->display('currency'); ?>" /> <input
	type="hidden" name="web_invoice_id_hash"
	value="<?php echo $invoice->display('hash'); ?>" />
<fieldset id="credit_card_information">
<ol>
	<li><label for="first_name"><?php _e('First Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("first_name",$invoice->recipient('first_name')); ?>
	</li>

	<li><label for="last_name"><?php _e('Last Name', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("last_name",$invoice->recipient('last_name')); ?>
	</li>

	<li><label for="email"><?php _e('Email Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("email_address",$invoice->recipient('email_address')); ?>
	</li>

	<li><label class="inputLabel" for="phonenumber"><?php _e('Phone Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="phonenumber" class="input_field" type="text"
		id="phonenumber" size="40" maxlength="50"
		value="<?php print $invoice->recipient('phonenumber'); ?>" /></li>

	<li><label for="address"><?php _e('Address', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
	</li>

	<li><label for="city"><?php _e('City', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
	</li>

	<li><label for="state"><?php _e('State (e.g. CA)', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php print web_invoice_draw_inputfield('state',$invoice->recipient('state'));  ?>
	</li>

	<li><label for="zip"><?php _e('Zip Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_inputfield("zip",$invoice->recipient('zip')); ?>
	</li>

	<li><label for="country"><?php _e('Country', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
	</li>

	<li class="hide_after_success"><label class="inputLabel" for="card_num"><?php _e('Credit Card Number', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input name="card_num" onkeyup="cc_card_pick();"
		id="card_num" class="credit_card_number input_field noautocomplete" type="text"
		size="22" maxlength="22" /></li>

	<li class="hide_after_success nocard cardimage"  id="cardimage" style=" background: url(<?php echo Web_Invoice::frontend_path(); ?>/images/card_array.png) no-repeat;">
	</li>

	<li class="hide_after_success"><label class="inputLabel"
		for="exp_month"><?php _e('Expiration Date', WEB_INVOICE_TRANS_DOMAIN); ?></label>
		<?php _e('Month', WEB_INVOICE_TRANS_DOMAIN); ?> <select
		name="exp_month" id="exp_month">
		<?php print web_invoice_printMonthDropdown(); ?>
	</select> <?php _e('Year', WEB_INVOICE_TRANS_DOMAIN); ?> <select
		name="exp_year" id="exp_year">
		<?php print web_invoice_printYearDropdown(); ?>
	</select></li>

	<li class="hide_after_success"><label class="inputLabel"
		for="card_code"><?php _e('Security Code', WEB_INVOICE_TRANS_DOMAIN); ?></label>
	<input id="card_code" name="card_code"
		class="input_field noautocomplete" style="width: 70px;" type="text" size="4"
		maxlength="4" /></li>

	<li id="web_invoice_process_wait"><label for="submit"><span></span>&nbsp;</label>
	<button type="submit" id="cc_pay_button" class="pay_button cc"
		class="hide_after_success submit_button"><?php printf(__('Pay %s', WEB_INVOICE_TRANS_DOMAIN), $invoice->display('display_amount')); ?></button>
	</li>
</ol>
<br class="cb" />
&nbsp;
<div id="wp_cc_response"></div>
</fieldset>
</form>
</div>
		<?php
}

function web_invoice_show_recurring_info($invoice_id) {
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	?>
<div id="recurring_info" class="clearfix">
<h2 id="web_invoice_welcome_message" class="invoice_page_subheading"><?php printf(__('Welcome, %s!', WEB_INVOICE_TRANS_DOMAIN), $invoice->recipient('callsign')); ?></h2>
<?php if($invoice->display('description')) { ?>
<p><?php echo $invoice->display('description');  ?></p>
<?php  } ?>

<p class="recurring_info_breakdown"><?php printf(__('This is a recurring invoice, id: <b>%s</b>.', WEB_INVOICE_TRANS_DOMAIN), $invoice->display('display_id')); ?></p>
<p><?php printf(__('You will be billed %1$s in the amount of %2$s ', WEB_INVOICE_TRANS_DOMAIN), $invoice->display('display_billing_rate'), $invoice->display('display_amount'));

// Determine if starting now or t a set date
if (web_invoice_meta($invoice_id,'web_invoice_subscription_start_day') != '' && web_invoice_meta($invoice_id,'web_invoice_subscription_start_month')  != '' && web_invoice_meta($invoice_id,'web_invoice_subscription_start_year'  != ''))
echo web_invoice_meta($invoice_id,'web_invoice_subscription_start_day') .", ". web_invoice_meta($invoice_id,'web_invoice_subscription_start_month') .", ".  web_invoice_meta($invoice_id,'web_invoice_subscription_start_year');
?>.</p>
<?php if($invoice->display('due_date')) { ?>
<p class="web_invoice_due_date"><?php printf(__("Due Date: %s", WEB_INVOICE_TRANS_DOMAIN), $invoice->display('due_date')); ?></p><?php } ?>
<?php 	
//Billing Business Address
if(get_option('web_invoice_show_business_address') == 'yes') web_invoice_show_business_address();
if(get_option('web_invoice_show_billing_address') == 'yes') web_invoice_show_billing_address($invoice_id);
?>
<?php echo web_invoice_draw_itemized_table($invoice_id); ?></div>
<?php
}


function web_invoice_draw_user_selection_form($user_id) {
	global $wpdb, $blog_id;
	$_SESSION['last_new_invoice'] = true;
	?>

<div class="postbox" id="wp_new_web_invoice_div">
<div class="inside">
<form action="admin.php?page=new_web_invoice" method='POST'>
<table class="form-table" id="get_user_info">
	<tr class="invoice_main">
		<th><?php if(isset($user_id)) { _e("Start New Invoice For: ", WEB_INVOICE_TRANS_DOMAIN); } else { _e("Create New Invoice For: ", WEB_INVOICE_TRANS_DOMAIN); } ?></th>
		<td><select name='user_id' class='user_selection'>
			<option></option>
			<?php
			if (is_dir(WP_CONTENT_DIR . '/mu-plugins')) {
				$prefix = $wpdb->base_prefix;
				$get_all_users = $wpdb->get_results("SELECT * FROM {$prefix}users LEFT JOIN {$prefix}usermeta on {$prefix}users.id={$prefix}usermeta.user_id WHERE {$prefix}usermeta.meta_key='primary_blog' and {$prefix}usermeta.meta_value = {$blog_id} ORDER BY {$prefix}usermeta.meta_value");
			} else {
				$prefix = $wpdb->prefix;
				$get_all_users = $wpdb->get_results("SELECT ID FROM {$prefix}users ORDER BY {$prefix}users.user_nicename");
			}
			
			foreach ($get_all_users as $user)
			{
				$profileuser = get_user_to_edit($user->ID);
				echo "<option ";
				if(isset($user_id) && $user_id == $user->ID) echo " SELECTED ";
				if(!empty($profileuser->last_name) && !empty($profileuser->first_name)) { echo " value=\"".$user->ID."\">". $profileuser->last_name. ", " . $profileuser->first_name . " (".$profileuser->user_email.")</option>\n";  }
				else
				{
					echo " value=\"".$user->ID."\">". $profileuser->user_login. " (".$profileuser->user_email.")</option>\n";
				}
			}
			?>
		</select> <input type='submit' class='button'
			id="web_invoice_create_new_web_invoice"
			value='<?php _e("Create New Invoice", WEB_INVOICE_TRANS_DOMAIN); ?>' />


			<?php if(web_invoice_number_of_invoices() > 0) { ?><span
			id="web_invoice_copy_invoice" class="web_invoice_click_me"><?php _e("copy from another", WEB_INVOICE_TRANS_DOMAIN); ?></span>
		<br />


		<div class="web_invoice_copy_invoice"><?php 	$all_invoices = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('main')); ?>
		<select name="copy_from_template">
			<option SELECTED value=""></option>
			<?php 	foreach ($all_invoices as $invoice) {
				$profileuser = get_user_to_edit($invoice->user_id);
				?>
			<option value="<?php echo $invoice->invoice_num; ?>"><?php if(web_invoice_recurring($invoice->invoice_num)) { _e("(recurring)", WEB_INVOICE_TRANS_DOMAIN); } ?>
			<?php echo $invoice->subject . " - $" .$invoice->amount; ?></option>

			<?php } ?>

		</select><input type='submit' class='button'
			value='<?php _e("New Invoice from Template", WEB_INVOICE_TRANS_DOMAIN); ?>' />
		<span id="web_invoice_copy_invoice_cancel"
			class="web_invoice_click_me"><?php _e("cancel", WEB_INVOICE_TRANS_DOMAIN); ?></span>
		</div>
		<?php }

		if(!isset($user_id)) { _e("User must have a profile to receive invoices.", WEB_INVOICE_TRANS_DOMAIN);

		if(current_user_can('create_users')) { if($GLOBALS['wp_version'] < '2.7') { echo "<a href=\"users.php\">".__("Create a new user account.", WEB_INVOICE_TRANS_DOMAIN)."</a>";  }
		else {
			echo "<a href=\"user-new.php\">".__("Create a new user account.", WEB_INVOICE_TRANS_DOMAIN)."</a>";
		} } }	 ?></td>
	</tr>

</table>
</form>
</div>
</div>
		<?php
}

function web_invoice_create_paypal_itemized_list($itemized_array,$invoice_id) {
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	$tax = $invoice->display('tax_percent');
	$amount = $invoice->display('amount');
	$display_id = $invoice->display('display_id');

	$tax_free_sum = 0;
	$counter = 1;
	foreach($itemized_array as $itemized_item) {

		// If we have a negative item, PayPal will not accept, we must group everything into one amount
		if($itemized_item[price] * $itemized_item[quantity] <= 0) {
			$tax = 0;
			$output = "
			<input type='hidden' name='item_name' value='Reference Invoice #$display_id' /> \n
			<input type='hidden' name='amount' value='$amount' />\n";

			$single_item = true;
			break;
		}

		$output .= "<input type='hidden' name='item_name_$counter' value='".$itemized_item[name]."' />\n";
		$output .= "<input type='hidden' name='amount_$counter' value='".$itemized_item[price] * $itemized_item[quantity]."' />\n";

		$tax_free_sum = $tax_free_sum + $itemized_item[price] * $itemized_item[quantity];
		$counter++;
	}

	// Add tax onnly by using tax_free_sum (which is the sums of all the individual items * quantities.
	if(!empty($tax)) {
		$tax_cart = round($tax_free_sum * ($tax / 100),2);
		$output .= "<input type='hidden' name='tax_cart' value='". $tax_cart ."' />\n";
	}

	if($single_item) $output .= "<input type='hidden' name='cmd' value='_xclick' />\n";
	if(!$single_item) $output .= "
	<input type='hidden' name='cmd' value='_ext-enter' />
	<input type='hidden' name='redirect_cmd' value='_cart' />\n";
	return $output;
}

function web_invoice_pfp_convert_interval($length, $val) {
	switch ($val) {
		case 'months':
			return 'Month';
		case 'days':
			return 'Day';
		default: return 'Day';
	}
}

function web_invoice_pfp_wpppe_convert_interval($length, $val) {
	switch ($val) {
		case 'months':
			if ($length == 3) {
				return 'QTER';
			}
			if ($length == 6) {
				return 'SMYR';
			}
			return 'MONT';
		case 'years':
			return 'YEAR';
		case 'days':
			if ($length == 7) {
				return 'WEEK';
			}
			if ($length == 14) {
				return 'BIWK';
			}
			if ($length == 28) {
				return 'FRWK';
			}
		default: return 'DAY';
	}
}

function web_invoice_google_checkout_convert_interval($length, $val) {
	switch ($val) {
		case 'months':
			switch ($length) {
				case 1: return 'MONTHLY';
				case 2: return 'EVERY_TWO_MONTHS';
				case 3: return 'QUARTERLY';
				case 12: return 'YEARLY';
				default: return 'DAILY';
			}
		case 'days':
			switch ($length) {
				case 7: return 'WEEKLY';
				case 14: return 'SEMI_MONTHLY';
				case 30: return 'MONTHLY';
				case 31: return 'MONTHLY';
				default: return 'DAILY';
			}
		default: return 'DAILY';
	}
}

function web_invoice_create_google_checkout_itemized_list($itemized_array, $invoice_id, $recurring) {
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	$tax = $invoice->display('tax_percent');
	$amount = $invoice->display('amount');
	$display_id = $invoice->display('display_id');
	$currency = $invoice->display('currency');
	$single_item = false;

	$tax_free_sum = 0;
	$counter = 1;

	if (empty($tax) && count($itemized_array) >  3) {
		$single_item = true;
	} else if (count($itemized_array) >  2) {
		$single_item = true;
	}
	
	$desc = array();
	
	foreach($itemized_array as $itemized_item) {
		$tax_free_sum = $tax_free_sum + $itemized_item[price] * $itemized_item[quantity];
		
		$desc[] = "{$itemized_item[name]} {$itemized_item[quantity]} @ {$currency} ".number_format($itemized_item[price], 2);
	}
	
	$desc = join(', ', $desc);
	
	if ((get_option('web_invoice_google_checkout_level2') == 'True') && !$recurring) {
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.item-name' value='Invoice #{$display_id} ' />\n";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.item-description' value='{$invoice->display('subject')}' />\n";
					
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.quantity' value='1' />\n";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.unit-price.currency' value='{$currency}' />\n";
					
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.unit-price' value='0' />\n";

			$counter++;
	} else {
		if ($recurring) {
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.item-name' value='Recurring invoice #{$display_id}: ".$invoice->display('subscription_name')."' />\n";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.item-description' value='{$desc}' />\n";
				
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.quantity' value='1' />\n";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.unit-price.currency' value='{$currency}' />\n";
			
			if (strtotime($invoice->display('startDate')) != strtotime(date('Y-m-d'))) {
				$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.unit-price' value='0' />\n";
				$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.start-date' value='".date('Y-m-d', strtotime($invoice->display('startDate')))."' />";
			} else {
				$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.unit-price' value='".number_format($tax_free_sum, 2, null, '')."' />\n";
			}
			
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.type' value='google'/>";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.period' value='".web_invoice_google_checkout_convert_interval($invoice->display('interval_length'), $invoice->display('interval_unit'))."' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.payments.subscription-payment-1.times' value='{$invoice->display('totalOccurrences')}' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.payments.subscription-payment-1.maximum-charge' value='".number_format($tax_free_sum, 2, null, '')."' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.payments.subscription-payment-1.maximum-charge.currency' value='{$currency}' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.recurrent-item.item-name' value='Recurring invoice #{$display_id}' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.recurrent-item.item-description' value='{$desc}' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.recurrent-item.item-id.merchant-item-id' value='{$invoice_id}-rec' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.recurrent-item.quantity' value='1' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.recurrent-item.unit-price' value='".number_format($tax_free_sum, 2, null, '')."' />";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.subscription.recurrent-item.unit-price.currency' value='{$currency}' />";
			
			$counter++;
		}
	}

	foreach($itemized_array as $itemized_item) {
		if (!$recurring) {
			
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.item-name' value='{$itemized_item[name]}' />\n";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.item-description' value='{$itemized_item[description]}' />\n";
				
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.quantity' value='{$itemized_item[quantity]}' />\n";
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.unit-price.currency' value='{$currency}' />\n";
				
			$output .= "<input type='hidden' name='shopping-cart.items.item-{$counter}.unit-price' value='".number_format($itemized_item[price], 2, null, '')."' />\n";
		}
			
		$counter++;
	}

	
	if(!empty($tax)) {
		if (get_option('web_invoice_google_checkout_tax_state') != 'UK') {
			$output .= "<input type='hidden' name='tax_rate' value='".($tax/100)."' />\n";
			$output .= "<input type='hidden' name='tax_us_state' value='".get_option('web_invoice_google_checkout_tax_state')."' />\n";
		} else {
			$output .= "<input type='hidden' name='checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.rate' value='".($tax/100)."' />\n";
	   		$output .= "<input type='hidden' name='checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.tax-area.postal-area.country-code' value='GB' />\n";
		}
	}

	return $output;
}

function web_invoice_create_moneybookers_itemized_list($itemized_array,$invoice_id) {
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	$tax = $invoice->display('tax_percent');
	$amount = $invoice->display('amount');
	$display_id = $invoice->display('display_id');
	$single_item = false;

	$tax_free_sum = 0;
	$counter = 1;

	if (empty($tax) && count($itemized_array) >  3) {
		$single_item = true;
	} else if (count($itemized_array) >  2) {
		$single_item = true;
	}

	foreach($itemized_array as $itemized_item) {
		if (!$single_item) {
			$output .= "<input type='hidden' name='detail{$counter}_description' value='".$itemized_item[name]."' />\n";
			$output .= "<input type='hidden' name='detail{$counter}_text' value='".$itemized_item[description]."' />\n";

			$counter++;

			$output .= "<input type='hidden' name='amount{$counter}' value='".$itemized_item[price] * $itemized_item[quantity]."' />\n";
		}

		$tax_free_sum = $tax_free_sum + $itemized_item[price] * $itemized_item[quantity];
	}

	// Add tax only by using tax_free_sum (which is the sums of all the individual items * quantities.
	if(!$single_item && !empty($tax)) {
		$tax_cart = round($tax_free_sum * ($tax / 100),2);
		$output .= "<input type='hidden' name='detail{$counter}_description' value='Tax' />\n";
		$output .= "<input type='hidden' name='detail{$counter}_text' value='({$tax} %)' />\n";
		$counter++;
		$output .= "<input type='hidden' name='amount{$counter}' value='". $tax_cart ."' />\n";
	}
	
	$output .= "<input type='hidden' name='detail1_description' value='Reference Invoice #:' />\n";
	$output .= "<input type='hidden' name='detail1_text' value='$display_id' />\n";

	return $output;
}

function web_invoice_create_alertpay_itemized_list($itemized_array,$invoice_id) {
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	$tax = $invoice->display('tax_percent');
	$amount = $invoice->display('amount');
	$display_id = $invoice->display('display_id');

	$tax_free_sum = 0;
	$counter = 1;
	foreach($itemized_array as $itemized_item) {
		$counter++;
		$tax_free_sum = $tax_free_sum + $itemized_item[price] * $itemized_item[quantity];
	}

	$output = "
		<input type='hidden' name='ap_description' value='Reference Invoice # $display_id' /> \n
		<input type='hidden' name='ap_amount' value='$tax_free_sum' />\n
		<input type='hidden' name='ap_quantity' value='1' />\n";

	// Add tax only by using tax_free_sum (which is the sums of all the individual items * quantities.
	if(!empty($tax)) {
		$tax_cart = round($tax_free_sum * ($tax / 100),2);
		$output .= "<input type='hidden' name='ap_taxamount' value='". $tax_cart ."' />\n";
	}

	return $output;
}


function web_invoice_print_help($invoice_id) {
	global $web_invoice_print;
	
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	
	if (!$web_invoice_print) {
	?>
<div class="noprint"><p><?php print sprintf(__("You can download a %s or print a copy of this invoice for your records; just 
select the 'Print' item under the 'File' menu in your browser, or use the 
&lt;CTRL&gt; + 'P' key combination to print a hard-copy in a more traditional, 
neatly laid-out format. <em>Thank you</em> for your business <em>and</em> your prompt 
payment!", WEB_INVOICE_TRANS_DOMAIN), '<a href="'.$invoice->display('print_link').'" class="web_invoice_pdf_link">PDF</a>'); ?></p></div>
<?php } 
}

function web_invoice_generate_pdf_content($invoice_id) {
	global $post, $web_invoice_print;
	$web_invoice_print = true;
	
	ob_start();
	?>
	<style type="text/css">
		.noprint { display: none; }
		#invoice_page { width: 500px; margin: 0 auto; font-size: 11px; font-family: 'Trebuchet MS','Lucida Grande',Verdana,Tahoma,Arial; }
		th { text-align: left; font-size: 13px; padding: 5px; }
		td { font-size: 12px; vertical-align: top; padding: 5px; }
		tr td { background-color: #fefefe; }
		tr.alt_row  td { background-color: #eee; }
		span.description_text { color: #333; font-size: 0.8em; }
		tr.web_invoice_bottom_line { font-size: 1.1em; font-weight: bold; }
		table { width: 100%; }
		h2 { font-size: 1.1em; }
		h1 { text-align: center; }
		p { margin: 5px 0px; }
		div.clear { clear: both; }
		
		#invoice_client_info { width: 100%; text-align: right; padding-top: -145; }
		#invoice_business_info { width: 100%; text-align: left; height: 100; }
	</style>
	<?php
	
		do_action('web_invoice_front_top', $invoice_id);
	
		print '<div class="clear"></div>';
		
		//If this is not recurring invoice, show regular message
		if(!($recurring = web_invoice_recurring($invoice_id)))  web_invoice_show_invoice_overview($invoice_id);
	
		// Show this if recurring
		if($recurring)  web_invoice_show_recurring_info($invoice_id);
	
		if(web_invoice_paid_status($invoice_id)) {
			web_invoice_show_already_paid($invoice_id);
			do_action('web_invoice_front_paid', $invoice_id);
		} else {
			//Show Billing Information
			web_invoice_show_billing_information($invoice_id);
			do_action('web_invoice_front_unpaid', $invoice_id);
		}
		do_action('web_invoice_front_bottom', $invoice_id);
		?>
	<script type="text/php">
		if ( isset($pdf) ) {
    		$font = Font_Metrics::get_font("verdana", "bold");
			$font_light = Font_Metrics::get_font("verdana");
			$pdf->page_text(52, 810, "Powered by Web Invoice ".WEB_INVOICE_VERSION_NUM, $font_light, 10, array(0,0,0));
    		$pdf->page_text(510, 810, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0,0,0));
  		}
	</script>
	<?php
	$content = ob_get_contents();
	ob_clean();
	
	return $content;
}
