-- ==========================================
-- RepairPoint - استيراد 490 موديل هاتف
-- ==========================================
-- تاريخ: 2025-11-15 21:26:31
-- إجمالي: 460 موديل
-- مع معرفات: 179
-- بدون معرفات: 281
-- ==========================================

SET NAMES utf8mb4;

-- البراندات
INSERT IGNORE INTO brands (name) VALUES
('Apple'),
('Samsung'),
('Xiaomi'),
('OPPO'),
('Realme'),
('VIVO'),
('Huawei'),
('TCL');

-- الموديلات
INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 1' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 1');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 2' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 2');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 3' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 3');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 4' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 4');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 5' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 5');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 6' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 6');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series SE' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series SE');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series SE 2022' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series SE 2022');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 7' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 7');

INSERT INTO models (brand_id, name)
SELECT id, 'Apple Watch Series 8' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'Apple Watch Series 8');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 16e' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 16e');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 16 Pro Max' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 16 Pro Max');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 16 Pro' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 16 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 16 Plus' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 16 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 16' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 16');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 15 Pro Max' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 15 Pro Max');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 15 Pro' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 15 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 15 Plus' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 15 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 15' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 15');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 14 Pro Max' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 14 Pro Max');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 14 Pro' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 14 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 14 Plus' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 14 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 14' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 14');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone SE 2022' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone SE 2022');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone SE 2020' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone SE 2020');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 13 Pro Max' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 13 Pro Max');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 13 Pro' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 13 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 13' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 13');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 13 Mini' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 13 Mini');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 12 Pro Max' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 12 Pro Max');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 12 Pro' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 12 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 12' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 12');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 12 Mini' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 12 Mini');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 11 Pro Max' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 11 Pro Max');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 11 Pro' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 11 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 11' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 11');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone Xs Max' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone Xs Max');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone XS' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone XS');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone XR' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone XR');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone X' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone X');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 8 Plus' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 8 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 8' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 8');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 7 Plus' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 7 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 7' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 7');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 6s Plus' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 6s Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 6S' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 6S');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 6 Plus' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 6 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 6' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 6');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone SE' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone SE');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 5S' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 5S');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 5C' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 5C');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 5' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 5');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 4S' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 4S');

