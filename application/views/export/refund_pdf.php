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

    .page {
      width: 210mm;
      height: 297mm;
      margin: 0 auto;
      /* vystředění při náhledu */
      padding: 15mm;
      /* vnitřní okraje faktury */
      border: 1px solid #000;
      box-sizing: border-box;
    }


    .invoice {
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      padding: 15px 20px 20px;
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

      padding: 5px 7px;
      margin-top: 6px;
      font-size: 10px;
    }

    .box-inner {
      padding: 3px 5px;
    }

    .box p {
      margin: 0;
      padding: 0;
      text-decoration: none;
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
  <?php
  $is_customer = ((int)$doc['member_type'] === 2);
  $is_member90 = ((int)$doc['member_type'] === 90);

  // text podle typu
  $title = $is_customer
    ? 'Fakturujeme Vám na základě vyúčtování - přeplatek za "Platba za připojení k síti internet" - ukončeno k'
    : 'Fakturujeme Vám na základě vyúčtování - přeplatek za "Členství" - ukončeno k';

  $leaving_date_fmt = '';

  if (!empty($doc['leaving_date'])) {
    $d = DateTime::createFromFormat('Y-m-d', $doc['leaving_date']);
    if ($d) {
      $leaving_date_fmt = $d->format('j.n.Y'); // 4.2.2026
    }
  }

  $amount_with_vat = (float)$doc['amount'];
  $vat_rate = 21;

  $amount_without_vat = $amount_with_vat / (1 + $vat_rate / 100);
  $vat_amount = $amount_with_vat - $amount_without_vat;

  $amount_without_vat = round($amount_without_vat, 2);
  $vat_amount = round($vat_amount, 2);

  // Dnešní datum
  $issue_date = new DateTime('today');

  // Splatnost +14 dní
  $due_date = (clone $issue_date)->modify('+14 days');

  // Formát pro výstup: 4.2.2026
  $issue_date_fmt = $issue_date->format('j.n.Y');
  $due_date_fmt   = $due_date->format('j.n.Y');



  ?>
  <div class="page">
    <div class="invoice">

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
            <div class="invoice-title">FAKTURA - DAŇOVÝ DOKLAD </div>
            <div class="invoice-title">č. <?= htmlspecialchars($doc['doc_number']) ?></div>

            <div class="box" style="margin-top:10px; text-align:right;">
              Variabilní symbol: <span style="display:inline-block; min-width:70px; text-align:left;"><?= htmlspecialchars($doc['variable_symbol']) ?></span>
            </div>


            <div class="box">
              <div class="box-inner">
                <h4>Odběratel:</h4>
                <p><?= htmlspecialchars($doc['name']) ?></p>
                <div>

                  <p>
                    <?= htmlspecialchars(trim($doc['address'])) ?>
                  </p>


                  <?php if (!empty($doc['ico'])): ?>IČO: <?php echo html::specialchars($doc['ico']); ?><?php endif; ?>
                  <?php if (!empty($doc['ico']) && !empty($doc['dic'])): ?>&nbsp;&nbsp;<?php endif; ?>
                  <?php if (!empty($doc['dic'])): ?>DIČ: <?php echo html::specialchars($doc['dic']); ?><?php endif; ?>

                </div>

              </div>
            </div>
          </td>
        </tr>
      </table>

      <!-- Účet -->
      <div class="account-box">

      </div>

      <!-- Datum a forma úhrady -->
      <table class="dates-row">
        <tr>
          <td class="col">
            <span class="label">Datum vystavení:</span><br>
            <?= htmlspecialchars($issue_date_fmt) ?>
          </td>
          <td class="col">
            <span class="label">Datum splatnosti:</span><br>
            <?= htmlspecialchars($due_date_fmt) ?>
          </td>
          <td class="col">
            <span class="label">Datum uskutečnění plnění:</span><br>
            <?= htmlspecialchars($leaving_date_fmt); ?>
          </td>
          <td class="col">
            <span class="label">Forma úhrady:</span><br>
            Převodem
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
            <td><?= htmlspecialchars($title . ' ' . $leaving_date_fmt); ?></td>
            <td class="num"><?= number_format('-' . $amount_without_vat, 2, ',', ' ') ?></td>
            <td class="num">21 %</td>
            <td class="num"><?= number_format('-' . $vat_amount, 2, ',', ' ') ?></td>
            <td class="num"><?= number_format('-' . $amount_with_vat, 2, ',', ' ') ?></td>

          </tr>




        </tbody>
      </table>

      <!-- Celkem k úhradě -->
      <div class="total-box">
        CELKEM K ÚHRADĚ: &nbsp; <?php echo html::specialchars('-' . number_format($doc['amount'], 2, ',', ' ') . ' Kč '); ?>
        <br>


      </div>


      <!-- Vystavil -->
      <div style="margin-top:8px; font-size:10px;">
      </div>

      <!-- Text pod tabulkou -->
      <div class="middle-note">
        Vzniklý přeplatek ve výši <?= htmlspecialchars(number_format($doc['amount'], 2, ',', ' ')) ?> kč Vám bude vrácen na účet <?= htmlspecialchars($doc['refund_account']) ?>
        <br>
        <br>
        Spolek PVfree.net, z.s., založen 12.3.2004, zapsán pod značkou L 10341/KSBR Krajským soudem v Brně.<br>
        <br>


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
                  <td class="num"><?= number_format('-' . $amount_without_vat, 2, ',', ' ') ?></td>
                  <td class="num">21 %</td>
                  <td class="num"><?= number_format('-' . $vat_amount, 2, ',', ' ') ?></td>
                  <td class="num"><?= number_format('-' . $amount_with_vat, 2, ',', ' ') ?></td>
                </tr>
              </tbody>
            </table>
          </td>

        </tr>
      </table>

      <div class="footer">
      </div>

    </div>
  </div>
</body>

</html>