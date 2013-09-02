<?php

function web_invoice_currency_array() {
	$currency_list = array(
        "AED" => array(__("UAE Dirham", WEB_INVOICE_TRANS_DOMAIN),2),
        "AFN" => array(__("Afghani", WEB_INVOICE_TRANS_DOMAIN),2),
        "ALL" => array(__("Lek", WEB_INVOICE_TRANS_DOMAIN),2),
        "AMD" => array(__("Armenian Dram", WEB_INVOICE_TRANS_DOMAIN),2),
        "ANG" => array(__("Netherlands Antillean Guilder", WEB_INVOICE_TRANS_DOMAIN),2),
        "AOA" => array(__("Kwanza", WEB_INVOICE_TRANS_DOMAIN),2),
        "ARS" => array(__("Argentine Peso", WEB_INVOICE_TRANS_DOMAIN),2),
        "AUD" => array(__("Australian Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "AWG" => array(__("Aruban Florin", WEB_INVOICE_TRANS_DOMAIN),2),
        "AZN" => array(__("Azerbaijanian Manat", WEB_INVOICE_TRANS_DOMAIN),2),
        "BAM" => array(__("Convertible Mark", WEB_INVOICE_TRANS_DOMAIN),2),
        "BBD" => array(__("Barbados Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "BDT" => array(__("Taka", WEB_INVOICE_TRANS_DOMAIN),2),
        "BGN" => array(__("Bulgarian Lev", WEB_INVOICE_TRANS_DOMAIN),2),
        "BHD" => array(__("Bahraini Dinar", WEB_INVOICE_TRANS_DOMAIN),3),
        "BIF" => array(__("Burundi Franc", WEB_INVOICE_TRANS_DOMAIN),0),
        "BMD" => array(__("Bermudian Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "BND" => array(__("Brunei Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "BOB" => array(__("Boliviano", WEB_INVOICE_TRANS_DOMAIN),2),
        "BOV" => array(__("Mvdol", WEB_INVOICE_TRANS_DOMAIN),2),
        "BRL" => array(__("Brazilian Real", WEB_INVOICE_TRANS_DOMAIN),2),
        "BSD" => array(__("Bahamian Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "BTN" => array(__("Ngultrum", WEB_INVOICE_TRANS_DOMAIN),2),
        "BWP" => array(__("Pula", WEB_INVOICE_TRANS_DOMAIN),2),
        "BYR" => array(__("Belarussian Ruble", WEB_INVOICE_TRANS_DOMAIN),0),
        "BZD" => array(__("Belize Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "CAD" => array(__("Canadian Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "CDF" => array(__("Congolese Franc", WEB_INVOICE_TRANS_DOMAIN),2),
        "CHF" => array(__("Swiss Franc", WEB_INVOICE_TRANS_DOMAIN),2),
        "CHW" => array(__("WIR Franc", WEB_INVOICE_TRANS_DOMAIN),2),
        "CLF" => array(__("Unidades de fomento", WEB_INVOICE_TRANS_DOMAIN),0),
        "CLP" => array(__("Chilean Peso", WEB_INVOICE_TRANS_DOMAIN),0),
        "CNY" => array(__("Yuan Renminbi", WEB_INVOICE_TRANS_DOMAIN),2),
        "COP" => array(__("Colombian Peso", WEB_INVOICE_TRANS_DOMAIN),2),
        "COU" => array(__("Unidad de Valor Real", WEB_INVOICE_TRANS_DOMAIN),2),
        "CRC" => array(__("Costa Rican Colon", WEB_INVOICE_TRANS_DOMAIN),2),
        "CUC" => array(__("Peso Convertible", WEB_INVOICE_TRANS_DOMAIN),2),
        "CUP" => array(__("Cuban Peso", WEB_INVOICE_TRANS_DOMAIN),2),
        "CVE" => array(__("Cape Verde Escudo", WEB_INVOICE_TRANS_DOMAIN),2),
        "CZK" => array(__("Czech Koruna", WEB_INVOICE_TRANS_DOMAIN),2),
        "DJF" => array(__("Djibouti Franc", WEB_INVOICE_TRANS_DOMAIN),0),
        "DKK" => array(__("Danish Krone", WEB_INVOICE_TRANS_DOMAIN),2),
        "DKK" => array(__("Danish Krone", WEB_INVOICE_TRANS_DOMAIN),2),
        "DKK" => array(__("Danish Krone", WEB_INVOICE_TRANS_DOMAIN),2),
        "DOP" => array(__("Dominican Peso", WEB_INVOICE_TRANS_DOMAIN),2),
        "DZD" => array(__("Algerian Dinar", WEB_INVOICE_TRANS_DOMAIN),2),
        "EGP" => array(__("Egyptian Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "ERN" => array(__("Nakfa", WEB_INVOICE_TRANS_DOMAIN),2),
        "ETB" => array(__("Ethiopian Birr", WEB_INVOICE_TRANS_DOMAIN),2),
        "EUR" => array(__("Euro", WEB_INVOICE_TRANS_DOMAIN),2),
        "FJD" => array(__("Fiji Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "FKP" => array(__("Falkland Islands Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "GBP" => array(__("Pound Sterling", WEB_INVOICE_TRANS_DOMAIN),2),
        "GEL" => array(__("Lari", WEB_INVOICE_TRANS_DOMAIN),2),
        "GHS" => array(__("Ghana Cedi", WEB_INVOICE_TRANS_DOMAIN),2),
        "GIP" => array(__("Gibraltar Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "GMD" => array(__("Dalasi", WEB_INVOICE_TRANS_DOMAIN),2),
        "GNF" => array(__("Guinea Franc", WEB_INVOICE_TRANS_DOMAIN),0),
        "GTQ" => array(__("Quetzal", WEB_INVOICE_TRANS_DOMAIN),2),
        "GYD" => array(__("Guyana Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "HKD" => array(__("Hong Kong Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "HNL" => array(__("Lempira", WEB_INVOICE_TRANS_DOMAIN),2),
        "HRK" => array(__("Croatian Kuna", WEB_INVOICE_TRANS_DOMAIN),2),
        "HTG" => array(__("Gourde", WEB_INVOICE_TRANS_DOMAIN),2),
        "HUF" => array(__("Forint", WEB_INVOICE_TRANS_DOMAIN),2),
        "IDR" => array(__("Rupiah", WEB_INVOICE_TRANS_DOMAIN),2),
        "ILS" => array(__("New Israeli Sheqel", WEB_INVOICE_TRANS_DOMAIN),2),
        "INR" => array(__("Indian Rupee", WEB_INVOICE_TRANS_DOMAIN),2),
        "IQD" => array(__("Iraqi Dinar", WEB_INVOICE_TRANS_DOMAIN),3),
        "IRR" => array(__("Iranian Rial", WEB_INVOICE_TRANS_DOMAIN),2),
        "ISK" => array(__("Iceland Krona", WEB_INVOICE_TRANS_DOMAIN),0),
        "JMD" => array(__("Jamaican Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "JOD" => array(__("Jordanian Dinar", WEB_INVOICE_TRANS_DOMAIN),3),
        "JPY" => array(__("Yen", WEB_INVOICE_TRANS_DOMAIN),0),
        "KES" => array(__("Kenyan Shilling", WEB_INVOICE_TRANS_DOMAIN),2),
        "KGS" => array(__("Som", WEB_INVOICE_TRANS_DOMAIN),2),
        "KHR" => array(__("Riel", WEB_INVOICE_TRANS_DOMAIN),2),
        "KMF" => array(__("Comoro Franc", WEB_INVOICE_TRANS_DOMAIN),0),
        "KPW" => array(__("North Korean Won", WEB_INVOICE_TRANS_DOMAIN),2),
        "KRW" => array(__("Won", WEB_INVOICE_TRANS_DOMAIN),0),
        "KWD" => array(__("Kuwaiti Dinar", WEB_INVOICE_TRANS_DOMAIN),3),
        "KYD" => array(__("Cayman Islands Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "KZT" => array(__("Tenge", WEB_INVOICE_TRANS_DOMAIN),2),
        "LAK" => array(__("Kip", WEB_INVOICE_TRANS_DOMAIN),2),
        "LBP" => array(__("Lebanese Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "LKR" => array(__("Sri Lanka Rupee", WEB_INVOICE_TRANS_DOMAIN),2),
        "LRD" => array(__("Liberian Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "LSL" => array(__("Loti", WEB_INVOICE_TRANS_DOMAIN),2),
        "LTL" => array(__("Lithuanian Litas", WEB_INVOICE_TRANS_DOMAIN),2),
        "LVL" => array(__("Latvian Lats", WEB_INVOICE_TRANS_DOMAIN),2),
        "LYD" => array(__("Libyan Dinar", WEB_INVOICE_TRANS_DOMAIN),3),
        "MAD" => array(__("Moroccan Dirham", WEB_INVOICE_TRANS_DOMAIN),2),
        "MDL" => array(__("Moldovan Leu", WEB_INVOICE_TRANS_DOMAIN),2),
        "MGA" => array(__("Malagasy Ariary", WEB_INVOICE_TRANS_DOMAIN),2),
        "MKD" => array(__("Denar", WEB_INVOICE_TRANS_DOMAIN),2),
        "MMK" => array(__("Kyat", WEB_INVOICE_TRANS_DOMAIN),2),
        "MNT" => array(__("Tugrik", WEB_INVOICE_TRANS_DOMAIN),2),
        "MOP" => array(__("Pataca", WEB_INVOICE_TRANS_DOMAIN),2),
        "MRO" => array(__("Ouguiya", WEB_INVOICE_TRANS_DOMAIN),2),
        "MUR" => array(__("Mauritius Rupee", WEB_INVOICE_TRANS_DOMAIN),2),
        "MVR" => array(__("Rufiyaa", WEB_INVOICE_TRANS_DOMAIN),2),
        "MWK" => array(__("Kwacha", WEB_INVOICE_TRANS_DOMAIN),2),
        "MXN" => array(__("Mexican Peso", WEB_INVOICE_TRANS_DOMAIN),2),
        "MYR" => array(__("Malaysian Ringgit", WEB_INVOICE_TRANS_DOMAIN),2),
        "MZN" => array(__("Mozambique Metical", WEB_INVOICE_TRANS_DOMAIN),2),
        "NAD" => array(__("Namibia Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "NGN" => array(__("Naira", WEB_INVOICE_TRANS_DOMAIN),2),
        "NIO" => array(__("Cordoba Oro", WEB_INVOICE_TRANS_DOMAIN),2),
        "NOK" => array(__("Norwegian Krone", WEB_INVOICE_TRANS_DOMAIN),2),
        "NPR" => array(__("Nepalese Rupee", WEB_INVOICE_TRANS_DOMAIN),2),
        "NZD" => array(__("New Zealand Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "OMR" => array(__("Rial Omani", WEB_INVOICE_TRANS_DOMAIN),3),
        "PAB" => array(__("Balboa", WEB_INVOICE_TRANS_DOMAIN),2),
        "PEN" => array(__("Nuevo Sol", WEB_INVOICE_TRANS_DOMAIN),2),
        "PGK" => array(__("Kina", WEB_INVOICE_TRANS_DOMAIN),2),
        "PHP" => array(__("Philippine Peso", WEB_INVOICE_TRANS_DOMAIN),2),
        "PKR" => array(__("Pakistan Rupee", WEB_INVOICE_TRANS_DOMAIN),2),
        "PLN" => array(__("Zloty", WEB_INVOICE_TRANS_DOMAIN),2),
        "PYG" => array(__("Guarani", WEB_INVOICE_TRANS_DOMAIN),0),
        "QAR" => array(__("Qatari Rial", WEB_INVOICE_TRANS_DOMAIN),2),
        "RON" => array(__("New Romanian Leu", WEB_INVOICE_TRANS_DOMAIN),2),
        "RSD" => array(__("Serbian Dinar", WEB_INVOICE_TRANS_DOMAIN),2),
        "RUB" => array(__("Russian Ruble", WEB_INVOICE_TRANS_DOMAIN),2),
        "RWF" => array(__("Rwanda Franc", WEB_INVOICE_TRANS_DOMAIN),0),
        "SAR" => array(__("Saudi Riyal", WEB_INVOICE_TRANS_DOMAIN),2),
        "SBD" => array(__("Solomon Islands Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "SCR" => array(__("Seychelles Rupee", WEB_INVOICE_TRANS_DOMAIN),2),
        "SDG" => array(__("Sudanese Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "SEK" => array(__("Swedish Krona", WEB_INVOICE_TRANS_DOMAIN),2),
        "SGD" => array(__("Singapore Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "SHP" => array(__("Saint Helena Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "SLL" => array(__("Leone", WEB_INVOICE_TRANS_DOMAIN),2),
        "SOS" => array(__("Somali Shilling", WEB_INVOICE_TRANS_DOMAIN),2),
        "SRD" => array(__("Surinam Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "SSP" => array(__("South Sudanese Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "STD" => array(__("Dobra", WEB_INVOICE_TRANS_DOMAIN),2),
        "SVC" => array(__("El Salvador Colon", WEB_INVOICE_TRANS_DOMAIN),2),
        "SYP" => array(__("Syrian Pound", WEB_INVOICE_TRANS_DOMAIN),2),
        "SZL" => array(__("Lilangeni", WEB_INVOICE_TRANS_DOMAIN),2),
        "THB" => array(__("Baht", WEB_INVOICE_TRANS_DOMAIN),2),
        "TJS" => array(__("Somoni", WEB_INVOICE_TRANS_DOMAIN),2),
        "TMT" => array(__("Turkmenistan New Manat", WEB_INVOICE_TRANS_DOMAIN),2),
        "TND" => array(__("Tunisian Dinar", WEB_INVOICE_TRANS_DOMAIN),3),
        "TOP" => array(__("Pa'anga", WEB_INVOICE_TRANS_DOMAIN),2),
        "TRY" => array(__("Turkish Lira", WEB_INVOICE_TRANS_DOMAIN),2),
        "TTD" => array(__("Trinidad and Tobago Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "TWD" => array(__("New Taiwan Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "TZS" => array(__("Tanzanian Shilling", WEB_INVOICE_TRANS_DOMAIN),2),
        "UAH" => array(__("Hryvnia", WEB_INVOICE_TRANS_DOMAIN),2),
        "UGX" => array(__("Uganda Shilling", WEB_INVOICE_TRANS_DOMAIN),0),
        "USD" => array(__("US Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "UYI" => array(__("Uruguay Peso en Unidades Indexadas", WEB_INVOICE_TRANS_DOMAIN),0),
        "UYU" => array(__("Peso Uruguayo", WEB_INVOICE_TRANS_DOMAIN),2),
        "UZS" => array(__("Uzbekistan Sum", WEB_INVOICE_TRANS_DOMAIN),2),
        "VEF" => array(__("Bolivar", WEB_INVOICE_TRANS_DOMAIN),2),
        "VND" => array(__("Dong", WEB_INVOICE_TRANS_DOMAIN),0),
        "VUV" => array(__("Vatu", WEB_INVOICE_TRANS_DOMAIN),0),
        "WST" => array(__("Tala", WEB_INVOICE_TRANS_DOMAIN),2),
        "XAF" => array(__("CFA Franc BEAC", WEB_INVOICE_TRANS_DOMAIN),0),
        "XCD" => array(__("East Caribbean Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
        "YER" => array(__("Yemeni Rial", WEB_INVOICE_TRANS_DOMAIN),2),
        "ZAR" => array(__("Rand", WEB_INVOICE_TRANS_DOMAIN),2),
        "ZMW" => array(__("Zambian Kwacha", WEB_INVOICE_TRANS_DOMAIN),2),
        "ZWL" => array(__("Zimbabwe Dollar", WEB_INVOICE_TRANS_DOMAIN),2),
	);
	
	asort($currency_list);

	return $currency_list;
}

function web_invoice_currency_allowed_array() {
    $allowed_currencies = get_option('web_invoice_allowed_currencies', array('USD','EUR'));
    $all_currencies = web_invoice_currency_array();
    $currency_list = array();
    
    foreach ($allowed_currencies as $currency) {
        $currency_list[$currency] = $all_currencies[$currency];   
    }
    
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
		'CHF' => 'CHF',
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
		'CHF' => __('CHF %s', WEB_INVOICE_TRANS_DOMAIN),
	);

	$success = false;
	
	foreach($currency_list as $value => $display)
	{
		if($currency == $value) { return $display; $success = true; break;}
	}
	if(!$success) return __("{$currency}%s", WEB_INVOICE_TRANS_DOMAIN);
}

function web_invoice_display_payment($currency, $amount) {
	return sprintf(web_invoice_currency_symbol_format($currency), web_invoice_currency_format($amount, $currency));
}

function web_invoice_currency_format($amount, $currency_code) {
	return number_format($amount, web_invoice_currency_minor($currency_code), __('.', WEB_INVOICE_TRANS_DOMAIN), __(',', WEB_INVOICE_TRANS_DOMAIN));
}

function web_invoice_currency_minor($currency_code) {
    $currency = web_invoice_currency_array();
    if (isset($currency[$currency_code]) && isset($currency[$currency_code][1])) {
        return intval($currency[$currency_code][1]);
    }
    return 2;
}