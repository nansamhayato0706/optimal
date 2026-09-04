<?php

declare(strict_types=1);

$formatTime = static function (?string $time) use ($h): string {
	if ($time === null || $time === '' || strtotime('today ' . $time) === false) {
		return (string) $time;
	}

	return date('H:i', strtotime('today ' . $time));
};

$weekdays = ['日', '月', '火', '水', '木', '金', '土'];
$dateTimestamp = strtotime((string) $date);
$dateLabel = $dateTimestamp === false
	? (string) $date
	: date('Y-m-d', $dateTimestamp) . '(' . $weekdays[(int) date('w', $dateTimestamp)] . ')';
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
	<link rel="stylesheet" href="<?= $h($cssBase) ?>report.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery-ui.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>datepicker-ja.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>report_daily.js?v=<?= $h($assetVer) ?>"></script>
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main">
		<div class="page-card panel-stack">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?>：<?= $h($dateLabel) ?></h3>
				<form id="frm" action="report_daily.php" method="post" class="toolbar-form">
					<input type="text" class="date" name="date" value="<?= $h($date) ?>">
				</form>
			</div>
			<div class="report-table-scroll">
				<table class="data-table report_list">
					<tr>
						<th>利用者名</th>
						<th class="report_nowrap text-nowrap">開始時間</th>
						<th class="report_nowrap text-nowrap">終了時間</th>
						<th>備考</th>
						<th>支援記録及び評価</th>
						<th class="report_nowrap text-nowrap"></th>
					</tr>
<?php foreach ($rows as $row): ?>
					<tr>
						<td><?= $h($row['user_name']) ?></td>
						<td class="report_nowrap text-nowrap"><?= $h($formatTime($row['training_start_time'])) ?></td>
						<td class="report_nowrap text-nowrap"><?= $h($formatTime($row['training_end_time'])) ?></td>
						<td><?= nl2br($h($row['remark'] ?? '')) ?></td>
						<td><?= nl2br($h($row['charge_comment'] ?? '')) ?></td>
<?php if (empty($row['report_uuid'])): ?>
						<td class="report_nowrap text-nowrap"><a href="report_edit.php?user_uuid=<?= $h($row['user_uuid']) ?>&amp;report_date=<?= $h($date) ?>">未提出（登録）</a></td>
<?php elseif (empty($row['report_admin_uuid'])): ?>
						<td class="report_nowrap text-nowrap"><a href="report_edit.php?i=<?= $h($row['report_uuid']) ?>">編集</a></td>
<?php else: ?>
						<td class="report_nowrap text-nowrap"><a href="report_detail.php?i=<?= $h($row['report_uuid']) ?>">確認</a></td>
<?php endif; ?>
					</tr>
<?php endforeach; ?>
				</table>
			</div>
		</div>
	</div>
</div>
</body>
</html>
