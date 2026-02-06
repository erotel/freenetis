<?php defined('SYSPATH') or die('No direct script access.');

/**
 * POHODA export refundů jako DOBROPIS (vydaná opravná faktura).
 *
 * - schema version_2, dataPack version 2.0
 * - invoiceType = issuedCorrectiveTax
 * - DPH vždy 21 %
 * - částka v DB kladná, do POHODY záporně
 * - partner: osoba = typ:name, firma = typ:company + typ:ico + typ:dic
 * - adresa se skládá z address_points / towns / streets / countries (přes members.address_point_id)
 */
class Pohoda_Refund_Export_Model
{
  protected string $export_dir;

  public function __construct()
  {
    $this->export_dir = APPPATH . '../data/export';
  }

  /**
   * Načti položky z fronty.
   * Vrací stdClass[] (Kohana DB).
   */
  public function get_queue_items(string $status = 'new', int $limit = 500): array
  {
    $db = Database::instance();

    return $db->query("
      SELECT
        q.*,
        m.name AS member_name,
        m.organization_identifier AS member_ico,
        m.vat_organization_identifier AS member_dic,

        ap.street_number AS addr_street_number,
        s.street         AS addr_street,
        t.town           AS addr_town,
        t.quarter        AS addr_quarter,
        t.zip_code       AS addr_zip,
        c.country_name   AS addr_country

      FROM pohoda_refund_queue q
      JOIN members m              ON m.id = q.member_id
      LEFT JOIN address_points ap ON ap.id = m.address_point_id
      LEFT JOIN towns t           ON t.id = ap.town_id
      LEFT JOIN streets s         ON s.id = ap.street_id
      LEFT JOIN countries c       ON c.id = ap.country_id

      WHERE q.status = ?
      ORDER BY q.id ASC
      LIMIT ?
    ", array($status, $limit))->as_array();
  }

  /**
   * Vygeneruje POHODA XML: inv:invoice (dobropis) v2.0
   */
  public function build_xml(array $rows, ?string $ico = null): string
  {
    $schemaVer = 'version_2';
    $packVer   = '2.0';

    $ns_dat = "http://www.stormware.cz/schema/{$schemaVer}/data.xsd";
    $ns_inv = "http://www.stormware.cz/schema/{$schemaVer}/invoice.xsd";
    $ns_typ = "http://www.stormware.cz/schema/{$schemaVer}/type.xsd";

    // ICO povinné
    $ico = trim((string)$ico);
    if ($ico === '') {
      $ico = trim((string)Settings::get('ico'));
    }
    if ($ico === '') {
      throw new Exception('POHODA export: missing required ICO (dataPack@ico).');
    }

    $xml = new SimpleXMLElement(
      '<?xml version="1.0" encoding="utf-8"?>'
        . '<dat:dataPack'
        . ' xmlns:dat="' . $ns_dat . '"'
        . ' xmlns:inv="' . $ns_inv . '"'
        . ' xmlns:typ="' . $ns_typ . '"'
        . '/>'
    );

    $xml->addAttribute('version', $packVer);
    $xml->addAttribute('id', 'FreenetISRefunds');
    $xml->addAttribute('application', 'FreenetIS');
    $xml->addAttribute('note', 'Refund export (Dobropisy)');
    $xml->addAttribute('ico', $ico);

    $today = date('Y-m-d');

    foreach ($rows as $r) {
      $itemId = 'RFQ_' . (int)$r->id;

      $dpi = $xml->addChild('dat:dataPackItem', null, $ns_dat);
      $dpi->addAttribute('version', $packVer);
      $dpi->addAttribute('id', $itemId);

      $inv = $dpi->addChild('inv:invoice', null, $ns_inv);
      $inv->addAttribute('version', $packVer);

      // ---- výpočty: DB kladně, dobropis záporně
      $amount_with_vat = (float)($r->amount ?? 0);
      if ($amount_with_vat <= 0) {
        continue;
      }

      $vat_rate = 21.0;
      $base_pos = $amount_with_vat / (1.0 + $vat_rate / 100.0);
      $vat_pos  = $amount_with_vat - $base_pos;

      $base_pos = round($base_pos, 2);
      $vat_pos  = round($vat_pos, 2);
      $sum_pos  = round($amount_with_vat, 2);

      $base = -$base_pos;
      $vat  = -$vat_pos;
      $sum  = -$sum_pos;

      // ---- header
      $hdr = $inv->addChild('inv:invoiceHeader', null, $ns_inv);
      $hdr->addChild('inv:invoiceType', 'issuedCorrectiveTax', $ns_inv);

      // číslo dobropisu (z fronty) – fallback RFQ-id
      $doc_number = trim((string)($r->doc_number ?? ''));
      if ($doc_number === '') {
        $doc_number = 'RFQ-' . (int)$r->id;
      }

      $num = $hdr->addChild('inv:number', null, $ns_inv);
      $num->addChild('typ:numberRequested', $this->xml_text($doc_number), $ns_typ);

      // datumy
      $created = $this->date_ymd((string)($r->created_at ?? '')) ?: $today;
      $hdr->addChild('inv:date', $created, $ns_inv);
      $hdr->addChild('inv:dateTax', $created, $ns_inv);
      $hdr->addChild('inv:dateAccounting', $created, $ns_inv);

      // VS
      $symVar = $this->digits_only($doc_number);
      if ($symVar === '') $symVar = (string)(int)$r->id;
      $hdr->addChild('inv:symVar', $this->xml_text($symVar), $ns_inv);

      // ---- partner + adresa
      $member = trim((string)($r->member_name ?? ''));
      if ($member === '') $member = 'Neznámý odběratel';

      $ico_member = $this->normalize_ico((string)($r->member_ico ?? ''));
      $dic_member = $this->normalize_dic((string)($r->member_dic ?? ''));

      $pid  = $hdr->addChild('inv:partnerIdentity', null, $ns_inv);
      $addr = $pid->addChild('typ:address', null, $ns_typ);

      // firma vs osoba
      if ($ico_member !== '' || $dic_member !== '') {
        $addr->addChild('typ:company', $this->xml_text($member), $ns_typ);
        if ($ico_member !== '') $addr->addChild('typ:ico', $this->xml_text($ico_member), $ns_typ);
        if ($dic_member !== '') $addr->addChild('typ:dic', $this->xml_text($dic_member), $ns_typ);
      } else {
        $addr->addChild('typ:name', $this->xml_text($member), $ns_typ);
      }

      // adresa (když něco chybí, POHODA to většinou přežije)
      $city   = $this->build_partner_city($r);
      $street = $this->build_partner_street($r);
      $zip    = $this->build_partner_zip($r);
      $country = $this->build_partner_country($r);

      if ($city !== '')   $addr->addChild('typ:city', $this->xml_text($city), $ns_typ);
      if ($street !== '') $addr->addChild('typ:street', $this->xml_text($street), $ns_typ);
      if ($zip !== '')    $addr->addChild('typ:zip', $this->xml_text($zip), $ns_typ);

      if ($country !== '') {
        $cnode = $addr->addChild('typ:country', null, $ns_typ);
        $cnode->addChild('typ:ids', $this->xml_text($country), $ns_typ);
      }

      // text / poznámka
      $text = 'Dobropis (vratka)';
      if (isset($r->invoice_number) && trim((string)$r->invoice_number) !== '') {
        $text .= ' / pův. faktura: ' . trim((string)$r->invoice_number);
      }
      $hdr->addChild('inv:text', $this->xml_text($text), $ns_inv);

      // ---- detail (1 řádek)
      $det = $inv->addChild('inv:invoiceDetail', null, $ns_inv);
      $it  = $det->addChild('inv:invoiceItem', null, $ns_inv);

      $it->addChild('inv:text', $this->xml_text('Vratka přeplatku'), $ns_inv);
      $it->addChild('inv:quantity', '1', $ns_inv);
      $it->addChild('inv:rateVAT', 'high', $ns_inv);

      $hc = $it->addChild('inv:homeCurrency', null, $ns_inv);
      $hc->addChild('typ:unitPrice', $this->fmt_money($base), $ns_typ);

      // ---- summary
      $sumNode = $inv->addChild('inv:invoiceSummary', null, $ns_inv);
      $sumNode->addChild('inv:roundingDocument', 'none', $ns_inv);
      $sumNode->addChild('inv:roundingVAT', 'none', $ns_inv);

      $hcs = $sumNode->addChild('inv:homeCurrency', null, $ns_inv);
      $hcs->addChild('typ:priceHigh',    $this->fmt_money($base), $ns_typ);
      $hcs->addChild('typ:priceHighVAT', $this->fmt_money($vat),  $ns_typ);
      $hcs->addChild('typ:priceHighSum', $this->fmt_money($sum),  $ns_typ);
    }

    return $this->pretty_xml((string)$xml->asXML());
  }

  public function save_xml(string $xml, string $filename): string
  {
    if (!is_dir($this->export_dir)) {
      @mkdir($this->export_dir, 0775, true);
    }
    $path = rtrim($this->export_dir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($path, $xml) === false) {
      throw new Exception('Cannot write POHODA export file: ' . $path);
    }
    return $path;
  }

  public function mark_exported(array $ids): void
  {
    if (!count($ids)) return;

    $db = Database::instance();
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $db->query("
      UPDATE pohoda_refund_queue
      SET status = 'exported',
          exported_at = NOW()
      WHERE id IN ($placeholders)
    ", $ids);
  }

  public function export_new_to_file(int $limit = 500, bool $mark_exported = true, ?string $ico = null): array
  {
    $rows = $this->get_queue_items('new', $limit);

    $xml = $this->build_xml($rows, $ico);

    $filename = sprintf('pohoda_refund_invoices_%s.xml', date('Ymd_His'));
    $file = $this->save_xml($xml, $filename);

    $ids = array();
    foreach ($rows as $r) $ids[] = (int)$r->id;

    if ($mark_exported && count($ids)) {
      $this->mark_exported($ids);
    }

    return array(
      'count' => count($rows),
      'file'  => $file,
      'ids'   => $ids,
    );
  }

  // ===== helpers =====

  protected function xml_text(string $s): string
  {
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
    return trim($s);
  }

  protected function fmt_money($v): string
  {
    return number_format((float)$v, 2, '.', '');
  }

  protected function date_ymd(string $dt): ?string
  {
    $dt = trim($dt);
    if ($dt === '') return null;
    if (preg_match('~^\d{4}-\d{2}-\d{2}~', $dt)) return substr($dt, 0, 10);
    return null;
  }

  protected function digits_only(string $s): string
  {
    return preg_replace('~\D+~', '', $s);
  }

  protected function normalize_ico(string $s): string
  {
    $s = trim($s);
    if ($s === '') return '';
    return preg_replace('~\D+~', '', $s);
  }

  protected function normalize_dic(string $s): string
  {
    $s = strtoupper(trim($s));
    if ($s === '') return '';
    $s = str_replace(' ', '', $s);
    return $s;
  }

  protected function build_partner_city(object $r): string
  {
    $town = trim((string)($r->addr_town ?? ''));
    $q    = trim((string)($r->addr_quarter ?? ''));

    if ($town === '' && $q === '') return '';
    if ($q !== '' && $town !== '' && mb_stripos($town, $q, 0, 'UTF-8') === false) {
      return $town . ' - ' . $q;
    }
    return $town !== '' ? $town : $q;
  }

  protected function build_partner_street(object $r): string
  {
    $street = trim((string)($r->addr_street ?? ''));
    $no     = trim((string)($r->addr_street_number ?? ''));

    if ($street === '') return $no;
    if ($no === '') return $street;
    return trim($street . ' ' . $no);
  }

  protected function build_partner_zip(object $r): string
  {
    return trim((string)($r->addr_zip ?? ''));
  }

  protected function build_partner_country(object $r): string
  {
    $c = trim((string)($r->addr_country ?? ''));
    // pokud máš v DB "Czech Republic" nebo "Česká republika", necháme jak je
    // případně fallback:
    return $c !== '' ? $c : 'Czech Republic';
  }

  protected function pretty_xml(string $xml): string
  {
    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml);
    return $dom->saveXML();
  }
}
