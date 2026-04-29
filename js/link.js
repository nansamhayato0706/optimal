$(function(){
	$('#insert').click(function(){
		$('#tbl').append('<tr><td><input type="text" style="ime-mode:inactive;" size="60" name="link_url[]" value=""></td><td><input type="text" style="ime-mode:active;" size="40" name="link_name[]" value=""></td><td><input type="text" style="ime-mode:disabled;" size="4" name="sort[]" value=""></td></tr>');
	});
});