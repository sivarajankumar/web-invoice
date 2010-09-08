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

class Web_Invoice_Payflow {

	var $invoice;

	var $ip;
	
	var $auth_code;
	var $avs_data;
	var $host_code;
	var $pnref;
	var $respmsg;
	var $result;
	
	var $emailaddress;
	var $firstname;
	var $lastname;
	var $address;
	var $city;
	var $state;
	var $country;
	var $zip;

	function Web_Invoice_Payflow($invoice_id, $request) {		
		$this->invoice = new Web_Invoice_GetInfo($invoice_id);
	}

	function _logFailure($ref) {
		web_invoice_update_log($this->invoice->id,'payflow_silent_post_fail',"Failed PayPal Payflow silent post request from {$this->ip}. REF: {$ref}. Serialized object ".serialize($this));
	}

	function _logSuccess($ref) {
		web_invoice_update_log($this->invoice->id,'payflow_silent_post_success',"Successful PayPal Payflow silent post request from {$this->ip}. REF: {$ref}");
	}
	
	function updateContactInfo() {
		$user_id = $this->invoice->recipient('user_id');
		$updated = false;

		if (!empty($this->address)) {
			update_usermeta($user_id, 'streetaddress', $this->address);
			$updated = true;
		}
		if (!empty($this->firstname)) {
			update_usermeta($user_id, 'first_name', $this->firstname);
			$updated = true;
		}
		if (!empty($this->lastname)) {
			update_usermeta($user_id, 'last_name', $this->lastname);
			$updated = true;
		}
		if (!empty($this->zip)) {
			update_usermeta($user_id, 'zip', $this->zip);
			$updated = true;
		}
		if (!empty($this->state)) {
			update_usermeta($user_id, 'state', $this->state);
			$updated = true;
		}
		if (!empty($this->city)) {
			update_usermeta($user_id, 'city', $this->city);
			$updated = true;
		}
		if (!empty($this->country)) {
			update_usermeta($user_id, 'country', $this->country);
			$updated = true;
		}

		if ($updated) {
			$this->_logSuccess('Updated user information with details from PayPal Payflow');
		}
	}
	
	function processRequest($ip, $request) {
		$this->ip = $ip;
		
		if ($request['RESULT'] == 0) {
			$this->emailaddress = $request['EMAIL'];
		
			$_names = preg_split('/ /', $request['NAME']);
			
			$this->lastname = array_pop($_names);
			$this->firstname = join(' ', $_names);
			
			$this->address = $request['ADDRESS'];
			$this->city = $request['CITY'];
			$this->state = $request['STATE'];
			$this->country = $request['COUNTRY'];
			$this->zip = $request['ZIP'];

			$this->result = $request['RESULT'];
			$this->respmsg = $request['RESPMSG'];
			
			if (isset($request['AUTHCODE'])) $this->auth_code = $request['AUTHCODE'];
			$this->avs_data = $request['AVSDATA'];
			if (isset($request['HOSTCODE'])) $this->host_code = $request['HOSTCODE'];
			$this->pnref = $request['PNREF'];
			
			if ($this->respmsg == 'AVSDECLINED' || $this->respmsg == 'CSCDECLINED') {
				$this->_processCancellation($ip, $request);
			} else {
				$this->_processPayment($ip, $request);
			}
			
			$this->updateContactInfo();
			web_invoice_update_invoice_meta($this->invoice->id, 'payflow_pnref', $this->pnref);
		} else {
			$this->_logFailure($this->respmsg);
			
			header('HTTP/1.0 200 OK');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Thank you very much for letting us know. REF: Failure';
		}
		
		exit(0);
	}
	
	function _processPayment($ip, $request) {
		$this->_logSuccess('Payment in progress ('.$this->respmsg.')');
		web_invoice_mark_as_paid($this->invoice->id);
		
		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know. REF: Paid';
	}
	
	function _processCancellation($ip, $request) {
		$this->_logSuccess('Subscription cancelled ('.$this->respmsg.')');
		web_invoice_mark_as_cancelled($this->invoice->id);
		
		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know. REF: Cancelled subscription';
	}
}
