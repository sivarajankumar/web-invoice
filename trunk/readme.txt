=== Web Invoice - Invoicing and billing for WordPress ===
Contributors: mohanjith
Donate link: http://mohanjith.com/c/wordpress
Tags: bill, moneybookers, google checkout, alertpay, paypal, invoice, pay, online payment, send invoice, bill clients, authorize.net, credit cards, recurring billing, ARB
Requires at least: 2.6
Tested up to: 3.0.0
Stable tag: 2.0.7

Web-Invoice lets you create and send web invoices and setup recurring billing for your clients.

== Description ==

Web Invoice lets WordPress and WordPress MU blog owners send itemized invoices to their clients. Ideal for web developers, SEO consultants, general contractors, or anyone with a WordPress blog and clients to bill. The plugin ties into WP's user management database to keep track of your clients and their information.

Once an invoice is created from the WP admin section, an email with a brief description and a unique link is sent to client. Clients follow the link to your blog's special invoice page, view their invoice, and pay their bill using Moneybookers, Google Checkout, AlertPay or PayPal. The control panel is very user-friendly and intuitive.

Credit card payments may be accepted via Authorize.net, MerchantPlus' NaviGate, Moneybookers, Google Checkout, AlertPay or PayPal account.  For recurring billing we have integrated Authorize.net's ARB API that will allow you to setup payment schedules along with invoices.

Some features:

* Create invoices from the WordPress control panel
* Prefill customer information using the WordPress user list
* Download invoice as PDF document
* Send invoice notifications to customers with a secured link back to the web invoice
* Send invoice reminders to customers with a secured link back to the web invoice
* Accept credit card payment via PayPal Payflow, Authorize.net, Sage Pay or MerchantPlus NaviGate
* Moneybookers, AlertPay, Google Checkout or PayPal available if you don't have a credit card processing account
* Setup recurring billing using Authorize.net's ARB (Automatic Recurring Billing) feature, Google Checkout Subscriptions, PayPal, AlertPay or Moneybookers
* Force web invoice pages to be viewed in SSL mode
* Archive old invoices
* Easily use old invoices as templates for new ones
* Dynamic and intuitive user interface
* Automatically mark invoices paid via Moneybookers (Requires merchant status), AlertPay 
  (Requires merchant status), PayPal, Payflow, Payflow Pro, Sage Pay, Authorize.net or Google Checkout as paid 
* Split gateway support (Your client is given the option of choosing the preferred gateway from
  the list of gateways you support). e.g PayPal and Moneybookers
* All user interfaces are internationalized
* E-mail templates and hooks to allow you to customize the e-mails sent to your clients
* Integrate with other plugins, actions available: web_invoice_mark_as_paid, web_invoice_content_append, web_invoice_front_top, web_invoice_front_bottom, web_invoice_front_top, web_invoice_front_paid, web_invoice_front_unpaid, web_invoice_invoice_save, web_invoice_process_settings, web_invoice_display_settings, web_invoice_invoice_restart_recurring, web_invoice_invoice_pause_recurring
* Works with WordPress 3.x and WPMU 3.x

Sponsored features:

* Google Checkout support sponsored by Aaron Petz, http://aaronpetz.com
* Google Checkout subscriptions and PayPal Payflow support sponsored by Sean Ham, http://consulting.dynamisart.com
* Sage Pay support sponsored by Euan Robertson, http://euan.co.uk/
* AlertPay subscriptions support sponsored by Nathan Prescott, http://neopetsguru.com/

