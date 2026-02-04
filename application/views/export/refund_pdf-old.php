<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; }
    h1 { font-size: 16pt; margin: 0 0 8px 0; }
    .meta { font-size: 9pt; color: #333; margin-bottom: 10px; }
    .box { border: 1px solid #000; padding: 10px; margin-top: 10px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 3px 0; vertical-align: top; }
    .right { text-align: right; }
    .small { font-size: 9pt; color: #333; margin-top: 12px; }
  </style>
</head>
<body>

<?php
  $is_customer = ((int)$doc['member_type'] === 2);
  $is_member90 = ((int)$doc['member_type'] === 90);

  // text podle typu
  $title = $is_customer
    ? 'Vratná faktura / doklad o vratce (zákazník)'
    : 'Vratná faktura / doklad o vratce (člen)';
?>

<h1><?php echo html::specialchars($title); ?></h1>

<div class="meta">
  <strong>Číslo dokladu:</strong> <?php echo html::specialchars($doc['doc_number']); ?>
  &nbsp;&nbsp;|&nbsp;&nbsp;
  <strong>VS:</strong> <?php echo html::specialchars($doc['variable_symbol']); ?>
</div>

<div class="box">
  <table>
    <tr>
      <td style="width:35%;"><strong>Jméno / Název:</strong></td>
      <td><?php echo html::specialchars($doc['name']); ?></td>
    </tr>

    <tr>
      <td><strong>Adresa:</strong></td>
      <td><?php echo nl2br(html::specialchars($doc['address'])); ?></td>
    </tr>

    <?php if (!empty($doc['ico']) || !empty($doc['dic'])): ?>
    <tr>
      <td><strong>IČO / DIČ:</strong></td>
      <td>
        <?php if (!empty($doc['ico'])): ?>IČO: <?php echo html::specialchars($doc['ico']); ?><?php endif; ?>
        <?php if (!empty($doc['ico']) && !empty($doc['dic'])): ?>&nbsp;&nbsp;<?php endif; ?>
        <?php if (!empty($doc['dic'])): ?>DIČ: <?php echo html::specialchars($doc['dic']); ?><?php endif; ?>
      </td>
    </tr>
    <?php endif; ?>

    <tr>
      <td><strong>Číslo účtu příjemce (vratka):</strong></td>
      <td><?php echo html::specialchars($doc['refund_account']); ?></td>
    </tr>

    <tr>
      <td><strong>Částka:</strong></td>
      <td><strong><?php echo html::specialchars($doc['amount'] . ' ' . $doc['currency']); ?></strong></td>
    </tr>
  </table>
</div>

<div class="small">
  <?php if ($is_customer): ?>
    Tento doklad slouží jako potvrzení o vrácení přeplatku při ukončení zákaznické smlouvy.
  <?php else: ?>
    Tento doklad slouží jako potvrzení o vrácení přeplatku při ukončení členství.
  <?php endif; ?>
</div>

</body>
</html>
