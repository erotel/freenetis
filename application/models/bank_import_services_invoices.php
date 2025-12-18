<?php defined('SYSPATH') or die('No direct script access.');

require_once(APPPATH . 'libraries/invoice_pdf.php');
/**
 * Vystavení faktury z bank importu (účet "services"):
 * - vloží fakturu do invoices + invoice_items
 * - vygeneruje PDF přes Invoice_Pdf
 * - uloží invoices.pdf_filename
 * - vloží email do fronty + připojí PDF
 *
 * POŽADAVEK:
 * - SERVICES faktury VŽDY 21 % DPH
 * - DUZP = datum importu (dnes)
 */
class Bank_import_services_invoices_Model
{
  public static function create_invoice_and_enqueue_pdf(
    int $member_id,
    float $total_amount,
    array $bank_transfer_ids,
    string $account_nr_str,
    string $period_tag
  ): int {
    $member_id = (int)$member_id;
    $total_amount = round((float)$total_amount, 2);

    if ($member_id <= 0 || $total_amount <= 0) {
      throw new Exception('Invalid member_id/amount for services invoice.');
    }

    $db = Database::instance();

    // --- anti-dup: 1x za měsíc a člena ---
    $note = sprintf('AUTO PVFREE SERVICES %s', $period_tag);

    $existing = $db->query(
      "SELECT id FROM invoices WHERE member_id = ? AND note = ? LIMIT 1",
      array($member_id, $note)
    )->current();

    if ($existing && isset($existing->id)) {
      return (int)$existing->id;
    }

    // --- data člena (stejně jako auto_credit_invoices, jen minimum) ---
    $row = $db->query(
      "SELECT
    a.id                       AS account_id,
    a.member_id                AS member_id,
    a.balance                  AS balance,
    m.name                     AS member_name,
    vs.variable_symbol         AS variable_symbol,
    s.street                   AS street_name,
    ap.street_number           AS street_number,
    t.town                     AS town_name,
    t.zip_code                 AS zip_code,
    m.organization_identifier      AS ico,
    m.vat_organization_identifier  AS dic
FROM accounts a
JOIN members m
    ON m.id = a.member_id
LEFT JOIN variable_symbols vs
    ON vs.account_id = a.id
LEFT JOIN address_points ap
    ON ap.id = m.address_point_id
LEFT JOIN streets s
    ON s.id = ap.street_id
LEFT JOIN towns t
    ON t.id = ap.town_id
WHERE a.member_id = ? LIMIT 1",
      array($member_id)
    )->current();

    if (!$row) {
      throw new Exception('Member not found for services invoice.');
    }

    $member_name = (string)$row->member_name;
    $street      = (string)$row->street_name;
    $street_no   = (string)$row->street_number;
    $town        = (string)$row->town_name;
    $zip         = (string)$row->zip_code;
    $ico         = (string)$row->ico;
    $dic         = (string)$row->dic;

    // VS = member_id
    $var_sym = (float)$member_id;

    // pokud existuje variable_symbol na kreditním účtu, použijeme ten
    if ($row->variable_symbol !== NULL && $row->variable_symbol !== '') {
      // invoices.var_sym je double, ale MariaDB si poradí i s číselným stringem
      $var_sym = (float)$row->variable_symbol;
    }

    // === SERVICES FAKTURY: VŽDY 21 % DPH ===
    $vat_rate = 0.21;   // 21 %
    $vat_flag = 1;      // invoices.vat = 1

    // invoice_nr: číslování ve tvaru RRRRxxxxx (jako auto_credit_invoices)
    $year = (int)date('Y');
    $min_nr = $year * 100000;
    $max_nr = $year * 100000 + 99999;

    $max_row = $db->query("
            SELECT IFNULL(MAX(invoice_nr), 0) AS max_nr
            FROM invoices
            WHERE invoice_nr >= $min_nr
              AND invoice_nr <= $max_nr
        ")->current();

    if ($max_row && (float)$max_row->max_nr >= $min_nr) {
      $next_nr = (int)$max_row->max_nr + 1;
    } else {
      $next_nr = $min_nr + 1;
    }

    $currency = 'CZK';

    // A) datum importu = dnes
    $date_inv = date('Y-m-d');
    $date_due = date('Y-m-d', strtotime('+14 days'));
    $date_vat = $date_inv; // DUZP = dnes

    // --- INSERT invoices ---
    $res = $db->query(
      "INSERT INTO invoices
             (
               member_id,
               partner_company,
               partner_name,
               partner_street,
               partner_street_number,
               partner_town,
               partner_zip_code,
               partner_country,
               organization_identifier,
               vat_organization_identifier,
               phone_number,
               email,
               invoice_nr,
               invoice_type,
               account_nr,
               var_sym,
               con_sym,
               date_inv,
               date_due,
               date_vat,
               vat,
               order_nr,
               currency,
               note
             )
             VALUES
             ( ?, ?, ?, ?, ?, ?, ?, ?,  ?, ?, ?, ?,  ?, ?, ?, ?, ?,  ?, ?, ?, ?, ?, ?, ? )",
      array(
        $member_id,
        NULL,
        $member_name,
        $street ?: NULL,
        $street_no ?: NULL,
        $town ?: NULL,
        $zip ?: NULL,
        NULL,
        $ico ?: NULL,
        $dic ?: NULL,
        NULL,
        NULL,
        $next_nr,
        0,
        $account_nr_str,
        $var_sym,
        NULL,
        $date_inv,
        $date_due,
        $date_vat,
        $vat_flag,
        NULL,
        $currency,
        $note
      )
    );

    $invoice_id = (int)$res->insert_id();

    // --- invoice_items ---
    $item_name = sprintf('Platba za připojení k síti Internet', $period_tag);
    $item_code = sprintf('BANK-%s', $period_tag);

    // cena bez DPH (vždy 21 %)
    $price_no_vat = round($total_amount / (1 + $vat_rate), 2);

    $db->query(
      "INSERT INTO invoice_items
             (
               invoice_id,
               name,
               code,
               quantity,
               author_fee,
               contractual_increase,
               service,
               price,
               vat
             )
             VALUES
             ( ?, ?, ?, ?, ?, ?, ?, ?, ? )",
      array(
        $invoice_id,
        $item_name,
        $item_code,
        1,
        0,
        0,
        1,
        $price_no_vat,
        0.21
      )
    );

    // --- PDF ---
    try {
      Invoice_Pdf::generate($invoice_id);
    } catch (Exception $e) {
      Log_queue_Model::error('services invoice PDF error', $e);
      return $invoice_id;
    }

    // načti PDF cestu z invoices.pdf_filename
    $pdf_row = $db->query("
            SELECT pdf_filename, invoice_nr
            FROM invoices
            WHERE id = ?
            LIMIT 1
        ", array($invoice_id))->current();

    $pdf_path = ($pdf_row && !empty($pdf_row->pdf_filename)) ? (string)$pdf_row->pdf_filename : '';

    if ($pdf_path !== '' && $pdf_path[0] !== '/') {
      $pdf_path = realpath(APPPATH . '../' . ltrim($pdf_path, '/'));
    } else {
      $pdf_path = realpath($pdf_path);
    }

    if (!$pdf_path || !is_file($pdf_path) || !is_readable($pdf_path)) {
      Log_queue_Model::error('services invoice: pdf_path not found/readable for invoice_id=' . $invoice_id);
      return $invoice_id;
    }

    // --- Email queue + attachment ---
    $emails = self::get_member_emails($member_id);
    if (!count($emails)) {
      return $invoice_id;
    }

    $invnr = ($pdf_row && !empty($pdf_row->invoice_nr)) ? (string)$pdf_row->invoice_nr : (string)$next_nr;

    $subject = sprintf('Faktura %s', $invnr);
    $body = sprintf(
      "Dobrý den,<br>\n<br>\nV příloze zasíláme fakturu za období %s.<br>\n"
        . "Platby ID: %s<br>\n"
        . "Částka: %s Kč<br>\n<br>\nPVFREE<br>\n",
      htmlspecialchars($period_tag),
      htmlspecialchars(implode(', ', $bank_transfer_ids)),
      number_format($total_amount, 2, ',', ' ')
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
            'name' => sprintf('faktura_%s.pdf', $invnr),
            'mime' => 'application/pdf',
          )
        )
      );
    }

    return $invoice_id;
  }

  /**
   * ZKOPÍROVÁNO 1:1 z Auto_credit_invoices_Model (je to private, tak to musíme mít tady).
   * contacts.type = 20 je email.
   *
   * @return string[]
   */
  private static function get_member_emails($member_id)
  {
    $db = Database::instance();

    $rows = $db->query("
            SELECT DISTINCT c.value AS email
            FROM users u
            JOIN users_contacts uc
              ON uc.user_id = u.id
            JOIN contacts c
              ON c.id = uc.contact_id
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
