<?php
/**
 * Authentication Middleware
 * Protects routes by validating JWT tokens
 */
class AuthMiddleware
{
    /**
     * Verify guest user authentication
     */
    public static function verifyGuest()
    {
        $token = self::getBearerToken();
        
        if (!$token) {
            Response::unauthorized('No token provided');
        }

        $user = Auth::validateGuestToken($token);
        
        if (!$user || !$user['user_id']) {
            Response::unauthorized('Invalid or expired token');
        }

        // Update last_seen
        self::updateLastSeen($user['user_id']);

        return $user;
    }

    /**
     * Verify admin authentication
     */
    public static function verifyAdmin()
    {
        $token = self::getBearerToken();
        
        if (!$token) {
            Response::unauthorized('No token provided');
        }

        $admin = Auth::validateAdminToken($token);
        
        if (!$admin || !$admin['admin_id']) {
            Response::unauthorized('Invalid or expired admin token');
        }

        return $admin;
    }

    /**
     * Get bearer token from Authorization header
     */
    private static function getBearerToken()
    {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }

    /**
     * Update user's last_seen timestamp
     */
    private static function updateLastSeen($userId)
    {
        try {
            $db = Db::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            // Silent fail - not critical
        }
    }
}
