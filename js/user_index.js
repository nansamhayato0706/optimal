$(function(){
	var $wrapper = $('#wrapper');
	if ($wrapper.length === 0) {
		return;
	}

	var statusUrl = $wrapper.data('status-url') || 'user_status.php';
	var contactDetailUrl = $wrapper.data('contact-detail-url') || 'contact_detail.php';
	var contactUpdateUrl = $wrapper.data('contact-update-url') || 'contact_update.php';
	var settingsShell = document.querySelector('.user-list-header-shell');
	var settingsToggle = document.getElementById('user-list-settings-toggle');
	var settingsPanel = document.getElementById('user-list-settings-panel');
	if (settingsShell && settingsToggle && settingsPanel) {
		function setSettingsOpen(isOpen) {
			settingsShell.classList.toggle('is-open', isOpen);
			settingsToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			settingsToggle.setAttribute('aria-label', isOpen ? '表示条件を閉じる' : '表示条件を開く');
			settingsPanel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
		}
		settingsToggle.addEventListener('click', function() {
			setSettingsOpen(!settingsShell.classList.contains('is-open'));
		});
		document.addEventListener('click', function(event) {
			if (settingsShell.classList.contains('is-open') && !settingsShell.contains(event.target)) {
				setSettingsOpen(false);
			}
		});
		document.addEventListener('keydown', function(event) {
			if (event.key === 'Escape' && settingsShell.classList.contains('is-open')) {
				setSettingsOpen(false);
				settingsToggle.focus();
			}
		});
	}

	var originalTitle = document.title;
	var flashTimer = null;
	var flashShown = false;
	var POLL_INTERVAL_VISIBLE = 10000;
	var POLL_INTERVAL_HIDDEN  = 30000;
	var POLL_CHECK_INTERVAL   = 5000;
	var lastPollAt = Date.now();

	var originalFaviconLink = document.querySelector('link[rel~="icon"]');
	var originalFaviconHref = originalFaviconLink ? (originalFaviconLink.getAttribute('href') || '') : '';
	var alertFaviconHref = 'data:image/svg+xml,' + encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">' +
		'<circle cx="32" cy="32" r="30" fill="#dc2626" stroke="#7f1d1d" stroke-width="4"/>' +
		'<text x="32" y="44" text-anchor="middle" font-family="Arial,sans-serif" font-size="38" font-weight="900" fill="#ffffff">!</text>' +
		'</svg>'
	);
	// 元のファビコンが無い場合は透明SVGをアイドル状態として使う。
	// link要素を削除してもブラウザがキャッシュ済みファビコンを表示し続けるため、必ず別のhrefをセットする必要がある。
	var idleFaviconHref = originalFaviconHref || ('data:image/svg+xml,' + encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><rect width="16" height="16" fill="rgba(0,0,0,0)"/></svg>'
	));

	function setFavicon(href) {
		var link = document.querySelector('link[rel~="icon"]');
		if (!link) {
			link = document.createElement('link');
			link.rel = 'icon';
			document.head.appendChild(link);
		}
		if (link.getAttribute('href') !== href) {
			link.setAttribute('href', href);
		}
	}

	function restoreFavicon() {
		setFavicon(idleFaviconHref);
	}

	function countInquiries() {
		return document.querySelectorAll('td.user_contact_3').length;
	}

	function countUnconfirmedContacts() {
		return document.querySelectorAll('td[data-col="contact"] .user-status-link').length;
	}

	function countActiveEmergencies() {
		return document.querySelectorAll('td.user_contact_5').length;
	}

	function countUnreadChats() {
		return document.querySelectorAll('td.user_chat').length;
	}

	function countSubmittedReports() {
		return document.querySelectorAll('td.user_report').length;
	}

	function mobileRowMatches(row, filter) {
		if (filter === 'urgent') {
			return row.querySelector('td.user_contact_5') !== null;
		}
		if (filter === 'inquiry') {
			return row.querySelector('td.user_contact_3') !== null;
		}
		if (filter === 'unconfirmed') {
			return row.querySelector('td[data-col="contact"] .user-status-link') !== null;
		}
		if (filter === 'report') {
			return row.querySelector('td.user_report') !== null;
		}
		if (filter === 'chat') {
			return row.querySelector('td.user_chat') !== null;
		}
		return true;
	}

	function updateMobileFilters() {
		var activeButton = document.querySelector('.user-mobile-filters button.is-active');
		var activeFilter = activeButton ? activeButton.getAttribute('data-mobile-filter') : 'all';
		var counts = {
			urgent: countActiveEmergencies(),
			inquiry: countInquiries(),
			unconfirmed: countUnconfirmedContacts(),
			report: countSubmittedReports(),
			chat: countUnreadChats()
		};

		document.querySelectorAll('[data-mobile-filter-count]').forEach(function(count) {
			var filter = count.getAttribute('data-mobile-filter-count');
			count.textContent = counts[filter] || 0;
		});

		document.querySelectorAll('.user_list tr[data-user-uuid]').forEach(function(row) {
			row.classList.toggle('has-mobile-urgent', mobileRowMatches(row, 'urgent'));
			row.classList.toggle('has-mobile-inquiry', mobileRowMatches(row, 'inquiry'));
			row.classList.toggle('has-mobile-unconfirmed', mobileRowMatches(row, 'unconfirmed'));
			row.classList.toggle('has-mobile-report', mobileRowMatches(row, 'report'));
			row.classList.toggle('has-mobile-chat', mobileRowMatches(row, 'chat'));
			row.hidden = !mobileRowMatches(row, activeFilter);
		});
	}

	function setMobileRowExpanded(row, expanded) {
		row.classList.toggle('is-expanded', expanded);
		row.setAttribute('aria-expanded', expanded ? 'true' : 'false');
	}

	function collapseMobileUserRows(exceptRow) {
		document.querySelectorAll('.user_list tr[data-user-uuid].is-expanded').forEach(function(row) {
			if (row !== exceptRow) {
				setMobileRowExpanded(row, false);
			}
		});
	}

	function scrollMobileUserRowIntoView(row) {
		if (!window.matchMedia('(max-width: 640px)').matches) {
			return;
		}
		window.requestAnimationFrame(function() {
			row.scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
	}

	function initMobileUserList() {
		document.querySelectorAll('.user_list tr[data-user-uuid]').forEach(function(row) {
			row.tabIndex = 0;
			row.setAttribute('aria-expanded', 'false');
			row.addEventListener('click', function(event) {
				if (event.target.closest('a, button, input, select, textarea, label')) {
					return;
				}
				var expand = !row.classList.contains('is-expanded');
				collapseMobileUserRows(row);
				setMobileRowExpanded(row, expand);
				if (expand) {
					scrollMobileUserRowIntoView(row);
				}
			});
			row.addEventListener('keydown', function(event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					row.click();
				}
			});
		});

		document.querySelectorAll('.user-mobile-filters button').forEach(function(button) {
			button.addEventListener('click', function() {
				collapseMobileUserRows(null);
				document.querySelectorAll('.user-mobile-filters button').forEach(function(item) {
					item.classList.toggle('is-active', item === button);
				});
				updateMobileFilters();
			});
		});
	}

	function startFlash(label, urgent) {
		var interval = urgent ? 400 : 800;
		var flashTitle = label + ' | ' + originalTitle;
		if (flashTimer) {
			document.title = flashTitle;
			setFavicon(alertFaviconHref);
			return;
		}
		flashShown = true;
		document.title = flashTitle;
		setFavicon(alertFaviconHref);
		flashTimer = window.setInterval(function() {
			flashShown = !flashShown;
			document.title = flashShown ? flashTitle : originalTitle;
		}, interval);
	}

	function stopFlash() {
		if (flashTimer) {
			window.clearInterval(flashTimer);
			flashTimer = null;
		}
		flashShown = false;
		document.title = originalTitle;
		restoreFavicon();
	}

	var lastInquiryCount = -1;
	var lastEmergencyCount = -1;
	var lastChatCount = -1;
	var activeNotification = null;
	var audioCtx = null;
	var inquiryAckStorageKey = 'zaitaku_user_inquiry_ack_v1';
	var acknowledgedInquiryIds = loadAcknowledgedInquiryIds();

	function loadAcknowledgedInquiryIds() {
		try {
			var raw = window.localStorage ? window.localStorage.getItem(inquiryAckStorageKey) : '';
			var parsed = raw ? JSON.parse(raw) : [];
			var map = {};
			if (Array.isArray(parsed)) {
				parsed.forEach(function(id) {
					if (id) {
						map[String(id)] = true;
					}
				});
			}
			return map;
		} catch (e) {
			return {};
		}
	}

	function saveAcknowledgedInquiryIds() {
		try {
			if (!window.localStorage) {
				return;
			}
			var ids = Object.keys(acknowledgedInquiryIds).slice(-200);
			window.localStorage.setItem(inquiryAckStorageKey, JSON.stringify(ids));
		} catch (e) {}
	}

	function contactUuidFromLink(link) {
		var href = link ? (link.getAttribute('href') || '') : '';
		var match = href.match(/[?&]i=([^&]+)/);
		return match ? decodeURIComponent(match[1]) : '';
	}

	function getPendingInquiryIds() {
		var ids = [];
		document.querySelectorAll('td.user_contact_3').forEach(function(td) {
			var id = contactUuidFromLink(td.querySelector('.user-status-link'));
			if (id) {
				ids.push(id);
			}
		});
		return ids;
	}

	function acknowledgeInquiryIds(ids) {
		var changed = false;
		ids.forEach(function(id) {
			if (id && !acknowledgedInquiryIds[id]) {
				acknowledgedInquiryIds[id] = true;
				changed = true;
			}
		});
		if (changed) {
			saveAcknowledgedInquiryIds();
		}
	}

	function countUnacknowledgedInquiryIds(ids) {
		var count = 0;
		ids.forEach(function(id) {
			if (id && !acknowledgedInquiryIds[id]) {
				count++;
			}
		});
		return count;
	}

	function notificationsSupported() {
		return ('Notification' in window);
	}

	function notificationsGranted() {
		return notificationsSupported() && Notification.permission === 'granted';
	}

	function updateNotifyToggleButton() {
		var btn = document.getElementById('notify-toggle');
		if (!btn) {
			return;
		}
		if (!notificationsSupported()) {
			btn.hidden = true;
			return;
		}
		if (Notification.permission === 'granted') {
			btn.hidden = true;
		} else if (Notification.permission === 'denied') {
			btn.hidden = false;
			btn.disabled = true;
			btn.textContent = '通知ブロック中';
			btn.title = 'ブラウザ設定で本サイトの通知を許可してください';
		} else {
			btn.hidden = false;
			btn.disabled = false;
			btn.textContent = '通知を有効にする';
			btn.title = 'クリックしてOS通知を有効化';
		}
	}

	function requestNotificationPermission() {
		if (!notificationsSupported()) {
			return;
		}
		var result = Notification.requestPermission(function() {
			updateNotifyToggleButton();
		});
		if (result && typeof result.then === 'function') {
			result.then(updateNotifyToggleButton);
		}
	}

	function fireInquiryNotification(count) {
		if (!notificationsGranted()) {
			return;
		}
		try {
			if (activeNotification) {
				activeNotification.close();
				activeNotification = null;
			}
			var notification = new Notification('問い合わせが届きました', {
				body: '未確認の問い合わせが' + count + '件あります。クリックで一覧へ。',
				icon: alertFaviconHref,
				tag:  'zaitaku-inquiry',
				renotify: true,
				requireInteraction: false
			});
			notification.onclick = function() {
				try {
					window.focus();
					if (window.parent && window.parent !== window) {
						window.parent.focus();
					}
				} catch (e) {}
				notification.close();
			};
			activeNotification = notification;
		} catch (e) {}
	}

	function fireChatNotification(count) {
		if (!notificationsGranted()) {
			return;
		}
		try {
			if (activeNotification) {
				activeNotification.close();
				activeNotification = null;
			}
			var notification = new Notification('チャットが届きました', {
				body: '未読のチャットが' + count + '件あります。クリックで一覧へ。',
				icon: alertFaviconHref,
				tag:  'zaitaku-chat',
				renotify: true,
				requireInteraction: false
			});
			notification.onclick = function() {
				try {
					window.focus();
					if (window.parent && window.parent !== window) {
						window.parent.focus();
					}
				} catch (e) {}
				notification.close();
			};
			activeNotification = notification;
		} catch (e) {}
	}

	function fireEmergencyNotification(count) {
		if (!notificationsGranted()) {
			return;
		}
		try {
			if (activeNotification) {
				activeNotification.close();
				activeNotification = null;
			}
			var notification = new Notification('🚨 緊急通知が届きました', {
				body: '緊急通知が' + count + '件あります。すぐに確認してください。',
				icon: alertFaviconHref,
				tag:  'zaitaku-emergency',
				renotify: true,
				requireInteraction: true
			});
			notification.onclick = function() {
				try {
					window.focus();
					if (window.parent && window.parent !== window) {
						window.parent.focus();
					}
				} catch (e) {}
				notification.close();
			};
			activeNotification = notification;
		} catch (e) {}
	}

	function initAudioContext() {
		try {
			var AudioContextClass = window.AudioContext || window.webkitAudioContext;
			if (!AudioContextClass) {
				return;
			}
			if (!audioCtx) {
				audioCtx = new AudioContextClass();
			}
			if (audioCtx.state === 'suspended') {
				audioCtx.resume();
			}
		} catch (e) {}
	}

	function playAlertSound() {
		if (!audioCtx || audioCtx.state !== 'running') {
			return;
		}
		try {
			var ctx = audioCtx;
			var beepCount = 0;
			function beep() {
				if (beepCount >= 3) {
					return;
				}
				var osc = ctx.createOscillator();
				var gain = ctx.createGain();
				osc.connect(gain);
				gain.connect(ctx.destination);
				osc.frequency.value = 880;
				gain.gain.setValueAtTime(0.3, ctx.currentTime);
				gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
				osc.start(ctx.currentTime);
				osc.stop(ctx.currentTime + 0.2);
				beepCount++;
				window.setTimeout(beep, 350);
			}
			beep();
		} catch (e) {}
	}

	function refreshUrgentNotification() {
		var emergencyCount = countActiveEmergencies();
		var pendingInquiryIds = getPendingInquiryIds();
		var inquiryCount = pendingInquiryIds.length;
		var unacknowledgedInquiryCount = countUnacknowledgedInquiryIds(pendingInquiryIds);
		var chatCount = countUnreadChats();
		var isFirstRun = lastInquiryCount < 0 && lastEmergencyCount < 0 && lastChatCount < 0;
		var wasEmergencyIncreased = !isFirstRun && emergencyCount > lastEmergencyCount;
		var wasInquiryIncreased = !isFirstRun && inquiryCount > lastInquiryCount;
		var wasChatIncreased = !isFirstRun && chatCount > lastChatCount;
		lastEmergencyCount = emergencyCount;
		lastInquiryCount = inquiryCount;
		lastChatCount = chatCount;

		if (emergencyCount > 0) {
			startFlash('🚨 緊急' + emergencyCount + '件', true);
			if (wasEmergencyIncreased) {
				fireEmergencyNotification(emergencyCount);
				playAlertSound();
			}
		} else if (unacknowledgedInquiryCount > 0 && document.hidden) {
			startFlash('🔴 問い合わせ' + unacknowledgedInquiryCount + '件', false);
			if (wasInquiryIncreased) {
				fireInquiryNotification(unacknowledgedInquiryCount);
			}
		} else if (chatCount > 0 && document.hidden) {
			startFlash('💬 チャット' + chatCount + '件', false);
			if (wasChatIncreased) {
				fireChatNotification(chatCount);
			}
		} else {
			if (!document.hidden && inquiryCount > 0) {
				acknowledgeInquiryIds(pendingInquiryIds);
			}
			stopFlash();
		}
	}
	var defaultDummyImage = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMjAiIGhlaWdodD0iMjQwIiB2aWV3Qm94PSIwIDAgMzIwIDI0MCIgcm9sZT0iaW1nIiBhcmlhLWxhYmVsPSJObyBpbWFnZSI+PHJlY3Qgd2lkdGg9IjMyMCIgaGVpZ2h0PSIyNDAiIGZpbGw9IiNmM2Y0ZjYiLz48cGF0aCBkPSJNNjQgMTc2bDU2LTY0IDQwIDQ4IDMyLTMyIDY0IDQ4IiBmaWxsPSJub25lIiBzdHJva2U9IiM5Y2EzYWYiIHN0cm9rZS13aWR0aD0iMTIiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz48Y2lyY2xlIGN4PSIyMTYiIGN5PSI4MCIgcj0iMjQiIGZpbGw9IiNjYmQ1ZTEiLz48dGV4dCB4PSIxNjAiIHk9IjIxMiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmaWxsPSIjNmI3MjgwIj5ObyBpbWFnZTwvdGV4dD48L3N2Zz4=';
	var dummyImage = $wrapper.data('dummy-image') || defaultDummyImage;
	var modal = null;
	var overlay = null;
	var form = null;
	var currentUserUUID = '';
	var rowIndex = {};

	document.querySelectorAll('tr[data-user-uuid]').forEach(function(row) {
		rowIndex[row.getAttribute('data-user-uuid')] = row;
	});

	function getUserRow(userUUID) {
		return rowIndex[userUUID] || null;
	}

	function getModalElements() {
		if (modal) {
			return true;
		}

		modal = document.getElementById('contact-modal');
		overlay = document.getElementById('contact-modal-overlay');
		form = document.getElementById('contact-modal-form');
		return !!(modal && overlay && form);
	}

	function clearErrors() {
		document.getElementById('contact-modal-confirm-error').textContent = '';
		document.getElementById('contact-modal-comment-error').textContent = '';
		document.getElementById('contact-modal-general-error').textContent = '';
	}

	function setLoading(loading) {
		document.getElementById('contact-modal-submit').disabled = loading;
		document.getElementById('contact-modal-cancel').disabled = loading;
	}

	function autosizeContactComment() {
		var field = document.getElementById('contact-modal-comment');
		var style = null;
		var borderY = 0;

		if (!field) {
			return;
		}

		field.style.height = 'auto';
		style = window.getComputedStyle(field);
		borderY = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
		field.style.height = (field.scrollHeight + borderY) + 'px';
	}

	function closeModal() {
		if (!getModalElements()) {
			return;
		}

		modal.hidden = true;
		overlay.hidden = true;
		form.reset();
		clearErrors();
		currentUserUUID = '';
	}

	function populateConfirmOptions(options, selectedValue) {
		var select = document.getElementById('contact-modal-confirm-div');
		select.innerHTML = '<option value="">選択してください</option>';

		if (!options) {
			return;
		}

		Object.keys(options).forEach(function(key) {
			var option = document.createElement('option');
			option.value = key;
			option.textContent = options[key];
			if (String(selectedValue) === String(key)) {
				option.selected = true;
			}
			select.appendChild(option);
		});
	}

	function openModal(data) {
		var image = null;

		if (!getModalElements()) {
			return;
		}

		document.getElementById('contact-modal-contact-uuid').value = data.contact_uuid || '';
		document.getElementById('contact-modal-user-name').textContent = data.user_name || '';
		document.getElementById('contact-modal-sent').textContent = (data.contact_date_label || '') + ' ' + (data.contact_label || '');
		image = document.getElementById('contact-modal-image');
		image.onerror = function() {
			this.onerror = null;
			this.src = dummyImage;
		};
		image.src = data.image_url || dummyImage;
		image.alt = data.contact_label || '連絡画像';
		populateConfirmOptions(data.confirm_options, data.confirm_div);
		document.getElementById('contact-modal-comment').value = data.comment || '';
		clearErrors();
		setLoading(false);
		modal.hidden = false;
		overlay.hidden = false;
		autosizeContactComment();
	}

	function updateContactCell(data) {
		var row = null;
		var td = null;

		if (!data) {
			return;
		}

		row = getUserRow(data.user_uuid || currentUserUUID);
		if (!row) {
			return;
		}

		td = row.querySelector('td[data-col="contact"]');
		if (!td) {
			return;
		}

		td.className = td.classList.contains('text-center') ? 'text-center' : '';
		if (data.contact_class) {
			data.contact_class.split(/\s+/).forEach(function(className) {
				if (className) {
					td.classList.add(className);
				}
			});
		}
		td.innerHTML = data.contact_html || '';
		refreshUrgentNotification();
	}

	function isSessionExpiredResponse(response) {
		var contentType = response.headers.get('content-type') || '';
		return response.redirected || contentType.indexOf('application/json') === -1;
	}

	function loadContactDetail(contactUuid, userUUID, userName, fallbackHref) {
		currentUserUUID = userUUID;
		fetch(contactDetailUrl + '?i=' + encodeURIComponent(contactUuid))
			.then(function(response) {
				if (isSessionExpiredResponse(response)) {
					throw new Error('session_expired');
				}
				return response.json();
			})
			.then(function(data) {
				if (data.error) {
					throw new Error(data.error);
				}
				data.user_name = userName;
				openModal(data);
			})
			.catch(function(error) {
				if (error && error.message === 'session_expired') {
					window.location.reload();
					return;
				}
				if (fallbackHref) {
					window.location.href = fallbackHref;
				}
			});
	}

	function submitContactForm() {
		var formData = new FormData(form);
		var submittedContactUuid = String(formData.get('contact_uuid') || '');
		fetch(contactUpdateUrl, {
			method: 'POST',
			body: formData
		})
			.then(function(response) {
				if (isSessionExpiredResponse(response)) {
					throw new Error('session_expired');
				}
				return response.json().then(function(data) {
					return {
						status: response.status,
						body: data
					};
				});
			})
			.then(function(result) {
				if (result.status >= 400) {
					if (result.body && result.body.errors) {
						document.getElementById('contact-modal-confirm-error').textContent = result.body.errors.confirm_div || '';
						document.getElementById('contact-modal-comment-error').textContent = result.body.errors.comment || '';
					} else {
						document.getElementById('contact-modal-general-error').textContent = '更新に失敗しました。時間をおいて再度お試しください。';
					}
					setLoading(false);
					return;
				}

				closeModal();
				setLoading(false);
				acknowledgeInquiryIds([submittedContactUuid]);
				updateContactCell(result.body);
				lastPollAt = 0;
				updateStatus();
			})
			.catch(function(error) {
				if (error && error.message === 'session_expired') {
					document.getElementById('contact-modal-general-error').textContent = 'セッションが切れました。F5 キーでページを再読み込みしてください。';
				} else {
					document.getElementById('contact-modal-general-error').textContent = '更新に失敗しました。時間をおいて再度お試しください。';
				}
				setLoading(false);
			});
	}

	function updateStatus() {
		fetch(statusUrl)
			.then(function(response) {
				if (response.status === 304) {
					return null;
				}
				return response.json();
			})
			.then(function(data) {
				if (!data || !data.users) {
					return;
				}

				data.users.forEach(function(user) {
					var row = getUserRow(user.user_uuid);
					if (!row) {
						return;
					}

					row.querySelectorAll('td[data-col]').forEach(function(td) {
						var className = '';
						var column = td.getAttribute('data-col');

						if (column === 'contact') {
							className = user.contact_class || '';
							td.innerHTML = user.contact_html;
						} else if (column === 'report') {
							className = user.report_class || '';
							td.innerHTML = user.report_html;
						} else if (column === 'chat') {
							className = user.chat_class || '';
							td.innerHTML = user.chat_html;
						}

						td.className = td.classList.contains('text-center') ? 'text-center' : '';
						if (className !== '') {
							td.className += (td.className ? ' ' : '') + className;
						}
					});
				});

				refreshUrgentNotification();
				updateMobileFilters();
			})
			.catch(function() {});
	}

	$(document).on('click', '.user-status-link', function(event) {
		var $link = $(this);
		var href = $link.attr('href') || '';
		var match = href.match(/[?&]i=([^&]+)/);
		var $row = $link.closest('tr[data-user-uuid]');

		if (!match) {
			return;
		}

		event.preventDefault();
		loadContactDetail(
			decodeURIComponent(match[1]),
			$row.attr('data-user-uuid') || '',
			$row.attr('data-user-name') || '',
			href
		);
	});

	if (getModalElements()) {
		$('#contact-modal-comment').on('input', autosizeContactComment);
		$(window).on('resize', autosizeContactComment);
		$('#contact-modal-close, #contact-modal-cancel').on('click', closeModal);
		$(overlay).on('click', closeModal);
		$(document).on('keydown', function(event) {
			if (event.key === 'Escape' && modal && !modal.hidden) {
				closeModal();
			}
		});
		$(form).on('submit', function(event) {
			event.preventDefault();
			clearErrors();
			setLoading(true);
			submitContactForm();
		});
	}

	function tickStatus() {
		var now = Date.now();
		var interval = document.hidden ? POLL_INTERVAL_HIDDEN : POLL_INTERVAL_VISIBLE;
		if (now - lastPollAt < interval) {
			return;
		}
		lastPollAt = now;
		updateStatus();
	}

	window.setInterval(tickStatus, POLL_CHECK_INTERVAL);
	document.addEventListener('visibilitychange', function() {
		if (!document.hidden) {
			lastPollAt = Date.now();
			updateStatus();
			if (activeNotification) {
				activeNotification.close();
				activeNotification = null;
			}
		}
		refreshUrgentNotification();
	});
	window.addEventListener('storage', function(event) {
		if (event.key === inquiryAckStorageKey) {
			acknowledgedInquiryIds = loadAcknowledgedInquiryIds();
			refreshUrgentNotification();
		}
	});

	$('#notify-toggle').on('click', requestNotificationPermission);
	updateNotifyToggleButton();
	refreshUrgentNotification();
	initMobileUserList();
	updateMobileFilters();
	document.addEventListener('click', initAudioContext, { once: true });
	document.addEventListener('keydown', initAudioContext, { once: true });
});
