<?php
declare(strict_types=1);
$h = static function ($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
$fieldId = static function (string $name): string {
	return 'user_' . preg_replace('/[^a-zA-Z0-9_]+/', '_', $name);
};
$errorId = static function (string $name) use ($fieldId): string {
	return $fieldId($name) . '_error';
};
$describedBy = static function (string $name) use ($errors, $errorId): string {
	return ($errors[$name] ?? '') !== '' ? $errorId($name) : '';
};
$options = static function (array $map, $selected) use ($h): string {
	$html = '<option value="">選択してください</option>';
	foreach ($map as $id => $label) {
		$html .= '<option value="' . $h($id) . '"' . ((string) $id === (string) $selected ? ' selected' : '') . '>' . $h($label) . '</option>';
	}
	return $html;
};
$booleanOptions = array(
	0 => 'なし',
	1 => 'あり',
);
?>
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="<?= $h($imgBase) ?>favicon.ico" type="image/x-icon">
		<title><?= $h($title) ?>｜在宅就労管理システム</title>
		<link rel="stylesheet" href="<?= $h($cssBase) ?>jquery-ui.min.css?v=<?= $h($assetVer) ?>">
		<link rel="stylesheet" href="<?= $h($cssBase) ?>datepicker-custom.css?v=<?= $h($assetVer) ?>">
		<link rel="stylesheet" href="<?= $h($cssBase) ?>base.css?v=<?= $h($assetVer) ?>">
		<link rel="stylesheet" href="<?= $h($cssBase) ?>components.css?v=<?= $h($assetVer) ?>">
		<link rel="stylesheet" href="<?= $h($cssBase) ?>common.css?v=<?= $h($assetVer) ?>">
		<link rel="stylesheet" href="<?= $h($cssBase) ?>user.css?v=<?= $h($assetVer) ?>">
		<script type="text/javascript" src="<?= $h($jsBase) ?>jquery.min.js?v=<?= $h($assetVer) ?>"></script>
		<script type="text/javascript" src="<?= $h($jsBase) ?>jquery-ui.min.js?v=<?= $h($assetVer) ?>"></script>
		<script type="text/javascript" src="<?= $h($jsBase) ?>datepicker-ja.js?v=<?= $h($assetVer) ?>"></script>
		<script type="text/javascript" src="<?= $h($jsBase) ?>common.js?v=<?= $h($assetVer) ?>"></script>
		<script type="text/javascript" src="<?= $h($jsBase) ?>user.js?v=<?= $h($assetVer) ?>"></script>
	</head>
	<body>
		<div id="wrapper">
			<div id="header">
				<div id="header-inner">
					<div id="header-brand"><?= $h($loginAdminId) ?></div>
					<div id="h_link_area">
<?php foreach ($headerLinks as $link): ?>
						<a class="h_link" href="<?= $h($link['link']) ?>"><?= $h($link['text']) ?></a>
<?php endforeach; ?>
					</div>
				</div>
			</div>
			<div id="main">
				<div class="page-card panel-stack">
					<div class="page-header">
						<h3 class="page-title"><?= $h($title) ?><?php if (($form['user_name'] ?? '') !== ''): ?>：<?= $h($form['user_name']) ?><?php endif; ?></h3>
					</div>
<?php if (!empty($errors['general'])): ?>
					<p class="err-box"><?= $h($errors['general']) ?></p>
<?php endif; ?>
					<form action="user_edit.php" method="post" class="user_edit_form">
						<?= csrf_field() ?>
						<input type="hidden" name="user_uuid" value="<?= $h($form['user_uuid']) ?>">

						<div class="rf-form">

							<div class="rf-card">
								<div class="rf-title">アカウント情報</div>
								<div class="rf-body">
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('user_id')) ?>">ユーザーID</label>
										<input id="<?= $h($fieldId('user_id')) ?>" type="text" name="user_id" value="<?= $h($form['user_id']) ?>"<?= ($errors['user_id'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_id') !== '' ? ' aria-describedby="' . $h($describedBy('user_id')) . '"' : '' ?>>
										<?php if (($errors['user_id'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_id')) ?>" role="alert"><?= $h($errors['user_id']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('user_password')) ?>">パスワード</label>
										<input id="<?= $h($fieldId('user_password')) ?>" type="text" name="user_password" value="<?= $h($form['user_password']) ?>"<?= ($errors['user_password'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_password') !== '' ? ' aria-describedby="' . $h($describedBy('user_password')) . '"' : '' ?>>
										<?php if (($errors['user_password'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_password')) ?>" role="alert"><?= $h($errors['user_password']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-md">
										<label class="rf-label" for="<?= $h($fieldId('user_div')) ?>">ユーザー区分</label>
										<select id="<?= $h($fieldId('user_div')) ?>" name="user_div"<?= ($errors['user_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_div') !== '' ? ' aria-describedby="' . $h($describedBy('user_div')) . '"' : '' ?>><?= $options($divMap['user'] ?? array(), $form['user_div']) ?></select>
										<?php if (($errors['user_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_div')) ?>" role="alert"><?= $h($errors['user_div']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-md">
										<label class="rf-label" for="<?= $h($fieldId('work_style_div')) ?>">就労形態</label>
										<select id="<?= $h($fieldId('work_style_div')) ?>" name="work_style_div"<?= ($errors['work_style_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('work_style_div') !== '' ? ' aria-describedby="' . $h($describedBy('work_style_div')) . '"' : '' ?>><?= $options($divMap['work_style'] ?? array(), $form['work_style_div']) ?></select>
										<?php if (($errors['work_style_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('work_style_div')) ?>" role="alert"><?= $h($errors['work_style_div']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('delete_flg')) ?>">削除フラグ</label>
										<select id="<?= $h($fieldId('delete_flg')) ?>" name="delete_flg"<?= ($errors['delete_flg'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('delete_flg') !== '' ? ' aria-describedby="' . $h($describedBy('delete_flg')) . '"' : '' ?>><?= $options($divMap['delete_flg'] ?? array(), $form['delete_flg']) ?></select>
										<?php if (($errors['delete_flg'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('delete_flg')) ?>" role="alert"><?= $h($errors['delete_flg']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('visual_impairment_flg')) ?>">視覚障害</label>
										<select id="<?= $h($fieldId('visual_impairment_flg')) ?>" name="visual_impairment_flg"<?= ($errors['visual_impairment_flg'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('visual_impairment_flg') !== '' ? ' aria-describedby="' . $h($describedBy('visual_impairment_flg')) . '"' : '' ?>><?= $options($booleanOptions, $form['visual_impairment_flg']) ?></select>
										<?php if (($errors['visual_impairment_flg'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('visual_impairment_flg')) ?>" role="alert"><?= $h($errors['visual_impairment_flg']) ?></p><?php endif; ?>
									</div>
								</div>
							</div>

							<div class="rf-card">
								<div class="rf-title">個人情報</div>
								<div class="rf-body">
									<div class="rf-field rf-field-md">
										<label class="rf-label" for="<?= $h($fieldId('user_name')) ?>">名前</label>
										<input id="<?= $h($fieldId('user_name')) ?>" type="text" name="user_name" value="<?= $h($form['user_name']) ?>"<?= ($errors['user_name'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_name') !== '' ? ' aria-describedby="' . $h($describedBy('user_name')) . '"' : '' ?>>
										<?php if (($errors['user_name'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_name')) ?>" role="alert"><?= $h($errors['user_name']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-md">
										<label class="rf-label" for="<?= $h($fieldId('user_name_kana')) ?>">フリガナ</label>
										<input id="<?= $h($fieldId('user_name_kana')) ?>" type="text" name="user_name_kana" value="<?= $h($form['user_name_kana']) ?>"<?= ($errors['user_name_kana'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_name_kana') !== '' ? ' aria-describedby="' . $h($describedBy('user_name_kana')) . '"' : '' ?>>
										<?php if (($errors['user_name_kana'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_name_kana')) ?>" role="alert"><?= $h($errors['user_name_kana']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('sex_div')) ?>">性別</label>
										<select id="<?= $h($fieldId('sex_div')) ?>" name="sex_div"<?= ($errors['sex_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('sex_div') !== '' ? ' aria-describedby="' . $h($describedBy('sex_div')) . '"' : '' ?>><?= $options($divMap['sex'] ?? array(), $form['sex_div']) ?></select>
										<?php if (($errors['sex_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('sex_div')) ?>" role="alert"><?= $h($errors['sex_div']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('birthday')) ?>">生年月日</label>
										<input id="<?= $h($fieldId('birthday')) ?>" type="text" class="date" name="birthday" value="<?= $h($form['birthday']) ?>" autocomplete="off"<?= ($errors['birthday'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('birthday') !== '' ? ' aria-describedby="' . $h($describedBy('birthday')) . '"' : '' ?>>
										<?php if (($errors['birthday'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('birthday')) ?>" role="alert"><?= $h($errors['birthday']) ?></p><?php endif; ?>
									</div>
								</div>
							</div>

							<div class="rf-card">
								<div class="rf-title">連絡先</div>
								<div class="rf-body">
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('user_zip_code')) ?>">郵便番号</label>
										<input id="<?= $h($fieldId('user_zip_code')) ?>" type="text" name="user_zip_code" value="<?= $h($form['user_zip_code']) ?>"<?= ($errors['user_zip_code'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_zip_code') !== '' ? ' aria-describedby="' . $h($describedBy('user_zip_code')) . '"' : '' ?>>
										<?php if (($errors['user_zip_code'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_zip_code')) ?>" role="alert"><?= $h($errors['user_zip_code']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('user_prefecture_div')) ?>">都道府県</label>
										<select id="<?= $h($fieldId('user_prefecture_div')) ?>" name="user_prefecture_div"<?= ($errors['user_prefecture_div'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_prefecture_div') !== '' ? ' aria-describedby="' . $h($describedBy('user_prefecture_div')) . '"' : '' ?>><?= $options($divMap['prefecture'] ?? array(), $form['user_prefecture_div']) ?></select>
										<?php if (($errors['user_prefecture_div'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_prefecture_div')) ?>" role="alert"><?= $h($errors['user_prefecture_div']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-xl">
										<label class="rf-label" for="<?= $h($fieldId('user_address')) ?>">住所</label>
										<input id="<?= $h($fieldId('user_address')) ?>" type="text" name="user_address" value="<?= $h($form['user_address']) ?>"<?= ($errors['user_address'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_address') !== '' ? ' aria-describedby="' . $h($describedBy('user_address')) . '"' : '' ?>>
										<?php if (($errors['user_address'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_address')) ?>" role="alert"><?= $h($errors['user_address']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-sm">
										<label class="rf-label" for="<?= $h($fieldId('user_tel')) ?>">電話番号</label>
										<input id="<?= $h($fieldId('user_tel')) ?>" type="tel" name="user_tel" value="<?= $h($form['user_tel']) ?>"<?= ($errors['user_tel'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_tel') !== '' ? ' aria-describedby="' . $h($describedBy('user_tel')) . '"' : '' ?>>
										<?php if (($errors['user_tel'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_tel')) ?>" role="alert"><?= $h($errors['user_tel']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-md">
										<label class="rf-label" for="<?= $h($fieldId('user_email')) ?>">e-mail</label>
										<input id="<?= $h($fieldId('user_email')) ?>" type="email" name="user_email" value="<?= $h($form['user_email']) ?>"<?= ($errors['user_email'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('user_email') !== '' ? ' aria-describedby="' . $h($describedBy('user_email')) . '"' : '' ?>>
										<?php if (($errors['user_email'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('user_email')) ?>" role="alert"><?= $h($errors['user_email']) ?></p><?php endif; ?>
									</div>
								</div>
							</div>

							<div class="rf-card">
								<div class="rf-title">機器情報</div>
								<div class="rf-body">
									<div class="rf-field rf-field-md">
										<label class="rf-label" for="<?= $h($fieldId('serial_no')) ?>">シリアル番号</label>
										<input id="<?= $h($fieldId('serial_no')) ?>" type="text" name="serial_no" value="<?= $h($form['serial_no']) ?>"<?= ($errors['serial_no'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('serial_no') !== '' ? ' aria-describedby="' . $h($describedBy('serial_no')) . '"' : '' ?>>
										<?php if (($errors['serial_no'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('serial_no')) ?>" role="alert"><?= $h($errors['serial_no']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-md">
										<label class="rf-label" for="<?= $h($fieldId('volume_no')) ?>">ボリューム番号</label>
										<input id="<?= $h($fieldId('volume_no')) ?>" type="text" name="volume_no" value="<?= $h($form['volume_no']) ?>">
									</div>
								</div>
							</div>

							<div class="rf-card">
								<div class="rf-title">通知間隔</div>
								<div class="rf-body">
									<div class="rf-field rf-field-xs">
										<label class="rf-label" for="<?= $h($fieldId('send_interval')) ?>">連絡（分）</label>
										<input id="<?= $h($fieldId('send_interval')) ?>" type="number" name="send_interval" value="<?= $h($form['send_interval']) ?>"<?= ($errors['send_interval'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('send_interval') !== '' ? ' aria-describedby="' . $h($describedBy('send_interval')) . '"' : '' ?>>
										<?php if (($errors['send_interval'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('send_interval')) ?>" role="alert"><?= $h($errors['send_interval']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-xs">
										<label class="rf-label" for="<?= $h($fieldId('keyboard_interval')) ?>">キーボード（分）</label>
										<input id="<?= $h($fieldId('keyboard_interval')) ?>" type="number" name="keyboard_interval" value="<?= $h($form['keyboard_interval']) ?>"<?= ($errors['keyboard_interval'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('keyboard_interval') !== '' ? ' aria-describedby="' . $h($describedBy('keyboard_interval')) . '"' : '' ?>>
										<?php if (($errors['keyboard_interval'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('keyboard_interval')) ?>" role="alert"><?= $h($errors['keyboard_interval']) ?></p><?php endif; ?>
									</div>
									<div class="rf-field rf-field-xs">
										<label class="rf-label" for="<?= $h($fieldId('mouse_interval')) ?>">マウス（分）</label>
										<input id="<?= $h($fieldId('mouse_interval')) ?>" type="number" name="mouse_interval" value="<?= $h($form['mouse_interval']) ?>"<?= ($errors['mouse_interval'] ?? '') !== '' ? ' aria-invalid="true"' : '' ?><?= $describedBy('mouse_interval') !== '' ? ' aria-describedby="' . $h($describedBy('mouse_interval')) . '"' : '' ?>>
										<?php if (($errors['mouse_interval'] ?? '') !== ''): ?><p class="err" id="<?= $h($errorId('mouse_interval')) ?>" role="alert"><?= $h($errors['mouse_interval']) ?></p><?php endif; ?>
									</div>
								</div>
							</div>

							<div class="rf-card">
								<div class="rf-title">備考</div>
								<div class="rf-body">
									<div class="rf-field rf-field-xl">
										<label class="rf-label" for="<?= $h($fieldId('remark')) ?>">備考</label>
										<input id="<?= $h($fieldId('remark')) ?>" type="text" name="remark" value="<?= $h($form['remark']) ?>">
									</div>
								</div>
							</div>

<?php if ($assignableAdmins !== array()): ?>
							<div class="rf-card">
								<fieldset class="rf-check-body">
									<legend class="rf-title">管理者の設定</legend>
									<?php foreach ($assignableAdmins as $admin): ?>
									<label class="rf-check-item" for="<?= $h($fieldId('admin_uuid_' . $admin['admin_uuid'])) ?>">
										<input id="<?= $h($fieldId('admin_uuid_' . $admin['admin_uuid'])) ?>" type="checkbox" name="admin_uuid[]" value="<?= $h($admin['admin_uuid']) ?>"<?= in_array($admin['admin_uuid'], $form['admin_uuid'], true) ? ' checked' : '' ?>>
										<?= $h($admin['admin_name']) ?>
									</label>
									<?php endforeach; ?>
								</fieldset>
							</div>
<?php endif; ?>

						</div>

						<div class="rf-actions">
							<input type="submit" name="act" value="登録" class="h_link">
						</div>
					</form>

<?php if (($form['user_uuid'] ?? '') !== ''): ?>
					<div class="rf-card" id="screenshot-card">
						<div class="rf-title">リモート操作</div>
						<div class="rf-body">
							<div class="rf-field rf-field-xl">
								<button type="button" id="screenshot-request-btn" class="h_link" style="background:#e08a1e;border-color:#e08a1e;" data-user-uuid="<?= $h($form['user_uuid']) ?>" data-login-admin-name="<?= $h($loginAdminName) ?>">このユーザーの画面をキャプチャ</button>
								<p id="screenshot-status" role="status" style="margin-top:8px;"></p>
							</div>
						</div>
					</div>

					<div class="rf-card" id="screenshot-history-card">
						<div class="rf-title">キャプチャ履歴</div>
						<div class="rf-body">
							<ul id="screenshot-history-list" style="list-style:none;margin:0;padding:0;">
<?php foreach ($screenshotHistory as $item): ?>
								<li id="screenshot-history-<?= $h($item['request_uuid']) ?>" class="screenshot-history-item" style="display:flex;align-items:center;gap:12px;padding:6px 0;border-bottom:1px solid #eee;">
									<span style="white-space:nowrap;"><?= $h($item['requested_date']) ?></span>
									<span style="white-space:nowrap;"><?= $h($item['admin_name']) ?></span>
									<span><?= $h(array('pending' => '取得中', 'done' => '完了', 'failed' => '失敗')[$item['status']] ?? $item['status']) ?></span>
<?php if ($item['image_url'] !== null): ?>
									<a href="<?= $h($item['image_url']) ?>" target="_blank" rel="noopener"><img src="<?= $h($item['image_url']) ?>" alt="スクリーンショット" style="max-width:120px;max-height:80px;border:1px solid #ccc;"></a>
<?php endif; ?>
									<button type="button" class="screenshot-delete-btn" data-request-uuid="<?= $h($item['request_uuid']) ?>" style="margin-left:auto;color:#b42318;">削除</button>
								</li>
<?php endforeach; ?>
							</ul>
							<p id="screenshot-history-empty" style="<?= $screenshotHistory === array() ? '' : 'display:none;' ?>">まだ取得履歴はありません。</p>
						</div>
					</div>
<?php endif; ?>

				</div>
			</div>
			<div id="footer"></div>
		</div>
<?php if (($form['user_uuid'] ?? '') !== ''): ?>
		<script>
		(function () {
			var btn = document.getElementById('screenshot-request-btn');
			if (!btn) { return; }
			var statusEl = document.getElementById('screenshot-status');
			var csrfToken = document.querySelector('.user_edit_form input[name="_token"]').value;
			var userUuid = btn.getAttribute('data-user-uuid');
			var loginAdminName = btn.getAttribute('data-login-admin-name');
			var historyList = document.getElementById('screenshot-history-list');
			var historyEmpty = document.getElementById('screenshot-history-empty');
			var pollTimer = null;

			function stopPolling() {
				if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
			}

			function loadImageWithRetry(img, url, attemptsLeft) {
				img.onerror = function () {
					if (attemptsLeft > 0) {
						attemptsLeft -= 1;
						setTimeout(function () {
							img.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'retry=' + Date.now();
						}, 1000);
					} else {
						img.onerror = null;
					}
				};
				img.src = url;
			}

			function prependHistoryItem(imageUrl, requestUuid, requestedDate) {
				if (!historyList) { return; }
				if (document.getElementById('screenshot-history-' + requestUuid)) { return; }
				if (historyEmpty) { historyEmpty.style.display = 'none'; }
				var li = document.createElement('li');
				li.id = 'screenshot-history-' + requestUuid;
				li.className = 'screenshot-history-item';
				li.style.cssText = 'display:flex;align-items:center;gap:12px;padding:6px 0;border-bottom:1px solid #eee;';

				var dateSpan = document.createElement('span');
				dateSpan.style.whiteSpace = 'nowrap';
				dateSpan.textContent = requestedDate || new Date().toLocaleString('ja-JP');

				var adminSpan = document.createElement('span');
				adminSpan.style.whiteSpace = 'nowrap';
				adminSpan.textContent = loginAdminName || '';

				var statusSpan = document.createElement('span');
				statusSpan.textContent = '完了';

				var link = document.createElement('a');
				link.href = imageUrl;
				link.target = '_blank';
				link.rel = 'noopener';
				var img = document.createElement('img');
				img.alt = 'スクリーンショット';
				img.style.cssText = 'max-width:120px;max-height:80px;border:1px solid #ccc;';
				loadImageWithRetry(img, imageUrl, 3);
				link.appendChild(img);

				li.appendChild(dateSpan);
				li.appendChild(adminSpan);
				li.appendChild(statusSpan);
				li.appendChild(link);
				var deleteButton = document.createElement('button');
				deleteButton.type = 'button';
				deleteButton.className = 'screenshot-delete-btn';
				deleteButton.setAttribute('data-request-uuid', requestUuid);
				deleteButton.style.cssText = 'margin-left:auto;color:#b42318;';
				deleteButton.textContent = '削除';
				li.appendChild(deleteButton);
				historyList.insertBefore(li, historyList.firstChild);
			}

			if (historyList) {
				historyList.addEventListener('click', function (event) {
					var deleteButton = event.target;
					if (!deleteButton.classList || !deleteButton.classList.contains('screenshot-delete-btn')) { return; }
					if (!window.confirm('このキャプチャ履歴と保存画像を削除します。よろしいですか？')) { return; }

					var requestUuid = deleteButton.getAttribute('data-request-uuid');
					var historyItem = deleteButton.closest('.screenshot-history-item');
					deleteButton.disabled = true;
					var body = new URLSearchParams();
					body.set('_token', csrfToken);
					body.set('user_uuid', userUuid);
					body.set('request_uuid', requestUuid);

					fetch('screenshot_delete.php', {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: body.toString()
					}).then(function (res) {
						return res.json().then(function (data) {
							return { ok: res.ok, data: data };
						}).catch(function () {
							return { ok: false, data: { error: 'サーバー応答を確認できません（HTTP ' + res.status + '）。' } };
						});
					}).then(function (response) {
						var data = response.data || {};
						if (!response.ok || data.result !== 'ok') {
							statusEl.textContent = 'キャプチャ履歴を削除できません: ' + (data.error || '不明なエラーです。');
							deleteButton.disabled = false;
							return;
						}
						if (historyItem) { historyItem.remove(); }
						if (historyEmpty && historyList.children.length === 0) { historyEmpty.style.display = ''; }
						statusEl.textContent = data.warning || 'キャプチャ履歴を削除しました。';
					}).catch(function () {
						statusEl.textContent = 'キャプチャ履歴の削除に失敗しました。';
						deleteButton.disabled = false;
					});
				});
			}

			function pollStatus(requestUuid) {
				function checkStatus() {
					fetch('screenshot_status.php?user_uuid=' + encodeURIComponent(userUuid) + '&request_uuid=' + encodeURIComponent(requestUuid), {
						credentials: 'same-origin'
					}).then(function (res) { return res.json(); }).then(function (data) {
						if (data.status === 'done') {
							stopPolling();
							statusEl.textContent = '取得が完了しました。キャプチャ履歴の先頭に追加しました。';
							prependHistoryItem(data.image_url, requestUuid, data.requested_date);
							btn.disabled = false;
						} else if (data.status === 'failed' || data.error) {
							stopPolling();
							statusEl.textContent = '取得に失敗しました。クライアントが起動しているか確認してください。';
							btn.disabled = false;
						} else {
							pollTimer = setTimeout(checkStatus, 3000);
						}
					}).catch(function () {
						stopPolling();
						statusEl.textContent = '状態確認に失敗しました。';
						btn.disabled = false;
					});
				}
				checkStatus();
			}

			btn.addEventListener('click', function () {
				if (!window.confirm('このユーザーの画面をキャプチャしてサーバーに保存します。よろしいですか？')) {
					return;
				}
				btn.disabled = true;
				statusEl.textContent = '要求を送信しています…';

				var body = new URLSearchParams();
				body.set('_token', csrfToken);
				body.set('user_uuid', userUuid);

				fetch('screenshot_request.php', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body.toString()
				}).then(function (res) { return res.json(); }).then(function (data) {
					if (data.error || !data.request_uuid) {
						statusEl.textContent = '要求の送信に失敗しました。';
						btn.disabled = false;
						return;
					}
					statusEl.textContent = 'クライアントからの応答を待っています…';
					pollStatus(data.request_uuid);
				}).catch(function () {
					statusEl.textContent = '要求の送信に失敗しました。';
					btn.disabled = false;
				});
			});
		})();
		</script>
<?php endif; ?>
	</body>
</html>
