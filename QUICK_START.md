# Flutter Chat & Products System - Quick Start Guide

## ⚡ سريع البدء

### 1. قاعدة البيانات (دقيقة واحدة)
```bash
mysql -u root -p < database/schema.sql
```

### 2. Backend API (دقيقتان)
```bash
cd backend
# انسخ إلى مجلد الويب (مثال: C:\xampp\htdocs\api)
# تأكد من إنشاء مجلد uploads
```

### 3. WebSocket Server (دقيقة واحدة)
```bash
cd ws-server
composer install
php server.php start
```

### 4. Flutter App (3 دقائق)
```bash
cd flutter_app
flutter pub get
flutter pub run build_runner build
# عدّل lib/core/config.dart (غير 10.0.2.2 حسب حاجتك)
flutter run
```

### 5. Admin Panel (30 ثانية)
```bash
# انسخ admin-panel إلى مجلد الويب
# افتح: http://localhost/admin
# دخول: admin@example.com / admin123
```

## 🎯 اختبار سريع

1. افتح التطبيق → سجل دخول بـ "user1"
2. اذهب لتبويب الدردشة → أرسل رسالة
3. افتح جهاز ثاني → سجل دخول بـ "user2"
4. يجب أن ترى الرسالة فوراً! ✅

## 📝 ملاحظات مهمة

- **Android Emulator**: استخدم `10.0.2.2` بدلاً من `localhost`
- **Physical Device**: استخدم IP جهازك (مثل `192.168.1.100`)
- **WebSocket Port**: تأكد أن المنفذ 8080 مفتوح

## 🔗 روابط مفيدة

- README الكامل: [README.md](file:///y:/flutter_chat_products/README.md)
- أمثلة API: [API_EXAMPLES.md](file:///y:/flutter_chat_products/API_EXAMPLES.md)
- Walkthrough: في مجلد artifacts

تم! 🎉 النظام جاهز للعمل
