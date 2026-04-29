<?php

declare(strict_types=1);

// Keep the contact detail/edit table in one place so the view wrapper stays small.
?>
<table class="contact-card">
	<tr>
		<th>送信情報</th>
		<th>対応情報</th>
	</tr>
	<tr>
		<td><?= $h(\App\Support\ContactViewHelpers::formatDateTime($contact['contact_date'] ?? '', 'Y/m/d H:i:s')) ?> <?= $h($divMap['contact'][(int) ($contact['contact_div'] ?? 0)] ?? '') ?></td>
		<td>
			<select name="confirm_div">
				<option value="">選択してください</option>
<?php foreach (($divMap['confirm'] ?? array()) as $id => $label): ?>
				<option value="<?= $h($id) ?>"<?= (string) $id === (string) ($contact['confirm_div'] ?? '') ? ' selected' : '' ?>><?= $h($label) ?></option>
<?php endforeach; ?>
			</select>
<?php if ($error('confirm_div') !== ''): ?>
			<p class="err"><?= $h($error('confirm_div')) ?></p>
<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td><img width="320" src="<?= $h($imageUrl) ?>" onerror="this.onerror=null;this.src='<?= $h($dummyImage) ?>';"></td>
		<td>
			<textarea rows="13" cols="30" name="comment"><?= $h($contact['comment'] ?? '') ?></textarea>
<?php if ($error('comment') !== ''): ?>
			<p class="err"><?= $h($error('comment')) ?></p>
<?php endif; ?>
		</td>
	</tr>
</table>
