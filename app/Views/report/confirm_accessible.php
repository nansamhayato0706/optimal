<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$showAdminComment = $loginAuth > 0;
$pickedLabels = static function (array $map, array $values): array {
	return \App\Support\ReportViewHelpers::pickedLabels($map, $values);
};
$pickedLabel = static function (array $map, $value): string {
	return \App\Support\ReportViewHelpers::pickedLabel($map, $value);
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
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>report.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>report_confirm.css" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js"></script>
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
			<div class="confirm-warning-banner" role="status" aria-live="polite">入力は完了していません。内容を上から順番に確認し、最後に登録してください。</div>
			<div class="accessible-page-header">
				<h3><?= $h($title) ?></h3>
			</div>
			<div class="accessible-intro" aria-live="polite">
				<p>この画面は確認専用です。上から順番に読み上げで確認し、問題がなければ最後に登録してください。</p>
				<p>修正する場合は、最後の「入力画面に戻る」を選んでください。</p>
			</div>

			<form id="main-content" action="report_complete.php" method="post" class="report_edit_form accessible-form" tabindex="-1">
				<?= csrf_field() ?>
				<section class="accessible-card"><h4 class="accessible-card-title"><?= $h($sectionLabel('basic')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('user_name')) ?></span><?= $h($form['user_name']) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('report_date')) ?></span><?= $h($form['report_date']) ?></div></div></section>

				<section class="accessible-card"><h4 class="accessible-card-title"><?= $h($sectionLabel('sleep')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('retiring_time')) ?></span><?= $h($form['retiring_time']) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('rising_time')) ?></span><?= $h($form['rising_time']) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('sleep_time')) ?></span><?= $h($form['sleep_time']) ?></div></div></section>

				<section class="accessible-card"><h4 class="accessible-card-title"><?= $h($sectionLabel('state')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('mood_div')) ?></span><?= $h($pickedLabel($divMap['mood'] ?? array(), $form['mood_div'] ?? 0)) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('condition_div')) ?></span><?= $h($pickedLabel($divMap['condition'] ?? array(), $form['condition_div'] ?? 0)) ?></div></div></section>

				<section class="accessible-card"><h4 class="accessible-card-title"><?= $h($sectionLabel('objective')) ?></h4><div class="accessible-summary-list"><?php foreach ($pickedLabels($divMap['objective'] ?? array(), $form['objective_div'] ?? array()) as $label): ?><div class="accessible-summary-item"><?= $h($label) ?></div><?php endforeach; ?><?php if (($form['objective_div'] ?? array()) === array()): ?><div class="accessible-summary-item"><?= $h($fieldLabel('none')) ?></div><?php endif; ?></div></section>

				<section class="accessible-card"><h4 class="accessible-card-title"><?= $h($sectionLabel('medicine')) ?></h4><div class="accessible-summary-list"><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('medicine_div')) ?></span><?= $h($pickedLabel($divMap['medicine'] ?? array(), $form['medicine_div'] ?? 0)) ?></div><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('medicine_reason')) ?></span><?= $h($form['medicine_reason']) ?></div></div></section>

				<section class="accessible-card"><h4 class="accessible-card-title"><?= $h($sectionLabel('outing')) ?></h4><div class="accessible-summary-list"><?php foreach ($pickedLabels($divMap['outing'] ?? array(), $form['outing_div'] ?? array()) as $label): ?><div class="accessible-summary-item"><?= $h($label) ?></div><?php endforeach; ?><?php if (($form['outing_div'] ?? array()) === array()): ?><div class="accessible-summary-item"><?= $h($fieldLabel('none')) ?></div><?php endif; ?></div></section>

				<section class="accessible-card"><h4 class="accessible-card-title"><?= $h($sectionLabel('talk')) ?></h4><div class="accessible-summary-item"><span class="accessible-summary-label"><?= $h($fieldLabel('talk_div')) ?></span><?= $h($pickedLabel($divMap['talk'] ?? array(), $form['talk_div'] ?? 0)) ?></div></section>

<?php
				$reportData = $form;
				$showReadonlyAdminComment = $showAdminComment;
				$renderMultiline = false;
				require __DIR__ . '/partials/accessible_readonly_training_section.php';
				require __DIR__ . '/partials/accessible_readonly_review_section.php';
				require __DIR__ . '/partials/accessible_readonly_question_section.php';
?>

				<div class="accessible-action-bar" id="frm_button"><a class="h_link btn-secondary" href="report_edit.php">入力画面に戻る</a><input type="submit" class="h_link" name="act" value="この内容で登録"></div>
			</form>
		</main>
		<footer id="footer"></footer>
	</div>
</body>
</html>

