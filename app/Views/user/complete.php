<?php
declare(strict_types=1);
$h = static function ($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $h($title) ?>｜在宅就労管理システム</title><link rel="stylesheet" href="<?= $h($cssBase) ?>base.css?v=<?= $h($assetVer) ?>"><link rel="stylesheet" href="<?= $h($cssBase) ?>components.css?v=<?= $h($assetVer) ?>"><link rel="stylesheet" href="<?= $h($cssBase) ?>common.css?v=<?= $h($assetVer) ?>"></head><body><div id="wrapper"><?php require dirname(__DIR__) . '/partials/header.php'; ?><div id="main"><h3><?= $h($title) ?></h3><div class="frm_row">ユーザーの登録が<?= $result ? '完了' : '失敗' ?>しました。</div></div></div></body></html>
	<link rel="icon" href="<?= $h($imgBase) ?>favicon.ico" type="image/x-icon">


