<?php 
/*
  Plugin Name: Payment Gateways
  Plugin URI: http://russian-payments.ru
  Description: Принимайте оплату Банковской картой, Яндекс, Вебмани, Робокасса, Qiwi в магазине на плагине WooCommerce.
  Version: 0.1
  Author: Vitalij Ananev
  Author URI: mailto:support@russian-payments.ru
  Documentstion:
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action( 'plugins_loaded', 'init_wc_rpg' );


 /**
 * Add roubles in currencies
 */
 
function rpg_rub_currency_symbol( $currency_symbol, $currency ) {
    if($currency == "RUB" and empty($currency_symbol)) {
        $currency_symbol = 'руб.';
    }
    return $currency_symbol;
}

function rpg_rub_currency( $currencies ) {
	if(in_array("RUB", $currencies)) return $currencies;
    $currencies["RUB"] = 'Russian Roubles';
    return $currencies;
}

add_filter( 'woocommerce_currency_symbol', 'rpg_rub_currency_symbol', 10, 2 );
add_filter( 'woocommerce_currencies', 'rpg_rub_currency', 10, 1 );

function init_wc_rpg(){

	// if the WC payment gateway class is not available, do nothing
	if (!class_exists('WC_Payment_Gateway') or class_exists('WC_rpg')) return;

	// adding gateway for wc gateway list
	add_filter( 'woocommerce_payment_gateways', 'add_rpg_gateway_class' );
	function add_rpg_gateway_class( $methods ) {
		$methods[] = 'WC_Rpg';
		$methods[] = 'WC_Rpg_Yandex';
		$methods[] = 'WC_Rpg_Webmoney';
		$methods[] = 'WC_Rpg_Card';
		$methods[] = 'WC_Rpg_Robokassa';
		return $methods;
	}
	define('RPG_ROOT_URL', plugin_dir_url(__FILE__) );
	// abstract is parent for all
	require_once dirname(__FILE__) . '/includes/wc-class-abstract-rpg.php';
	// class is containing clobal settings and process result/success/fail responce
	require_once dirname(__FILE__) . '/includes/wc-class-rpg.php';
	// abstract class for all gateways
	require_once dirname(__FILE__) . '/includes/wc-class-rpg-gateway.php';
	// yandex gateway class
	require_once dirname(__FILE__) . '/includes/wc-class-rpg-yandex.php';
	// webmoney gateway class
	require_once dirname(__FILE__) . '/includes/wc-class-rpg-webmoney.php';
	// bank card gateway class
	require_once dirname(__FILE__) . '/includes/wc-class-rpg-card.php';
	// robokassa gateway class
	require_once dirname(__FILE__) . '/includes/wc-class-rpg-robokassa.php';
}
?>