# Flutter Chat & Products System

نظام تطبيق Flutter متكامل للدردشة والمنتجات مع backend PHP وWebSocket Server

## 📋 نظرة عامة

هذا نظام متكامل يحتوي على:
- ✅ تطبيق Flutter للهواتف (Android/iOS)
- ✅ Backend PHP REST API + MySQL
- ✅ WebSocket Server (Workerman) للدردشة الفورية
- ✅ لوحة تحكم ويب للإدارة
- ✅ دعم وضع عدم الاتصال (Offline Mode)
- ✅ Material 3 Design

## 🏗️ بنية المشروع

```
flutter_chat_products/
├── database/
│   └── schema.sql                 # قاعدة البيانات MySQL
├── backend/
│   ├── public/
│   │   ├── index.php             # Router الرئيسي
│   │   ├── .htaccess
│   │   └── uploads/              # ملفات الرفع
│   └── src/
│       ├── Config.php
│       ├── Db.php
│       ├── Auth.php
│       ├── Controllers/
│       ├── Middleware/
│       └── Utils/
├── ws-server/
│   ├── composer.json
│   ├── server.php                # WebSocket Server
│   └── src/
│       ├── Db.php
│       ├── WsAuth.php
│       └── WsHandlers.php
├── flutter_app/
│   ├── lib/
│   │   ├── main.dart
│   │   ├── core/
│   │   ├── models/
│   │   ├── services/
│   │   ├── providers/
│   │   └── screens/
│   └── pubspec.yaml
└── admin-panel/
    ├── index.php                  # تسجيل دخول المدير
    ├── dashboard.php
    ├── products.php               # إدارة المنتجات
    ├── settings.php               # الإعدادات
    └── includes/
```

## 🚀 التثبيت والإعداد

### 1️⃣ قاعدة البيانات MySQL

```bash
# إنشاء قاعدة البيانات
mysql -u root -p
CREATE DATABASE flutter_chat_products CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# استيراد Schema
mysql -u root -p flutter_chat_products < database/schema.sql
```

**ملاحظة**: البيانات الافتراضية سيتم إنشاؤها تلقائياً:
- Admin: `admin@example.com` / `admin123`
- 3 منتجات تجريبية

### 2️⃣ Backend PHP REST API

#### المتطلبات:
- PHP >= 7.4
- MySQL >= 5.7
- OpenLiteSpeed أو Apache/Nginx

#### التثبيت:

```bash
cd backend

# تعديل الإعدادات (اختياري)
# افتح src/Config.php وعدل:
# - بيانات قاعدة البيانات
# - JWT_SECRET (غيره في الإنتاج!)
# - UPLOAD_DIR و UPLOAD_URL

# إنشاء مجلد الرفع
mkdir -p public/uploads
chmod 755 public/uploads

# للتجربة المحلية (XAMPP/WAMP)
# انسخ المجلد backend إلى: C:\xampp\htdocs\api
# وانتقل إلى: http://localhost/api
```

#### إعدادات PHP (php.ini):
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

### 3️⃣ WebSocket Server (Workerman)

```bash
cd ws-server

# تثبيت Composer dependencies
composer install

# تعديل إعدادات قاعدة البيانات
# افتح src/Db.php وعدل بيانات الاتصال

# تشغيل السيرفر
php server.php start

# للتشغيل كخدمة daemon
php server.php start -d

# إيقاف السيرفر
php server.php stop

# إعادة التشغيل
php server.php restart
```

**ملاحظة**: في Windows، استخدم:
```bash
php server.php start
```

### 4️⃣ تطبيق Flutter

```bash
cd flutter_app

# تثبيت Dependencies
flutter pub get

# توليد Hive Adapters
flutter pub run build_runner build --delete-conflicting-outputs

# تعديل الإعدادات
# افتح lib/core/config.dart وعدل:
# - apiBaseUrl (عنوان API الخاص بك)
# - wsUrl (عنوان WebSocket)

# للتجربة على Android Emulator:
# استخدم 10.0.2.2 بدلاً من localhost

# للتجربة على جهاز فعلي:
# استخدم عنوان IP الخاص بجهاز الكمبيوتر
# مثال: 192.168.1.100

# تشغيل التطبيق
flutter run
```

#### ملاحظات مهمة للـ Flutter:

1. **Android emulator**: استخدم `10.0.2.2` للوصول إلى localhost
2. **Physical device**: استخدم IP الفعلي لجهازك
3. **Permissions**: تأكد من إضافة أذونات الإنترنت في `AndroidManifest.xml`:

```xml
<manifest>
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
</manifest>
```

### 5️⃣ لوحة التحكم Admin Panel

