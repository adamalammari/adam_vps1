<?php
namespace WsServer;

use Workerman\Connection\TcpConnection;

/**
 * WebSocket Event Handlers
 */
class WsHandlers
{
    private $worker;
    private $clients = []; // [connection_id => user_data]

    public function __construct($worker)
    {
        $this->worker = $worker;
    }

    /**
     * Handle new connection
     */
    public function onConnect(TcpConnection $connection)
    {
        echo "New connection: {$connection->id}\n";
    }

    /**
     * Handle incoming messages
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        try {
            $message = json_decode($data, true);
            
            if (!$message || !isset($message['type'])) {
                $this->sendError($connection, 'Invalid message format');
                return;
            }

            $type = $message['type'];

            switch ($type) {
                case 'join':
                    $this->handleJoin($connection, $message);
                    break;

                case 'message':
                    $this->handleMessage($connection, $message);
                    break;

                case 'typing':
                    $this->handleTyping($connection, $message);
                    break;

                case 'ping':
                    $this->handlePing($connection);
                    break;

                default:
                    $this->sendError($connection, 'Unknown message type');
            }

        } catch (\Exception $e) {
            error_log("Message handling error: " . $e->getMessage());
            $this->sendError($connection, 'Internal error');
        }
    }

    /**
     * Handle user joining
     */
    private function handleJoin(TcpConnection $connection, $message)
    {
        if (!isset($message['token'])) {
            $this->sendError($connection, 'Token required');
            return;
        }

        $user = WsAuth::validateToken($message['token']);
        
        if (!$user || !$user['user_id']) {
            $this->sendError($connection, 'Invalid token');
            return;
        }

        // Store user data with connection
        $this->clients[$connection->id] = $user;
        $connection->user = $user;

        // Send join success
        $this->send($connection, [
            'type' => 'joined',
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'online_count' => count($this->clients)
        ]);

        // Broadcast user joined
        $this->broadcast([
            'type' => 'user_joined',
            'username' => $user['username'],
            'online_count' => count($this->clients)
        ], $connection->id);

        echo "User joined: {$user['username']} (ID: {$user['user_id']})\n";
    }

    /**
     * Handle sending message
     */
    private function handleMessage(TcpConnection $connection, $message)
    {
        if (!isset($connection->user)) {
            $this->sendError($connection, 'Not authenticated');
            return;
        }

        if (!isset($message['messageType']) || !isset($message['content'])) {
            $this->sendError($connection, 'Invalid message data');
            return;
        }

        $user = $connection->user;
        $messageType = $message['messageType'];
        $content = $message['content'];
        $clientMsgId = $message['clientMsgId'] ?? null;

        // Validate message type
        if (!in_array($messageType, ['text', 'image', 'video'])) {
            $this->sendError($connection, 'Invalid message type');
            return;
        }

        try {
            // Save message to database
            $db = Db::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO messages (user_id, room_id, type, content, client_msg_id, created_at)
                VALUES (?, 1, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $user['user_id'],
                $messageType,
                $content,
                $clientMsgId
            ]);

            $messageId = $db->lastInsertId();

            // Get the saved message with timestamp
            $stmt = $db->prepare("
                SELECT id, user_id, type, content, client_msg_id, created_at
                FROM messages WHERE id = ?
            ");
            $stmt->execute([$messageId]);
            $savedMessage = $stmt->fetch();

            // Prepare broadcast data
            $broadcastData = [
                'type' => 'new_message',
                'message' => [
                    'id' => (int)$savedMessage['id'],
                    'user_id' => (int)$savedMessage['user_id'],
                    'username' => $user['username'],
                    'type' => $savedMessage['type'],
                    'content' => $savedMessage['content'],
                    'client_msg_id' => $savedMessage['client_msg_id'],
                    'created_at' => $savedMessage['created_at'],
                    'timestamp' => strtotime($savedMessage['created_at'])
                ]
            ];

            // Send acknowledgment to sender
            $this->send($connection, [
                'type' => 'message_ack',
                'client_msg_id' => $clientMsgId,
                'message_id' => (int)$messageId,
                'created_at' => $savedMessage['created_at']
            ]);

            // Broadcast to all connected clients
            $this->broadcast($broadcastData);

            echo "Message from {$user['username']}: {$messageType}\n";

        } catch (\Exception $e) {
            error_log("Save message error: " . $e->getMessage());
            $this->sendError($connection, 'Failed to save message');
        }
    }

    /**
     * Handle typing indicator
     */
    private function handleTyping(TcpConnection $connection, $message)
    {
        if (!isset($connection->user)) {
            return;
        }

        $isTyping = $message['isTyping'] ?? false;

        // Broadcast typing status to others
        $this->broadcast([
            'type' => 'user_typing',
            'user_id' => $connection->user['user_id'],
            'username' => $connection->user['username'],
            'is_typing' => $isTyping
        ], $connection->id);
    }

    /**
     * Handle ping (keepalive)
     */
    private function handlePing(TcpConnection $connection)
    {
        $this->send($connection, ['type' => 'pong']);
    }

    /**
     * Handle connection close
     */
    public function onClose(TcpConnection $connection)
    {
        if (isset($this->clients[$connection->id])) {
            $user = $this->clients[$connection->id];
            unset($this->clients[$connection->id]);

            // Broadcast user left
            $this->broadcast([
                'type' => 'user_left',
                'username' => $user['username'],
                'online_count' => count($this->clients)
            ]);

            echo "User left: {$user['username']}\n";
        }

        echo "Connection closed: {$connection->id}\n";
    }

    /**
     * Send message to a specific connection
     */
    private function send(TcpConnection $connection, $data)
    {
        $connection->send(json_encode($data));
    }

    /**
     * Send error message
     */
    private function sendError(TcpConnection $connection, $message)
    {
        $this->send($connection, [
            'type' => 'error',
            'message' => $message
        ]);
    }

    /**
     * Broadcast message to all connected clients
     */
    private function broadcast($data, $excludeId = null)
    {
        $json = json_encode($data);

        foreach ($this->worker->connections as $connection) {
            if ($excludeId && $connection->id === $excludeId) {
                continue;
            }
            $connection->send($json);
        }
    }
}
