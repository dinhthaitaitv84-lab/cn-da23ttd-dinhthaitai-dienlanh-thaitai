<?php
require_once __DIR__ . '/../../config/database.php';

class SupportModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        if (!($this->conn instanceof PDO)) {
            throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
        }
    }

    public function createOrGetOpenConversation($customerId)
    {
        $this->conn->beginTransaction();
        try {
            $customerStatement = $this->conn->prepare(
                'SELECT id FROM users WHERE id = :id AND role = :role AND status = 1 FOR UPDATE'
            );
            $customerStatement->execute([':id' => (int) $customerId, ':role' => 'customer']);
            if (!$customerStatement->fetchColumn()) {
                throw new RuntimeException('Không tìm thấy tài khoản khách hàng đang hoạt động.');
            }

            $conversationStatement = $this->conn->prepare(
                "SELECT id FROM support_conversations
                 WHERE customer_id = :customer_id AND status = 'open'
                 ORDER BY id DESC LIMIT 1"
            );
            $conversationStatement->execute([':customer_id' => (int) $customerId]);
            $conversationId = $conversationStatement->fetchColumn();
            if ($conversationId === false) {
                $insertStatement = $this->conn->prepare(
                    "INSERT INTO support_conversations (customer_id, status)
                     VALUES (:customer_id, 'open')"
                );
                $insertStatement->execute([':customer_id' => (int) $customerId]);
                $conversationId = $this->conn->lastInsertId();
            }

            $this->conn->commit();
            return (int) $conversationId;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function getInbox()
    {
        $statement = $this->conn->query(
            "SELECT c.id, c.customer_id, c.status, c.last_message_at, c.updated_at,
                    u.full_name AS customer_name, u.phone AS customer_phone,
                    (SELECT m.message FROM support_messages m
                     WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
                    (SELECT COUNT(*) FROM support_messages m
                     INNER JOIN users sender ON sender.id = m.sender_id
                     WHERE m.conversation_id = c.id
                       AND sender.role = 'customer' AND m.read_at IS NULL) AS unread_count
             FROM support_conversations c
             INNER JOIN users u ON u.id = c.customer_id
             ORDER BY (c.status = 'open') DESC, c.last_message_at DESC, c.id DESC
             LIMIT 100"
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findForAdmin($conversationId)
    {
        $statement = $this->conn->prepare(
            'SELECT c.id, c.customer_id, c.status, c.last_message_at, c.created_at,
                    u.full_name AS customer_name, u.phone AS customer_phone
             FROM support_conversations c
             INNER JOIN users u ON u.id = c.customer_id
             WHERE c.id = :id LIMIT 1'
        );
        $statement->execute([':id' => (int) $conversationId]);
        $conversation = $statement->fetch(PDO::FETCH_ASSOC);
        return $conversation ?: null;
    }

    public function getMessages($conversationId, $userId, $role, $afterId = 0)
    {
        $conversation = $this->findForUser($conversationId, $userId, $role);
        if (!$conversation) {
            return null;
        }

        $readStatement = $this->conn->prepare(
            'UPDATE support_messages
             SET read_at = CURRENT_TIMESTAMP
             WHERE conversation_id = :conversation_id
               AND sender_id <> :user_id AND read_at IS NULL'
        );
        $readStatement->execute([
            ':conversation_id' => (int) $conversationId,
            ':user_id' => (int) $userId,
        ]);

        $messageStatement = $this->conn->prepare(
            'SELECT m.id, m.sender_id, u.full_name AS sender_name, m.message, m.created_at
             FROM support_messages m
             INNER JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = :conversation_id AND m.id > :after_id
             ORDER BY m.id ASC LIMIT 100'
        );
        $messageStatement->bindValue(':conversation_id', (int) $conversationId, PDO::PARAM_INT);
        $messageStatement->bindValue(':after_id', max(0, (int) $afterId), PDO::PARAM_INT);
        $messageStatement->execute();

        return [
            'conversation' => $conversation,
            'messages' => $messageStatement->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function sendMessage($conversationId, $userId, $role, $message)
    {
        $this->conn->beginTransaction();
        try {
            $conversation = $this->findForUser($conversationId, $userId, $role, true);
            if (!$conversation) {
                $this->conn->rollBack();
                return ['status' => 'not_found'];
            }
            if ($conversation['status'] !== 'open') {
                $this->conn->rollBack();
                return ['status' => 'closed'];
            }

            $insertStatement = $this->conn->prepare(
                'INSERT INTO support_messages (conversation_id, sender_id, message)
                 VALUES (:conversation_id, :sender_id, :message)'
            );
            $insertStatement->execute([
                ':conversation_id' => (int) $conversationId,
                ':sender_id' => (int) $userId,
                ':message' => $message,
            ]);
            $messageId = (int) $this->conn->lastInsertId();

            $updateStatement = $this->conn->prepare(
                'UPDATE support_conversations SET last_message_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $updateStatement->execute([':id' => (int) $conversationId]);
            $this->conn->commit();

            $messageStatement = $this->conn->prepare(
                'SELECT m.id, m.sender_id, u.full_name AS sender_name, m.message, m.created_at
                 FROM support_messages m
                 INNER JOIN users u ON u.id = m.sender_id
                 WHERE m.id = :id LIMIT 1'
            );
            $messageStatement->execute([':id' => $messageId]);
            return ['status' => 'sent', 'message' => $messageStatement->fetch(PDO::FETCH_ASSOC)];
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    public function closeConversation($conversationId)
    {
        $statement = $this->conn->prepare(
            "UPDATE support_conversations SET status = 'closed'
             WHERE id = :id AND status = 'open'"
        );
        $statement->execute([':id' => (int) $conversationId]);
        return $this->findForAdmin($conversationId);
    }

    private function findForUser($conversationId, $userId, $role, $forUpdate = false)
    {
        if (!in_array($role, ['admin', 'customer'], true)) {
            return null;
        }

        $sql = 'SELECT id, customer_id, status FROM support_conversations WHERE id = :id';
        $parameters = [':id' => (int) $conversationId];
        if ($role === 'customer') {
            $sql .= ' AND customer_id = :customer_id';
            $parameters[':customer_id'] = (int) $userId;
        }
        $sql .= ' LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');

        $statement = $this->conn->prepare($sql);
        $statement->execute($parameters);
        $conversation = $statement->fetch(PDO::FETCH_ASSOC);
        return $conversation ?: null;
    }
}
