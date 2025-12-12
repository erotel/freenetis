<?php defined('SYSPATH') or die('No direct access allowed.');

require_once(APPPATH . 'libraries/invoice_pdf.php');

class Auto_credit_invoices_Model extends Model
{
    // účet s kreditem
    const CREDIT_ATTR_ID = 221100;
    // limit kreditu pro fakturaci
    const MIN_BALANCE    = 320.0;
    const NOVAT_BALANCE  = 264.46;
    // čas, kdy to v produkci poběží (1. den v měsíci v 00:10)
    const RUN_TIME       = '00:10';

    /**
     * @param int|null $timestamp  čas (typicky $this->t ze Scheduler_Controller)
     * @param bool     $force      TRUE = ignoruje datum/čas + Settings (pro test)
     * @param bool     $dryRun     TRUE = nic nezapisuje do DB, jen loguje
     */
    public static function run($timestamp = NULL, $force = FALSE, $dryRun = FALSE)
    {
        if ($timestamp === NULL) {
            $timestamp = time();
        }

        // --- podmínky běhu v produkci ---
        if (! $force) {
            // jen 1. den v měsíci v 00:10
            if (date('d', $timestamp) !== '01' || date('H:i', $timestamp) !== self::RUN_TIME) {
                return;
            }

            // přepínač v config tabulce – můžeš si vytvořit záznam v `config`
            if (! Settings::get('auto_credit_invoices_enabled')) {
                return;
            }
        }

        $db = Database::instance();

        // fakturujeme minulé období = předchozí měsíc
        #    $ts_prev = strtotime('-1 month', $timestamp);
        $ts_prev = $timestamp;
        $month = (int)date('n', $ts_prev); // 1-12
        $year  = (int)date('Y', $ts_prev);
        $period = sprintf('%04d-%02d', $year, $month);

        // hlavní bankovní účet (type = 0)
        $bank_row = $db->query("
            SELECT account_nr, bank_nr
            FROM bank_accounts
            WHERE type = 0
            LIMIT 1
        ")->current();

        $account_nr_str = NULL;
        if ($bank_row) {
            $acc = trim((string)$bank_row->account_nr);
            $bnk = trim((string)$bank_row->bank_nr);
            if ($acc !== '' && $bnk !== '') {
                $account_nr_str = $acc . '/' . $bnk;
            } elseif ($acc !== '') {
                $account_nr_str = $acc;
            }
        }

        // --- vyber členy s kreditem > MIN_BALANCE a type = 2 (aktivní) ---
        $members_result = $db->query("
    SELECT
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

WHERE a.account_attribute_id = " . self::CREDIT_ATTR_ID . "
  AND a.balance >= " . self::MIN_BALANCE . "
  AND m.type = 2;

");

        if (count($members_result) === 0) {
            Log_queue_Model::info('auto_credit_invoices: žádní členové nesplňují podmínky');
            return;
        }

        // --- další číslo faktury ---
        // číslování faktur ve tvaru RRRRxxxxx (např. 2026xxxxx)
        $year_int = (int)$year;                // $year už máme z výpočtu období
        $min_nr   = $year_int * 100000;
        $max_nr   = $year_int * 100000 + 99999;

        $max_row = $db->query("
    SELECT IFNULL(MAX(invoice_nr), 0) AS max_nr
    FROM invoices
    WHERE invoice_nr >= $min_nr
      AND invoice_nr <= $max_nr
")->current();

        if ($max_row && (float)$max_row->max_nr >= $min_nr) {
            $next_nr = (int)$max_row->max_nr + 1;
        } else {
            // první faktura v daném roce
            $next_nr = $min_nr + 1; // např. 202600001
        }


        $due_days = 14;
        $date_inv = date('Y-m-d', $timestamp);
        $date_due = date('Y-m-d', strtotime('+' . $due_days . ' days', $timestamp));
        $date_vat = $date_inv;

        // pro začátek natvrdo 320 Kč – později můžeš nahradit logikou z fees/members_fees
        $invoice_amount = self::MIN_BALANCE;
        $novatprice = self::NOVAT_BALANCE;
        $currency       = 'CZK';

        $created_count = 0;

        foreach ($members_result as $row) {
            $member_id   = (int)$row->member_id;
            $member_name = (string)$row->member_name;
            $balance     = (float)$row->balance;
            $street      = (string)$row->street_name;
            $street_number = (string)$row->street_number;
            $town          = (string)$row->town_name;
            $zip           = (string)$row->zip_code;
            $ico           = (string)$row->ico;
            $dic           = (string)$row->dic;


            // výchozí VS = member_id (fallback)
            $var_sym = $member_id;

            // pokud existuje variable_symbol na kreditním účtu, použijeme ten
            if ($row->variable_symbol !== NULL && $row->variable_symbol !== '') {
                // invoices.var_sym je double, ale MariaDB si poradí i s číselným stringem
                $var_sym = (float)$row->variable_symbol;
            }

            // jedinečný "podpis" faktury – podle toho poznáme, jestli už za období existuje
            $note = sprintf('AUTO PVFREE CREDIT %s', $period);

            // kontrola, jestli už taková faktura pro člena není
            $exists_row = $db->query("
                SELECT id
                FROM invoices
                WHERE member_id = ?
                  AND note = ?
                LIMIT 1
            ", array($member_id, $note))->current();

            if ($exists_row) {
                // pro tohoto člena už je faktura za období vytvořená
                continue;
            }

            if ($dryRun) {
                // jen zalogujeme, co by se stalo
                Log_queue_Model::info(
                    sprintf(
                        'DRY-RUN auto_credit_invoices: vytvořil bych fakturu %s pro člena %d (%s), balance=%.2f, částka=%.2f',
                        $period,
                        $member_id,
                        $member_name,
                        $balance,
                        $invoice_amount
                    )
                );
                $created_count++;
                $next_nr++;
                continue;
            }

            // --- OSTRÝ INSERT faktury ---
            $res = $db->query("
                INSERT INTO invoices
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
                (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?
                )
            ", array(
                $member_id,        // member_id
                NULL,              // partner_company
                $member_name,      // partner_name
                $street,              // partner_street
                $street_number,              // partner_street_number
                $town,              // partner_town
                $zip,              // partner_zip_code
                NULL,              // partner_country
                $ico,              // organization_identifier
                $dic,              // vat_organization_identifier
                NULL,              // phone_number
                NULL,              // email
                $next_nr,          // invoice_nr
                0,                 // invoice_type (0 = běžná vystavená, dle vaší konvence)
                $account_nr_str,   // account_nr
                $var_sym,          // var_sym
                NULL,              // con_sym
                $date_inv,         // date_inv
                $date_due,         // date_due
                $date_vat,         // date_vat
                1,                 // vat (0 = neplátce)
                NULL,              // order_nr
                $currency,         // currency
                $note              // note
            ));

            // ID právě vložené faktury
            $invoice_id = $res->insert_id();

            // --- položka faktury ---
            $item_name = sprintf('Platba za internet %s/%s', $month, $year);
            $item_code = sprintf('FEE-%s', $period);

            $db->query("
                INSERT INTO invoice_items
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
                (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ", array(
                $invoice_id,       // invoice_id
                $item_name,        // name
                $item_code,        // code
                1,                 // quantity
                0,             // author_fee
                0,                 // contractual_increase
                1,                 // service (1 = služba)
                $novatprice,       // price
                0.21               // vat
            ));

            // vytvoříme PDF faktury
            try {
                Invoice_Pdf::generate($invoice_id);
                // načti PDF cestu (předpokládám, že Invoice_Pdf::generate ji ukládá do invoices.pdf_path)
            } catch (Exception $e) {
                // neblokujme kvůli PDF generování celý proces faktur
                Log_queue_Model::error('auto_credit_invoices PDF error', $e);
            }

            // === EMAIL S PDF FAKTUROU (na všechny emaily člena) ===
            try {
                // cesta k PDF (většinou se ukládá do invoices.pdf_path)
                $pdf_row = $db->query("
        SELECT pdf_filename, invoice_nr
        FROM invoices
        WHERE id = ?
        LIMIT 1
    ", array($invoice_id))->current();

                $pdf_path = ($pdf_row && !empty($pdf_row->pdf_filename)) ? (string)$pdf_row->pdf_filename : '';

                // některé instalace ukládají relativní cestu -> zkus převést na absolutní
                if ($pdf_path !== '' && $pdf_path[0] !== '/') {
                    $pdf_path = realpath(APPPATH . '../' . ltrim($pdf_path, '/'));
                } else {
                    $pdf_path = realpath($pdf_path);
                }

                if ($pdf_path && is_file($pdf_path) && is_readable($pdf_path)) {
                    $emails = self::get_member_emails($member_id);

                    if (count($emails)) {
                        $eq = new Email_queue_Model();

                        $invnr = ($pdf_row && !empty($pdf_row->invoice_nr)) ? (string)$pdf_row->invoice_nr : (string)$next_nr;

                        $subject = sprintf('Faktura %s', $invnr);
                        $body = sprintf(
                            "Dobrý den,<br>\n<br>\nV příloze zasíláme fakturu za období %s.<br>\n<br>\nPVFREE<br>\n",
                            $period
                        );

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
                    }
                } else {
                    Log_queue_Model::error('auto_credit_invoices: pdf_path not found/readable for invoice_id=' . $invoice_id);
                }
            } catch (Exception $e) {
                Log_queue_Model::error('auto_credit_invoices: email enqueue PDF failed', $e);
            }



            $created_count++;
            $next_nr++;
        }

        if ($dryRun) {
            $msg = sprintf(
                'DRY-RUN auto_credit_invoices: vytvořil bych %d faktur za období %s',
                $created_count,
                $period
            );
        } else {
            $msg = sprintf(
                'auto_credit_invoices: vytvořeno %d faktur za období %s',
                $created_count,
                $period
            );
        }

        if (!$dryRun) {
            $self = new self();
            $self->export_pohoda_monthly((int)$year, (int)$month);
        }


        Log_queue_Model::info($msg);
    }

    /**
     * Export POHODA XML pro zadaný měsíc a zařazení do email fronty s přílohou.
     *
     * Spouštět 1. den v měsíci pro stejný měsíc (faktury jsou na daný měsíc).
     *
     * @param int $year
     * @param int $month 1-12
     */
    private function export_pohoda_monthly($year, $month)
    {
        $year  = (int)$year;
        $month = (int)$month;


        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new Exception('export_pohoda_monthly: invalid year/month');
        }

        // === cílový soubor ===
        $dir = APPPATH . '../data/export';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $filename = sprintf('pohoda_%04d_%02d.xml', $year, $month);
        $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        // Pokud už existuje a je neprázdný, neexportovat znovu (anti-duplicitní pojistka)
        if (is_file($file) && filesize($file) > 100) {
            return;
        }

        // === vygeneruj XML ===
        $m = new Pohoda_export_Model();
        $note = sprintf('AUTO PVFREE CREDIT %04d-%02d', $year, $month);
        $invoices = $m->get_invoices_by_note($note);

        if (!count($invoices)) {
            // nic k exportu
            return;
        }

        $xml = $m->build_xml($invoices);
        $m->save_xml($year, $month, $xml, $filename); // uloží přesně na $file

        // === zařaď email do fronty s přílohou ===
        // TODO: nastav si adresáta podle reality (konfig / env / config file)
        $to = 'slezi2@pvfree.net';

        $subject = sprintf('POHODA export faktur %02d/%04d', $month, $year);
        $body    = sprintf(
            "Dobrý den,<br>\n<br>\nV příloze posílám POHODA XML export vystavených faktur za %02d/%04d.<br>\nPočet faktur: %d<br>\n<br>\nFreenetIS scheduler<br>\n",
            $month,
            $year,
            count($invoices)
        );

        $eq = new Email_queue_Model();
        $eq->push(
            'noreply@pvfree.net',
            $to,
            $subject,
            $body,
            array(
                array(
                    'path' => $file,
                    'name' => $filename,
                    'mime' => 'application/xml',
                )
            )
        );
    }

    /**
     * Vrátí všechny emaily člena (unikátní), přes members -> users -> users_contacts -> contacts.
     *
     * contacts.type = 20 je email. :contentReference[oaicite:1]{index=1}
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
