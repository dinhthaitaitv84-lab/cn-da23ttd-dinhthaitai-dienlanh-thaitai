<?php
class SupportController extends Controller
{
    public function inbox()
    {
        $this->requireRole('admin');
        $model = $this->model('SupportModel');
        $conversations = $model->getInbox();
        $conversationId = $this->queryInt('conversation_id');
        if ($conversationId < 1 && !empty($conversations)) {
            $conversationId = (int) $conversations[0]['id'];
        }

        $conversation = $conversationId > 0 ? $model->findForAdmin($conversationId) : null;
        if ($conversationId > 0 && !$conversation) {
            $this->setFlash('error', 'Không tìm thấy cuộc trò chuyện.');
            $this->redirect(url('/support/inbox'));
        }

        $messages = [];
        if ($conversation) {
            $result = $model->getMessages(
                $conversationId,
                (int) $_SESSION['user']['id'],
                'admin',
                0
            );
            $messages = $result['messages'];
            foreach ($conversations as &$inboxConversation) {
                if ((int) $inboxConversation['id'] === $conversationId) {
                    $inboxConversation['unread_count'] = 0;
                    break;
                }
            }
            unset($inboxConversation);
        }

        $this->view('admin/support', [
            'title' => 'Hỗ trợ khách hàng',
            'conversations' => $conversations,
            'conversation' => $conversation,
            'messages' => $messages,
            'currentUserId' => (int) $_SESSION['user']['id'],
        ]);
    }

    public function start()
    {
        $this->requireRole('customer');
        $this->verifyApiPost();
        $conversationId = $this->model('SupportModel')->createOrGetOpenConversation(
            (int) $_SESSION['user']['id']
        );
        $this->jsonResponse([
            'conversation_id' => $conversationId,
            'status' => 'open',
        ]);
    }

    public function messages($id = 0)
    {
        $this->requireRole(['customer', 'admin']);
        $this->verifyApiPost();
        $conversationId = (int) $id;
        $afterId = $this->postInteger('after_id');
        if ($conversationId < 1 || $afterId === null) {
            $this->jsonResponse(['error' => 'Thông tin cuộc trò chuyện không hợp lệ.'], 422);
        }

        $role = $_SESSION['user']['role'];
        $result = $this->model('SupportModel')->getMessages(
            $conversationId,
            (int) $_SESSION['user']['id'],
            $role,
            $afterId
        );
        if (!$result) {
            $this->jsonResponse(['error' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }
        foreach ($result['messages'] as &$message) {
            $message['is_mine'] = (int) $message['sender_id'] === (int) $_SESSION['user']['id'];
        }
        unset($message);

        $this->jsonResponse([
            'conversation' => $result['conversation'],
            'messages' => $result['messages'],
        ]);
    }

    public function send($id = 0)
    {
        $this->requireRole(['customer', 'admin']);
        $this->verifyApiPost();
        $conversationId = (int) $id;
        $message = $_POST['message'] ?? null;
        if ($conversationId < 1 || !is_string($message)) {
            $this->jsonResponse(['error' => 'Nội dung tin nhắn không hợp lệ.'], 422);
        }
        $message = trim($message);
        $messageLength = mb_strlen($message, 'UTF-8');
        if ($messageLength < 1 || $messageLength > 2000) {
            $this->jsonResponse(['error' => 'Tin nhắn phải có từ 1 đến 2.000 ký tự.'], 422);
        }

        $result = $this->model('SupportModel')->sendMessage(
            $conversationId,
            (int) $_SESSION['user']['id'],
            $_SESSION['user']['role'],
            $message
        );
        if ($result['status'] === 'not_found') {
            $this->jsonResponse(['error' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }
        if ($result['status'] === 'closed') {
            $this->jsonResponse(['error' => 'Cuộc trò chuyện đã kết thúc.'], 409);
        }
        $result['message']['is_mine'] = true;
        $this->jsonResponse($result);
    }

    public function close($id = 0)
    {
        $this->requireRole('admin');
        $this->verifyApiPost();
        $conversationId = (int) $id;
        if ($conversationId < 1) {
            $this->jsonResponse(['error' => 'Cuộc trò chuyện không hợp lệ.'], 422);
        }
        $conversation = $this->model('SupportModel')->closeConversation($conversationId);
        if (!$conversation) {
            $this->jsonResponse(['error' => 'Không tìm thấy cuộc trò chuyện.'], 404);
        }
        $this->jsonResponse(['conversation' => $conversation]);
    }

    public function inboxData()
    {
        $this->requireRole('admin');
        $this->verifyApiPost();
        $this->jsonResponse([
            'conversations' => $this->model('SupportModel')->getInbox(),
        ]);
    }

    private function verifyApiPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Phương thức không được hỗ trợ.'], 405);
        }
        if (!$this->verifyCsrf()) {
            $this->jsonResponse(['error' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 419);
        }
    }

    private function postInteger($key)
    {
        $value = $_POST[$key] ?? null;
        if (is_int($value) && $value >= 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', $value)) {
            return (int) $value;
        }
        return null;
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        $json = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
        if ($json === false) {
            throw new RuntimeException('Không thể tạo phản hồi JSON.');
        }
        echo $json;
        exit;
    }
}
