<?php
declare(strict_types=1);
$h = static function ($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $h($title) ?>｜在宅就労管理システム</title><link rel="stylesheet" href="<?= $h($cssBase) ?>base.css"><link rel="stylesheet" href="<?= $h($cssBase) ?>components.css"><link rel="stylesheet" href="<?= $h($cssBase) ?>common.css"></head><body><div id="wrapper"><div id="header"><div id="header-inner"><div id="header-brand"><?= $h($loginAdminId) ?></div><div id="h_link_area"><?php foreach ($headerLinks as $link): ?><a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a><?php endforeach; ?></div></div></div><div id="main"><h3><?= $h($title) ?></h3><div class="frm_row">ユーザーの登録が<?= $result ? '完了' : '失敗' ?>しました。</div></div></div></body></html>


