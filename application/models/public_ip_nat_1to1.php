<?php defined('SYSPATH') or die('No direct script access.');

class Public_ip_nat_1to1_Model extends Model
{
  protected $db;

  public function __construct()
  {
    parent::__construct();
    $this->db = Database::instance('default');
  }

  public function find_all(array $filters = array())
  {
    $sql = "
SELECT
  n.*,
  mc.name AS created_by_name,
  mm.name AS modified_by_name,
  u.member_id AS owner_member_id,
  om.name     AS owner_member_name
FROM public_ip_nat_1to1 n
LEFT JOIN members mc ON mc.id = n.created_by
LEFT JOIN members mm ON mm.id = n.modified_by
LEFT JOIN ip_addresses ip
  ON ip.ip_address COLLATE utf8mb3_czech_ci = n.private_ip COLLATE utf8mb3_czech_ci
LEFT JOIN ifaces i        ON i.id = ip.iface_id
LEFT JOIN devices d       ON d.id = i.device_id
LEFT JOIN users u         ON u.id = d.user_id
LEFT JOIN members om      ON om.id = u.member_id
WHERE 1=1
";



    $args = array();

    $enabled = isset($filters['enabled']) ? (string)$filters['enabled'] : 'all';
    if ($enabled === '1' || $enabled === '0') {
      $sql .= " AND enabled = ?";
      $args[] = (int)$enabled;
    }

    $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
    if ($q !== '') {
      $sql .= " AND (n.public_ip LIKE ? OR n.private_ip LIKE ? OR om.name LIKE ? OR u.member_id LIKE ?)";
      $args[] = '%' . $q . '%';
      $args[] = '%' . $q . '%';
      $args[] = '%' . $q . '%';
      $args[] = '%' . $q . '%';
    }

    $sql .= " ORDER BY n.enabled DESC, INET_ATON(n.public_ip) ASC";

    return $this->db->query($sql, $args);
  }

  public function get(int $id)
  {
    return $this->db->query(
      "SELECT * FROM public_ip_nat_1to1 WHERE id = ? LIMIT 1",
      array($id)
    )->current();
  }

  public function create(array $data, int $member_id): void
  {
    $this->db->insert('public_ip_nat_1to1', array(
      'public_ip'  => $data['public_ip'],
      'private_ip' => $data['private_ip'],
      'scope'      => NULL,
      'enabled'    => (int)$data['enabled'],
      'comment'    => NULL, // už neřešíš
      'created'    => date('Y-m-d H:i:s'),
      'modified'   => NULL,
      'created_by' => ($member_id ?: NULL),
      'modified_by' => NULL,
    ));
  }


  public function update(int $id, array $data, int $member_id): void
  {
    $this->db->update('public_ip_nat_1to1', array(
      'public_ip'   => $data['public_ip'],
      'private_ip'  => $data['private_ip'],
      'enabled'     => (int)$data['enabled'],
      'modified'    => date('Y-m-d H:i:s'),
      'modified_by' => ($member_id ?: NULL),
    ), array('id' => $id));
  }


  public function delete(int $id): void
  {
    $this->db->delete('public_ip_nat_1to1', array('id' => $id));
  }

  public function set_enabled(int $id, int $enabled, int $member_id): void
  {
    $this->db->update('public_ip_nat_1to1', array(
      'enabled'     => $enabled ? 1 : 0,
      'modified'    => date('Y-m-d H:i:s'),
      'modified_by' => ($member_id ?: NULL),
    ), array('id' => $id));
  }


  /**
   * @param int|null $ignore_id pro edit, aby nepadala unikátnost na sebe sama
   */
  public function validate(array $data, array &$errors, int $ignore_id = NULL): bool
  {
    $errors = array();

    $pub = trim((string)$data['public_ip']);
    $priv = trim((string)$data['private_ip']);

    if ($pub === '' || !filter_var($pub, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $errors['public_ip'] = __('Invalid public IPv4 address');
    }
    // private IP může být prázdná (clear)
    if ($priv !== '' && !filter_var($priv, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      $errors['private_ip'] = __('Invalid private IPv4 address');
    }

    if ($pub !== '' && $priv !== '' && $pub === $priv) {
      $errors['private_ip'] = __('Public and private IP cannot be the same');
    }

    // unikátnost public_ip + scope(NULL)
    if (!isset($errors['public_ip']) && $pub !== '') {
      $sql = "SELECT id FROM public_ip_nat_1to1 WHERE public_ip = ? AND scope IS NULL";
      $args = array($pub);

      if ($ignore_id) {
        $sql .= " AND id <> ?";
        $args[] = (int)$ignore_id;
      }

      $exists = $this->db->query($sql . " LIMIT 1", $args)->current();
      if ($exists) {
        $errors['public_ip'] = __('This public IP already exists');
      }
    }

    return !count($errors);
  }

  public function update_private_ip(int $id, ?string $private_ip, int $member_id): void
  {
    $private_ip = trim((string)$private_ip);
    if ($private_ip === '') $private_ip = NULL;

    $this->db->update('public_ip_nat_1to1', array(
      'private_ip'  => $private_ip,
      'modified'    => date('Y-m-d H:i:s'),
      'modified_by' => ($member_id ?: NULL),
    ), array('id' => $id));
  }

  public function clear_private_ip(int $id, int $member_id): void
  {
    $this->db->update('public_ip_nat_1to1', array(
      'private_ip'  => NULL,
      'modified'    => date('Y-m-d H:i:s'),
      'modified_by' => ($member_id ?: NULL),
    ), array('id' => $id));
  }
}
