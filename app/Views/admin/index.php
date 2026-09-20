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
	<?php require dirname(__DIR__) . '/partials/header.php'; ?>
	<div id="main"><div class="page-card panel-stack"><div class="page-header"><div><h3 class="page-title"><?= $h($title) ?></h3><?php if ($admins !== []): ?><p class="admin-count"><?= count($admins) ?>名</p><?php endif; ?></div><a class="btn admin-create-link" href="admin_edit.php">＋ 管理者登録</a></div>
<?php if ($admins !== []): ?>
	<table class="data-table admin-list-table">
		<thead><tr><th scope="col">管理者ID</th><th scope="col">名前</th><th scope="col">e-mail</th><th scope="col">電話番号</th><th scope="col">変更</th><th scope="col">管理画面</th></tr></thead>
		<tbody>
<?php foreach ($admins as $admin): ?>
		<tr>
			<td data-label="管理者ID"><?= $h($admin['admin_id']) ?></td>
			<td data-label="名前"><?= $h($admin['admin_name']) ?></td>
			<td data-label="e-mail"><?= $h($admin['admin_email']) ?></td>
			<td data-label="電話番号"><?= $h($admin['admin_tel']) ?></td>
			<td data-label="詳細・編集"><a class="btn btn-secondary" href="admin_edit.php?i=<?= $h($admin['admin_uuid']) ?>">詳細・編集</a></td>
			<td data-label="就労者一覧"><a class="btn" href="user.php?i=<?= $h($admin['admin_uuid']) ?>">就労者一覧</a></td>
		</tr>
<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	<h3>データが存在しません。</h3>
<?php endif; ?>
	</div></div>
</body>
</html>
