<?php defined('SYSPATH') or die('No direct script access.');

class Outgoing_payments_Controller extends Controller
{
  protected $db;

  public function __construct()
  {
    parent::__construct();

    if (!Settings::get('finance_enabled')) {
      self::error(ACCESS);
    }

    if (!$this->acl_check_view('Accounts_Controller', 'bank_transfers')) {
      self::error(ACCESS);
    }

    // DB init - use explicit group name (most often 'default')
    $this->db = Database::instance('default');
  }


  public function index($status = 'all')
  {
    if (!$this->acl_check_view('Accounts_Controller', 'bank_transfers')) {
      self::error(ACCESS);
    }

    $allowed = array('all', 'draft', 'approved', 'exported', 'paid', 'cancelled');
    if (!in_array($status, $allowed)) $status = 'all';

    $where = '';
    $params = array();

    if ($status !== 'all') {
      $where = "WHERE op.status = ?";
      $params[] = $status;
    }

    $sql = "
		SELECT
			op.*,
			ba.account_nr AS from_account_nr,
			ba.bank_nr AS from_bank_nr
		FROM outgoing_payments op
		LEFT JOIN bank_accounts ba ON ba.id = op.bank_account_id
		$where
		ORDER BY op.id DESC
	";

    $res = $this->db->query($sql, $params);

    // podle driveru: někdy je to ->result() nebo ->as_array()
    $items = method_exists($res, 'result') ? $res->result() : $res;
    // --- export buttons bank accounts from config (e.g. "32,33") ---
    $ids_raw = trim((string) Settings::get('outgoing_payments_export_bank_accounts')); // nastavíš v DB settings
    $export_bank_accounts = array();

    if ($ids_raw !== '') {
      $ids = array();
      foreach (preg_split('/[,\s;]+/', $ids_raw, -1, PREG_SPLIT_NO_EMPTY) as $v) {
        $v = trim($v);
        if ($v !== '' && ctype_digit($v)) $ids[] = (int)$v;
      }
      $ids = array_values(array_unique($ids));

      if (!empty($ids)) {
        // načti jen existující účty
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $res2 = $this->db->query(
          "SELECT id, account_nr, bank_nr, name
         FROM bank_accounts
        WHERE id IN ($ph)
        ORDER BY FIELD(id, $ph)",
          array_merge($ids, $ids) // FIELD(...) chce stejné parametry znovu
        );
        $export_bank_accounts = method_exists($res2, 'result') ? $res2->result() : $res2;
      }
    }
    $view = new View('main');
    $view->title = __('Outgoing payments');
    $view->content = new View('outgoing_payments/index');
    $view->content->export_bank_accounts = $export_bank_accounts;
    $view->content->export_bank_accounts_raw = $ids_raw;
    $view->content->items = $items;
    $view->content->status = $status;
    $view->render(TRUE);
  }



  public function approve($id)
  {
    if (!$this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);
    $id = (int)$id;

    $res = $this->db->query("SELECT * FROM outgoing_payments WHERE id = ?", array($id));
    $rows = method_exists($res, 'result') ? $res->result() : array();
    $op = isset($rows[0]) ? $rows[0] : NULL;

    if (!$op) self::error(RECORD);

    if ($op->status !== 'draft') {
      status::warning(__('Only draft payments can be approved.'));
      url::redirect('outgoing_payments');
    }

    $this->db->query(
      "UPDATE outgoing_payments
		 SET status='approved', approved_by=?, updated_at=?
		 WHERE id=?",
      array((int)$this->user_id, date('Y-m-d H:i:s'), $id)
    );

    status::success(__('Outgoing payment approved.'));
    url::redirect('outgoing_payments');
  }



  public function cancel($id)
  {
    if (!$this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);
    $id = (int)$id;

    $res = $this->db->query("SELECT * FROM outgoing_payments WHERE id = ?", array($id));
    $rows = method_exists($res, 'result') ? $res->result() : array();
    $op = isset($rows[0]) ? $rows[0] : NULL;

    if (!$op) self::error(RECORD);

    if (!in_array($op->status, array('draft', 'approved'))) {
      status::warning(__('Only draft/approved payments can be cancelled.'));
      url::redirect('outgoing_payments');
    }

    $this->db->query(
      "UPDATE outgoing_payments
		 SET status='cancelled', updated_at=?
		 WHERE id=?",
      array(date('Y-m-d H:i:s'), $id)
    );

    status::success(__('Outgoing payment cancelled.'));
    url::redirect('outgoing_payments');
  }

