<?php defined('SYSPATH') or die('No direct script access.'); ?>

<?php
// ===== Pojistky proti undefined proměnným =====
if (!isset($action)) $action = 'edit';
if (!isset($data) || !is_array($data)) $data = array();
if (!isset($errors) || !is_array($errors)) $errors = array();

$defaults = array(
  'public_ip'  => '',
  'private_ip' => '',
  'enabled'    => 1,
);

foreach ($defaults as $k => $v) {
  if (!isset($data[$k])) $data[$k] = $v;
}
?>

<h2>
  <?php
    if ($action === 'edit') {
      echo __('Edit public IP (1:1 NAT)');
    } else {
      echo __('Add public IP (1:1 NAT)');
    }
  ?>
</h2>

<br />

<?php if (!empty($errors) && is_array($errors)): ?>
  <div class="error">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?php echo html::specialchars((string)$e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <br />
<?php endif; ?>

<form method="post" action="">
  <table class="extended">

    <!-- PUBLIC IP -->
    <tr>
      <th><?php echo __('Public IP') ?></th>
      <td>

        <?php if ($action === 'edit'): ?>

          <!-- při edit readonly -->
          <strong><?php echo html::specialchars((string)$data['public_ip']); ?></strong>

        <?php else: ?>

          <input type="text" name="public_ip"
            value="<?php echo html::specialchars((string)$data['public_ip']); ?>" />

          <?php if (!empty($errors['public_ip'])): ?>
            <div class="error"><?php echo html::specialchars((string)$errors['public_ip']); ?></div>
          <?php endif; ?>

        <?php endif; ?>

      </td>
    </tr>

    <!-- PRIVATE IP -->
    <tr>
      <th><?php echo __('Private IP') ?></th>
      <td>

        <input type="text" name="private_ip"
          value="<?php echo html::specialchars((string)$data['private_ip']); ?>" />

        <?php if ($action === 'edit'): ?>
          <span style="color:#666; margin-left:10px;">
            <?php echo __('leave empty to clear mapping'); ?>
          </span>
        <?php endif; ?>

        <?php if (!empty($errors['private_ip'])): ?>
          <div class="error"><?php echo html::specialchars((string)$errors['private_ip']); ?></div>
        <?php endif; ?>

      </td>
    </tr>

   

    <!-- SUBMIT -->
    <tr>
      <th></th>
      <td>
        <input type="submit" class="submit" value="<?php echo __('Save'); ?>" />
        &nbsp;
        <a href="<?php echo url_lang::base(); ?>network/public_ip_nat">
          <?php echo __('Back'); ?>
        </a>
      </td>
    </tr>

  </table>
</form>
