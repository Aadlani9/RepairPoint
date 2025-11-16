# إرشادات تشغيل Migration للضمان

## الحقول المطلوبة في جدول `repairs`

هذا الـ migration يضيف حقول مهمة لنظام الضمان وإعادة الفتح:
- `original_delivered_at` - تاريخ التسليم الأول
- `reopen_delivered_at` - تاريخ إعادة التسليم بعد الضمان
- `reopen_warranty_days` - أيام الضمان الجديدة
- `reopen_completed_at` - تاريخ الإنجاز بعد إعادة الفتح
- `reopen_count` - عدد مرات إعادة الفتح
- `last_reopen_by` - المستخدم الذي قام بآخر إعادة فتح

## كيفية تشغيل Migration

### الطريقة 1: من phpMyAdmin
1. افتح phpMyAdmin
2. اختر قاعدة البيانات `repairpoint`
3. اذهب إلى تبويب SQL
4. افتح ملف `sql/migrations/add_warranty_tracking_and_history.sql`
5. انسخ المحتوى والصقه
6. اضغط "تنفيذ" (Go/Execute)

### الطريقة 2: من سطر الأوامر
```bash
mysql -u root -p repairpoint < sql/migrations/add_warranty_tracking_and_history.sql
```

### الطريقة 3: من خلال صفحة ويب (إذا كان PHP متصل بقاعدة البيانات)
1. افتح المتصفح على: `http://localhost/RepairPoint/run_migration.php`
2. اتبع التعليمات على الشاشة

## ملاحظات

- ✅ الكود محمي ضد عدم وجود الحقول (defensive programming)
- ✅ يعمل حتى قبل تشغيل migration (لن تظهر warnings)
- ⚠️  لكن للحصول على ميزات الضمان الكاملة، يجب تشغيل migration

## التحقق من نجاح Migration

بعد تشغيل migration، تحقق من وجود الحقول:
```sql
SHOW COLUMNS FROM repairs LIKE '%deliver%';
```

يجب أن ترى:
- delivered_at
- original_delivered_at  
- reopen_delivered_at
