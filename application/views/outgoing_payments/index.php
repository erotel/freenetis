<h2><?php echo __('Outgoing payments') ?></h2>
<br />




<?php if ($this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')): ?>
  <div style="margin: 0 0 15px 0;">
    <div style="margin-bottom:8px;">
      <?php echo __('Export approved payments: if Fio token is not set, XML file is downloaded; if token is set, it is uploaded to Fio.') ?>
    </div>

    <a class="submit" style="text-decoration:none; color:white"
      href="<?php echo url_lang::site() ?>/outgoing_payments/export/31"
      onclick="return confirm('<?php echo __('Export/Send approved payments for bank account ID 31?') ?>');">
      <?php echo __('Export/Send (bank 31)') ?>
    </a>

    &nbsp;

    <a class="submit" style="text-decoration:none; color:white"
      href="<?php echo url_lang::site() ?>/outgoing_payments/export/32"
      onclick="return confirm('<?php echo __('Export/Send approved payments for bank account ID 32?') ?>');">
      <?php echo __('Export/Send (bank 32)') ?>
    </a>
  </div>

  <?php if ($status === 'all'): ?>
    <a class="submit"
      style="text-decoration:none; color:white"
      href="<?php echo url_lang::site() ?>/outgoing_payments/approve_all"
      onclick="return confirm('<?php
                                echo __('Approve ALL draft outgoing payments?');
                                ?>');">
      <?php echo __('Schválit vše (draft)'); ?>
    </a>
  <?php endif; ?>
<?php endif; ?>




<div>
  <?php echo __('Filter') ?>:
  <?php echo html::anchor('outgoing_payments/index/all', __('All')) ?> |
  <?php echo html::anchor('outgoing_payments/index/draft', __('Draft')) ?> |
  <?php echo html::anchor('outgoing_payments/index/approved', __('Approved')) ?> |
  <?php echo html::anchor('outgoing_payments/index/exported', __('Exported')) ?> |
  <?php echo html::anchor('outgoing_payments/index/paid', __('Paid')) ?> |
  <?php echo html::anchor('outgoing_payments/index/cancelled', __('Cancelled')) ?>
</div>

<br />

<table class="extended">
  <tr>
    <th>ID</th>
    <th><?php echo __('Created') ?></th>
    <th><?php echo __('Reason') ?></th>
    <th><?php echo __('From bank') ?></th>
    <th><?php echo __('To account') ?></th>
    <th><?php echo __('Amount') ?></th>
    <th><?php echo __('VS') ?></th>
    <th><?php echo __('Status') ?></th>
    <th><?php echo __('Actions') ?></th>
  </tr>

  <?php foreach ($items as $op): ?>
    <tr>

      <td><?php echo (int)$op->id ?></td>
      <td><?php echo $op->created_at ?></td>
      <td>
        <?php
        $reason = trim((string)$op->reason);
        echo $reason !== '' ? url_lang::lang('texts.' . $reason) : '';
        ?>
      </td>

      <td><?php echo html::specialchars($op->from_account_nr . '/' . $op->from_bank_nr) ?></td>
      <td><?php echo html::specialchars($op->target_account) ?></td>
      <td><?php echo number_format((float)$op->amount, 2, ',', ' ') . ' ' . $op->currency ?></td>
      <td><?php echo html::specialchars((string)$op->variable_symbol) ?></td>
      <td>
        <?php
        $status = trim((string)$op->status);
        echo $status !== '' ? url_lang::lang('texts.' . $status) : '';
        ?>
      </td>
      <td>
        <?php if ($op->status == 'draft' && $this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')): ?>
          <a class="submit" style="text-decoration:none; color:white"
            href="<?php echo url_lang::site() ?>/outgoing_payments/approve/<?php echo (int)$op->id ?>"
            onclick="return confirm('<?php echo __('Approve this outgoing payment?') ?>');">
            <?php echo __('Approve') ?>
          </a>
        <?php endif; ?>

        <?php if (in_array($op->status, array('draft', 'approved')) && $this->acl_check_edit('Accounts_Controller', 'unidentified_transfers')): ?>
          &nbsp;
          <a class="submit" style="text-decoration:none; color:white; background:#777"
            href="<?php echo url_lang::site() ?>/outgoing_payments/cancel/<?php echo (int)$op->id ?>"
            onclick="return confirm('<?php echo __('Cancel this outgoing payment?') ?>');">
            <?php echo __('Cancel') ?>
          </a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>



</table>