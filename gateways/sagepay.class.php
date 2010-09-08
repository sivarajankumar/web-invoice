<?php

class Web_Invoice_SagePay
{
	protected $transkey;
	protected $results  = array();

	protected $approved = false;
	protected $declined = false;
	protected $error    = false;
	
	protected $ip;
	protected $response;
	protected $invoice;

	static $instances = 0;
	static $version = '2.23';
	
	public function __construct($crypt)
	{
		$this->transkey = get_option('web_invoice_sagepay_vendor_key');
		$this->response = web_invoice_xor_decrypt($crypt, $this->transkey);
	}

	public function isApproved()
	{
		return $this->approved;
	}

	public function isDeclined()
	{
		return $this->declined;
	}

	public function isError()
	{
		return $this->error;
	}
	
	public function processRequest($ip) {
		$this->ip = $ip;
		
		$parts = preg_split('/&/', $this->response);
		
		foreach ($parts as $part) {
			$p = preg_split('/=/', $part, 2);
			$this->results[$p[0]] = $p[1];
		}
		
		$this->invoice = new Web_Invoice_GetInfo(web_invoice_get_invoice_id_by_payment($this->results['VendorTxCode']));
		
		if (!$this->invoice) {
			return;
		}
		if ($this->results['Status'] == 'OK' && intval($this->results['Amount']) == intval($this->invoice->display('amount'))) {
			$this->approved = true;
			web_invoice_mark_as_paid($this->invoice->id);
			$this->_logSuccess($this->results['VPSTxId']);
			web_invoice_update_invoice_meta($this->invoice->id, 'transaction_id', $this->results['VPSTxId']);
			web_invoice_update_invoice_meta($this->invoice->id, 'auth_code', $this->results['TxAuthNo']);
		} else if ($this->results['Status'] == 'NOTAUTHED') {
			$this->declined = true;
			$this->_logFailure($this->results['VPSTxId']);
			web_invoice_update_invoice_meta($this->invoice->id, 'transaction_id', $this->results['VPSTxId']);
		} else {
			$this->error = true;
			$this->_logFailure($this->results['VPSTxId']);
			web_invoice_update_invoice_meta($this->invoice->id, 'transaction_id', $this->results['VPSTxId']);
		}
		wp_redirect(web_invoice_build_invoice_link($this->invoice->id));
	}
	
	public function _logFailure($ref) {
		web_invoice_update_log($this->invoice->id,'sagepay_api_fail',"Failed Sage Pay API request from {$this->ip}. REF: {$ref}. Serialized object ".serialize($this));
	}

	public function _logSuccess($ref) {
		web_invoice_update_log($this->invoice->id,'sagepay_api_success',"Successful Sage Pay API request from {$this->ip}. REF: {$ref}");
	}

	public function getGatewayResponse()
	{
		return $this->results['Status'];
	}
	
	public function getResponseText()
	{
		return $this->results['ACK'];
	}
	
	public function getResponseCode()
	{
		return $this->results['StatusDetail'];
	}

	public function getAuthCode()
	{
		return $this->results['TxAuthNo'];
	}

	public function getAVSResponse()
	{
		return $this->results['AVSCV2'];
	}
	
	public function getTransactionID()
	{
		return $this->results['VPSTxId'];
	}
}
