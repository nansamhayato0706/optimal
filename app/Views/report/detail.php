<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$divName = static function (string $parent, $id) use ($divMap): string {
	return (string) ($divMap[$parent][(int) $id] ?? '');
};
$pickedLabels = static function (array $map, array $values): array {
	return \App\Support\ReportViewHelpers::pickedLabels($map, $values);
};
$formatTime = static function (?string $time): string {
	if ($time === null || $time === '' || strtotime('today ' . $time) === false) {
		return (string) $time;
	}

	return date('H:i', strtotime('today ' . $time));
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
			<div id="main" class="report_lock_view">
				<div class="report_detail_header">
					<h3 class="report_detail_title">
						<?= $h($title) ?>
<?php if ($loginAuth > 0): ?>
						<a class="h_link" href="report_pdf.php?i=<?= $h($detail['report_uuid']) ?>" target="_blank">PDF出力</a>
<?php endif; ?>
					</h3>
				</div>

				<div class="rf-form">
					<div class="rf-card">
						<div class="rf-body rf-body--basic-info">
							<div class="rf-field rf-field-lg">
								<span class="rf-label">氏名</span>
								<div class="rf-value"><?= $h($detail['user_name']) ?></div>
							</div>
							<div class="rf-field rf-field-sm">
								<span class="rf-label">日付</span>
								<div class="rf-value"><?= $h($detail['report_date']) ?></div>
							</div>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">睡眠</div>
						<div class="rf-body">
							<div class="rf-field rf-field-sm"><span class="rf-label">昨日の就寝時間</span><div class="rf-value"><?= $h($formatTime($detail['retiring_time'])) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">今日の起床時間</span><div class="rf-value"><?= $h($formatTime($detail['rising_time'])) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">睡眠時間</span><div class="rf-value"><?= $h($detail['sleep_time']) ?></div></div>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">該当する内容を選んでください。</div>
						<div class="rf-body">
							<div class="rf-field rf-field-md"><span class="rf-label">今日の気分</span><div class="rf-value"><?= $h($divName('mood', $detail['mood_div'])) ?></div></div>
							<div class="rf-field rf-field-md"><span class="rf-label">今日の体調</span><div class="rf-value"><?= $h($divName('condition', $detail['condition_div'])) ?></div></div>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">今日の目標</div>
						<div class="rf-check-body">
<?php $objectiveLabels = $pickedLabels($divMap['objective'] ?? array(), $detail['objective_div'] ?? array()); ?>
<?php if ($objectiveLabels === array()): ?>
							<div class="rf-value">選択なし</div>
<?php else: ?>
<?php foreach ($objectiveLabels as $label): ?>
							<div class="rf-check-item"><?= $h($label) ?></div>
<?php endforeach; ?>
<?php endif; ?>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">服薬</div>
						<div class="rf-body">
							<div class="rf-field rf-field-sm"><span class="rf-label">決まった通りに飲みましたか？</span><div class="rf-value"><?= $h($divName('medicine', $detail['medicine_div'])) ?></div></div>
							<div class="rf-field rf-field-lg"><span class="rf-label">服薬の理由</span><div class="rf-value"><?= $h($detail['medicine_reason']) ?></div></div>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">外出</div>
						<div class="rf-check-body">
<?php $outingLabels = $pickedLabels($divMap['outing'] ?? array(), $detail['outing_div'] ?? array()); ?>
<?php if ($outingLabels === array()): ?>
							<div class="rf-value">選択なし</div>
<?php else: ?>
<?php foreach ($outingLabels as $label): ?>
							<div class="rf-check-item"><?= $h($label) ?></div>
<?php endforeach; ?>
<?php endif; ?>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">会話人数</div>
						<div class="rf-body">
							<div class="rf-field rf-field-sm"><span class="rf-label">会話人数</span><div class="rf-value"><?= $h($divName('talk', $detail['talk_div'])) ?></div></div>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">訓練状況</div>
						<div class="rf-body rf-body--compact">
							<div class="rf-row-3">
								<div class="rf-field rf-field-lg"><span class="rf-label">訓練内容 午前1</span><div class="rf-value"><?= $h($detail['training_am_1']) ?></div></div>
								<div class="rf-field rf-field-lg"><span class="rf-label">訓練内容 午前2</span><div class="rf-value"><?= $h($detail['training_am_2']) ?></div></div>
								<div class="rf-field rf-field-lg"><span class="rf-label">訓練内容 午前3</span><div class="rf-value"><?= $h($detail['training_am_3']) ?></div></div>
							</div>
							<div class="rf-row-3">
								<div class="rf-field rf-field-lg"><span class="rf-label">訓練内容 午後1</span><div class="rf-value"><?= $h($detail['training_pm_1']) ?></div></div>
								<div class="rf-field rf-field-lg"><span class="rf-label">訓練内容 午後2</span><div class="rf-value"><?= $h($detail['training_pm_2']) ?></div></div>
								<div class="rf-field rf-field-lg"><span class="rf-label">訓練内容 午後3</span><div class="rf-value"><?= $h($detail['training_pm_3']) ?></div></div>
							</div>
							<div class="rf-break"></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">訓練 開始時間</span><div class="rf-value"><?= $h($formatTime($detail['training_start_time'])) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">訓練 終了時間</span><div class="rf-value"><?= $h($formatTime($detail['training_end_time'])) ?></div></div>
							<div class="rf-field rf-field-xs"><span class="rf-label">合計</span><div class="rf-value"><?= $h($detail['training_time']) ?></div></div>
							<div class="rf-break"></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">昼休憩(分)</span><div class="rf-value"><?= $h($detail['lunch_time']) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">途中休憩(分)</span><div class="rf-value"><?= $h($detail['break_time']) ?></div></div>
							<div class="rf-field rf-field-xs"><span class="rf-label">合計(分)</span><div class="rf-value"><?= $h($detail['lunch_break_time']) ?>分</div></div>
							<div class="rf-field rf-field-xs"><span class="rf-label">実働</span><div class="rf-value"><?= $h($detail['work_time']) ?></div></div>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">振り返り</div>
						<div class="rf-body">
							<div class="rf-field rf-field-xl"><span class="rf-label">午前の振り返り</span><div class="rf-value"><?= $h($detail['rethink_am']) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">午前 達成度(％)</span><div class="rf-value"><?= $h($detail['achieve_am']) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">午前 疲労度(％)</span><div class="rf-value"><?= $h($detail['fatigue_am']) ?></div></div>
							<div class="rf-field rf-field-xl"><span class="rf-label">午後の振り返り</span><div class="rf-value"><?= $h($detail['rethink_pm']) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">午後 達成度(％)</span><div class="rf-value"><?= $h($detail['achieve_pm']) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label">午後 疲労度(％)</span><div class="rf-value"><?= $h($detail['fatigue_pm']) ?></div></div>
						</div>
					</div>

					<div class="rf-card">
						<div class="rf-title">疑問や質問</div>
						<div class="rf-body-split">
							<div class="rf-split-col"><span class="rf-label">備考欄</span><div class="rf-value rf-value--multiline" aria-label="備考欄"><?= $h($detail['remark']) ?></div></div>
							<div class="rf-split-col"><span class="rf-label">返信</span><div class="rf-value rf-value--multiline" aria-label="返信"><?= $h($detail['reply']) ?></div></div>
<?php if ($loginAuth > 0): ?>
							<div class="rf-split-col"><span class="rf-label">支援記録及び評価</span><div class="rf-value rf-value--multiline" aria-label="支援記録及び評価"><?= $h($detail['charge_comment']) ?></div></div>
<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
			<div id="footer"></div>
		</div>
	</body>
</html>

