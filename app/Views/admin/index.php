<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?= $h($imgBase) ?>favicon.ico" type="image/x-icon">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css?v=<?= $h($assetVer) ?>">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css?v=<?= $h($assetVer) ?>">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css?v=<?= $h($assetVer) ?>">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>admin.css?v=<?= $h($assetVer) ?>">
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main"><div class="page-card panel-stack"><div class="page-header"><h3 class="page-title"><?= $h($title) ?></h3></div>
<?php if ($admins !== []): ?>
	<table class="data-table">
		<tr><th>管理者ID</th><th>名前</th><th>e-mail</th><th>電話番号</th><th>変更</th><th>管理画面</th></tr>
<?php foreach ($admins as $admin): ?>
		<tr>
			<td><?= $h($admin['admin_id']) ?></td>
			<td><?= $h($admin['admin_name']) ?></td>
			<td><?= $h($admin['admin_email']) ?></td>
			<td><?= $h($admin['admin_tel']) ?></td>
			<td><a href="admin_edit.php?i=<?= $h($admin['admin_uuid']) ?>">詳細・編集</a></td>
			<td><a href="user.php?i=<?= $h($admin['admin_uuid']) ?>">就労者一覧</a></td>
		</tr>
<?php endforeach; ?>
	</table>
<?php else: ?>
	<h3>データが存在しません。</h3>
<?php endif; ?>
	</div></div>
</div>
</body>
</html>
