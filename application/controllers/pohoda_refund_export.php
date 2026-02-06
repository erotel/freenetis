<?php defined('SYSPATH') or die('No direct script access.');

$exp = new Pohoda_Refund_Export_Model();

// IDS bankovního účtu v POHODĚ (musí sedět na Bankovní účty v POHODĚ)
$pohoda_ids = 'FIO';

$res = $exp->export_new_to_file($pohoda_ids, 500, true /* označit exported? */, null /* ICO */);

echo "Exported {$res['count']} items to {$res['file']}\n";
