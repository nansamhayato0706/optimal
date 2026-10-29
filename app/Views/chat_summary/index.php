<?php

declare(strict_types=1);
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
	<link rel="stylesheet" href="<?= $h($cssBase) ?>month-picker.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>chat_summary.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>month-picker.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>chat_summary.js?v=<?= $h($assetVer) ?>"></script>
</head>
<body>
<div id="wrapper">
	<?php require dirname(__DIR__) . '/partials/header.php'; ?>
	<div id="main">
		<div class="page-card panel-stack chat-summary-page">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?>：<?= $h($monthLabel) ?></h3>
				<form id="frm" action="chat_summary.php" method="get" class="toolbar-form">
					<span class="mp-nav-wrap">
						<button type="button" class="mp-nav-btn mp-nav-prev" aria-label="前月">&#8249;</button>
						<input type="text" class="month-picker" name="month" value="<?= $h($month) ?>" autocomplete="off">
						<button type="button" class="mp-nav-btn mp-nav-next" aria-label="翌月">&#8250;</button>
					</span>
				</form>
			</div>
			<p class="chat-summary-legend">
				<span class="cs-badge cs-badge--send">送</span> ＝ 送信（管理者→就労者）、
				<span class="cs-badge cs-badge--recv">受</span> ＝ 受信（就労者→管理者）
				<span class="cs-legend-warn">赤</span> 勤務した日で、やり取りが<?= $h($minExchanges - 1) ?>回以下
			</p>
<?php if ($rows === []): ?>
			<p class="chat-summary-empty">表示できる利用者がいません。</p>
<?php else: ?>
			<div class="chat-summary-scroll">
				<table class="data-table table-compact chat-summary-table">
					<thead>
						<tr>
							<th rowspan="2" class="cs-user-col">ユーザー</th>
<?php foreach ($days as $day): ?>
							<th class="cs-day<?= $day['dow'] === 6 ? ' is-sat' : '' ?><?= $day['dow'] === 0 ? ' is-sun' : '' ?><?= $day['is_today'] ? ' is-today' : '' ?>"><?= $h($day['day']) ?></th>
<?php endforeach; ?>
							<th rowspan="2" class="cs-total-col">合計</th>
						</tr>
						<tr>
<?php foreach ($days as $day): ?>
							<th class="cs-weekday<?= $day['dow'] === 6 ? ' is-sat' : '' ?><?= $day['dow'] === 0 ? ' is-sun' : '' ?><?= $day['is_today'] ? ' is-today' : '' ?>"><?= $h($day['weekday']) ?></th>
<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
<?php foreach ($rows as $row): ?>
						<tr>
							<td class="cs-user-col" title="<?= $h($row['user_name']) ?>"><a href="chat.php?i=<?= $h($row['user_uuid']) ?>"><?= $h($row['user_name']) ?></a></td>
<?php foreach ($row['cells'] as $i => $cell): ?>
<?php $dow = $days[$i]['dow']; ?>
<?php $hasCell = $cell['worked'] || $cell['send'] > 0 || $cell['recv'] > 0; ?>
							<td class="cs-cell<?= $cell['state'] === 'warn' ? ' is-warn' : '' ?><?= ($dow === 0 || $dow === 6) ? ' is-holiday' : '' ?>"<?= $hasCell ? ' title="' . $h($days[$i]['day']) . '日 送信' . $h($cell['send']) . '回・受信' . $h($cell['recv']) . '回"' : '' ?>>
<?php if ($hasCell): ?>
								<span class="cs-badge cs-badge--send<?= $cell['send'] === 0 ? ' is-zero' : '' ?>">送<?= $h($cell['send']) ?></span>
								<span class="cs-badge cs-badge--recv<?= $cell['recv'] === 0 ? ' is-zero' : '' ?>">受<?= $h($cell['recv']) ?></span>
<?php endif; ?>
							</td>
<?php endforeach; ?>
							<td class="cs-total-col" title="送信<?= $h($row['total_send']) ?>回・受信<?= $h($row['total_recv']) ?>回">
								<span class="cs-badge cs-badge--send">送<?= $h($row['total_send']) ?></span>
								<span class="cs-badge cs-badge--recv">受<?= $h($row['total_recv']) ?></span>
							</td>
						</tr>
<?php endforeach; ?>
					</tbody>
				</table>
			</div>
<?php endif; ?>
		</div>
	</div>
</div>
</body>
</html>
