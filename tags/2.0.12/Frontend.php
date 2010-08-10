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

function web_invoice_the_content($content) {
	global $post;
	$ip=$_SERVER['REMOTE_ADDR'];

	// check if web_invoice_web_invoice_page is set, and that this it matches the current page, and the invoice_id is valid
	if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page'))) {
		
		// Check to see a proper invoice id is used, or show regular content
		if(!($invoice_id = web_invoice_md5_to_invoice($_GET['invoice_id']))) return $content;

		// Show receipt if coming back from PayPal
		if(isset($_REQUEST['receipt_id'])) return web_invoice_show_paypal_receipt($invoice_id);

		// Invoice viewed, update log
		web_invoice_update_log($invoice_id,'visited',"Viewed by $ip");

		$content .= web_invoice_generate_html($invoice_id);
		
		return $content;
	} else return $content;
}

function web_invoice_frontend_js() {
	if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page')))  {
		function web_invoice_curPageURL() {
			$host_x = preg_split('/\//', get_option('siteurl'));
			$host = $host_x[2];  
						
			$pageURL = "http://".$host.$_SERVER['REQUEST_URI'];
			
			if(	get_option('web_invoice_force_https') == 'true' ) {  
				$pageURL = preg_replace('/^http/', 'https', $pageURL); 
			}
			return $pageURL;
		}
		?>
<script type="text/javascript">

var _invoice_id = "<?php print $_GET['invoice_id'];?>";

function cc_card_pick(card_image, card_num){
	if (card_image == null) {
		card_image = '#cardimage';
	}
	if (card_num == null) {
		card_num = '#card_num';
	}

	numLength = jQuery(card_num).val().length;
	number = jQuery(card_num).val();
	if (numLength > 10)
	{
		if((number.charAt(0) == '4') && ((numLength == 13)||(numLength==16))) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage visa_card'); }
		else if((number.charAt(0) == '5' && ((number.charAt(1) >= '1') && (number.charAt(1) <= '5'))) && (numLength==16)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage mastercard'); }
		else if(number.substring(0,4) == "6011" && (numLength==16)) 	{ jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage amex'); }
		else if((number.charAt(0) == '3' && ((number.charAt(1) == '4') || (number.charAt(1) == '7'))) && (numLength==15)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage discover_card'); }
		else { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage nocard'); }

	}
}

function process_cc_checkout(type) {
	if (type == null) {
		type = '';
	}

	jQuery('#web_invoice_process_wait span').html('<img src="<?php echo Web_Invoice::frontend_path(); ?>/images/processing-ajax.gif">');

	site_url = '<?php echo web_invoice_curPageURL(); ?>';
	
	if (type == 'pfp') {
		link_id = 'wp_pfp_response';
		_checkout_form = 'pfp_checkout_form';
	} else if (type == 'sagepay') {
		link_id = 'wp_sagepay_response';
		_checkout_form = 'sagepay_checkout_form';
	} else {
		link_id = 'wp_cc_response';
		_checkout_form = 'checkout_form';
	}

	var req = jQuery.post ( site_url, jQuery('#' + _checkout_form).serialize(), function(html) {

			var explode = html.toString().split('\n');
			var shown = false;
			var msg = '<?php _e('<b>There are problems with your transaction:</b>', WEB_INVOICE_TRANS_DOMAIN); ?><ol>';


			for ( var i in explode )
			{
				var explode_again = explode[i].toString().split('|');
				if (explode_again[0]=='error')
				{
					if ( ! shown ) {
						jQuery('#' + link_id).fadeIn("slow");
					}
					shown = true;
					add_remove_class('ok','error',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).html(explode_again[2]); */
					msg += "<li>" + explode_again[2] + "</li>";
				}
				else if (explode_again[0]=='ok') {
					add_remove_class('error','ok',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).hide(); */
				}
			}

			if ( ! shown ) {
				if(html == 'Transaction okay.') {
					jQuery('#' + link_id).fadeIn("slow");
					jQuery('#' + link_id).html("<?php _e('Thank you! <br />Payment processed successfully!', WEB_INVOICE_TRANS_DOMAIN); ?>");
					jQuery("#credit_card_information").hide();
	
					jQuery('#' + link_id).show();
					window.location = site_url;
				}
			} else {
				add_remove_class('success','error',link_id);
				jQuery('#' + link_id).html(msg + "</ol>");
			}
			jQuery('#web_invoice_process_wait span').html('');
			req = null;
		}
	);
}

function process_sagepay_process(type) {
	if (type == null) {
		type = 'form';
	}

	jQuery('#web_invoice_process_wait span').html('<img src="<?php echo Web_Invoice::frontend_path(); ?>/images/processing-ajax.gif">');

	site_url = '<?php echo web_invoice_curPageURL(); ?>';
	
	if (type == 'form') {
		link_id = 'wp_sagepay_response';
		_checkout_form = 'sagepay_checkout_form';
	}

	var req = jQuery.post ( site_url, jQuery('#' + _checkout_form).serialize(), function(html) {

			var explode = html.toString().split('\n');
			var shown = false;
			var msg = '<?php _e('<b>There are problems with your transaction:</b>', WEB_INVOICE_TRANS_DOMAIN); ?><ol>';


			for ( var i in explode )
			{
				var explode_again = explode[i].toString().split('|');
				if (explode_again[0]=='error')
				{
					if ( ! shown ) {
						jQuery('#' + link_id).fadeIn("slow");
					}
					shown = true;
					add_remove_class('ok','error',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).html(explode_again[2]); */
					msg += "<li>" + explode_again[2] + "</li>";
				}
				else if (explode_again[0]=='ok') {
					add_remove_class('error','ok',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).hide(); */
				}
			}

			if ( ! shown )
			{
				jQuery('#sagepay_crypt').val(html);
				jQuery('#sagepay_submit_form').submit();
			} else {
				add_remove_class('success','error',link_id);
				jQuery('#' + link_id).html(msg + "</ol>");
			}
			jQuery('#web_invoice_process_wait span').html('');
			req = null;
		}
	);
}

function add_remove_class(search,replace,element_id)
{
	if (jQuery('#' + element_id).hasClass(search)){
		jQuery('#' + element_id).removeClass(search);
	}
	jQuery('#' + element_id).addClass(replace);
}

</script>
		<?php
	}
}

function web_invoice_frontend_css() {
	if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page')))  {
		echo '<meta name="robots" content="noindex, nofollow" />';

		if(get_option('web_invoice_use_css') == 'yes') {
			echo '<link type="text/css" media="print" rel="stylesheet" href="' . Web_Invoice::frontend_path() . '/css/web_invoice-print.css?2.0.8"></link>' . "\n";
			echo '<link type="text/css" media="screen" rel="stylesheet" href="' . Web_Invoice::frontend_path() . '/css/web_invoice-screen.css?2.0.8"></link>' . "\n";
		}
	}
}

function web_invoice_print_pdf() {
	$ip=$_SERVER['REMOTE_ADDR'];
		
	// Check to see a proper invoice id is used, or show regular content
	if(!($invoice_id = web_invoice_md5_to_invoice($_GET['invoice_id']))) return $content;

	// Invoice viewed, update log
	web_invoice_update_log($invoice_id, 'visited', "PDF downloaded by $ip");
	
	
	$dompdf = web_invoice_pdf_get($invoice_id);
	$dompdf->stream("web-invoice-{$invoice_id}.pdf");
	
	exit(0);
}
