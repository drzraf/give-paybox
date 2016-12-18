<?php

/**
 * Plugin Name: give-paybox
 * License: General Public Licence v3 or later
 * Description: Binds wp-paybox to Give payments gateways
 * Author: Raphaël Droz <raphael.droz+floss@gmail.com>
 * Author URI: https://drzraf.me
 * Version: 0.1
 * Text Domain: give-paybox
 * Domain Path: /languages
 * GitHub Plugin URI: https://gitlab.com/drzraf/wp-paybox
 */


load_plugin_textdomain( 'give-paybox', false, __DIR__ );


require __DIR__ . '/paybox-integration.php' ;
add_action('init', ['Give_Paybox', 'endpoints']);

require __DIR__ . '/give-integration.php' ;
add_filter('give_get_payment_transaction_id-paybox', ['Give_Paybox_Gateway', 'get_payment_transaction_id'], 10, 1 );
add_filter('give_payment_gateways',            ['Give_Paybox_Gateway', 'register_gateway']);
add_action('give_view_order_details_before',   ['Give_Paybox_Gateway', 'admin_payment_js'], 100);
add_action('give_purchase_form_after_cc_form', ['Give_Paybox_Gateway', 'form_infos'], 100);
add_action('give_checkout_error_checks',       ['Give_Paybox_Gateway', 'validate_fields'], 10, 2);
add_action('give_gateway_paybox',              ['Give_Paybox_Gateway', 'process_payment']);


// mutual integration
add_filter('give_payment_confirm_paybox', ['Give_Paybox', 'onClientSuccess']);


if(is_admin()) {
  require __DIR__ . '/give-paybox-admin.php' ;
  add_filter( 'give_settings_gateways', 'give_paybox_add_settings' );
  add_filter( 'give_payment_details_transaction_id-paybox', 'give_paybox_link_transaction_id', 10, 2 );
}
