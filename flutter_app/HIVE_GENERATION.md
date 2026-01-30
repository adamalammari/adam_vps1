# Hive Type Adapters Generator Instructions

## هذا الملف يوضح كيفية توليد Hive adapters

لم يتم توليد ملفات `.g.dart` تلقائياً. يجب تشغيل الأمر التالي:

```bash
cd flutter_app
flutter pub run build_runner build --delete-conflicting-outputs
```

هذا سيقوم بتوليد الملفات التالية:
- `lib/models/user.g.dart`
- `lib/models/message.g.dart`
- `lib/models/product.g.dart`

## ملاحظات:
1. تأكد من تثبيت جميع الـ dependencies أولاً: `flutter pub get`
2. إذا واجهت أخطاء، حاول حذف الملفات القديمة: `flutter pub run build_runner clean`
3. ثم أعد التوليد: `flutter pub run build_runner build`

## في حالة الخطأ:
إذا ظهر خطأ "Conflicting outputs", استخدم:
```bash
flutter pub run build_runner build --delete-conflicting-outputs
```
