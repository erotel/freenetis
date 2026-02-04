<?php defined('SYSPATH') or die('No direct script access.');

require_once(APPPATH . 'libraries/refund_pdf.php');

class Termination_refund_mail_Model
{
  public static function send_refund_pdf(
    int $member_id,
    int $member_old_type,
    string $doc_number,
    string $leaving_date,
    string $refund_account,
    float $refund_amount,
    ?int $outgoing_payment_id = null
  ): void {

    $db = Database::instance();

    // 1) načíst jméno (minimální)
    $m = $db->query(
      "SELECT id, name FROM members WHERE id = ? LIMIT 1",
      array($member_id)
    )->current();

    if (!$m) {
      throw new Exception('Member not found');
    }

    $member_name = is_array($m) ? (string)$m['name'] : (string)$m->name;

    // 2) emaily
    $emails = self::get_member_emails($member_id);
    if (!count($emails)) {
      return; // není kam poslat
    }

    // 3) PDF (předáváme VŠECHNO, co Refund_Pdf potřebuje)
    $pdf_path = Refund_Pdf::generate(
      (int)$member_id,
      (string)$member_name,
      (int)$member_old_type,
      (string)$doc_number,
      (string)$leaving_date,
      (string)$refund_account,
      (float)$refund_amount,
      'CZK',
      $outgoing_payment_id
    );

    // 4) Email queue + attachment
    $subject = sprintf('Vratka přeplatku – doklad %s', $doc_number);

    $body = sprintf(
      "Dobrý den,<br>\n<br>\n"
        . "v příloze zasíláme potvrzení o vratce přeplatku při ukončení.<br>\n"
        . "Doklad: <strong>%s</strong><br>\n"
        . "Částka: %s Kč<br>\n"
        . "Účet: %s<br>\n<br>\n"
        . "PVfree.net, z.s.<br>\n",
      htmlspecialchars($doc_number),
      number_format((float)$refund_amount, 2, ',', ' '),
      htmlspecialchars($refund_account)
    );

    $eq = new Email_queue_Model();
    foreach ($emails as $to) {
      $eq->push(
        'noreply@pvfree.net',
        $to,
        $subject,
        $body,
        array(
          array(
            'path' => $pdf_path,
            'name' => basename($pdf_path),
            'mime' => 'application/pdf',
          )
        )
      );
    }
  }


  /**
   * Zkopíruj 1:1 z Bank_import_services_invoices_Model::get_member_emails() :contentReference[oaicite:5]{index=5}
   */
  private static function get_member_emails($member_id)
  {
    $db = Database::instance();
    $rows = $db->query("
      SELECT DISTINCT c.value AS email
      FROM users u
      JOIN users_contacts uc ON uc.user_id = u.id
      JOIN contacts c ON c.id = uc.contact_id
      WHERE u.member_id = ?
        AND c.type = 20
        AND c.value IS NOT NULL
        AND c.value <> ''
    ", array((int)$member_id))->as_array();

    $emails = array();
    foreach ($rows as $r) {
      if (is_object($r)) $r = get_object_vars($r);
      $e = trim((string)($r['email'] ?? ''));
      if ($e !== '') $emails[$e] = true;
    }
    return array_keys($emails);
  }
}
