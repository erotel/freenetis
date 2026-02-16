<?php defined('SYSPATH') or die('No direct script access.'); ?>

<h2><?php echo __('Public IP (1:1 NAT)') ?></h2>
<br />

<form method="get" action="">
  <label><?php echo __('Enabled') ?>:</label>
  <select name="enabled">
    <option value="all" <?php echo ($filters['enabled'] === 'all' ? 'selected' : '') ?>><?php echo __('All') ?></option>
    <option value="1" <?php echo ($filters['enabled'] === '1' ? 'selected' : '') ?>><?php echo __('Yes') ?></option>
    <option value="0" <?php echo ($filters['enabled'] === '0' ? 'selected' : '') ?>><?php echo __('No') ?></option>
  </select>

  &nbsp;&nbsp;

  <label><?php echo __('Search') ?>:</label>
  <input type="text" name="q" value="<?php echo html::specialchars($filters['q']) ?>" />

  <input type="submit" class="submit" value="<?php echo __('Filter') ?>" />

  <?php if ($can_edit): ?>
    &nbsp;&nbsp;
    <a class="submit" style="text-decoration:none; color:white"
      href="<?php echo url_lang::base() ?>network/public_ip_nat_add">
      <?php echo __('Add') ?>
    </a>
  <?php endif; ?>
</form>

<br />

<table class="extended">
  <tr>
    <th><?php echo __('Public IP') ?></th>
    <th><?php echo __('Private IP') ?></th>
    <th><?php echo __('Owner') ?></th>
    <th><?php echo __('Enabled') ?></th>
    <th><?php echo __('Last change') ?></th>
    <?php if ($can_edit): ?><th><?php echo __('Actions') ?></th><?php endif; ?>
  </tr>

  <?php if ($rows && $rows->count()): ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo html::specialchars($r->public_ip) ?></td>
        <td><?php echo html::specialchars($r->private_ip) ?></td>

        <td>
          <?php
          if (!empty($r->owner_member_id)) {
            echo (int)$r->owner_member_id . ' - ' . html::specialchars((string)$r->owner_member_name);
          } else {
            echo '-';
          }
          ?>
        </td>

        <td>
          <?php echo ((int)$r->enabled ? __('Yes') : __('No')) ?>
        </td>
        <td>
          <?php
          if ($r->modified && $r->modified_by) {
            echo html::specialchars($r->modified)
              . ' (' . html::specialchars((string)$r->modified_by_name) . ')';
          } else {
            echo html::specialchars($r->created)
              . ' (' . html::specialchars((string)$r->created_by_name) . ')';
          }
          ?>
        </td>




        <?php if ($can_edit): ?>
          <td>
            <a href="<?php echo url_lang::base() ?>network/public_ip_nat_edit/<?php echo (int)$r->id ?>">
              <?php echo __('Edit') ?>
            </a>
            &nbsp;|&nbsp;
            <a href="<?php echo url_lang::base() ?>network/public_ip_nat_toggle/<?php echo (int)$r->id ?>">
              <?php echo ((int)$r->enabled ? __('Disable') : __('Enable')) ?>
            </a>
            &nbsp;|&nbsp;
            <a href="<?php echo url_lang::base() ?>network/public_ip_nat_delete/<?php echo (int)$r->id ?>"
              onclick="return confirm('<?php echo __('Really delete?') ?>');">
              <?php echo __('Delete') ?>
            </a>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr>
      <td colspan="<?php echo ($can_edit ? 5 : 4) ?>"><?php echo __('No records found') ?></td>
    </tr>
  <?php endif; ?>
</table>