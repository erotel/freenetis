<?php defined('SYSPATH') or die('No direct access allowed.');

class Member_doc_data_Model extends ORM
{
  public static function get(int $member_id): array
  {
    $db = Database::instance();

    $row = $db->query(
      "SELECT
        m.id                         AS member_id,
        m.name                       AS member_name,
        vs.variable_symbol           AS variable_symbol,
        s.street                     AS street_name,
        ap.street_number             AS street_number,
        t.town                       AS town_name,
        t.zip_code                   AS zip_code,
        m.organization_identifier        AS ico,
        m.vat_organization_identifier    AS dic
      FROM members m
      LEFT JOIN accounts a
        ON a.member_id = m.id
       AND a.account_attribute_id = " . (int)Account_attribute_Model::CREDIT . "
      LEFT JOIN variable_symbols vs
        ON vs.account_id = a.id
      LEFT JOIN address_points ap
        ON ap.id = m.address_point_id
      LEFT JOIN streets s
        ON s.id = ap.street_id
      LEFT JOIN towns t
        ON t.id = ap.town_id
      WHERE m.id = ?
      LIMIT 1",
      array($member_id)
    )->current();

    if (!$row) {
      throw new Exception('Member not found');
    }

    // objekt/array kompatibilita
    $get = function ($k) use ($row) {
      if (is_array($row)) return (string)($row[$k] ?? '');
      return (string)($row->$k ?? '');
    };

    $name = trim($get('member_name'));

    $street = trim($get('street_name'));
    $street_no = trim($get('street_number'));
    $town = trim($get('town_name'));
    $zip = trim($get('zip_code'));

    $address = '';
    if ($street !== '' || $street_no !== '') {
      $address .= trim($street . ' ' . $street_no);
    }
    if ($town !== '' || $zip !== '') {
      $address .= ($address !== '' ? "\n" : '') . trim($zip . ' ' . $town);
    }

    $vs = trim($get('variable_symbol'));
    if ($vs === '') {
      // fallback (jako u vás někdy): VS = member_id
      $vs = (string)$member_id;
    }

    return array(
      'member_id' => $member_id,
      'name'      => $name,
      'variable_symbol' => $vs,
      'address'   => $address,
      'ico'       => trim($get('ico')),
      'dic'       => trim($get('dic')),
    );
  }
}
