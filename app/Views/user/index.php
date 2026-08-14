<?php

declare(strict_types=1);

use App\Support\Esc;
use App\Support\UserViewHelpers;

// Reuse the shared escaper so the page stays aligned with the rest of the app views.
$h = array(Esc::class, 'h');
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
	<link rel="stylesheet" href="<?= $h($cssBase) ?>user.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>user_index.js?v=<?= $h($assetVer) ?>"></script>
</head>
<body>
<div id="wrapper"
	data-status-url="user_status.php"
	data-contact-detail-url="contact_detail.php"
	data-contact-update-url="contact_update.php"
	data-dummy-image="<?= $h($dummyImage) ?>">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main">
		<form id="frm" action="user.php" method="post" class="toolbar-form">
			<?= csrf_field() ?>
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?>（<?= $h(date('Y-m-d H:i:s')) ?> 現在）</h3>
				<div class="page-toolbar">
					<span class="toolbar-label">表示条件</span>
					<select name="delete_flg" onchange="document.getElementById('frm').submit()">
						<option value="">選択してください</option>
<?php foreach (($divMap['delete_flg'] ?? array()) as $id => $label): ?>
						<option value="<?= $h($id) ?>"<?= (string) $id === $deleteFlag ? ' selected' : '' ?>><?= $h($label) ?></option>
<?php endforeach; ?>
					</select>
					<input type="submit" class="h_link" name="renew" value="更新">
<?php if ($statusSummaryRefreshEnabled): ?>
					<input type="submit" class="h_link" name="status_summary_refresh" value="サマリ再反映（テスト用）" onclick="return confirm('利用中ユーザーのステータスサマリを再反映します。実行しますか？');">
<?php endif; ?>
					<button type="button" class="h_link" id="notify-toggle" hidden>通知を有効にする</button>
				</div>
			</div>
		</form>

<?php if ($statusSummaryRefreshCount !== ''): ?>
<?php require __DIR__ . '/partials/summary_cards.php'; ?>
<?php endif; ?>

		<div class="page-card panel-stack">
			<div style="overflow-x:auto;">
				<table class="data-table table-compact user_list">
					<tr><th>No</th><th>ユーザーID</th><th>区分</th><th>名前</th><th class="col-sex">性別</th><th class="col-age">年齢</th><th>利用状況</th><th>日報</th><th>チャット</th><th>ログ</th><th>設定</th></tr>
<?php require __DIR__ . '/partials/user_rows.php'; ?>
				</table>
			</div>
		</div>
	</div>
</div>
<?php require __DIR__ . '/partials/contact_modal.php'; ?>
</body>
</html>
