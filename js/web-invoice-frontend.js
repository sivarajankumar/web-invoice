var _web_invoice_method_count = 0;

jQuery(document).ready( function() {
	if (_web_invoice_method_count > 1) {
		jQuery(".payment_form").hide();
	}
	jQuery(".noautocomplete").attr("autocomplete", "off");
	jQuery("#payment_methods a").click( function() {
		jQuery(".payment_form").hide();
		_web_invoice_anchor = jQuery(this).attr("href").split("#");
		jQuery('#'+_web_invoice_anchor[_web_invoice_anchor.length-1]).show();
	});
	
	jQuery(".country").change(function () {
		_state_field = jQuery(this).parent().parent().find('.state');
		if (jQuery(this).val() != "US") {
			_state_field.val('');
			_state_field.parent().hide();
		} else {
			_state_field.parent().show();
		}
	});
	jQuery(".country").change();
	
	jQuery(".shipto_country").change(function () {
		_state_field = jQuery(this).parent().parent().find('.shipto_state');
		if (jQuery(this).val() != "US") {
			_state_field.val('');
			_state_field.parent().hide();
		} else {
			_state_field.parent().show();
		}
	});
	jQuery(".shipto_country").change();
	
	jQuery(".COUNTRY").change(function () {
		_state_field = jQuery(this).parent().parent().find('.STATE');
		if (jQuery(this).val() == "GB") {
			_state_field.val('');
			_state_field.parent().hide();
		} else {
			_state_field.parent().show();
		}
	});
	jQuery(".COUNTRY").change();
	
	jQuery(".COUNTRYTOSHIP").change(function () {
		_state_field = jQuery(this).parent().parent().find('.STATETOSHIP');
		if (jQuery(this).val() == "GB") {
			_state_field.val('');
			_state_field.parent().hide();
		} else {
			_state_field.parent().show();
		}
	});
	jQuery(".COUNTRYTOSHIP").change();
});

function payflow_copy_billing(suffix) {
	_payflow_billing_fields = ['NAME', 'EMAIL', 'PHONE', 'ADDRESS', 'CITY', 'STATE', 'ZIP', 'COUNTRY'];
	
	for (_i=0; _i<_payflow_billing_fields.length; _i++) {
		jQuery('form#payflow_form #'+_payflow_billing_fields[_i]+suffix).val(jQuery('form#payflow_form #'+_payflow_billing_fields[_i]).val());
	}
	jQuery(".COUNTRYSHIP").change();
}

function pfp_copy_billing(prefix) {
	_pfp_billing_fields = ['first_name', 'last_name', 'phonenumber', 'email_address', 'address', 'city', 'state', 'zip', 'country'];
	
	for (_i=0; _i<_pfp_billing_fields.length; _i++) {
		jQuery('form#pfp_checkout_form #'+prefix+'_'+_pfp_billing_fields[_i]).val(jQuery('form#pfp_checkout_form #'+_pfp_billing_fields[_i]).val());
	}
	jQuery(".shipto_country").change();
}

function sagepay_copy_billing(prefix) {
	_sagepay_billing_fields = ['first_name', 'last_name', 'phonenumber', 'email_address', 'address', 'city', 'state', 'zip', 'country'];
	
	for (_i=0; _i<_sagepay_billing_fields.length; _i++) {
		jQuery('form#sagepay_checkout_form #'+prefix+'_'+_sagepay_billing_fields[_i]).val(jQuery('form#sagepay_checkout_form #'+_sagepay_billing_fields[_i]).val());
	}
	jQuery(".shipto_country").change();
}
