<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

defined( 'ABSPATH' ) or exit;


function give_paybox_add_gateway($sections) {
  $sections += array('paybox' => esc_html__( 'Paybox', 'give-paybox' ));
  return $sections;
}

// keep in sync with WP_Paybox_Settings::page_init
function give_paybox_add_settings( $settings ) {
  $current_section = give_get_current_setting_section();
  if ($current_section !== 'paybox') {
    return $settings;
  }

  $paybox_settings = [
    // Section X: Paybox
    [
      'type' => 'title',
      'id'   => 'give_title_gateway_settings_paybox',
    ],

    [ 'id'   => 'give_title_paybox',
      'name' => esc_html__( 'Paybox Settings', 'give-paybox' ),
      'desc' => '<hr>'],

    [ 'id'   => 'site',
      'name' => esc_html__('Paybox site value', 'give-paybox'),
      'type' => 'text',
      'default' => '1999888'],

    [ 'id'   => 'rang',
      'name' => esc_html__('Paybox rang value', 'give-paybox'),
      'type' => 'text',
      'default' => '43'],

    [ 'id'   => 'ident',
      'name' => esc_html__('Paybox ident value', 'give-paybox'),
      'type' => 'text',
      'default' => '107975626'],

    [ 'id'   => 'hmac',
      'name' => esc_html__('Paybox hmac value', 'give-paybox'),
      'type' => 'text',
      'default' => str_repeat('0123456789ABCDEF',8)],

    [ 'id'   => 'checkip',
      'name' => esc_html__("Should IPN endpoint check Paybox source IP?", 'give-paybox'),
      'type' => 'radio_inline',
      'row_classes' => 'give-subfield',
      'options'     => array(
        'yes' => esc_html__( 'Yes', 'give' ),
        'no'  => esc_html__( 'No', 'give' ),
      ),
      'default'     => 'no' ],

    [ 'id'   => '3dmin',
      'name' => esc_html__('Minimum amount for 3D Secure', 'give-paybox'),
      'type' => 'number' ],

    [ 'id'   => 'accept3x',
      'name' => esc_html__('Accept 3x payments', 'give-paybox'),
      'type' => 'radio_inline',
      'row_classes' => 'give-subfield',
      'options'     => array(
        'yes' => esc_html__( 'Yes', 'give' ),
        'no'  => esc_html__( 'No', 'give' ),
      ),
      'default'     => 'no' ],

    [ 'id'   => '3xmin',
      'name' => esc_html__('The minimum amount to activate the multiple payments', 'give-paybox'),
      'type' => 'number' ],


    [ 'id'   => 'nbdays',
      'name' => esc_html__('Number of days between multiple payments', 'give-paybox'),
      'type' => 'number' ],

    [ 'id'   => 'limit_payment',
      'name' => esc_html__('Limit payment types', 'give-paybox'),
      'type' => 'select',
      'options' => WP_Paybox::TYPE_PAIEMENT_POSSIBLE, // TODO: No restriction
    ],

    // TODO: if ($this->options['limit_payment'])
    /*
    [ 'id'   => 'limit_card',
      'name' => esc_html__('Restriction sur le type de carte', 'give-paybox'),
      'description' =>
      _e('Submit settings in order to select below card restrictions', 'give-paybox') .
      _e('/!\ Not fully implemented yet!', 'give-paybox'),
      'options' => WP_Paybox::TYPE_CARTE[xxx->options['limit_payment']] // 'CB' ? 'CB (= Any card)'
    ],*/

    [
      'type' => 'sectionend',
      'id'   => 'give_title_gateway_settings_paybox',
    ]

  ];

  return $paybox_settings;
}

function give_paybox_link_transaction_id( $transaction_id, $payment_id ) {
	$test = give_get_payment_meta( $payment_id, '_give_payment_mode' ) === 'test';
	return sprintf('<a href="https://%s.paybox.com/%s" target="_blank" title="%s">%s</a>',
                 $test ? 'preprod-admin' : 'admin',
                 $transaction_id,
                 __('See in the Paybox UI'),
                 $transaction_id);
}
