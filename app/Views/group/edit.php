<?php
$options = static function (array $items, $selected) use ($h): string {
    $html = '<option value="">選択してください</option>';
    foreach ($items as $id => $label) {
        $isSelected = (string) $id === (string) $selected ? ' selected' : '';
        $html .= '<option value="' . $h($id) . '"' . $isSelected . '>' . $h($label) . '</option>';
    }
    return $html;
};
?><!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css">
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main"><div class="page-card panel-stack"><div class="page-header"><h3 class="page-title"><?= $h($title) ?></h3></div>
	<form action="group_edit.php" method="post">
		<?= $_csrf_field ?>
		<input type="hidden" name="group_uuid" value="<?= $h($form['group_uuid']) ?>">
		<div class="frm_row">
			<div class="frm_parts_mm"><div class="frm_title">名前</div><div class="frm_input"><input type="text" name="group_name" value="<?= $h($form['group_name']) ?>"></div><p class="err"><?= $h($errors['group_name'] ?? '') ?></p></div>
			<div class="frm_parts_mm"><div class="frm_title">フリガナ</div><div class="frm_input"><input type="text" name="group_name_kana" value="<?= $h($form['group_name_kana']) ?>"></div><p class="err"><?= $h($errors['group_name_kana'] ?? '') ?></p></div>
		</div>
		<div class="frm_row">
			<div class="frm_parts_mm"><div class="frm_title">郵便番号</div><div class="frm_input"><input type="text" name="group_zip_code" value="<?= $h($form['group_zip_code']) ?>"></div><p class="err"><?= $h($errors['group_zip_code'] ?? '') ?></p></div>
			<div class="frm_parts_mm"><div class="frm_title">都道府県</div><div class="frm_input"><select name="group_prefecture_div"><?= $options($divMap['prefecture'] ?? [], $form['group_prefecture_div']) ?></select></div><p class="err"><?= $h($errors['group_prefecture_div'] ?? '') ?></p></div>
		</div>
		<div class="frm_row"><div class="frm_parts_xlm"><div class="frm_title">住所</div><div class="frm_input_xl"><input type="text" name="group_address" value="<?= $h($form['group_address']) ?>"></div><p class="err"><?= $h($errors['group_address'] ?? '') ?></p></div></div>
		<div class="frm_row">
			<div class="frm_parts_mm"><div class="frm_title">電話番号</div><div class="frm_input"><input type="tel" name="group_tel" value="<?= $h($form['group_tel']) ?>"></div><p class="err"><?= $h($errors['group_tel'] ?? '') ?></p></div>
			<div class="frm_parts_mm"><div class="frm_title">e-mail</div><div class="frm_input"><input type="email" name="group_email" value="<?= $h($form['group_email']) ?>"></div><p class="err"><?= $h($errors['group_email'] ?? '') ?></p></div>
		</div>
		<div class="frm_row"><div class="frm_parts_xlm"><div class="frm_title">備考</div><div class="frm_input_xl2"><input type="text" name="remark" value="<?= $h($form['remark']) ?>"></div><p class="err"><?= $h($errors['remark'] ?? '') ?></p></div></div>
		<div class="frm_row"><div id="frm_button"><input type="submit" name="act" value="登録"></div></div>
	</form>
	</div></div>
</div>
</body>
</html>
