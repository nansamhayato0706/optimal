$(function(){
	var $wrapper = $('#wrapper');

	function initMobileNavigation(){
		var menuButton = document.getElementById('mobile-nav-toggle');
		var links = document.getElementById('h_link_area');
		if (!menuButton || !links) {
			return;
		}

		$wrapper.addClass('mobile-nav-ready');
		menuButton.addEventListener('click', function(){
			var isOpen = $wrapper.toggleClass('mobile-nav-open').hasClass('mobile-nav-open');
			menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});
		links.querySelectorAll('a').forEach(function(link){
			link.addEventListener('click', function(){
				$wrapper.removeClass('mobile-nav-open');
				menuButton.setAttribute('aria-expanded', 'false');
			});
		});
		document.addEventListener('keydown', function(event){
			if (event.key === 'Escape') {
				$wrapper.removeClass('mobile-nav-open');
				menuButton.setAttribute('aria-expanded', 'false');
			}
		});
	}

	function initMobileFilters(){
		var cards = Array.prototype.slice.call(document.querySelectorAll('.report-daily-mobile-card'));
		if (cards.length === 0) {
			return;
		}

		['all', 'unsubmitted', 'pending', 'confirmed'].forEach(function(status){
			var count = status === 'all' ? cards.length : cards.filter(function(card){
				return card.getAttribute('data-daily-status') === status;
			}).length;
			var countTarget = document.querySelector('[data-daily-filter-count="' + status + '"]');
			if (countTarget) {
				countTarget.textContent = count;
			}
		});

		document.querySelectorAll('[data-daily-filter]').forEach(function(button){
			button.addEventListener('click', function(){
				collapseMobileReportDetails(null);
				var filter = button.getAttribute('data-daily-filter');
				document.querySelectorAll('[data-daily-filter]').forEach(function(item){
					item.classList.toggle('is-active', item === button);
				});
				cards.forEach(function(card){
					card.classList.toggle('is-hidden', filter !== 'all' && card.getAttribute('data-daily-status') !== filter);
				});
			});
		});
	}

	function setMobileReportDetailExpanded(details, expanded){
		var card = details.closest('.report-daily-mobile-card');
		var summary = details.querySelector('summary');
		details.open = expanded;
		if (card) {
			card.classList.toggle('is-expanded', expanded);
		}
		if (summary) {
			summary.textContent = expanded ? '内容を閉じる' : '内容を表示';
		}
	}

	function collapseMobileReportDetails(exceptDetails){
		document.querySelectorAll('.report-daily-mobile-card .report-mobile-details[open]').forEach(function(details){
			if (details !== exceptDetails) {
				setMobileReportDetailExpanded(details, false);
			}
		});
	}

	function scrollMobileReportCardIntoView(details){
		var card = details.closest('.report-daily-mobile-card');
		if (!card) {
			return;
		}
		window.requestAnimationFrame(function(){
			card.scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
	}

	function initMobileReportDetails(){
		document.querySelectorAll('.report-daily-mobile-card .report-mobile-details').forEach(function(details){
			setMobileReportDetailExpanded(details, details.open);
			details.addEventListener('toggle', function(){
				if (details.open) {
					collapseMobileReportDetails(details);
					scrollMobileReportCardIntoView(details);
				}
				setMobileReportDetailExpanded(details, details.open);
			});
		});
	}

	initMobileNavigation();
	initMobileFilters();
	initMobileReportDetails();

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
