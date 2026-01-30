<?php
/**
 * Auth Controller
 * Handles guest user authentication
 */
class AuthController
{
    private $db;

    public function __construct()
    {
        $this->db = Db::getInstance()->getConnection();
    }

    /**
     * Guest login with username only
     * POST /api/auth/guest-login
     * Body: {username}
     */
    public function guestLogin()
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['username'])) {
            Response::error('Username is required', 400);
        }

        $username = trim($input['username']);

        // Validate username
        $errors = Validator::validateUsername($username);
        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id, username, token FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // User exists, return existing token
                Response::success([
                    'token' => $user['token'],
                    'user' => [
                        'id' => (int)$user['id'],
                        'username' => $user['username']
                    ]
                ], 'Login successful');
            }

            // Create new user
            $userId = $this->createUser($username);
            
            // Generate token
            $token = Auth::generateGuestToken($userId, $username);

            // Save token to database
            $stmt = $this->db->prepare("UPDATE users SET token = ? WHERE id = ?");
            $stmt->execute([$token, $userId]);

            Response::success([
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'username' => $username
                ]
            ], 'User created successfully', 201);

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            Response::serverError('Failed to process login');
        }
    }

    /**
     * Create new user
     */
    private function createUser($username)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, token, created_at) VALUES (?, '', NOW())"
        );
        $stmt->execute([$username]);
        return $this->db->lastInsertId();
    }
}
