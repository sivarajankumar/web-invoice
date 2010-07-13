<?php
/*
 Plugin Name: Web Invoice
 Plugin URI: http://mohanjith.com/wordpress/web-invoice.html
 Description: Send itemized web invoices directly to your clients.  Credit card payments may be accepted via Authorize.net, MerchantPlus NaviGate, Moneybookers, AlertPay, Google Checkout or PayPal account. Recurring billing is also available via Authorize.net's ARB, Moneybookers, Google Checkout and PayPal. Visit <a href="admin.php?page=web_invoice_settings">Web Invoice Settings Page</a> to setup.
 Author: S H Mohanjith
 Version: 2.0.7
 Author URI: http://mohanjith.com/
 Text Domain: web-invoice
 Stable tag: 2.0.7
 License: GPL

 Copyright 2010  S H Mohanjith (email : moha@mohanjith.net)
 */

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

define("WEB_INVOICE_VERSION_NUM", "2.0.7");
define("WEB_INVOICE_TRANS_DOMAIN", "web-invoice");

require_once "Flow.php";
require_once "Functions.php";
require_once "Display.php";
require_once "Frontend.php";

global $web_invoice, $web_invoice_print, $web_invoice_memory_head_room;

$web_invoice_memory_head_room = web_invoice_return_bytes(ini_get('memory_limit'))-memory_get_usage();

$web_invoice_print = false;

$web_invoice = new Web_Invoice();
$web_invoice->security();

class Web_Invoice {

	var $Invoice;
	var $web_invoice_user_level = array('administrator');
	var $uri;
	var $the_path;
	var $message;