Would you like to see this plugin in other languages? Please show your interest in
the [Web Invoice community forum](http://mohanjith.com/forum/forum.php?id=1).

Web Invoice is already translated to:

* Italian (it_IT) by Dukessa
* Belarusian (be_BY) by iam, http://www.antsar.info
* Portugese (pt_BR) by André Luiz, http://andrewebmaster.com.br
* Spanish (es_ES) by Danilo Casati, http://www.e-rgonomy.com/
* Swedish (sv_SE) by Lena Petersson, http://designerstudion.se/
* French (fr_FR) by Aphrodite, http://mgr-artagency.com/

If you like this plugin please give it a good rating, and consider saying thanks, sponsoring a feature or making a donation.

Plug-in uses [dompdf](http://www.digitaljunkies.ca/dompdf/) to generate PDF documents.

This is a fork of [WP-Invoice](http://wordpress.org/extend/plugins/wp-invoice/), however now lot of things have changed since.

== Installation ==

1. Upload `web-invoice` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Follow set-up steps on main 'Web Invoice' page
1. To create your first invoice navigate to 'Web Invoice' -> 'New Invoice', and select the user who will be the recipient

Please see the [Web Invoice plugin home page](http://mohanjith.com/wordpress/web-invoice.html) for details.

== Frequently Asked Questions ==

1. Does this plugin support WordPress 3.x and WPMU 3.0? Yes

Please visit the [Web Invoice community forum](http://mohanjith.com/forum/forum.php?id=1) for suggestions and help.

Please visit the [Web Invoice issue tracker](http://code.google.com/p/web-invoice/issues/list) to report any bugs 
or submit feature requests.

== Screenshots ==

1. Invoice Overview
1. New Invoice Creation
1. Client Email Preview
1. Frontend Example

== Change Log ==

**Version 2.0.8**

* Fixed issue 26: "Same As Billing" does not copy country
* Fixed issue 27: "Tax" field seems to have control characters in it. e.g. "s:4:" or "s:0:"
* Fixed issue 28: Web/HTML Invoice Template
* Fixed issue 29: Tabbed settings and templates page

**Version 2.0.7**

* Fixed issue 22: PayPal recurring payments
* Fixed issue 24: Not all date strings are localized
* Fixed issue 25: Duplicate content on invoice page

**Version 2.0.6**

* Fixed issue 21: Call to undefined function sys_get_temp_dir()
* Fixed issue 20: Persistent Invoice Sorting
* Fixed issue 19: Check memory available and warn user before running out of memory

**Version 2.0.5**

* Fixed issue 17: Invalid argument supplied for foreach()

**Version 2.0.4**

* Slashing issue in from name in mails
* Fixed issue 2: logo implementation for pdf
* Fixed issue 7: Attach PDF invoice to e-mail
* Fixed issue 5: Email 'From' Error
* Fixed issue 11: Bug in add user dropdown on add new invoice page
* Fixed issue 6: Users missing in Drop Down list
* Fixed issue 10: Date of transaction in admin overview page
* French localization
* Fixed issue 14: Add new invoice button on Invoice overview page
* Fixed issue 4: Support for multiple tax

**Version 2.0.3**

* Invoice page bug fix

**Version 2.0.2**

* PDF UTF support
* Swedish localization
* Multiple Tax rates
* WPMU Receipt e-mail issue

**Version 2.0.1**

* Fixed; values greather than 1000 (ones with 1000 seperator) of some currencies are truncated. 

**Version 2.0.0**

* Compatibility with WPMU
* PDF permission issue - take 2
* Select correct charset (inherit from Wordpress) when creating tables
* WordPress 3.0 compatibility
* PayPal IPN

**Version 1.12.15**

* SagePay VendorEMail added
* Possible fix for redirect to SSL after payment issue 

**Version 1.12.14**

* Google Checkout XML error when amount is greater than 1000

**Version 1.12.13**

* Localized missed phrases ('Quantity', 'Tax', 'Invoice Total'). Thanks Martin Kamensky.

**Version 1.12.12**

* Fixing amounts greater than 1000 shown inaccurately
* Item description in PDF was too tiny

**Version 1.12.11**

* Fix recurring billing layout
* Imrove PDF layout
* Move money formatting to locale

**Version 1.12.10**

* New display property profileEndDate

**Version 1.12.9**

* Moved invoice history bellow rest of invoice content

**Version 1.12.8**

* Record start date when a recurring payment starts
* Not set the Profile start date to current date when updating Payflow profiles
* Fixed Authorize.net issues

**Version 1.12.7**

* Added new hooks/actions web_invoice_invoice_pause_recurring and web_invoice_invoice_restart_recurring

**Version 1.12.6**

* Pause and recurring PayPal Payflow Pro profiles
* Update shipping details on recurring PayPal Payflo Pro profiles

**Version 1.12.5**

* Google Checkout UK VAT issue
* DOMPDF EUR work around (€ displayed as ?)

**Version 1.12.4**

* Added variables invoice_id and invoice_hash to e-mail templates

**Version 1.12.3**

* Changed date formats for en_US and en_GB
* Changed 'Bill To' and 'Bill From' to 'Invoice From' and 'Invoice To'

**Version 1.12.2**

* More improvements to invoice layout

**Version 1.12.1**

* HTML Syntax issue (missed closing tag)

**Version 1.12.0**

* Much awaited download invoice as PDF

**Version 1.11.14**

* Update Payflow Pro recurring profiles when invoice is updated
* Cancel Payflow Pro recurring profiles when invoice is deleted

**Version 1.11.13**

* Website Payments Pro HTTPS interface headers
* Load archived invoices via AJAX

**Version 1.11.12**

* Bug fix

**Version 1.11.11**

* PayPal Website Payments Pro Payflow Edition bug fixes 

**Version 1.11.10**

* Added experimental PayPal Website Payments Pro Payflow Edition support

**Version 1.11.9**

* SagePay issue, state

**Version 1.11.8**

* Do not show ugly PHP error when associated user is deleted

**Version 1.11.7**

* AlertPay recurring payment
* Allow non privileged users to create invoices for self
* PayPal yearly invoice fix
* Fix for some settings when localized

**Version 1.11.6**

* Portugese (Brazil) localization

**Version 1.11.5**

* Work around notorious plugins that include their own version of jQuery

**Version 1.11.4**

* Hide State when not required

**Version 1.11.3**

* Fixed IE js issues

**Version 1.11.2**

* Redirect to correct invoice when the Sage Pay transaction fails

**Version 1.11.1**

* Upgrading leaves payment table schema out of sync

**Version 1.11.0**

* Fixed missing HTML end tags and moved autocomplete="off" to js (Better standard compliance)
* Experimental Sage Pay Form support

**Version 1.10.10**

* Shipping details added to user profile

**Version 1.10.9**

* Fixed typo
* Graduated PayPal recurring billing to production ready

**Version 1.10.8**

* PayPal payflow pro shipping details

**Version 1.10.7**

* PayPal payflow pro mark invoice as paid bug fix.

**Version 1.10.6**

* Added new actions (web_invoice_invoice_save, web_invoice_process_settings, and web_invoice_display_settings)

**Version 1.10.5**

* Fixed Google Checkout tax state issue

**Version 1.10.4**

* PayPal Payflow Pro support
* Automatic Payflow Recurring profile creation

**Version 1.10.3**

* Recurring payments with PayPal

**Version 1.10.2**

* PayPal Payflow support is production ready (upgraded from experimental)

**Version 1.10.1**

* Bug fixes, Google Checkout mark invoice as paid (2nd Level integration)
* Google Checkout Subscription (recurring) support
* PayPal Payflow support (experimental)

**Version 1.9.22**

* Bug fixes, more permission issues in new installations

**Version 1.9.21**

* Bug fixes, permission issue in new installations

**Version 1.9.20**

* Capabilities based authentication

**Version 1.9.19**

* Use wp_dropdown_roles to list the roles (Makes Web Invoice compatible with Capabilities Manager plugin)

**Version 1.9.18**

* More actions to integrate with other plugins 

**Version 1.9.17**

* Strip slashes when displaying invoice
* List user invoices under Profile
* Do not list create invoice under Profile for users who lack permission to create invoices

**Version 1.9.16**

* Send itemised invoices to Google Checkout
* Small improvements to print view (as suggested by nv1962)

**Version 1.9.15**

* Mark recurring invoices as paid
* Send reminders for recurring invoices

**Version 1.9.14**

* New Moneybookers IPs

**Version 1.9.13**

* Bug fix

**Version 1.9.12**

* Other/Bank as payment option
* Show billing billing details

**Version 1.9.11**

* Bug fixes

**Version 1.9.10**

* Localized to Belarusian
* Moneybookers recurring payment bug fix

**Version 1.9.9**

* Localized to Italian
* Company name and Tax ID as user information
* Billing Tax ID
* Display Billing Phone and TAX ID

**Version 1.9.8**

* Moneybookers recurring payment IPN bug fix

**Version 1.9.7**

* IP verification bug fix (Moneybookers and AlertPay)

**Version 1.9.6**

* Bug fixes

**Version 1.9.5**

* Secure URLs to payment method logos
* Separate address/username for Moneybookers recurring payments

**Version 1.9.4**

* Tested with WordPress 2.8.4
* Compatibility with MySQL 4.0.x

**Version 1.9.3**

* Fixed javascript error outside of invoice page

**Version 1.9.2**

* Usability fixes

**Version 1.9.1**

* Re-released because the payment forms used Google Checkout sandbox

**Version 1.9.0**

* Google Checkout support

**Version 1.8.0**

* Compatibility with WordPress 2.8.0
* Upgrade jquery.calculation, jquery.delegate, jquery.field and jquery.form to latest available

**Version 1.7.5**

* IE not showing payment forms
* Updating invoice forces the invoice data cache to purge
* Add support for WP Mail SMTP (wp_mail)

**Version 1.7.4**

* Bug fixes

**Version 1.7.3**

* Bug fixes

**Version 1.7.2**

* Call to undefined function web_invoice_curpageurl when CC is enabled

**Version 1.7.1**

* Added database table health status
* Activation and Deactivation hooks may not have fired if
  the plugin directory is symlinked

**Version 1.7.0**

* States are no longer selectable (it's just a text box)
* AlertPay API bug fix
* AlertPay IP range added (more security)
* AlertPay country code (ISO_3166-1 alpha-3 country code) to ISO_3166-1 alpha-2 country code mapping
* Allow just the button payments for PayPal
* E-mail templates
* E-mail hooks (add you own variables via another plugin)

**Version 1.6.3**

* Moneybookers API tested thoroughly (Thank you MB for the test account)
* Receipt e-mail made more meaning full
* Amount decimal points fixed (using number_format)
* Dates and number formatting internationalized

**Version 1.6.2**

* Formatting issue in invoice display

**Version 1.6.1**

* Show unit price when displaying quantity. Change cost to the product of quantity and unti price
* Use css label missing

**Version 1.6.0**

* Moneybookers recurring billing support

**Version 1.5.4**

* Currency symbol shown as html entity in the mailed invoice
* MB API interop fix

**Version 1.5.3**

* Fixed issue with street address, phone number and country of clients
  being reset every upgrade.
* Bug fixes (Due date)

**Version 1.5.2**

* Fixed display issue with MB

**Version 1.5.1**

* Added translations for en, en_US, en_GB
* Fixed issue with Moneybookers when there are more than 5 items
  and itemized details are sent to MB.
* Fixed issue with negative quantity or price in any payment
  processor

**Version 1.5.0**

* Internationalization

**Version 1.4.0**

* Split Gateway support

**Version 1.3.0**

* Add support for AlertPay
* Support AlertPay IPN (Similar to PayPal IPN)

**Version 1.2.4**

* Corrected typo (Reciept => Receipt)

**Version 1.2.3**

* Moneybookers API bug fixes (Using POST instead of GET)

**Version 1.2.2**

* Better debugging for Moneybookers API
* Send reminders
* Bug fixes from 1.2.1

**Version 1.2.1**

* Bug fixes from 1.2.0

**Version 1.2.0**

* Support Moneybookers API (Similar to PayPal IPN)

**Version 1.1.2**

* Made compatible with PHP4

**Version 1.1.1**

* Made compatible with PHP4
* When the invoice doesn't save, the MySQL error code is given along with
  other information.
* Bug fixes from 1.1.0

**Version 1.1.0**

* Using SQL to find the invoice id from the md5 hash
* Improved SQL queries for efficiency
* Halved number of queries

**Version 1.0.0**

* Initial release
