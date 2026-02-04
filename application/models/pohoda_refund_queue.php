<?php defined('SYSPATH') or die('No direct script access.');

class Pohoda_refund_queue_Model extends ORM
{

  protected $table_name = 'pohoda_refund_queue';

  public function enqueue($member_id, $member_type, $refund_account, $amount, $currency = 'CZK', $outgoing_payment_id = null, $note = null,  string $doc_number)
  {
    $db = Database::instance('default');
    $now = date('Y-m-d H:i:s');

    $db->query(
      "INSERT INTO pohoda_refund_queue
        (member_id, member_type, doc_number, outgoing_payment_id, refund_account, amount, currency, reason, note, created_at, status)
       VALUES
        (?, ?,?, ?, ?, ?, ?, 'termination_refund', ?, ?, 'new')",
      array(
        (int)$member_id,
        (int)$member_type,
        (string)$doc_number,
        ($outgoing_payment_id !== null ? (int)$outgoing_payment_id : null),
        (string)$refund_account,
        (float)$amount,
        (string)$currency,
        ($note !== null ? (string)$note : null),
        $now
      )
    );
  }

  public function generate_next_doc_number(int $member_type): string
  {
    $year = date('y'); // 26
    $is_member = ($member_type === 90);

    $prefix = $year . ($is_member ? 'ČL' : '');
    $pad = $is_member ? 4 : 3;

    $db = Database::instance('default');

    // zamkne řádky pro daný prefix
    $row = $db->query(
      "SELECT doc_number
             FROM pohoda_refund_queue
             WHERE doc_number LIKE ?
             ORDER BY id DESC
             LIMIT 1
             FOR UPDATE",
      array($prefix . '%')
    )->current();

    if ($row) {
      $last = is_array($row)
        ? $row['doc_number']
        : $row->doc_number;

      // vytáhni číselnou část
      $num = (int)preg_replace('/\D+/', '', substr($last, strlen($prefix)));
    } else {
      $num = 0;
    }

    $num++;

    return $prefix . str_pad((string)$num, $pad, '0', STR_PAD_LEFT);
  }
}
