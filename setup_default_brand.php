<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Marca Predeterminada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            max-width: 700px;
            width: 100%;
        }
        .log-entry {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .log-success { background: #d4edda; color: #155724; }
        .log-error { background: #f8d7da; color: #721c24; }
        .log-info { background: #d1ecf1; color: #0c5460; }
        .log-warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="setup-container">
        <h2 class="mb-4 text-center">
            <i class="bi bi-gear"></i> Setup - Marca Predeterminada
        </h2>
        <p class="text-muted text-center mb-4">
            Este script agregará una marca y modelo predeterminado para dispositivos personalizados
        </p>

        <div class="log-container">
<?php
// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

function logMessage($message, $type = 'info') {
    $class = 'log-' . $type;
    echo "<div class='log-entry $class'>$message</div>\n";
    flush();
    ob_flush();
}

try {
    logMessage("🚀 Iniciando migration...", "info");

    $db = getDB();

    // 1. التحقق من وجود الماركة
    logMessage("1️⃣ Verificando marca 'Desconocido'...", "info");
    $existingBrand = $db->selectOne(
        "SELECT id FROM brands WHERE name = ?",
        ['Desconocido']
    );

    if ($existingBrand) {
        $defaultBrandId = $existingBrand['id'];
        logMessage("✅ Marca ya existe (ID: $defaultBrandId)", "success");
    } else {
        // إضافة الماركة
        logMessage("📝 Agregando marca 'Desconocido'...", "info");
        $defaultBrandId = $db->insert(
            "INSERT INTO brands (name, created_at) VALUES (?, NOW())",
            ['Desconocido']
        );
        logMessage("✅ Marca agregada exitosamente (ID: $defaultBrandId)", "success");
    }

    // 2. التحقق من وجود الموديل
    logMessage("2️⃣ Verificando modelo 'Dispositivo Personalizado'...", "info");
    $existingModel = $db->selectOne(
        "SELECT id FROM models WHERE brand_id = ? AND name = ?",
        [$defaultBrandId, 'Dispositivo Personalizado']
    );

    if ($existingModel) {
        $defaultModelId = $existingModel['id'];
        logMessage("✅ Modelo ya existe (ID: $defaultModelId)", "success");
    } else {
        // إضافة الموديل
        logMessage("📝 Agregando modelo 'Dispositivo Personalizado'...", "info");
        $defaultModelId = $db->insert(
            "INSERT INTO models (brand_id, name, created_at) VALUES (?, ?, NOW())",
            [$defaultBrandId, 'Dispositivo Personalizado']
        );
        logMessage("✅ Modelo agregado exitosamente (ID: $defaultModelId)", "success");
    }

    // 3. حفظ الإعدادات في جدول config
    logMessage("3️⃣ Guardando configuraciones...", "info");

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

    logMessage("✅ Configuraciones guardadas correctamente", "success");

    // 4. التحقق من البيانات
    logMessage("4️⃣ Verificando datos...", "info");
    $verifyBrand = $db->selectOne("SELECT * FROM brands WHERE id = ?", [$defaultBrandId]);
    $verifyModel = $db->selectOne("SELECT * FROM models WHERE id = ?", [$defaultModelId]);

    logMessage("   📌 Marca: " . $verifyBrand['name'] . " (ID: " . $verifyBrand['id'] . ")", "info");
    logMessage("   📌 Modelo: " . $verifyModel['name'] . " (ID: " . $verifyModel['id'] . ")", "info");

    // 5. عرض النتائج النهائية
    echo "<div class='alert alert-success mt-4' role='alert'>";
    echo "<h5>✨ ¡Migration completado exitosamente!</h5>";
    echo "<hr>";
    echo "<p class='mb-1'><strong>Default Brand ID:</strong> $defaultBrandId</p>";
    echo "<p class='mb-1'><strong>Default Model ID:</strong> $defaultModelId</p>";
    echo "<p class='mb-1'><strong>Nombre de Marca:</strong> Desconocido</p>";
    echo "<p class='mb-0'><strong>Nombre de Modelo:</strong> Dispositivo Personalizado</p>";
    echo "</div>";

    echo "<div class='text-center mt-4'>";
    echo "<a href='pages/add_repair.php' class='btn btn-primary'>Ir a Nueva Reparación</a>";
    echo "<a href='pages/dashboard.php' class='btn btn-secondary ms-2'>Ir al Dashboard</a>";
    echo "</div>";

} catch (Exception $e) {
    logMessage("❌ Error en Migration: " . $e->getMessage(), "error");
    echo "<div class='alert alert-danger mt-4' role='alert'>";
    echo "<h5>❌ Error</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
