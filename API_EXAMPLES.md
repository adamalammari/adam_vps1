# API Examples - أمثلة استخدام الـ API

## 1. Guest Login

```bash
curl -X POST http://localhost/api/auth/guest-login \
  -H "Content-Type: application/json" \
  -d '{"username": "testuser123"}'
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "username": "testuser123"
    }
  }
}
```

## 2. Get Messages

```bash
curl -X GET "http://localhost/api/chat/messages?limit=20" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 3. Upload Image

```bash
curl -X POST http://localhost/api/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/image.jpg"
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "url": "http://localhost/uploads/2026/01/29/abc123.jpg",
    "type": "image",
    "filename": "abc123.jpg",
    "size": 152400
  }
}
```

## 4. Get Products

```bash
curl -X GET "http://localhost/api/products" \
  -H "Content-Type: application/json"
```

## 5. Admin Login

```bash
curl -X POST http://localhost/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "admin123"}'
```

## 6. Create Product (Admin)

```bash
curl -X POST http://localhost/api/admin/products \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Samsung Galaxy S24",
    "price": 899.99,
    "description": "Latest Samsung flagship phone",
    "image_url": "https://example.com/image.jpg",
    "category": "Electronics",
    "contact_link": "https://wa.me/1234567890",
    "is_active": 1
  }'
```

## 7. Update Product (Admin)

```bash
curl -X PUT http://localhost/api/admin/products/1 \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Samsung Galaxy S24 Updated",
    "price": 849.99,
    "description": "Updated description",
    "image_url": "https://example.com/new-image.jpg",
    "category": "Smartphones",
    "contact_link": "https://wa.me/1234567890",
    "is_active": 1
  }'
```

## 8. Delete Product (Admin)

```bash
curl -X DELETE http://localhost/api/admin/products/1 \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

## 9. Get Settings (Admin)

```bash
curl -X GET http://localhost/api/admin/settings \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

## 10. Update Settings (Admin)

```bash
curl -X PUT http://localhost/api/admin/settings \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "app_name": "My Chat App",
    "default_contact_link": "https://wa.me/9876543210",
    "welcome_message": "مرحباً بك في تطبيقنا!"
  }'
```

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation Error",
  "errors": {
    "username": "Username must be between 3 and 20 characters"
  }
}
```

### Unauthorized (401)
```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Product not found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Internal server error"
}
```
