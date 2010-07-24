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

class Web_Invoice_Decider {

	var $message;
	var $ouput = "";

	function Web_Invoice_Decider($web_invoice_action = null) {
		global $wpdb, $web_invoice_memory_head_room;
		
		if (26214400 > $web_invoice_memory_head_room) {
			$this->message = sprintf(__("Less than 25MB of memory available for Web Invoice, please set <code>memory_limit = %s</code> in your".
			"<code>php.ini</code> if Web Invoice crashes unexpectedly", WEB_INVOICE_TRANS_DOMAIN), web_invoice_return_bytes_nice(web_invoice_return_bytes(ini_get('memory_limit'))+27000000));
		}

		$web_invoice_action = (!empty($_REQUEST['web_invoice_action']) ? $_REQUEST['web_invoice_action'] : $web_invoice_action);
		$invoice_id = $_REQUEST['invoice_id'];
		if (!$invoice_id) {
			$invoice_id = $_REQUEST['multiple_invoices'][0];
		}
		$web_invoice_recurring_billing = web_invoice_meta($invoice_id, 'web_invoice_recurring_billing');
		//echo "do this: " . $web_invoice_action;

		echo "<div class='wrap'>";
		switch($web_invoice_action)
		{
			case "save_and_preview":
				if(empty($invoice_id)) { web_invoice_show_message("Error - invoice id was not passed."); }
				else {
					web_invoice_show_message(web_invoice_process_invoice_update($invoice_id),'updated fade');
					if (web_invoice_meta($invoice_id, 'subscription_id') &&	web_invoice_meta($invoice_id, 'recurring_transaction_id')) {
						require_once('gateways/payflowpro.class.php');
						
						$pfp = new Web_Invoice_PayflowProRecurring();
						if (web_invoice_meta($invoice_id, 'web_invoice_recurring_billing')){
							$pfp->updateProfile($invoice_id);
							web_invoice_update_log($invoice_id, 'pfp_subscription_update', "Subscription updated. REF: ".$pfp->getRef());
						} else {
							if ($pfp->deleteProfile(web_invoice_meta($invoice_id, 'subscription_id'))) {
								web_invoice_update_log($invoice_id, 'pfp_subscription_update', "Subscription cancelled. REF: ".$pfp->getRef());
								web_invoice_update_invoice_meta($invoice_id, 'pfp_status', 'cancelled');
								web_invoice_delete_invoice_meta($invoice_id, 'subscription_id');
							}
						}
					}
					web_invoice_saved_preview($invoice_id);
					do_action('web_invoice_invoice_save', $invoice_id);
				}
				break;

			case "clear_log":
				web_invoice_show_message(web_invoice_clear_invoice_status($invoice_id),'updated fade');
				web_invoice_options_manageInvoice($invoice_id);
				break;
				
			case "doPausePfp":
				if (web_invoice_meta($invoice_id, 'subscription_id') &&	web_invoice_meta($invoice_id, 'recurring_transaction_id')) {
					require_once('gateways/payflowpro.class.php');
						
					$pfp = new Web_Invoice_PayflowProRecurring();
					if (web_invoice_meta($invoice_id, 'web_invoice_recurring_billing')) {
						$profile_id = web_invoice_meta($invoice_id, 'subscription_id');
						if ($pfp->pauseProfile($profile_id)) {
							web_invoice_update_log($invoice_id, 'pfp_subscription_update', "Subscription paused. REF: ".$pfp->getRef());
							web_invoice_update_invoice_meta($invoice_id, 'pfp_status', 'paused');
							web_invoice_delete_invoice_meta($invoice_id,'subscription_id');
							do_action('web_invoice_invoice_pause_recurring', $invoice_id);
							$message = 'Paused subscription.';
						} else {
							$message = 'Failed to pause subscription.';
						}
						$message .= " <a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=".$invoice_id."'>Continue editing</a>";
						web_invoice_show_message($message, 'updated fade');
					}
				}
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;
				
			case "doRestartRecurringPfp":
				if (web_invoice_meta($invoice_id, 'recurring_transaction_id')) {
					require_once('gateways/payflowpro.class.php');
						
					$pfp = new Web_Invoice_PayflowProRecurring();
					if (web_invoice_meta($invoice_id, 'web_invoice_recurring_billing')){
						$profile_id = web_invoice_meta($invoice_id, 'recurring_transaction_id');
						if ($pfp->reactivateProfile($profile_id, $invoice_id)) {
							web_invoice_update_log($invoice_id, 'pfp_subscription_update', "Subscription reactivated. REF: ".$pfp->getRef());
							web_invoice_update_invoice_meta($invoice_id, 'pfp_status', 'active');
							web_invoice_update_invoice_meta($invoice_id, 'subscription_id', $profile_id);
							do_action('web_invoice_invoice_restart_recurring', $invoice_id);
							$message = 'Reactivated subscription.';
						} else {
							$message = 'Failed to reactivate subscription.';
						}
						$message .= " <a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=".$invoice_id."'>Continue editing</a>";
						web_invoice_show_message($message, 'updated fade');
					}
				}
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;
				
			case "complete_removal":
				web_invoice_complete_removal();
				web_invoice_show_settings();
				break;

			case "doInvoice":
				if(isset($invoice_id)) { web_invoice_options_manageInvoice($invoice_id); }
				else {	web_invoice_options_manageInvoice();	}
				break;

			case "overview":
				web_invoice_default();
				break;
				
			case "user_overview":
				web_invoice_user_default();
				break;

			case "web_invoice_show_welcome_message":
				web_invoice_show_welcome_message();
				break;

			case "web_invoice_recurring_billing":
				web_invoice_recurring_overview();
				break;

			case "send_now":
				web_invoice_show_message(web_invoice_send_email($invoice_id));
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;

			case "first_setup":
				if(isset($_POST['web_invoice_web_invoice_page'])) update_option('web_invoice_web_invoice_page', $_POST['web_invoice_web_invoice_page']);
				if(isset($_POST['web_invoice_payment_method'])) update_option('web_invoice_payment_method', join($_POST['web_invoice_payment_method'],','));
				if(isset($_POST['web_invoice_gateway_username'])) update_option('web_invoice_gateway_username', $_POST['web_invoice_gateway_username']);
				if(isset($_POST['web_invoice_gateway_tran_key'])) update_option('web_invoice_gateway_tran_key', $_POST['web_invoice_gateway_tran_key']);
				if(isset($_POST['web_invoice_gateway_merchant_email'])) update_option('web_invoice_gateway_merchant_email', $_POST['web_invoice_gateway_merchant_email']);
				// PayPal
				if(isset($_POST['web_invoice_paypal_address'])) update_option('web_invoice_paypal_address', $_POST['web_invoice_paypal_address']);
				if(isset($_POST['web_invoice_paypal_only_button'])) update_option('web_invoice_paypal_only_button', $_POST['web_invoice_paypal_only_button']);
				if(isset($_POST['web_invoice_paypal_sandbox'])) update_option('web_invoice_paypal_sandbox', $_POST['web_invoice_paypal_sandbox']);
				// Payflow
				if(isset($_POST['web_invoice_payflow_login'])) update_option('web_invoice_payflow_login', $_POST['web_invoice_payflow_login']);
				if(isset($_POST['web_invoice_payflow_partner'])) update_option('web_invoice_payflow_partner', $_POST['web_invoice_payflow_partner']);
				if(isset($_POST['web_invoice_payflow_only_button'])) update_option('web_invoice_payflow_only_button', $_POST['web_invoice_payflow_only_button']);
				if(isset($_POST['web_invoice_payflow_silent_post'])) update_option('web_invoice_payflow_silent_post', $_POST['web_invoice_payflow_silent_post']);
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

				web_invoice_options_manageInvoice();
				break;

			case "web_invoice_settings":
				web_invoice_process_settings();
				web_invoice_show_settings();
				break;

			case "web_invoice_email_templates":
				web_invoice_process_email_templates();
				web_invoice_show_email_templates();
				break;

			case "delete_invoice":
				web_invoice_show_message(web_invoice_delete($_REQUEST['multiple_invoices']));
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;
				
			case "send_invoice":
				if(empty($_REQUEST['multiple_invoices'])) { web_invoice_show_message("No invoices selected, nothing sent."); }
				else { web_invoice_show_message(web_invoice_send_email($_REQUEST['multiple_invoices']), 'updated fade'); }
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;

			case "send_reminder":
				if(empty($_REQUEST['multiple_invoices'])) { web_invoice_show_message("No invoices selected, no reminder sent."); }
				else { web_invoice_show_message(web_invoice_send_email($_REQUEST['multiple_invoices'], 'reminder'), 'updated fade'); }
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;

			case "archive_invoice":
				if(empty($_REQUEST['multiple_invoices'])) { web_invoice_show_message("No invoices selected, nothing archived."); }
				else { web_invoice_show_message(web_invoice_archive($_REQUEST['multiple_invoices']), 'updated fade'); }
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;

			case "unarchive_invoice":
				if(empty($_REQUEST['multiple_invoices'])) { web_invoice_show_message("No invoices selected, nothing un-archived."); }
				else { web_invoice_show_message(web_invoice_unarchive($_REQUEST['multiple_invoices']), 'updated fade'); }
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;

			case "mark_as_paid":
				if(empty($_REQUEST['multiple_invoices'])) { web_invoice_show_message("No invoices selected, nothing marked as paid."); }
				else { web_invoice_show_message(web_invoice_mark_as_paid($_REQUEST['multiple_invoices']), 'updated fade'); }
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;

			case "mark_as_sent":
				if(empty($_REQUEST['multiple_invoices'])) { web_invoice_show_message("No invoices selected, nothing marked as sent.."); }
				else { web_invoice_show_message(web_invoice_mark_as_sent($_REQUEST['multiple_invoices']), 'updated fade'); }
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}
				break;

			case "save_not_send":
				// Already saved, this just shows a message
				$web_invoice_custom_invoice_id = web_invoice_meta($invoice_id, 'web_invoice_custom_invoice_id');

				if($web_invoice_custom_invoice_id) {$message =  "Invoice <b>$web_invoice_custom_invoice_id</b> saved.";}
				else { 	$message =  "Invoice <b>#" . $invoice_id . "</b> saved.";	}
				$message .= " <a href=".web_invoice_build_invoice_link($invoice_id) .">View Web Invoice</a>";

				web_invoice_show_message($message,' updated fade');
				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}

				break;

			default:

				if($web_invoice_recurring_billing) { web_invoice_recurring_overview(); } else { web_invoice_default();}

				break;
		}
		echo "</div>";

	}

	function display() {
		echo "<div class=\"wrap\">";
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo isset($this->output)?$this->output:'';
		echo "</div>";
	}

}
