<?php defined('SYSPATH') or die('No direct script access.');

class Network_Controller extends Controller
{
  protected $db;

  public function __construct()
  {
    parent::__construct();

    if (!Settings::get('networks_enabled')) {
      self::error(ACCESS);
    }

    $this->db = Database::instance('default');
  }

  public function public_ip_nat()
  {
    if (!$this->acl_check_view('Network_Controller', 'public_ip_nat')) {
      self::error(ACCESS);
    }

    $model = new Public_ip_nat_1to1_Model();

    $filters = array(
      'enabled' => $this->get_scalar('enabled', 'all', 'get'),
      'q'       => trim($this->get_scalar('q', '', 'get')),
    );


    $rows = $model->find_all($filters);

    $view = new View('main');
    $view->title = __('Public IP (1:1 NAT)');
    $view->content = new View('network/public_ip_nat/index');
    $view->content->rows = $rows;
    $view->content->filters = $filters;
    $view->content->can_edit = $this->acl_check_edit('Network_Controller', 'public_ip_nat');
    $view->render(TRUE);
  }

  public function public_ip_nat_add()
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ip_nat')) {
      self::error(ACCESS);
    }

    $model = new Public_ip_nat_1to1_Model();
    $errors = array();

    $data = array(
      'public_ip'  => '',
      'private_ip' => '',
      'enabled'    => 1,
      'comment'    => '',
    );

    if ($_POST) {
      $data['public_ip']  = trim($this->get_scalar('public_ip', '', 'post'));
      $data['private_ip'] = trim($this->get_scalar('private_ip', '', 'post'));
      $data['enabled']    = $this->input->post('enabled') ? 1 : 0;
      $data['comment']    = trim($this->get_scalar('comment', '', 'post'));

      if ($model->validate($data, $errors)) {
        $model->create($data, (int)$this->member_id);
        status::success(__('Saved'));
        $this->redirect('network/public_ip_nat');
      }
    }

    $view = new View('main');
    $view->title = __('Add public IP (1:1 NAT)');
    $view->content = new View('network/public_ip_nat/form');
    $view->content->action = 'add';
    $view->content->data = $data;
    $view->content->errors = $errors;
    $view->render(TRUE);
  }

  public function public_ip_nat_edit($id = NULL)
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ip_nat')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);

    $model = new Public_ip_nat_1to1_Model();
    $row = $model->get((int)$id);
    if (!$row) self::error(RECORD);

    $errors = array();

    $data = array(
      'public_ip'  => (string)$row->public_ip,
      'private_ip' => (string)$row->private_ip,
      'enabled'    => (int)$row->enabled,
      'comment'    => (string)$row->comment,
    );

    if ($_POST) {
      //$data['public_ip']  = trim($this->get_scalar('public_ip', '', 'post'));
      $data['private_ip'] = trim($this->get_scalar('private_ip', '', 'post'));
      $data['enabled']    = $this->input->post('enabled') ? 1 : 0;
      $data['comment']    = trim($this->get_scalar('comment', '', 'post'));

      if ($model->validate($data, $errors, (int)$id)) {
        $model->update_private_ip((int)$id, $data['private_ip'], (int)$this->member_id);
        status::success(__('Saved'));
        $this->redirect('network/public_ip_nat');
      }
    }

    $view = new View('main');
    $view->title = __('Edit public IP (1:1 NAT)');
    $view->content = new View('network/public_ip_nat/form');
    $view->content->action = 'edit';
    $view->content->id = (int)$id;
    $view->content->data = $data;
    $view->content->errors = $errors;
    $view->render(TRUE);
  }

  public function public_ip_nat_toggle($id = NULL)
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ip_nat')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);

    $model = new Public_ip_nat_1to1_Model();
    $row = $model->get((int)$id);
    if (!$row) self::error(RECORD);

    $new = ((int)$row->enabled) ? 0 : 1;
    $model->set_enabled((int)$id, $new, (int)$this->member_id);
    $this->redirect('network/public_ip_nat');
  }

  public function public_ip_nat_delete($id = NULL)
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ip_nat')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);

    $model = new Public_ip_nat_1to1_Model();
    $row = $model->get((int)$id);
    if (!$row) self::error(RECORD);

    $model->delete((int)$id);
    status::success(__('Deleted'));
    $this->redirect('network/public_ip_nat');
  }


  private function get_scalar($key, $default = '', $source = 'get')
  {
    $v = ($source === 'post')
      ? $this->input->post($key, $default)
      : $this->input->get($key, $default);

    if (is_array($v)) {
      $v = reset($v);
    }

    if ($v === NULL) return $default;
    return (string)$v;
  }

  public function public_ports()
  {
    if (!$this->acl_check_view('Network_Controller', 'public_ports')) {
      self::error(ACCESS);
    }

    $model = new Public_port_forwards_Model();
    $rows = $model->find_all();

    $view = new View('main');
    $view->title = __('Public ports');
    $view->content = new View('network/public_ports/index');
    $view->content->rows = $rows;
    $view->content->can_edit = $this->acl_check_edit('Network_Controller', 'public_ports');
    $view->render(TRUE);
  }

  public function public_ports_add()
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ports')) {
      self::error(ACCESS);
    }

    $model = new Public_port_forwards_Model();
    $errors = array();

    $data = array(
      'public_ip' => '',
      'public_port_from' => '',
      'public_port_to' => '',
      'private_ip' => '',
      'private_port_from' => '',
      'private_port_to' => '',
      'protocol' => 'tcp',
      'enabled' => 1,
    );

    if ($_POST) {
      $data['public_ip'] = trim($this->get_scalar('public_ip', '', 'post'));
      $data['private_ip'] = trim($this->get_scalar('private_ip', '', 'post'));

      $data['public_port_from'] = (int)$this->get_scalar('public_port_from', '0', 'post');
      $data['public_port_to']   = (int)$this->get_scalar('public_port_to', '0', 'post');
      $data['private_port_from'] = (int)$this->get_scalar('private_port_from', '0', 'post');
      $data['private_port_to']   = (int)$this->get_scalar('private_port_to', '0', 'post');

      // pokud "to" prázdné/0 -> rovno "from"
      if ($data['public_port_to'] <= 0)  $data['public_port_to']  = $data['public_port_from'];
      if ($data['private_port_to'] <= 0) $data['private_port_to'] = $data['private_port_from'];

      $proto = strtolower(trim($this->get_scalar('protocol', 'tcp', 'post')));
      $data['protocol'] = in_array($proto, array('tcp', 'udp')) ? $proto : 'tcp';

      $data['enabled'] = $this->input->post('enabled') ? 1 : 0;

      if ($model->validate($data, $errors, 0)) {
        $model->create($data, (int)$this->member_id);
        status::success(__('Saved'));
        $this->redirect('network/public_ports');
      }
    }

    $view = new View('main');
    $view->title = __('Add public port');
    $view->content = new View('network/public_ports/form');
    $view->content->action = 'add';
    $view->content->data = $data;
    $view->content->errors = $errors;
    $view->content->public_ips = $model->get_allowed_public_ips();
    $view->render(TRUE);
  }
  public function public_ports_edit($id = NULL)
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ports')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);

    $model = new Public_port_forwards_Model();
    $row = $model->get((int)$id);
    if (!$row) self::error(RECORD);

    $errors = array();

    $data = array(
      'public_ip' => (string)$row->public_ip,
      'public_port_from' => (int)$row->public_port_from,
      'public_port_to' => (int)$row->public_port_to,
      'private_ip' => (string)$row->private_ip,
      'private_port_from' => (int)$row->private_port_from,
      'private_port_to' => (int)$row->private_port_to,
      'protocol' => (string)$row->protocol,
      'enabled' => (int)$row->enabled,
    );

    if ($_POST) {
      $data['public_ip'] = trim($this->get_scalar('public_ip', '', 'post'));
      $data['private_ip'] = trim($this->get_scalar('private_ip', '', 'post'));

      $data['public_port_from'] = (int)$this->get_scalar('public_port_from', '0', 'post');
      $data['public_port_to']   = (int)$this->get_scalar('public_port_to', '0', 'post');
      $data['private_port_from'] = (int)$this->get_scalar('private_port_from', '0', 'post');
      $data['private_port_to']   = (int)$this->get_scalar('private_port_to', '0', 'post');

      if ($data['public_port_to'] <= 0)  $data['public_port_to']  = $data['public_port_from'];
      if ($data['private_port_to'] <= 0) $data['private_port_to'] = $data['private_port_from'];

      $proto = strtolower(trim($this->get_scalar('protocol', 'tcp', 'post')));
      $data['protocol'] = in_array($proto, array('tcp', 'udp')) ? $proto : 'tcp';

      $data['enabled'] = $this->input->post('enabled') ? 1 : 0;

      if ($model->validate($data, $errors, (int)$id)) {
        $model->update((int)$id, $data, (int)$this->member_id);
        status::success(__('Saved'));
        $this->redirect('network/public_ports');
      }
    }

    $view = new View('main');
    $view->title = __('Edit public port');
    $view->content = new View('network/public_ports/form');
    $view->content->action = 'edit';
    $view->content->id = (int)$id;
    $view->content->data = $data;
    $view->content->errors = $errors;
    $view->content->public_ips = $model->get_allowed_public_ips();
    $view->render(TRUE);
  }

  public function public_ports_toggle($id = NULL)
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ports')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);

    $model = new Public_port_forwards_Model();
    $row = $model->get((int)$id);
    if (!$row) self::error(RECORD);

    $new = ((int)$row->enabled) ? 0 : 1;
    $model->set_enabled((int)$id, $new, (int)$this->member_id);

    $this->redirect('network/public_ports');
  }

  public function public_ports_delete($id = NULL)
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ports')) {
      self::error(ACCESS);
    }
    if (!is_numeric($id)) self::error(RECORD);

    $model = new Public_port_forwards_Model();
    $row = $model->get((int)$id);
    if (!$row) self::error(RECORD);

    $model->delete((int)$id);
    status::success(__('Deleted'));
    $this->redirect('network/public_ports');
  }

  public function public_ip_nat_clear($id = NULL)
  {
    if (!$this->acl_check_edit('Network_Controller', 'public_ip_nat'))
      self::error(ACCESS);

    if (!is_numeric($id)) self::error(RECORD);

    $model = new Public_ip_nat_1to1_Model();
    $row = $model->get((int)$id);
    if (!$row) self::error(RECORD);

    $model->clear_private_ip((int)$id, (int)$this->member_id);

    status::success(__('Mapping cleared'));
    $this->redirect('network/public_ip_nat');
  }
}
