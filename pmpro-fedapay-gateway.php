<?php
/**
 * Plugin Name: FedaPay Gateway for Paid Memberships Pro
 * Plugin URI: https://wordpress.org/plugins/pmpro-fedapay-gateway/
 * Description: Take credit card and mobile money payments from your members using Fedapay.
 * Author: Fedapay
 * Author URI: https://fedapay.com/
 * Requires at least: 4.4
 * Tested up to: 5.4.2
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: pmpro-fedapay-gateway
 * Domain Path: /languages
 * Version: 0.1.1
 *
 */

define("PMPRO_FEDAPAY_GATEWAY_VERWION", '0.1.1');
define("PMPRO_FEDAPAY_GATEWAY_DIR", dirname(__FILE__));
define('PMPRO_FEDAPAY_WEBHOOK_DELAY', 2);

//load payment gateway class
require_once(PMPRO_FEDAPAY_GATEWAY_DIR . '/includes/class-pmpro-fedapay-gateway.php');
require_once( PMPRO_FEDAPAY_GATEWAY_DIR . '/includes/functions.php' );
