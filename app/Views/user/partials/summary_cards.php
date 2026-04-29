<?php

// Keep the summary tiles together so count layout changes stay in one file.
?>
<div class="summary-cards">
	<div class="summary-card summary-card--working"><span class="summary-card__label">作業開始中</span><span class="summary-card__value"><?= $h($trainingStartCount) ?><span class="summary-card__unit">人</span></span></div>
	<div class="summary-card summary-card--done"><span class="summary-card__label">今日の訓練終了</span><span class="summary-card__value"><?= $h($trainingEndTodayCount) ?><span class="summary-card__unit">人</span></span></div>
	<div class="summary-card summary-card--report"><span class="summary-card__label">日報通知</span><span class="summary-card__value"><?= $h($reportNoticeCount) ?><span class="summary-card__unit">人</span></span></div>
	<div class="summary-card summary-card--report"><span class="summary-card__label">日報確認</span><span class="summary-card__value"><?= $h($reportConfirmedCount) ?><span class="summary-card__unit">人</span></span></div>
	<div class="summary-card summary-card--chat"><span class="summary-card__label">チャット通知</span><span class="summary-card__value"><?= $h($chatNoticeCount) ?><span class="summary-card__unit">人</span></span></div>
	<div class="summary-card summary-card--refresh"><span class="summary-card__label">サマリ再反映件数</span><span class="summary-card__value"><?= $h($statusSummaryRefreshCount) ?><span class="summary-card__unit">件</span></span></div>
</div>
