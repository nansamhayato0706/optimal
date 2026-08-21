<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$pickedLabels = static function (array $map, array $values): array {
	return \App\Support\ReportViewHelpers::pickedLabels($map, $values);
};
$pickedLabel = static function (array $map, $value): string {
	return \App\Support\ReportViewHelpers::pickedLabel($map, $value);
};
$sectionId = static function (string $name): string {
	return \App\Support\ReportViewHelpers::sectionId('detail_accessible_', $name);
};
$sectionLabel = static function (string $key): string {
	return \App\Support\ReportAccessibleLabels::section($key);
};
$fieldLabel = static function (string $key): string {
	return \App\Support\ReportAccessibleLabels::field($key);
};
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
	<link rel="stylesheet" href="<?= $h($cssBase) ?>report.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>report_confirm.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js?v=<?= $h($assetVer) ?>"></script>
</head>
<body>
	<a class="skip-link" href="#main-content">本文へ移動</a>
	<div id="wrapper">
		<header id="header" role="banner">
			<div id="header-inner">
				<h1 id="header-brand"><?= $h($loginAdminId) ?></h1>
				<nav id="h_link_area" aria-label="管理メニュー">
					<?php foreach ($headerLinks as $link): ?>
						<a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a>
					<?php endforeach; ?>
				</nav>
			</div>
		</header>
		<main id="main" role="main">
			<div class="accessible-page-header">
				<h3><?= $h($title) ?></h3>
				<?php if ($loginAuth > 0): ?>
					<a class="h_link" href="report_pdf.php?i=<?= $h($detail['report_uuid']) ?>" target="_blank" tabindex="-1">PDF出力</a>
				<?php endif; ?>
			</div>
			<div class="accessible-intro" aria-live="polite">
				<p>この画面は確認専用です。項目を上から順番に読むと、日報全体を把握しやすくなります。</p>
			</div>

			<div id="main-content" class="report_edit_form accessible-form" tabindex="0">
				<section class="accessible-card" tabindex="0" aria-labelledby="<?= $h($sectionId('basic')) ?>"><h4 class="accessible-card-title" id="<?= $h($sectionId('basic')) ?>"><?= $h($sectionLabel('basic')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('user_name')) ?></span><?= $h($detail['user_name']) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('report_date')) ?></span><?= $h($detail['report_date']) ?></div></div></section>

				<section class="accessible-card" tabindex="0" aria-labelledby="<?= $h($sectionId('sleep')) ?>"><h4 class="accessible-card-title" id="<?= $h($sectionId('sleep')) ?>"><?= $h($sectionLabel('sleep')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('retiring_time')) ?></span><?= $h($detail['retiring_time']) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('rising_time')) ?></span><?= $h($detail['rising_time']) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('sleep_time')) ?></span><?= $h($detail['sleep_time']) ?></div></div></section>

				<section class="accessible-card" tabindex="0" aria-labelledby="<?= $h($sectionId('state')) ?>"><h4 class="accessible-card-title" id="<?= $h($sectionId('state')) ?>"><?= $h($sectionLabel('state')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('mood_div')) ?></span><?= $h($pickedLabel($divMap['mood'] ?? array(), $detail['mood_div'] ?? 0)) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('condition_div')) ?></span><?= $h($pickedLabel($divMap['condition'] ?? array(), $detail['condition_div'] ?? 0)) ?></div></div></section>

				<section class="accessible-card" tabindex="0" aria-labelledby="<?= $h($sectionId('objective')) ?>"><h4 class="accessible-card-title" id="<?= $h($sectionId('objective')) ?>"><?= $h($sectionLabel('objective')) ?></h4><div class="accessible-summary-list"><?php foreach ($pickedLabels($divMap['objective'] ?? array(), $detail['objective_div'] ?? array()) as $label): ?><div class="accessible-summary-item"><?= $h($label) ?></div><?php endforeach; ?><?php if (($detail['objective_div'] ?? array()) === array()): ?><div class="accessible-summary-item"><?= $h($fieldLabel('none')) ?></div><?php endif; ?></div></section>

				<section class="accessible-card" tabindex="0" aria-labelledby="<?= $h($sectionId('medicine')) ?>"><h4 class="accessible-card-title" id="<?= $h($sectionId('medicine')) ?>"><?= $h($sectionLabel('medicine')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('medicine_div')) ?></span><?= $h($pickedLabel($divMap['medicine'] ?? array(), $detail['medicine_div'] ?? 0)) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('medicine_reason_detail')) ?></span><?= $h($detail['medicine_reason']) ?></div></div></section>

				<section class="accessible-card" tabindex="0" aria-labelledby="<?= $h($sectionId('outing')) ?>"><h4 class="accessible-card-title" id="<?= $h($sectionId('outing')) ?>"><?= $h($sectionLabel('outing')) ?></h4><div class="accessible-summary-list"><?php foreach ($pickedLabels($divMap['outing'] ?? array(), $detail['outing_div'] ?? array()) as $label): ?><div class="accessible-summary-item"><?= $h($label) ?></div><?php endforeach; ?><?php if (($detail['outing_div'] ?? array()) === array()): ?><div class="accessible-summary-item"><?= $h($fieldLabel('none')) ?></div><?php endif; ?></div></section>

				<section class="accessible-card" tabindex="0" aria-labelledby="<?= $h($sectionId('talk')) ?>"><h4 class="accessible-card-title" id="<?= $h($sectionId('talk')) ?>"><?= $h($sectionLabel('talk')) ?></h4><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('talk_div_detail')) ?></span><?= $h($pickedLabel($divMap['talk'] ?? array(), $detail['talk_div'] ?? 0)) ?></div></section>

<?php
				$reportData = $detail;
				$showReadonlyAdminComment = $loginAuth > 0;
				$renderMultiline = true;
				require __DIR__ . '/partials/accessible_readonly_training_section.php';
				require __DIR__ . '/partials/accessible_readonly_review_section.php';
				require __DIR__ . '/partials/accessible_readonly_question_section.php';
?>
			</div>
		</main>
		<footer id="footer"></footer>
	</div>
</body>
</html>

