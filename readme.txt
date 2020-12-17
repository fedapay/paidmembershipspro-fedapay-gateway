=== FedaPay Gateway for Paid Memberships Pro ===
Contributors: fedapay
Tags: pmpro, paid memberships pro, members, memberships, credit card, fedapay, mobile money
Requires at least: 4
Tested up to: 5.5.3
Requires PHP: 5.6
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Take credit card and mobile money subcriptions from your members using FedaPay.

== Description ==

Accept Visa, MasterCard, MTN Mobile Money directly to pay with the FedaPay payment gateway.

= Take Credit card and Mobile Money payments easily and directly on your store =

The FedaPay plugin extends Paid Memberships Pro allowing you to take payments via FedaPay’s API.

FedaPay is available in:

* Benin
* Ivory Coast
* Togo

= Why choose FedaPay? =

FedaPay has no setup fees, no monthly fees, no hidden costs: you only get charged when you earn money! Earnings are transferred to your bank or mobile money account on a 7-day rolling basis.

== Installation ==

1. Make sure you have the Paid Memberships Pro plugin installed and activated.
1. Upload the `pmpro-fedapay-gateway` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Memberships -> Payment Settings in your WordPress dashboard to complete the FedaPay settings.

= Setup =

1. Register and validate your account with FedaPay: https://live.fedapay.com/register.
1. Log in to your FedaPay account and select 'Api' to retrieve your merchant api details.
1. Log in to your WordPress dashboard and navigate to Memberships > Payment Settings.
1. Change your Payment Settings to the "FedaPay" gateway. Save.
1. Enter your FedaPay public and secret key.
1. Set your currency to "West Africa FCFA (XOF)".
1. Save your settings.
1. Copy the webhook URL and go to your FedaPay dashboard to create a webhook with the URL.

== Frequently Asked Questions ==

= Does this support recurring payments, like for subscriptions? =

Not yet!

= Does this require an SSL certificate? =

Yes! In live mode, an SSL certificate must be installed on your site to use FedaPay. In addition to SSL encryption.

= Does this support both production mode and sandbox mode for testing? =

Yes it does - production and sandbox mode is driven by the API keys you use.

= Where can I find documentation? =

For help setting up and configuring, please refer to our [user guide](https://docs.fedapay.com/)

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the Plugin Forum.

== Changelog ==

= 0.1.1 =
* Deploy on Worspress plugin directory

= 0.1.0 =
* Beta release
