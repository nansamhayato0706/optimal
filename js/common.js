$(function(){
	$(document).on('click', ':button', function(){
		var href = $(this).attr('data-href');
		if(typeof href !== 'undefined' && href !== false){
			location.href = $(this).attr('data-href');
		}
		return false;
	});
});