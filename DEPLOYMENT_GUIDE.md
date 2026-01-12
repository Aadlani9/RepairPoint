# 🚀 دليل نشر RepairPoint على السيرفر

## 📋 المعلومات الأساسية

- **السيرفر**: fr-int-web2000.user.fm
- **المستخدم**: u948397987
- **الدومين**: rp.electromti.com
- **المسار**: `/home/u948397987/domains/rp.electromti.com/public_html`

---

## 🔐 الخطوة 1: إعداد قاعدة البيانات

### 1.1 إنشاء قاعدة البيانات

1. افتح **cPanel** من حساب الاستضافة
2. اذهب إلى **MySQL Databases**
3. أنشئ قاعدة بيانات جديدة:
   ```
   اسم القاعدة: u948397987_repairpoint
   ```
4. أنشئ مستخدم جديد:
   ```
   اسم المستخدم: u948397987_repair
   كلمة المرور: [اختر كلمة مرور قوية]
   ```
5. أضف المستخدم إلى القاعدة مع **ALL PRIVILEGES**

### 1.2 استيراد قاعدة البيانات

```bash
# من خلال SSH
cd /home/u948397987/domains/rp.electromti.com
mysql -u u948397987_repair -p u948397987_repairpoint < sql/repairpoint_structure.sql
```

أو من **phpMyAdmin**:
1. افتح phpMyAdmin
2. اختر قاعدة البيانات `u948397987_repairpoint`
3. اذهب إلى تبويب **Import**
4. اختر ملف `sql/repairpoint_structure.sql`
5. اضغط **Go**

---

## 📦 الخطوة 2: رفع الملفات

### الطريقة الأولى: استخدام السكريبت التلقائي (موصى بها)

```bash
# من جهازك المحلي
cd /path/to/RepairPoint
./deploy.sh
```

### الطريقة الثانية: رفع يدوي عبر FTP

استخدم **FileZilla** أو أي برنامج FTP:

```
Host: rp.electromti.com
Username: u948397987
Password: [كلمة مرور FTP]
Port: 21
```

ارفع جميع الملفات إلى:
```
/home/u948397987/domains/rp.electromti.com/public_html/
```

### الطريقة الثالثة: استخدام SSH و rsync

```bash
rsync -avz --exclude='.git' --exclude='node_modules' \
  ./ u948397987@fr-int-web2000.user.fm:/home/u948397987/domains/rp.electromti.com/public_html/
```

---

## ⚙️ الخطوة 3: إعداد الملفات على السيرفر

### 3.1 تعديل ملف database.php

```bash
# اتصل بالسيرفر عبر SSH
ssh u948397987@fr-int-web2000.user.fm

# انتقل إلى مجلد المشروع
cd /home/u948397987/domains/rp.electromti.com/public_html

# انسخ ملف الإنتاج
cp config/database.production.php config/database.php

# عدّل البيانات
nano config/database.php
```

غيّر هذه القيم:
```php
'host' => 'localhost',
'username' => 'u948397987_repair',
'password' => 'YOUR_DB_PASSWORD',  // كلمة مرور قاعدة البيانات
'database' => 'u948397987_repairpoint',
```

### 3.2 تعديل ملف .htaccess

```bash
nano .htaccess
```

غيّر السطر 58:
```apache
# من
RewriteCond %{HTTP_REFERER} !^https?://[^/]*\.?yourdomain\.com [NC]

# إلى
RewriteCond %{HTTP_REFERER} !^https?://[^/]*\.?rp\.electromti\.com [NC]
```

---

## 🔒 الخطوة 4: تعيين الصلاحيات

```bash
# صلاحيات المجلدات
find /home/u948397987/domains/rp.electromti.com/public_html -type d -exec chmod 755 {} \;

# صلاحيات الملفات
find /home/u948397987/domains/rp.electromti.com/public_html -type f -exec chmod 644 {} \;

# صلاحيات خاصة لمجلدات الكتابة
chmod 777 /home/u948397987/domains/rp.electromti.com/public_html/logs
chmod 777 /home/u948397987/domains/rp.electromti.com/public_html/assets/uploads
```

---

## 🧪 الخطوة 5: اختبار التثبيت

### 5.1 اختبار الاتصال بقاعدة البيانات

