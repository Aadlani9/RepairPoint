<?php
/**
 * RepairPoint - Migration Runner
 * إضافة ماركة افتراضية للأجهزة المخصصة
 */

// تعيين متغيرات HTTP للعمل من سطر الأوامر
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/RepairPoint/';
$_SERVER['SCRIPT_NAME'] = '/RepairPoint/index.php';
$_SERVER['HTTPS'] = 'off';

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

echo "==============================================\n";
echo "Migration: إضافة ماركة افتراضية للأجهزة المخصصة\n";
echo "==============================================\n\n";

try {
    $db = getDB();

    // 1. التحقق من وجود الماركة
    echo "1. التحقق من وجود ماركة 'Desconocido'...\n";
    $existingBrand = $db->selectOne(
        "SELECT id FROM brands WHERE name = ?",
        ['Desconocido']
    );

    if ($existingBrand) {
        $defaultBrandId = $existingBrand['id'];
        echo "   ✅ الماركة موجودة بالفعل (ID: $defaultBrandId)\n";
    } else {
        // إضافة الماركة
        echo "   📝 إضافة ماركة 'Desconocido'...\n";
        $defaultBrandId = $db->insert(
            "INSERT INTO brands (name, created_at) VALUES (?, NOW())",
            ['Desconocido']
        );
        echo "   ✅ تم إضافة الماركة بنجاح (ID: $defaultBrandId)\n";
    }

    // 2. التحقق من وجود الموديل
    echo "\n2. التحقق من وجود موديل 'Dispositivo Personalizado'...\n";
    $existingModel = $db->selectOne(
        "SELECT id FROM models WHERE brand_id = ? AND name = ?",
        [$defaultBrandId, 'Dispositivo Personalizado']
    );

    if ($existingModel) {
        $defaultModelId = $existingModel['id'];
        echo "   ✅ الموديل موجود بالفعل (ID: $defaultModelId)\n";
    } else {
        // إضافة الموديل
        echo "   📝 إضافة موديل 'Dispositivo Personalizado'...\n";
        $defaultModelId = $db->insert(
            "INSERT INTO models (brand_id, name, created_at) VALUES (?, ?, NOW())",
            [$defaultBrandId, 'Dispositivo Personalizado']
        );
        echo "   ✅ تم إضافة الموديل بنجاح (ID: $defaultModelId)\n";
    }

    // 3. حفظ الإعدادات في جدول config
    echo "\n3. حفظ الإعدادات في جدول config...\n";

    // حذف الإعدادات القديمة إن وجدت
    $db->execute("DELETE FROM config WHERE setting_key IN ('default_unknown_brand_id', 'default_unknown_model_id')");

    // إضافة الإعدادات الجديدة
    $db->insert(
        "INSERT INTO config (setting_key, setting_value, description, created_at) VALUES (?, ?, ?, NOW())",
        ['default_unknown_brand_id', $defaultBrandId, 'ID de la marca por defecto para dispositivos personalizados']
    );

    $db->insert(
        "INSERT INTO config (setting_key, setting_value, description, created_at) VALUES (?, ?, ?, NOW())",
        ['default_unknown_model_id', $defaultModelId, 'ID del modelo por defecto para dispositivos personalizados']
    );

    echo "   ✅ تم حفظ الإعدادات بنجاح\n";

    // 4. عرض النتائج النهائية
    echo "\n==============================================\n";
    echo "✅ Migration مكتمل بنجاح!\n";
    echo "==============================================\n";
    echo "Default Brand ID: $defaultBrandId\n";
    echo "Default Model ID: $defaultModelId\n";
    echo "Brand Name: Desconocido\n";
    echo "Model Name: Dispositivo Personalizado\n";
    echo "==============================================\n";

    // 5. التحقق من البيانات
    echo "\n5. التحقق من البيانات المضافة...\n";
    $verifyBrand = $db->selectOne("SELECT * FROM brands WHERE id = ?", [$defaultBrandId]);
    $verifyModel = $db->selectOne("SELECT * FROM models WHERE id = ?", [$defaultModelId]);

    echo "   ماركة: " . $verifyBrand['name'] . " (ID: " . $verifyBrand['id'] . ")\n";
    echo "   موديل: " . $verifyModel['name'] . " (ID: " . $verifyModel['id'] . ")\n";
    echo "   ✅ التحقق مكتمل\n";

    echo "\n✨ يمكنك الآن استخدام خيار 'Otro Dispositivo' في صفحة الإصلاحات\n";

} catch (Exception $e) {
    echo "\n❌ خطأ في Migration:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
