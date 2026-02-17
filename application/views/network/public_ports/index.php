<?php defined('SYSPATH') or die('No direct script access.'); ?>

<h2><?php echo __('Public ports') ?></h2>
<br />

<?php if ($can_edit): ?>
  <a class="submit" style="text-decoration:none; color:white"
     href="<?php echo url_lang::base() ?>network/public_ports_add">
    <?php echo __('Add') ?>
  </a>
  <br /><br />
<?php endif; ?>

<table class="extended">
  <tr>
    <th><?php echo __('Public IP') ?></th>
    <th><?php echo __('Public port') ?></th>
    <th><?php echo __('Private IP') ?></th>
    <th><?php echo __('Owner') ?></th>
    <th><?php echo __('Private port') ?></th>
    <th><?php echo __('Proto') ?></th>
    
    <th><?php echo __('Last change') ?></th>
    <?php if ($can_edit): ?><th><?php echo __('Actions') ?></th><?php endif; ?>
  </tr>

  <?php if ($rows && $rows->count()): ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo html::specialchars($r->public_ip) ?></td>

        <td>
          <?php
            $pp = ((int)$r->public_port_from === (int)$r->public_port_to)
              ? (string)(int)$r->public_port_from
              : ((int)$r->public_port_from . '-' . (int)$r->public_port_to);
            echo html::specialchars($pp);
          ?>
        </td>

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
          <?php
            $rp = ((int)$r->private_port_from === (int)$r->private_port_to)
              ? (string)(int)$r->private_port_from
              : ((int)$r->private_port_from . '-' . (int)$r->private_port_to);
            echo html::specialchars($rp);
          ?>
        </td>

        <td><?php echo html::specialchars(strtoupper((string)$r->protocol)) ?></td>

        

        <td>
          <?php
            if ($r->modified && $r->modified_by) {
              echo html::specialchars($r->modified) . ' (' . html::specialchars((string)$r->modified_by_name) . ')';
            } else {
              echo html::specialchars($r->created) . ' (' . html::specialchars((string)$r->created_by_name) . ')';
            }
          ?>
        </td>

        <?php if ($can_edit): ?>
          <td>
            <a href="<?php echo url_lang::base() ?>network/public_ports_edit/<?php echo (int)$r->id ?>">
              <?php echo __('Edit') ?>
            </a>
            
            &nbsp;|&nbsp;
            <a href="<?php echo url_lang::base() ?>network/public_ports_delete/<?php echo (int)$r->id ?>"
               onclick="return confirm('<?php echo __('Really delete?') ?>');">
              <?php echo __('Delete') ?>
            </a>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="<?php echo ($can_edit ? 9 : 8) ?>"><?php echo __('No records found') ?></td></tr>
  <?php endif; ?>
</table>
