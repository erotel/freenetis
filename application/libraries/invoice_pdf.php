<?php defined('SYSPATH') or die('No direct access allowed.');

// pokud používáš stejný autoload jako registrace, nech to takto:
require_once(APPPATH . 'vendors/vendor/autoload.php');
// pokud by registrace používala jinou cestu (třeba vendors/mpdf/mpdf.php),
// změň řádek výše podle ní.

class Invoice_Pdf
{
    /**
     * Vygeneruje PDF fakturu, uloží ji na disk a zapíše cestu do invoices.pdf_filename
     *
     * @param int $invoice_id
     * @return string cesta k PDF souboru
     * @throws Exception
     */
    public static function generate($invoice_id)
    {
        $db = Database::instance();

        // načtení faktury
        $res = $db->query("
            SELECT *
            FROM invoices
            WHERE id = ?
            LIMIT 1
        ", array($invoice_id));

        $inv = $res->current();

        if (! $inv) {
            throw new Exception("Invoice not found: " . $invoice_id);
        }

        // načtení položek
        $items_res = $db->query("
            SELECT *
            FROM invoice_items
            WHERE invoice_id = ?
        ", array($invoice_id));

        $items = $items_res->as_array();

        // spočítat celkovou částku (součet položek)
        $total = 0.0;
        foreach ($items as $it) {
            $qty   = (float)$it->quantity;
            $price = (float)$it->price;
            $total += $qty * $price;
        }

        // údaje organizace z configu (můžeš doladit podle toho, co máš v Settings)
        $org = array(
            'name'   => Settings::get('association_name'),
            'street' => Settings::get('association_street'),
            'city'   => Settings::get('association_city'),
            'zip'    => Settings::get('association_zip'),
            'ico'    => Settings::get('association_ico'),
            'dic'    => Settings::get('association_dic'),
        );

        // proměnné z faktury
        $date_inv = date('d.m.Y', strtotime($inv->date_inv));
        $date_due = date('d.m.Y', strtotime($inv->date_due));
        $date_vat = date('d.m.Y', strtotime($inv->date_vat));
        $vs       = $inv->var_sym;
        $acc      = $inv->account_nr;
        $currency = $inv->currency ? $inv->currency : 'CZK';

        // HTML šablona (jednodušší „Pohoda-like“)
        ob_start();
?>
        <!doctype html>
        <html lang="cs">

        <head>
            <meta charset="utf-8">
            <title>Faktura</title>
            <style>
                * {
                    box-sizing: border-box;
                }

                body {
                    margin: 0;
                    padding: 0;
                }

                table.page-frame {
                    width: 100%;
                    /* 100 % šířky tiskové plochy (A4) */
                    border: 1px solid #000;
                    /* rámeček dokola */
                    border-collapse: collapse;
                }

                table.page-frame td {
                    padding: 10mm;
                    /* vnitřní okraj od rámečku */
                    height: 277mm;
                    /* 297 - 2×10mm padding = „plná“ výška */
                    box-sizing: border-box;
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 10pt;
                    vertical-align: top;
                }

                .top-row {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10px;
                }

                .top-row td {
                    vertical-align: top;
                }

                .supplier-name {
                    font-weight: bold;
                    font-size: 14px;
                    margin-bottom: 5px;
                }

                .invoice-title {
                    text-align: right;
                    font-weight: bold;
                    font-size: 14px;
                    text-transform: uppercase;
                }

                .invoice-number {
                    text-align: right;
                    font-weight: bold;
                    margin-top: 3px;
                }

                .box {
                    border: 1px solid #000;
                    padding: 5px 7px;
                    margin-top: 6px;
                    font-size: 10px;
                }

                .box h4 {
                    margin: 0 0 3px;
                    font-size: 10px;
                    font-weight: bold;
                    text-transform: uppercase;
                }

                .inline-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 10px;
                }

                .inline-table td {
                    padding: 1px 0;
                }

                .account-box {
                    border: 1px solid #000;
                    padding: 5px 7px;
                    text-align: center;
                    margin: 10px 0;
                    font-size: 11px;
                    font-weight: bold;
                }

                .dates-row {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 8px;
                    font-size: 10px;
                }

                .dates-row td {
                    vertical-align: top;
                    padding: 2px 0;
                }

                .dates-row .col {
                    width: 25%;
                }

                .dates-row .label {
                    font-weight: normal;
                    width: auto;
                }

                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 8px;
                    font-size: 10px;
                }

                .items-table th,
                .items-table td {
                    border: 1px solid #000;
                    padding: 3px 4px;
                }

                .items-table th {
                    text-align: center;
                    font-weight: bold;
                    background: #f2f2f2;
                }

                .items-table td.num {
                    text-align: right;
                    white-space: nowrap;
                }

                .items-table tfoot td {
                    font-weight: bold;
                }

                .total-box {
                    margin-top: 10px;
                    text-align: right;
                    font-size: 12px;
                    font-weight: bold;
                }

                .total-box span {
                    border-top: 1px solid #000;
                    padding-top: 3px;
                    display: inline-block;
                    min-width: 120px;
                    text-align: right;
                }

                .middle-note {
                    margin-top: 10px;
                    font-size: 9px;
                }

                .bottom-row {
                    margin-top: 15px;
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 9px;
                }

                .bottom-row td {
                    vertical-align: top;
                    padding-top: 5px;
                }

                .qr-box {
                    width: 40%;
                }

                .qr-placeholder {
                    width: 70mm;
                    height: 70mm;
                    border: 1px solid #000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 8px;
                    margin-top: 5px;
                }

                .vat-box {
                    width: 35%;
                    padding-left: 10px;
                }

                .vat-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 5px;
                }

