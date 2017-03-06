<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// needed for give_get_option()
require_once(__DIR__ . '/../give/includes/admin/class-give-settings.php');

class Give_Paybox_Channel extends WP_Paybox {
  public static function opt($opt) {
    return give_get_option($opt);
  }

  public static function isTestMode() {
    return (defined('PAYBOX_TEST_MODE') && PAYBOX_TEST_MODE) || give_is_test_mode();
  }

  function setReturnURLS(&$PBX_EFFECTUE, &$PBX_REFUSE, &$PBX_ANNULE) {
    $PBX_EFFECTUE = urlencode(home_url('paybox/success'));
    $PBX_REFUSE   = urlencode(home_url('paybox/error'));
    $PBX_ANNULE   = urlencode(home_url('paybox/cancel'));
  }
}

class Give_Paybox extends WP_Paybox_RedirectController implements PrePersistPayboxable {

  private $payment_id;

  public function __construct($payment_id) {
    $this->payment_id = $payment_id;
  }

  function getUniqId()   { return $this->payment_id; }
  function getEmail()    { return give_get_payment_user_email($this->payment_id); }
  function getAmount()   { return give_get_payment_amount($this->payment_id); }
  function getCurrency() { return WP_Paybox::currency_codes[give_get_payment_currency_code($this->payment_id)]; }
	function isPayment3X() { return give_get_payment_meta($this->payment_id)['paybox3x']; /* value of radio from templates/add-payment3x-option.tpl.php */ }

  // see http://wordpress.stackexchange.com/a/182718
  // Use anything better in future WP release (Router plugin?)
  function endpoints() {
    $url_path = trim(parse_url(add_query_arg(array()), PHP_URL_PATH), '/');
    if ( $url_path === 'paybox/confirm') {
    }
    // if ( $url_path === 'paybox/redirect') {}
    if ( $url_path === 'paybox/confirm') {
    }
    if ( $url_path === 'paybox/error') {
    }
    if ( $url_path === 'paybox/cancel') {
    }
  }
 
  // binding with give_filter_success_page_content
  function onClientSuccess($args = NULL) {
    ob_start();
    give_get_template_part( 'payment', 'processing.tpl' );
    $content = ob_get_clean();
    return $content;
  }

	function onClientError() {
    ob_start();
    give_get_template_part( 'payment', 'error.tpl' );
    $content = ob_get_clean();
    return $content;
  }

	function onClientConfirmation() {
    printf('<p>%s</p>', __('Your payment request through CB/Paybox is complete.', 'give-paybox'));
  }

  function set() {
    // no-op
    // see process_payment()
  }

  function handleIPN($obj, $vars, $logger = NULL) {
    if(is_callable($logger)) {
      $looger = function($args) use ($logger) {
        call_user_func_array($logger, $args);
      };
    }
    else { $logger = function() { return ; }; }


    if(! $obj) {
      $logger("Can't load payment request id {$vars['ref']}: exit.", LOG_ERR, "IPN-give");
      give_record_gateway_error(esc_html( 'IPN Error'), sprintf("Can't load payment request id %s: exit.", $vars['ref']));
      exit;
    }

    if($obj->post_type != 'give_payment') {
      $logger("Don't know how to handle the IPN for WP post of type \"{$obj->post_type}\". Dunno (hook_action return)", LOG_WARNING, "IPN-give");
      return;
    }

    $payment = new Give_Payment($obj->ID);
    if(! $payment) {
      $logger("Object is not an actual Give payment. Weird. exit", LOG_ERR, "IPN-give");
      return;
    }

    $total = floatval(number_format($vars['pbx_amount'], 2, '.', ''))/100;

    // see give_get_payment_statuses()
    if($vars['pbx_error'] === '00000') {
      if (! in_array($payment->status, ['pending', 'publish', 'failed', 'preapproval'])) {
        $logger(sprintf("Transaction %s, current status = %s. Does not expect a (another?) successful payment!", $payment->ID, $payment->status), LOG_ERR, "IPN-give");
        $payment->add_note("Attempt to add a payment of {$total}€.");
        return;
      }

      if ($total != $payment->total) { // TODO: use $payment->add_donation() ??
        $str = sprintf("Received a valid payment of a distinct amount (expected: % 3.2f, received: % 3.2f)", $payment->total, $total);
        $payment->add_note($str);
        if ($total > $payment->total) {
          $logger($str, LOG_WARNING, "IPN-give");
          $payment->update_status('publish');
        } else {
          $logger($str, LOG_ERR, "IPN-give");
        }
        return;
      }

      if ($payment->status == 'publish') {
        $logger(sprintf("Couldn't update the statut from %s to to %s. exit", $payment->status, 'publish'), LOG_WARNING, "IPN-give");
        return;
      }

      // normal case:
      $payment->update_status('publish'); /* couldn't return FALSE happen because update_status() is bugged */
      $payment->update_meta( '_give_payment_transaction_id', $vars['pbx_trans']);
      $payment->add_note(sprintf("payment added (amount: $total, paybox transaction: %s, client country: %s, bank country: %s",
                                                     $vars['pbx_trans'], $vars['pbx_client_country'], $vars['pbx_bank_country']));
      $logger(sprintf("Added a successful payment of $total for %s (final status: publish). exit", $payment->ID), LOG_INFO, "IPN-give");
      return;
    }

    else {
      if (! in_array($payment->status, ['pending', 'publish', 'preapproval'])) {
        $logger(sprintf("Transaction %s, status = %s. Does not expect a (another?) payment error!", $payment->ID, $payment->status), LOG_ERR, "IPN-give");
        $payment->add_note(sprintf("Paybox sent a failure (amount: $total, paybox transaction: %s, error: %s", $vars['pbx_trans'], $vars['pbx_error']));
        return;
      }

      $payment->update_status('failed');
      $payment->add_note("Paybox Payment error: {$vars['pbx_error']}.");

      // http://www1.paybox.com/espace-integrateur-documentation/dictionnaire-des-donnees/codes-reponses/
      $logger(sprintf("Paybox announce error %s for transaction ref %s. cf https://tinyurl.com/o8kym3g.", $vars['pbx_error'], $vars["ref"]), LOG_ERR, "IPN-give");
      give_record_gateway_error('Paybox IPN Error', sprintf("Paybox error %s for transaction ref %s. cf https://tinyurl.com/o8kym3g.", $vars['pbx_error'], $vars["ref"]));
    }

    /* Ensure IPN replay does not cause problem (hitting twice the URL should be safe)
       Wild guess: 3x times payment imply 3 distinct transaction ID
       How would a unique pbx_trans come again ?
       - Paybox cUrl IPN bug/double-run
       - apache/access_log sniff/rerun/... */
    // TODO
    // 1) get all past payments for this donation
    // 2) check pbx_trans NOT in past payments
    // 3) otherwise "IPN", "... this transaction ID {$vars['pbx_trans']} was already registered: exit.", LOG_WARNING

    $message_string = '';
    if ($vars['pbx_abonnement']) $message_string .= "Abonnement: {$vars['pbx_abonnement']}\n";
    if (!empty($vars['pbx_bank_country'])) $message_string .= "Pays de la banque: {$vars['pbx_bank_country']}\n";
    $message_string .= <<<EOF
Pays du client: {$vars['pbx_client_country']}
N° d'autorisation: {$vars['pbx_auth']}
Transaction/Paybox ID: {$vars['pbx_trans']}
Total paiement: {$total}€

EOF;

    // TODO: good, somehow "validate" payment has gone OK
  }
}
