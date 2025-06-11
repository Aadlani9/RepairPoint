<?php
/**
 * RepairPoint - Shop Setup System
 * نظام إعداد المحلات الجديدة مع البيانات الافتراضية
 */

// Prevenir acceso directo
if (!defined('SECURE_ACCESS')) {
    die('Acceso denegado');
}

/**
 * فئة إعداد المحلات الجديدة
 */
class ShopSetup {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * التحقق من حالة إعداد المحل
     */
    public function isShopSetupComplete($shop_id) {
        $shop = $this->db->selectOne(
            "SELECT setup_completed FROM shops WHERE id = ?",
            [$shop_id]
        );

        return $shop ? (bool)$shop['setup_completed'] : false;
    }

    /**
     * إعداد محل جديد بالبيانات الافتراضية
     */
    public function setupNewShop($shop_id, $options = []) {
        try {
            $this->db->beginTransaction();

            // التحقق من وجود المحل
            $shop = $this->db->selectOne("SELECT id, name FROM shops WHERE id = ?", [$shop_id]);
            if (!$shop) {
                throw new Exception('المحل غير موجود');
            }

            // التحقق من عدم وجود إعداد مسبق
            if ($this->isShopSetupComplete($shop_id)) {
                throw new Exception('المحل تم إعداده مسبقاً');
            }

            // إعداد البيانات الافتراضية
            $setup_result = [
                'brands_added' => 0,
                'models_added' => 0,
                'issues_added' => 0,
                'errors' => []
            ];

            // خيارات الإعداد
            $setup_brands = $options['setup_brands'] ?? true;
            $setup_models = $options['setup_models'] ?? true;
            $setup_issues = $options['setup_issues'] ?? true;
            $selected_brands = $options['selected_brands'] ?? [];

            // إعداد البراندات والموديلات
            if ($setup_brands) {
                $result = $this->setupBrandsAndModels($shop_id, $selected_brands, $setup_models);
                $setup_result['brands_added'] = $result['brands_added'];
                $setup_result['models_added'] = $result['models_added'];
                $setup_result['errors'] = array_merge($setup_result['errors'], $result['errors']);
            }

            // إعداد المشاكل الشائعة
            if ($setup_issues) {
                $result = $this->setupCommonIssues($shop_id, $options['selected_categories'] ?? []);
                $setup_result['issues_added'] = $result['issues_added'];
                $setup_result['errors'] = array_merge($setup_result['errors'], $result['errors']);
            }

            // تحديث حالة الإعداد
            $this->db->update(
                "UPDATE shops SET setup_completed = TRUE WHERE id = ?",
                [$shop_id]
            );

            $this->db->commit();

            // تسجيل النشاط
            logActivity('shop_setup_completed', "إعداد المحل {$shop['name']} مكتمل", null);

            return [
                'success' => true,
                'message' => 'تم إعداد المحل بنجاح',
                'data' => $setup_result
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("خطأ في إعداد المحل: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * إعداد البراندات والموديلات
     */
    private function setupBrandsAndModels($shop_id, $selected_brands = [], $include_models = true) {
        $brands_added = 0;
        $models_added = 0;
        $errors = [];

        try {
            // الحصول على البراندات من التمبليت
            $where_clause = '';
            $params = [];

            if (!empty($selected_brands)) {
                $placeholders = str_repeat('?,', count($selected_brands) - 1) . '?';
                $where_clause = "WHERE id IN ($placeholders) AND is_active = TRUE";
                $params = $selected_brands;
            } else {
                $where_clause = "WHERE is_active = TRUE";
            }

            $brand_templates = $this->db->select(
                "SELECT id, name FROM default_brands_template $where_clause ORDER BY name",
                $params
            );

            foreach ($brand_templates as $brand_template) {
                try {
                    // إدراج البراند للمحل
                    $brand_id = $this->db->insert(
                        "INSERT INTO brands (shop_id, name, created_at) VALUES (?, ?, NOW())",
                        [$shop_id, $brand_template['name']]
                    );

                    if ($brand_id) {
                        $brands_added++;

                        // إدراج الموديلات إذا كان مطلوباً
                        if ($include_models) {
                            $model_templates = $this->db->select(
                                "SELECT name FROM default_models_template 
                                 WHERE brand_template_id = ? AND is_active = TRUE 
                                 ORDER BY name",
                                [$brand_template['id']]
                            );

                            foreach ($model_templates as $model_template) {
                                try {
                                    $model_id = $this->db->insert(
                                        "INSERT INTO models (shop_id, brand_id, name, created_at) VALUES (?, ?, ?, NOW())",
                                        [$shop_id, $brand_id, $model_template['name']]
                                    );

                                    if ($model_id) {
                                        $models_added++;
                                    }
                                } catch (Exception $e) {
                                    $errors[] = "خطأ في إدراج الموديل {$model_template['name']}: " . $e->getMessage();
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "خطأ في إدراج البراند {$brand_template['name']}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $errors[] = "خطأ عام في إعداد البراندات: " . $e->getMessage();
        }

        return [
            'brands_added' => $brands_added,
            'models_added' => $models_added,
            'errors' => $errors
        ];
    }

    /**
     * إعداد المشاكل الشائعة
     */
    private function setupCommonIssues($shop_id, $selected_categories = []) {
        $issues_added = 0;
        $errors = [];

        try {
            // الحصول على المشاكل من التمبليت
            $where_clause = '';
            $params = [];

            if (!empty($selected_categories)) {
                $placeholders = str_repeat('?,', count($selected_categories) - 1) . '?';
                $where_clause = "WHERE category IN ($placeholders) AND is_active = TRUE";
                $params = $selected_categories;
            } else {
                $where_clause = "WHERE is_active = TRUE";
            }

            $issue_templates = $this->db->select(
                "SELECT category, issue_text FROM default_issues_template $where_clause ORDER BY category, issue_text",
                $params
            );

            foreach ($issue_templates as $issue_template) {
                try {
                    $issue_id = $this->db->insert(
                        "INSERT INTO common_issues (shop_id, category, issue_text, created_at) VALUES (?, ?, ?, NOW())",
                        [$shop_id, $issue_template['category'], $issue_template['issue_text']]
                    );

                    if ($issue_id) {
                        $issues_added++;
                    }
                } catch (Exception $e) {
                    $errors[] = "خطأ في إدراج المشكلة {$issue_template['issue_text']}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $errors[] = "خطأ عام في إعداد المشاكل الشائعة: " . $e->getMessage();
        }

        return [
            'issues_added' => $issues_added,
            'errors' => $errors
        ];
    }

    /**
     * الحصول على البراندات المتاحة للإعداد
     */
    public function getAvailableBrands() {
        return $this->db->select(
            "SELECT id, name FROM default_brands_template WHERE is_active = TRUE ORDER BY name"
        );
    }

    /**
     * الحصول على الفئات المتاحة للمشاكل الشائعة
     */
    public function getAvailableIssueCategories() {
        return $this->db->select(
            "SELECT DISTINCT category FROM default_issues_template WHERE is_active = TRUE ORDER BY category"
        );
    }

    /**
     * الحصول على عدد الموديلات لكل براند
     */
    public function getBrandModelsCount() {
        return $this->db->select(
            "SELECT dbt.id, dbt.name, COUNT(dmt.id) as models_count
             FROM default_brands_template dbt
             LEFT JOIN default_models_template dmt ON dbt.id = dmt.brand_template_id AND dmt.is_active = TRUE
             WHERE dbt.is_active = TRUE
             GROUP BY dbt.id, dbt.name
             ORDER BY dbt.name"
        );
    }

    /**
     * الحصول على عدد المشاكل لكل فئة
     */
    public function getCategoryIssuesCount() {
        return $this->db->select(
            "SELECT category, COUNT(*) as issues_count
             FROM default_issues_template 
             WHERE is_active = TRUE
             GROUP BY category
             ORDER BY category"
        );
    }

    /**
     * إعادة تشغيل الإعداد للمحل (حذف البيانات الحالية وإعادة الإعداد)
     */
    public function resetShopSetup($shop_id, $options = []) {
        try {
            $this->db->beginTransaction();

            // التحقق من وجود المحل
            $shop = $this->db->selectOne("SELECT id, name FROM shops WHERE id = ?", [$shop_id]);
            if (!$shop) {
                throw new Exception('المحل غير موجود');
            }

            // حذف البيانات الحالية
            $this->clearShopData($shop_id);

            // تحديث حالة الإعداد
            $this->db->update(
                "UPDATE shops SET setup_completed = FALSE WHERE id = ?",
                [$shop_id]
            );

            $this->db->commit();

            // إعادة الإعداد
            return $this->setupNewShop($shop_id, $options);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("خطأ في إعادة تشغيل الإعداد: " . $e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * حذف بيانات المحل (brands, models, issues)
     */
    private function clearShopData($shop_id) {
        // التحقق من عدم وجود رeparaciones
        $repairs_count = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM repairs WHERE shop_id = ?",
            [$shop_id]
        )['count'] ?? 0;

        if ($repairs_count > 0) {
            throw new Exception('لا يمكن حذف البيانات لوجود رeparaciones مرتبطة');
        }

        // حذف البيانات بالترتيب الصحيح
        $this->db->delete("DELETE FROM common_issues WHERE shop_id = ?", [$shop_id]);
        $this->db->delete("DELETE FROM models WHERE shop_id = ?", [$shop_id]);
        $this->db->delete("DELETE FROM brands WHERE shop_id = ?", [$shop_id]);
    }

    /**
     * الحصول على إحصائيات الإعداد للمحل
     */
    public function getShopSetupStats($shop_id) {
        $stats = [];

        // عدد البراندات
        $stats['brands_count'] = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM brands WHERE shop_id = ?",
            [$shop_id]
        )['count'] ?? 0;

        // عدد الموديلات
        $stats['models_count'] = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM models WHERE shop_id = ?",
            [$shop_id]
        )['count'] ?? 0;

        // عدد المشاكل الشائعة
        $stats['issues_count'] = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM common_issues WHERE shop_id = ?",
            [$shop_id]
        )['count'] ?? 0;

        // عدد الفئات
        $stats['categories_count'] = $this->db->selectOne(
            "SELECT COUNT(DISTINCT category) as count FROM common_issues WHERE shop_id = ?",
            [$shop_id]
        )['count'] ?? 0;

        // حالة الإعداد
        $stats['setup_completed'] = $this->isShopSetupComplete($shop_id);

        return $stats;
    }
}

// ===================================================
// Helper Functions للـ Shop Setup
// ===================================================

/**
 * الحصول على instance من ShopSetup
 */
function getShopSetup() {
    static $shop_setup = null;
    if ($shop_setup === null) {
        $shop_setup = new ShopSetup();
    }
    return $shop_setup;
}

/**
 * التحقق من إكمال إعداد المحل والتوجيه للإعداد إذا لزم الأمر
 */
function requireShopSetup($shop_id, $redirect_url = null) {
    $shop_setup = getShopSetup();

    if (!$shop_setup->isShopSetupComplete($shop_id)) {
        if ($redirect_url === null) {
            $redirect_url = url('pages/shop_setup.php');
        }

        setMessage('يجب إكمال إعداد المحل أولاً', MSG_WARNING);
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * إعداد محل جديد بالبيانات الافتراضية (سريع)
 */
function quickSetupShop($shop_id) {
    $shop_setup = getShopSetup();

    return $shop_setup->setupNewShop($shop_id, [
        'setup_brands' => true,
        'setup_models' => true,
        'setup_issues' => true,
        'selected_brands' => [], // جميع البراندات
        'selected_categories' => [] // جميع الفئات
    ]);
}

/**
 * إعداد محل مخصص
 */
function customSetupShop($shop_id, $selected_brands = [], $selected_categories = []) {
    $shop_setup = getShopSetup();

    return $shop_setup->setupNewShop($shop_id, [
        'setup_brands' => !empty($selected_brands),
        'setup_models' => !empty($selected_brands),
        'setup_issues' => !empty($selected_categories),
        'selected_brands' => $selected_brands,
        'selected_categories' => $selected_categories
    ]);
}

/**
 * التحقق من وجود بيانات افتراضية للمحل
 */
function hasShopDefaultData($shop_id) {
    $db = getDB();

    $brands_count = $db->selectOne(
        "SELECT COUNT(*) as count FROM brands WHERE shop_id = ?",
        [$shop_id]
    )['count'] ?? 0;

    return $brands_count > 0;
}

/**
 * الحصول على معلومات الإعداد للمحل
 */
function getShopSetupInfo($shop_id) {
    $shop_setup = getShopSetup();

    return [
        'is_setup_complete' => $shop_setup->isShopSetupComplete($shop_id),
        'stats' => $shop_setup->getShopSetupStats($shop_id),
        'available_brands' => $shop_setup->getAvailableBrands(),
        'available_categories' => $shop_setup->getAvailableIssueCategories(),
        'brands_models_count' => $shop_setup->getBrandModelsCount(),
        'categories_issues_count' => $shop_setup->getCategoryIssuesCount()
    ];
}

?>