INSERT INTO models (brand_id, name)
SELECT id, 'iPhone 4' FROM brands WHERE name = 'Apple'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Apple') AND name = 'iPhone 4');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F700 Galaxy Z Flip', 'F700' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F700 Galaxy Z Flip');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F707 Galaxy Z Flip 5G', 'F707' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F707 Galaxy Z Flip 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F711 Galaxy Z Flip 3 5G', 'F711' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F711 Galaxy Z Flip 3 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F721 Galaxy Z Flip 4 5G', 'F721' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F721 Galaxy Z Flip 4 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F731 Galaxy Z Flip 5 5G', 'F731' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F731 Galaxy Z Flip 5 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F741 Galaxy Z Flip 6 5G', 'F741' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F741 Galaxy Z Flip 6 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F900 Galaxy Fold', 'F900' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F900 Galaxy Fold');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F907 Galaxy Fold 5G', 'F907' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F907 Galaxy Fold 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F916 Galaxy Z Fold 2 5G', 'F916' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F916 Galaxy Z Fold 2 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F926 Galaxy Z Fold 3 5G', 'F926' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F926 Galaxy Z Fold 3 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F936 Galaxy Z Fold 4 5G', 'F936' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F936 Galaxy Z Fold 4 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'F946 Galaxy Z Fold 5 5G', 'F946' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'F946 Galaxy Z Fold 5 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A91', 'A91' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A91');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A90 5G', 'A908' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A90 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A80', 'A805' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A80');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A73 5G', 'A736' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A73 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A72', 'A725' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A72');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A71 5G', 'A716' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A71 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A71', 'A715' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A71');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A70', 'A705' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A70');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A60', 'A606' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A60');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A56 5G', 'A566' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A56 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A55 5G', 'A556' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A55 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A54 5G', 'A546' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A54 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A53 5G', 'A536' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A53 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A52s 5G', 'A528' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A52s 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A52', 'A525/A526' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A52');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A51', 'A515' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A51');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A50s', 'A507' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A50s');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A50', 'A505' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A50');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A42 5G', 'A426' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A42 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A41', 'A415' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A41');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A40', 'A405' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A40');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A36 5G', 'A366' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A36 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A35 5G', 'A356' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A35 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A34 5G', 'A346' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A34 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A33 5G', 'A336' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A33 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A32 5G', 'A326' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A32 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A32 4G', 'A325' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A32 4G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A31', 'A315' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A31');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A30s', 'A307' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A30s');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy A30', 'A305' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy A30');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S25 Ultra 5G', 'S938' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S25 Ultra 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S25 Edge 5G', 'S937' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S25 Edge 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S25 Plus 5G', 'S936' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S25 Plus 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S25 5G', 'S931' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S25 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S24 Ultra 5G', 'S928' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S24 Ultra 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S24 Plus 5G', 'S926' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S24 Plus 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S24 FE 5G', 'S721' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S24 FE 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S24 5G', 'S921' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S24 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S23 FE 5G', 'S711' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S23 FE 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S23 Ultra 5G', 'S918' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S23 Ultra 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S23 Plus 5G', 'S916' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S23 Plus 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S23 5G', 'S911' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S23 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S22 Ultra 5G', 'S908' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S22 Ultra 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S22 Plus 5G', 'S906' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S22 Plus 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S22 5G', 'S901' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S22 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S21 Ultra 5G', 'G998' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S21 Ultra 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S21 Plus 5G', 'G996' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S21 Plus 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S21 5G', 'G991' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S21 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S21 FE 5G', 'G990' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S21 FE 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S20 FE', 'G780/G781' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S20 FE');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S20 Ultra', 'G988' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S20 Ultra');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S20 Plus', 'G986' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S20 Plus');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S20', 'G980' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S20');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S10 5G', 'G977' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S10 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S10 Plus', 'G975' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S10 Plus');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S10 Lite', 'G770' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S10 Lite');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S10E', 'G970' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S10E');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S10', 'G973' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S10');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S9 Plus', 'G965' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S9 Plus');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S9', 'G960' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S9');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S8 Plus', 'G955' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S8 Plus');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S8', 'G950' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S8');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S7 Edge', 'G935' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S7 Edge');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S7', 'G930' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S7');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S6 Active', 'G890' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S6 Active');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S6 Edge+', 'G928' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S6 Edge+');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S6 Edge', 'G925' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S6 Edge');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S6', 'G920' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S6');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S5 Neo', 'G903' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S5 Neo');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S5 mini', 'G800' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S5 mini');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S5', 'G900' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S5');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S4 mini', 'i9195' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S4 mini');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S4', 'i9500/i9595/i9506' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S4');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S3 Neo', 'i9301' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S3 Neo');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S3', 'i9300/i9305' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S3');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S3 mini', 'i8190' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S3 mini');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Galaxy S2', 'i9100' FROM brands WHERE name = 'Samsung'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Samsung') AND name = 'Galaxy S2');

INSERT INTO models (brand_id, name)
SELECT id, 'Mi 14 Ultra 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Mi 14 Ultra 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Mi 14T Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Mi 14T Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Mi 14 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Mi 14 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Mi 14T 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Mi 14T 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Mi 14 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Mi 14 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Mi 13 Ultra 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Mi 13 Ultra 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Mi 11 Ultra 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Mi 11 Ultra 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco C40' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco C40');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X7 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X7 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X7 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X7 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X5 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X5 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F7 Ultra 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F7 Ultra 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F7 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F7 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F6 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F6 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F6' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F6');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F5 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F5 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F5 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F5 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F4 GT' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F4 GT');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F4 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F4 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X6 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X6 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X6 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X6 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X4 GT' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X4 GT');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X4 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X4 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X3 Pro' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X3 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco X3' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco X3');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco M5' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco M5');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco M4 Pro' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco M4 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco M3 Pro' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco M3 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco M3' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco M3');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F3 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F3 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Poco F2 Pro' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Poco F2 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 14C' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 14C');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 13' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 13');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi A5' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi A5');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi A3' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi A3');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi A2' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi A2');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi A1' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi A1');

