<?php

declare(strict_types=1);

use App\Support\ContactImageUrl;
use App\Support\AppConfig;

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$divName = static function (string $parent, $id) use ($divMap): string {
	return (string) ($divMap[$parent][(int) $id] ?? '');
};
$formatDateTime = static function (?string $value, string $format): string {
	if ($value === null || $value === '') {
		return '';
	}
	$ts = strtotime($value);
	return $ts === false ? (string) $value : date($format, $ts);
};
$imageUrl = static function (array $contact): string {
	return ContactImageUrl::resolve($contact, AppConfig::fromGlobals());
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?= $h($imgBase) ?>favicon.ico" type="image/x-icon">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>jquery-ui.min.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>datepicker-custom.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>log.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery-ui.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>datepicker-ja.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>log.js?v=<?= $h($assetVer) ?>"></script>
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main">
		<div class="page-card panel-stack">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?><?php if ($userName !== ''): ?>：<?= $h($userName) ?><?php endif; ?></h3>
				<form id="frm" action="log.php" method="post" class="toolbar-form">
					<input type="text" class="date log_date" name="date_st" value="<?= $h($dateSt) ?>">
					<span class="toolbar-separator">～</span>
					<input type="text" class="date log_date" name="date_ed" value="<?= $h($dateEd) ?>">
					<input type="submit" class="h_link" name="contact" value="連絡CSV">
					<input type="submit" class="h_link" name="log" value="ログCSV">
				</form>
			</div>
			<table>
				<tr>
					<th>日時</th>
					<th width="340">写真</th>
					<th>対応状態</th>
					<th width="400">コメント</th>
				</tr>
<?php foreach ($contacts as $contact): ?>
				<tr>
					<td><?= $h($contact['contact_date'] ?? '') ?><br>【<?= $h($divName('contact', $contact['contact_div'] ?? 0)) ?>】</td>
					<td><img width="320" style="display: block;" src="<?= $h($imageUrl($contact)) ?>" onerror="this.onerror=null;this.src='<?= $h($dummyImage) ?>';"></td>
					<td><?= $h($contact['confirm_date'] ?? '') ?><br>【<?= $h($divName('confirm', $contact['confirm_div'] ?? 0)) ?>】</td>
					<td><?= nl2br($h($contact['comment'] ?? ''), false) ?></td>
				</tr>
<?php endforeach; ?>
			</table>
			<h4>ログリスト</h4>
			<table>
				<tr>
					<th>日時</th>
					<th width="200">種別</th>
					<th width="200">状態</th>
				</tr>
<?php foreach ($sends as $send): ?>
				<tr>
					<td><?= $h($send['send_date'] ?? '') ?></td>
					<td><?= $h($divName('send', $send['send_div'] ?? 0)) ?></td>
					<td><?= $h($divName('hook', $send['hook_div'] ?? 0)) ?></td>
				</tr>
<?php endforeach; ?>
			</table>
		</div>
	</div>
</div>
</body>
</html>
