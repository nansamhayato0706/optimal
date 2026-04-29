<?php

declare(strict_types=1);

/** @var array<string, string> $errors */

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$readonly = $mode !== 'edit';
$commentOnly = $mode === 'comment';
$locked = $mode === 'lock';
$showAdminComment = $loginAuth > 0;
$idPrefix = 'report_accessible_';
$fieldId = static function (string $name) use ($idPrefix): string {
	return \App\Support\ReportViewIds::fieldId($idPrefix, $name);
};
$errorId = static function (string $name) use ($idPrefix): string {
	return \App\Support\ReportViewIds::errorId($idPrefix, $name);
};
$helpId = static function (string $name) use ($idPrefix): string {
	return \App\Support\ReportViewIds::helpId($idPrefix, $name);
};
$inputAttr = static function (string $name, bool $readonly, bool $invalid, string $describedBy, string $extra = '') use ($h): string {
	$attr = $readonly ? ' readonly' : '';
	if ($invalid) {
		$attr .= ' aria-invalid="true"';
	}
	if ($describedBy !== '') {
		$attr .= ' aria-describedby="' . $h($describedBy) . '"';
	}
	if ($extra !== '') {
		$attr .= ' ' . trim($extra);
	}
	return $attr;
};
$describedBy = static function (string $name, bool $hasHelp = false) use ($errors, $idPrefix): string {
	return \App\Support\ReportViewIds::describedBy($errors, $idPrefix, $name, $hasHelp);
};
$checkedInt = static function (array $values, $id): bool {
	return in_array((int) $id, $values, true);
};
$errorSummary = array_filter($errors, static function ($value, $key): bool {
	return $key !== 'general';
}, ARRAY_FILTER_USE_BOTH);
$radioGroup = static function (string $name, array $map, $selected, bool $disabled, $fieldId, $h): string {
	$listClass = $disabled ? 'accessible-choice-list accessible-choice-list--readonly' : 'accessible-choice-list';
	$html = '<div class="' . $listClass . '">';
	foreach ($map as $id => $label) {
		$idAttr = $fieldId($name . '_' . $id);
		$checked = (string) $id === (string) $selected;
		$labelClass = 'accessible-choice' . ($checked ? ' accessible-choice--checked' : '');
		$html .= '<label class="' . $labelClass . '" for="' . $h($idAttr) . '">';
		$html .= '<input id="' . $h($idAttr) . '" type="radio" name="' . $h($name) . '" value="' . $h($id) . '"';
		$html .= ($checked ? ' checked' : '');
		$html .= ($disabled ? ' aria-disabled="true" tabindex="-1"' : '') . '>';
		$html .= '<span class="accessible-choice-text">' . $h($label) . '</span></label>';
	}
	$html .= '</div>';
	return $html;
};
$checkboxGroup = static function (string $name, array $map, array $selected, bool $disabled, $fieldId, $h, $checkedInt): string {
	$listClass = $disabled ? 'accessible-choice-list accessible-choice-list--readonly' : 'accessible-choice-list';
	$html = '<div class="' . $listClass . '">';
	foreach ($map as $id => $label) {
		$idAttr = $fieldId($name . '_' . $id);
		$checked = $checkedInt($selected, $id);
		$labelClass = 'accessible-choice' . ($checked ? ' accessible-choice--checked' : '');
		$html .= '<label class="' . $labelClass . '" for="' . $h($idAttr) . '">';
		$html .= '<input id="' . $h($idAttr) . '" type="checkbox" name="' . $h($name) . '[]" value="' . $h($id) . '"';
		$html .= ($checked ? ' checked' : '');
		$html .= ($disabled ? ' aria-disabled="true" tabindex="-1"' : '') . '>';
		$html .= '<span class="accessible-choice-text">' . $h($label) . '</span></label>';
	}
	$html .= '</div>';
	return $html;
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>jquery-ui.min.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>datepicker-custom.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>report.css" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery-ui.min.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>datepicker-ja.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>report.js"></script>
</head>
<body>
	<a class="skip-link" href="#main-content">本文へ移動</a>
	<div id="wrapper" data-report-accessible="1">
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
			</div>
			<div class="accessible-intro" aria-live="polite">
				<p>この画面は読み上げを前提に、上から順番に確認しやすい並びにしています。</p>
				<p>選択肢は縦に並べています。項目名、補足説明、入力欄の順で読み上げられます。</p>
				<p>時間は 24 時間表記で「06:30」のように入力してください。数値項目は半角数字で入力してください。</p>
				<?php if ($locked): ?><p>この日報は確定済みです。内容の確認のみできます。</p><?php endif; ?>
			</div>
			<?php if ($errorSummary !== array()): ?>
				<div class="accessible-intro" id="report-accessible-error-summary" role="alert" tabindex="-1">
					<p><strong>入力内容を確認してください。</strong></p>
					<?php foreach ($errorSummary as $key => $message): ?>
						<?php $label = $errorLabels[$key] ?? $key; ?>
						<p><?= $h($label . '：' . $message) ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<form id="main-content" action="report_edit.php" method="post" class="report_edit_form accessible-form" tabindex="-1">
				<?= csrf_field() ?>
				<input type="hidden" name="report_uuid" value="<?= $h($form['report_uuid']) ?>">
				<input type="hidden" name="user_uuid" value="<?= $h($form['user_uuid']) ?>">
				<input type="hidden" name="admin_uuid" value="<?= $h($form['admin_uuid']) ?>">
				<input type="hidden" name="report_date" value="<?= $h($form['report_date']) ?>">
				<section class="accessible-card"><h4 class="accessible-card-title">1. 基本情報</h4><div class="accessible-stack"><div class="accessible-field"><span class="rf-label">氏名</span><div class="accessible-value accessible-value-strong"><?= $h($form['user_name']) ?></div></div><div class="accessible-field"><span class="rf-label">日付</span><div class="accessible-value"><?= $h($form['report_date']) ?></div><?php if (($errors['report_date'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('report_date')) ?>" role="alert"><?= $h($errors['report_date']) ?></p><?php endif; ?></div></div></section>
				<section class="accessible-card"><h4 class="accessible-card-title">2. 睡眠</h4><div class="accessible-stack"><div class="accessible-field"><label class="rf-label" for="<?= $h($fieldId('retiring_time')) ?>">昨日の就寝時間</label><p class="accessible-help" id="<?= $h($helpId('retiring_time')) ?>">24時間表記で入力してください。例 22:30</p><input id="<?= $h($fieldId('retiring_time')) ?>" type="time" name="retiring_time" value="<?= $h($form['retiring_time']) ?>"<?= $inputAttr('retiring_time', $readonly, ($errors['retiring_time'] ?? '') !== '', $describedBy('retiring_time', true)) ?>><?php if (($errors['retiring_time'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('retiring_time')) ?>" role="alert"><?= $h($errors['retiring_time']) ?></p><?php endif; ?></div><div class="accessible-field"><label class="rf-label" for="<?= $h($fieldId('rising_time')) ?>">今日の起床時間</label><p class="accessible-help" id="<?= $h($helpId('rising_time')) ?>">24時間表記で入力してください。例 06:30</p><input id="<?= $h($fieldId('rising_time')) ?>" type="time" name="rising_time" value="<?= $h($form['rising_time']) ?>"<?= $inputAttr('rising_time', $readonly, ($errors['rising_time'] ?? '') !== '', $describedBy('rising_time', true)) ?>><?php if (($errors['rising_time'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('rising_time')) ?>" role="alert"><?= $h($errors['rising_time']) ?></p><?php endif; ?></div><div class="accessible-field"><span class="rf-label">睡眠時間</span><div class="accessible-value"><?= $h($form['sleep_time']) ?></div></div></div></section>
				<section class="accessible-card"><h4 class="accessible-card-title">3. 今日の状態</h4><div class="accessible-stack"><fieldset class="accessible-choice-group"><legend class="accessible-group-legend">今日の気分</legend><?= $radioGroup('mood_div', $divMap['mood'] ?? array(), $form['mood_div'], $readonly, $fieldId, $h) ?><?php if (($errors['mood_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('mood_div')) ?>" role="alert"><?= $h($errors['mood_div']) ?></p><?php endif; ?></fieldset><fieldset class="accessible-choice-group"><legend class="accessible-group-legend">今日の体調</legend><?= $radioGroup('condition_div', $divMap['condition'] ?? array(), $form['condition_div'], $readonly, $fieldId, $h) ?><?php if (($errors['condition_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('condition_div')) ?>" role="alert"><?= $h($errors['condition_div']) ?></p><?php endif; ?></fieldset></div></section>
				<section class="accessible-card"><h4 class="accessible-card-title">4. 今日の目標</h4><fieldset class="accessible-choice-group"><legend class="accessible-group-legend">当てはまる項目にチェックしてください。</legend><?= $checkboxGroup('objective_div', $divMap['objective'] ?? array(), $form['objective_div'], $readonly, $fieldId, $h, $checkedInt) ?></fieldset></section>
				<section class="accessible-card"><h4 class="accessible-card-title">5. 服薬</h4><div class="accessible-stack"><fieldset class="accessible-choice-group"><legend class="accessible-group-legend">決まった通りに飲みましたか？</legend><?= $radioGroup('medicine_div', $divMap['medicine'] ?? array(), $form['medicine_div'], $readonly, $fieldId, $h) ?><?php if (($errors['medicine_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('medicine_div')) ?>" role="alert"><?= $h($errors['medicine_div']) ?></p><?php endif; ?></fieldset><div class="accessible-field"><label class="rf-label" for="<?= $h($fieldId('medicine_reason')) ?>">服薬の理由</label><p class="accessible-help" id="<?= $h($helpId('medicine_reason')) ?>">必要な場合のみ入力してください。</p><input id="<?= $h($fieldId('medicine_reason')) ?>" type="text" name="medicine_reason" value="<?= $h($form['medicine_reason']) ?>" <?= $readonly ? ' readonly' : '' ?><?= ($errors['medicine_reason'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('medicine_reason', true) !== '' ? ' aria-describedby="' . $h($describedBy('medicine_reason', true)) . '"' : '' ?>><?php if (($errors['medicine_reason'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('medicine_reason')) ?>" role="alert"><?= $h($errors['medicine_reason']) ?></p><?php endif; ?></div></div></section>
				<section class="accessible-card"><h4 class="accessible-card-title">6. 外出</h4><fieldset class="accessible-choice-group"><legend class="accessible-group-legend">当てはまる項目にチェックしてください。</legend><?= $checkboxGroup('outing_div', $divMap['outing'] ?? array(), $form['outing_div'], $readonly, $fieldId, $h, $checkedInt) ?></fieldset></section>
				<section class="accessible-card"><h4 class="accessible-card-title">7. 会話人数</h4><fieldset class="accessible-choice-group"><legend class="accessible-group-legend">今日会話した人数を選んでください。</legend><?= $radioGroup('talk_div', $divMap['talk'] ?? array(), $form['talk_div'], $readonly, $fieldId, $h) ?><?php if (($errors['talk_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('talk_div')) ?>" role="alert"><?= $h($errors['talk_div']) ?></p><?php endif; ?></fieldset></section>
<?php
				require __DIR__ . '/partials/accessible_edit_training_section.php';
				require __DIR__ . '/partials/accessible_edit_review_section.php';
				require __DIR__ . '/partials/accessible_edit_question_section.php';
?>
				<?php if (!$locked): ?><div class="accessible-action-bar" id="frm_button"><input type="submit" name="act" value="確認画面へ進む" class="h_link"></div><?php endif; ?>
			</form>
		</main>
		<footer id="footer"></footer>
	</div>
</body>
</html>