INSERT INTO models (brand_id, name)
SELECT id, 'REDMI S2' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'REDMI S2');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 14 Pro+ 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 14 Pro+ 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 14 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 14 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 14 Pro 4G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 14 Pro 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 14 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 14 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 13 Pro Plus 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 13 Pro Plus 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 13 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 13 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 13 Pro 4G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 13 Pro 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 13 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 13 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 13 4G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 13 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 12S' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 12S');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 12 Pro Plus 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 12 Pro Plus 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 12 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 12 Pro 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Redmi Note 12 Pro 4G', '2209116AG' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 12 Pro 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 12 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 12 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 12 4G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 12 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 11T Pro Plus' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 11T Pro Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 11T Pro' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 11T Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 11 Pro+ 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 11 Pro+ 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 11 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 11 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 11S' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 11S');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 11 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 11 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 11 4G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 11 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 10 Pro 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 10 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 10 Pro 4G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 10 Pro 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 10S' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 10S');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 10 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 10 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 10 4G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 10 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 9S (Note 9 Pro)' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 9S (Note 9 Pro)');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 9T' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 9T');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 9' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 9');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Redmi Note 8 2021', 'M1908C3JGG' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 8 2021');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 8T' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 8T');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 8 Pro' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 8 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi Note 8' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi Note 8');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 13C' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 13C');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 12C' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 12C');

INSERT INTO models (brand_id, name)
SELECT id, 'REDMI 12' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'REDMI 12');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 10A' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 10A');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 10C' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 10C');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 10 5G' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 10 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 10' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 10');

INSERT INTO models (brand_id, name)
SELECT id, 'Redmi 9T' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Redmi 9T');

INSERT INTO models (brand_id, name)
SELECT id, 'Black Shark' FROM brands WHERE name = 'Xiaomi'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Xiaomi') AND name = 'Black Shark');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 12 Pro 5G', 'CPH2629' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 12 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Reno 12F' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 12F');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 12 5G', 'CPH2625' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 12 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Reno 11 Pro' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 11 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Reno 11F' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 11F');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Reno 10 Pro 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 10 Pro 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 10 5G', 'CPH2531' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 10 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 8 Pro 5G', 'CPH2357' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 8 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Reno 8T' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 8T');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 8 5G', 'CPH2359' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 8 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 8 4G', 'CPH2457' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 8 4G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 6 Pro 5G', 'PEPM00/CPH2249' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 6 Pro 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 7Z 5G', 'CPH2343' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 7Z 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Reno 6 5G', 'PEQM00/CPH2251' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 6 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Reno 5 Lite' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Reno 5 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Find X5 Pro 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X5 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Find X5 Lite 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X5 Lite 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Find X5' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X5');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Find X3 Pro 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X3 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Find X3 Neo 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X3 Neo 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Find X3 Lite 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X3 Lite 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Find X2 Lite 5G', 'CPH2005' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X2 Lite 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO Find X2 Pro' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X2 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO Find X2 5G', 'CPH2023' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO Find X2 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A80' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A80');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A9 2020' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A9 2020');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A98 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A98 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Oppo A97' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'Oppo A97');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A96 4G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A96 4G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO A94 5G', 'CPH2211' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A94 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO A94 4G', 'CPH2203' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A94 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A78' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A78');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A77 5G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A77 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A76' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A76');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Oppo A74 5G', 'CPH2197' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'Oppo A74 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A74 4G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A74 4G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO A73 5G', 'CPH2161' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A73 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO A72 5G', 'PDYM20' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A72 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO A72 2020', 'CPH2067' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A72 2020');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A60' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A60');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A58 4G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A58 4G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'OPPO A57 4G', 'CPH2387' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A57 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A54 5G (A93 5G)' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A54 5G (A93 5G)');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A54 4G' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A54 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'OPPO A53 (A53s/A32 2020)' FROM brands WHERE name = 'OPPO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'OPPO') AND name = 'OPPO A53 (A53s/A32 2020)');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 12 Pro 5G', 'RMX3842' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 12 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme 12X 5G' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 12X 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 11 Pro 5G', 'RMX3771' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 11 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme 10 Pro 5G' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 10 Pro 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme 10 4G' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 10 4G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme X3 (X50)', 'RMX2052' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme X3 (X50)');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme X2 Pro', 'RMX1931' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme X2 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme X2', 'RMX1993' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme X2');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 2 Pro', 'RMX1801/RMX1807' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 2 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 3', 'RMX1825/RMX1821' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 3');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 3 Pro', 'RMX1851' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 3 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 3i', 'RMX1827' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 3i');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 5', 'RMX1911' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 5');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 5 Pro', 'RMX1971' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 5 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 5i', 'RMX2030' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 5i');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 6', 'RMX2001' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 6');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 6i', 'RMX2040' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 6i');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 6 Pro', 'RMX2061' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 6 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 7', 'RMX2155' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 7');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 7 5G', 'RMX2111' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 7 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 7i', 'RMX2103' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 7i');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 7 Pro', 'RMX2170' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 7 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 8i', 'RMA3151' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 8i');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 8 5G', 'RMX3241' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 8 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 8 Pro', 'RMX3081' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 8 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 9', 'RMX3521' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 9');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 9 5G', 'RMX3474' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 9 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 9i', 'RMX3491' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 9i');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 9i 5G', 'RMX3612' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 9i 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 9 Pro', 'RMX3471' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 9 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme 9 Pro Plus', 'RMX3392' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme 9 Pro Plus');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C1', 'A1603' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C1');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C2', 'RMX1941/RMX1945' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C2');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C3', 'RMX2020' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C3');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C11', 'RMX2185' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C11');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C11 2021', 'RMX3231' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C11 2021');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme C20 (C21)' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C20 (C21)');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C21Y', 'RMX3261' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C21Y');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C30', 'RMX3581' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C30');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C31', 'RMX3501' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C31');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C35', 'RMX3511' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C35');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C53', 'RMX3760' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C53');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme C55', 'RMX3710' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C55');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme C61' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C61');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme C63' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C63');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme C65' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C65');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme C67' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C67');

