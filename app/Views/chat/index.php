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
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main">
		<div class="page-card panel-stack">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?><?php if (($chatData['user_name'] ?? '') !== ''): ?>：<?= $h($chatData['user_name']) ?><?php endif; ?></h3>
			</div>
			<div id="chat">
				<form class="chat-send-form" action="chat_send.php" method="post">
					<?= csrf_field() ?>
					<input type="hidden" name="insert_date" value="<?= $h($chatData['date'] ?? '') ?>">
					<input type="text" name="chat_text" id="chat_text" placeholder="メッセージを入力...">
					<input type="submit" name="send" class="h_link" value="送信">
				</form>
<?php if ($errorMessage !== ''): ?>
				<p class="err"><?= $h($errorMessage) ?></p>
<?php endif; ?>
<?php require __DIR__ . '/partials/message_list.php'; ?>
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
</body>
</html>

