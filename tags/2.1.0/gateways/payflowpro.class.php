<?php

class Web_Invoice_PayflowPro
{
	protected $login;
	protected $transkey;
	protected $params   = array();
	protected $results  = array();

	protected $approved = false;
	protected $declined = false;
	protected $error    = true;

	protected $fields;
	protected $response;

	static $instances = 0;
	static $version = '57.0';
	
	public function __construct()
	{
		if (self::$instances == 0)
		{
			$this->params['METHOD']         = "doDirectPayment";
			$this->params['TRXTYPE']        = "S";
			if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
				$this->params['PAYMENTACTION']  = "Sale";
				$this->params['VERSION']        = self::$version;
			} else {
				$this->params['CURRENCY'] = $this->params['CURRENCYCODE'];
			}
		
			if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
				if (get_option("web_invoice_pfp_env") == 'live') {
					$this->url = 'https://api-3t.paypal.com/nvp';
				} else {
					$this->url = 'https://api-3t.sandbox.paypal.com/nvp';
				}
				
				if (get_option('web_invoice_pfp_authentication')=='3token') {
					$this->params['PARTNER']        = stripslashes(get_option("web_invoice_pfp_partner"));
					$this->params['USER']           = stripslashes(get_option("web_invoice_pfp_username"));
					$this->params['PWD']            = stripslashes(get_option("web_invoice_pfp_password"));
					$this->params['SIGNATURE']      = stripslashes(get_option("web_invoice_pfp_signature"));
				} else {
					$this->params['SUBJECT']        = stripslashes(get_option("web_invoice_pfp_3rdparty_email"));
				}
			} else {
				if (get_option("web_invoice_pfp_env") == 'live') {
					$this->url = 'https://payflowpro.paypal.com/';
				} else {
					$this->url = 'https://pilot-payflowpro.paypal.com/';
				}
				
				$this->params['PARTNER']            = stripslashes(get_option("web_invoice_pfp_partner"));
				$this->params['VENDOR']               = stripslashes(get_option("web_invoice_pfp_wpppe_vendor"));
				$this->params['USER']               = stripslashes(get_option("web_invoice_pfp_wpppe_username"));
				$this->params['PWD']                = stripslashes(get_option("web_invoice_pfp_wpppe_password"));
			}
			
			$this->params['TENDER']         = "C";
			
