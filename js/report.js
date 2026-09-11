$(function(){
	var $wrapper = $('#wrapper');

	$(document).on('keydown', '.report_edit_form', function(e){
		if(e.key === 'Enter' && e.target.tagName !== 'TEXTAREA'){
			e.preventDefault();
		}
	});

	$(document).on('change', '#frm .month-picker', function(){
		$('#frm').submit();
	});

	$(document).on('click', '.mp-nav-btn', function(){
		var $btn = $(this);
		var delta = $btn.hasClass('mp-nav-prev') ? -1 : 1;

		var $monthInput = delta === -1 ? $btn.next('input.month-picker') : $btn.prev('input.month-picker');
		if ($monthInput.length) {
			var val = $monthInput.val();
			if (!val || !/^\d{4}-\d{2}/.test(val)) { return; }
			var y = parseInt(val.substring(0, 4), 10);
			var m = parseInt(val.substring(5, 7), 10) - 1 + delta;
			y += Math.floor(m / 12);
			m = ((m % 12) + 12) % 12;
			$monthInput.val(y + '-' + (m + 1 < 10 ? '0' : '') + (m + 1)).trigger('change');
			return;
		}

		var $dateInput = delta === -1 ? $btn.next('input.date') : $btn.prev('input.date');
		if ($dateInput.length) {
			var dval = $dateInput.val();
			if (!dval || !/^\d{4}-\d{2}-\d{2}$/.test(dval)) { return; }
			var parts = dval.split('-');
			var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
			d.setDate(d.getDate() + delta);
			var yy = d.getFullYear();
			var mm = String(d.getMonth() + 1).padStart(2, '0');
			var dd = String(d.getDate()).padStart(2, '0');
			$dateInput.val(yy + '-' + mm + '-' + dd);
			return;
		}

		var $select = delta === -1 ? $btn.next('select') : $btn.prev('select');
		if ($select.length) {
			var idx = $select.prop('selectedIndex') + delta;
			if (idx >= 0 && idx < $select.find('option').length) {
				$select.prop('selectedIndex', idx).trigger('change');
			}
		}
	});

	if (typeof $.fn.datepicker === 'function') {
		$('.date').datepicker({
			yearRange:'2016:+1',
			dateFormat:'yy-mm-dd',
			showButtonPanel:false,
			changeMonth:true,
			changeYear:true
		});
	}

	var maxLines = 10;
	var maxLength = 500;
	var targetSelector = 'textarea[name="remark"]:not([readonly]), textarea[name="reply"]:not([readonly]), textarea[name="charge_comment"]:not([readonly])';
	var $lineLimitMessage = $('#report_line_limit_message');
	var lineLimitMessageText = '入力は' + maxLines + '行までです。';
	var lengthLimitMessageText = '入力は' + maxLength + '文字までです。';

	function exceedsVisibleLines(field){
		return field.scrollHeight > field.clientHeight + 1;
	}

	function autosizeField(field){
		field.style.height = 'auto';
		// box-sizing: border-box のため、style.height には border 分も含める必要がある。
		// scrollHeight は border を含まないので、そのまま height に代入すると
		// clientHeight が border 分だけ scrollHeight を下回り、exceedsVisibleLines() が
		// 常に true 判定になってしまう（＝1文字目から「行数オーバー」扱いで入力が巻き戻る）。
		var style = window.getComputedStyle(field);
		var borderY = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
		var contentHeight = field.scrollHeight + borderY;

		var maxLinesAttr = field.getAttribute('data-max-lines');
		if (!maxLinesAttr) {
			field.style.height = contentHeight + 'px';
			return;
		}
		var lineHeight = parseFloat(style.lineHeight);
		if (isNaN(lineHeight)) {
			lineHeight = parseFloat(style.fontSize) * 1.4;
		}
		var paddingY = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
		var maxHeight = lineHeight * parseInt(maxLinesAttr, 10) + paddingY + borderY;
		if (contentHeight > maxHeight){
			field.style.height = maxHeight + 'px';
			field.style.overflowY = 'auto';
		} else {
			field.style.height = contentHeight + 'px';
			field.style.overflowY = 'hidden';
		}
	}

	$('textarea.rf-autosize').each(function(){
		autosizeField(this);
	});

	$(document).on('input', 'textarea.rf-autosize', function(){
		autosizeField(this);
	});

	$(window).on('resize', function(){
		$('textarea.rf-autosize').each(function(){
			autosizeField(this);
		});
	});

	$(targetSelector).each(function(){
		$(this).data('lastValidValue', $(this).val());
	});

	$(document).on('input change', '.rf-field input[aria-invalid="true"], .rf-field select[aria-invalid="true"]', function(){
		var $field = $(this);
		var describedBy = $field.attr('aria-describedby') || '';
		describedBy.split(' ').forEach(function(id){
			if(id.indexOf('_error') !== -1){
				$('#' + id).hide();
			}
		});
		$field.removeAttr('aria-invalid');
	});

	var trainingSelector = [
		'[name="training_am_1"]',
		'[name="training_am_2"]',
		'[name="training_am_3"]',
		'[name="training_pm_1"]',
		'[name="training_pm_2"]',
		'[name="training_pm_3"]'
	].join(',');
	var $trainingSectionErr = $('.rf-section-err');

	$(document).on('input', trainingSelector, function(){
		var anyFilled = $(trainingSelector).toArray().some(function(el){
			return $(el).val().trim() !== '';
		});
		if(anyFilled){
			$trainingSectionErr.hide();
		}
	});

	$(document).on('input', targetSelector, function(){
		var $field = $(this);
		var value = $(this).val();
		var lineCount = value.split(/\r\n|\r|\n/).length;
		if(value.length > maxLength){
			$field.val($field.data('lastValidValue'));
			autosizeField(this);
			$lineLimitMessage.text(lengthLimitMessageText).show();
			return;
		}
		autosizeField(this);
		if(lineCount > maxLines || exceedsVisibleLines(this)){
			$field.val($field.data('lastValidValue'));
			autosizeField(this);
			$lineLimitMessage.text(lineLimitMessageText).show();
			return;
		}
		$field.data('lastValidValue', value);
		$lineLimitMessage.hide().text('');
	});

	if ($wrapper.data('report-accessible')) {
		var errorSummary = document.getElementById('report-accessible-error-summary');
		if (errorSummary) {
			errorSummary.focus();
		}
	}
});
