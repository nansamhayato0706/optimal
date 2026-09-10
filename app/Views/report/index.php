<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatTime = static function (?string $time): string {
	if ($time === null || $time === '' || strtotime('today ' . $time) === false) {
		return (string) $time;
	}

	return date('H:i', strtotime('today ' . $time));
};

$formatDateWithWeekday = static function (?string $date): string {
	if ($date === null || $date === '') {
		return (string) $date;
	}

	$timestamp = strtotime($date);
	if ($timestamp === false) {
		return $date;
	}

	$weekdays = ['日', '月', '火', '水', '木', '金', '土'];
	return date('Y-m-d', $timestamp) . '(' . $weekdays[(int) date('w', $timestamp)] . ')';
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
	<link rel="stylesheet" href="<?= $h($cssBase) ?>report.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>month-picker.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery-ui.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>datepicker-ja.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>month-picker.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>report.js?v=<?= $h($assetVer) ?>"></script>
</head>

<body>
	<div id="wrapper">
		<div id="header">
			<div id="header-inner">
				<div id="header-brand"><?= $h($loginAdminId) ?></div>
				<div id="h_link_area">
					<?php foreach ($headerLinks as $link): ?>
						<a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div id="main">
			<div class="page-card panel-stack">
				<div class="page-header report_index_header">
					<div class="report_index_title_row">
						<h3 class="page-title report_index_title"><?= $h($title) ?><?php if ($pageData['user_name'] !== ''): ?><span class="page-username"><?= $h($pageData['user_name']) ?></span><?php endif; ?></h3>
						<div class="report_index_title_actions">
							<?php if ($loginAuth > 0 && !empty($userOptions)): ?>
							<div class="report_detail_user_switch">
								<label for="report_index_user_select">利用者</label>
								<span class="mp-nav-wrap">
									<button type="button" class="mp-nav-btn mp-nav-prev" aria-label="前の利用者">&#8249;</button>
									<select id="report_index_user_select" onchange="if (this.value) { location.href = 'report.php?i=' + encodeURIComponent(this.value); }">
<?php foreach ($userOptions as $option): ?>
										<option value="<?= $h($option['user_uuid']) ?>"<?= $option['user_uuid'] === $currentUserUuid ? ' selected' : '' ?>><?= $h($option['user_name']) ?></option>
<?php endforeach; ?>
									</select>
									<button type="button" class="mp-nav-btn mp-nav-next" aria-label="次の利用者">&#8250;</button>
								</span>
							</div>
							<?php endif; ?>
							<?php if ($loginAuth === 1 || $loginAuth === 2): ?>
							<form action="report_edit.php" method="get" class="report_index_create_form">
								<input type="hidden" name="user_uuid" value="<?= $h($pageData['user_uuid']) ?>">
								<span class="report_index_create_label">日報追加</span>
								<span class="mp-nav-wrap">
									<button type="button" class="mp-nav-btn mp-nav-prev" aria-label="前日">&#8249;</button>
									<input type="text" name="report_date" autocomplete="off" value="<?= $h($pageData['report_date_new']) ?>" class="date report_index_create_date">
									<button type="button" class="mp-nav-btn mp-nav-next" aria-label="翌日">&#8250;</button>
								</span>
								<input type="submit" class="h_link report_index_button" value="追加">
							</form>
							<?php endif; ?>
						</div>
					</div>
					<div class="report_index_toolbar">
						<div class="report_index_action_group">
							<span class="report_index_action_label"><?= $loginAuth > 0 ? '表示期間・CSV出力' : '表示期間' ?></span>
							<form id="frm" action="report.php<?= $currentUserUuid !== '' && $loginAuth > 0 ? '?i=' . rawurlencode($currentUserUuid) : '' ?>" method="post" class="report_index_search_form">
								<span class="mp-nav-wrap">
									<button type="button" class="mp-nav-btn mp-nav-prev" aria-label="前月">&#8249;</button>
									<input type="text" class="month-picker" name="date_st" value="<?= $h($pageData['date_st']) ?>" placeholder="開始月" autocomplete="off">
									<button type="button" class="mp-nav-btn mp-nav-next" aria-label="翌月">&#8250;</button>
								</span>
								<span class="report_index_separator">～</span>
								<span class="mp-nav-wrap">
									<button type="button" class="mp-nav-btn mp-nav-prev" aria-label="前月">&#8249;</button>
									<input type="text" class="month-picker month-picker-end" name="date_ed" value="<?= $h($pageData['date_ed']) ?>" placeholder="終了月" autocomplete="off">
									<button type="button" class="mp-nav-btn mp-nav-next" aria-label="翌月">&#8250;</button>
								</span>
								<?php if ($loginAuth > 0): ?>
									<input type="submit" class="h_link report_index_button btn-secondary" name="report" value="CSV">
								<?php endif; ?>
							</form>
						</div>
						<?php if ($loginAuth > 0): ?>
							<div class="report_index_action_group report_index_action_group-pdf">
								<span class="report_index_action_label">PDF出力</span>
								<form action="report_pdf.php" method="get" target="_blank" class="report_index_create_form">
									<input type="hidden" name="user_uuid" value="<?= $h($pageData['user_uuid']) ?>">
									<input type="hidden" name="default_month" value="<?= $h(date('Y-m')) ?>">
									<span class="mp-nav-wrap">
										<button type="button" class="mp-nav-btn mp-nav-prev" aria-label="前月">&#8249;</button>
										<input type="text" class="month-picker" name="date_st" value="<?= $h($pageData['date_st']) ?>" placeholder="開始月" autocomplete="off">
										<button type="button" class="mp-nav-btn mp-nav-next" aria-label="翌月">&#8250;</button>
									</span>
									<span class="report_index_separator">～</span>
									<span class="mp-nav-wrap">
										<button type="button" class="mp-nav-btn mp-nav-prev" aria-label="前月">&#8249;</button>
										<input type="text" class="month-picker month-picker-end" name="date_ed" value="<?= $h($pageData['date_ed']) ?>" placeholder="終了月" autocomplete="off">
										<button type="button" class="mp-nav-btn mp-nav-next" aria-label="翌月">&#8250;</button>
									</span>
									<input type="submit" class="h_link report_index_button btn-secondary" value="PDF出力">
								</form>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="report-table-scroll">
					<table class="data-table report_list">
						<tr>
							<th class="report_nowrap text-nowrap">日付</th>
							<th class="report_nowrap text-nowrap">開始時間</th>
							<th class="report_nowrap text-nowrap">終了時間</th>
							<th>備考</th>
							<th>返信</th>
							<?php if ($loginAuth > 0): ?>
							<th>支援記録及び評価</th>
							<?php endif; ?>
							<th class="report_nowrap text-nowrap"></th>
						</tr>
						<?php foreach ($pageData['report'] as $report): ?>
							<tr>
								<td class="report_nowrap text-nowrap"><?= $h($formatDateWithWeekday($report['report_date'])) ?></td>
								<td class="report_nowrap text-nowrap"><?= $h($formatTime($report['training_start_time'])) ?></td>
								<td class="report_nowrap text-nowrap"><?= $h($formatTime($report['training_end_time'])) ?></td>
								<td class="report_align_top"><?= nl2br($h($report['remark'])) ?></td>
								<td class="report_align_top"><?= nl2br($h($report['reply'])) ?></td>
								<?php if ($loginAuth > 0): ?>
								<td class="report_align_top"><?= nl2br($h($report['charge_comment'])) ?></td>
								<?php endif; ?>
								<td class="report_nowrap text-nowrap"><a href="<?= !empty($report['admin_uuid']) ? 'report_detail.php' : 'report_edit.php' ?>?i=<?= $h($report['report_uuid']) ?>">確認</a></td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</div>
		</div>
		<div id="footer">
		</div>
	</div>
</body>

</html>
