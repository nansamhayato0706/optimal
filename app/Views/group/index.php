<!DOCTYPE html>
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
<?php if ($groups !== []): ?>
	<table class="data-table">
		<tr><th>名前</th><th>住所</th><th>e-mail</th><th>電話番号</th><th></th><th></th></tr>
<?php foreach ($groups as $group): ?>
		<tr>
			<td><?= $h($group['group_name']) ?></td>
			<td><?= $h($group['group_address']) ?></td>
			<td><?= $h($group['group_email']) ?></td>
			<td><?= $h($group['group_tel']) ?></td>
			<td><a href="group_edit.php?i=<?= $h($group['group_uuid']) ?>">変更</a></td>
			<td><a href="admin.php?i=<?= $h($group['group_uuid']) ?>">管理者リスト</a></td>
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