                .vat-table th,
                .vat-table td {
                    border: 1px solid #000;
                    padding: 2px 3px;
                    text-align: right;
                    white-space: nowrap;
                }

                .vat-table th:first-child,
                .vat-table td:first-child {
                    text-align: left;
                }

                .sign-box {
                    width: 25%;
                    padding-left: 15px;
                    font-size: 10px;
                }

                .sign-box .label {
                    font-weight: bold;
                    margin-bottom: 30px;
                }

                .sign-line {
                    border-top: 1px solid #000;
                    margin-top: 35px;
                    text-align: center;
                    font-size: 9px;
                    padding-top: 2px;
                }

                .footer {
                    margin-top: 10px;
                    font-size: 8px;
                }
            </style>
        </head>

        <body>

            <div class="page-frame">

                <!-- Horní část: dodavatel / název faktury -->
                <table class="top-row">
                    <tr>
                        <td style="width:55%;">
                            <div class="supplier-name">PVfree.net z.s.</div>
                            <div>Daliborka 3<br>796 01 Prostějov</div>
                            <br>
                            <div>IČ: 26656787</div>
                            <div>DIČ: CZ26656787</div>
                            <div>Telefon: 588 207 234</div>
                            <div>E-mail: rada@pvfree.net</div>
                            <div>www.pvfree.net</div>
                        </td>
                        <td style="width:45%; text-align:right;">
                            <div class="invoice-title">FAKTURA - DAŇOVÝ DOKLAD</div>
                            <div class="invoice-number">č. <?= htmlspecialchars($inv->invoice_nr) ?></div>

                            <div style="margin-top:10px;">
                                <table class="inline-table">
                                    <tr>
                                        <td class="label">Variabilní symbol:</td>
                                        <td style="text-align:right;"><?= htmlspecialchars($vs) ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div>
                                <h4>Odběratel:</h4>
                                <?= htmlspecialchars($inv->partner_name) ?><br>
                                <div><?php if ($inv->partner_street): ?>
                                        <?= htmlspecialchars($inv->partner_street) ?>
                                    <?php endif; ?>
                                    <?php if ($inv->partner_street_number): ?>
                                        <?= htmlspecialchars($inv->partner_street_number) ?><br>
                                    <?php endif; ?>
                                    <?php if ($inv->partner_zip_code || $inv->partner_town): ?>
                                        <?= htmlspecialchars(trim($inv->partner_zip_code . ' ' . $inv->partner_town)) ?><br>
                                    <?php endif; ?>
                                    <?php if ($inv->organization_identifier): ?>
                                        IČO: <?= htmlspecialchars($inv->organization_identifier) ?><br>
                                    <?php endif; ?>
                                    <?php if ($inv->vat_organization_identifier): ?>
                                        DIČ: <?= htmlspecialchars($inv->vat_organization_identifier) ?><br>
                                    <?php endif; ?></div>
                                <br>
                            </div>

