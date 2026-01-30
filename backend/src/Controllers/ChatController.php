<?php
/**
 * Chat Controller
 * Handles chat message operations
 */
class ChatController
{
    private $db;

    public function __construct()
    {
        $this->db = Db::getInstance()->getConnection();
    }

    /**
     * Get messages with pagination
     * GET /api/chat/messages?before_id=123&limit=50
     */
    public function getMessages()
    {
        // Verify authentication
        $user = AuthMiddleware::verifyGuest();

        // Get query parameters
        $beforeId = isset($_GET['before_id']) ? (int)$_GET['before_id'] : PHP_INT_MAX;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : Config::DEFAULT_PAGE_LIMIT;
        
        // Enforce max limit
        $limit = min($limit, Config::MAX_PAGE_LIMIT);

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    m.id,
                    m.user_id,
                    m.type,
                    m.content,
                    m.client_msg_id,
                    m.created_at,
                    u.username
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                WHERE m.room_id = 1 AND m.id < ?
                ORDER BY m.id DESC
                LIMIT ?
            ");
            
            $stmt->execute([$beforeId, $limit]);
            $messages = $stmt->fetchAll();

            // Reverse to get chronological order
            $messages = array_reverse($messages);

            // Format messages
            $formattedMessages = array_map(function($msg) {
                return [
                    'id' => (int)$msg['id'],
                    'user_id' => (int)$msg['user_id'],
                    'username' => $msg['username'],
                    'type' => $msg['type'],
                    'content' => $msg['content'],
                    'client_msg_id' => $msg['client_msg_id'],
                    'created_at' => $msg['created_at'],
                    'timestamp' => strtotime($msg['created_at'])
                ];
            }, $messages);

            Response::success([
                'messages' => $formattedMessages,
                'count' => count($formattedMessages),
                'has_more' => count($formattedMessages) === $limit
            ]);

        } catch (PDOException $e) {
            error_log("Get messages error: " . $e->getMessage());
            Response::serverError('Failed to fetch messages');
        }
    }

    /**
     * Send message (REST fallback - WebSocket is preferred)
     * POST /api/chat/send
     * Body: {type, content, client_msg_id}
     */
    public function sendMessage()
    {
        // Verify authentication
        $user = AuthMiddleware::verifyGuest();

        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            Response::error('Invalid JSON', 400);
        }

        // Validate required fields
        $errors = Validator::required(['type', 'content'], $input);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        $type = $input['type'];
        $content = $input['content'];
        $clientMsgId = $input['client_msg_id'] ?? null;

        // Validate type
        if (!in_array($type, ['text', 'image', 'video'])) {
            Response::error('Invalid message type', 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO messages (user_id, room_id, type, content, client_msg_id, created_at)
                VALUES (?, 1, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user['user_id'],
                $type,
                $content,
                $clientMsgId
            ]);

            $messageId = $this->db->lastInsertId();

            // Get the created message
            $stmt = $this->db->prepare("
                SELECT 
                    m.id,
                    m.user_id,
                    m.type,
                    m.content,
                    m.client_msg_id,
                    m.created_at,
                    u.username
                FROM messages m
                INNER JOIN users u ON m.user_id = u.id
                WHERE m.id = ?
            ");
            $stmt->execute([$messageId]);
            $message = $stmt->fetch();

            Response::success([
                'message' => [
                    'id' => (int)$message['id'],
                    'user_id' => (int)$message['user_id'],
                    'username' => $message['username'],
                    'type' => $message['type'],
                    'content' => $message['content'],
                    'client_msg_id' => $message['client_msg_id'],
                    'created_at' => $message['created_at'],
                    'timestamp' => strtotime($message['created_at'])
                ]
            ], 'Message sent successfully', 201);

        } catch (PDOException $e) {
            error_log("Send message error: " . $e->getMessage());
            Response::serverError('Failed to send message');
        }
    }
}
