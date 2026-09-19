<?php

declare(strict_types=1);

// Keep chat message rendering together so list formatting changes stay localized.
?>
<?php foreach ($chat as $message): ?>
<?php $name = \App\Support\ChatViewHelpers::displayName($message); ?>
<?php $cssClass = \App\Support\ChatViewHelpers::cssClass($message); ?>
<?php $canEdit = \App\Support\ChatViewHelpers::canEdit($message, $loginAdminUuid ?? ''); ?>
<div class="chat-message <?= $h($cssClass) ?>" data-chat-uuid="<?= $h($message['chat_uuid'] ?? '') ?>">
	<div class="chat-meta"><?= $h($name) ?> · <?= $h($message['insert_date'] ?? '') ?></div>
	<div class="chat-bubble"><?= nl2br(\App\Support\ChatViewHelpers::linkify($h($message['chat_text'] ?? '')), false) ?></div>
	<div class="chat-message-actions">
<?php if ($canEdit): ?>
		<form class="chat-edit-form" action="chat_edit.php" method="post" onsubmit="return jigyodanChatEditSubmit(this);" data-current-text="<?= $h($message['chat_text'] ?? '') ?>">
			<?= csrf_field() ?>
			<input type="hidden" name="chat_uuid" value="<?= $h($message['chat_uuid'] ?? '') ?>">
			<input type="hidden" name="chat_text" value="">
			<button type="submit" class="chat-edit-btn">修正</button>
		</form>
<?php endif; ?>
		<form class="chat-delete-form" action="chat_delete.php" method="post" onsubmit="return confirm('削除します！');">
			<?= csrf_field() ?>
			<input type="hidden" name="chat_uuid" value="<?= $h($message['chat_uuid'] ?? '') ?>">
			<button type="submit" class="chat-delete-btn">削除</button>
		</form>
	</div>
</div>
<?php endforeach; ?>
