<?php
$fieldId = static function (string $name): string {
	return 'admin_' . preg_replace('/[^a-zA-Z0-9_]+/', '_', $name);
};
$errorId = static function (string $name) use ($fieldId): string {
	return $fieldId($name) . '_error';
};
$describedBy = static function (string $name) use ($errors, $errorId): string {
	return ($errors[$name] ?? '') !== '' ? $errorId($name) : '';
};
$options = static function (array $map, $selected) use ($h): string {
	$html = '<option value="">選択してください</option>';
	foreach ($map as $id => $label) {
		$isSelected = (string) $id === (string) $selected ? ' selected' : '';
		$html .= '<option value="' . $h($id) . '"' . $isSelected . '>' . $h($label) . '</option>';
	}
	return $html;
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>admin.css">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>admin.js"></script>
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main"><div class="page-card panel-stack"><div class="page-header"><h3 class="page-title"><?= $h($title) ?></h3></div>
	<form action="admin_edit.php" method="post" class="admin_edit_form">
		<?= $_csrf_field ?>
		<input type="hidden" name="admin_uuid" value="<?= $h($form['admin_uuid']) ?>">
		<input type="hidden" name="group_uuid" value="<?= $h($form['group_uuid']) ?>">

		<div class="rf-form">
			<div class="rf-card">
				<div class="rf-title">アカウント情報</div>
				<div class="rf-body">
					<div class="rf-field rf-field-sm">
						<label class="rf-label" for="<?= $h($fieldId('admin_id')) ?>">管理者ID</label>
						<input id="<?= $h($fieldId('admin_id')) ?>" type="text" name="admin_id" value="<?= $h($form['admin_id']) ?>"<?= ($errors['admin_id'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('admin_id') !== '' ? ' aria-describedby="' . $h($describedBy('admin_id')) . '"' : '' ?>>
						<?php if (($errors['admin_id'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('admin_id')) ?>" role="alert"><?= $h($errors['admin_id']) ?></p><?php endif; ?>
					</div>
					<div class="rf-field rf-field-sm">
						<label class="rf-label" for="<?= $h($fieldId('admin_password')) ?>">パスワード</label>
						<input id="<?= $h($fieldId('admin_password')) ?>" type="text" name="admin_password" value="<?= $h($form['admin_password']) ?>"<?= ($errors['admin_password'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('admin_password') !== '' ? ' aria-describedby="' . $h($describedBy('admin_password')) . '"' : '' ?>>
						<?php if (($errors['admin_password'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('admin_password')) ?>" role="alert"><?= $h($errors['admin_password']) ?></p><?php endif; ?>
					</div>
					<div class="rf-field rf-field-md">
						<label class="rf-label" for="<?= $h($fieldId('admin_div')) ?>">管理者区分</label>
						<select id="<?= $h($fieldId('admin_div')) ?>" name="admin_div"<?= ($errors['admin_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('admin_div') !== '' ? ' aria-describedby="' . $h($describedBy('admin_div')) . '"' : '' ?>><?= $options($divMap['admin'] ?? [], $form['admin_div']) ?></select>
						<?php if (($errors['admin_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('admin_div')) ?>" role="alert"><?= $h($errors['admin_div']) ?></p><?php endif; ?>
					</div>
				</div>
			</div>

			<div class="rf-card">
				<div class="rf-title">個人情報</div>
				<div class="rf-body">
					<div class="rf-field rf-field-md">
						<label class="rf-label" for="<?= $h($fieldId('admin_name')) ?>">名前</label>
						<input id="<?= $h($fieldId('admin_name')) ?>" type="text" name="admin_name" value="<?= $h($form['admin_name']) ?>"<?= ($errors['admin_name'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('admin_name') !== '' ? ' aria-describedby="' . $h($describedBy('admin_name')) . '"' : '' ?>>
						<?php if (($errors['admin_name'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('admin_name')) ?>" role="alert"><?= $h($errors['admin_name']) ?></p><?php endif; ?>
					</div>
					<div class="rf-field rf-field-md">
						<label class="rf-label" for="<?= $h($fieldId('admin_name_kana')) ?>">フリガナ</label>
						<input id="<?= $h($fieldId('admin_name_kana')) ?>" type="text" name="admin_name_kana" value="<?= $h($form['admin_name_kana']) ?>"<?= ($errors['admin_name_kana'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('admin_name_kana') !== '' ? ' aria-describedby="' . $h($describedBy('admin_name_kana')) . '"' : '' ?>>
						<?php if (($errors['admin_name_kana'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('admin_name_kana')) ?>" role="alert"><?= $h($errors['admin_name_kana']) ?></p><?php endif; ?>
					</div>
				</div>
			</div>

			<div class="rf-card">
				<div class="rf-title">連絡先</div>
				<div class="rf-body">
					<div class="rf-field rf-field-sm">
						<label class="rf-label" for="<?= $h($fieldId('admin_tel')) ?>">電話番号</label>
						<input id="<?= $h($fieldId('admin_tel')) ?>" type="tel" name="admin_tel" value="<?= $h($form['admin_tel']) ?>"<?= ($errors['admin_tel'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('admin_tel') !== '' ? ' aria-describedby="' . $h($describedBy('admin_tel')) . '"' : '' ?>>
						<?php if (($errors['admin_tel'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('admin_tel')) ?>" role="alert"><?= $h($errors['admin_tel']) ?></p><?php endif; ?>
					</div>
					<div class="rf-field rf-field-md">
						<label class="rf-label" for="<?= $h($fieldId('admin_email')) ?>">e-mail</label>
						<input id="<?= $h($fieldId('admin_email')) ?>" type="email" name="admin_email" value="<?= $h($form['admin_email']) ?>"<?= ($errors['admin_email'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('admin_email') !== '' ? ' aria-describedby="' . $h($describedBy('admin_email')) . '"' : '' ?>>
						<?php if (($errors['admin_email'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('admin_email')) ?>" role="alert"><?= $h($errors['admin_email']) ?></p><?php endif; ?>
					</div>
				</div>
			</div>

			<div class="rf-card">
				<div class="rf-title">備考</div>
				<div class="rf-body">
					<div class="rf-field rf-field-xl">
						<label class="rf-label" for="<?= $h($fieldId('remark')) ?>">備考</label>
						<input id="<?= $h($fieldId('remark')) ?>" type="text" name="remark" value="<?= $h($form['remark']) ?>">
					</div>
				</div>
			</div>
		</div>

		<div class="rf-actions"><input type="submit" name="act" class="h_link" value="登録"></div>
	</form>
	</div></div>
</div>
</body>
</html>