افتح المتصفح:
```
https://rp.electromti.com/api/test_connection.php
```

يجب أن ترى:
```json
{
  "status": "success",
  "message": "Database connection successful"
}
```

### 5.2 اختبار الصفحة الرئيسية

```
https://rp.electromti.com/
```

يجب أن تظهر صفحة تسجيل الدخول.

---

## 🔐 الخطوة 6: إنشاء مستخدم مدير

```bash
# عبر SSH
cd /home/u948397987/domains/rp.electromti.com/public_html
php setup_admin_user.php
```

أو افتح في المتصفح:
```
https://rp.electromti.com/setup_admin_user.php
```

بيانات المدير الافتراضية:
```
Username: admin
Password: admin123
```

**⚠️ مهم جداً**: غيّر كلمة المرور فوراً بعد الدخول!

---

## 📊 الخطوة 7: إعداد البيانات الأساسية

### 7.1 إضافة العلامات التجارية

```bash
php setup_default_brand.php
```

### 7.2 استيراد موديلات الهواتف (اختياري)

```
https://rp.electromti.com/import_phones_data.php
```

---

## 🛡️ الخطوة 8: الأمان والحماية

### 8.1 حذف ملفات التثبيت

```bash
cd /home/u948397987/domains/rp.electromti.com/public_html
rm -f setup_admin_user.php
rm -f api/test_connection.php
rm -f import_phones_data.php
```

### 8.2 تفعيل HTTPS

في cPanel:
1. اذهب إلى **SSL/TLS Status**
2. فعّل AutoSSL لدومين `rp.electromti.com`
3. انتظر حتى يصبح الشهادة نشطة (عادة 5-10 دقائق)

### 8.3 إعداد النسخ الاحتياطي التلقائي

أنشئ ملف Cron Job في cPanel:
```bash
# كل يوم الساعة 2 صباحاً
0 2 * * * /usr/bin/mysqldump -u u948397987_repair -p'PASSWORD' u948397987_repairpoint > /home/u948397987/backups/db_$(date +\%Y\%m\%d).sql
```

---

## 🔍 استكشاف الأخطاء

### مشكلة: لا تظهر الصفحة الرئيسية

**الحل:**
```bash
# تحقق من ملف .htaccess
cat .htaccess

# تحقق من صلاحيات index.php
ls -la index.php

# تحقق من سجل الأخطاء
tail -f logs/php_errors.log
```

### مشكلة: خطأ في الاتصال بقاعدة البيانات

**الحل:**
```bash
# اختبر الاتصال
mysql -u u948397987_repair -p u948397987_repairpoint

# تحقق من ملف config
cat config/database.php | grep -A 10 "config\['database'\]"
```

### مشكلة: 403 Forbidden على مجلدات معينة

**الحل:**
```bash
# أعد تعيين الصلاحيات
chmod 755 pages/
chmod 644 pages/*.php
```

---

## 📝 ملاحظات مهمة

1. **النسخ الاحتياطي**: احتفظ بنسخة احتياطية يومية من:
   - قاعدة البيانات
   - مجلد `assets/uploads/`
   - ملف `config/database.php`

2. **التحديثات**: عند تحديث المشروع:
   ```bash
   # احفظ نسخة من config
   cp config/database.php config/database.backup.php

   # ارفع الملفات الجديدة
   # استعد config
   cp config/database.backup.php config/database.php
   ```

3. **المراقبة**: راقب سجلات الأخطاء:
   ```bash
   tail -f logs/php_errors.log
   tail -f logs/auth_errors.log
   ```

4. **الأداء**: لتحسين الأداء:
   - فعّل OPcache في cPanel (PHP Settings)
   - فعّل Gzip compression (تم إعداده في .htaccess)
   - استخدم CDN للملفات الثابتة (اختياري)

---

## 🎉 الخلاصة

بعد اتباع هذه الخطوات، يجب أن يكون المشروع جاهزاً للعمل على:

**🌐 https://rp.electromti.com**

في حال واجهت أي مشاكل:
- تحقق من سجلات الأخطاء في `logs/`
- راجع ملف `.htaccess`
- تأكد من صلاحيات الملفات والمجلدات

---

**آخر تحديث**: 2026-01-12
**الإصدار**: 1.0
