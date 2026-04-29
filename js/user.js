$(function(){
	$(document).on('input change', '.user_edit_form .rf-field input[aria-invalid="true"], .user_edit_form .rf-field select[aria-invalid="true"]', function(){
		var $field = $(this);
		var describedBy = $field.attr('aria-describedby') || '';
		describedBy.split(' ').forEach(function(id){
			if(id.indexOf('_error') !== -1){
				$('#' + id).hide();
			}
		});
		$field.removeAttr('aria-invalid');
	});

	$('.user_edit_form input.date[name="birthday"]').datepicker({
		yearRange:'-100:+0',
		dateFormat:'yy-mm-dd',
		showButtonPanel:false,
		changeMonth:true,
		changeYear:true
	});
});
