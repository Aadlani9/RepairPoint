<?php
/**
 * RepairPoint - Configuración del Sistema المحسنة والمُصححة
 * إصلاح شامل لجميع المشاكل + تبسيط الكود
 */

// تنظيف Output Buffer قبل بدء الجلسة
if (ob_get_length()) {
    ob_end_clean();
}

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación y permisos de administrador
authMiddleware();

if (!isAdmin()) {
    setMessage('No tienes permisos para acceder a esta página', MSG_ERROR);
    header('Location: ' . url('pages/dashboard.php'));
    exit;
}

$page_title = 'Configuración del Sistema';
$current_user = getCurrentUser();
$shop_id = $_SESSION['shop_id'];

// معالجة طلبات AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $response = ['success' => false, 'message' => 'Token de seguridad inválido'];

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } else {
            setMessage($response['message'], MSG_ERROR);
        }
    } else {
        $action = $_POST['action'] ?? '';
        $success = false;
        $message = '';
        $active_tab = $_POST['active_tab'] ?? 'shop-settings';

        switch ($action) {
            case 'update_shop':
                $success = updateShopSettings($_POST, $shop_id);
                $message = $success ? 'Información del taller actualizada correctamente' : 'Error al actualizar la información';
                break;

            case 'upload_logo':
                $result = uploadShopLogo($_FILES['logo'] ?? null, $shop_id);
                $success = $result['success'];
                $message = $result['message'];
                break;

            case 'add_brand':
                $success = addBrand($_POST['brand_name'], $shop_id);
                $message = $success ? 'Marca agregada correctamente' : 'Error al agregar la marca';
                $active_tab = 'brands-models';
                break;

            case 'add_model':
                $success = addModel($_POST['brand_id'], $_POST['model_name'], $shop_id);
                $message = $success ? 'Modelo agregado correctamente' : 'Error al agregar el modelo';
                $active_tab = 'brands-models';
                break;

            case 'delete_brand':
                $success = deleteBrand($_POST['brand_id'], $shop_id);
                $message = $success ? 'Marca eliminada correctamente' : 'Error al eliminar la marca (puede estar en uso)';
                $active_tab = 'brands-models';
                break;

            case 'delete_model':
                $success = deleteModel($_POST['model_id'], $shop_id);
                $message = $success ? 'Modelo eliminado correctamente' : 'Error al eliminar el modelo (puede estar en uso)';
                $active_tab = 'brands-models';
                break;

            case 'add_issue':
                $success = addCommonIssue($_POST['category'], $_POST['issue_text'], $shop_id);
                $message = $success ? 'Problema común agregado correctamente' : 'Error al agregar el problema';
                $active_tab = 'common-issues';
                break;

            case 'delete_issue':
                $success = deleteCommonIssue($_POST['issue_id'], $shop_id);
                $message = $success ? 'Problema eliminado correctamente' : 'Error al eliminar el problema';
                $active_tab = 'common-issues';
                break;
        }

        // إرجاع استجابة AJAX
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $message,
                'active_tab' => $active_tab
            ]);
            exit;
        }

        setMessage($message, $success ? MSG_SUCCESS : MSG_ERROR);

        // إعادة توجيه مع المحافظة على التبويب النشط
        if ($success) {
            header('Location: ' . url('pages/settings.php#' . $active_tab));
            exit;
        }
    }
}

// Obtener datos del taller
$db = getDB();
$shop = $db->selectOne("SELECT * FROM shops WHERE id = ?", [$shop_id]);

// Obtener marcas y modelos - محسن للأداء
$brands = $db->select("SELECT * FROM brands ORDER BY name");
$models_by_brand = [];
foreach ($brands as $brand) {
    $models_by_brand[$brand['id']] = $db->select(
        "SELECT * FROM models WHERE brand_id = ? ORDER BY name",
        [$brand['id']]
    );
}

