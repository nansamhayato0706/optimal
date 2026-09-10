$(function(){
	$('.date').datepicker({
		yearRange:'2016:+1',
		dateFormat:'yy-mm-dd',
		showButtonPanel:false,
		changeMonth:true,
		changeYear:true,
		onSelect: function(){
			$('#frm').trigger('submit');
		}
	});

	$(document).on('click', '.mp-nav-btn', function(){
		var delta = $(this).hasClass('mp-nav-prev') ? -1 : 1;
		var $input = delta === -1
			? $(this).next('input.date')
			: $(this).prev('input.date');
		var val = $input.val();
		if (!val || !/^\d{4}-\d{2}-\d{2}$/.test(val)) { return; }
		var parts = val.split('-');
		var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
		d.setDate(d.getDate() + delta);
		var y = d.getFullYear();
		var m = String(d.getMonth() + 1).padStart(2, '0');
		var day = String(d.getDate()).padStart(2, '0');
		$input.val(y + '-' + m + '-' + day);
		$('#frm').trigger('submit');
	});
});
