<?php defined('SYSPATH') or die('No direct script access.');

class Public_port_forwards_Model extends Model
{
  protected $db;

  public function __construct()
  {
    parent::__construct();
    $this->db = Database::instance('default');
  }

  public function find_all()
  {
    $sql = "
  SELECT p.*,
         u.member_id AS owner_member_id,
         om.name     AS owner_member_name,
         mc.name     AS created_by_name,
         mm.name     AS modified_by_name
  FROM public_port_forwards p
  LEFT JOIN ip_addresses ip ON BINARY ip.ip_address = BINARY p.private_ip
  LEFT JOIN ifaces i ON i.id = ip.iface_id
  LEFT JOIN devices d ON d.id = i.device_id
  LEFT JOIN users u ON u.id = d.user_id
  LEFT JOIN members om ON om.id = u.member_id
  LEFT JOIN members mc ON mc.id = p.created_by
  LEFT JOIN members mm ON mm.id = p.modified_by
  ORDER BY p.public_ip ASC, p.protocol ASC, p.public_port_from ASC
  ";
    return $this->db->query($sql);
  }


  public function get(int $id)
  {
    return $this->db->query(
      "SELECT * FROM public_port_forwards WHERE id = ? LIMIT 1",
      array($id)
    )->current();
  }

  public function create(array $data, int $member_id)
  {
    $this->db->insert('public_port_forwards', array(
      'public_ip' => $data['public_ip'],
      'public_port_from' => $data['public_port_from'],
      'public_port_to' => $data['public_port_to'],
      'private_ip' => $data['private_ip'],
      'private_port_from' => $data['private_port_from'],
      'private_port_to' => $data['private_port_to'],
      'protocol' => $data['protocol'],
      'enabled' => $data['enabled'],
      'created' => date('Y-m-d H:i:s'),
      'created_by' => $member_id
    ));
  }

  public function update(int $id, array $data, int $member_id)
  {
    $this->db->update('public_port_forwards', array(
      'public_ip' => $data['public_ip'],
      'public_port_from' => $data['public_port_from'],
      'public_port_to' => $data['public_port_to'],
      'private_ip' => $data['private_ip'],
      'private_port_from' => $data['private_port_from'],
      'private_port_to' => $data['private_port_to'],
      'protocol' => $data['protocol'],
      'enabled' => $data['enabled'],
      'modified' => date('Y-m-d H:i:s'),
      'modified_by' => $member_id
    ), array('id' => $id));
  }

  public function delete(int $id)
  {
    $this->db->delete('public_port_forwards', array('id' => $id));
  }

  public function validate(array $data, array &$errors, int $ignore_id = 0): bool
  {
    $errors = array();

    $allowed = $this->get_allowed_public_ips();
    if (empty($data['public_ip']) || !in_array($data['public_ip'], $allowed, true)) {
      $errors['public_ip'] = __('Public IP must be selected from allowed list');
    }


    if (!filter_var($data['public_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
      $errors['public_ip'] = 'Invalid public IP';

    if (!filter_var($data['private_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
      $errors['private_ip'] = 'Invalid private IP';

    foreach (['public_port_from', 'public_port_to', 'private_port_from', 'private_port_to'] as $k) {
      if ($data[$k] < 1 || $data[$k] > 65535)
        $errors[$k] = 'Port must be 1-65535';
    }

    if ($data['public_port_from'] > $data['public_port_to'])
      $errors['public_port_from'] = 'Invalid public range';

    if ($data['private_port_from'] > $data['private_port_to'])
      $errors['private_port_from'] = 'Invalid private range';

    if (($data['public_port_to'] - $data['public_port_from']) !=
      ($data['private_port_to'] - $data['private_port_from'])
    )
      $errors['private_port_from'] = 'Ranges must have same size';

    // kolize
    if (!count($errors)) {
      $sql = "
      SELECT id FROM public_port_forwards
      WHERE public_ip = ?
        AND protocol = ?
        AND enabled = 1
        AND id <> ?
        AND public_port_from <= ?
        AND public_port_to >= ?
      LIMIT 1
      ";

      $exists = $this->db->query($sql, array(
        $data['public_ip'],
        $data['protocol'],
        $ignore_id,
        $data['public_port_to'],
        $data['public_port_from']
      ))->current();

      if ($exists)
        $errors['public_port_from'] = 'Port collision detected';
    }

    return !count($errors);
  }

  public function set_enabled(int $id, int $enabled, int $member_id): void
  {
    $this->db->update('public_port_forwards', array(
      'enabled' => $enabled ? 1 : 0,
      'modified' => date('Y-m-d H:i:s'),
      'modified_by' => ($member_id ?: NULL),
    ), array('id' => $id));
  }

  public function get_allowed_public_ips(): array
  {
    $res = $this->db->query(
      "SELECT ip FROM public_ips WHERE enabled = 1 ORDER BY ip ASC"
    );

    $ips = array();
    foreach ($res as $r) {
      $ips[] = (string)$r->ip;
    }
    return $ips;
  }
}
