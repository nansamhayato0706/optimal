<?php

$requestPath = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
$currentHeaderPath = is_string($requestPath) ? basename($requestPath) : '';
$headerBrandTag = isset($headerBrandTag) && $headerBrandTag === 'h1' ? 'h1' : 'div';
$navIcons = [
    'group.php' => 'M4 21V3h12v18M16 9h4v12M8 7h4M8 11h4M8 15h4M8 21v-3h4v3',
    'admin.php' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3a4 4 0 0 1 0 8M22 21v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
    'notice.php' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
    'link.php' => 'M14 3h7v7M21 3 10 14M10 3H3v18h18v-7',
    'user.php' => 'M20 21v-2a6 6 0 0 0-6-6h-4a6 6 0 0 0-6 6v2M16 6a4 4 0 1 1-8 0 4 4 0 0 1 8 0',
    'report_daily.php' => 'M3 5h18v16H3zM7 3v4M17 3v4M3 11h18M7 15h3M14 15h3',
    'user_edit.php' => 'M12 5v14M5 12h14',
    'login.php' => 'M9 3H3v18h6M9 12h12M16 7l5 5-5 5',
];
$mobileHeaderLinks = $headerLinks;
$mobileNavOrder = ['user.php' => 0, 'report_daily.php' => 1, 'notice.php' => 2, 'link.php' => 3, 'group.php' => 4, 'admin.php' => 5, 'user_edit.php' => 6, 'login.php' => 8];
usort($mobileHeaderLinks, static function ($left, $right) use ($mobileNavOrder) {
    $leftPath = basename((string) $left['link']);
    $rightPath = basename((string) $right['link']);
    return (isset($mobileNavOrder[$leftPath]) ? $mobileNavOrder[$leftPath] : 7)
        <=> (isset($mobileNavOrder[$rightPath]) ? $mobileNavOrder[$rightPath] : 7);
});
?>
<header id="header" class="site-header" role="banner">
	<div id="header-inner">
		<<?= $headerBrandTag ?> id="header-brand"><span class="site-desktop-brand"><?= $h($loginAdminId) ?></span><span class="site-mobile-title"><?= $h(isset($title) ? $title : '在宅就労管理') ?></span></<?= $headerBrandTag ?>>
		<div id="h_link_area">
<?php foreach ($headerLinks as $link): ?>
			<a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a>
<?php endforeach; ?>
		</div>
		<details class="site-mobile-nav">
			<summary aria-controls="site-mobile-panel"><span aria-hidden="true">☰</span> メニュー</summary>
			<button type="button" class="site-nav-backdrop" aria-label="メニューを閉じる" tabindex="-1"></button>
			<nav id="site-mobile-panel" aria-label="管理メニュー">
				<div class="site-nav-heading"><div><small>在宅就労管理システム</small><strong>メニュー</strong></div><button type="button" class="site-nav-dismiss" aria-label="メニューを閉じる">×</button></div>
				<div class="site-mobile-nav-user"><span aria-hidden="true">●</span> ログイン中：<?= $h($loginAdminId) ?></div>
				<div class="site-nav-links">
<?php foreach ($mobileHeaderLinks as $link): ?>
<?php
$linkPath = basename((string) $link['link']);
$isCurrent = $linkPath === $currentHeaderPath;
$isManagement = in_array($linkPath, ['group.php', 'admin.php', 'user_edit.php'], true);
?>
				<a class="site-mobile-nav-link<?= $isCurrent ? ' is-current' : '' ?><?= $linkPath === 'login.php' ? ' is-logout' : '' ?><?= $isManagement ? ' is-management' : '' ?>" href="<?= $h($link['link']) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>><span class="site-nav-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="<?= $h(isset($navIcons[$linkPath]) ? $navIcons[$linkPath] : 'M5 12h14M12 5v14') ?>"/></svg></span><span class="site-nav-label"><?= $h($link['text']) ?></span><span class="site-nav-indicator"><?= $isCurrent ? '現在地' : '›' ?></span></a>
<?php endforeach; ?>
				</div>
			</nav>
		</details>
	</div>
</header>
<?php if (in_array($currentHeaderPath, ['chat.php', 'log.php'], true)): ?>
<a class="site-mobile-back" href="user.php">← ユーザー一覧</a>
<?php endif; ?>
<script src="<?= $h($jsBase) ?>site_nav.js?v=<?= $h($assetVer) ?>" defer></script>
