<?php
/**
 * Authentication Utilities
 * Handles token generation and validation for both guest and admin users
 */
class Auth
{
    /**
     * Generate a simple token for guest users
     */
    public static function generateGuestToken($userId, $username)
    {
        $payload = [
            'user_id' => $userId,
            'username' => $username,
            'type' => 'guest',
            'exp' => time() + Config::GUEST_TOKEN_EXPIRY
        ];
        
        return self::generateJWT($payload);
    }

    /**
     * Generate admin token
     */
    public static function generateAdminToken($adminId, $email)
    {
        $payload = [
            'admin_id' => $adminId,
            'email' => $email,
            'type' => 'admin',
            'exp' => time() + Config::ADMIN_TOKEN_EXPIRY
        ];
        
        return self::generateJWT($payload);
    }

    /**
     * Validate guest token and return user info
     */
    public static function validateGuestToken($token)
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
     * Validate admin token
     */
    public static function validateAdminToken($token)
    {
        $payload = self::decodeJWT($token);
        
        if (!$payload || !isset($payload['type']) || $payload['type'] !== 'admin') {
            return null;
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // Token expired
        }

        return [
            'admin_id' => $payload['admin_id'] ?? null,
            'email' => $payload['email'] ?? null
        ];
    }

    /**
     * Get user from database by token
     */
    public static function getUserByToken($token)
    {
        try {
            $db = Db::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, username FROM users WHERE token = ?");
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Simple JWT generation (base64 encoding)
     * Note: For production, use a proper JWT library like firebase/php-jwt
     */
    private static function generateJWT($payload)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            Config::JWT_SECRET,
            true
        );
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
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
            Config::JWT_SECRET,
            true
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }
        
        $payload = self::base64UrlDecode($base64UrlPayload);
        return json_decode($payload, true);
    }

    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Hash password for admin
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
