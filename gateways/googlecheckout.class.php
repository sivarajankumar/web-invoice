<?php
/*
 Created by S H Mohanjith
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

class Web_Invoice_GoogleCheckout {

	var $invoice;

	var $ip;
	var $int_ip;

	var $gc_custemailaddress;
	var $gc_custfirstname;
	var $gc_custlastname;
	var $gc_custaddress;
	var $gc_custcity;
	var $gc_custstate;
	var $gc_custcountry;
	var $gc_custzip;

	var $gc_referencenumber;

	var $gc_totalamount;
	var $gc_currency;
	var $gc_status;
	var $gc_securitycode;
	var $gc_amount;
	var $gc_test;


	function Web_Invoice_GoogleCheckout($_type, $request) {
		if (!$this->_allowed()) {
			header('HTTP/1.0 403 Forbidden');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were unable to authenticate the request';
			exit(0);
		}
		
		switch ($_type) {
			case 'new-order-notification':
				$this->invoice = new Web_Invoice_GetInfo(web_invoice_gc_name_to_invoice($request['shopping-cart_items_item-1_item-name']));
				break;
			default:
				$this->invoice = new Web_Invoice_GetInfo(web_invoice_gc_serial_to_invoice($request['google-order-number']));
				break;
		}
	}

	function _logFailure($ref) {
		web_invoice_update_log($this->invoice->id,'google_checkout_api_fail',"Failed Google Checkout API request from {$this->ip}. REF: {$ref}. Serialized object ".serialize($this));
	}

	function _logSuccess($ref) {
		web_invoice_update_log($this->invoice->id,'google_checkout_api_success',"Successful Google Checkout API request from {$this->ip}. REF: {$ref}");
	}

	function _allowed() {
		if (get_option('web_invoice_google_checkout_merchant_id') == $_SERVER['PHP_AUTH_USER']
			&& get_option('web_invoice_google_checkout_merchant_key') == $_SERVER['PHP_AUTH_PW'])  {
			return true;
		}
		
		return false;
	}

	function updateContactInfo() {
		$user_id = $this->invoice->recipient('user_id');
		$updated = false;

		if (!empty($this->gc_custaddress)) {
			update_usermeta($user_id, 'streetaddress', $this->gc_custaddress);
			$updated = true;
		}
		if (!empty($this->gc_custfirstname)) {
			update_usermeta($user_id, 'first_name', $this->gc_custfirstname);
			$updated = true;
		}
		if (!empty($this->gc_custlastname)) {
			update_usermeta($user_id, 'last_name', $this->gc_custlastname);
			$updated = true;
		}
		if (!empty($this->gc_custzip)) {
			update_usermeta($user_id, 'zip', $this->gc_custzip);
			$updated = true;
		}
		if (!empty($this->gc_custstate)) {
			update_usermeta($user_id, 'state', $this->gc_custstate);
			$updated = true;
		}
		if (!empty($this->gc_custcity)) {
			update_usermeta($user_id, 'city', $this->gc_custcity);
			$updated = true;
		}
		if (!empty($this->gc_custcountry)) {
			update_usermeta($user_id, 'country', $this->gc_custcountry);
			$updated = true;
		}

		if ($updated) {
			$this->_logSuccess('Updated user information with details from Google Checkout');
		}
	}
	
	function processRequest($ip, $request) {
		$this->ip = $ip;
		
		switch ($request['_type']) {
			case 'new-order-notification':
				$this->_processNewRequest($ip, $request);
				break;
			case 'order-state-change-notification':
				$this->_processCharge($ip, $request);
				break;
			case 'cancelled-subscription-notification':
				$this->_processSubscriptionCancel($ip, $request);
				break;
			default:
				$this->_processRequest($ip, $request);
		}
	}

	function _processNewRequest($ip, $request) {

		$this->ip = $ip;

		$this->gc_custemailaddress = $request['buyer-billing-address_email'];
		
		$_names = preg_split('/ /', $request['buyer-billing-address_contact-name']);
		
		$this->gc_custlastname = array_pop($_names);
		$this->gc_custfirstname = join(' ', $_names);
		
		$this->gc_custaddress = "{$request['buyer-billing-address_address1']}\n {$request['buyer-billing-address_address2']}";
		$this->gc_custcity = $request['buyer-billing-address_city'];
		$this->gc_custstate = $request['buyer-billing-address_region'];
		$this->gc_custcountry = $request['buyer-billing-address_country-code'];
		$this->gc_custzip = $request['buyer-billing-address_postal-code'];

		$this->gc_referencenumber = $request['google-order-number'];
		$this->gc_totalamount = $request['order-total'];
		$this->gc_currency = $request['order-total_currency'];

		$this->gc_status = $request['financial-order-state'];

		if (!$this->_allowed()) {
			$this->_logFailure('Invalid user');

			header('HTTP/1.0 403 Forbidden');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were unable to authenticate the request';
			exit(0);
		}

		if (!$this->invoice->id) {
			$this->_logFailure('Invoice not found');

			header('HTTP/1.0 404 Not Found');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Invoice not found';
			exit(0);
		}

		if (($this->gc_currency != web_invoice_meta($this->invoice->id, 'web_invoice_currency_code'))) {
			$this->_logFailure('Invalid currency');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: AP0';
			exit(0);
		}
		if (($this->gc_totalamount != $this->invoice->display('amount'))) {
			$this->_logFailure('Invalid amount');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: AP1';
			exit(0);
		}

		web_invoice_update_invoice_meta($this->invoice->id, 'gc_serial_number', $request['google-order-number']);
		$this->_logSuccess('New payment serial '.$request['google-order-number']);
		
		$this->updateContactInfo();

		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know';
		exit(0);
	}
	
	function _processRequest($ip, $request) {
		$this->_logSuccess('Progress notification ('.$request['_type'].')');

		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know. REF: Not success';
		exit(0);
	}
	
	function _processCharge($ip, $request) {		
		$this->gc_status = $request['new-financial-order-state'];
		
		if (strtolower($this->gc_status) != "charged") {
			$this->_logSuccess('Payment in progress ('.$this->gc_status.')');

			header('HTTP/1.0 200 OK');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Thank you very much for letting us know. REF: Not success';
			exit(0);
		}
		
		web_invoice_mark_as_paid($this->invoice->id);
	}
	
	function _processSubscriptionCancel($ip, $request) {		
		$this->gc_status = 'cancelled';
		
		$this->_logSuccess('Subscription cancelled ('.$request['reason'].')');
		
		web_invoice_mark_as_cancelled($this->invoice->id);

		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know. REF: Cancelled subscription';
		exit(0);
	}
}
