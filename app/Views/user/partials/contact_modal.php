<?php

// Keep the contact modal markup together so JS and server fields stay aligned.
?>
<div id="contact-modal-overlay" class="contact-modal-overlay" hidden></div>
<div id="contact-modal" class="contact-modal" hidden>
	<div class="contact-modal-header">
		<h3 class="contact-modal-title">連絡確認：<span id="contact-modal-user-name"></span></h3>
		<button type="button" id="contact-modal-close" class="contact-modal-close" aria-label="閉じる">×</button>
	</div>
	<form id="contact-modal-form">
		<?= csrf_field() ?>
		<input type="hidden" name="contact_uuid" id="contact-modal-contact-uuid" value="">
		<div class="contact-modal-body">
			<div>
				<div class="contact-modal-section-label">送信情報</div>
				<div class="contact-modal-sent-text" id="contact-modal-sent"></div>
				<div class="contact-modal-image-wrap">
					<img id="contact-modal-image" src="<?= $h($dummyImage) ?>" alt="連絡画像">
				</div>
			</div>
			<div>
				<div class="contact-modal-section-label">対応情報</div>
				<div class="contact-modal-field">
					<label for="contact-modal-confirm-div">確認区分</label>
					<select name="confirm_div" id="contact-modal-confirm-div"></select>
					<p class="err" id="contact-modal-confirm-error"></p>
				</div>
				<div class="contact-modal-field">
					<label for="contact-modal-comment">コメント</label>
					<textarea name="comment" id="contact-modal-comment"></textarea>
					<p class="err" id="contact-modal-comment-error"></p>
				</div>
			</div>
		</div>
		<p id="contact-modal-general-error" class="contact-modal-error"></p>
		<div class="contact-modal-footer">
			<input type="button" id="contact-modal-cancel" class="h_link btn-secondary" value="閉じる">
			<input type="submit" id="contact-modal-submit" class="h_link" value="登録">
		</div>
	</form>
</div>