  public function export($bank_account_id)
  {
    if (!$this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')) {
      self::error(ACCESS);
    }
    if (!is_numeric($bank_account_id)) self::error(RECORD);
    $bank_account_id = (int)$bank_account_id;

    // approved platby pro daný bankovní účet
    $res = $this->db->query(
      "SELECT op.*
		 FROM outgoing_payments op
		 WHERE op.bank_account_id=? AND op.status='approved'
		 ORDER BY op.id ASC",
      array($bank_account_id)
    );
    $rows = $res->result();
    if (!$rows || !count($rows)) {
      status::warning(__('No approved outgoing payments for export.'));
      url::redirect('outgoing_payments');
    }

    // vezmi účet odesílatele (bank_accounts)
    $ba = $this->db->query(
      "SELECT * FROM bank_accounts WHERE id=?",
      array($bank_account_id)
    )->result();
    $ba = $ba && isset($ba[0]) ? $ba[0] : null;
    if (!$ba) self::error(RECORD);

    $token_key = 'fio_api_token_bank_account_' . $bank_account_id;
    $token = trim((string)Settings::get($token_key));

    $xml = $this->build_fio_import_xml($rows, $ba);

    // bez tokenu -> stáhnout soubor
    if ($token === '') {
      $fn = 'fio_import_' . $bank_account_id . '_' . date('Ymd_His') . '.xml';
      $this->mark_exported($rows, $fn, null, null);

      header('Content-Type: application/xml; charset=UTF-8');
      header('Content-Disposition: attachment; filename="' . $fn . '"');
      echo $xml;
      exit;
    }

    // token existuje -> poslat do Fio (vytvoří dávku k podpisu)
    $resp = $this->send_fio_import($token, $xml, 'cs'); // lng=cs
    // $resp = ['idInstruction'=>..., 'errorCode'=>..., 'raw'=>...]
    if ((string)$resp['errorCode'] !== '0') {
      // necháme approved, ať to jde opravit a zkusit znovu
      throw new Exception("Fio import failed (errorCode={$resp['errorCode']})");
    }

    $this->mark_exported($rows, null, (string)$resp['idInstruction'], (string)$resp['raw']);

    status::success(__('Batch uploaded to Fio. Authorize it in Internetbanking (Payments to authorize).'));
    url::redirect('outgoing_payments');
  }


  protected function build_abo($rows, $bank_account_id)
  {
    // TODO: nahradit přesným ABO formátem dle Fio
    // dočasně "CSV-like" pro test, ať vidíš data
    $lines = array();
    foreach ($rows as $op) {
      $lines[] = implode(';', array(
        $op->target_account,
        number_format((float)$op->amount, 2, '.', ''),
        $op->variable_symbol,
        $op->message
      ));
    }
    return implode("\r\n", $lines) . "\r\n";
  }

