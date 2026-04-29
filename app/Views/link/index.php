<?php

declare(strict_types=1);

$h = static function ($value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$rowError = static function (string $field, int $index) use ($errors): string {
	return (string) ($errors[$field][$index] ?? '');
};
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $h($title) ?>｜在宅就労管理システム</title>
	<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css" type="text/css" media="screen">
	<link rel="stylesheet" href="<?= $h($cssBase) ?>link.css" type="text/css" media="screen">
	<script type="text/javascript">
		function addLinkRow() {
			var tbody = document.getElementById('link-rows');
			var tr = document.createElement('tr');
			tr.innerHTML = '<td data-label="外部リンクURL"><input type="text" name="link_url[]" value=""></td>'
				+ '<td data-label="外部リンク名"><input type="text" name="link_name[]" value=""></td>'
				+ '<td data-label="並び順"><input type="text" name="sort[]" value=""></td>';
			tbody.appendChild(tr);
		}
	</script>
</head>
<body>
<div id="wrapper">
	<div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div>
	<div id="main">
		<div class="page-card panel-stack">
			<div class="page-header">
				<h3 class="page-title"><?= $h($title) ?><?php if ($res !== ''): ?> <span class="page-result"><?= $h($res) ?></span><?php endif; ?></h3>
			</div>
			<form action="link.php" method="post">
				<?= csrf_field() ?>
				<table id="tbl" class="link-table">
					<thead><tr><th>外部リンクURL</th><th>外部リンク名</th><th>並び順</th></tr></thead>
					<tbody id="link-rows">
<?php foreach ($links as $index => $link): ?>
						<tr>
							<td data-label="外部リンクURL"><input type="text" name="link_url[]" value="<?= $h($link['link_url'] ?? '') ?>"><?php if ($rowError('link_url', $index) !== ''): ?><p class="err"><?= $h($rowError('link_url', $index)) ?></p><?php endif; ?></td>
							<td data-label="外部リンク名"><input type="text" name="link_name[]" value="<?= $h($link['link_name'] ?? '') ?>"><?php if ($rowError('link_name', $index) !== ''): ?><p class="err"><?= $h($rowError('link_name', $index)) ?></p><?php endif; ?></td>
							<td data-label="並び順"><input type="text" name="sort[]" value="<?= $h($link['sort'] ?? '') ?>"><?php if ($rowError('sort', $index) !== ''): ?><p class="err"><?= $h($rowError('sort', $index)) ?></p><?php endif; ?></td>
						</tr>
<?php endforeach; ?>
					</tbody>
				</table>
				<div class="link-btn-row">
					<input type="submit" name="act" class="h_link" value="登録">
					<input type="button" onclick="addLinkRow()" class="h_link btn-secondary" value="行追加">
				</div>
			</form>
		</div>
	</div>
</div>
</body>
</html>

