<?php
/**
 * Upload Controller
 * Handles file uploads (images and videos)
 */
class UploadController
{
    /**
     * Upload file
     * POST /api/upload
     * Multipart: file
     */
    public function upload()
    {
        // Verify authentication
        $user = AuthMiddleware::verifyGuest();

        if (!isset($_FILES['file'])) {
            Response::error('No file uploaded', 400);
        }

        $file = $_FILES['file'];

        // Detect file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $isImage = in_array($mimeType, Config::ALLOWED_IMAGE_TYPES);
        $isVideo = in_array($mimeType, Config::ALLOWED_VIDEO_TYPES);

        if (!$isImage && !$isVideo) {
            Response::error('Invalid file type. Only images and videos are allowed', 400);
        }

        // Validate file
        if ($isImage) {
            $errors = Validator::validateImageFile($file);
            $fileType = 'image';
        } else {
            $errors = Validator::validateVideoFile($file);
            $fileType = 'video';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
        }

        try {
            // Create upload directory structure by date
            $datePath = date('Y/m/d');
            $uploadPath = Config::UPLOAD_DIR . $datePath . '/';
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $fullPath = $uploadPath . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                Response::serverError('Failed to save file');
            }

            // Generate URL
            $url = rtrim(Config::API_BASE_URL, '/') . Config::UPLOAD_URL . $datePath . '/' . $filename;

            Response::success([
                'url' => $url,
                'type' => $fileType,
                'filename' => $filename,
                'size' => $file['size']
            ], 'File uploaded successfully', 201);

        } catch (Exception $e) {
            error_log("Upload error: " . $e->getMessage());
            Response::serverError('Failed to upload file');
        }
    }
}
