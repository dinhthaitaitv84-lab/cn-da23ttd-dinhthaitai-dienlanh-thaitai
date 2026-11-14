<?php
$baseUrl = BASE_URL;
$conversationId = (int) ($conversation['id'] ?? 0);
$lastMessageId = 0;
if (!empty($messages)) {
    $lastMessage = end($messages);
    $lastMessageId = (int) ($lastMessage['id'] ?? 0);
}
?>
<div class="admin-heading support-page-heading">
    <div>
        <h1>Hỗ trợ khách hàng</h1>
        <p>Trò chuyện trực tiếp và phản hồi các yêu cầu hỗ trợ.</p>
    </div>
</div>

<section class="support-admin-layout"
    data-support-admin
    data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>"
    data-csrf-token="<?= htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8') ?>"
    data-inbox-data-url="<?= htmlspecialchars($baseUrl . '/support/inboxData', ENT_QUOTES, 'UTF-8') ?>"
    data-inbox-url="<?= htmlspecialchars($baseUrl . '/support/inbox', ENT_QUOTES, 'UTF-8') ?>"
    data-conversation-id="<?= $conversationId ?>"
    data-current-user-id="<?= (int) $currentUserId ?>"
    data-last-message-id="<?= $lastMessageId ?>">
    <aside class="support-inbox">
        <div class="support-inbox-heading">
            <div>
                <h2>Cuộc trò chuyện</h2>
                <p data-inbox-count><?= count($conversations) ?> hội thoại</p>
            </div>
            <span class="support-live-indicator"><i></i> Trực tuyến</span>
        </div>
        <div class="support-inbox-list" data-chat-inbox-list>
            <?php if (empty($conversations)): ?>
                <p class="support-empty-inbox">Chưa có cuộc trò chuyện nào.</p>
            <?php else: ?>
                <?php foreach ($conversations as $inboxConversation): ?>
                    <?php
                    $isSelected = (int) $inboxConversation['id'] === $conversationId;
                    $statusText = $inboxConversation['status'] === 'open' ? 'Đang mở' : 'Đã kết thúc';
                    ?>
                    <a class="support-inbox-item <?= $isSelected ? 'is-active' : '' ?>"
                        href="<?= htmlspecialchars($baseUrl . '/support/inbox?conversation_id=' . (int) $inboxConversation['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-conversation-link="<?= (int) $inboxConversation['id'] ?>">
                        <span class="support-inbox-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($inboxConversation['customer_name'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="support-inbox-copy">
                            <strong><?= htmlspecialchars($inboxConversation['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($inboxConversation['last_message'] ?? 'Chưa có tin nhắn', ENT_QUOTES, 'UTF-8') ?></small>
                            <small class="support-inbox-meta">
                                <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                · <?= htmlspecialchars(date('d/m H:i', strtotime($inboxConversation['last_message_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </small>
                        </span>
                        <?php if ((int) $inboxConversation['unread_count'] > 0): ?>
                            <span class="support-unread-count"><?= (int) $inboxConversation['unread_count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <section class="support-admin-thread" aria-label="Nội dung trò chuyện">
        <?php if ($conversation): ?>
            <?php
            $conversationOpen = $conversation['status'] === 'open';
            $statusText = $conversationOpen ? 'Đang hỗ trợ' : 'Đã kết thúc';
            ?>
            <header class="support-thread-heading">
                <div class="support-thread-customer">
                    <span class="support-inbox-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($conversation['customer_name'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span>
                        <strong><?= htmlspecialchars($conversation['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars($conversation['customer_phone'] ?: 'Khách hàng', ENT_QUOTES, 'UTF-8') ?></small>
                    </span>
                </div>
                <div class="support-thread-actions">
                    <span class="support-thread-status <?= $conversationOpen ? 'is-open' : 'is-closed' ?>" data-chat-status><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($conversationOpen): ?>
                        <button class="button button-outline" type="button" data-close-chat>Kết thúc chat</button>
                    <?php endif; ?>
                </div>
            </header>
            <div class="support-chat-messages support-admin-messages" data-chat-messages aria-live="polite">
                <?php foreach ($messages as $message): ?>
                    <?php $isMine = (int) $message['sender_id'] === (int) $currentUserId; ?>
                    <article class="support-message <?= $isMine ? 'is-mine' : 'is-theirs' ?>" data-message-id="<?= (int) $message['id'] ?>">
                        <span class="support-message-sender"><?= $isMine ? 'Bạn' : htmlspecialchars($conversation['customer_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <p><?= htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8') ?></p>
                        <time datetime="<?= htmlspecialchars($message['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('H:i d/m', strtotime($message['created_at'])), ENT_QUOTES, 'UTF-8') ?></time>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="support-chat-notice" data-chat-closed-notice <?= $conversationOpen ? 'hidden' : '' ?>>Cuộc trò chuyện đã kết thúc.</p>
            <form class="support-chat-compose" data-chat-form <?= $conversationOpen ? '' : 'hidden' ?>>
                <label class="sr-only" for="support-admin-message">Tin nhắn phản hồi</label>
                <textarea id="support-admin-message" name="message" rows="2" maxlength="2000" placeholder="Nhập nội dung phản hồi..." required></textarea>
                <button class="button" type="submit">Gửi phản hồi</button>
            </form>
        <?php else: ?>
            <div class="support-thread-empty">
                <span aria-hidden="true">✦</span>
                <h2>Sẵn sàng hỗ trợ khách hàng</h2>
                <p>Chọn một cuộc trò chuyện bên trái để xem và phản hồi tin nhắn.</p>
            </div>
        <?php endif; ?>
    </section>
</section>