INSERT INTO models (brand_id, name)
SELECT id, 'Realme C75' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme C75');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme Note 50', 'RMX3834' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme Note 50');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Realme GT 5G', 'RMX2202' FROM brands WHERE name = 'Realme'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Realme') AND name = 'Realme GT 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y3' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y3');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y01' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y01');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y02' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y02');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y91' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y91');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y72 5G' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y72 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y70' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y70');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y11s (Y20s)' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y11s (Y20s)');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y15S' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y15S');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'VIVO Y16', 'V2204/V2214' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y16');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'VIVO Y17S', 'V2310' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y17S');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y18' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y18');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y19 (Y5s)' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y19 (Y5s)');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y21 (2021)' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y21 (2021)');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y21S (2021)' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y21S (2021)');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y22S' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y22S');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y28 4G' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y28 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y28S 5G' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y28S 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Vivio Y33s', 'V2109' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivio Y33s');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y35 4G' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y35 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO Y36' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y36');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Vivo Y52s', 'V2057A' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y52s');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Vivo Y52 5G', 'V2053' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y52 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'VIVO Y55 5G', 'V2127/V2154' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO Y55 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y93' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y93');

INSERT INTO models (brand_id, name)
SELECT id, 'Vivo Y50' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'Vivo Y50');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO X27' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO X27');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO X27 Pro' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO X27 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO X60 Pro' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO X60 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'VIVO V21 5G', 'V2050' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO V21 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'VIVO V23 5G', 'V2130' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO V23 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'VIVO V40 SE' FROM brands WHERE name = 'VIVO'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'VIVO') AND name = 'VIVO V40 SE');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 200' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 200');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 90 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 90 Lite');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Honor 90', 'REA-AN00/REA-NX9' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 90');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 70 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 70 Lite');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Honor 70', 'FNE-AN00/FNE-NX9' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 70');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor Magic 7 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor Magic 7 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor Magic 7 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor Magic 7 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor Magic 6 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor Magic 6 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor Magic 5 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor Magic 5 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor Magic 4 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor Magic 4 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 50 SE' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 50 SE');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 50 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 50 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 50' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 50');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X10 5G' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X10 5G');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Honor X9B', 'ALI-NX1' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X9B');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X9' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X9');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Honor X8B', 'LLY-LX1/LLY-LX2/LLY-LX3' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X8B');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X8A' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X8A');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X8 5G' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X8 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X8 4G' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X8 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X7C' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X7C');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X7B' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X7B');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Honor X7A', 'RKY-LX1/RKY-LX2' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X7A');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X7' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X7');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Honor X6B', 'JDY-LX1/JDY-LX2' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X6B');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor X6A' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X6A');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'Honor X5 4G', 'VNA-LX2' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor X5 4G');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor Play' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor Play');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 20 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 20 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 20' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 20');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 10 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 10 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Honor 10' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Honor 10');

