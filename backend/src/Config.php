<?php
/**
 * Configuration Class
 * Centralized configuration for the application
 */
class Config
{
    // Database Configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'flutter_chat_products';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';

    // JWT Secret Key (Change this to a random string!)
    const JWT_SECRET = 'your-secret-key-change-this-in-production';
    
    // Token Expiration (in seconds)
    const GUEST_TOKEN_EXPIRY = 30 * 24 * 60 * 60; // 30 days
    const ADMIN_TOKEN_EXPIRY = 24 * 60 * 60; // 24 hours

    // Upload Configuration
    const UPLOAD_DIR = __DIR__ . '/../public/uploads/';
    const UPLOAD_URL = '/uploads/';
    const MAX_UPLOAD_SIZE = 50 * 1024 * 1024; // 50MB
    const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];

    // API Configuration
    const API_BASE_URL = 'http://localhost'; // Change this to your domain
    
    // CORS Configuration
    const CORS_ALLOWED_ORIGINS = '*'; // Change to specific domain in production
    
    // Pagination
    const DEFAULT_PAGE_LIMIT = 50;
    const MAX_PAGE_LIMIT = 200;
}
