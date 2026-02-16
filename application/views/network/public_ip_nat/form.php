<?php defined('SYSPATH') or die('No direct script access.'); ?>
<?php
if (!isset($data) || !is_array($data)) $data = array();
if (!isset($data['public_ip']))  $data['public_ip'] = '';
if (!isset($data['private_ip'])) $data['private_ip'] = '';
if (!isset($data['enabled']))    $data['enabled'] = 1;
if (!isset($data['comment']))    $data['comment'] = '';
if (!isset($errors) || !is_array($errors)) $errors = array();
?>

<h2>
	<?php echo ($action === 'add' ? __('Add public IP (1:1 NAT)') : __('Edit public IP (1:1 NAT)')) ?>
</h2>
<br />

<?php if (!empty($errors)): ?>
	<div class="error">
		<ul>
			<?php foreach ($errors as $k => $v): ?>
				<li><?php echo html::specialchars($v) ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<br />
<?php endif; ?>

<form method="post" action="">
	<table class="extended">
		<tr>
			<th><?php echo __('Public IP') ?></th>
			<td>
				<input type="text" name="public_ip" value="<?php echo html::specialchars($data['public_ip']) ?>" />
			</td>
		</tr>

		<tr>
			<th><?php echo __('Private IP') ?></th>
			<td>
				<input type="text" name="private_ip" value="<?php echo html::specialchars($data['private_ip']) ?>" />
			</td>
		</tr>

		<tr>
			<th><?php echo __('Enabled') ?></th>
			<td>
				<input type="checkbox" name="enabled" value="1" <?php echo ((int)$data['enabled'] ? 'checked' : '') ?> />
			</td>
		</tr>

		
	</table>

	<br />
	<input type="submit" class="submit" value="<?php echo __('Save') ?>" />
	&nbsp;
	<a class="submit" style="text-decoration:none; color:white"
	   href="<?php echo url_lang::base() ?>network/public_ip_nat">
		<?php echo __('Back') ?>
	</a>
</form>