```bash
# انسخ المجلد admin-panel إلى مجلد الويب
# مثال: C:\xampp\htdocs\admin

# افتح المتصفح واذهب إلى:
# http://localhost/admin

# تسجيل الدخول:
# البريد: admin@example.com
# كلمة المرور: admin123

# ⚠️ غير كلمة المرور بعد أول تسجيل دخول!
```

## 🌐 API Endpoints

### Authentication
```bash
# Guest Login
POST /api/auth/guest-login
Content-Type: application/json

{
  "username": "user123"
}
```

### Chat Messages
```bash
# Get Messages (with pagination)
GET /api/chat/messages?before_id=100&limit=50
Authorization: Bearer TOKEN

# Send Message (REST fallback)
POST /api/chat/send
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "type": "text",
  "content": "Hello!",
  "client_msg_id": "uuid-here"
}
```

### File Upload
```bash
# Upload Image/Video
POST /api/upload
Authorization: Bearer TOKEN
Content-Type: multipart/form-data

file: [binary data]
```

### Products
```bash
# Get All Products
GET /api/products

# Get Products by Category
GET /api/products?category=Electronics

# Get Single Product
GET /api/products/1

# Get Categories
GET /api/products/categories
```

### Admin Endpoints
```bash
# Admin Login
POST /api/admin/login
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "admin123"
}

# Get All Products (Admin)
GET /api/admin/products
Authorization: Bearer ADMIN_TOKEN

# Create Product
POST /api/admin/products
Authorization: Bearer ADMIN_TOKEN
Content-Type: application/json

{
  "name": "iPhone 15",
  "price": 999.99,
  "description": "Latest iPhone",
  "image_url": "https://...",
  "category": "Electronics",
  "contact_link": "https://wa.me/123",
  "is_active": 1
}

# Update Product
PUT /api/admin/products/1
Authorization: Bearer ADMIN_TOKEN
[Same body as create]

# Delete Product
DELETE /api/admin/products/1
Authorization: Bearer ADMIN_TOKEN

# Get Settings
GET /api/admin/settings
Authorization: Bearer ADMIN_TOKEN

# Update Settings
PUT /api/admin/settings
Authorization: Bearer ADMIN_TOKEN

{
  "app_name": "My App",
  "default_contact_link": "https://wa.me/123",
  "welcome_message": "Welcome!"
}
```

## 🔌 WebSocket Events

### من العميل إلى السيرفر:

```javascript
// Join Chat Room
{
  "type": "join",
  "token": "JWT_TOKEN"
}

// Send Message
{
  "type": "message",
  "token": "JWT_TOKEN",
  "messageType": "text",  // text, image, video
  "content": "Hello!",
  "clientMsgId": "uuid-here"
}

// Typing Indicator
{
  "type": "typing",
  "token": "JWT_TOKEN",
  "isTyping": true
}

// Ping (Keepalive)
{
  "type": "ping"
}
```

### من السيرفر إلى العميل:

```javascript
// Joined Successfully
{
  "type": "joined",
  "user_id": 1,
  "username": "user123",
  "online_count": 5
}

// New Message
{
  "type": "new_message",
  "message": {
    "id": 123,
    "user_id": 1,
    "username": "user123",
    "type": "text",
    "content": "Hello!",
    "created_at": "2026-01-29 12:00:00",
    "timestamp": 1706529600
  }
}

// Message Acknowledgment
{
  "type": "message_ack",
  "client_msg_id": "uuid-here",
  "message_id": 123,
  "created_at": "2026-01-29 12:00:00"
}

// User Typing
{
  "type": "user_typing",
  "user_id": 2,
  "username": "other_user",
  "is_typing": true
}

// Pong
{
  "type": "pong"
}
```

## 📦 التوزيع على OpenLiteSpeed

### 1. إعداد OpenLiteSpeed

```bash
# تثبيت OpenLiteSpeed
wget -O - http://rpms.litespeedtech.com/debian/enable_lst_debian_repo.sh | sudo bash
sudo apt-get update
sudo apt-get install openlitespeed lsphp80

# بدء الخدمة
sudo systemctl start lsws
sudo systemctl enable lsws
```

### 2. إعداد SSL (Let's Encrypt)

```bash
sudo apt-get install certbot
sudo certbot certonly --standalone -d yourdomain.com
```

### 3. رفع الملفات

```bash
# Backend API
sudo cp -r backend/* /usr/local/lsws/Example/html/api/

# Admin Panel
sudo cp -r admin-panel/* /usr/local/lsws/Example/html/admin/

# صلاحيات الملفات
sudo chown -R lsadm:lsadm /usr/local/lsws/Example/html/
sudo chmod -R 755 /usr/local/lsws/Example/html/
sudo chmod -R 755 /usr/local/lsws/Example/html/api/public/uploads/
```

