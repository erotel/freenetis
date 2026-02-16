<?php defined('SYSPATH') or die('No direct script access.'); ?>

<?php
// ===== Pojistky proti undefined proměnným =====
if (!isset($data) || !is_array($data)) $data = array();
if (!isset($errors) || !is_array($errors)) $errors = array();
if (!isset($public_ips) || !is_array($public_ips)) $public_ips = array();
if (!isset($action)) $action = 'add';

$defaults = array(
  'public_ip' => '',
  'public_port_from' => '',
  'public_port_to' => '',
  'private_ip' => '',
  'private_port_from' => '',
  'private_port_to' => '',
  'protocol' => 'tcp',
  'enabled' => 1,
);

foreach ($defaults as $k => $v) {
  if (!isset($data[$k])) $data[$k] = $v;
}
?>

<h2>
  <?php echo ($action === 'add'
      ? __('Add public port forward')
      : __('Edit public port forward')); ?>
</h2>

<br />

<?php if (!empty($errors)): ?>
  <div class="error">
    <ul>
      <?php foreach ($errors as $err): ?>
        <li><?php echo html::specialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <br />
<?php endif; ?>

<form method="post" action="">
  <table class="extended">

    <!-- Public IP -->
    <tr>
      <th><?php echo __('Public IP') ?></th>
      <td>
        <select name="public_ip">
          <option value=""><?php echo __('Select public IP') ?></option>

          <?php foreach ($public_ips as $ip): ?>
            <option value="<?php echo html::specialchars($ip) ?>"
              <?php echo ($data['public_ip'] === $ip ? 'selected' : '') ?>>
              <?php echo html::specialchars($ip) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </td>
    </tr>

    <!-- Public Port -->
    <tr>
      <th><?php echo __('Public port from') ?></th>
      <td>
        <input type="text"
               name="public_port_from"
               style="width:120px"
               value="<?php echo html::specialchars($data['public_port_from']) ?>" />

        &nbsp;<?php echo __('to') ?>&nbsp;

        <input type="text"
               name="public_port_to"
               style="width:120px"
               value="<?php echo html::specialchars($data['public_port_to']) ?>" />

        <span style="color:#666; margin-left:10px;">
          <?php echo __('leave "to" empty for single port') ?>
        </span>
      </td>
    </tr>

    <!-- Private IP -->
    <tr>
      <th><?php echo __('Private IP') ?></th>
      <td>
        <input type="text"
               name="private_ip"
               value="<?php echo html::specialchars($data['private_ip']) ?>" />
      </td>
    </tr>

    <!-- Private Port -->
    <tr>
      <th><?php echo __('Private port from') ?></th>
      <td>
        <input type="text"
               name="private_port_from"
               style="width:120px"
               value="<?php echo html::specialchars($data['private_port_from']) ?>" />

        &nbsp;<?php echo __('to') ?>&nbsp;

        <input type="text"
               name="private_port_to"
               style="width:120px"
               value="<?php echo html::specialchars($data['private_port_to']) ?>" />
      </td>
    </tr>

    <!-- Protocol -->
    <tr>
      <th><?php echo __('Protocol') ?></th>
      <td>
        <select name="protocol">
          <option value="tcp"
            <?php echo ($data['protocol'] === 'tcp' ? 'selected' : '') ?>>
            TCP
          </option>
          <option value="udp"
            <?php echo ($data['protocol'] === 'udp' ? 'selected' : '') ?>>
            UDP
          </option>
        </select>
      </td>
    </tr>

    <!-- Enabled -->
    <tr>
      <th><?php echo __('Enabled') ?></th>
      <td>
        <input type="checkbox"
               name="enabled"
               value="1"
               <?php echo ((int)$data['enabled'] ? 'checked' : '') ?> />
      </td>
    </tr>

  </table>

  <br />

  <input type="submit"
         class="submit"
         value="<?php echo __('Save') ?>" />

  &nbsp;

  <a class="submit"
     style="text-decoration:none; color:white"
     href="<?php echo url_lang::base() ?>network/public_ports">
    <?php echo __('Back') ?>
  </a>

</form>
