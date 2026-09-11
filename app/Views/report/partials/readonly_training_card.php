<?php

declare(strict_types=1);

// Render the shared readonly training summary for normal confirm/detail screens.
?>
<div class="rf-card"><div class="rf-title">訓練状況</div><div class="rf-body rf-body--compact">
	<div class="rf-row-3">
		<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('training_am_1_detail')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['training_am_1']) ?></textarea></div>
		<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('training_am_2')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['training_am_2']) ?></textarea></div>
		<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('training_am_3')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['training_am_3']) ?></textarea></div>
	</div>
	<div class="rf-row-3">
		<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('training_pm_1_detail')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['training_pm_1']) ?></textarea></div>
		<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('training_pm_2')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['training_pm_2']) ?></textarea></div>
		<div class="rf-field rf-field-lg"><span class="rf-label"><?= $h($fieldLabel('training_pm_3')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['training_pm_3']) ?></textarea></div>
	</div>
	<div class="rf-break"></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('training_start_time')) ?></span><input type="time" value="<?= $h($formatTime($reportData['training_start_time'])) ?>" readonly></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('training_end_time')) ?></span><input type="time" value="<?= $h($formatTime($reportData['training_end_time'])) ?>" readonly></div>
	<div class="rf-field rf-field-xs"><span class="rf-label"><?= $h($fieldLabel('training_time')) ?></span><div class="rf-value"><?= $h($reportData['training_time']) ?></div></div>
	<div class="rf-break"></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('lunch_time_minutes')) ?></span><input type="number" value="<?= $h($reportData['lunch_time']) ?>" readonly></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('break_time_minutes')) ?></span><input type="number" value="<?= $h($reportData['break_time']) ?>" readonly></div>
	<div class="rf-field rf-field-xs"><span class="rf-label"><?= $h($fieldLabel('lunch_break_time_minutes')) ?></span><div class="rf-value"><?= $h($reportData['lunch_break_time']) ?>分</div></div>
	<div class="rf-field rf-field-xs"><span class="rf-label"><?= $h($fieldLabel('work_time_short')) ?></span><div class="rf-value"><?= $h($reportData['work_time']) ?></div></div>
</div></div>
