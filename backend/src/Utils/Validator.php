<?php
/**
 * Validator Utility Class
 * Input validation utilities
 */
class Validator
{
    /**
     * Validate username
     * - 3-20 characters
     * - Only alphanumeric and underscore
     * - No spaces
     */
    public static function validateUsername($username)
    {
        $errors = [];

        if (empty($username)) {
            $errors[] = 'Username is required';
            return $errors;
        }

        if (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = 'Username must be between 3 and 20 characters';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }

        if (preg_match('/\s/', $username)) {
            $errors[] = 'Username cannot contain spaces';
        }

        return $errors;
    }

    /**
     * Validate required fields
     */
    public static function required($fields, $data)
    {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        
        return $errors;
    }

    /**
     * Validate email
     */
    public static function validateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Invalid email format'];
        }
        return [];
    }

    /**
     * Validate image file
     */
    public static function validateImageFile($file)
    {
        $errors = [];

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded';
            return $errors;
        }

        // Check file size
        if ($file['size'] > Config::MAX_UPLOAD_SIZE) {
            $maxSizeMB = Config::MAX_UPLOAD_SIZE / (1024 * 1024);
            $errors[] = "File size exceeds maximum of {$maxSizeMB}MB";
        }

        // Check mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, Config::ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Invalid image type. Allowed: JPG, PNG, WEBP';
        }

        return $errors;
    }

    /**
     * Validate video file
     */
    public static function validateVideoFile($file)
    {
        $errors = [];

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded';
            return $errors;
        }

        // Check file size
        if ($file['size'] > Config::MAX_UPLOAD_SIZE) {
            $maxSizeMB = Config::MAX_UPLOAD_SIZE / (1024 * 1024);
            $errors[] = "File size exceeds maximum of {$maxSizeMB}MB";
        }

        // Check mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, Config::ALLOWED_VIDEO_TYPES)) {
            $errors[] = 'Invalid video type. Allowed: MP4, MOV, AVI';
        }

        return $errors;
    }

    /**
     * Sanitize string
     */
    public static function sanitize($string)
    {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
}