INSERT INTO models (brand_id, name)
SELECT id, 'Nova 10' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Nova 10');

INSERT INTO models (brand_id, name)
SELECT id, 'Nova 10 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Nova 10 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Nova 10 SE' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Nova 10 SE');

INSERT INTO models (brand_id, name)
SELECT id, 'Nova Y61' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Nova Y61');

INSERT INTO models (brand_id, name)
SELECT id, 'Nova Y70' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Nova Y70');

INSERT INTO models (brand_id, name)
SELECT id, 'Nova Y90' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Nova Y90');

INSERT INTO models (brand_id, name)
SELECT id, 'Nova Y91' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Nova Y91');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 30 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 30 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 30 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 30 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 30' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 30');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 20X' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 20X');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 20 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 20 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 20 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 20 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 20' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 20');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 10 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 10 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 10 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 10 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 10' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 10');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 9' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 9');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 8' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 8');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate 7' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate 7');

INSERT INTO models (brand_id, name)
SELECT id, 'Mate S' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'Mate S');

INSERT INTO models (brand_id, name)
SELECT id, 'P smart S' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P smart S');

INSERT INTO models (brand_id, name)
SELECT id, 'P Smart Z' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P Smart Z');

INSERT INTO models (brand_id, name)
SELECT id, 'P Smart 2021' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P Smart 2021');

INSERT INTO models (brand_id, name)
SELECT id, 'P Smart 2020' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P Smart 2020');

INSERT INTO models (brand_id, name)
SELECT id, 'P Smart 2019' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P Smart 2019');

INSERT INTO models (brand_id, name)
SELECT id, 'P Smart Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P Smart Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'P Smart Plus' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P Smart Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'P Smart' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P Smart');

INSERT INTO models (brand_id, name)
SELECT id, 'P60' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P60');

INSERT INTO models (brand_id, name)
SELECT id, 'P50 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P50 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'P40 Pro Plus' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P40 Pro Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'P40 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P40 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'P40 Lite E (Y7p 2020)' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P40 Lite E (Y7p 2020)');

INSERT INTO models (brand_id, name)
SELECT id, 'P40 Lite 5G' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P40 Lite 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'P40 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P40 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'P30 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P30 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'P30 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P30 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'P30' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P30');

INSERT INTO models (brand_id, name)
SELECT id, 'P20 Pro' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P20 Pro');

INSERT INTO models (brand_id, name)
SELECT id, 'P20 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P20 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'P20' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P20');

INSERT INTO models (brand_id, name)
SELECT id, 'P10 Plus' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P10 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'P10 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P10 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'P10' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P10');

INSERT INTO models (brand_id, name)
SELECT id, 'P9 Plus' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P9 Plus');

INSERT INTO models (brand_id, name)
SELECT id, 'P9 Lite 2017' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P9 Lite 2017');

INSERT INTO models (brand_id, name)
SELECT id, 'P9 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P9 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'P9' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P9');

INSERT INTO models (brand_id, name)
SELECT id, 'P8 Lite 2017' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P8 Lite 2017');

INSERT INTO models (brand_id, name)
SELECT id, 'P8 Lite' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P8 Lite');

INSERT INTO models (brand_id, name)
SELECT id, 'P8' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P8');

