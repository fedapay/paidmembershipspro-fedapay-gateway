<?php
/**
 * Plugin Name: Paid Memberships Pro FedaPay Gateway
 * Plugin URI: https://wordpress.org/pmpro-gateway-fedapay/
 * Description: Take credit card and mobile money payments from your members using Fedapay.
 * Author: Fedapay
 * Author URI: https://fedapay.com/
 * Requires at least: 4.4
 * Tested up to: 5.4.2
 * WC requires at least: 2.6
 * WC tested up to: 4.3.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: pmpro-fedapay-gateway
 * Domain Path: /languages
 * Version: 0.1.0
 *
 */

define("PMPRO_FEDAPAY_GATEWAY_VERWION", '0.1.0');
define("PMPRO_FEDAPAY_GATEWAY_DIR", dirname(__FILE__));

//load payment gateway class
require_once(PMPRO_FEDAPAY_GATEWAY_DIR . '/includes/class-pmpro-fedapay-gateway.php');
require_once( PMPRO_FEDAPAY_GATEWAY_DIR . '/includes/functions.php' );
