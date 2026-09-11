<?php

declare(strict_types=1);

// Share the readonly question/comment card while keeping admin-only comments optional.
?>
<div class="rf-card"><div class="rf-title">疑問や質問</div><div class="rf-body-split">
	<div class="rf-split-col"><span class="rf-label"><?= $h($fieldLabel('remark')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['remark']) ?></textarea></div>
	<div class="rf-split-col"><span class="rf-label"><?= $h($fieldLabel('reply')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['reply']) ?></textarea></div>
<?php if ($showReadonlyAdminComment): ?>
	<div class="rf-split-col"><span class="rf-label"><?= $h($fieldLabel('charge_comment')) ?></span><textarea class="rf-autosize" rows="1" readonly><?= $h($reportData['charge_comment']) ?></textarea></div>
<?php endif; ?>
</div></div>
