<?php

declare(strict_types=1);

// Keep the normal edit question card in one place while preserving validation markup.
?>
<div class="rf-card"><div class="rf-title">疑問や質問</div><div class="rf-body-split">
	<div class="rf-split-col"><label class="rf-label" for="<?= $h($fieldId('remark')) ?>"><?= $h($fieldLabel('remark')) ?></label><span class="rf-help" id="<?= $h($helpId('remark')) ?>">255文字以内、5行以内で入力してください。</span><textarea id="<?= $h($fieldId('remark')) ?>" rows="5" name="remark"<?= $readonly ? ' readonly' : '' ?><?= ($errors['remark'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('remark', true) !== '' ? ' aria-describedby="' . $h($describedBy('remark', true)) . '"' : '' ?>><?= $h($form['remark']) ?></textarea><?php if (($errors['remark'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('remark')) ?>" role="alert"><?= $h($errors['remark']) ?></p><?php endif; ?><p class="err" id="report_line_limit_message" aria-live="polite"></p></div>
<?php if ($showAdminComment): ?>
	<div class="rf-split-col"><label class="rf-label" for="<?= $h($fieldId('charge_comment')) ?>"><?= $h($fieldLabel('charge_comment')) ?></label><span class="rf-help" id="<?= $h($helpId('charge_comment')) ?>">255文字以内、5行以内で入力してください。</span><textarea id="<?= $h($fieldId('charge_comment')) ?>" rows="5" name="charge_comment"<?= ($commentOnly && !$locked) ? '' : ' readonly' ?><?= ($errors['charge_comment'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('charge_comment', true) !== '' ? ' aria-describedby="' . $h($describedBy('charge_comment', true)) . '"' : '' ?>><?= $h($form['charge_comment']) ?></textarea><?php if (($errors['charge_comment'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('charge_comment')) ?>" role="alert"><?= $h($errors['charge_comment']) ?></p><?php endif; ?></div>
<?php endif; ?>
</div></div>
