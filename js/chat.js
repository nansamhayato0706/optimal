$(function(){
	var timer_id = setInterval(function(){
		if($('#chat_text').val() == ''){
			$('#timer').submit();
		}
	}, 60000);
});