<?php
$divName = static function (string $parent, $id) use ($divMap): string {
    return (string) ($divMap[$parent][(int) $id] ?? '');
};
?><!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?= $h($imgBase) ?>favicon.ico" type="image/x-icon">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css?v=<?= $h($assetVer) ?>">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css?v=<?= $h($assetVer) ?>">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css?v=<?= $h($assetVer) ?>">
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main"><div class="page-card panel-stack"><div class="page-header"><h3 class="page-title"><?= $h($title) ?></h3></div>
	<div class="frm_row">
		<div class="frm_parts_mm"><div class="frm_title">名前</div><div class="frm_input"><input type="text" value="<?= $h($form['group_name']) ?>" readonly></div></div>
		<div class="frm_parts_mm"><div class="frm_title">フリガナ</div><div class="frm_input"><input type="text" value="<?= $h($form['group_name_kana']) ?>" readonly></div></div>
	</div>
	<div class="frm_row">
		<div class="frm_parts_mm"><div class="frm_title">郵便番号</div><div class="frm_input"><input type="text" value="<?= $h($form['group_zip_code']) ?>" readonly></div></div>
		<div class="frm_parts_mm"><div class="frm_title">都道府県</div><div class="frm_input"><input type="text" value="<?= $h($divName('prefecture', $form['group_prefecture_div'])) ?>" readonly></div></div>
	</div>
	<div class="frm_row"><div class="frm_parts_xlm"><div class="frm_title">住所</div><div class="frm_input_xl"><input type="text" value="<?= $h($form['group_address']) ?>" readonly></div></div></div>
	<div class="frm_row">
		<div class="frm_parts_mm"><div class="frm_title">電話番号</div><div class="frm_input"><input type="text" value="<?= $h($form['group_tel']) ?>" readonly></div></div>
		<div class="frm_parts_mm"><div class="frm_title">e-mail</div><div class="frm_input"><input type="text" value="<?= $h($form['group_email']) ?>" readonly></div></div>
	</div>
	<div class="frm_row"><div class="frm_parts_xlm"><div class="frm_title">Slack Webhook URL</div><div class="frm_input_xl"><input type="text" value="<?= $h($form['notify_slack_webhook_url']) ?>" readonly></div></div></div>
	<div class="frm_row"><div class="frm_parts_xlm"><div class="frm_title">備考</div><div class="frm_input_xl2"><input type="text" value="<?= $h($form['remark']) ?>" readonly></div></div></div>
	<div class="frm_row"><div id="frm_button"><a class="h_link btn-secondary" href="group_edit.php">戻る</a><a class="h_link" href="group_complete.php">登録</a></div></div>
	</div></div>
</div>
</body>
</html>
