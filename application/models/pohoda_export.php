<?php defined('SYSPATH') or die('No direct script access.');

/**
 * POHODA export (XML dataPack) pro vystavené faktury z FreenetIS.
 *
 * Čte z tabulek:
 * - invoices
 * - invoice_items
 *
 * Ukládá do: APPPATH/../data/export/
 */
class Pohoda_export_Model extends Model
{
  /** @var string */
  protected $export_dir;

  public function __construct()
  {
    parent::__construct();
    $this->export_dir = APPPATH . '../data/export';
  }

  /**
   * Vrátí [from,to] pro daný měsíc (YYYY-MM-DD).
   */
  public function month_range($year, $month)
  {
    $year  = (int)$year;
    $month = (int)$month;

    $from = sprintf('%04d-%02d-01', $year, $month);
    $to   = date('Y-m-t', strtotime($from));

    return array($from, $to);
  }

  /**
   * Načte faktury za měsíc podle date_inv.
   *
   * @return array[]
   */
  public function get_invoices_for_month($year, $month)
  {
    list($from, $to) = $this->month_range($year, $month);

    $db = Database::instance();

    return $db->query("
        SELECT *
        FROM invoices
        WHERE date_inv BETWEEN ? AND ?
        ORDER BY invoice_nr
    ", array($from, $to))->as_array();
  }

  /**
   * Načte položky faktury.
   *
   * @return array[]
   */
  public function get_items($invoice_id)
  {
    $db = Database::instance();

    return $db->query("
        SELECT *
        FROM invoice_items
        WHERE invoice_id = ?
        ORDER BY id
    ", array((int)$invoice_id))->as_array();
  }

  public function get_invoices_by_note($note)
  {
    $db = Database::instance();
    return $db->query("
        SELECT *
        FROM invoices
        WHERE note = ?
        ORDER BY invoice_nr
    ", array($note))->as_array();
  }


  /**
   * Vygeneruje POHODA XML (dataPack) pro zadané faktury.
   * Struktura 1:1 podle referenčního FreenetIS exportu + doplněné typ:dic.
   *
   * @param array $invoices pole faktur (může obsahovat stdClass)
   * @return string XML
   */
  public function build_xml(array $invoices)
  {
    // Namespace URI natvrdo
    $ns_dat = 'http://www.stormware.cz/schema/version_2/data.xsd';
    $ns_inv = 'http://www.stormware.cz/schema/version_2/invoice.xsd';
    $ns_typ = 'http://www.stormware.cz/schema/version_2/type.xsd';

    // Obálka (dataPack) – povinné ico
    $ico = trim((string)Settings::get('ico'));
    if ($ico === '') {
      throw new Exception('POHODA export: chybí pohoda_ico v config/Settings');
    }
    $app  = trim((string)Settings::get('pohoda_application'));
    if ($app === '') $app = 'Freenetis';

    $pack_id = 'inv_' . date('Y-m-d_H-i-s');
    $note    = 'Imported from Freenetis';

    // Pomocné formátování čísel jako ve vzoru (bez zbytečných nul, tečka jako desetinná)
    $fmt = function ($n) {
      if ($n === NULL || $n === '') return '';
      if (!is_numeric($n)) return (string)$n;
      $s = number_format((float)$n, 2, '.', '');
      $s = rtrim(rtrim($s, '0'), '.');
      return $s === '' ? '0' : $s;
    };

    $xml = new SimpleXMLElement(
      '<?xml version="1.0" encoding="utf-8"?>' .
        '<dat:dataPack ' .
        'id="' . htmlspecialchars($pack_id, ENT_QUOTES, 'UTF-8') . '" ' .
        'ico="' . htmlspecialchars($ico, ENT_QUOTES, 'UTF-8') . '" ' .
        'application="' . htmlspecialchars($app, ENT_QUOTES, 'UTF-8') . '" ' .
        'version="2.0" ' .
        'note="' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '" ' .
        'xmlns:dat="' . $ns_dat . '" ' .
        'xmlns:inv="' . $ns_inv . '" ' .
        'xmlns:typ="' . $ns_typ . '"' .
        ' />'
    );

    foreach ($invoices as $invRow) {
      if (is_object($invRow)) $invRow = get_object_vars($invRow);

      $invoice_id = (int)($invRow['id'] ?? 0);
      if ($invoice_id <= 0) continue;

      $invoice_nr = $invRow['invoice_nr'] ?? $invoice_id;

      // položky
      $items = $this->get_items($invoice_id);

      // dataPackItem
      $dpi = $xml->addChild('dat:dataPackItem', null, $ns_dat);
      $dpi->addAttribute('id', 'DP' . $fmt($invoice_nr));
      $dpi->addAttribute('version', '2.0');

      // invoice
      $inv = $dpi->addChild('inv:invoice', null, $ns_inv);
      $inv->addAttribute('version', '2.0');

      // ===== invoiceHeader =====
      $h = $inv->addChild('inv:invoiceHeader', null, $ns_inv);

      $h->addChild('inv:invoiceType', 'issuedInvoice', $ns_inv);

      $num = $h->addChild('inv:number', null, $ns_inv);
      $num->addChild('typ:numberRequested', $fmt($invoice_nr), $ns_typ);

      // symVar (POHODA XSD)
      $h->addChild('inv:symVar', $fmt($invRow['var_sym'] ?? ''), $ns_inv);

      // pořadí jako ve vzoru: date, dateTax, dateDue
      if (!empty($invRow['date_inv'])) $h->addChild('inv:date', $invRow['date_inv'], $ns_inv);
      if (!empty($invRow['date_vat'])) $h->addChild('inv:dateTax', $invRow['date_vat'], $ns_inv);
      if (!empty($invRow['date_due'])) $h->addChild('inv:dateDue', $invRow['date_due'], $ns_inv);

      // partnerIdentity/address (pořadí 1:1)
      $pi = $h->addChild('inv:partnerIdentity', null, $ns_inv);
      $addr = $pi->addChild('typ:address', null, $ns_typ);

      $addr->addChild('typ:company', '', $ns_typ);

      $name = (string)($invRow['partner_name'] ?? '');
      if ($name === '') $name = ' '; // ať tam něco je (některé XSD jsou háklivé)
      $addr->addChild('typ:name', htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), $ns_typ);

      $city = (string)($invRow['partner_town'] ?? '');
      $addr->addChild('typ:city', htmlspecialchars($city, ENT_QUOTES, 'UTF-8'), $ns_typ);

      $street = trim((string)($invRow['partner_street'] ?? '') . ' ' . (string)($invRow['partner_street_number'] ?? ''));
      $addr->addChild('typ:street', htmlspecialchars($street, ENT_QUOTES, 'UTF-8'), $ns_typ);

      $zip = (string)($invRow['partner_zip_code'] ?? '');
      $addr->addChild('typ:zip', htmlspecialchars($zip, ENT_QUOTES, 'UTF-8'), $ns_typ);

      $ico_partner = (string)($invRow['organization_identifier'] ?? '');
      $addr->addChild('typ:ico', htmlspecialchars($ico_partner, ENT_QUOTES, 'UTF-8'), $ns_typ);

      // === NOVĚ: DIC hned za ICO ===
      $dic_partner = (string)($invRow['vat_organization_identifier'] ?? '');
      if ($dic_partner !== '') {
        $addr->addChild('typ:dic', htmlspecialchars($dic_partner, ENT_QUOTES, 'UTF-8'), $ns_typ);
      }

      // country jako ve vzoru: <typ:country><typ:ids>Czech Republic</typ:ids></typ:country>
      $country = $addr->addChild('typ:country', null, $ns_typ);
      $country->addChild('typ:ids', 'Czech Republic', $ns_typ);

      // phone/email jako ve vzoru (pokud nemáš, klidně prázdné)
      $addr->addChild('typ:phone', htmlspecialchars((string)($invRow['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'), $ns_typ);
      $addr->addChild('typ:email', htmlspecialchars((string)($invRow['email'] ?? ''), ENT_QUOTES, 'UTF-8'), $ns_typ);

      // další pole ve vzoru
      $h->addChild('inv:numberOrder', '', $ns_inv);
      $h->addChild('inv:symConst', '', $ns_inv);

      // note (ne text!)
      $inv_note = (string)($invRow['note'] ?? '');
      $h->addChild('inv:note', htmlspecialchars($inv_note, ENT_QUOTES, 'UTF-8'), $ns_inv);

      // ===== invoiceDetail =====
      $detail = $inv->addChild('inv:invoiceDetail', null, $ns_inv);

      // součty pro summary
      $sumHighBase = 0.0;
      $sumHighVat  = 0.0;

      foreach ($items as $it) {
        if (is_object($it)) $it = get_object_vars($it);

        $text = (string)($it['name'] ?? '');
        $qty  = (float)($it['quantity'] ?? 1);
        if ($qty <= 0) $qty = 1;

        $unitPrice = (float)($it['price'] ?? 0.0);

        // DPH v DB může být 0.21 -> 21
        $vatRate = (float)($it['vat'] ?? 0.0);
        if ($vatRate > 0.0 && $vatRate <= 1.0) $vatRate *= 100.0;

        // ve vzoru vždy high (u vás 21%)
        $rateVatTag = ($vatRate >= 20.0) ? 'high' : (($vatRate > 0.0) ? 'low' : 'none');

        // ceny jako ve vzoru
        $base = round($unitPrice * $qty, 2);
        $vat  = round($base * ($vatRate / 100.0), 2);
        $sum  = round($base + $vat, 0); // ve vzoru 320 bez desetinných

        if ($rateVatTag === 'high') {
          $sumHighBase += $base;
          $sumHighVat  += $vat;
        }

        $item = $detail->addChild('inv:invoiceItem', null, $ns_inv);
        $item->addChild('inv:text', htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), $ns_inv);
        $item->addChild('inv:quantity', $fmt($qty), $ns_inv);

        $item->addChild('inv:rateVAT', $rateVatTag, $ns_inv);

        $hc = $item->addChild('inv:homeCurrency', null, $ns_inv);
        $hc->addChild('typ:unitPrice', $fmt($unitPrice), $ns_typ);
        $hc->addChild('typ:price', $fmt($base), $ns_typ);
        $hc->addChild('typ:priceVAT', $fmt($vat), $ns_typ);
        $hc->addChild('typ:priceSum', $fmt($sum), $ns_typ);

        $code = (string)($it['code'] ?? '');
        $item->addChild('inv:code', htmlspecialchars($code, ENT_QUOTES, 'UTF-8'), $ns_inv);
      }

      // ===== invoiceSummary =====
      $sum = $inv->addChild('inv:invoiceSummary', null, $ns_inv);
      $sum->addChild('inv:roundingDocument', 'math2one', $ns_inv);

      $hcS = $sum->addChild('inv:homeCurrency', null, $ns_inv);

      // ve vzoru jsou low/none 0
      $hcS->addChild('typ:priceNone', '0', $ns_typ);
      $hcS->addChild('typ:priceLow', '0', $ns_typ);
      $hcS->addChild('typ:priceLowVAT', '0', $ns_typ);
      $hcS->addChild('typ:priceLowSum', '0', $ns_typ);

      $highBase = round($sumHighBase, 2);
      $highVat  = round($sumHighVat, 2);
      $highSum  = round($highBase + $highVat, 0);

      $hcS->addChild('typ:priceHigh', $fmt($highBase), $ns_typ);
      $hcS->addChild('typ:priceHighVAT', $fmt($highVat), $ns_typ);
      $hcS->addChild('typ:priceHighSum', $fmt($highSum), $ns_typ);

      $round = $hcS->addChild('typ:round', null, $ns_typ);
      $round->addChild('typ:priceRound', '0', $ns_typ);
    }

    return $xml->asXML();
  }



  /**
   * Uloží XML do data/export a vrátí cestu k souboru.
   *
   * @return string absolutní cesta
   */
  public function save_xml($year, $month, $xml, $filename = null)
  {
    $year  = (int)$year;
    $month = (int)$month;

    if ($filename === null || trim($filename) === '') {
      $filename = sprintf('pohoda_%04d_%02d.xml', $year, $month);
    }

    if (!is_dir($this->export_dir)) {
      @mkdir($this->export_dir, 0775, true);
    }

    $path = rtrim($this->export_dir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($path, $xml) === false) {
      throw new Exception('Cannot write POHODA export file: ' . $path);
    }

    return $path;
  }

  /**
   * „One-shot“ export: načte faktury za měsíc, vygeneruje XML a uloží.
   *
   * @return array ['count'=>int, 'file'=>string, 'from'=>string, 'to'=>string]
   */
  public function export_month($year, $month)
  {
    list($from, $to) = $this->month_range($year, $month);

    $invoices = $this->get_invoices_for_month($year, $month);
    $xml = $this->build_xml($invoices);
    $file = $this->save_xml($year, $month, $xml);

    return array(
      'count' => count($invoices),
      'file'  => $file,
      'from'  => $from,
      'to'    => $to,
    );
  }

  // ===== Helpers =====

  protected function partner_name(array $invRow)
  {
    $company = trim((string)$invRow['partner_company']);
    $name    = trim((string)$invRow['partner_name']);

    if ($company !== '') return $company;
    if ($name !== '') return $name;
    return 'Neznámý odběratel';
  }

  protected function vat_rate_to_pohoda_rate($vat_rate, $has_vat)
  {
    if (!$has_vat) return 'none';
    if ($vat_rate <= 0.0) return 'none';

    // typicky: 21 => high, 12/15 => low
    if ($vat_rate > 0 && $vat_rate <= 1.0) return 'high';
    return 'low';
  }

  protected function safe_id($v)
  {
    // invoice_nr je v DB double; POHODA id atribut chceme bez mezer a divných znaků
    $s = $this->as_string($v);
    $s = preg_replace('~[^0-9A-Za-z_.-]+~', '_', $s);
    return $s === '' ? '0' : $s;
  }

  protected function as_string($v)
  {
    // u čísel odstranit vědecký zápis apod.
    if (is_float($v) || is_int($v)) {
      // 2 desetinná místa max, ale bez zbytečných nul
      $s = rtrim(rtrim(number_format((float)$v, 6, '.', ''), '0'), '.');
      return $s === '' ? '0' : $s;
    }
    $s = (string)$v;
    $s = trim($s);
    return $s;
  }

  protected function xml_text($s)
  {
    // SimpleXML si escapuje sám, ale tady aspoň ořežeme a vyhodíme řídicí znaky
    $s = (string)$s;
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
    return trim($s);
  }
}
