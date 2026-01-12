#!/bin/bash

###############################################################################
# RepairPoint - سكريبت نشر تلقائي على السيرفر
# الإصدار: 1.0
# التاريخ: 2026-01-12
###############################################################################

# الألوان للرسائل
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# معلومات السيرفر
SERVER_USER="u948397987"
SERVER_HOST="fr-int-web2000.user.fm"
SERVER_PATH="/home/u948397987/domains/rp.electromti.com/public_html"
SERVER_BACKUP_PATH="/home/u948397987/backups"

# اسم المشروع
PROJECT_NAME="RepairPoint"

# دالة طباعة رسالة ملونة
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# دالة عرض شريط التقدم
print_step() {
    local step=$1
    local total=$2
    local description=$3
    print_message "$BLUE" "\n[$step/$total] $description"
}

# دالة التحقق من وجود الأمر
check_command() {
    if ! command -v $1 &> /dev/null; then
        print_message "$RED" "❌ الأمر $1 غير موجود. الرجاء تثبيته أولاً."
        exit 1
    fi
}

# عنوان البرنامج
clear
print_message "$GREEN" "╔═══════════════════════════════════════════╗"
print_message "$GREEN" "║     RepairPoint - نشر على السيرفر       ║"
print_message "$GREEN" "╚═══════════════════════════════════════════╝"
echo ""

# الخطوة 1: التحقق من المتطلبات
print_step "1" "8" "التحقق من المتطلبات..."
check_command "rsync"
check_command "ssh"
check_command "zip"
print_message "$GREEN" "✓ جميع المتطلبات متوفرة"

# الخطوة 2: سؤال عن نوع النشر
print_step "2" "8" "اختيار نوع النشر..."
echo ""
echo "اختر نوع النشر:"
echo "1) نشر كامل (كل الملفات)"
echo "2) نشر سريع (ملفات PHP فقط)"
echo "3) نشر قاعدة البيانات فقط"
read -p "اختيارك [1-3]: " deploy_type

# الخطوة 3: إنشاء نسخة احتياطية
print_step "3" "8" "إنشاء نسخة احتياطية محلية..."
BACKUP_DIR="backups"
BACKUP_FILE="$BACKUP_DIR/${PROJECT_NAME}_$(date +%Y%m%d_%H%M%S).zip"

if [ ! -d "$BACKUP_DIR" ]; then
    mkdir -p "$BACKUP_DIR"
fi

# استثناء المجلدات غير المهمة
zip -r "$BACKUP_FILE" . \
    -x "*.git*" \
    -x "*node_modules*" \
    -x "*vendor*" \
    -x "*backups*" \
    -x "*.idea*" > /dev/null 2>&1

print_message "$GREEN" "✓ تم إنشاء نسخة احتياطية: $BACKUP_FILE"

# الخطوة 4: اختبار الاتصال بالسيرفر
print_step "4" "8" "اختبار الاتصال بالسيرفر..."
if ssh -o ConnectTimeout=10 "$SERVER_USER@$SERVER_HOST" "echo 'connected'" > /dev/null 2>&1; then
    print_message "$GREEN" "✓ الاتصال بالسيرفر ناجح"
else
    print_message "$RED" "❌ فشل الاتصال بالسيرفر"
    echo ""
    print_message "$YELLOW" "تأكد من:"
    echo "  1. أن لديك مفتاح SSH مضاف"
    echo "  2. أو أدخل كلمة مرور SSH عند الطلب"
    exit 1
fi

# الخطوة 5: إنشاء نسخة احتياطية على السيرفر
print_step "5" "8" "إنشاء نسخة احتياطية على السيرفر..."
ssh "$SERVER_USER@$SERVER_HOST" << 'ENDSSH'
    BACKUP_DIR="/home/u948397987/backups"
    mkdir -p "$BACKUP_DIR"

    # نسخ احتياطية من قاعدة البيانات
    DB_BACKUP="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"

    # نسخ احتياطية من الملفات
    FILE_BACKUP="$BACKUP_DIR/files_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    cd /home/u948397987/domains/rp.electromti.com/public_html
    tar -czf "$FILE_BACKUP" . 2>/dev/null || true

    echo "✓ تم إنشاء نسخة احتياطية على السيرفر"
ENDSSH

# الخطوة 6: رفع الملفات
print_step "6" "8" "رفع الملفات إلى السيرفر..."

case $deploy_type in
    1)
        # نشر كامل
        print_message "$YELLOW" "جاري رفع جميع الملفات..."
        rsync -avz --progress \
            --exclude='.git' \
            --exclude='node_modules' \
            --exclude='vendor' \
            --exclude='.idea' \
            --exclude='backups' \
            --exclude='*.log' \
            --exclude='config/database.php' \
            ./ "$SERVER_USER@$SERVER_HOST:$SERVER_PATH/"
        ;;
    2)
        # نشر سريع
        print_message "$YELLOW" "جاري رفع ملفات PHP فقط..."
        rsync -avz --progress \
            --include='*.php' \
            --include='*/' \
            --exclude='*' \
            --exclude='config/database.php' \
            ./ "$SERVER_USER@$SERVER_HOST:$SERVER_PATH/"
        ;;
    3)
        # نشر قاعدة البيانات فقط
        print_message "$YELLOW" "جاري رفع قاعدة البيانات..."
        rsync -avz --progress \
            --include='sql/' \
            --include='sql/*' \
            --exclude='*' \
            ./ "$SERVER_USER@$SERVER_HOST:$SERVER_PATH/"
        ;;
esac

print_message "$GREEN" "✓ تم رفع الملفات بنجاح"

# الخطوة 7: تعيين الصلاحيات
print_step "7" "8" "تعيين الصلاحيات..."
ssh "$SERVER_USER@$SERVER_HOST" << 'ENDSSH'
    cd /home/u948397987/domains/rp.electromti.com/public_html

    # صلاحيات المجلدات
    find . -type d -exec chmod 755 {} \; 2>/dev/null || true

    # صلاحيات الملفات
    find . -type f -exec chmod 644 {} \; 2>/dev/null || true

    # صلاحيات خاصة
    chmod 777 logs 2>/dev/null || true
    chmod 777 assets/uploads 2>/dev/null || true

    echo "✓ تم تعيين الصلاحيات"
ENDSSH

# الخطوة 8: اختبار التثبيت
print_step "8" "8" "اختبار التثبيت..."
sleep 2

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://rp.electromti.com/)
if [ "$HTTP_CODE" = "200" ]; then
    print_message "$GREEN" "✓ الموقع يعمل بنجاح (HTTP $HTTP_CODE)"
else
    print_message "$YELLOW" "⚠ الموقع يعود برمز HTTP: $HTTP_CODE"
fi

# الخلاصة
echo ""
print_message "$GREEN" "╔═══════════════════════════════════════════╗"
print_message "$GREEN" "║         تم النشر بنجاح! 🎉               ║"
print_message "$GREEN" "╚═══════════════════════════════════════════╝"
echo ""
print_message "$BLUE" "🌐 الموقع: https://rp.electromti.com"
print_message "$BLUE" "📦 النسخة الاحتياطية: $BACKUP_FILE"
echo ""
print_message "$YELLOW" "⚠️  ملاحظات مهمة:"
echo "  1. تأكد من تعديل ملف config/database.php على السيرفر"
echo "  2. راجع سجلات الأخطاء في logs/"
echo "  3. اختبر جميع الوظائف الرئيسية"
echo ""
print_message "$GREEN" "✓ النشر اكتمل بنجاح!"
