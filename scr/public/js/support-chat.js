document.addEventListener('DOMContentLoaded', function () {
    var postForm = async function (url, values) {
        var body = new URLSearchParams(values);
        var response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        });
        var result;
        try {
            result = await response.json();
        } catch (error) {
            throw new Error('Không thể kết nối dịch vụ chat. Vui lòng tải lại trang.');
        }
        if (!response.ok) {
            throw new Error(result.error || 'Không thể hoàn tất yêu cầu chat.');
        }
        return result;
    };

    var formatTime = function (value) {
        var date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    };

    var appendMessage = function (container, message) {
        if (container.querySelector('[data-message-id="' + Number(message.id) + '"]')) {
            return;
        }
        var welcome = container.querySelector('.support-chat-welcome');
        if (welcome) {
            welcome.remove();
        }

        var item = document.createElement('article');
        item.className = 'support-message ' + (message.is_mine ? 'is-mine' : 'is-theirs');
        item.dataset.messageId = String(message.id);

        var sender = document.createElement('span');
        sender.className = 'support-message-sender';
        sender.textContent = message.is_mine ? 'Bạn' : (message.sender_name || 'Tư vấn viên');

        var content = document.createElement('p');
        content.textContent = message.message;

        var time = document.createElement('time');
        time.textContent = formatTime(message.created_at);

        item.appendChild(sender);
        item.appendChild(content);
        item.appendChild(time);
        container.appendChild(item);
        container.scrollTop = container.scrollHeight;
    };

    var customerWidget = document.querySelector('[data-support-widget]');
    if (customerWidget) {
        var baseUrl = customerWidget.dataset.baseUrl;
        var csrfToken = customerWidget.dataset.csrfToken;
        var panel = customerWidget.querySelector('[data-chat-panel]');
        var openButton = customerWidget.querySelector('[data-chat-open]');
        var closeButton = customerWidget.querySelector('[data-chat-close]');
        var newChatButton = customerWidget.querySelector('[data-new-chat]');
        var messageList = customerWidget.querySelector('[data-chat-messages]');
        var messageForm = customerWidget.querySelector('[data-chat-form]');
        var textarea = messageForm.querySelector('textarea');
        var statusLabel = customerWidget.querySelector('[data-chat-status]');
        var closedNotice = customerWidget.querySelector('[data-chat-closed-notice]');
        var errorNotice = customerWidget.querySelector('[data-chat-error]');
        var conversationId = 0;
        var lastMessageId = 0;
        var pollTimer = null;
        var sending = false;

        var setError = function (message) {
            errorNotice.textContent = message || '';
            errorNotice.hidden = !message;
        };
        var setConversationStatus = function (status) {
            var isOpen = status === 'open';
            statusLabel.textContent = isOpen ? 'Tư vấn viên sẽ phản hồi sớm' : 'Cuộc trò chuyện đã kết thúc';
            closedNotice.hidden = isOpen;
            messageForm.hidden = !isOpen;
            newChatButton.hidden = isOpen;
        };
        var loadMessages = async function () {
            if (!conversationId || panel.hidden) {
                return;
            }
            var result = await postForm(baseUrl + '/support/messages/' + conversationId, {
                csrf_token: csrfToken,
                after_id: String(lastMessageId)
            });
            result.messages.forEach(function (message) {
                appendMessage(messageList, message);
                lastMessageId = Math.max(lastMessageId, Number(message.id));
            });
            setConversationStatus(result.conversation.status);
        };
        var schedulePoll = function () {
            window.clearTimeout(pollTimer);
            if (!panel.hidden && conversationId) {
                pollTimer = window.setTimeout(poll, 3000);
            }
        };
        var poll = async function () {
            try {
                await loadMessages();
                setError('');
            } catch (error) {
                setError(error.message);
            }
            schedulePoll();
        };
        var startConversation = async function () {
            setError('');
            statusLabel.textContent = 'Đang kết nối...';
            try {
                var result = await postForm(baseUrl + '/support/start', { csrf_token: csrfToken });
                conversationId = Number(result.conversation_id);
                await loadMessages();
                schedulePoll();
            } catch (error) {
                statusLabel.textContent = 'Chưa thể kết nối';
                setError(error.message);
            }
        };
        var openChat = function () {
            panel.hidden = false;
            openButton.setAttribute('aria-expanded', 'true');
            if (conversationId) {
                loadMessages().then(schedulePoll).catch(function (error) {
                    setError(error.message);
                    schedulePoll();
                });
            } else {
                startConversation();
            }
        };
        var closeChat = function () {
            panel.hidden = true;
            openButton.setAttribute('aria-expanded', 'false');
            window.clearTimeout(pollTimer);
        };

        openButton.addEventListener('click', openChat);
        closeButton.addEventListener('click', closeChat);
        newChatButton.addEventListener('click', function () {
            conversationId = 0;
            lastMessageId = 0;
            messageList.textContent = '';
            var welcome = document.createElement('p');
            welcome.className = 'support-chat-welcome';
            welcome.textContent = 'Xin chào! Hãy gửi câu hỏi, chúng tôi sẽ phản hồi sớm nhất.';
            messageList.appendChild(welcome);
            startConversation();
        });
        messageForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            var message = textarea.value.trim();
            if (!message || sending || !conversationId) {
                return;
            }
            sending = true;
            textarea.disabled = true;
            setError('');
            try {
                var result = await postForm(baseUrl + '/support/send/' + conversationId, {
                    csrf_token: csrfToken,
                    message: message
                });
                appendMessage(messageList, result.message);
                lastMessageId = Math.max(lastMessageId, Number(result.message.id));
                textarea.value = '';
            } catch (error) {
                setError(error.message);
                if (error.message.indexOf('đã kết thúc') !== -1) {
                    setConversationStatus('closed');
                }
            } finally {
                sending = false;
                textarea.disabled = false;
                schedulePoll();
            }
        });
    }

    var adminInbox = document.querySelector('[data-support-admin]');
    if (adminInbox) {
        var adminBaseUrl = adminInbox.dataset.baseUrl;
        var adminCsrfToken = adminInbox.dataset.csrfToken;
        var inboxDataUrl = adminInbox.dataset.inboxDataUrl;
        var inboxUrl = adminInbox.dataset.inboxUrl;
        var selectedConversationId = Number(adminInbox.dataset.conversationId);
        var adminMessageList = adminInbox.querySelector('[data-chat-messages]');
        var adminForm = adminInbox.querySelector('[data-chat-form]');
        var adminTextarea = adminForm ? adminForm.querySelector('textarea') : null;
        var adminStatus = adminInbox.querySelector('[data-chat-status]');
        var adminClosedNotice = adminInbox.querySelector('[data-chat-closed-notice]');
        var adminCloseButton = adminInbox.querySelector('[data-close-chat]');
        var inboxList = adminInbox.querySelector('[data-chat-inbox-list]');
        var inboxCount = adminInbox.querySelector('[data-inbox-count]');
        var adminError = document.createElement('p');
        var adminLastMessageId = Number(adminInbox.dataset.lastMessageId) || 0;
        var adminPollTimer = null;
        var inboxPollTimer = null;
        var adminSending = false;
        adminError.className = 'support-chat-error';
        adminError.setAttribute('role', 'status');
        adminError.hidden = true;
        if (adminForm) {
            adminForm.parentNode.insertBefore(adminError, adminForm);
        } else {
            adminInbox.prepend(adminError);
        }

        var updateAdminStatus = function (status) {
            var isOpen = status === 'open';
            if (adminStatus) {
                adminStatus.textContent = isOpen ? 'Đang hỗ trợ' : 'Đã kết thúc';
                adminStatus.classList.toggle('is-open', isOpen);
                adminStatus.classList.toggle('is-closed', !isOpen);
            }
            if (adminForm) {
                adminForm.hidden = !isOpen;
            }
            if (adminClosedNotice) {
                adminClosedNotice.hidden = isOpen;
            }
            if (adminCloseButton) {
                adminCloseButton.hidden = !isOpen;
            }
        };
        var refreshAdminMessages = async function () {
            if (!selectedConversationId || !adminMessageList) {
                return;
            }
            var result = await postForm(adminBaseUrl + '/support/messages/' + selectedConversationId, {
                csrf_token: adminCsrfToken,
                after_id: String(adminLastMessageId)
            });
            result.messages.forEach(function (message) {
                appendMessage(adminMessageList, message);
                adminLastMessageId = Math.max(adminLastMessageId, Number(message.id));
            });
            updateAdminStatus(result.conversation.status);
        };
        var scheduleAdminPoll = function () {
            window.clearTimeout(adminPollTimer);
            if (selectedConversationId && adminMessageList) {
                adminPollTimer = window.setTimeout(pollAdminMessages, 3000);
            }
        };
        var pollAdminMessages = async function () {
            try {
                await refreshAdminMessages();
                adminError.hidden = true;
            } catch (error) {
                adminError.textContent = error.message;
                adminError.hidden = false;
            }
            scheduleAdminPoll();
        };
        var renderInbox = function (conversations) {
            inboxList.textContent = '';
            inboxCount.textContent = conversations.length + ' hội thoại';
            if (!conversations.length) {
                var empty = document.createElement('p');
                empty.className = 'support-empty-inbox';
                empty.textContent = 'Chưa có cuộc trò chuyện nào.';
                inboxList.appendChild(empty);
                return;
            }
            conversations.forEach(function (conversation) {
                var link = document.createElement('a');
                link.className = 'support-inbox-item' +
                    (Number(conversation.id) === selectedConversationId ? ' is-active' : '');
                link.dataset.conversationLink = String(conversation.id);
                link.href = inboxUrl + '?conversation_id=' + encodeURIComponent(conversation.id);

                var avatar = document.createElement('span');
                avatar.className = 'support-inbox-avatar';
                avatar.textContent = String(conversation.customer_name || '?').trim().charAt(0).toLocaleUpperCase('vi-VN');

                var copy = document.createElement('span');
                copy.className = 'support-inbox-copy';
                var name = document.createElement('strong');
                name.textContent = conversation.customer_name || 'Khách hàng';
                var preview = document.createElement('small');
                preview.textContent = conversation.last_message || 'Chưa có tin nhắn';
                var meta = document.createElement('small');
                meta.className = 'support-inbox-meta';
                meta.textContent = (conversation.status === 'open' ? 'Đang mở' : 'Đã kết thúc') +
                    ' · ' + formatTime(conversation.last_message_at);
                copy.appendChild(name);
                copy.appendChild(preview);
                copy.appendChild(meta);

                link.appendChild(avatar);
                link.appendChild(copy);
                if (Number(conversation.unread_count) > 0) {
                    var unread = document.createElement('span');
                    unread.className = 'support-unread-count';
                    unread.textContent = String(conversation.unread_count);
                    link.appendChild(unread);
                }
                inboxList.appendChild(link);
            });
        };
        var refreshInbox = async function () {
            var result = await postForm(inboxDataUrl, { csrf_token: adminCsrfToken });
            renderInbox(result.conversations);
        };
        var scheduleInboxPoll = function () {
            window.clearTimeout(inboxPollTimer);
            inboxPollTimer = window.setTimeout(pollInbox, 5000);
        };
        var pollInbox = async function () {
            try {
                await refreshInbox();
            } catch (error) {
                adminError.textContent = error.message;
                adminError.hidden = false;
            }
            scheduleInboxPoll();
        };

        if (adminForm && selectedConversationId) {
            adminForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                var message = adminTextarea.value.trim();
                if (!message || adminSending) {
                    return;
                }
                adminSending = true;
                adminTextarea.disabled = true;
                adminError.hidden = true;
                try {
                    var result = await postForm(adminBaseUrl + '/support/send/' + selectedConversationId, {
                        csrf_token: adminCsrfToken,
                        message: message
                    });
                    appendMessage(adminMessageList, result.message);
                    adminLastMessageId = Math.max(adminLastMessageId, Number(result.message.id));
                    adminTextarea.value = '';
                    await refreshInbox();
                } catch (error) {
                    adminError.textContent = error.message;
                    adminError.hidden = false;
                } finally {
                    adminSending = false;
                    adminTextarea.disabled = false;
                    scheduleAdminPoll();
                }
            });
        }
        if (adminCloseButton && selectedConversationId) {
            adminCloseButton.addEventListener('click', async function () {
                adminCloseButton.disabled = true;
                adminError.hidden = true;
                try {
                    var result = await postForm(adminBaseUrl + '/support/close/' + selectedConversationId, {
                        csrf_token: adminCsrfToken
                    });
                    updateAdminStatus(result.conversation.status);
                    await refreshInbox();
                } catch (error) {
                    adminError.textContent = error.message;
                    adminError.hidden = false;
                } finally {
                    adminCloseButton.disabled = false;
                }
            });
        }

        if (selectedConversationId && adminMessageList) {
            scheduleAdminPoll();
        }
        scheduleInboxPoll();
    }
});
