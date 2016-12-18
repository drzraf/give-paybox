<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

class Give_Paybox_Gateway {

  public $email;

  static function register_gateway( $gateways ) {
    $gateways['stripe'] = array(
      'admin_label'    => esc_html__( 'CB with Paybox', 'give-paybox' ),
      'checkout_label' => esc_html__( 'CB with Paybox', 'give-paybox' ),
    );
  }

  static function admin_payment_js( $payment_id = 0 ) {
    if ( give_get_payment_gateway($payment_id) !== 'paybox') {
      return;
    }
    echo __METHOD__;
  }

  static function get_payment_transaction_id( $payment_id ) {
    $notes          = give_get_payment_notes( $payment_id );
    $transaction_id = '';
    foreach ( $notes as $note ) {
      if ( preg_match( '/^Paybox ID: ([^\s]+)/', $note->comment_content, $match ) ) {
        $transaction_id = $match[1];
        continue;
      }
    }
    return $transaction_id;
  }

  static function form_infos($form_id) {
    if (give_get_option('accept3x')) {
      wp_register_script('paybox-3x-js', plugins_url( 'assets/js/payment3x-option.js', __FILE__ ));
      wp_enqueue_script('paybox-3x-js');
      load_template( __DIR__ . '/templates/add-payment3x-option.tpl.php');
    }
    _e("You will be directed to Paybox in order to process the secured payment");
  }

  static function validate_fields($data, $posted) {
    /*
      if (! isset( $data['gateway']) || $data['gateway'] !== 'paybox' ) return;
      if ( empty( $posted['user_email'] ) ) {
      give_set_error( 'foo', esc_html__( 'The email is needed.', 'give-paybox' ) );
      give_record_gateway_error( 'Paybox Error', esc_html__( 'foo.', 'give-paybox' ) );
      }
      */
  }


  // cf Give-core's give_process_paypal_purchase()
  static function process_payment( $purchase_data ) {
    $form_id  = intval( $purchase_data['post_data']['give-form-id'] );
    $price_id = isset( $purchase_data['post_data']['give-price-id'] ) ? $purchase_data['post_data']['give-price-id'] : '';

    // Collect payment data.
    $payment_data = array(
      'price'           => $purchase_data['price'],
      'give_form_title' => $purchase_data['post_data']['give-form-title'],
      'give_form_id'    => $form_id,
      'give_price_id'   => $price_id,
      'date'            => $purchase_data['date'],
      'user_email'      => $purchase_data['user_email'],
      'purchase_key'    => $purchase_data['purchase_key'],
      'currency'        => give_get_currency(),
      'user_info'       => $purchase_data['user_info'],
      'status'          => 'pending',
      'gateway'         => 'paybox'
    );

    // Record the pending payment.
    $payment_id = give_insert_payment( $payment_data );
    wp_redirect('paybox/redirect');

    // dunno
  }
}
