<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$readonly = $mode !== 'edit';
$commentOnly = $mode === 'comment';
$locked = $mode === 'lock';
$showAdminComment = $loginAuth > 0;
$idPrefix = 'report_';
$formatTime = static function (?string $time): string {
	return (string) $time;
};
// Share the same visible labels across edit, confirm, and detail screens.
$fieldLabel = static function (string $key): string {
	return \App\Support\ReportAccessibleLabels::field($key);
};
$fieldId = static function (string $name) use ($idPrefix): string {
	return \App\Support\ReportViewIds::fieldId($idPrefix, $name);
};
$errorId = static function (string $name) use ($idPrefix): string {
	return \App\Support\ReportViewIds::errorId($idPrefix, $name);
};
$helpId = static function (string $name) use ($idPrefix): string {
	return \App\Support\ReportViewIds::helpId($idPrefix, $name);
};
$describedBy = static function (string $name, bool $hasHelp = false) use ($errors, $idPrefix): string {
	return \App\Support\ReportViewIds::describedBy($errors, $idPrefix, $name, $hasHelp);
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
				<h3><?= $h($title) ?></h3>
				<form action="report_edit.php" method="post" class="report_edit_form">
					<?= csrf_field() ?>
					<input type="hidden" name="report_uuid" value="<?= $h($form['report_uuid']) ?>">
					<input type="hidden" name="user_uuid" value="<?= $h($form['user_uuid']) ?>">
					<input type="hidden" name="admin_uuid" value="<?= $h($form['admin_uuid']) ?>">
					<input type="hidden" name="report_date" value="<?= $h($form['report_date']) ?>">

					<div class="rf-form">
						<div class="rf-card"><div class="rf-body rf-body--basic-info">
							<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('user_name')) ?></span><div class="rf-value"><?= $h($form['user_name']) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('report_date')) ?></span><div class="rf-value"><?= $h($form['report_date']) ?></div><?php if (($errors['report_date'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('report_date')) ?>" role="alert"><?= $h($errors['report_date']) ?></p><?php endif; ?></div>
						</div></div>

						<div class="rf-card"><div class="rf-title">睡眠</div><div class="rf-body">
							<div class="rf-field rf-field-sm"><label class="rf-label" for="<?= $h($fieldId('retiring_time')) ?>"><?= $h($fieldLabel('retiring_time')) ?></label><input id="<?= $h($fieldId('retiring_time')) ?>" type="time" name="retiring_time" value="<?= $h($formatTime($form['retiring_time'])) ?>"<?= $readonly ? ' readonly' : '' ?><?= ($errors['retiring_time'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('retiring_time') !== '' ? ' aria-describedby="' . $h($describedBy('retiring_time')) . '"' : '' ?>><?php if (($errors['retiring_time'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('retiring_time')) ?>" role="alert"><?= $h($errors['retiring_time']) ?></p><?php endif; ?></div>
							<div class="rf-field rf-field-sm"><label class="rf-label" for="<?= $h($fieldId('rising_time')) ?>"><?= $h($fieldLabel('rising_time')) ?></label><input id="<?= $h($fieldId('rising_time')) ?>" type="time" name="rising_time" value="<?= $h($formatTime($form['rising_time'])) ?>"<?= $readonly ? ' readonly' : '' ?><?= ($errors['rising_time'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('rising_time') !== '' ? ' aria-describedby="' . $h($describedBy('rising_time')) . '"' : '' ?>><?php if (($errors['rising_time'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('rising_time')) ?>" role="alert"><?= $h($errors['rising_time']) ?></p><?php endif; ?></div>
							<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('sleep_time')) ?></span><div class="rf-value"><?= $h($form['sleep_time']) ?></div></div>
						</div></div>

						<div class="rf-card"><div class="rf-title">該当する内容を選んでください。</div><div class="rf-body">
							<div class="rf-field rf-field-md"><label class="rf-label" for="<?= $h($fieldId('mood_div')) ?>"><?= $h($fieldLabel('mood_div')) ?></label><select id="<?= $h($fieldId('mood_div')) ?>" name="mood_div"<?= $readonly ? ' disabled' : '' ?><?= ($errors['mood_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('mood_div') !== '' ? ' aria-describedby="' . $h($describedBy('mood_div')) . '"' : '' ?>><option value="">選択してください</option><?php foreach (($divMap['mood'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['mood_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select><?php if (($errors['mood_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('mood_div')) ?>" role="alert"><?= $h($errors['mood_div']) ?></p><?php endif; ?></div>
							<div class="rf-field rf-field-md"><label class="rf-label" for="<?= $h($fieldId('condition_div')) ?>"><?= $h($fieldLabel('condition_div')) ?></label><select id="<?= $h($fieldId('condition_div')) ?>" name="condition_div"<?= $readonly ? ' disabled' : '' ?><?= ($errors['condition_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('condition_div') !== '' ? ' aria-describedby="' . $h($describedBy('condition_div')) . '"' : '' ?>><option value="">選択してください</option><?php foreach (($divMap['condition'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['condition_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select><?php if (($errors['condition_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('condition_div')) ?>" role="alert"><?= $h($errors['condition_div']) ?></p><?php endif; ?></div>
						</div></div>

						<div class="rf-card"><fieldset class="rf-check-body"><legend class="rf-title">今日の目標</legend><?php foreach (($divMap['objective'] ?? array()) as $id => $label): ?><label class="rf-check-item" for="<?= $h($fieldId('objective_div_' . $id)) ?>"><input id="<?= $h($fieldId('objective_div_' . $id)) ?>" type="checkbox" name="objective_div[]" value="<?= $h($id) ?>"<?= in_array((int) $id, $form['objective_div'], true) ? ' checked' : '' ?><?= $readonly ? ' disabled' : '' ?>><?= $h($label) ?></label><?php endforeach; ?></fieldset></div>

						<div class="rf-card"><div class="rf-title">服薬</div><div class="rf-body">
							<div class="rf-field rf-field-sm"><label class="rf-label" for="<?= $h($fieldId('medicine_div')) ?>"><?= $h($fieldLabel('medicine_div')) ?></label><select id="<?= $h($fieldId('medicine_div')) ?>" name="medicine_div"<?= $readonly ? ' disabled' : '' ?><?= ($errors['medicine_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('medicine_div') !== '' ? ' aria-describedby="' . $h($describedBy('medicine_div')) . '"' : '' ?>><option value="">選択してください</option><?php foreach (($divMap['medicine'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['medicine_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select><?php if (($errors['medicine_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('medicine_div')) ?>" role="alert"><?= $h($errors['medicine_div']) ?></p><?php endif; ?></div>
							<div class="rf-field rf-field-lg"><label class="rf-label" for="<?= $h($fieldId('medicine_reason')) ?>"><?= $h($fieldLabel('medicine_reason')) ?></label><input id="<?= $h($fieldId('medicine_reason')) ?>" type="text" name="medicine_reason" value="<?= $h($form['medicine_reason']) ?>"<?= $readonly ? ' readonly' : '' ?><?= ($errors['medicine_reason'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('medicine_reason') !== '' ? ' aria-describedby="' . $h($describedBy('medicine_reason')) . '"' : '' ?>><?php if (($errors['medicine_reason'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('medicine_reason')) ?>" role="alert"><?= $h($errors['medicine_reason']) ?></p><?php endif; ?></div>
						</div></div>

						<div class="rf-card"><fieldset class="rf-check-body"><legend class="rf-title">外出</legend><?php foreach (($divMap['outing'] ?? array()) as $id => $label): ?><label class="rf-check-item" for="<?= $h($fieldId('outing_div_' . $id)) ?>"><input id="<?= $h($fieldId('outing_div_' . $id)) ?>" type="checkbox" name="outing_div[]" value="<?= $h($id) ?>"<?= in_array((int) $id, $form['outing_div'], true) ? ' checked' : '' ?><?= $readonly ? ' disabled' : '' ?>><?= $h($label) ?></label><?php endforeach; ?></fieldset></div>

						<div class="rf-card"><div class="rf-title">会話人数</div><div class="rf-body"><div class="rf-field rf-field-sm"><label class="rf-label" for="<?= $h($fieldId('talk_div')) ?>"><?= $h($fieldLabel('talk_div')) ?></label><select id="<?= $h($fieldId('talk_div')) ?>" name="talk_div"<?= $readonly ? ' disabled' : '' ?><?= ($errors['talk_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('talk_div') !== '' ? ' aria-describedby="' . $h($describedBy('talk_div')) . '"' : '' ?>><option value="">選択してください</option><?php foreach (($divMap['talk'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['talk_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select><?php if (($errors['talk_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('talk_div')) ?>" role="alert"><?= $h($errors['talk_div']) ?></p><?php endif; ?></div></div></div>

<?php
						require __DIR__ . '/partials/edit_training_card.php';
						require __DIR__ . '/partials/edit_review_card.php';
						require __DIR__ . '/partials/edit_question_card.php';
?>
					</div>

<?php if (!$locked): ?>
					<div class="rf-actions" id="frm_button">
						<input type="submit" name="act" value="確認" class="h_link">
					</div>
<?php endif; ?>
				</form>
			</div>
			<div id="footer"></div>
		</div>
	</body>
</html>