INSERT INTO models (brand_id, name)
SELECT id, 'P7' FROM brands WHERE name = 'Huawei'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'Huawei') AND name = 'P7');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 10 SE', 'T766' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 10 SE');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 10L', 'T770H/T770B' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 10L');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 10 5G', 'T790Y' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 10 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 10 Lite' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 10 Lite');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 10 Pro', 'T799B/T799H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 10 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 10 Plus', 'T782H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 10 Plus');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 20 5G', 'T781/T781K/T781H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 20 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 20Y (2021)' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 20Y (2021)');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 20E (2021)' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 20E (2021)');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 20R', 'T767H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 20R');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 20 SE', 'T671H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 20 SE');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 20 Pro', 'T810H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 20 Pro');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 20L', 'T774H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 20L');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 30 (TCL 30 Plus)' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 30 (TCL 30 Plus)');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 30 SE', '6165H' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 30 SE');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 30E', '6127A/6127l' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 30E');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 305i' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 305i');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 405', 'T506D' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 405');

INSERT INTO models (brand_id, name, model_reference)
SELECT id, 'TCL 40R', 'T771K' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 40R');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 40 SE' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 40 SE');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 40 NxtPaper 5G' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 40 NxtPaper 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 501' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 501');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 505' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 505');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 50 5G' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 50 5G');

INSERT INTO models (brand_id, name)
SELECT id, 'TCL 50 Pro NxtPaper 5G' FROM brands WHERE name = 'TCL'
WHERE NOT EXISTS (SELECT 1 FROM models WHERE brand_id = (SELECT id FROM brands WHERE name = 'TCL') AND name = 'TCL 50 Pro NxtPaper 5G');

