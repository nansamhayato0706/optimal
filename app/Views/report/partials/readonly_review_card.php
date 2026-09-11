<?php

declare(strict_types=1);

// Keep the readonly review card markup identical between confirm and detail.
?>
<div class="rf-card"><div class="rf-title">振り返り</div><div class="rf-body">
	<div class="rf-field rf-field-xl"><span class="rf-label"><?= $h($fieldLabel('rethink_am_short')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['rethink_am']) ?></textarea></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('achieve_short')) ?></span><input type="number" value="<?= $h($reportData['achieve_am']) ?>" readonly></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('fatigue_short')) ?></span><input type="number" value="<?= $h($reportData['fatigue_am']) ?>" readonly></div>
	<div class="rf-field rf-field-xl"><span class="rf-label"><?= $h($fieldLabel('rethink_pm_short')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['rethink_pm']) ?></textarea></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('achieve_short')) ?></span><input type="number" value="<?= $h($reportData['achieve_pm']) ?>" readonly></div>
	<div class="rf-field rf-field-sm"><span class="rf-label"><?= $h($fieldLabel('fatigue_short')) ?></span><input type="number" value="<?= $h($reportData['fatigue_pm']) ?>" readonly></div>
</div></div>
