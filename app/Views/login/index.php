<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?= $h($imgBase) ?>favicon.ico" type="image/x-icon">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>jquery-ui.min.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>datepicker-custom.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>login.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
</head>
<body>
	<div id="wrapper">
		<div id="login-page">
			<div class="login-card">
				<h2 class="login-title">在宅就労管理システム</h2>
				<p class="login-subtitle">管理者ログイン</p>
<?php if (($form['error'] ?? '') !== ''): ?>
				<p class="err"><?= $h($form['error']) ?></p>
<?php endif; ?>
				<form action="login.php" method="post">
					<?= $_csrf_field ?>
					<div class="login-field">
						<label class="login-label">ログインID</label>
						<input type="text" autocomplete="username" style="ime-mode:disabled;" name="admin_id" value="<?= $h($form['admin_id'] ?? '') ?>">
					</div>
					<div class="login-field">
						<label class="login-label">パスワード</label>
						<input type="password" autocomplete="current-password" name="admin_password" value="<?= $h($form['admin_password'] ?? '') ?>">
					</div>
					<div class="login-submit">
						<input type="submit" name="act" value="ログイン" class="h_link login-btn">
					</div>
				</form>
			</div>
		</div>
		<div id="footer"></div>
	</div>
</body>
</html>
