<?php defined('SYSPATH') or die('No direct script access.');

class Outgoing_payment_Model extends ORM
{
  public function create_draft($bank_account_id, $target_account, $amount, $currency = 'CZK', $message = '', $reason = 'termination_refund', $created_by = '1')
  {
    $db = Database::instance('default');
    $now = date('Y-m-d H:i:s');

    // Pozn.: vycházím z toho, co používáš v Outgoing_payments_Controller:
    // status, bank_account_id, target_account, amount, currency, message, reason, created_at, updated_at
    // (statusy: draft/approved/exported/paid/cancelled) :contentReference[oaicite:0]{index=0}
    $db->query(
      "INSERT INTO outgoing_payments
    (bank_account_id, transfer_id, target_account, amount, currency, message, reason, status, created_by, created_at, updated_at)
   VALUES
    (?, NULL, ?, ?, ?, ?, ?, 'draft', ?, ?, ?)",
      array(
        (int)$bank_account_id,
        (string)$target_account,
        (float)$amount,
        (string)$currency,
        (string)$message,
        (string)$reason,
        (int)$created_by,
        $now,
        $now
      )
    );


    // poslední insert id (MariaDB/MySQL)
    $tmp = $db->query("SELECT LAST_INSERT_ID() AS id")->current();
    $id = (int)(is_array($tmp) ? $tmp['id'] : $tmp->id);

    return $id;
  }
}
