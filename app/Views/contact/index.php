<?php

declare(strict_types=1);

use App\Support\ContactViewHelpers;

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$error = static function (string $key) use ($errors): string {
	return (string) ($errors[$key] ?? '');
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
	<link rel="stylesheet" href="<?= $h($cssBase) ?>contact.css" type="text/css" media="screen">
	<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js"></script>
	<script type="text/javascript" src="<?= $h($jsBase) ?>common.js"></script>
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main">
		<div class="page-card panel-stack">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?></h3>
			</div>
			<form action="contact.php" method="post" class="contact-form">
				<?= csrf_field() ?>
				<input type="hidden" name="contact_uuid" value="<?= $h($contact['contact_uuid']) ?>">
<?php require __DIR__ . '/partials/contact_card.php'; ?>
				<div id="frm_button">
					<input type="submit" name="act" value="登録" class="h_link">
				</div>
			</form>
		</div>
	</div>
</div>
</body>
</html>

