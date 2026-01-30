<?php
/**
 * Response Utility Class
 * Standardized JSON response formatting
 */
class Response
{
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = null)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, 403);
    }

    /**
     * Send not found response
     */
    public static function notFound($message = 'Not Found')
    {
        self::error($message, 404);
    }

    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation Error')
    {
        self::error($message, 422, $errors);
    }

    /**
     * Send server error response
     */
    public static function serverError($message = 'Internal Server Error')
    {
        self::error($message, 500);
    }
}
