<?php

/**
 * Plugin Name: give-paybox
 * License: General Public Licence v3 or later
 * Description: Binds wp-paybox to Give payments gateways
 * Author: Raphaël Droz <raphael.droz+floss@gmail.com>
 * Author URI: https://drzraf.me
 * Version: 0.2
 * Text Domain: give-paybox
 * Domain Path: /languages
 * GitHub Plugin URI: https://gitlab.com/drzraf/wp-paybox
 */

define('PAYBOX_TEST_MODE', TRUE);


load_plugin_textdomain( 'give-paybox', false, __DIR__ );

require_once __DIR__ . '/../wp-paybox/class-wp-paybox.php';
require_once __DIR__ . '/../wp-paybox/controller-redirect.php';
require_once __DIR__ . '/../wp-paybox/interface-payboxable.php';

require_once __DIR__ . '/paybox-integration.php' ;

add_action('init', ['Give_Paybox', 'endpoints']);

// mutual integration
add_action('paybox_handle_IPN', ['Give_Paybox', 'handleIPN'], 10, 3);
add_action( 'rest_api_init', function () {
  $ipn = new WP_Paybox_IPN(give_is_setting_enabled(Give_Paybox_Channel::opt('checkip')), WP_Paybox::PBX_RETOUR, WP_Paybox::SOURCE_IPS);
  register_rest_route('wp-paybox/v1' , '/ipn', ['methods' => 'GET', 'callback' => [$ipn, 'init']], TRUE);
});


require_once __DIR__ . '/give-integration.php' ;
add_filter('give_get_payment_transaction_id-paybox', ['Give_Paybox_Gateway', 'get_payment_transaction_id'], 10, 1 );
// add_action('give_view_order_details_before',   ['Give_Paybox_Gateway', 'admin_payment_js']); // still unused
add_action('give_paybox_cc_form',              ['Give_Paybox_Gateway', 'cc_form']);
add_action('give_donation_form_after_cc_form', ['Give_Paybox_Gateway', 'after_cc_form']);
add_action('give_checkout_error_checks',       ['Give_Paybox_Gateway', 'validate_fields'], 10, 2);
add_action('give_gateway_paybox',              ['Give_Paybox_Gateway', 'process_payment']);

// This hook activate the (visible) Give/Paybox integration part (form tweaks + admin config')
if(!PAYBOX_TEST_MODE || is_admin()) {
  add_filter('give_payment_gateways',            ['Give_Paybox_Gateway', 'register_gateway']);
}

if(is_admin()) {
  require __DIR__ . '/give-paybox-admin.php' ;
  add_filter( 'give_get_sections_gateways', 'give_paybox_add_gateway', 10, 1);
  add_filter( 'give_get_settings_gateways', 'give_paybox_add_settings', 10, 1 );
  add_filter( 'give_payment_details_transaction_id-paybox', 'give_paybox_link_transaction_id', 10, 2 );
}
