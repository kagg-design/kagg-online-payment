<?php
/**
 * Plugin Name: KAGG Online Payment
 * Plugin URI: https://kagg.eu/en
 * Description: Allow online payment for certain products only
 * Version: 2.0
 * Author: Ivan Ovsyannikov, KAGG Design
 * Author URI: https://kagg.eu/en
 *
 * @package kagg-online-payment
 */

namespace KAGG\OnlinePayment;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	// @codeCoverageIgnoreStart
	exit;
	// @codeCoverageIgnoreEnd
}

/**
 * Plugin version.
 */
const KAGG_ONLINE_PAYMENT_VERSION = '2.0.0';

/**
 * Path to the plugin dir.
 */
const KAGG_ONLINE_PAYMENT_PATH = __DIR__;

/**
 * Plugin dir url.
 */
define( 'KAGG_ONLINE_PAYMENT_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * Main plugin file.
 */
const KAGG_ONLINE_PAYMENT_FILE = __FILE__;

require_once KAGG_ONLINE_PAYMENT_PATH . '/vendor/autoload.php';

( new Main() )->init();