// Obtener problemas comunes
$common_issues = $db->select("SELECT * FROM common_issues ORDER BY category, issue_text");
$issues_by_category = [];
foreach ($common_issues as $issue) {
    $category = $issue['category'] ?: 'General';
    if (!isset($issues_by_category[$category])) {
        $issues_by_category[$category] = [];
    }
    $issues_by_category[$category][] = $issue;
}

// ===================================================
// دوال المعالجة المحسنة
// ===================================================

function updateShopSettings($data, $shop_id) {
    $required_fields = ['name', 'phone1'];
    $errors = validateRequired($data, $required_fields);

    if (!empty($errors)) return false;

    $db = getDB();

    if (!empty($data['email']) && !isValidEmail($data['email'])) {
        return false;
    }

    try {
        $updated = $db->update(
            "UPDATE shops SET 
                name = ?, email = ?, phone1 = ?, phone2 = ?, 
                address = ?, website = ?, city = ?, country = ?, notes = ?
             WHERE id = ?",
            [
                cleanString($data['name']),
                cleanString($data['email'] ?? ''),
                cleanString($data['phone1']),
                cleanString($data['phone2'] ?? ''),
                cleanString($data['address'] ?? ''),
                cleanString($data['website'] ?? ''),
                cleanString($data['city'] ?? ''),
                cleanString($data['country'] ?? 'España'),
                cleanString($data['notes'] ?? ''),
                $shop_id
            ]
        );

        if ($updated !== false) {
            logActivity('shop_settings_updated', "Configuración del taller actualizada", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error actualizando configuración: " . $e->getMessage());
    }

    return false;
}

function uploadShopLogo($file, $shop_id) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }

    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowed_types)];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 2MB'];
    }

    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_filename = 'logo_' . $shop_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $db = getDB();
        $old_logo = $db->selectOne("SELECT logo FROM shops WHERE id = ?", [$shop_id])['logo'] ?? '';
        $logo_url = 'assets/uploads/' . $new_filename;
        $updated = $db->update("UPDATE shops SET logo = ? WHERE id = ?", [$logo_url, $shop_id]);

        if ($updated !== false) {
            if ($old_logo && file_exists('../' . $old_logo)) {
                unlink('../' . $old_logo);
            }
            logActivity('shop_logo_updated', "Logo del taller actualizado", $_SESSION['user_id']);
            return ['success' => true, 'message' => 'Logo subido correctamente'];
        } else {
            unlink($upload_path);
            return ['success' => false, 'message' => 'Error al guardar en la base de datos'];
        }
    }

    return ['success' => false, 'message' => 'Error al subir el archivo'];
}

