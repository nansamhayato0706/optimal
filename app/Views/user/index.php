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
<body class="user-index-page">
<div id="wrapper"
	data-status-url="user_status.php"
	data-contact-detail-url="contact_detail.php"
	data-contact-update-url="contact_update.php"
	data-dummy-image="<?= $h($dummyImage) ?>">
	<div class="user-list-header-shell">
		<?php $headerExtraPartial = __DIR__ . '/partials/settings_toggle.php'; ?>
		<?php require dirname(__DIR__) . '/partials/header.php'; ?>
		<div class="user-list-settings-panel" id="user-list-settings-panel" aria-hidden="true">
		<form id="frm" action="user.php" method="post" class="user-list-settings-form">
			<?= csrf_field() ?>
			<div class="page-toolbar">
				<label class="toolbar-label" for="delete_flg">表示条件</label>
				<select id="delete_flg" name="delete_flg" onchange="document.getElementById('frm').submit()">
						<option value="">選択してください</option>
<?php foreach (($divMap['delete_flg'] ?? array()) as $id => $label): ?>
						<option value="<?= $h($id) ?>"<?= (string) $id === $deleteFlag ? ' selected' : '' ?>><?= $h($label) ?></option>
<?php endforeach; ?>
				</select>
<?php if ($statusSummaryRefreshEnabled): ?>
				<input type="submit" class="h_link" name="status_summary_refresh" value="サマリ再反映（テスト用）" onclick="return confirm('利用中ユーザーのステータスサマリを再反映します。実行しますか？');">
<?php endif; ?>
				<button type="button" class="h_link" id="notify-toggle" hidden>通知を有効にする</button>
			</div>
		</form>
		</div>
	</div>
	<div id="main">
		<div class="user-mobile-filters" aria-label="利用者の絞り込み">
			<button type="button" class="is-active" data-mobile-filter="all">すべて</button>
			<button type="button" data-mobile-filter="urgent">緊急 <span data-mobile-filter-count="urgent">0</span></button>
			<button type="button" data-mobile-filter="inquiry">問合せ <span data-mobile-filter-count="inquiry">0</span></button>
			<button type="button" data-mobile-filter="unconfirmed">未確認 <span data-mobile-filter-count="unconfirmed">0</span></button>
			<button type="button" data-mobile-filter="report">日報 <span data-mobile-filter-count="report">0</span></button>
			<button type="button" data-mobile-filter="chat">チャット <span data-mobile-filter-count="chat">0</span></button>
		</div>

<?php if ($statusSummaryRefreshCount !== ''): ?>
<?php require __DIR__ . '/partials/summary_cards.php'; ?>
<?php endif; ?>

		<div class="page-card panel-stack">
			<?php if ($users === []): ?>
				<p class="user-list-empty">選択中の管理者には、現在の表示条件に一致する利用者がいません。</p>
			<?php else: ?>
			<div class="user-list-scroll">
				<table class="data-table table-compact user_list">
					<tr><th>No</th><th>ユーザーID</th><th class="col-work-style">区分</th><th>名前</th><th class="col-sex">性別</th><th class="col-age">年齢</th><th>利用状況</th><th>日報</th><th>チャット</th><th>ログ</th><th>設定</th></tr>
<?php require __DIR__ . '/partials/user_rows.php'; ?>
				</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php require __DIR__ . '/partials/contact_modal.php'; ?>
</body>
</html>
