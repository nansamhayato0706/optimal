<?php

declare(strict_types=1);

// Keep chat message rendering together so list formatting changes stay localized.
?>
<?php foreach ($chat as $message): ?>
<?php $name = \App\Support\ChatViewHelpers::displayName($message); ?>
<?php $cssClass = \App\Support\ChatViewHelpers::cssClass($message); ?>
<div class="chat-message <?= $h($cssClass) ?>">
	<div class="chat-meta"><?= $h($name) ?> · <?= $h($message['insert_date'] ?? '') ?></div>
	<div class="chat-bubble"><?= nl2br($h($message['chat_text'] ?? ''), false) ?></div>
</div>
<?php endforeach; ?>