			self::$instances++;
		}
		else
		{
			return false;
		}
	}

	public function transaction($cardnum)
	{
		$this->params['ACCT']  = trim($cardnum);
		if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
			$this->params['CREDITCARDTYPE'] = $this->guessCcType();
		} else {
			$this->params['ACCTTYPE'] = $this->guessCcType();
		}
	}
	
	public function guessCcType() {
		$numLength = strlen($this->params['ACCT']);
		$number = $this->params['ACCT'];
		if ($numLength > 10)
		{
			if((substr($number, 0, 1) == '4') && (($numLength == 13)||($numLength==16))) { return 'Visa'; }
			else if((substr($number, 0, 1) == '5' && ((substr($number, 1, 1) >= '1') && (substr($number, 1, 1) <= '5'))) && ($numLength==16)) { return 'MasterCard'; }
			else if(substr($number, 0, 4) == "6011" && ($numLength==16)) 	{ return 'Discover'; }
			else if((substr($number, 0, 1) == '3' && ((substr($number, 1, 1) == '4') || (substr($number, 1, 1) == '7'))) && ($numLength==15)) { return 'Amex'; }
			else { return ''; }
	
		}
	}

	public function process($retries = 1)
	{
		$this->_prepareParameters();
		$ch = curl_init($this->url);

		$count = 0;
		while ($count < $retries)
		{

			//required for GoDaddy
			if(get_option('web_invoice_using_godaddy') == 'yes') {
				curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				curl_setopt ($ch, CURLOPT_PROXY,"http://proxy.shr.secureserver.net:3128");
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt ($ch, CURLOPT_TIMEOUT, 120);
			}
			//required for GoDaddy
			
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($this->fields, "& "));
			
			$headers[] = "Connect: close"; // either text/namevalue or text/xml
			$headers[] = "Content-Type: text/namevalue"; // either text/namevalue or text/xml
			$headers[] = "X-VPS-Client-Timeout: 95"; // timeout length - keep trying to access the page for this long (in seconds)
			$headers[] = "X-VPS-VIT-OS-Name: ".PHP_OS;  // Name of your Operating System (OS)
			$headers[] = "X-VPS-VIT-OS-Version: ".php_uname('r');  // OS Version
			$headers[] = "X-VPS-VIT-Integration-Product: Web Invoice";  // application name
			$headers[] = "X-VPS-VIT-Integration-Version: ".WEB_INVOICE_VERSION_NUM; // Application version
			$headers[] = "X-VPS-Request-ID: ".md5(uniqid(null, true));
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
			$this->response = curl_exec($ch);

			$this->parseResults();

			if ($this->getResultResponseFull() == "Approved")
			{	
				$this->approved = true;
				$this->declined = false;
				$this->error    = false;
				break;
			}
			else if ($this->getResultResponseFull() == "Declined")
			{
				$this->approved = false;
				$this->declined = true;
				$this->error    = false;
				break;
			}
			$count++;
		}

		curl_close($ch);
	}

	function parseResults()
	{
		$results = explode('&', $this->response);
		
		foreach ($results as $result) {
			list($k, $v) = explode('=', $result);
			$this->results[$k] = urldecode($v);
		}
	}

	public function setParameter($param, $value)
	{
		$param                = trim($param);
		$value                = trim($value);
		$this->params[$param] = $value;
	}

	public function setTransactionType($type)
	{
		$this->params['TRXTYPE'] = strtoupper(trim($type));
	}

	private function _prepareParameters()
	{
		$this->fields = "";
		foreach($this->params as $key => $value)
		{
			$this->fields .= "$key=" . $value . "&";
		}
	}

	public function getGatewayResponse()
	{
		return $this->results['RESULT'];
	}

	public function getResultResponseFull()
	{
		if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
			switch ($this->results['ACK']) {
				case "Success":
					return "Approved";
				case 12:
					return "Declined";
				case 13 || 126:
					return "Deferred";
				default: 
					return "Error"; 
			}
		} else {
			switch ($this->results['RESULT']) {
				case 0:
					return $this->results['RESPMSG'];
				case 12:
					return $this->results['RESPMSG'];
				case 13 || 126:
					return $this->results['RESPMSG'];
				default: 
					return $this->results['RESPMSG'];
			}
		}
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

	public function getResponseText()
	{
		return ($this->results['ACK'] == "")?$this->results['RESPMSG']:$this->results['ACK'];
	}
	
	public function getResponseCode()
	{
		return $this->results['L_ERRORCODE0'];
	}

	public function getAuthCode()
	{
		return $this->results['AUTHCODE'];
	}

	public function getAVSResponse()
	{
		return $this->results['AVSCODE'];
	}
	
	public function getTransactionID()
	{
		if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
			return $this->results['CORRELATIONID'];
		} else {
			return $this->results['PNREF'];
		}
	}
}


class Web_Invoice_PayflowProRecurring extends Web_Invoice_PayflowPro {

	static $version = '50.0';
	
	public function __construct()
	{
		if (self::$instances < 2)
		{
			$this->params['METHOD']         = "CreateRecurringPaymentsProfile";
			$this->params['TRXTYPE']        = "R";
			if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
				$this->params['PAYMENTACTION']  = "Sale";
				$this->params['VERSION']        = self::$version;
			} else {
				$this->params['CURRENCY'] = $this->params['CURRENCYCODE'];
			}
			
			if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
				if (get_option("web_invoice_pfp_env") == 'live') {
					$this->url = 'https://api-3t.paypal.com/nvp';
				} else {
					$this->url = 'https://api-3t.sandbox.paypal.com/nvp';
				}
			
				if (get_option('web_invoice_pfp_authentication')=='3token') {
					$this->params['PARTNER']    = stripslashes(get_option("web_invoice_pfp_partner"));
					$this->params['USER']       = stripslashes(get_option("web_invoice_pfp_username"));
					$this->params['PWD']        = stripslashes(get_option("web_invoice_pfp_password"));
					$this->params['SIGNATURE']  = stripslashes(get_option("web_invoice_pfp_signature"));
				} else {
					$this->params['SUBJECT']    = stripslashes(get_option("web_invoice_pfp_3rdparty_email"));
				}
			} else {
				if (get_option("web_invoice_pfp_env") == 'live') {
					$this->url = 'https://payflowpro.paypal.com/';
				} else {
					$this->url = 'https://pilot-payflowpro.paypal.com/';
				}
				
				$this->params['PARTNER']            = stripslashes(get_option("web_invoice_pfp_partner"));
				$this->params['VENDOR']               = stripslashes(get_option("web_invoice_pfp_wpppe_vendor"));
				$this->params['USER']               = stripslashes(get_option("web_invoice_pfp_wpppe_username"));
				$this->params['PWD']                = stripslashes(get_option("web_invoice_pfp_wpppe_password"));
			}
			$this->params['TENDER']         = "C";
			
