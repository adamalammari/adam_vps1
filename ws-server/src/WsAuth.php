<?php
namespace WsServer;

/**
 * WebSocket Authentication
 */
class WsAuth
{
    const JWT_SECRET = 'your-secret-key-change-this-in-production'; // Must match backend

    /**
     * Validate token and get user info
     */
    public static function validateToken($token)
    {
        $payload = self::decodeJWT($token);
        
        if (!$payload || !isset($payload['type']) || $payload['type'] !== 'guest') {
            return null;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token expired
        }

        return [
            'user_id' => $payload['user_id'] ?? null,
            'username' => $payload['username'] ?? null
        ];
    }

    /**
     * Get user from database
     */
    public static function getUserById($userId)
    {
        try {
            $db = Db::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (\Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Decode JWT
     */
    private static function decodeJWT($jwt)
    {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        
        // Verify signature
        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            self::JWT_SECRET,
            true
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }
        
        $payload = self::base64UrlDecode($base64UrlPayload);
        return json_decode($payload, true);
    }

    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