function addBrand($name, $shop_id) {
    $name = trim($name);
    if (empty($name)) return false;

    $db = getDB();
    $existing = $db->selectOne("SELECT id FROM brands WHERE name = ?", [$name]);
    if ($existing) return false;

    try {
        $brand_id = $db->insert("INSERT INTO brands (name) VALUES (?)", [$name]);
        if ($brand_id) {
            logActivity('brand_added', "Marca agregada: $name", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error agregando marca: " . $e->getMessage());
    }

    return false;
}

function addModel($brand_id, $name, $shop_id) {
    $brand_id = intval($brand_id);
    $name = trim($name);

    if (!$brand_id || empty($name)) return false;

    $db = getDB();
    $brand = $db->selectOne("SELECT name FROM brands WHERE id = ?", [$brand_id]);
    if (!$brand) return false;

    $existing = $db->selectOne("SELECT id FROM models WHERE brand_id = ? AND name = ?", [$brand_id, $name]);
    if ($existing) return false;

    try {
        $model_id = $db->insert("INSERT INTO models (brand_id, name) VALUES (?, ?)", [$brand_id, $name]);
        if ($model_id) {
            logActivity('model_added', "Modelo agregado: {$brand['name']} $name", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error agregando modelo: " . $e->getMessage());
    }

    return false;
}

function deleteBrand($brand_id, $shop_id) {
    $brand_id = intval($brand_id);
    if (!$brand_id) return false;

    $db = getDB();
    $brand = $db->selectOne("SELECT name FROM brands WHERE id = ?", [$brand_id]);
    if (!$brand) return false;

    $used_in_repairs = $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE brand_id = ?", [$brand_id])['count'] ?? 0;
    if ($used_in_repairs > 0) return false;

    try {
        $db->beginTransaction();
        $db->delete("DELETE FROM models WHERE brand_id = ?", [$brand_id]);
        $deleted = $db->delete("DELETE FROM brands WHERE id = ?", [$brand_id]);

        if ($deleted) {
            $db->commit();
            logActivity('brand_deleted', "Marca eliminada: {$brand['name']}", $_SESSION['user_id']);
            return true;
        } else {
            $db->rollback();
        }
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error eliminando marca: " . $e->getMessage());
    }

    return false;
}

function deleteModel($model_id, $shop_id) {
    $model_id = intval($model_id);
    if (!$model_id) return false;

    $db = getDB();
    $model = $db->selectOne(
        "SELECT m.name, b.name as brand_name FROM models m JOIN brands b ON m.brand_id = b.id WHERE m.id = ?",
        [$model_id]
    );
    if (!$model) return false;

    $used_in_repairs = $db->selectOne("SELECT COUNT(*) as count FROM repairs WHERE model_id = ?", [$model_id])['count'] ?? 0;
    if ($used_in_repairs > 0) return false;

    try {
        $deleted = $db->delete("DELETE FROM models WHERE id = ?", [$model_id]);
        if ($deleted) {
            logActivity('model_deleted', "Modelo eliminado: {$model['brand_name']} {$model['name']}", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error eliminando modelo: " . $e->getMessage());
    }

    return false;
}

function addCommonIssue($category, $issue_text, $shop_id) {
    $category = trim($category);
    $issue_text = trim($issue_text);

    if (empty($issue_text)) return false;

    $db = getDB();
    $existing = $db->selectOne("SELECT id FROM common_issues WHERE issue_text = ?", [$issue_text]);
    if ($existing) return false;

    try {
        $issue_id = $db->insert(
            "INSERT INTO common_issues (category, issue_text) VALUES (?, ?)",
            [$category ?: null, $issue_text]
        );

        if ($issue_id) {
            logActivity('common_issue_added', "Problema común agregado: $issue_text", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error agregando problema común: " . $e->getMessage());
    }

    return false;
}

function deleteCommonIssue($issue_id, $shop_id) {
    $issue_id = intval($issue_id);
    if (!$issue_id) return false;

    $db = getDB();
    $issue = $db->selectOne("SELECT issue_text FROM common_issues WHERE id = ?", [$issue_id]);
    if (!$issue) return false;

    try {
        $deleted = $db->delete("DELETE FROM common_issues WHERE id = ?", [$issue_id]);
        if ($deleted) {
            logActivity('common_issue_deleted', "Problema común eliminado: {$issue['issue_text']}", $_SESSION['user_id']);
            return true;
        }
    } catch (Exception $e) {
        error_log("Error eliminando problema común: " . $e->getMessage());
    }

    return false;
}

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= url('pages/dashboard.php') ?>">
                        <i class="bi bi-house"></i> Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-gear"></i> Configuración
                </li>
            </ol>
        </nav>

        <!-- Header de la página -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header bg-primary text-white p-4 rounded">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-1">
                                <i class="bi bi-gear me-2"></i>
                                Configuración del Sistema
                            </h1>
                            <p class="mb-0 opacity-75">
                                Gestiona la configuración del taller y del sistema
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-light" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mostrar mensajes -->
        <?php displayMessage(); ?>

        <div class="row">
            <!-- Navegación lateral -->
            <div class="col-lg-3 mb-4">
                <div class="settings-nav">
                    <div class="list-group">
                        <a href="#shop-settings" class="list-group-item list-group-item-action active" data-bs-toggle="pill">
                            <i class="bi bi-shop me-2"></i>Información del Taller
                        </a>
                        <a href="#brands-models" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="bi bi-tags me-2"></i>Marcas y Modelos
                        </a>
                        <a href="#common-issues" class="list-group-item list-group-item-action" data-bs-toggle="pill">
                            <i class="bi bi-exclamation-triangle me-2"></i>Problemas Comunes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Información del Taller -->
                    <div class="tab-pane fade show active" id="shop-settings">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-shop me-2"></i>Información del Taller
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="update_shop">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shop_name" class="form-label">Nombre del Taller *</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="shop_name"
                                                   name="name"
                                                   value="<?= safeHtml($shop['name']) ?>"
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor introduce el nombre del taller
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shop_email" class="form-label">Email</label>
                                            <input type="email"
                                                   class="form-control"
                                                   id="shop_email"
                                                   name="email"
                                                   value="<?= safeHtml($shop['email']) ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shop_phone1" class="form-label">Teléfono Principal *</label>
                                            <input type="tel"
                                                   class="form-control"
                                                   id="shop_phone1"
                                                   name="phone1"
                                                   value="<?= safeHtml($shop['phone1']) ?>"
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor introduce el teléfono principal
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shop_phone2" class="form-label">Teléfono Secundario</label>
                                            <input type="tel"
                                                   class="form-control"
                                                   id="shop_phone2"
                                                   name="phone2"
                                                   value="<?= safeHtml($shop['phone2']) ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="shop_address" class="form-label">Dirección</label>
                                        <textarea class="form-control"
                                                  id="shop_address"
                                                  name="address"
                                                  rows="3"><?= safeHtml($shop['address']) ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="shop_city" class="form-label">Ciudad</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="shop_city"
                                                   name="city"
                                                   value="<?= safeHtml($shop['city']) ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="shop_country" class="form-label">País</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="shop_country"
                                                   name="country"
                                                   value="<?= safeHtml($shop['country'] ?: 'España') ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="shop_website" class="form-label">Sitio Web</label>
                                        <input type="url"
                                               class="form-control"
                                               id="shop_website"
                                               name="website"
                                               value="<?= safeHtml($shop['website']) ?>"
                                               placeholder="https://ejemplo.com">
                                    </div>

                                    <div class="mb-3">
                                        <label for="shop_notes" class="form-label">Notas</label>
                                        <textarea class="form-control"
                                                  id="shop_notes"
                                                  name="notes"
                                                  rows="3"><?= safeHtml($shop['notes']) ?></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-2"></i>Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Logo del Taller -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-image me-2"></i>Logo del Taller
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="current-logo mb-3">
                                            <?php if ($shop['logo']): ?>
                                                <img src="<?= url($shop['logo']) ?>"
                                                     alt="Logo del taller"
                                                     class="img-thumbnail"
                                                     style="max-width: 200px; max-height: 200px;">
                                            <?php else: ?>
                                                <div class="no-logo bg-light p-4 text-center rounded">
                                                    <i class="bi bi-image display-4 text-muted"></i>
                                                    <p class="text-muted mb-0">Sin logo</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <form method="POST" action="" enctype="multipart/form-data">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="upload_logo">

                                            <div class="mb-3">
                                                <label for="logo" class="form-label">Subir nuevo logo</label>
                                                <input type="file"
                                                       class="form-control"
                                                       id="logo"
                                                       name="logo"
                                                       accept="image/*"
                                                       required>
                                                <div class="form-text">
                                                    Formatos soportados: JPG, PNG, GIF<br>
                                                    Tamaño máximo: 2MB
                                                </div>
                                            </div>

                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-upload me-2"></i>Subir Logo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Marcas y Modelos -->
                    <div class="tab-pane fade" id="brands-models">
                        <div class="row">
                            <!-- Marcas -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-tags me-2"></i>Marcas
                                        </h5>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="list-group list-group-flush" id="brands-list">
                                            <?php if (empty($brands)): ?>
                                                <div class="list-group-item text-center text-muted">
                                                    No hay marcas registradas
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($brands as $brand): ?>
                                                    <div class="list-group-item d-flex justify-content-between align-items-center brand-item"
                                                         data-brand-id="<?= $brand['id'] ?>"
                                                         style="cursor: pointer;">
                                                        <div onclick="displayModelsSimple(<?= $brand['id'] ?>, '<?= addslashes($brand['name']) ?>')">
                                                            <strong><?= safeHtml($brand['name']) ?></strong>
                                                            <small class="text-muted d-block">
                                                                <?= count($models_by_brand[$brand['id']] ?? []) ?> modelos
                                                            </small>
                                                        </div>
                                                        <div class="btn-group btn-group-sm">
                                                            <button class="btn btn-outline-danger"
                                                                    onclick="event.stopPropagation(); deleteBrandAjax(<?= $brand['id'] ?>)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modelos -->
                            <div class="col-lg-6 mb-4">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-phone me-2"></i>Modelos
                                        </h5>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModelModal">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="models-container">
                                            <div class="p-4 text-center text-muted">
                                                <i class="bi bi-phone display-4 mb-3"></i>
                                                <p class="mb-0">Selecciona una marca para ver los modelos</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Problemas Comunes -->
                    <div class="tab-pane fade" id="common-issues">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Problemas Comunes
                                </h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIssueModal">
                                    <i class="bi bi-plus me-2"></i>Agregar Problema
                                </button>
                            </div>
                            <div class="card-body" id="issues-container">
                                <?php if (empty($issues_by_category)): ?>
                                    <div class="text-center text-muted p-4">
                                        <i class="bi bi-exclamation-triangle display-4 mb-3"></i>
                                        <p>No hay problemas comunes registrados</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($issues_by_category as $category => $issues): ?>
                                        <div class="category-section mb-4">
                                            <h6 class="category-title fw-bold text-primary border-bottom pb-2">
                                                <?= safeHtml($category) ?>
                                            </h6>
                                            <div class="issues-list">
                                                <?php foreach ($issues as $issue): ?>
                                                    <div class="issue-item d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded">
                                                        <span><?= safeHtml($issue['issue_text']) ?></span>
                                                        <button class="btn btn-sm btn-outline-danger"
                                                                onclick="deleteIssueAjax(<?= $issue['id'] ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Marca -->
    <div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="addBrandModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBrandModalLabel">
                        <i class="bi bi-tags me-2"></i>Agregar Marca
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="addBrandForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="add_brand">
                        <input type="hidden" name="active_tab" value="brands-models">

                        <div class="mb-3">
                            <label for="brand_name" class="form-label">Nombre de la Marca *</label>
                            <input type="text"
                                   class="form-control"
                                   id="brand_name"
                                   name="brand_name"
                                   placeholder="Ej: Apple, Samsung"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Modelo -->
    <div class="modal fade" id="addModelModal" tabindex="-1" aria-labelledby="addModelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModelModalLabel">
                        <i class="bi bi-phone me-2"></i>Agregar Modelo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="addModelForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="add_model">
                        <input type="hidden" name="active_tab" value="brands-models">

                        <div class="mb-3">
                            <label for="model_brand_id" class="form-label">Marca *</label>
                            <select class="form-select" id="model_brand_id" name="brand_id" required>
                                <option value="">Selecciona una marca</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= $brand['id'] ?>">
                                        <?= safeHtml($brand['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="model_name" class="form-label">Nombre del Modelo *</label>
                            <input type="text"
                                   class="form-control"
                                   id="model_name"
                                   name="model_name"
                                   placeholder="Ej: iPhone 15, Galaxy S24"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Problema Común -->
    <div class="modal fade" id="addIssueModal" tabindex="-1" aria-labelledby="addIssueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addIssueModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Agregar Problema Común
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="addIssueForm">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="add_issue">
                        <input type="hidden" name="active_tab" value="common-issues">

                        <div class="mb-3">
                            <label for="issue_category" class="form-label">Categoría</label>
                            <input type="text"
                                   class="form-control"
                                   id="issue_category"
                                   name="category"
                                   placeholder="Ej: Pantalla, Batería, Carga"
                                   list="existing-categories">
                            <datalist id="existing-categories">
                                <?php
                                $categories = array_keys($issues_by_category);
                                foreach ($categories as $category): ?>
                                <option value="<?= safeHtml($category) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="mb-3">
                            <label for="issue_text" class="form-label">Descripción del Problema *</label>
                            <input type="text"
                                   class="form-control"
                                   id="issue_text"
                                   name="issue_text"
                                   placeholder="Ej: Pantalla rota, No carga"
                                   required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* ===================================================
         * CSS محسن ومنظم
         * ================================================= */

        .page-header {
            background: linear-gradient(135deg, var(--bs-primary) 0%, #0056b3 100%);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        /* تحسين التنقل */
        .settings-nav .list-group-item {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.3s ease;
        }

        .settings-nav .list-group-item:hover {
            background-color: rgba(13, 110, 253, 0.1);
            transform: translateX(5px);
        }

        .settings-nav .list-group-item.active {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        /* تحسين البطاقات */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .card-header {
            background: rgba(13, 110, 253, 0.05);
            border-bottom: 1px solid rgba(13, 110, 253, 0.1);
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1rem 1.5rem;
        }

        /* تحسين عرض الماركات */
        .brand-item {
            transition: all 0.3s ease;
            border-radius: 0.5rem !important;
        }

        .brand-item:hover {
            background-color: rgba(13, 110, 253, 0.05) !important;
            transform: translateX(5px);
        }

        .brand-item.selected {
            background-color: rgba(13, 110, 253, 0.1) !important;
            border-left: 4px solid var(--bs-primary) !important;
            transform: translateX(5px);
        }

        /* تحسين عرض الموديلات */
        .model-item {
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            background: #f8f9fa;
            margin-bottom: 0.5rem;
        }

        .model-item:hover {
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateX(3px);
        }

        /* تحسين الشعار */
        .current-logo img {
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .no-logo {
            border: 2px dashed #dee2e6;
            border-radius: 1rem;
        }

        /* تحسين المشاكل الشائعة */
        .category-title {
            color: var(--bs-primary);
            margin-bottom: 0.75rem;
        }

        .issue-item {
            transition: all 0.3s ease;
            border-radius: 0.5rem !important;
        }

        .issue-item:hover {
            background-color: rgba(13, 110, 253, 0.1) !important;
            transform: translateX(5px);
        }

        /* Loading animations */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                text-align: center;
                padding: 2rem 1rem !important;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .settings-nav {
                margin-bottom: 2rem;
            }

            .settings-nav .list-group {
                display: flex;
                flex-direction: row;
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 0.5rem;
            }

            .settings-nav .list-group-item {
                flex: 0 0 auto;
                margin-right: 0.5rem;
                margin-bottom: 0;
            }

            .brand-item:hover,
            .model-item:hover,
            .issue-item:hover {
                transform: none;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .page-header {
                margin-left: -0.75rem;
                margin-right: -0.75rem;
                border-radius: 0;
            }

            .card-body {
                padding: 1rem;
            }

            .current-logo img {
                max-width: 150px !important;
                max-height: 150px !important;
            }
        }
    </style>

    <script>
        // ===================================================
        // JavaScript محسن ومُصحح بالكامل
        // ===================================================

        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Settings page loaded successfully');

            // تحميل البيانات
            loadModelsData();

            // إعداد الـ Tabs
            setupTabHandling();

            // إعداد النماذج
            setupAjaxForms();

            // إعداد التفاعلات
            setupInteractions();
        });

        // ===================================================
        // تحميل بيانات الموديلات
        // ===================================================
        function loadModelsData() {
            window.modelsData = <?= json_encode($models_by_brand) ?>;
            console.log('📱 Models data loaded:', Object.keys(window.modelsData).length, 'brands');
        }

        // ===================================================
        // إعداد التبويبات
        // ===================================================
        function setupTabHandling() {
            // المحافظة على التبويب النشط من الـ URL
            const hash = window.location.hash;
            if (hash) {
                const targetTab = document.querySelector(`a[href="${hash}"]`);
                if (targetTab) {
                    targetTab.click();
                }
            }

            // تحديث الرابط عند تغيير التبويب
            document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (e) {
                    const target = e.target.getAttribute('href');
                    history.replaceState(null, null, target);
                });
            });
        }

        // ===================================================
        // إعداد النماذج AJAX
        // ===================================================
        function setupAjaxForms() {
            // نموذج إضافة الماركة
            const addBrandForm = document.getElementById('addBrandForm');
            if (addBrandForm) {
                addBrandForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitAjaxForm(this, function(response) {
                        if (response.success) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    });
                });
            }

            // نموذج إضافة الموديل
            const addModelForm = document.getElementById('addModelForm');
            if (addModelForm) {
                addModelForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitAjaxForm(this, function(response) {
                        if (response.success) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    });
                });
            }

            // نموذج إضافة المشكلة الشائعة
            const addIssueForm = document.getElementById('addIssueForm');
            if (addIssueForm) {
                addIssueForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitAjaxForm(this, function(response) {
                        if (response.success) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    });
                });
            }
        }

        // ===================================================
        // دالة إرسال النماذج AJAX محسنة
        // ===================================================
        function submitAjaxForm(form, callback) {
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // إظهار مؤشر التحميل
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // إظهار الرسالة
                    showNotification(data.message, data.success ? 'success' : 'error');

                    // إغلاق النموذج إذا نجح
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                        if (modal) {
                            modal.hide();
                            form.reset();
                        }
                    }

                    // استدعاء callback function
                    if (callback) callback(data);
                })
                .catch(error => {
                    console.error('❌ AJAX Error:', error);
                    showNotification('Error de conexión', 'error');
                })
                .finally(() => {
                    // إعادة تعيين الزر
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        }

        // ===================================================
        // دالة عرض الموديلات البسيطة ✅
        // ===================================================
        function displayModelsSimple(brandId, brandName) {

            const modelsContainer = document.getElementById('models-container');
            if (!modelsContainer) {
                console.error('❌ Models container not found');
                return;
            }

            // إزالة التمييز من جميع الماركات
            document.querySelectorAll('.brand-item').forEach(item => {
                item.classList.remove('selected');
            });

            // تمييز الماركة المختارة
            const currentBrand = document.querySelector(`[data-brand-id="${brandId}"]`);
            if (currentBrand) {
                currentBrand.classList.add('selected');
            }

            // الحصول على الموديلات
            const models = window.modelsData[brandId] || [];
            // بناء HTML بطريقة بسيطة وآمنة
            let html = `
            <div class="p-3 border-bottom bg-light">
                <h6 class="mb-1 text-primary">
                    <i class="bi bi-phone me-2"></i>${brandName}
                </h6>
                <small class="text-muted">${models.length} modelo${models.length !== 1 ? 's' : ''}</small>
            </div>
        `;

            if (models.length === 0) {
                html += `
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-phone display-4 mb-3"></i>
                    <p class="mb-3">No hay modelos para esta marca</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModelModal" onclick="preSelectBrand(${brandId})">
                        <i class="bi bi-plus me-1"></i>Agregar primer modelo
                    </button>
                </div>
            `;
            } else {
                html += '<div class="p-2">';

                models.forEach(model => {
                    html += `
                    <div class="model-item d-flex justify-content-between align-items-center p-2 mb-2">
                        <div>
                            <strong>${escapeHtml(model.name)}</strong>
                            <small class="text-muted d-block">ID: ${model.id}</small>
                        </div>
                        <button class="btn btn-outline-danger btn-sm"
                                onclick="deleteModelSimple(${model.id}, '${escapeHtml(model.name)}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                });

                html += '</div>';
            }

            // عرض النتيجة مع تأثير
            modelsContainer.style.opacity = '0.5';
            setTimeout(() => {
                modelsContainer.innerHTML = html;
                modelsContainer.style.opacity = '1';
                modelsContainer.classList.add('fade-in');
            }, 200);
        }

        // ===================================================
        // دالة الحذف البسيطة ✅
        // ===================================================
        function deleteModelSimple(modelId, modelName) {
            if (!confirm(`¿Eliminar "${modelName}"?`)) {
                return;
            }

            console.log('🗑️ Deleting model:', modelName, '(ID:', modelId, ')');

            const formData = new FormData();
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            formData.append('action', 'delete_model');
            formData.append('model_id', modelId);
            formData.append('active_tab', 'brands-models');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');

                    if (data.success) {
                        // إعادة تحميل الصفحة لتحديث البيانات
                        setTimeout(() => location.reload(), 1000);
                    }
                })
                .catch(error => {
                    console.error('❌ Delete error:', error);
                    showNotification('Error de conexión', 'error');
                });
        }

        // ===================================================
        // دوال الحذف للماركات والمشاكل
        // ===================================================
        function deleteBrandAjax(brandId) {
            if (!confirm('¿Estás seguro de eliminar esta marca? Se eliminarán también todos sus modelos.')) {
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            formData.append('action', 'delete_brand');
            formData.append('brand_id', brandId);
            formData.append('active_tab', 'brands-models');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1000);
                    }
                })
                .catch(error => {
                    console.error('❌ Error:', error);
                    showNotification('Error de conexión', 'error');
                });
        }

        function deleteIssueAjax(issueId) {
            if (!confirm('¿Estás seguro de eliminar este problema común?')) {
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            formData.append('action', 'delete_issue');
            formData.append('issue_id', issueId);
            formData.append('active_tab', 'common-issues');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => location.reload(), 1000);
                    }
                })
                .catch(error => {
                    console.error('❌ Error:', error);
                    showNotification('Error de conexión', 'error');
                });
        }

        // ===================================================
        // دوال مساعدة
        // ===================================================

        // تحديد الماركة مسبقاً في النموذج
        function preSelectBrand(brandId) {
            setTimeout(() => {
                const brandSelect = document.getElementById('model_brand_id');
                if (brandSelect) {
                    brandSelect.value = brandId;
                }
            }, 100);
        }

        // تأمين HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // عرض الإشعارات
        function showNotification(message, type) {
            if (typeof Utils !== 'undefined' && Utils.showNotification) {
                Utils.showNotification(message, type);
            } else {
                // Fallback إلى alert
                alert(message);
            }
        }

        // إعداد التفاعلات الإضافية
        function setupInteractions() {
            // تنظيف النماذج عند إغلاق النوافذ المنبثقة
            ['addBrandModal', 'addModelModal', 'addIssueModal'].forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('hidden.bs.modal', function() {
                        const form = this.querySelector('form');
                        if (form) {
                            form.reset();
                            form.classList.remove('was-validated');
                        }
                    });
                }
            });

            // إعداد validation للنماذج
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });

        }

        // ===================================================
        // Global Functions (للوصول من HTML)
        // ===================================================
        window.displayModelsSimple = displayModelsSimple;
        window.deleteModelSimple = deleteModelSimple;
        window.deleteBrandAjax = deleteBrandAjax;
        window.deleteIssueAjax = deleteIssueAjax;
        window.preSelectBrand = preSelectBrand;

        console.log('✅ Settings JavaScript fully loaded and optimized');
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>