			self::$instances++;
		}
		else
		{
			return false;
		}
	}
	
	public function getTransactionID() {
		return $this->results['PROFILEID'];
	}
	
	public function getSubscriberID() {
		return $this->results['PROFILEID'];
	}

	public function getRef() {
		return isset($this->results['RPREF'])?$this->results['RPREF']:$this->results['PROFILEID'];
	}
	
	public function createAccount() {
		return $this->process();
	}
	
	public function isSuccessful() {
		return $this->isApproved();
	}
	
	public function deleteProfile($profile_id) {
		$this->updateProfileStatus($profile_id);
		$self_copy = new Web_Invoice_PayflowProRecurring();
		
		$self_copy->getProfileStatus($profile_id);

		switch ($self_copy->results['STATUS']) {
			case 'ACTIVE':
				return false;
			default:
				return true;
		}
	}
	
	public function pauseProfile($profile_id) {
		$this->updateProfileStatus($profile_id, "Pause");
		$self_copy = new Web_Invoice_PayflowProRecurring();
		
		$self_copy->getProfileStatus($profile_id); 
		
		switch ($self_copy->results['STATUS']) {
			case 'DEACTIVATED BY MERCHANT':
				return true;
			default:
				return false;
		}
	}
	
	public function reactivateProfile($profile_id, $invoice_id) {
		$this->updateProfile($invoice_id);
		$this->updateProfileStatus($profile_id, "Reactivate");
		
		$self_copy = new Web_Invoice_PayflowProRecurring();
		
		$self_copy->getProfileStatus($profile_id);
		
		switch ($self_copy->results['STATUS']) {
			case 'ACTIVE':
				return true;
			default:
				return false;
		}
		 
	}
	
	public function updateProfileStatus($profile_id, $status="Cancel") {
		$this->params['METHOD']         = "ManageRecurringPaymentsProfileStatus";
		switch ($status) {
			case "Pause":
				$this->params['ACTION']         = "Suspend";
				$this->params['NOTE']           = "Suspended from Web Invoice";
				break;
			case "Reactivate":
				$this->params['ACTION']         = "Reactivate";
				$this->params['NOTE']           = "Reactivated from Web Invoice";
				break;
			default:
				$this->params['ACTION']         = "Cancel";
				$this->params['NOTE']           = "Cancelled from Web Invoice";
				break;
		}
		
		if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {	
			unset($this->params['TENDER']);
			unset($this->params['TRXTYPE']);
			$this->params['PROFILEID']      = $profile_id;
		} else {
			switch ($status) {
				case "Reactivate":
					$this->params['ACTION']         = "R";
					break;
				default:
					$this->params['ACTION']         = "C";
					break;
			}
			$this->params['ORIGPROFILEID']  = $profile_id;
		}
		
		unset($this->params['CURRENCY']);
			
		return $this->process();
	}
	
	public function getProfileStatus($profile_id) {
		$this->params['METHOD']         = "GetRecurringPaymentsProfileDetails";
		
		if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {	
			unset($this->params['TENDER']);
			unset($this->params['TRXTYPE']);
			$this->params['PROFILEID']      = $profile_id;
		} else {
			$this->params['ACTION']         = "I";
			$this->params['ORIGPROFILEID']  = $profile_id;
		}
		
		unset($this->params['CURRENCY']);
		
		
		return $this->process();
	}
	
	public function updateProfile($invoice_id) {
		$this->params['METHOD']         = "UpdateRecurringPaymentsProfile";
		
		$invoice = new Web_Invoice_GetInfo($invoice_id);
		$user_id = $invoice->recipient('user_id');

		if (get_option('web_invoice_pfp_authentication') == '3token' || get_option('web_invoice_pfp_authentication') == 'unipay') {
			$this->params['DESC'] = $invoice->display('subscription_name');
			$this->params['PROFILESTARTDATE'] = date('c', strtotime($invoice->display('startDateM')));
			$this->params['TOTALBILLINGCYCLES'] = $invoice->display('totalOccurrences');
			$this->params['NOTE']           = "Related invoice was updated";
			$this->params['PROFILEID']      = web_invoice_meta($invoice_id, 'subscription_id');
			
			$this->params["FIRSTNAME"] = get_usermeta($user_id, 'first_name');
			$this->params["LASTNAME"] = get_usermeta($user_id, 'last_name');
			$this->params["STREET"] = get_usermeta($user_id, 'address');
			$this->params["CITY"] = get_usermeta($user_id, 'city');
			$this->params["STATE"] = get_usermeta($user_id, 'state');
			$this->params["COUNTRYCODE"] = get_usermeta($user_id, 'country');
			$this->params["ZIP"] = get_usermeta($user_id, 'zip');
			$this->params["PHONENUM"] = get_usermeta($user_id, 'phonenumber');
			
			if (get_option('web_invoice_pfp_shipping_details') == 'True') {
				//Shipping Info
				$this->params["SHIPTONAME"] =  get_usermeta($user_id, 'shipto_first_name')." ". get_usermeta($user_id, 'shipto_last_name');
				$this->params["SHIPTOSTREET"] =  get_usermeta($user_id, 'shipto_address');
				$this->params["SHIPTOCITY"] =  get_usermeta($user_id, 'shipto_city');
				$this->params["SHIPTOSTATE"] =  get_usermeta($user_id, 'shipto_state');
				$this->params["SHIPTOCOUNTRY"] =  get_usermeta($user_id, 'shipto_country');
				$this->params["SHIPTOZIP"] =  get_usermeta($user_id, 'shipto_zip');
				$this->params["SHIPTOPHONENUM"] = get_usermeta($user_id, 'shipto_phonenumber');
			}
		} else {
			$this->params['ACTION']         = "M";
			$this->params['ORIGPROFILEID']  = web_invoice_meta($invoice_id, 'subscription_id');;
			
			$this->params['PROFILENAME'] = $invoice->display('subscription_name');
			$this->params['START'] = date('mdY',  strtotime($invoice->display('startDateM'))+3600*24);
			$this->params['TERM'] = $invoice->display('totalOccurrences');
			$this->params['PAYPERIOD'] = web_invoice_pfp_wpppe_convert_interval($invoice->display('interval_length'), $invoice->display('interval_unit'));
			$this->params["COMMENT1"] = get_usermeta($user_id, 'first_name')." ".get_usermeta($user_id, 'last_name')." ".$invoice->display('subscription_name')." Recurring";
			
			$this->params["FIRSTNAME"] = get_usermeta($user_id, 'first_name');
			$this->params["LASTNAME"] = get_usermeta($user_id, 'last_name');
			$this->params["STREET"] = get_usermeta($user_id, 'streetaddress');
			$this->params["CITY"] = get_usermeta($user_id, 'city');
			$this->params["STATE"] = get_usermeta($user_id, 'state');
			$this->params["COUNTRY"] = get_usermeta($user_id, 'country');
			$this->params["ZIP"] = get_usermeta($user_id, 'zip');
			$this->params["PHONENUM"] = get_usermeta($user_id, 'phonenumber');
			
			if (get_option('web_invoice_pfp_shipping_details') == 'True') {
				//Shipping Info
				$this->params["SHIPTONAME"] =  get_usermeta($user_id, 'shipto_first_name')." ". get_usermeta($user_id, 'shipto_last_name');
				$this->params["SHIPTOSTREET"] =  get_usermeta($user_id, 'shipto_streetaddress');
				$this->params["SHIPTOCITY"] =  get_usermeta($user_id, 'shipto_city');
				$this->params["SHIPTOSTATE"] =  get_usermeta($user_id, 'shipto_state');
				$this->params["SHIPTOCOUNTRY"] =  get_usermeta($user_id, 'shipto_country');
				$this->params["SHIPTOZIP"] =  get_usermeta($user_id, 'shipto_zip');
				$this->params["SHIPTOPHONENUM"] = get_usermeta($user_id, 'shipto_phonenumber');
			}
		}
		
		$this->params["AMT"] = $invoice->display('amount');
		$this->params["CURRENCYCODE"] = $invoice->display('currency');
		
		return $this->process();
	}
}
