<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

defined( 'ABSPATH' ) or exit;

// keep in sync with WP_Paybox_Settings::page_init
function give_paybox_add_settings( $settings ) {
  $paybox_settings = [
    [ 'id'   => 'give_title_paybox',
      'name' => esc_html__( 'Paybox Settings', 'give-paybox' ),
      'desc' => '<hr>'],

    [ 'id'   => 'site',
      'name' => esc_html__('Paybox site value', 'give-paybox'),
      'type' => 'text_small'],

    [ 'id'   => 'rang',
      'name' => esc_html__('Paybox rang value', 'give-paybox'),
      'type' => 'text_small'],

    [ 'id'   => 'ident',
      'name' => esc_html__('Paybox ident value', 'give-paybox'),
      'type' => 'text_small'],

    [ 'id'   => 'hmac',
      'name' => esc_html__('Paybox hmac value', 'give-paybox'),
      'type' => 'text'],

    [ 'id'   => 'checkip',
      'name' => esc_html__("Should IPN endpoint check Paybox source IP?", 'give-paybox'),
      'type' => 'radio_inline',
      'row_classes' => 'give-subfield',
      'options'     => array(
        'yes' => esc_html__( 'Yes', 'give' ),
        'no'  => esc_html__( 'No', 'give' ),
      ),
      'default'     => 'no' ],

    [ 'id'   => 'testmode',
      'name' => esc_html__('Test mode', 'give-paybox'),
      'type' => 'radio_inline',
      'row_classes' => 'give-subfield',
      'options'     => array(
        'yes' => esc_html__( 'Yes', 'give' ),
        'no'  => esc_html__( 'No', 'give' ),
      ),
      'default'     => 'no' ],
                         
    [ 'id'   => '3dmin',
      'name' => esc_html__('Minimum amount for 3D Secure', 'give-paybox'),
      'type' => 'text_money' ],

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
      'type' => 'text_money' ],


    [ 'id'   => 'nbdays',
      'name' => esc_html__('Number of days between multiple payments', 'give-paybox'),
      'type' => 'number' ],

    [ 'id'   => 'limit_payment',
      'name' => esc_html__('Limit payment types', 'give-paybox'),
      'type' => 'select',
      'options' => WP_Paybox::TYPE_PAIEMENT_POSSIBLE, // TODO: No restriction
    ],

    // TODO: if ($this->options['limit_payment'])
    [ 'id'   => 'limit_card',
      'name' => esc_html__('Restriction sur le type de carte', 'give-paybox'),
      'description' =>
      _e('Submit settings in order to select below card restrictions', 'give-paybox') .
      _e('/!\ Not fully implemented yet!', 'give-paybox'),
      'options' => WP_Paybox::TYPE_CARTE[$this->options['limit_payment']] // 'CB' ? 'CB (= Any card)'
    ],
  ];

  return $paybox_settings;
}

function give_paybox_link_transaction_id( $transaction_id, $payment_id ) {
	$test = give_get_payment_meta( $payment_id, '_give_payment_mode' ) === 'test' ? 'test/' : '';
	return sprintf('<a href="https://admin.paybox.com/%s/%s" target="_blank">%s</a>',
                 $test, $transaction_id);
}
