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
					<span class="chat-drop-hint">ファイルをここにドラッグして添付できます（1件）</span>
					<span class="chat-file-error" id="chat_file_error" role="alert"></span>
					<div class="chat-upload-progress" id="chat_upload_progress" hidden>
						<progress id="chat_upload_bar" max="100" value="0" aria-label="ファイル送信の進捗"></progress>
						<span id="chat_upload_status" role="status">送信中 0%</span>
					</div>
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
	var fileError = document.getElementById('chat_file_error');
	var sendForm = document.querySelector('.chat-send-form');
	if (fileInput && fileNameLabel && fileError && sendForm) {
		var dragDepth = 0;
		var uploadProgress = document.getElementById('chat_upload_progress');
		var uploadBar = document.getElementById('chat_upload_bar');
		var uploadStatus = document.getElementById('chat_upload_status');
		var sendButton = sendForm.querySelector('input[name="send"]');
		var uploading = false;
		function hasDraggedFiles(event) {
			var types = event.dataTransfer && event.dataTransfer.types;
			return types && Array.prototype.indexOf.call(types, 'Files') !== -1;
		}
		function updateFileName() {
			fileNameLabel.textContent = fileInput.files && fileInput.files.length > 0
				? fileInput.files[0].name
				: '';
			fileError.textContent = '';
		}
		fileInput.addEventListener('change', function () {
			updateFileName();
		});
		sendForm.addEventListener('submit', function (event) {
			if (uploading) {
				event.preventDefault();
				return;
			}
			if (!fileInput.files || fileInput.files.length === 0 || !window.XMLHttpRequest || !window.FormData) {
				return;
			}
			event.preventDefault();
			var formData = new FormData(sendForm);
			var xhr = new XMLHttpRequest();
			uploading = true;
			sendButton.disabled = true;
			fileError.textContent = '';
			uploadProgress.hidden = false;
			uploadBar.value = 0;
			uploadStatus.textContent = '送信中 0%';
			xhr.open('POST', sendForm.getAttribute('action'));
			xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
			xhr.upload.addEventListener('progress', function (progressEvent) {
				if (progressEvent.lengthComputable) {
					var percent = Math.min(100, Math.round(progressEvent.loaded / progressEvent.total * 100));
					uploadBar.value = percent;
					uploadStatus.textContent = percent === 100 ? 'アップロード完了・処理中' : '送信中 ' + percent + '%';
				} else {
					uploadBar.removeAttribute('value');
					uploadStatus.textContent = '送信中';
				}
			});
			function finishWithError(message) {
				uploading = false;
				sendButton.disabled = false;
				uploadProgress.hidden = true;
				fileError.textContent = message;
			}
			xhr.addEventListener('load', function () {
				var result;
				try {
					result = JSON.parse(xhr.responseText);
				} catch (error) {
					finishWithError('送信結果を確認できませんでした。チャットを再表示して確認してください。');
					return;
				}
				if (xhr.status === 200 && result.success && result.redirect) {
					window.location.assign(result.redirect);
					return;
				}
				finishWithError(result.error || 'ファイルの送信に失敗しました。');
			});
			xhr.addEventListener('error', function () {
				finishWithError('通信が途切れました。チャットを再表示して送信結果を確認してください。');
			});
			try {
				xhr.send(formData);
			} catch (error) {
				finishWithError('送信を開始できませんでした。もう一度お試しください。');
			}
		});
		sendForm.addEventListener('dragenter', function (event) {
			if (!hasDraggedFiles(event)) {
				return;
			}
			event.preventDefault();
			dragDepth++;
			sendForm.classList.add('chat-send-form--dragover');
		});
		sendForm.addEventListener('dragover', function (event) {
			if (hasDraggedFiles(event)) {
				event.preventDefault();
				event.dataTransfer.dropEffect = 'copy';
			}
		});
		sendForm.addEventListener('dragleave', function (event) {
			if (!hasDraggedFiles(event)) {
				return;
			}
			dragDepth = Math.max(0, dragDepth - 1);
			if (dragDepth === 0) {
				sendForm.classList.remove('chat-send-form--dragover');
			}
		});
		sendForm.addEventListener('drop', function (event) {
			if (!hasDraggedFiles(event)) {
				return;
			}
			event.preventDefault();
			dragDepth = 0;
			sendForm.classList.remove('chat-send-form--dragover');
			var files = event.dataTransfer.files;
			if (files.length !== 1) {
				fileError.textContent = 'ファイルは1件ずつ添付してください。';
				return;
			}
			try {
				fileInput.files = files;
				if (!fileInput.files || fileInput.files.length !== 1) {
					throw new Error('File input did not accept the dropped file.');
				}
				updateFileName();
			} catch (error) {
				fileError.textContent = 'ドラッグで添付できませんでした。＋ボタンから選択してください。';
			}
		});
		document.addEventListener('dragover', function (event) {
			if (hasDraggedFiles(event)) {
				event.preventDefault();
			}
		});
		document.addEventListener('drop', function (event) {
			if (hasDraggedFiles(event)) {
				event.preventDefault();
				dragDepth = 0;
				sendForm.classList.remove('chat-send-form--dragover');
			}
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