  protected function send_fio_import($token, $xml, $lng = 'cs')
  {
    $url = 'https://fioapi.fio.cz/v1/rest/import/'; // oficiální import endpoint :contentReference[oaicite:6]{index=6}

    $tmp = tempnam(sys_get_temp_dir(), 'fio_');
    file_put_contents($tmp, $xml);

    $post = array(
      'type'  => 'xml',
      'token' => $token,
      'lng'   => $lng,
      'file'  => new CURLFile($tmp, 'application/xml', 'davka.xml'),
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    @unlink($tmp);

    if ($raw === false || $code >= 400) {
      throw new Exception("Fio import HTTP=$code $err");
    }

    // odpověď je XML (responseImportIB.xsd) – zajímá nás errorCode + idInstruction :contentReference[oaicite:7]{index=7}
    $xmlr = @simplexml_load_string($raw);
    if (!$xmlr) {
      return array('errorCode' => '', 'idInstruction' => null, 'raw' => $raw);
    }

    // robustní čtení bez ohledu na namespace/prefixy
    $ec = $xmlr->xpath('//*[local-name()="errorCode"]');
    $ii = $xmlr->xpath('//*[local-name()="idInstruction"]');

    $errorCode = ($ec && isset($ec[0])) ? trim((string)$ec[0]) : '';
    $idInstr   = ($ii && isset($ii[0])) ? trim((string)$ii[0]) : '';

    return array('errorCode' => $errorCode, 'idInstruction' => $idInstr, 'raw' => $raw);
  }


  protected function mark_exported($rows, $filename = null, $export_ref = null, $api_response = null)
  {
    $now = date('Y-m-d H:i:s');

    foreach ($rows as $op) {

      // 1) outgoing_payments -> exported
      $this->db->query(
        "UPDATE outgoing_payments
             SET status='exported',
                 exported_at=?,
                 export_filename=?,
                 export_ref=?,
                 api_response=?,
                 updated_at=?
             WHERE id=? AND status='approved'",
        array($now, $filename, $export_ref, $api_response, $now, (int)$op->id)
      );

      // 2) Pokud je to refund neidentifikované platby (má transfer_id),
      // upravíme text v bank_transfers u původního příchozího převodu,
      // aby v 'Neidentifikované převody' bylo vidět "čeká na potvrzení z banky".
      if (!empty($op->transfer_id) && (int)$op->transfer_id > 0) {

        // pokud chceš omezit jen na refundy:
        // if (empty($op->reason) || $op->reason !== 'unidentified_refund') continue;

        $this->db->query(
          "UPDATE bank_transfers
                 SET comment = CONCAT(
                        IFNULL(comment,''),
                        CASE WHEN comment IS NULL OR comment = '' THEN '' ELSE ' | ' END,
                        ?
                     )
                 WHERE transfer_id = ?",
          array('čeká na potvrzení z banky', (int)$op->transfer_id)
        );

        // volitelné: i do transfers.text (aby to bylo vidět i mimo bank_transfers)
        $this->db->query(
          "UPDATE transfers
                 SET text = ?
                 WHERE id = ?",
          array('Přiřazení neidentifikované platby – čeká na potvrzení z banky', (int)$op->transfer_id)
        );
      }
    }
  }


  protected function build_fio_import_xml($rows, $ba)
  {
    // accountFrom: jen číslo účtu (bez /kód banky)
    $accountFrom = preg_replace('~/.*$~', '', (string)$ba->account_nr);

    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    $import = $doc->createElement('Import');
    $import->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $import->setAttribute('xsi:noNamespaceSchemaLocation', 'http://www.fio.cz/schema/importIB.xsd');
    $doc->appendChild($import);

    $orders = $doc->createElement('Orders');
    $import->appendChild($orders);

    $today = date('Y-m-d');

    foreach ($rows as $op) {
      // target_account můžeš mít jako "2212-2000000699/0300" nebo "2600625206/2010"
      list($accountTo, $bankCode) = $this->split_cz_account((string)$op->target_account);

      $tx = $doc->createElement('DomesticTransaction');

      $tx->appendChild($doc->createElement('accountFrom', $accountFrom));
      $tx->appendChild($doc->createElement('currency', $op->currency ?: 'CZK'));
      $tx->appendChild($doc->createElement('amount', number_format((float)$op->amount, 2, '.', '')));
      $tx->appendChild($doc->createElement('accountTo', $accountTo));
      $tx->appendChild($doc->createElement('bankCode', $bankCode));

      if (!empty($op->constant_symbol)) $tx->appendChild($doc->createElement('ks', (string)$op->constant_symbol));
      if (!empty($op->variable_symbol)) $tx->appendChild($doc->createElement('vs', (string)$op->variable_symbol));
      if (!empty($op->specific_symbol)) $tx->appendChild($doc->createElement('ss', (string)$op->specific_symbol));

      $tx->appendChild($doc->createElement('date', date('Y-m-d')));



      $comment = 'Vrácení platby – FreenetIS';
      $comment .= ' (OP #' . (int)$op->id . ')';

      $tx->appendChild(
        $doc->createElement(
          'comment',
          $this->limit($comment, 255)
        )
      );


      // standardní platba: 431001 (dle manuálu) :contentReference[oaicite:4]{index=4}
      $tx->appendChild($doc->createElement('paymentType', !empty($op->payment_type) ? (string)$op->payment_type : '431001'));

      $orders->appendChild($tx);
    }

    return $doc->saveXML();
  }

  protected function split_cz_account($s)
  {
    $s = trim($s);

    // očekáváme něco jako "2212-2000000699/0300" nebo "2600625206/2010" nebo jen "2600625206"
    $bank = '2010'; // fallback
    $acc = $s;

    if (strpos($s, '/') !== false) {
      list($acc, $bank) = explode('/', $s, 2);
      $acc = trim($acc);
      $bank = trim($bank);
    }

    // čistění
    $bank = preg_replace('~\D~', '', $bank);
    if ($bank === '') $bank = '2010';

    // accountTo může mít prefix "2212-..."
    // necháme jen čísla a případně jednu pomlčku
    $acc = preg_replace('~[^0-9\-]~', '', $acc);

    return array($acc, $bank);
  }

  protected function limit($s, $max)
  {
    $s = trim(preg_replace("~\r\n|\r~", "\n", (string)$s));
    if ($s === '') return '';
    // jednoduchý limit (UTF-8 bezpečné)
    if (function_exists('mb_substr')) return mb_substr($s, 0, $max, 'UTF-8');
    return substr($s, 0, $max);
  }

  protected function pvfree_try_mark_outgoing_paid(array $item, Bank_transfer_Model $bt): bool
  {
    $msg = isset($item['zprava']) ? (string)$item['zprava'] : '';
    if ($msg === '') return false;

    if (!preg_match('~\bOP\s*#\s*([0-9]+)\b~i', $msg, $m)) {
      return false;
    }

    $op_id = (int)$m[1];
    if ($op_id <= 0) return false;

    $ba = $this->get_bank_account();
    if (!$ba || !$ba->id) return false;

    $amount = abs((float)$item['castka']);
    $now = date('Y-m-d H:i:s');

    $db = Database::instance('default');

    $res = $db->query(
      "SELECT id, bank_account_id, status, amount
         FROM outgoing_payments
         WHERE id=?",
      array($op_id)
    )->result();

    if (empty($res)) return false;
    $op = $res[0];

    if ((int)$op->bank_account_id !== (int)$ba->id) return false;
    if (abs(((float)$op->amount) - $amount) > 0.01) return false;
    if (!in_array($op->status, array('exported', 'approved'), true)) return false;

    // označení jako paid
    $db->query(
      "UPDATE outgoing_payments
         SET status='paid', paid_at=?, updated_at=?
         WHERE id=?",
      array($now, $now, $op_id)
    );

    Log_queue_Model::info("BANK IMPORT: OP #$op_id marked as PAID");

    return true;
  }

  public function approve_all()
  {
    if (!$this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')) {
      self::error(ACCESS);
    }

    $now = date('Y-m-d H:i:s');

    // schválí všechny draft outgoing payments
    $res = $this->db->query(
      "UPDATE outgoing_payments
         SET status = 'approved',
             approved_by = ?,
             updated_at = ?
         WHERE status = 'draft'",
      array((int)$this->user_id, $now)
    );

    status::success(__('All draft outgoing payments approved.'));
    url::redirect('outgoing_payments');
  }

  protected function get_export_bank_account_ids(): array
  {
    $raw = (string) Settings::get('outgoing_payments_export_bank_accounts'); // např. "31,2 "
    $raw = trim($raw);

    if ($raw === '') return array();

    $ids = array();
    $bad = array();

    foreach (preg_split('/[,\s;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $tok) {
      $tok = trim($tok);
      if ($tok === '') continue;

      if (ctype_digit($tok)) {
        $ids[] = (int)$tok;
      } else {
        $bad[] = $tok;
      }
    }

    $ids = array_values(array_unique($ids));

    // volitelně: zalogovat/ukázat, že config obsahoval bordel
    if (!empty($bad)) {
      // do logu:
      // Log_queue_Model::warning("Outgoing payments: invalid bank account IDs in settings: " . implode(', ', $bad));

      // nebo klidně jen status warning (pokud to chceš vidět v UI):
      // status::warning(__('Invalid bank account IDs in settings were ignored: %s', implode(', ', $bad)));
    }

    return $ids;
  }


  protected function get_export_bank_accounts(): array
  {
    $ids = $this->get_export_bank_account_ids();
    if (empty($ids)) return array();

    $ph = implode(',', array_fill(0, count($ids), '?'));

    $res = $this->db->query(
      "SELECT id, account_nr, bank_nr, name
       FROM bank_accounts
      WHERE id IN ($ph)
      ORDER BY id",
      $ids
    );

    // Kohana: většinou ->result() = array objektů
    if (is_object($res) && method_exists($res, 'result')) {
      $rows = $res->result();
      return is_array($rows) ? $rows : array();
    }

    // fallback: kdyby driver vracel rovnou pole
    return is_array($res) ? $res : array();
  }


  public function export_all()
  {
    if (!$this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')) {
      self::error(ACCESS);
    }

    $accounts = $this->get_export_bank_accounts();
    if (empty($accounts)) {
      status::warning(__('No bank accounts configured for bulk export.'));
      url::redirect('outgoing_payments');
    }

    $sent = 0;
    $skipped = 0;
    $no_token = 0;
    $errors = array();

    foreach ($accounts as $acc) {
      $bank_account_id = (int)$acc->id;

      try {
        // approved platby pro daný bankovní účet
        $res = $this->db->query(
          "SELECT op.*
           FROM outgoing_payments op
          WHERE op.bank_account_id=? AND op.status='approved'
          ORDER BY op.id ASC",
          array($bank_account_id)
        );
        $rows = method_exists($res, 'result') ? $res->result() : array();

        if (!$rows || !count($rows)) {
          $skipped++;
          continue;
        }

        // účet odesílatele
        $ba = $this->db->query(
          "SELECT * FROM bank_accounts WHERE id=?",
          array($bank_account_id)
        )->result();
        $ba = $ba && isset($ba[0]) ? $ba[0] : null;
        if (!$ba) {
          $errors[] = "bank_account_id=$bank_account_id: missing bank_accounts record";
          continue;
        }

        $token_key = 'fio_api_token_bank_account_' . $bank_account_id;
        $token = trim((string) Settings::get($token_key));

        $xml = $this->build_fio_import_xml($rows, $ba);

        // Bez tokenu: v bulk režimu nebudeme stahovat soubor do prohlížeče.
        // Buď to jen přeskočíme, nebo uložíme server-side (doporučeno).
        if ($token === '') {
          $no_token++;

          // varianta: uložit XML na disk, aby šlo ručně nahrát
          $fn = 'fio_import_' . $bank_account_id . '_' . date('Ymd_His') . '.xml';
          $path = sys_get_temp_dir() . '/' . $fn;
          @file_put_contents($path, $xml);

          // označit exported i bez uploadu? já bych spíš NE (ať to nezmizí z approved)
          // pokud chceš označit exported i při uložení, odkomentuj:
          // $this->mark_exported($rows, $fn, null, null);

          continue;
        }

        // Token existuje -> poslat do Fio
        $resp = $this->send_fio_import($token, $xml, 'cs');
        if ((string)$resp['errorCode'] !== '0') {
          $errors[] = "bank_account_id=$bank_account_id: Fio errorCode={$resp['errorCode']}";
          continue;
        }

        $this->mark_exported($rows, null, (string)$resp['idInstruction'], (string)$resp['raw']);
        $sent++;
      } catch (Exception $e) {
        $errors[] = "bank_account_id=$bank_account_id: " . $e->getMessage();
      }
    }

    // Souhrn do status hlášek
    if ($sent > 0) {
      status::success(__('Uploaded batches to Fio for %s bank account(s).', $sent));
    } else {
      status::warning(__('No batches were uploaded to Fio.'));
    }

    if ($skipped > 0) {
      status::info(__('Skipped %s bank account(s) with no approved payments.', $skipped));
    }

    if ($no_token > 0) {
      status::warning(__('Skipped %s bank account(s) without Fio token configured.', $no_token));
    }

    if (!empty($errors)) {
      // zkráceně, ať to nerozbije UI
      $msg = implode(' | ', array_slice($errors, 0, 5));
      if (count($errors) > 5) $msg .= ' ...';
      status::error(__('Some exports failed: %s', $msg));
    }

    url::redirect('outgoing_payments');
  }
}
