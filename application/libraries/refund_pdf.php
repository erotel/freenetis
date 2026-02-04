<?php defined('SYSPATH') or die('No direct access allowed.');




require_once(APPPATH . 'vendors/vendor/autoload.php');

class Refund_Pdf
{
  /**
   * Vygeneruje PDF doklad o vratce a uloží ho na disk.
   *
   * @return string absolutní cesta k PDF
   */
  public static function generate(
    int $member_id,
    string $member_name,
    int $member_old_type,        // 2 / 90 (původní typ)
    string $doc_number,          // 26XXX / 26ČLXXXX
    string $leaving_date,
    string $refund_account,
    float $refund_amount,
    string $currency = 'CZK',
    ?int $outgoing_payment_id = null
  ): string {

    $refund_amount = round((float)$refund_amount, 2);
    if ($refund_amount <= 0) {
      throw new Exception('Invalid refund_amount');
    }

    // data člena (VS, adresa, IČO/DIČ...)
    $md = Member_doc_data_Model::get($member_id);

    $view = new View('export/refund_pdf');
    $view->doc = array(
      'doc_number'      => $doc_number,
      'variable_symbol' => (string)($md['variable_symbol'] ?? (string)$member_id),
      'name'            => (string)($md['name'] ?? $member_name),
      'address'         => (string)($md['address'] ?? ''),
      'ico'             => (string)($md['ico'] ?? ''),
      'dic'             => (string)($md['dic'] ?? ''),
      'member_type'     => (int)$member_old_type,   // 2 / 90
      'leaving_date'    => (string)$leaving_date,
      'amount'          => $refund_amount,
      'currency'        => $currency,
      'refund_account'  => $refund_account,
      'op_id'           => $outgoing_payment_id,
    );

    $html = (string)$view;

    // mPDF tempDir – dej radši cache root (mPDF si dělá podadresář mpdf/mpdf)
    $tmp = APPPATH . 'cache';
    if (!is_dir($tmp)) {
      @mkdir($tmp, 0775, true);
    }

    $mpdf = new \Mpdf\Mpdf(array(
      'tempDir' => $tmp,
      'format'  => 'A4',
      'default_font' => 'dejavusans',
      'default_font_size' => 10,
      'margin_left' => 10,
      'margin_right' => 10,
      'margin_top' => 12,
      'margin_bottom' => 12,
    ));

    $mpdf->WriteHTML($html);

    // kam uložit
    $year = date('Y');
    $dir = DOCROOT . 'data/refunds/' . $year;
    if (!is_dir($dir)) {
      mkdir($dir, 0777, true);
    }

    $safeName = url::title('refund-' . $doc_number);
    $filename = $safeName .  '.pdf';
    $path = $dir . '/' . $filename;

    $mpdf->Output($path, 'F');

    if (!is_file($path) || !is_readable($path)) {
      throw new Exception('Refund PDF not created/readable: ' . $path);
    }

    return $path;
  }
}
