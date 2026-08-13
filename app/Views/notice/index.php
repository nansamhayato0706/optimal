<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
	<link rel="stylesheet" href="<?= $h($cssBase) ?>notice.css?v=<?= $h($assetVer) ?>" type="text/css" media="screen">
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main">
		<div class="page-card">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?><?php if ($res !== ''): ?> <span class="page-result"><?= $h($res) ?></span><?php endif; ?></h3>
			</div>
			<form action="notice.php" method="post">
				<?= csrf_field() ?>
				<div class="form-body">
					<div class="frm_row">
						<div class="frm_parts_xlm">
							<div class="frm_title frm_height_xl">お知らせ</div>
							<div class="frm_input_xl frm_height_xl"><textarea style="ime-mode:active;" name="notification"><?= $h($notice['notification'] ?? '') ?></textarea></div>
<?php if (($errors['notification'] ?? '') !== ''): ?>
							<p class="err"><?= $h($errors['notification']) ?></p>
<?php endif; ?>
						</div>
					</div>
				</div>
				<input type="submit" name="act" class="h_link" value="登録">
			</form>
		</div>
	</div>
</div>
</body>
</html>

