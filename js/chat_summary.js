$(function(){
	// 月が変わったら（ポップアップ選択・前月/翌月ボタン）、そのまま再表示する
	$(document).on('change', '#frm .month-picker', function(){
		$('#frm').submit();
	});
});
