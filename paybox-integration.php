<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

class Give_Paybox extends WP_Paybox_RedirectController implements PrePersistPayboxable {

  private $payment_id;

  function setReturnURLS(&$PBX_EFFECTUE, &$PBX_REFUSE, &$PBX_ANNULE) {
    $PBX_EFFECTUE = urlencode(home_url('paybox/success'));
    $PBX_REFUSE   = urlencode(home_url('paybox/error'));
    $PBX_ANNULE   = urlencode(home_url('paybox/cancel'));
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
    if ( $url_path === 'paybox/redirect') {
      // WP_Paybox_RedirectController
    }
    if ( $url_path === 'paybox/confirm') {
    }
    if ( $url_path === 'paybox/error') {
    }
    if ( $url_path === 'paybox/cancel') {
    }
  }
 
  // binding with give_filter_success_page_content
  function onClientSuccess() {
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

  function handleIPN($logguer, $vars) {
    // TODO: missing $_GET['pbx_amount'] or $_GET['trans'] ?
    // référence de commande presente => traitement de la commande
    $payment = get_posts($vars['ref']);

    if(! $payment) {
      $logger("IPN", "Can't load payment request id {$vars['ref']}: exit.", LOG_ERR);
      give_record_gateway_error(esc_html( 'IPN Error'), sprintf("Can't load payment request id %s: exit.", $vars['ref']));
      exit;
    }

    $total = floatval(number_format($vars['pbx_amount'], 2, '.', ''))/100;

    if($vars['pbx_error'] === '00000') {
      $next_state_name = 'PS_OS_PAYMENT';
    }
    else {
      $next_state_name = 'PS_OS_ERROR';
      // http://www1.paybox.com/espace-integrateur-documentation/dictionnaire-des-donnees/codes-reponses/
      $logger("IPN", sprintf("Paybox announce error %s for transaction ref %s. cf https://tinyurl.com/o8kym3g.", $vars['pbx_error'], $vars["ref"]), LOG_ERR);
      give_record_gateway_error(esc_html( 'IPN Error' ), sprintf("Paybox announce error %s for transaction ref %s. cf https://tinyurl.com/o8kym3g.", $vars['pbx_error'], $vars["ref"]));
    }

    /* Ensure IPN replay does not cause problem (hitting twice the URL should be safe)
       Wild guess: 3x times payment imply 3 distinct transaction ID
       How would a unique pbx_trans come again ?
       - Paybox cUrl IPN bug/double-run
       - apache/access_log sniff/rerun/... */
    // TODO
    // 1) get all past payments for this "order"
    // 2) check pbx_trans NOT in past payments
    // 3) otherwise "IPN", "... this transaction ID {$vars['pbx_trans']} was already registered: exit.", LOG_WARNING

    $message_string = '';
    if ($vars['pbx_abonnement']) $message_string .= "Abonnement: {$vars['pbx_abonnement']}\n";
    if (isset($vars['pbx_bank_country']) && $vars['pbx_bank_country']) $message_string .= "Pays de la banque: {$vars['pbx_bank_country']}\n";
    $message_string .= <<<EOF
Pays du client: {$vars['pbx_client_country']}
N° d'autorisation: {$vars['pbx_auth']}
Transaction: {$vars['pbx_trans']}
Total paiement: {$total}€

EOF;

    // TODO: good, somehow "validate" payment has gone OK
  }
}