                        </td>
                    </tr>
                </table>

                <!-- Účet -->
                <div class="account-box">
                    Číslo účtu: <?= htmlspecialchars($acc) ?>
                </div>

                <!-- Datum a forma úhrady -->
                <table class="dates-row">
                    <tr>
                        <td class="col">
                            <span class="label">Datum vystavení:</span><br>
                            <?= htmlspecialchars($date_inv) ?>
                        </td>
                        <td class="col">
                            <span class="label">Datum splatnosti:</span><br>
                            <?= htmlspecialchars($date_due) ?>
                        </td>
                        <td class="col">
                            <span class="label">Datum uskutečnění plnění:</span><br>
                            <?= htmlspecialchars($date_vat) ?>
                        </td>
                        <td class="col">
                            <span class="label">Forma úhrady:</span><br>
                            Příkazem
                        </td>
                    </tr>
                </table>
                <!-- Položky -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Označení dodávky</th>
                            <th style="width:90px;">Cena</th>
                            <th style="width:90px;">% DPH</th>
                            <th style="width:70px;">DPH</th>
                            <th style="width:80px;">Kč Celkem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Platba za Připojení k síti Internet za období 01/2026</td>
                            <td class="num">264,46</td>
                            <td class="num">21%</td>
                            <td class="num">55,54</td>
                            <td class="num">320,00</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="num">Zaokrouhlení</td>
                            <td class="num">0,00</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="num">Zaplaceno na zalohách</td>
                            <td class="num">-320,00</td>
                        </tr>

                        </tbodyt>
                </table>

                <!-- Celkem k úhradě -->
                <div class="total-box">
                    CELKEM K ÚHRADĚ: &nbsp; 0,00 Kč
                </div>
                <div class="total-box">
                    NEPLAŤTE ZAPLACENO ZÁLOHOU !
                </div>


                <!-- Vystavil -->
                <div style="margin-top:8px; font-size:10px;">
                </div>

                <!-- Text pod tabulkou -->
                <div class="middle-note">
                    Spolek PVfree.net, z.s., založen 12.3.2004, zapsán pod značkou L 10341/KSBR Krajským soudem v Brně.<br>

                </div>

                <!-- Spodní část: QR + rekapitulace DPH + razítko -->
                <table class="bottom-row">
                    <tr>
                        <td class="qr-box">
                        </td>

                        <td class="vat-box">
                            <div><strong>Rekapitulace DPH v Kč:</strong></div>
                            <table class="vat-table">
                                <thead>
                                    <tr>
                                        <th>Základ v Kč</th>
                                        <th>Sazba</th>
                                        <th>DPH v Kč</th>
                                        <th>Celkem s DPH v Kč</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="num">264,46</td>
                                        <td class="num">21%</td>
                                        <td class="num">55,54</td>
                                        <td class="num">320,00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>

                    </tr>
                </table>

                <div class="footer">
                </div>

            </div>

        </body>

        </html>


<?php
        $html = ob_get_clean();

        // vytvoření mPDF
        $mpdf = new \Mpdf\Mpdf([
            'format'           => 'A4',
            'margin_left'      => 10,
            'margin_right'     => 10,
            'margin_top'       => 40,
            'margin_bottom'    => 20,
            'default_font'     => 'dejavusans',
            'default_font_size' => 9,
        ]);

        $mpdf->WriteHTML($html);

        // cesta pro uložení
        $year = substr($inv->date_inv, 0, 4);
        if (empty($year)) {
            $year = date('Y');
        }

        $dir = DOCROOT . 'data/invoices/' . $year;
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = $inv->invoice_nr . '.pdf';
        $path     = $dir . '/' . $filename;

        if (!is_writable(dirname($path))) {
            throw new Exception("Cesta není zapisovatelná: " . dirname($path));
        }


        // uložení PDF
        $mpdf->Output($path, 'F');

        /* // uložení cesty do DB
        $db->query("
            UPDATE invoices
            SET pdf_filename = ?
            WHERE id = ?
            LIMIT 1
        ", array($path, $invoice_id));
        */
        return $path;
    }
}
