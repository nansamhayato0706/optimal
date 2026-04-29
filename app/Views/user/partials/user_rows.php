<?php

// Keep the list row markup together so column changes do not sprawl across the page view.
foreach ($users as $index => $user):
	$confirmDiv = (int) ($user['confirm_div'] ?? 0);
	$contactDiv = (int) ($user['contact_div'] ?? 0);
	$nameTitle = \App\Support\UserViewHelpers::nameTitle($user);
?>
					<tr data-user-uuid="<?= $h($user['user_uuid']) ?>" data-user-name="<?= $h($user['user_name']) ?>">
						<td class="row_no text-right"><?= $h($index + 1) ?></td>
						<td class="text-center"><?= $h($user['user_id']) ?></td>
						<td class="text-center"><?= $h(\App\Support\UserViewHelpers::divName($divMap, 'work_style', $user['work_style_div'])) ?></td>
						<td<?= $nameTitle !== '' ? ' title="' . $h($nameTitle) . '"' : '' ?>><?= $h($user['user_name']) ?></td>
						<td class="text-center"><?= $h(\App\Support\UserViewHelpers::divName($divMap, 'sex', $user['sex_div'])) ?></td>
						<td class="text-center"><?= $h(\App\Support\UserViewHelpers::age($user['birthday'])) ?></td>
<?php if ($confirmDiv === 1 || $confirmDiv === 2): ?>
						<td class="user_contact_<?= $h($contactDiv) ?>" data-col="contact"><?= $h(\App\Support\UserViewHelpers::formatDateTime($user['contact_date'] ?? '', 'y/m/d H:i')) ?> <?= $h(\App\Support\UserViewHelpers::divName($divMap, 'contact', $contactDiv)) ?> <a class="h_link user-status-link" href="contact.php?i=<?= $h($user['contact_uuid']) ?>">確認</a></td>
<?php else: ?>
						<td class="user_contact_f_<?= $h($contactDiv) ?>" data-col="contact"><?= $h(\App\Support\UserViewHelpers::formatDateTime($user['contact_date'] ?? '', 'y/m/d H:i')) ?> <?= $h(\App\Support\UserViewHelpers::divName($divMap, 'contact', $contactDiv)) ?> <?= $h(\App\Support\UserViewHelpers::divName($divMap, 'confirm', $confirmDiv)) ?></td>
<?php endif; ?>
<?php if (empty($user['report_uuid'])): ?>
						<td class="text-center" data-col="report"><a href="report.php?i=<?= $h($user['user_uuid']) ?>">一覧</a></td>
<?php elseif (trim((string) ($user['report_admin_uuid'] ?? '')) === ''): ?>
						<td class="user_report text-center" data-col="report"><a href="report_edit.php?i=<?= $h($user['report_uuid']) ?>">編集</a></td>
<?php else: ?>
						<td class="text-center" data-col="report"><a href="report_detail.php?i=<?= $h($user['report_uuid']) ?>">確認</a></td>
<?php endif; ?>
						<td class="<?= !empty($user['chat_user_uuid']) ? 'user_chat ' : '' ?>text-center" data-col="chat"><a href="chat.php?i=<?= $h($user['user_uuid']) ?>">確認</a></td>
						<td class="text-center"><a href="log.php?i=<?= $h($user['user_uuid']) ?>">一覧</a></td>
						<td class="text-center"><a href="user_edit.php?i=<?= $h($user['user_uuid']) ?>">変更</a></td>
					</tr>
<?php endforeach; ?>
