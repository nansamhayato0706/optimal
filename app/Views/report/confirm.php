<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$showAdminComment = $loginAuth > 0;
$formatTime = static function (?string $time): string {
	return (string) $time;
};
// Reuse the same wording dictionary across normal and accessible confirm screens.
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
		<script>
		$(function(){
			$(document).on('keydown', '.report_edit_form', function(e){
				if(e.key === 'Enter' && e.target.tagName !== 'TEXTAREA'){
					e.preventDefault();
				}
			});

			var $consent = $('#report_consent_flg');
			if ($consent.length) {
				var $err = $('#report_consent_flg_error');
				$('.report_edit_form').on('submit', function(e){
					if (!$consent.prop('checked')) {
						e.preventDefault();
						$err.show();
						$consent.attr('aria-invalid', 'true').focus();
					}
				});
				$consent.on('change', function () {
					if ($consent.prop('checked')) {
						$err.hide();
						$consent.removeAttr('aria-invalid');
					}
				});
			}
		});
		</script>
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
				<div class="confirm-warning-banner">入力は完了していません。確認して問題なければ下の登録ボタンを押してください。</div>
				<h3><?= $h($title) ?></h3>
				<form action="report_complete.php" method="post" class="report_edit_form">
					<?= csrf_field() ?>
					<div class="rf-form">
						<div class="rf-card"><div class="rf-body rf-body--basic-info">
							<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('user_name')) ?></span><div class="rf-value"><?= $h($form['user_name']) ?></div></div>
							<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('report_date')) ?></span><div class="rf-value"><?= $h($form['report_date']) ?></div></div>
						</div></div>

						<div class="rf-card"><div class="rf-title">睡眠</div><div class="rf-body">
							<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('retiring_time')) ?></span><input type="time" name="retiring_time" value="<?= $h($formatTime($form['retiring_time'])) ?>" readonly></div>
							<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('rising_time')) ?></span><input type="time" name="rising_time" value="<?= $h($formatTime($form['rising_time'])) ?>" readonly></div>
							<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('sleep_time')) ?></span><div class="rf-value"><?= $h($form['sleep_time'] ?? '') ?></div></div>
						</div></div>

						<div class="rf-card"><div class="rf-title">該当する内容を選んでください。</div><div class="rf-body">
							<div class="rf-field rf-field-md"><span class="rf-label"><?= $h($fieldLabel('mood_div')) ?></span><select name="mood_div" disabled><option value="">選択してください</option><?php foreach (($divMap['mood'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['mood_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
							<div class="rf-field rf-field-md"><span class="rf-label"><?= $h($fieldLabel('condition_div')) ?></span><select name="condition_div" disabled><option value="">選択してください</option><?php foreach (($divMap['condition'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['condition_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
						</div></div>

						<div class="rf-card"><div class="rf-title">今日の目標</div><div class="rf-check-body"><?php foreach (($divMap['objective'] ?? array()) as $id => $label): ?><label class="rf-check-item"><input type="checkbox" name="objective_div[]" value="<?= $h($id) ?>"<?= in_array((int) $id, $form['objective_div'], true) ? ' checked' : '' ?> disabled><?= $h($label) ?></label><?php endforeach; ?></div></div>

						<div class="rf-card"><div class="rf-title">服薬</div><div class="rf-body">
							<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('medicine_div')) ?></span><select name="medicine_div" disabled><option value="">選択してください</option><?php foreach (($divMap['medicine'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['medicine_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div>
							<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('medicine_reason')) ?></span><input type="text" name="medicine_reason" value="<?= $h($form['medicine_reason']) ?>" readonly></div>
						</div></div>

						<div class="rf-card"><div class="rf-title">外出</div><div class="rf-check-body"><?php foreach (($divMap['outing'] ?? array()) as $id => $label): ?><label class="rf-check-item"><input type="checkbox" name="outing_div[]" value="<?= $h($id) ?>"<?= in_array((int) $id, $form['outing_div'], true) ? ' checked' : '' ?> disabled><?= $h($label) ?></label><?php endforeach; ?></div></div>

						<div class="rf-card"><div class="rf-title">会話人数</div><div class="rf-body"><div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('talk_div')) ?></span><select name="talk_div" disabled><option value="">選択してください</option><?php foreach (($divMap['talk'] ?? array()) as $id => $label): ?><option value="<?= $h($id) ?>"<?= (string) $id === (string) $form['talk_div'] ? ' selected' : '' ?>><?= $h($label) ?></option><?php endforeach; ?></select></div></div></div>

<?php
						$reportData = $form;
						$showReadonlyAdminComment = $showAdminComment;
						require __DIR__ . '/partials/readonly_training_card.php';
						require __DIR__ . '/partials/readonly_review_card.php';
						require __DIR__ . '/partials/readonly_question_card.php';
?>
					</div>

					<?php if ($loginAuth === 0): ?>
					<div class="rf-card rf-consent">
						<label class="rf-check-item" for="report_consent_flg">
							<input id="report_consent_flg" type="checkbox" name="consent_flg" value="1"<?= ($errors['consent_flg'] ?? '') !== '' ? ' aria-invalid="true" aria-describedby="report_consent_flg_error"' : '' ?>>
							<?= $h($fieldLabel('consent_text')) ?>
						</label>
						<p class="err" id="report_consent_flg_error" role="alert"<?= ($errors['consent_flg'] ?? '') === '' ? ' style="display:none"' : '' ?>><?= $h($errors['consent_flg'] ?? '本日の利用について確認し、チェックを入れてください。') ?></p>
					</div>
					<?php endif; ?>

					<div class="rf-actions" id="frm_button">
						<a class="h_link btn-secondary" href="report_edit.php"><?= $h($fieldLabel('back')) ?></a>
						<input type="submit" class="h_link" name="act" value="<?= $h($fieldLabel('register')) ?>">
					</div>
				</form>
			</div>
			<div id="footer"></div>
		</div>
	</body>
</html>

