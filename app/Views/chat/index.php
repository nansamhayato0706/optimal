<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$chat = $chatData['chat'] ?? array();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?= $h($imgBase) ?>favicon.ico" type="image/x-icon">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>chat.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
</head>
<body>
<div id="wrapper">
	<?php require dirname(__DIR__) . '/partials/header.php'; ?>
	<div id="main">
		<div class="page-card panel-stack">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?><?php if (($chatData['user_name'] ?? '') !== ''): ?>：<?= $h($chatData['user_name']) ?><?php endif; ?></h3>
			</div>
			<div id="chat">
				<p class="rf-help" id="chat_text_help">255文字以内、8行以内で入力してください。</p>
				<form class="chat-send-form" action="chat_send.php" method="post" enctype="multipart/form-data">
					<?= csrf_field() ?>
					<input type="hidden" name="insert_date" value="<?= $h($chatData['date'] ?? '') ?>">
					<div class="chat-input-row">
						<div class="chat-input-wrap">
							<label class="chat-attach-btn" for="chat_file" title="ファイルを添付">
								<span aria-hidden="true">＋</span>
							</label>
							<input type="file" name="chat_file" id="chat_file" class="chat-file-input">
							<textarea class="rf-autosize" name="chat_text" id="chat_text" placeholder="メッセージを入力..." rows="1" data-max-lines="8" aria-describedby="chat_text_help"></textarea>
						</div>
						<input type="submit" name="send" class="h_link chat-send-btn" value="送信">
					</div>
					<span class="chat-file-name" id="chat_file_name"></span>
				</form>
<?php if ($errorMessage !== ''): ?>
				<p class="err"><?= $h($errorMessage) ?></p>
<?php endif; ?>
				<div id="chat-messages" data-poll-since="<?= $h($chatPollSince) ?>" data-login-admin-uuid="<?= $h($loginAdminUuid) ?>">
				<?php require __DIR__ . '/partials/message_list.php'; ?>
				</div>
				<div class="chat-history-area">
					<form action="chat.php" method="post">
						<input type="hidden" name="insert_date" value="<?= $h($chatData['date'] ?? '') ?>">
						<input type="submit" name="history" class="h_link btn-secondary" value="前日・以前のすべての情報を取得">
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
<script type="text/javascript" src="<?= $h($jsBase) ?>report.js?v=<?= $h($assetVer) ?>"></script>
<script type="text/javascript" src="<?= $h($jsBase) ?>chat.js?v=<?= $h($assetVer) ?>"></script>
<script>
function jigyodanChatEditSubmit(form) {
	var current = form.getAttribute('data-current-text') || '';
	var next = prompt('メッセージを修正', current);
	if (next === null) {
		return false;
	}
	next = next.trim();
	if (next === '') {
		return false;
	}
	form.querySelector('input[name="chat_text"]').value = next;
	return true;
}

document.addEventListener('DOMContentLoaded', function () {
	var fileInput = document.getElementById('chat_file');
	var fileNameLabel = document.getElementById('chat_file_name');
	if (fileInput && fileNameLabel) {
		fileInput.addEventListener('change', function () {
			fileNameLabel.textContent = fileInput.files && fileInput.files.length > 0
				? fileInput.files[0].name
				: '';
		});
	}

	var chatInput = document.getElementById('chat_text');
	var chatInputWrap = chatInput ? chatInput.parentNode : null;
	if (!chatInput || !chatInputWrap) {
		return;
	}

	function updateChatInputShape() {
		chatInputWrap.classList.toggle('chat-input-wrap--multiline', /[\r\n]/.test(chatInput.value));
	}

	chatInput.addEventListener('input', updateChatInputShape);
	updateChatInputShape();
});
</script>
</body>
</html>

