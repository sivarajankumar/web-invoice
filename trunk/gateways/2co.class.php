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

class Web_Invoice_2CO {

	var $invoice;

	var $ip;

	var $tco_order_number;
	var $tco_cart_order_id;
	var $tco_credit_card_processed;
	var $tco_key;
	var $tco_demo;
	var $tco_total;
	
	var $trx_id;

	function Web_Invoice_2CO($invoice_id) {
		if (get_option('web_invoice_partial_payments') == 'yes') {
			$invoice_id_parts = explode('_', $invoice_id);
			$invoice_id = $invoice_id_parts[0];
			$this->trx_id = $invoice_id_parts[1];
		}
		$this->invoice = new Web_Invoice_GetInfo($invoice_id);
	}

	function _logFailure($ref) {
		web_invoice_update_log($this->invoice->id,'2co_api_fail',"Failed 2CO API request from {$this->ip}. REF: {$ref}. Serialized object ".serialize($this));
	}

	function _logSuccess($ref) {
		web_invoice_update_log($this->invoice->id,'2co_api_success',"Successful 2CO API request from {$this->ip}. REF: {$ref}");
	}

	function processRequest($ip, $request) {

		$this->ip = $ip;

		$this->tco_order_number = $request['order_number'];
		$this->tco_cart_order_id = $request['cart_order_id'];
		$this->tco_credit_card_processed = $request['credit_card_processed'];
		$this->tco_key = $request['key'];
		$this->tco_demo = $request['demo'];
		$this->tco_total = $request['total'];

		if (!$this->invoice->id) {
			$this->_logFailure('Invoice not found');

			header('HTTP/1.0 404 Not Found');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Invoice not found';
			exit(0);
		}
		
		$calc_key = md5(get_option('web_invoice_2co_secret_word').get_option('web_invoice_2co_sid').$this->tco_order_number.$this->tco_total);

		if (strtolower($this->tco_key) != strtolower($calc_key)) {
			$this->_logFailure('Invalid security code');

			header('HTTP/1.0 403 Forbidden');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were unable to authenticate the request';
			exit(0);
		}

		if (strtolower($this->tco_credit_card_processed ) != "y") {
			$this->_logSuccess('2CO order # '.$this->tco_order_number);

			header('HTTP/1.0 200 OK');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Thank you very much for letting us know. REF: Not success';
			exit(0);
		}

		if (strtolower($this->tco_demo) == "y") {
			if (get_option('web_invoice_2co_demo_mode') == 'TRUE') {
				$this->_logFailure('Test payment');
			}
		} else {
			if (intval($this->tco_total) >= $this->invoice->display('due_amount')) {
				web_invoice_mark_as_paid($this->invoice->id);
			}
			$payment_id = web_invoice_payment_register($this->invoice->id, $this->tco_total, $this->trx_id, 1);
			web_invoice_update_payment_meta($payment_id,'time_stamp',time());
		}

		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know';
		exit(0);
	}
}
