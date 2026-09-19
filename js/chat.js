(function () {
	'use strict';

	var POLL_INTERVAL = 10000;
	var messages = document.getElementById('chat-messages');
	if (!messages) {
		return;
	}

	var pollSince = messages.getAttribute('data-poll-since') || '';
	var loginAdminUuid = messages.getAttribute('data-login-admin-uuid') || '';
	var userUuid = new URLSearchParams(window.location.search).get('i') || '';
	var csrfToken = (document.querySelector('input[name="_token"]') || {}).value || '';
	var knownMessageIds = {};
	var polling = false;

	messages.querySelectorAll('[data-chat-uuid]').forEach(function (message) {
		knownMessageIds[message.getAttribute('data-chat-uuid')] = true;
	});

	function appendPlainText(element, text) {
		text.split('\n').forEach(function (line, index) {
			if (index > 0) {
				element.appendChild(document.createElement('br'));
			}
			element.appendChild(document.createTextNode(line));
		});
	}

	function appendTextWithLinks(element, text) {
		var pattern = /(https?:\/\/[^\s<]+)/g;
		var lastIndex = 0;
		var match;

		while ((match = pattern.exec(text)) !== null) {
			appendPlainText(element, text.slice(lastIndex, match.index));
			var link = document.createElement('a');
			link.href = match[0];
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
			link.textContent = match[0];
			element.appendChild(link);
			lastIndex = match.index + match[0].length;
		}

		appendPlainText(element, text.slice(lastIndex));
	}

	function appendHiddenInput(form, name, value) {
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		form.appendChild(input);
	}

	function appendActions(item, message) {
		var actions = document.createElement('div');
		actions.className = 'chat-message-actions';

		if (!message.user_name && message.insert_uuid === loginAdminUuid && !/^https?:\/\/\S+$/u.test((message.chat_text || '').trim())) {
			var editForm = document.createElement('form');
			editForm.className = 'chat-edit-form';
			editForm.action = 'chat_edit.php';
			editForm.method = 'post';
			editForm.setAttribute('data-current-text', message.chat_text || '');
			appendHiddenInput(editForm, '_token', csrfToken);
			appendHiddenInput(editForm, 'chat_uuid', message.chat_uuid);
			appendHiddenInput(editForm, 'chat_text', '');
			var editButton = document.createElement('button');
			editButton.type = 'submit';
			editButton.className = 'chat-edit-btn';
			editButton.textContent = '修正';
			editForm.appendChild(editButton);
			editForm.addEventListener('submit', function (event) {
				if (!window.jigyodanChatEditSubmit(editForm)) {
					event.preventDefault();
				}
			});
			actions.appendChild(editForm);
		}

		var deleteForm = document.createElement('form');
		deleteForm.className = 'chat-delete-form';
		deleteForm.action = 'chat_delete.php';
		deleteForm.method = 'post';
		appendHiddenInput(deleteForm, '_token', csrfToken);
		appendHiddenInput(deleteForm, 'chat_uuid', message.chat_uuid);
		var deleteButton = document.createElement('button');
		deleteButton.type = 'submit';
		deleteButton.className = 'chat-delete-btn';
		deleteButton.textContent = '削除';
		deleteForm.appendChild(deleteButton);
		deleteForm.addEventListener('submit', function (event) {
			if (!window.confirm('削除します！')) {
				event.preventDefault();
			}
		});
		actions.appendChild(deleteForm);
		item.appendChild(actions);
	}

	function appendMessage(message) {
		if (!message.chat_uuid || knownMessageIds[message.chat_uuid]) {
			return;
		}

		var item = document.createElement('div');
		item.className = message.user_name ? 'chat-message chat-from-user' : 'chat-message chat-from-admin';
		item.setAttribute('data-chat-uuid', message.chat_uuid);

		var meta = document.createElement('div');
		meta.className = 'chat-meta';
		meta.textContent = (message.user_name || message.admin_name || '') + ' · ' + (message.insert_date || '');
		item.appendChild(meta);

		var bubble = document.createElement('div');
		bubble.className = 'chat-bubble';
		appendTextWithLinks(bubble, message.chat_text || '');
		item.appendChild(bubble);
		appendActions(item, message);

		messages.insertBefore(item, messages.firstChild);
		knownMessageIds[message.chat_uuid] = true;
	}

	function poll() {
		if (polling || document.hidden || pollSince === '') {
			return;
		}

		polling = true;
		fetch('chat_poll.php?i=' + encodeURIComponent(userUuid) + '&since=' + encodeURIComponent(pollSince), {
			credentials: 'same-origin'
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('chat poll failed');
				}
				return response.json();
			})
			.then(function (data) {
				(data.messages || []).forEach(appendMessage);
				if (data.since) {
					pollSince = data.since;
				}
			})
			.catch(function () {})
			.then(function () {
				polling = false;
			});
	}

	window.setInterval(poll, POLL_INTERVAL);
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			poll();
		}
	});
}());