### 4. WebSocket كخدمة Systemd

```bash
# إنشاء ملف الخدمة
sudo nano /etc/systemd/system/flutter-ws.service
```

```ini
[Unit]
Description=Flutter Chat WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/ws-server
ExecStart=/usr/bin/php /var/www/ws-server/server.php start
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
# تفعيل وتشغيل الخدمة
sudo systemctl daemon-reload
sudo systemctl enable flutter-ws
sudo systemctl start flutter-ws
sudo systemctl status flutter-ws
```

### 5. فتح المنافذ في Firewall

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8080/tcp   # WebSocket
sudo ufw reload
```

### 6. Reverse Proxy للـ WebSocket (اختياري)

في إعدادات OpenLiteSpeed، أضف:

```
External App Type: Web Server
Name: websocket
Address: 127.0.0.1:8080

Context:
URI: /ws
Web Server: [websocket]
```

## 🔧 استكشاف الأخطاء

### مشكلة: لا يمكن الاتصال بـ API من التطبيق

```bash
# تحقق من:
1. تأكد من تشغيل الخادم (Apache/OpenLiteSpeed)
2. تحقق من عنوان API في config.dart
3. للـ emulator استخدم 10.0.2.2 بدلاً من localhost
4. للجهاز الفعلي، تأكد من أنك على نفس الشبكة
5. تعطيل الجدار الناري مؤقتاً للاختبار
```

### مشكلة: WebSocket لا يتصل

```bash
# تحقق من:
1. تشغيل WebSocket server: php server.php status
2. المنفذ 8080 مفتوح في الجدار الناري
3. عنوان WebSocket في config.dart صحيح
4. Token صحيح ولم ينته
```

### مشكلة: فشل رفع الملفات

```bash
# تحقق من:
1. صلاحيات مجلد uploads: chmod 755 uploads/
2. حجم الملف ضمن الحد المسموح (50MB)
3. إعدادات PHP: upload_max_filesize و post_max_size
```

## 📝 ملاحظات مهمة

1. **الأمان**:
   - غير `JWT_SECRET` في الإنتاج
   - غير كلمة مرور المدير الافتراضية
   - استخدم HTTPS في الإنتاج
   - احم مجلد الرفع من التنفيذ المباشر

2. **الأداء**:
   - استخدم Redis للتخزين المؤقت (اختياري)
   - فعّل Gzip compression
   - استخدم CDN للملفات الثابتة

3. **النسخ الاحتياطي**:
   - اعمل نسخة احتياطية لقاعدة البيانات بشكل دوري
   - احتفظ بنسخة من مجلد uploads

## 🧪 اختبار النظام

### سيناريو 1: إرسال رسالة نصية

1. افتح التطبيق وسجل دخول باسم مستخدم
2. انتقل إلى تبويب الدردشة
3. اكتب رسالة واضغط إرسال
4. يجب أن تظهر الرسالة فوراً
5. افتح التطبيق في جهاز آخر وسجل دخول
6. يجب أن تظهر الرسالة في الجهاز الثاني

### سيناريو 2: رفع صورة

1. في شاشة الدردشة، اضغط على أيقونة الصورة
2. اختر صورة من المعرض
3. انتظر رفع الصورة
4. يجب أن تظهر الصورة في الدردشة

### سيناريو 3: إضافة منتج

1. افتح لوحة التحكم (http://yourdomain.com/admin)
2. سجل دخول بحساب المدير
3. اذهب إلى المنتجات > إضافة منتج
4. املأ البيانات واحفظ
5. افتح تطبيق Flutter وانتقل لتبويب المنتجات
6. يجب أن يظهر المنتج الجديد

### سيناريو 4: وضع عدم الاتصال

1. افتح التطبيق وتأكد من وجود رسائل ومنتجات
2. فعّل وضع الطيران
3. يجب أن تستطيع رؤية آخر الرسائل والمنتجات المحفوظة
4. عطل وضع الطيران
5. يجب أن يتصل التطبيق تلقائياً ويحمّل المحتوى الجديد

## 📞 الدعم

إذا واجهت أي مشاكل، تحقق من:
- سجلات الأخطاء (error logs)
- إعدادات قاعدة البيانات
- الصلاحيات
- إعدادات الجدار الناري

## 📄 الترخيص

هذا المشروع مفتوح المصدر للاستخدام التعليمي والتجاري.

---

تم التطوير بواسطة: Antigravity AI Assistant
التاريخ: 2026-01-29
الإصدار: 1.0.0
#   a d a m _ v p s 1  
 