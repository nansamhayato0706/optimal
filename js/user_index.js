$(function(){
	var $wrapper = $('#wrapper');
	if ($wrapper.length === 0) {
		return;
	}

	var statusUrl = $wrapper.data('status-url') || 'user_status.php';
	var contactDetailUrl = $wrapper.data('contact-detail-url') || 'contact_detail.php';
	var contactUpdateUrl = $wrapper.data('contact-update-url') || 'contact_update.php';
	var defaultDummyImage = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMjAiIGhlaWdodD0iMjQwIiB2aWV3Qm94PSIwIDAgMzIwIDI0MCIgcm9sZT0iaW1nIiBhcmlhLWxhYmVsPSJObyBpbWFnZSI+PHJlY3Qgd2lkdGg9IjMyMCIgaGVpZ2h0PSIyNDAiIGZpbGw9IiNmM2Y0ZjYiLz48cGF0aCBkPSJNNjQgMTc2bDU2LTY0IDQwIDQ4IDMyLTMyIDY0IDQ4IiBmaWxsPSJub25lIiBzdHJva2U9IiM5Y2EzYWYiIHN0cm9rZS13aWR0aD0iMTIiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz48Y2lyY2xlIGN4PSIyMTYiIGN5PSI4MCIgcj0iMjQiIGZpbGw9IiNjYmQ1ZTEiLz48dGV4dCB4PSIxNjAiIHk9IjIxMiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmaWxsPSIjNmI3MjgwIj5ObyBpbWFnZTwvdGV4dD48L3N2Zz4=';
	var dummyImage = $wrapper.data('dummy-image') || defaultDummyImage;
	var modal = null;
	var overlay = null;
	var form = null;
	var currentUserUUID = '';

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
	}

	function updateContactCell(data) {
		var row = null;
		var td = null;

		if (!data) {
			return;
		}

		row = document.querySelector('tr[data-user-uuid="' + (data.user_uuid || currentUserUUID) + '"]');
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
	}

	function loadContactDetail(contactUuid, userUUID, userName, fallbackHref) {
		currentUserUUID = userUUID;
		fetch(contactDetailUrl + '?i=' + encodeURIComponent(contactUuid))
			.then(function(response) {
				return response.json();
			})
			.then(function(data) {
				if (data.error) {
					throw new Error(data.error);
				}
				data.user_name = userName;
				openModal(data);
			})
			.catch(function() {
				if (fallbackHref) {
					window.location.href = fallbackHref;
				}
			});
	}

	function submitContactForm() {
		var formData = new FormData(form);
		fetch(contactUpdateUrl, {
			method: 'POST',
			body: formData
		})
			.then(function(response) {
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

				updateContactCell(result.body || {});
				closeModal();
				setLoading(false);
			})
			.catch(function() {
				document.getElementById('contact-modal-general-error').textContent = '更新に失敗しました。時間をおいて再度お試しください。';
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
					var row = document.querySelector('tr[data-user-uuid="' + user.user_uuid + '"]');
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

	window.setInterval(updateStatus, 10000);
});
