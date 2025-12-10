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
        $date_inv = $inv->date_inv;
        $date_due = $inv->date_due;
        $date_vat = $inv->date_vat;
        $vs       = $inv->var_sym;
        $acc      = $inv->account_nr;
        $currency = $inv->currency ? $inv->currency : 'CZK';

        // HTML šablona (jednodušší „Pohoda-like“)
        ob_start();
?>
        <html>

        <head>
            <meta charset="utf-8">
            <style>
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 9pt;
                }

                .header-table td {
                    vertical-align: top;
                }

                .box {
                    border: 1px solid #000;
                    padding: 5px 7px;
                    margin-bottom: 5px;
                }

                .title {
                    font-size: 16pt;
                    font-weight: bold;
                }

                .items-table {
                    border-collapse: collapse;
                    width: 100%;
                    margin-top: 15px;
                }

                .items-table th,
                .items-table td {
                    border: 1px solid #000;
                    padding: 4px 6px;
                }

                .items-table th {
                    background: #eeeeee;
                }

                .right {
                    text-align: right;
                }

                .small {
                    font-size: 8pt;
                }
            </style>
        </head>

        <body>

            <table width="100%" class="header-table">
                <tr>
                    <td width="55%">
                        <div class="box">
                            <div class="title">Faktura - daňový doklad</div>
                            <br>
                            <b>Dodavatel:</b><br>
                            <?= htmlspecialchars($org['name']) ?><br>
                            <?= htmlspecialchars($org['street']) ?><br>
                            <?= htmlspecialchars($org['zip'] . ' ' . $org['city']) ?><br>
                            IČO: <?= htmlspecialchars($org['ico']) ?><br>
                            DIČ: <?= htmlspecialchars($org['dic']) ?><br>
                        </div>
                    </td>
                    <td width="45%">
                        <div class="box">
                            <b>Odběratel:</b><br>
                            <?= htmlspecialchars($inv->partner_name) ?><br>
                            <?php if ($inv->partner_street): ?>
                                <?= htmlspecialchars($inv->partner_street) ?><br>
                            <?php endif; ?>
                            <?php if ($inv->partner_zip_code || $inv->partner_town): ?>
                                <?= htmlspecialchars(trim($inv->partner_zip_code . ' ' . $inv->partner_town)) ?><br>
                            <?php endif; ?>
                            <?php if ($inv->organization_identifier): ?>
                                IČO: <?= htmlspecialchars($inv->organization_identifier) ?><br>
                            <?php endif; ?>
                            <?php if ($inv->vat_organization_identifier): ?>
                                DIČ: <?= htmlspecialchars($inv->vat_organization_identifier) ?><br>
                            <?php endif; ?>
                        </div>
                        <div class="box small">
                            <b>Číslo dokladu:</b> <?= htmlspecialchars($inv->invoice_nr) ?><br>
                            <b>Datum vystavení:</b> <?= htmlspecialchars($date_inv) ?><br>
                            <b>Datum splatnosti:</b> <?= htmlspecialchars($date_due) ?><br>
                            <b>Variabilní symbol:</b> <?= htmlspecialchars($vs) ?><br>
                            <b>Bankovní účet:</b> <?= htmlspecialchars($acc) ?><br>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <tr>
                    <th>Popis</th>
                    <th>Množství</th>
                    <th>Jedn. cena</th>
                    <th>Částka</th>
                </tr>
                <?php foreach ($items as $it):
                    $qty   = (float)$it->quantity;
                    $price = (float)$it->price;
                    $line  = $qty * $price;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($it->name) ?></td>
                        <td class="right"><?= number_format($qty, 2, ',', ' ') ?></td>
                        <td class="right"><?= number_format($price, 2, ',', ' ') . ' ' . $currency ?></td>
                        <td class="right"><?= number_format($line, 2, ',', ' ') . ' ' . $currency ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="right"><b>Celkem k úhradě:</b></td>
                    <td class="right"><b><?= number_format($total, 2, ',', ' ') . ' ' . $currency ?></b></td>
                </tr>
            </table>

            <br><br>
            <div class="small">
                Datum uskutečnění zdanitelného plnění: <?= htmlspecialchars($date_vat) ?><br>
                Tento doklad byl vystaven elektronicky.
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

        // uložení cesty do DB
        $db->query("
            UPDATE invoices
            SET pdf_filename = ?
            WHERE id = ?
            LIMIT 1
        ", array($path, $invoice_id));

        return $path;
    }
}
