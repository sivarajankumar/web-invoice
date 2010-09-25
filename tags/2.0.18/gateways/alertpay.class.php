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

class Web_Invoice_AlertPay {

	var $invoice;

	var $ip;
	var $int_ip;

	var $ap_custemailaddress;
	var $ap_custfirstname;
	var $ap_custlastname;
	var $ap_custaddress;
	var $ap_custcity;
	var $ap_custstate;
	var $ap_custcountry;
	var $ap_custzip;

	var $ap_merchant;
	var $ap_referencenumber;

	var $ap_totalamount;
	var $ap_currency;
	var $ap_status;
	var $ap_securitycode;
	var $ap_amount;
	var $ap_test;


	function Web_Invoice_AlertPay($invoice_id) {
		$this->invoice = new Web_Invoice_GetInfo($invoice_id);
	}

	function _logFailure($ref) {
		web_invoice_update_log($this->invoice->id,'alertpay_api_fail',"Failed AlertPay API request from {$this->ip}. REF: {$ref}. Serialized object ".serialize($this));
	}

	function _logSuccess($ref) {
		web_invoice_update_log($this->invoice->id,'alertpay_api_success',"Successful AlertPay API request from {$this->ip}. REF: {$ref}");
	}

	function _quadIpToInt($ip) {
		$ip_parts = preg_split('/\./', $ip);
		$numeric_ip = 0;

		foreach ($ip_parts as $ip_part) {
			$numeric_ip=($numeric_ip*256)+intval($ip_part);
		}

		return $numeric_ip;
	}

	function _allowedIp() {
		$allowed_ips = get_option('web_invoice_alertpay_ip');
		$this->int_ip = $this->_quadIpToInt($this->ip);

		$ip_ranges = preg_split('/,/', $allowed_ips);

		foreach ($ip_ranges as $ip_range) {
			list($start_ips,$end_ips) = preg_split('/-/', $ip_range);

			$start_ip = $this->_quadIpToInt($start_ips);
			$end_ip = $this->_quadIpToInt($end_ips);

			if (($this->int_ip >= $start_ip) && ($end_ip >= $this->int_ip)) {
				return true;
			}
			
			if ($end_ip == 0 && $start_ip == $this->int_ip) return true;
		}

		return false;
	}

	function updateContactInfo() {
		$user_id = $this->invoice->recipient('user_id');
		$updated = false;

		if (!empty($this->ap_custaddress)) {
			update_usermeta($user_id, 'streetaddress', $this->ap_custaddress);
			$updated = true;
		}
		if (!empty($this->ap_custfirstname)) {
			update_usermeta($user_id, 'first_name', $this->ap_custfirstname);
			$updated = true;
		}
		if (!empty($this->ap_custlastname)) {
			update_usermeta($user_id, 'last_name', $this->ap_custlastname);
			$updated = true;
		}
		if (!empty($this->ap_custzip)) {
			update_usermeta($user_id, 'zip', $this->ap_custzip);
			$updated = true;
		}
		if (!empty($this->ap_custstate)) {
			update_usermeta($user_id, 'state', $this->ap_custstate);
			$updated = true;
		}
		if (!empty($this->ap_custcity)) {
			update_usermeta($user_id, 'city', $this->ap_custcity);
			$updated = true;
		}
		if (!empty($this->ap_custcountry)) {
			update_usermeta($user_id, 'country', web_invoice_map_country3_to_country($this->ap_custcountry));
			$updated = true;
		}

		if ($updated) {
			$this->_logSuccess('Updated user information with details from AlertPay');
		}
	}

	function processRequest($ip, $request) {

		$this->ip = $ip;

		$this->ap_custemailaddress = $request['ap_custemailaddress'];
		$this->ap_custfirstname = $request['ap_custfirstname'];
		$this->ap_custlastname = $request['ap_custlastname'];
		$this->ap_custaddress = $request['ap_custaddress'];
		$this->ap_custcity = $request['ap_custcity'];
		$this->ap_custstate = $request['ap_custstate'];
		$this->ap_custcountry = $request['ap_custcountry'];
		$this->ap_custzip = $request['ap_custzip'];

		$this->ap_merchant = $request['ap_merchant'];
		$this->ap_referencenumber = $request['ap_referencenumber'];
		$this->ap_totalamount = $request['ap_totalamount'];
		$this->ap_currency = $request['ap_currency'];

		$this->ap_amount = $request['ap_amount'];
		$this->ap_itemname = $request['ap_itemname'];

		$this->ap_securitycode = $request['ap_securitycode'];
		$this->ap_status = $request['ap_status'];
		$this->ap_test = $request['ap_test'];

		if (!$this->_allowedIp()) {
			$this->_logFailure('Invalid IP');

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

		if (($this->ap_currency != web_invoice_meta($this->invoice->id, 'web_invoice_currency_code'))) {
			$this->_logFailure('Invalid currency');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: AP0';
			exit(0);
		}
		if (($this->ap_totalamount != $this->invoice->display('amount'))) {
			$this->_logFailure('Invalid amount');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: AP1';
			exit(0);
		}
		if (($this->ap_merchant != get_option('web_invoice_alertpay_address'))) {
			$this->_logFailure('Invalid pay_to_email');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: AP2';
			exit(0);
		}

		if ($this->ap_securitycode != get_option('web_invoice_alertpay_secret')) {
			$this->_logFailure('Invalid security code');

			header('HTTP/1.0 403 Forbidden');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were unable to authenticate the request';
			exit(0);
		}

		if (strtolower($this->ap_status) != "success") {
			$this->_logSuccess('Payment failed (status)');

			header('HTTP/1.0 200 OK');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Thank you very much for letting us know. REF: Not success';
			exit(0);
		}

		if ($this->ap_test == 1) {
			if (get_option('web_invoice_alertpay_test_mode') == 'TRUE') {
				$this->_logFailure('Test payment');
				$this->updateContactInfo();
			}
		} else {
			$this->updateContactInfo();
			web_invoice_mark_as_paid($this->invoice->id);
		}

		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know';
		exit(0);
	}
}