	function the_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		return $path;
	}

	function frontend_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		if(get_option('web_invoice_force_https') == 'true') $path = str_replace('http://','https://',$path);
		return $path;
	}

	function Web_Invoice() {

		$version = get_option('web_invoice_version');
		$_file = "web-invoice/" . basename(__FILE__);

		$this->path = dirname(__FILE__);
		$this->file = basename(__FILE__);
		$this->directory = basename($this->path);
		$this->uri = WP_PLUGIN_URL."/".$this->directory;
		$this->the_path = $this->the_path();

		add_action('init',  array($this, 'init'), 0);
		add_action('profile_update','web_invoice_profile_update');
		add_action('edit_user_profile', 'web_invoice_user_profile_fields');
		add_action('show_user_profile', 'web_invoice_user_profile_fields');

		add_action('wp', array($this, 'api'));

		register_activation_hook($_file, array(&$this, 'install'));
		register_deactivation_hook($_file, array(&$this, 'uninstall'));

		add_action('admin_head', array($this, 'admin_head'));
		add_action('contextual_help', 'web_invoice_contextual_help_list');
		add_action('wp_head', 'web_invoice_frontend_css');

		add_filter('favorite_actions', array(&$this, 'favorites'));
		add_action('admin_menu', array($this, 'web_invoice_add_pages'));

		if (strcasecmp(get_option('web_invoice_payment_method'), 'cc') || strcasecmp(get_option('web_invoice_payment_method'), 'pfp')) {
			add_action('wp_head', 'web_invoice_frontend_js');
		}

		add_filter('the_content', 'web_invoice_the_content');
		add_filter('web_invoice_email_variables', 'web_invoice_email_variables');
		add_filter('web_invoice_pdf_variables', 'web_invoice_pdf_variables');
		add_filter('web_invoice_web_variables', 'web_invoice_web_variables');
		add_filter('wp_redirect', array($this, 'redirect'));

		$this->SetUserAccess(get_option('web_invoice_user_level'));
	}

	function SetUserAccess($level = 8) {
		if (is_array($level) && count($level) > 0) {
			$this->web_invoice_user_level = 'manage_web_invoice';
		} else {
			$this->web_invoice_user_level = $level;
		}
	}
	
	function redirect($location) {
		if (get_option('web_invoice_redirect_after_user_add') == 'yes' && preg_match('/^users\.php\?usersearch/', $location) > 0) {
			return 'admin.php?page=new_web_invoice';
		}
		return $location;
	}

	static function tablename ($table) {
		global $table_prefix;
		return $table_prefix.'web_invoice_'.$table;
	}

	function admin_head() {
		echo "<link rel='stylesheet' href='".$this->uri."/css/wp_admin.css?v=1.11.13' type='text/css'type='text/css' media='all' />";
	}

	function web_invoice_add_pages() {
		$file = "web-invoice/" . basename(__FILE__);

		add_menu_page(__('Web Invoice System', WEB_INVOICE_TRANS_DOMAIN), __('Web Invoice', WEB_INVOICE_TRANS_DOMAIN),  $this->web_invoice_user_level, $file, array(&$this,'invoice_overview'),$this->uri."/images/web_invoice.png");
		add_submenu_page($file, __("Recurring Billing", WEB_INVOICE_TRANS_DOMAIN), __("Recurring Billing", WEB_INVOICE_TRANS_DOMAIN), $this->web_invoice_user_level, 'web_invoice_recurring_billing', array(&$this,'recurring'));
		add_submenu_page($file, __("Manage Invoice", WEB_INVOICE_TRANS_DOMAIN), __("New Invoice", WEB_INVOICE_TRANS_DOMAIN), $this->web_invoice_user_level, 'new_web_invoice', array(&$this,'new_web_invoice'));
		add_submenu_page($file, __("E-mail templates", WEB_INVOICE_TRANS_DOMAIN), __("E-mail templates", WEB_INVOICE_TRANS_DOMAIN), $this->web_invoice_user_level, 'web_invoice_email_templates', array(&$this,'email_template_page'));
		// add_submenu_page($file, __("Items/Inventory"), __("Items"), $this->web_invoice_user_level, 'web_invoice_inventory_items', array(&$this,'inventory_items_page'));
		add_submenu_page($file, __("Settings", WEB_INVOICE_TRANS_DOMAIN), __("Settings", WEB_INVOICE_TRANS_DOMAIN), $this->web_invoice_user_level, 'web_invoice_settings', array(&$this,'settings_page'));
	
		add_submenu_page('profile.php', __("Your invoices", WEB_INVOICE_TRANS_DOMAIN), __("Invoices", WEB_INVOICE_TRANS_DOMAIN), 'subscriber', 'user_invoice_overview', array(&$this,'user_invoice_overview'));
	}

	function security() {
		//More to come later
		if(($_REQUEST['eqdkp_data'])) {setcookie('eqdkp_data'); };
	}

	function new_web_invoice() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('doInvoice');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function favorites($actions) {
		$key = 'admin.php?page=new_web_invoice';
		$actions[$key] = array(__('New Invoice', WEB_INVOICE_TRANS_DOMAIN),$this->web_invoice_user_level);
		return $actions;
	}

	function recurring() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('web_invoice_recurring_billing');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function api() {
		if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page'))) {
			if((get_option('web_invoice_moneybookers_merchant') == 'True') && isset($_POST['mb_transaction_id']) && isset($_POST['status'])) {
				require_once("gateways/moneybookers.class.php");
				$moneybookers_obj = new Web_Invoice_Moneybookers($_POST['transaction_id']);
				$moneybookers_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			} else if((get_option('web_invoice_alertpay_merchant') == 'True') && isset($_POST['ap_itemname']) && isset($_POST['ap_securitycode'])) {
				require_once("gateways/alertpay.class.php");
				$alertpay_obj = new Web_Invoice_AlertPay($_POST['ap_itemname']);
				$alertpay_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			} else if((get_option('web_invoice_google_checkout_level2') == 'True') && isset($_POST['_type'])) {
				require_once("gateways/googlecheckout.class.php");
				$gc_obj = new Web_Invoice_GoogleCheckout($_POST['_type'], $_POST);
				$gc_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			} else if((get_option('web_invoice_payflow_silent_post') == 'True') && isset($_POST['PNREF'])) {
				require_once("gateways/payflow.class.php");
				$pf_obj = new Web_Invoice_Payflow($_POST['CUSTID'], $_POST);
				$pf_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			} else if (isset($_GET['crypt'])) {
				require_once("gateways/sagepay.class.php");
				$pf_obj = new Web_Invoice_SagePay($_GET['crypt']);
				$pf_obj->processRequest($_SERVER['REMOTE_ADDR']);
			} else if (isset($_GET['paypal_ipn'])) {
				require_once("gateways/paypal.class.php");
				$pf_obj = new Web_Invoice_Paypal($_GET['invoice_id']);
				$pf_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			}
			
			wp_enqueue_script('jquery');
			wp_enqueue_script('web-invoice',$this->uri."/js/web-invoice-frontend.js", array('jquery'), '1.11.13');
				
			// Make sure proper MD5 is being passed (32 chars), and strip of everything but numbers and letters
			if(isset($_GET['invoice_id']) && strlen($_GET['invoice_id']) != 32) unset($_GET['invoice_id']);
			$_GET['invoice_id'] = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['invoice_id']);
				
			// Make sure proper MD5 is being passed (32 chars), and strip of everything but numbers and letters
			if (isset($_GET['generate_from']) && strlen($_GET['generate_from']) != 32) unset($_GET['generate_from']);
			$_GET['generate_from'] = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['generate_from']);
				
			if (isset($_GET['generate_from']) && !empty($_GET['generate_from']) && (get_option('web_invoice_self_generate_from_template') == "yes")) {
				global $current_user;
				get_currentuserinfo();
					
				if ($current_user->ID > 0) {
					// Convert MD5 hash into Actual Invoice ID
					$template_id = web_invoice_md5_to_invoice($_GET['generate_from']);
					$invoice_id = web_invoice_self_generate_from_template($template_id, $current_user->ID);
						
					$web_invoice_getinfo = new Web_Invoice_GetInfo($invoice_id);
					wp_redirect($web_invoice_getinfo->display('link'));
					
					exit(0);
				}
			}
			
			if (isset($_GET['print'])) {
				web_invoice_print_pdf();
			}
		}
	}

	function invoice_overview() {
		$web_invoice_web_invoice_page = get_option("web_invoice_web_invoice_page");

		$Web_Invoice_Decider = new Web_Invoice_Decider('overview');

		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		if(!function_exists('curl_exec')) echo "<div id=\"message\" class='error' ><p>".__('cURL is not turned on on your server, credit card processing will not work. If you have access to your php.ini file, activate <b>extension=php_curl.dll</b>.', WEB_INVOICE_TRANS_DOMAIN)."</p></div>";
		echo $Web_Invoice_Decider->display();
	}
	
	function user_invoice_overview() {
		$web_invoice_web_invoice_page = get_option("web_invoice_web_invoice_page");

		$Web_Invoice_Decider = new Web_Invoice_Decider('user_overview');

		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function settings_page() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('web_invoice_settings');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function email_template_page() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('web_invoice_email_templates');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}
	
	function inventory_items_page() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('web_invoice_inventory_items');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function init() {
		global $wpdb, $wp_version;

		if (version_compare($wp_version, '2.6', '<')) // Using old WordPress
			load_plugin_textdomain(WEB_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages');
		else
			load_plugin_textdomain(WEB_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages', dirname(plugin_basename(__FILE__)).'/languages');

		if(is_admin()) {
			wp_enqueue_script('jquery');
		
			wp_enqueue_script('jquery.maskedinput',$this->uri."/js/jquery.maskedinput.js", array('jquery'));
			wp_enqueue_script('jquery.form',$this->uri."/js/jquery.form.js", array('jquery') );
			wp_enqueue_script('jquery.impromptu',$this->uri."/js/jquery-impromptu.1.7.js", array('jquery'), '1.8.0');
			wp_enqueue_script('jquery.field',$this->uri."/js/jquery.field.min.js", array('jquery'), '1.8.0');
			wp_enqueue_script('jquery.delegate',$this->uri."/js/jquery.delegate.js", array('jquery'), '1.8.0');
			wp_enqueue_script('jquery.calculation',$this->uri."/js/jquery.calculation.min.js", array('jquery'), '1.8.0');
			wp_enqueue_script('jquery.tablesorter',$this->uri."/js/jquery.tablesorter.min.js", array('jquery'), '1.8.0');
			wp_enqueue_script('jquery.autogrow-textarea',$this->uri."/js/jquery.autogrow-textarea.js", array('jquery'), '1.8.0');
			wp_enqueue_script('web-invoice',$this->uri."/js/web-invoice.js", array('jquery'), '2.0.4');
		} else {
			if(isset($_POST['web_invoice_id_hash'])) {

				$md5_invoice_id = $_POST['web_invoice_id_hash'];
				$invoice_id = web_invoice_md5_to_invoice($md5_invoice_id);
				
				//Check to see if this is a credit card transaction, if so process
				if(web_invoice_does_invoice_exist($invoice_id)) { web_invoice_process_cc_transaction($_POST); exit(0); }
			}
			
			if (isset($_GET['invoice_id'])) {
	
				$md5_invoice_id = $_GET['invoice_id'];
	
				// Convert MD5 hash into Actual Invoice ID
				$invoice_id = web_invoice_md5_to_invoice($md5_invoice_id);
	
				//Check if invoice exists, SSL enforcement is setp, and we are not currently browing HTTPS,  then reload page into HTTPS
				if(!function_exists('wp_https_redirect')) {
					if(	web_invoice_does_invoice_exist($invoice_id) && get_option('web_invoice_force_https') == 'true'
						&& $_SERVER['HTTPS'] != "on" && preg_match('/^https/', get_option('siteurl')) == 0) {
						$host_x = preg_split('/\//', get_option('siteurl'));
						$host = $host_x[2];  
						header("Location: https://". $host . $_SERVER['REQUEST_URI']); 
						exit(0);
					}
				}
			}
		}
		if(empty($_GET['invoice_id'])) unset($_GET['invoice_id']);
	}

	function uninstall() {
		global $wpdb;
	}

	function install() {
		global $wpdb;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		if ( ! empty($wpdb->charset) )
        	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
        	$charset_collate .= " COLLATE $wpdb->collate";
		

		//change old table name to new one
		if($wpdb->get_var("SHOW TABLES LIKE 'web_invoice'")) {
			global $table_prefix;
			$sql_update = "RENAME TABLE ".$table_prefix."invoice TO ". Web_Invoice::tablename('main')."";
			$wpdb->query($sql_update);
		}

		//if($wpdb->get_var("SHOW TABLES LIKE '". Web_Invoice::tablename('main') ."'") != Web_Invoice::tablename('main')) {
		$sql_main = "CREATE TABLE IF NOT EXISTS ". Web_Invoice::tablename('main') ." (
				  id int(11) NOT NULL auto_increment,
				  amount double default '0',
				  description text NOT NULL,
				  invoice_num varchar(45) NOT NULL default '',
				  user_id varchar(20) NOT NULL default '',
				  subject text NOT NULL,
				  itemized text NOT NULL,
				  status int(11) NOT NULL,
				  PRIMARY KEY  (id),
				  UNIQUE KEY invoice_num (invoice_num)
				) {$charset_collate};";
		dbDelta($sql_main);
		//}

		//if($wpdb->get_var("SHOW TABLES LIKE '". Web_Invoice::tablename('log') ."'") != Web_Invoice::tablename('log')) {
		if(preg_match('/^4\.0.*/', $wpdb->get_var("SELECT version()")) > 0) {
			$sql_log = "CREATE TABLE IF NOT EXISTS " . Web_Invoice::tablename('log') . " (
					  id bigint(20) NOT NULL auto_increment,
					  invoice_id int(11) NOT NULL default '0',
					  action_type varchar(255) NOT NULL,
					  `value` longtext NOT NULL,
					  time_stamp timestamp NOT NULL,
					  PRIMARY KEY  (id)
					) {$charset_collate};";
			dbDelta($sql_log);
		} else {
			$sql_log = "CREATE TABLE IF NOT EXISTS " . Web_Invoice::tablename('log') . " (
					  id bigint(20) NOT NULL auto_increment,
					  invoice_id int(11) NOT NULL default '0',
					  action_type varchar(255) NOT NULL,
					  `value` longtext NOT NULL,
					  time_stamp timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
					  PRIMARY KEY  (id)
					) {$charset_collate};";
			dbDelta($sql_log);
		}
		//}

		//if($wpdb->get_var("SHOW TABLES LIKE '". Web_Invoice::tablename('meta') ."'") != Web_Invoice::tablename('meta')) {
		$sql_meta= "CREATE TABLE IF NOT EXISTS `" . Web_Invoice::tablename('meta') . "` (
				`meta_id` bigint(20) NOT NULL auto_increment,
				`invoice_id` bigint(20) NOT NULL default '0',
				`meta_key` varchar(255) default NULL,
				`meta_value` longtext,
				PRIMARY KEY  (`meta_id`),
				KEY `invoice_id` (`invoice_id`),
				KEY `meta_key` (`meta_key`)
				) {$charset_collate};";
		dbDelta($sql_meta);
		//}

		if($wpdb->get_var("SHOW TABLES LIKE '". Web_Invoice::tablename('payment') ."'") != Web_Invoice::tablename('payment')) {
			$sql_payment = "CREATE TABLE IF NOT EXISTS ". Web_Invoice::tablename('payment') ." (
				  payment_id int(20) NOT NULL auto_increment,
				  amount double default '0',
				  invoice_id int(20) NOT NULL,
				  user_id int(20) NOT NULL,
				  status int(11) NOT NULL,
				  PRIMARY KEY  (payment_id)
				) {$charset_collate};";
			dbDelta($sql_payment);
		} else {
			if ($wpdb->get_var("SHOW COLUMNS FROM ". Web_Invoice::tablename('payment') ." LIKE 'invoice_id'") != 'invoice_id') {
				$wpdb->query("ALTER TABLE ". Web_Invoice::tablename('payment') ." ADD `invoice_id` INT( 20 ) NOT NULL AFTER `amount`;");
				$wpdb->query("ALTER TABLE ". Web_Invoice::tablename('payment') ." CHANGE `user_id` `user_id` INT( 20 ) NOT NULL;");
				$wpdb->query("ALTER TABLE ". Web_Invoice::tablename('payment') ." CHANGE `payment_id` `payment_id` INT( 20 ) NOT NULL;");
			}
			if ($wpdb->get_var("SHOW COLUMNS FROM ". Web_Invoice::tablename('payment') ." LIKE 'trx_id'") != 'trx_id') {
				$wpdb->query("TRUNCATE TABLE ". Web_Invoice::tablename('payment') .";");
				$wpdb->query("ALTER TABLE ". Web_Invoice::tablename('payment') ." CHANGE `payment_id` `payment_id` INT( 20 ) NOT NULL AUTO_INCREMENT;"); 
				$wpdb->query("ALTER TABLE ". Web_Invoice::tablename('payment') ." ADD `trx_id` VARCHAR( 25 ) NOT NULL AFTER `invoice_id`, ADD UNIQUE ( `trx_id` );");
			}
		}

		//if($wpdb->get_var("SHOW TABLES LIKE '". Web_Invoice::tablename('payment_meta') ."'") != Web_Invoice::tablename('payment_meta')) {
		$sql_payment_meta = "CREATE TABLE IF NOT EXISTS `" . Web_Invoice::tablename('payment_meta') . "` (
				`payment_meta_id` int(20) NOT NULL auto_increment,
				`payment_id` int(20) NOT NULL default '0',
				`meta_key` varchar(255) default NULL,
				`meta_value` longtext,
				PRIMARY KEY  (`payment_meta_id`),
				KEY `payment_id` (`payment_id`),
				KEY `meta_key` (`meta_key`)
				) {$charset_collate};";
		dbDelta($sql_payment_meta);
		//}

		// Fix Paid Statuses  from Old Version where they were kept in main table
		$all_invoices = $wpdb->get_results("SELECT invoice_num FROM ".Web_Invoice::tablename('main')." WHERE status ='1'");
		if(!empty($all_invoices)) {
			foreach ($all_invoices as $invoice) {
				web_invoice_update_invoice_meta($invoice->invoice_num,'paid_status','paid');
			}
		}

		// Fix old phone_number and street_address to be without the dash
		$all_users_with_meta = $wpdb->get_col("SELECT DISTINCT user_id FROM $wpdb->usermeta");
		if(!empty($all_users_with_meta)) {
			foreach ($all_users_with_meta as $user) {
				if(get_usermeta($user, 'company_name')) { update_usermeta($user, 'company_name',get_usermeta($user, 'company_name')); }
				if(get_usermeta($user, 'tax_id')) { update_usermeta($user, 'tax_id',get_usermeta($user, 'tax_id')); }
				if(get_usermeta($user, 'street_address')) { update_usermeta($user, 'streetaddress',get_usermeta($user, 'street_address')); }
				if(get_usermeta($user, 'phone_number')) { update_usermeta($user, 'phonenumber',get_usermeta($user, 'phone_number')); }
				if(get_usermeta($user, 'country')) { update_usermeta($user, 'country',get_usermeta($user, 'country')); }
			}
		}

		add_option('web_invoice_version', WEB_INVOICE_SCHEDULER_VERSION_NUM);
		add_option('web_invoice_email_address',get_bloginfo('admin_email'));
		add_option('web_invoice_business_name', get_bloginfo('blogname'));
		add_option('web_invoice_business_address', '');
		add_option('web_invoice_show_billing_address', 'no');
		add_option('web_invoice_show_business_address', 'no');
		add_option('web_invoice_payment_method','');
		add_option('web_invoice_protocol','http');
		add_option('web_invoice_user_level', array('administrator'));
		
		$current_role = get_option('web_invoice_user_level');
		
		if (!is_array($current_role) || in_array('level_8', $current_role)) {
			$current_role = array('administrator');
			update_option('web_invoice_user_level', $current_role);
		}
		
		$ro = new WP_Roles();
		foreach ($ro->role_objects as $role) {
			if (in_array($role->name, $current_role)) {
				$role->add_cap('manage_web_invoice', true);
			}
		}
			
		add_option('web_invoice_web_invoice_page','');
		add_option('web_invoice_redirect_after_user_add', 'no');
		add_option('web_invoice_self_generate_from_template', 'no');
		add_option('web_invoice_default_currency_code','USD');

		add_option('web_invoice_show_quantities','Hide');
		add_option('web_invoice_use_css','yes');
		add_option('web_invoice_force_https','false');
		add_option('web_invoice_send_thank_you_email','no');
		add_option('web_invoice_cc_thank_you_email','no');
		add_option('web_invoice_tax_count','1');
		add_option('web_invoice_tax_name',serialize(array()));

		//Authorize.net Gateway Settings
		add_option('web_invoice_gateway_username','');
		add_option('web_invoice_gateway_tran_key','');
		add_option('web_invoice_gateway_delim_char',',');
		add_option('web_invoice_gateway_encap_char','');
		add_option('web_invoice_gateway_merchant_email',get_bloginfo('admin_email'));
		add_option('web_invoice_gateway_header_email_receipt','Thanks for your payment!');
		add_option('web_invoice_recurring_gateway_url','https://api.authorize.net/xml/v1/request.api');
		add_option('web_invoice_gateway_url','https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi');
		add_option('web_invoice_gateway_MD5Hash','');

		add_option('web_invoice_gateway_test_mode','FALSE');
		add_option('web_invoice_gateway_delim_data','TRUE');
		add_option('web_invoice_gateway_relay_response','FALSE');
		add_option('web_invoice_gateway_email_customer','FALSE');

		// PayPal
		add_option('web_invoice_paypal_address','');
		add_option('web_invoice_paypal_only_button', 'False');
		
		// Payflow
		add_option('web_invoice_payflow_login','');
		add_option('web_invoice_payflow_partner','');
		add_option('web_invoice_payflow_only_button', 'False');
		add_option('web_invoice_payflow_shipping_details', 'True');
		add_option('web_invoice_payflow_silent_post', 'False');
		
		// Payflow Pro
		add_option('web_invoice_pfp_partner','');
		add_option('web_invoice_pfp_env', 'live');
		add_option('web_invoice_pfp_authentication','');
		add_option('web_invoice_pfp_username', '');
		add_option('web_invoice_pfp_password', '');
		add_option('web_invoice_pfp_signature', '');
		add_option('web_invoice_pfp_wpppe_vendor', '');
		add_option('web_invoice_pfp_wpppe_username', '');
		add_option('web_invoice_pfp_wpppe_password', '');
		add_option('web_invoice_pfp_3rdparty_email', '');
		add_option('web_invoice_pfp_shipping_details', 'True');
		
		// PayPal
		add_option('web_invoice_other_details','');

		// Moneybookers
		add_option('web_invoice_moneybookers_address','');
		add_option('web_invoice_moneybookers_recurring_address','');
		add_option('web_invoice_moneybookers_merchant','False');
		add_option('web_invoice_moneybookers_secret',uniqid());
		add_option('web_invoice_moneybookers_ip', '83.220.158.0-83.220.158.31,213.129.75.193-213.129.75.206,91.208.28.0-91.208.28.255');

		// AlertPay
		add_option('web_invoice_alertpay_address','');
		add_option('web_invoice_alertpay_merchant','False');
		add_option('web_invoice_alertpay_secret',uniqid());
		add_option('web_invoice_alertpay_test_mode','FALSE');
		add_option('web_invoice_alertpay_ip', '67.205.87.225-67.205.87.226,67.205.87.235');

		// Google Checkout
		add_option('web_invoice_google_checkout_env','live');
		add_option('web_invoice_google_checkout_merchant_id','');
		add_option('web_invoice_google_checkout_level2','False');
		add_option('web_invoice_google_checkout_merchant_key','');
		add_option('web_invoice_google_checkout_tax_state','NY');
		
		// Sage Pay
		add_option('web_invoice_sagepay_env','live');
		add_option('web_invoice_sagepay_vendor_name','');
		add_option('web_invoice_sagepay_vendor_key','');
		add_option('web_invoice_sagepay_shipping_details', 'True');
		
		// Send invoice
		add_option('web_invoice_email_send_invoice_subject','%subject');
		add_option('web_invoice_email_send_invoice_content',
"Dear %call_sign, 

%business_name has sent you a %recurring
web invoice in the amount of %amount.

%description

You may pay, view and print the invoice online by visiting the following link: 
%link

Best regards,
%business_name ( %business_email )");

		// Send reminder
		add_option('web_invoice_email_send_reminder_subject','[Reminder] %subject');
		add_option('web_invoice_email_send_reminder_content',
"Dear %call_sign, 

%business_name has ent you a reminder for the %recurring
web invoice in the amount of %amount.

%description

You may pay, view and print the invoice online by visiting the following link: 
%link.

Best regards,
%business_name ( %business_email )");

		// Send receipt
		add_option('web_invoice_email_send_receipt_subject','Receipt for %subject');
		add_option('web_invoice_email_send_receipt_content',
"Dear %call_sign, 

%business_name has received your payment for the %recurring
web invoice in the amount of %amount.

Thank you very much for your patronage.

Best regards,
%business_name ( %business_email )");
		
		add_option('web_invoice_pdf_content',
"<html>
	<head>
		<title>Invoice</title>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
	</head>
	<body>
		<div id='invoice_page' class='clearfix'>
			<img style='float: right;' src='".$this->the_path."/images/web-invoice.png' style='width:101px; height: 128px;' />
			<h1>Invoice</h1>
			%content
		</div>
	</body>
</html>");
	}

}

global $_web_invoice_getinfo;
global $_web_invoice_payment_cache;
global $_web_invoice_payment_meta_cache;
global $_web_invoice_clear_cache;

class Web_Invoice_GetInfo {
	var $id;
	var $_row_cache;

	function __construct($invoice_id) {
		global $_web_invoice_getinfo, $_web_invoice_clear_cache, $wpdb;

		$this->id = $invoice_id;

		if (isset($_web_invoice_getinfo[$this->id]) && $_web_invoice_getinfo[$this->id]) {
			$this->_row_cache = $_web_invoice_getinfo[$this->id];
		}

		if (!$this->_row_cache || $_web_invoice_clear_cache) {
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$invoice_id}'"));
			$_web_invoice_clear_cache = false;
		}

		if (!$this->_row_cache) {
			$_custom_invoice = $wpdb->get_row("SELECT invoice_id FROM ".Web_Invoice::tablename('meta')." WHERE meta_key = 'web_invoice_custom_invoice_id' AND meta_value = '{$invoice_id}'");
			$this->id = $_custom_invoice->invoice_id;
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$this->id}'"));
		}
	}

	function _setRowCache($row) {
		global $_web_invoice_getinfo;

		if (!$row) {
			$this->id = null;
			return;
		}

		$this->_row_cache = $row;
		$_web_invoice_getinfo[$this->id] = $this->_row_cache;
	}

	function recipient($what) {
		global $_web_invoice_clear_cache, $wpdb;

		if (!$this->_row_cache || $_web_invoice_clear_cache) {
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$this->id}'"));
			$_web_invoice_clear_cache = false;
		}

		if ($this->_row_cache) {
			$uid = $this->_row_cache->user_id;
			$profileuser = get_userdata($uid);
			$user_email = $profileuser->user_email;
		} else {
			$uid = false;
			$user_email = false;
		}

		$invoice_info = $this->_row_cache;

		switch ($what) {
			case 'callsign':
				$first_name = strip_tags(get_usermeta($uid,'first_name'));
				$last_name = strip_tags(get_usermeta($uid,'last_name'));
				if(empty($first_name) || empty($last_name)) return $user_email; else return $first_name . " " . $last_name;
				break;

			case 'user_id':
				return $uid;
				break;

			case 'email_address':
				return $user_email;
				break;

			case 'first_name':
				return strip_tags(get_usermeta($uid,'first_name'));
				break;

			case 'last_name':
				return strip_tags(get_usermeta($uid,'last_name'));
				break;

			case 'phonenumber':
				return web_invoice_format_phone(get_usermeta($uid,'phonenumber'));
				break;

			case 'paypal_phonenumber':
				return get_usermeta($uid,'phonenumber');
				break;

			case 'log_status':
				if($status_update = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('log')." WHERE invoice_id = ".$this->id ." ORDER BY `".Web_Invoice::tablename('log')."`.`time_stamp` DESC LIMIT 0 , 1"))
				return $status_update->value . " - " . web_invoice_Date::convert($status_update->time_stamp, 'Y-m-d H', __('M d Y', WEB_INVOICE_TRANS_DOMAIN));
				break;

			case 'paid_date':
				$paid_date = $wpdb->get_var("SELECT time_stamp FROM  ".Web_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$this->id."' ORDER BY time_stamp DESC LIMIT 0, 1");
				if($paid_date) return web_inv;
				break;

			case 'streetaddress':
				return get_usermeta($uid,'streetaddress');
				break;

			case 'state':
				return strtoupper(get_usermeta($uid,'state'));
				break;

			case 'city':
				return get_usermeta($uid,'city');
				break;

			case 'zip':
				return get_usermeta($uid,'zip');
				break;

			case 'country':
				if(get_usermeta($uid,'country')) return get_usermeta($uid,'country');  else  return "US";
				break;
			
			case 'company_name':
				if(get_usermeta($uid,'company_name')) return get_usermeta($uid,'company_name');  else  return "";
				break;
			
			case 'tax_id':
				if(get_usermeta($uid,'tax_id')) return get_usermeta($uid,'tax_id');  else  return "";
				break;
		}

	}
	
	function shipping($what) {
		global $_web_invoice_clear_cache, $wpdb;

		if (!$this->_row_cache || $_web_invoice_clear_cache) {
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$this->id}'"));
			$_web_invoice_clear_cache = false;
		}

		if ($this->_row_cache) {
			$uid = $this->_row_cache->user_id;
			$user_email = $wpdb->get_var("SELECT user_email FROM ". $wpdb->prefix . "users WHERE id=".$uid);
		} else {
			$uid = false;
			$user_email = false;
		}

		$invoice_info = $this->_row_cache;

		switch ($what) {
			case 'first_name':
				return (get_usermeta($uid,'shipto_first_name')!="")?get_usermeta($uid,'shipto_first_name'):get_usermeta($uid,'first_name');
				break;

			case 'last_name':
				return (get_usermeta($uid,'shipto_last_name')!="")?get_usermeta($uid,'shipto_last_name'):get_usermeta($uid,'last_name');
				break;
				
			case 'email_address':
				return $user_email;
				break;

			case 'phonenumber':
				$phone_number = (get_usermeta($uid,'shipto_phonenumber')!="")?get_usermeta($uid,'shipto_phonenumber'):get_usermeta($uid,'phonenumber');
				return web_invoice_format_phone($phone_number);
				break;

			case 'paypal_phonenumber':
				return (get_usermeta($uid,'shipto_phonenumber')!="")?get_usermeta($uid,'shipto_phonenumber'):get_usermeta($uid,'phonenumber');
				break;

			case 'streetaddress':
				return (get_usermeta($uid,'shipto_streetaddress')!="")?get_usermeta($uid,'shipto_streetaddress'):get_usermeta($uid,'streetaddress');
				break;

			case 'state':
				$state = (get_usermeta($uid,'shipto_state')!="")?get_usermeta($uid,'shipto_state'):get_usermeta($uid,'state');
				return strtoupper($state);
				break;

			case 'city':
				return (get_usermeta($uid,'shipto_city')!="")?get_usermeta($uid,'shipto_city'):get_usermeta($uid,'city');
				break;

			case 'zip':
				return (get_usermeta($uid,'shipto_zip')!="")?get_usermeta($uid,'shipto_zip'):get_usermeta($uid,'zip');
				break;

			case 'country':
				if(get_usermeta($uid,'shipto_country')) 
					return get_usermeta($uid,'shipto_country');  
				else if(get_usermeta($uid,'country')) 
					return get_usermeta($uid,'country');  
				else
					return "US";
				break;
				
			case 'company_name':
				if(get_usermeta($uid,'shipto_company_name'))
					return get_usermeta($uid,'shipto_company_name');
				else if(get_usermeta($uid,'company_name'))
					return get_usermeta($uid,'company_name');
				else
					return "";
				break;
		}

	}

	function display($what) {
		global $_web_invoice_clear_cache, $wpdb;

		if (!$this->_row_cache || $_web_invoice_clear_cache) {
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$this->id}'"));
			$_web_invoice_clear_cache = false;
		}

		$invoice_info = $this->_row_cache;

		switch ($what) {
			case 'log_status':
				if($status_update = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('log')." WHERE invoice_id = ".$this->id ." ORDER BY `".Web_Invoice::tablename('log')."`.`time_stamp` DESC LIMIT 0 , 1"))
				return $status_update->value . " - " . web_invoice_Date::convert($status_update->time_stamp, 'Y-m-d H', __('M d Y', WEB_INVOICE_TRANS_DOMAIN));
				break;

			case 'paid_date':
				$paid_date = $wpdb->get_var("SELECT time_stamp FROM  ".Web_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$this->id."' ORDER BY time_stamp DESC LIMIT 0, 1");
				if($paid_date) return web_invoice_Date::convert($paid_date, 'Y-m-d H', __('M d Y', WEB_INVOICE_TRANS_DOMAIN));
				break;
				
			case 'paid_date_raw':
				$paid_date = $wpdb->get_var("SELECT time_stamp FROM  ".Web_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$this->id."' ORDER BY time_stamp DESC LIMIT 0, 1");
				if($paid_date) return $paid_date;
				break;

			case 'subscription_name':
				return web_invoice_meta($this->id,'web_invoice_subscription_name');
				break;

			case 'interval_length':
				return web_invoice_meta($this->id,'web_invoice_subscription_length');
				break;

			case 'interval_unit':
				return web_invoice_meta($this->id,'web_invoice_subscription_unit');
				break;

			case 'totalOccurrences':
				return web_invoice_meta($this->id,'web_invoice_subscription_total_occurances');
				break;

			case 'startDate':
				$web_invoice_subscription_start_day = web_invoice_meta($this->id,'web_invoice_subscription_start_day');
				$web_invoice_subscription_start_year = web_invoice_meta($this->id,'web_invoice_subscription_start_year');
				$web_invoice_subscription_start_month = web_invoice_meta($this->id,'web_invoice_subscription_start_month');

				if($web_invoice_subscription_start_month && $web_invoice_subscription_start_year && $web_invoice_subscription_start_day) {
					return date(__('Y-m-d', WEB_INVOICE_TRANS_DOMAIN), strtotime($web_invoice_subscription_start_year . "-" . $web_invoice_subscription_start_month . "-" . $web_invoice_subscription_start_day));
				} else {
					return date(__("Y-m-d", WEB_INVOICE_TRANS_DOMAIN));
				}
				break;
					
					
			case 'endDate':
				return date(__('Y-m-d', WEB_INVOICE_TRANS_DOMAIN), strtotime("+".($this->display('interval_length')*$this->display('totalOccurrences'))." ".$this->display('interval_unit'), strtotime($this->display('startDate'))));
				break;
				
			
			case 'profileEndDate':
				return date(__('Y-m-d', WEB_INVOICE_TRANS_DOMAIN), strtotime("+".($this->display('interval_length')*($this->display('totalOccurrences')-1))." ".$this->display('interval_unit'), strtotime($this->display('startDate'))+3600*24));
				break;
				

			case 'archive_status':
				$result = $wpdb->get_col("SELECT action_type FROM  ".Web_Invoice::tablename('log')." WHERE invoice_id = '".$this->id."' ORDER BY time_stamp DESC");
				foreach($result as $event){
					if ($event == 'unarchive') { return ''; break; }
					if ($event == 'archive') { return 'archive'; break; }
				}
				break;

			case 'display_billing_rate':
				$length = web_invoice_meta($this->id,'web_invoice_subscription_length');
				$unit = web_invoice_meta($this->id,'web_invoice_subscription_unit');
				$occurances = web_invoice_meta($this->id,'web_invoice_subscription_total_occurances');
				// days
				if($unit == "days") {
					if($length == '1') return "daily for $occurances days";
					if($length > '1') return "every $length days for a total of $occurances billing cycles";
				}
				//months
				if($unit == "months"){
					if($length == '1') return "monthly for $occurances months";
					if($length > '1') return "every $length months $occurances times";
				}
				if($unit == "years"){
					if($length == '1') return "annually for $occurances years";
					if($length > '1') return "every $length years $occurances times";
				}
				break;

			case 'link':
				$link_to_page = get_permalink(get_option('web_invoice_web_invoice_page'));
				$hashed = md5($this->id);
				if(get_option("permalink_structure")) { return $link_to_page . "?invoice_id=" .$hashed; }
				else { return  $link_to_page . "&invoice_id=" . $hashed; }
				break;
			case 'invoice_hash':
				return md5($this->id);
				break;
			case 'print_link':
				return $this->display('link').'&print=1';
				break;

			case 'hash':
				return md5($this->id);
				break;

			case 'currency':
				if(web_invoice_meta($this->id,'web_invoice_currency_code') != '') {
					$currency_code = web_invoice_meta($this->id,'web_invoice_currency_code');
				} else if (get_option('web_invoice_default_currency_code') != '') {
					$currency_code = get_option('web_invoice_default_currency_code');
				} else {
					$currency_code = "USD";
				}
				return $currency_code;
				break;

			case 'display_id':
				$web_invoice_custom_invoice_id = web_invoice_meta($this->id,'web_invoice_custom_invoice_id');
				if(empty($web_invoice_custom_invoice_id)) { return $this->id; }	else { return $web_invoice_custom_invoice_id; }
				break;

			case 'due_date':
				$web_invoice_due_date_month = web_invoice_meta($this->id,'web_invoice_due_date_month');
				$web_invoice_due_date_year = web_invoice_meta($this->id,'web_invoice_due_date_year');
				$web_invoice_due_date_day = web_invoice_meta($this->id,'web_invoice_due_date_day');
				if(!empty($web_invoice_due_date_month) && !empty($web_invoice_due_date_year) && !empty($web_invoice_due_date_day))
				return date(__('Y-m-d', WEB_INVOICE_TRANS_DOMAIN), strtotime("$web_invoice_due_date_year-$web_invoice_due_date_month-$web_invoice_due_date_day"));
				return date(__('Y-m-d', WEB_INVOICE_TRANS_DOMAIN));
				break;

			case 'amount':
				return $invoice_info->amount;
				break;

			case 'tax_percent':
				$_tax_values = unserialize(web_invoice_meta($this->id,'tax_value'));
				if (is_array($_tax_values)) {
					$_tax_value = 0;
					foreach ($_tax_values as $_tax_valuex) {
						$_tax_value += $_tax_valuex;
					}
				} else {
					$_tax_value = $_tax_values;
				}
				return $_tax_value;
				break;

			case 'tax_total':
				$_tax_values = unserialize(web_invoice_meta($this->id,'tax_value'));
				if (is_array($_tax_values)) {
					$_tax_value = 0;
					foreach ($_tax_values as $_tax_valuex) {
						$_tax_value += $_tax_valuex;
					}
				} else {
					$_tax_value = $_tax_values;
				}
				return  $_tax_value * $invoice_info->amount;
				break;

			case 'subject':
				return $invoice_info->subject;
				break;

			case 'display_amount':
				return sprintf(web_invoice_currency_symbol_format($this->display('currency')), web_invoice_currency_format($invoice_info->amount));
				break;

			case 'description':
				return  str_replace("\n", "<br />", $invoice_info->description);
				break;

			case 'itemized':
				return unserialize(urldecode($invoice_info->itemized));
				break;

			case 'status':
				return $invoice_info->status;
				break;
			case 'trx_id':
				return web_invoice_payment_register($this->id, $this->display('amount'));
				break;
		}
	}

}