-- تحديث المعرفات للموديلات الموجودة
UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F700'
WHERE b.name = 'Samsung' AND m.name = 'F700 Galaxy Z Flip' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F707'
WHERE b.name = 'Samsung' AND m.name = 'F707 Galaxy Z Flip 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F711'
WHERE b.name = 'Samsung' AND m.name = 'F711 Galaxy Z Flip 3 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F721'
WHERE b.name = 'Samsung' AND m.name = 'F721 Galaxy Z Flip 4 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F731'
WHERE b.name = 'Samsung' AND m.name = 'F731 Galaxy Z Flip 5 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F741'
WHERE b.name = 'Samsung' AND m.name = 'F741 Galaxy Z Flip 6 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F900'
WHERE b.name = 'Samsung' AND m.name = 'F900 Galaxy Fold' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F907'
WHERE b.name = 'Samsung' AND m.name = 'F907 Galaxy Fold 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F916'
WHERE b.name = 'Samsung' AND m.name = 'F916 Galaxy Z Fold 2 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F926'
WHERE b.name = 'Samsung' AND m.name = 'F926 Galaxy Z Fold 3 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F936'
WHERE b.name = 'Samsung' AND m.name = 'F936 Galaxy Z Fold 4 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'F946'
WHERE b.name = 'Samsung' AND m.name = 'F946 Galaxy Z Fold 5 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A91'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A91' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A908'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A90 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A805'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A80' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A736'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A73 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A725'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A72' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A716'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A71 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A715'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A71' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A705'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A70' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A606'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A60' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A566'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A56 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A556'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A55 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A546'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A54 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A536'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A53 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A528'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A52s 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A525/A526'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A52' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A515'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A51' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A507'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A50s' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A505'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A50' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A426'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A42 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A415'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A41' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A405'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A40' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A366'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A36 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A356'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A35 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A346'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A34 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A336'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A33 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A326'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A32 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A325'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A32 4G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A315'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A31' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A307'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A30s' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A305'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy A30' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S938'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S25 Ultra 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S937'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S25 Edge 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S936'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S25 Plus 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S931'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S25 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S928'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S24 Ultra 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S926'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S24 Plus 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S721'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S24 FE 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S921'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S24 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S711'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S23 FE 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S918'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S23 Ultra 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S916'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S23 Plus 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S911'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S23 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S908'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S22 Ultra 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S906'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S22 Plus 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'S901'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S22 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G998'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S21 Ultra 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G996'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S21 Plus 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G991'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S21 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G990'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S21 FE 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G780/G781'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S20 FE' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G988'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S20 Ultra' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G986'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S20 Plus' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G980'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S20' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G977'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S10 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G975'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S10 Plus' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G770'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S10 Lite' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G970'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S10E' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G973'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S10' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G965'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S9 Plus' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G960'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S9' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G955'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S8 Plus' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G950'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S8' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G935'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S7 Edge' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G930'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S7' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G890'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S6 Active' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G928'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S6 Edge+' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G925'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S6 Edge' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G920'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S6' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G903'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S5 Neo' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G800'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S5 mini' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'G900'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S5' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'i9195'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S4 mini' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'i9500/i9595/i9506'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S4' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'i9301'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S3 Neo' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'i9300/i9305'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S3' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'i8190'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S3 mini' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'i9100'
WHERE b.name = 'Samsung' AND m.name = 'Galaxy S2' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = '2209116AG'
WHERE b.name = 'Xiaomi' AND m.name = 'Redmi Note 12 Pro 4G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'M1908C3JGG'
WHERE b.name = 'Xiaomi' AND m.name = 'Redmi Note 8 2021' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2629'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 12 Pro 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2625'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 12 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2531'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 10 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2357'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 8 Pro 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2359'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 8 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2457'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 8 4G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'PEPM00/CPH2249'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 6 Pro 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2343'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 7Z 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'PEQM00/CPH2251'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Reno 6 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2005'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Find X2 Lite 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2023'
WHERE b.name = 'OPPO' AND m.name = 'OPPO Find X2 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2211'
WHERE b.name = 'OPPO' AND m.name = 'OPPO A94 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2203'
WHERE b.name = 'OPPO' AND m.name = 'OPPO A94 4G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2197'
WHERE b.name = 'OPPO' AND m.name = 'Oppo A74 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2161'
WHERE b.name = 'OPPO' AND m.name = 'OPPO A73 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'PDYM20'
WHERE b.name = 'OPPO' AND m.name = 'OPPO A72 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2067'
WHERE b.name = 'OPPO' AND m.name = 'OPPO A72 2020' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'CPH2387'
WHERE b.name = 'OPPO' AND m.name = 'OPPO A57 4G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3842'
WHERE b.name = 'Realme' AND m.name = 'Realme 12 Pro 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3771'
WHERE b.name = 'Realme' AND m.name = 'Realme 11 Pro 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2052'
WHERE b.name = 'Realme' AND m.name = 'Realme X3 (X50)' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1931'
WHERE b.name = 'Realme' AND m.name = 'Realme X2 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1993'
WHERE b.name = 'Realme' AND m.name = 'Realme X2' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1801/RMX1807'
WHERE b.name = 'Realme' AND m.name = 'Realme 2 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1825/RMX1821'
WHERE b.name = 'Realme' AND m.name = 'Realme 3' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1851'
WHERE b.name = 'Realme' AND m.name = 'Realme 3 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1827'
WHERE b.name = 'Realme' AND m.name = 'Realme 3i' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1911'
WHERE b.name = 'Realme' AND m.name = 'Realme 5' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1971'
WHERE b.name = 'Realme' AND m.name = 'Realme 5 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2030'
WHERE b.name = 'Realme' AND m.name = 'Realme 5i' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2001'
WHERE b.name = 'Realme' AND m.name = 'Realme 6' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2040'
WHERE b.name = 'Realme' AND m.name = 'Realme 6i' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2061'
WHERE b.name = 'Realme' AND m.name = 'Realme 6 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2155'
WHERE b.name = 'Realme' AND m.name = 'Realme 7' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2111'
WHERE b.name = 'Realme' AND m.name = 'Realme 7 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2103'
WHERE b.name = 'Realme' AND m.name = 'Realme 7i' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2170'
WHERE b.name = 'Realme' AND m.name = 'Realme 7 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMA3151'
WHERE b.name = 'Realme' AND m.name = 'Realme 8i' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3241'
WHERE b.name = 'Realme' AND m.name = 'Realme 8 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3081'
WHERE b.name = 'Realme' AND m.name = 'Realme 8 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3521'
WHERE b.name = 'Realme' AND m.name = 'Realme 9' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3474'
WHERE b.name = 'Realme' AND m.name = 'Realme 9 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3491'
WHERE b.name = 'Realme' AND m.name = 'Realme 9i' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3612'
WHERE b.name = 'Realme' AND m.name = 'Realme 9i 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3471'
WHERE b.name = 'Realme' AND m.name = 'Realme 9 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3392'
WHERE b.name = 'Realme' AND m.name = 'Realme 9 Pro Plus' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'A1603'
WHERE b.name = 'Realme' AND m.name = 'Realme C1' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX1941/RMX1945'
WHERE b.name = 'Realme' AND m.name = 'Realme C2' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2020'
WHERE b.name = 'Realme' AND m.name = 'Realme C3' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2185'
WHERE b.name = 'Realme' AND m.name = 'Realme C11' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3231'
WHERE b.name = 'Realme' AND m.name = 'Realme C11 2021' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3261'
WHERE b.name = 'Realme' AND m.name = 'Realme C21Y' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3581'
WHERE b.name = 'Realme' AND m.name = 'Realme C30' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3501'
WHERE b.name = 'Realme' AND m.name = 'Realme C31' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3511'
WHERE b.name = 'Realme' AND m.name = 'Realme C35' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3760'
WHERE b.name = 'Realme' AND m.name = 'Realme C53' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3710'
WHERE b.name = 'Realme' AND m.name = 'Realme C55' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX3834'
WHERE b.name = 'Realme' AND m.name = 'Realme Note 50' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RMX2202'
WHERE b.name = 'Realme' AND m.name = 'Realme GT 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2204/V2214'
WHERE b.name = 'VIVO' AND m.name = 'VIVO Y16' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2310'
WHERE b.name = 'VIVO' AND m.name = 'VIVO Y17S' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2109'
WHERE b.name = 'VIVO' AND m.name = 'Vivio Y33s' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2057A'
WHERE b.name = 'VIVO' AND m.name = 'Vivo Y52s' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2053'
WHERE b.name = 'VIVO' AND m.name = 'Vivo Y52 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2127/V2154'
WHERE b.name = 'VIVO' AND m.name = 'VIVO Y55 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2050'
WHERE b.name = 'VIVO' AND m.name = 'VIVO V21 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'V2130'
WHERE b.name = 'VIVO' AND m.name = 'VIVO V23 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'REA-AN00/REA-NX9'
WHERE b.name = 'Huawei' AND m.name = 'Honor 90' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'FNE-AN00/FNE-NX9'
WHERE b.name = 'Huawei' AND m.name = 'Honor 70' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'ALI-NX1'
WHERE b.name = 'Huawei' AND m.name = 'Honor X9B' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'LLY-LX1/LLY-LX2/LLY-LX3'
WHERE b.name = 'Huawei' AND m.name = 'Honor X8B' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'RKY-LX1/RKY-LX2'
WHERE b.name = 'Huawei' AND m.name = 'Honor X7A' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'JDY-LX1/JDY-LX2'
WHERE b.name = 'Huawei' AND m.name = 'Honor X6B' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'VNA-LX2'
WHERE b.name = 'Huawei' AND m.name = 'Honor X5 4G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T766'
WHERE b.name = 'TCL' AND m.name = 'TCL 10 SE' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T770H/T770B'
WHERE b.name = 'TCL' AND m.name = 'TCL 10L' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T790Y'
WHERE b.name = 'TCL' AND m.name = 'TCL 10 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T799B/T799H'
WHERE b.name = 'TCL' AND m.name = 'TCL 10 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T782H'
WHERE b.name = 'TCL' AND m.name = 'TCL 10 Plus' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T781/T781K/T781H'
WHERE b.name = 'TCL' AND m.name = 'TCL 20 5G' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T767H'
WHERE b.name = 'TCL' AND m.name = 'TCL 20R' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T671H'
WHERE b.name = 'TCL' AND m.name = 'TCL 20 SE' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T810H'
WHERE b.name = 'TCL' AND m.name = 'TCL 20 Pro' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T774H'
WHERE b.name = 'TCL' AND m.name = 'TCL 20L' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = '6165H'
WHERE b.name = 'TCL' AND m.name = 'TCL 30 SE' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = '6127A/6127l'
WHERE b.name = 'TCL' AND m.name = 'TCL 30E' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T506D'
WHERE b.name = 'TCL' AND m.name = 'TCL 405' AND (m.model_reference IS NULL OR m.model_reference = '');

UPDATE models m JOIN brands b ON m.brand_id = b.id
SET m.model_reference = 'T771K'
WHERE b.name = 'TCL' AND m.name = 'TCL 40R' AND (m.model_reference IS NULL OR m.model_reference = '');

