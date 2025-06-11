# RepairPoint - Includes Directory Protection
# حماية مجلد الملفات المضمنة

# منع الوصول لجميع الملفات في هذا المجلد
Order Deny,Allow
Deny from all

# السماح فقط للملفات PHP المطلوبة من الخادم نفسه
<Files "*.php">
Order Allow,Deny
Allow from 127.0.0.1
Allow from localhost
</Files>

# رسالة خطأ مخصصة
ErrorDocument 403 "Access Denied - Include files are protected"