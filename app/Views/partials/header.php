<?php

$requestPath = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
$currentHeaderPath = is_string($requestPath) ? basename($requestPath) : '';
$headerBrandTag = isset($headerBrandTag) && $headerBrandTag === 'h1' ? 'h1' : 'div';
?>
<header id="header" class="site-header" role="banner">
	<div id="header-inner">
		<<?= $headerBrandTag ?> id="header-brand"><?= $h($loginAdminId) ?></<?= $headerBrandTag ?>>
		<div id="h_link_area">
<?php foreach ($headerLinks as $link): ?>
			<a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a>
<?php endforeach; ?>
		</div>
		<details class="site-mobile-nav">
			<summary><span class="site-mobile-nav-open">メニュー</span><span class="site-mobile-nav-close">閉じる</span></summary>
			<nav aria-label="管理メニュー">
				<div class="site-mobile-nav-user">ログイン中：<?= $h($loginAdminId) ?></div>
<?php foreach ($headerLinks as $link): ?>
<?php $isCurrent = basename((string) $link['link']) === $currentHeaderPath; ?>
				<a class="site-mobile-nav-link<?= $isCurrent ? ' is-current' : '' ?><?= $link['text'] === 'ログアウト' ? ' is-logout' : '' ?>" href="<?= $h($link['link']) ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>><?= $h($link['text']) ?><?php if ($isCurrent): ?><span>現在地</span><?php endif; ?></a>
<?php endforeach; ?>
			</nav>
		</details>
	</div>
</header>
