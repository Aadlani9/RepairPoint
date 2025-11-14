# RepairPoint - تحديث نظام معرفات الموديلات والأجهزة المخصصة
# RepairPoint - Actualización: Referencias de Modelos y Dispositivos Personalizados

**التاريخ / Fecha:** 2025-11-13
**النسخة / Versión:** 1.0

---

## 📋 ملخص التحديث / Resumen de la Actualización

تم إضافة ميزات جديدة لتحسين إدارة الأجهزة وقطع الغيار:

Se han añadido nuevas funcionalidades para mejorar la gestión de dispositivos y repuestos:

### ✨ الميزات الجديدة / Nuevas Funcionalidades:

1. **معرفات الموديلات (Model References)**
   - إضافة معرّف فريد لكل موديل (مثل: V2244، SM-S928)
   - البحث السريع باستخدام المعرّف

2. **3 أوضاع لاختيار الجهاز (3 Modos de Selección)**
   - Seleccionar de la lista
   - Búsqueda rápida por modelo/referencia
   - Otro (dispositivo personalizado)

3. **تحسين عرض قطع الغيار**
   - عرض المعرّفات في معلومات التوافق

---

## 🚀 خطوات التثبيت / Pasos de Instalación

### 1️⃣ تنفيذ Migration على قاعدة البيانات / Ejecutar Migration en la Base de Datos

**⚠️ مهم جداً / MUY IMPORTANTE:**

قبل تشغيل النظام، يجب تنفيذ ملف SQL التالي:

Antes de ejecutar el sistema, debes ejecutar el siguiente archivo SQL:

```bash
mysql -u root -p repairpoint < sql/migrations/add_model_reference_and_custom_devices.sql
```

أو من phpMyAdmin:

O desde phpMyAdmin:

1. افتح phpMyAdmin / Abre phpMyAdmin
2. اختر قاعدة البيانات `repairpoint` / Selecciona la base de datos `repairpoint`
3. اذهب لتبويب "SQL" / Ve a la pestaña "SQL"
4. انسخ محتوى الملف: / Copia el contenido del archivo:
   ```
   sql/migrations/add_model_reference_and_custom_devices.sql
   ```
5. نفّذ الكود / Ejecuta el código

---

## 📖 دليل الاستخدام / Guía de Uso

### 1. إضافة معرف للموديل / Agregar Referencia al Modelo

#### في صفحة الإعدادات / En la Página de Configuración:

1. اذهب إلى: `Settings` → `Brands & Models`
2. اختر ماركة / Selecciona una marca
3. اضغط على "+" لإضافة موديل جديد / Haz clic en "+" para agregar un nuevo modelo

**الحقول / Campos:**
- **Nombre del Modelo** (إلزامي / obligatorio): مثل / Ej: `V29 Lite 5G`
- **Referencia del Modelo** (اختياري / opcional): مثل / Ej: `V2244`

**ملاحظات مهمة / Notas Importantes:**
- ✅ المعرف **فريد** - لا يمكن استخدام نفس المعرف لموديلين / La referencia es **única** - no se puede usar la misma referencia para dos modelos
- ✅ المعرف **اختياري** - للهواتف القديمة أو غير المعروفة / La referencia es **opcional** - para teléfonos antiguos o desconocidos
- ✅ يظهر المعرف بين قوسين في القائمة / La referencia aparece entre paréntesis en la lista: `V29 Lite 5G (V2244)`

---

### 2. إضافة إصلاح جديد / Agregar Nueva Reparación

في صفحة `Add Repair`، الآن لديك **3 خيارات** لاختيار الجهاز:

En la página `Add Repair`, ahora tienes **3 opciones** para seleccionar el dispositivo:

#### خيار 1: Seleccionar de la lista

الطريقة التقليدية:

Método tradicional:

1. اختر الماركة من القائمة / Selecciona la marca de la lista
2. اختر الموديل من القائمة / Selecciona el modelo de la lista
3. ✅ سيظهر المعرّف بين قوسين إذا كان موجوداً / La referencia aparecerá entre paréntesis si existe

**مثال / Ejemplo:**
```
Marca: VIVO
Modelo: V29 Lite 5G (V2244)
```

---

#### خيار 2: Búsqueda rápida

البحث السريع باستخدام الاسم أو المعرّف:

Búsqueda rápida usando nombre o referencia:

1. اختر "Búsqueda rápida" / Selecciona "Búsqueda rápida"
2. اكتب في حقل البحث / Escribe en el campo de búsqueda:
   - المعرّف / La referencia: `V2244`
   - أو جزء من اسم الموديل / O parte del nombre: `V29`
   - أو اسم الماركة / O nombre de la marca: `VIVO`
3. اختر من النتائج / Selecciona de los resultados

**مزايا / Ventajas:**
- ⚡ أسرع من القوائم / Más rápido que las listas
- 🔍 البحث في جميع الحقول / Búsqueda en todos los campos
- 📱 مثالي للأجهزة الشائعة / Ideal para dispositivos comunes

---

#### خيار 3: Otro (Dispositivo no encontrado)

للأجهزة غير الموجودة في النظام:

Para dispositivos que no están en el sistema:

1. اختر "Otro" / Selecciona "Otro"
2. أدخل الماركة (اختياري) / Ingresa la marca (opcional)
3. أدخل الموديل (إلزامي) / Ingresa el modelo (obligatorio)

**تنبيه / Advertencia:**
```
⚠️ No habrá repuestos compatibles disponibles automáticamente
```

لن تظهر قطع الغيار المتوافقة تلقائياً

**متى تستخدمه / Cuándo usarlo:**
- 📱 هواتف صينية غير معروفة / Teléfonos chinos desconocidos
- 🆕 موديلات جديدة جداً / Modelos muy nuevos
- 🔧 أجهزة نادرة / Dispositivos raros

**مثال / Ejemplo:**
```
Marca: Realme (opcional)
Modelo: GT Neo 3
```

---

## 🔧 إدارة قطع الغيار / Gestión de Repuestos

### عرض معلومات التوافق / Mostrar Información de Compatibilidad

الآن عند اختيار قطعة غيار، سترى المعرّف:

Ahora al seleccionar un repuesto, verás la referencia:

```
Full Screen For VIVO V29 Lite 5G
Código: V-2470-CRPD
Compatible: VIVO V29 Lite 5G (V2244)  ← المعرّف هنا
Precio: €120.00
Stock: Disponible (5)
```

**الفلترة التلقائية / Filtrado Automático:**

- إذا اخترت موديل من القائمة → تظهر القطع المتوافقة فقط
- Si seleccionas un modelo de la lista → solo se muestran piezas compatibles

- إذا اخترت "Otro" → لن تظهر قطع مقترحة (يمكن البحث يدوياً)
- Si seleccionas "Otro" → no se muestran piezas sugeridas (puedes buscar manualmente)

---

## 📊 التغييرات في قاعدة البيانات / Cambios en la Base de Datos

### جدول `models`:

| حقل جديد / Campo Nuevo | النوع / Tipo | ملاحظات / Notas |
|---|---|---|
| `model_reference` | VARCHAR(50) NULL UNIQUE | معرّف الموديل الفريد / Referencia única del modelo |

### جدول `repairs`:

| حقل جديد / Campo Nuevo | النوع / Tipo | ملاحظات / Notas |
|---|---|---|
| `device_input_type` | ENUM('list','search','otro') | طريقة إدخال الجهاز / Método de entrada del dispositivo |
| `custom_brand` | VARCHAR(100) NULL | ماركة مخصصة / Marca personalizada |
| `custom_model` | VARCHAR(100) NULL | موديل مخصص / Modelo personalizado |

---

## 🎯 أمثلة عملية / Ejemplos Prácticos

### مثال 1: إضافة موديل مع معرّف / Ejemplo 1: Agregar Modelo con Referencia

```
Settings → Brands & Models
1. Marca: VIVO
2. Modelo: V29 Lite 5G
3. Referencia: V2244
4. ✅ Guardar
```

### مثال 2: إصلاح لهاتف معروف / Ejemplo 2: Reparación para Teléfono Conocido

```
Add Repair
1. Método: "Búsqueda rápida"
2. Buscar: "V2244"
3. Seleccionar: VIVO V29 Lite 5G (V2244)
4. ✅ Automáticamente se cargan repuestos compatibles
```

### مثال 3: إصلاح لهاتف غير معروف / Ejemplo 3: Reparación para Teléfono Desconocido

```
Add Repair
1. Método: "Otro"
2. Marca: Realme (opcional)
3. Modelo: GT Neo 3
4. ⚠️ Seleccionar repuestos manualmente
```

---

## ⚠️ ملاحظات مهمة / Notas Importantes

### 1. البيانات القديمة / Datos Antiguos

✅ **جميع الإصلاحات القديمة آمنة**

Todas las reparaciones antiguas están seguras:
- `device_input_type` = `'list'` (افتراضياً / por defecto)
- لن تتأثر / No se verán afectadas

### 2. النسخ الاحتياطي / Copia de Seguridad

⚠️ **يُنصح بشدة بعمل نسخة احتياطية قبل التحديث**

Se recomienda encarecidamente hacer una copia de seguridad antes de actualizar:

```bash
mysqldump -u root -p repairpoint > backup_before_update_$(date +%Y%m%d).sql
```

### 3. الصلاحيات / Permisos

👤 **فقط الـ Admin يمكنه:**

Solo el Admin puede:
- إضافة/تعديل معرفات الموديلات / Agregar/editar referencias de modelos
- إضافة قطع غيار / Agregar repuestos
- رؤية معلومات التكلفة / Ver información de costos

---

## 🐛 حل المشكلات / Solución de Problemas

### مشكلة: لا يظهر حقل "Referencia" في Settings

**الحل / Solución:**
```bash
# تحقق من تنفيذ migration
mysql -u root -p repairpoint -e "SHOW COLUMNS FROM models LIKE 'model_reference'"
```

إذا لم يظهر شيء، نفّذ migration مرة أخرى

Si no aparece nada, ejecuta el migration nuevamente.

---

### مشكلة: خطأ "Referencia duplicada" / Error "Referencia duplicada"

**السبب / Causa:** المعرّف موجود بالفعل / La referencia ya existe

**الحل / Solución:**
- استخدم معرّفاً مختلفاً / Usa una referencia diferente
- أو اتركه فارغاً / O déjalo vacío

---

### مشكلة: البحث السريع لا يعمل / Búsqueda rápida no funciona

**التحقق / Verificación:**
1. تأكد من وجود الملف / Verifica que existe el archivo:
   ```
   api/models_search.php
   ```

2. اختبر API مباشرة / Prueba el API directamente:
   ```
   http://localhost/RepairPoint/api/models_search.php?term=V2244
   ```

---

## 📞 الدعم / Soporte

إذا واجهت أي مشاكل:

Si encuentras algún problema:

1. تحقق من ملف الأخطاء / Revisa el archivo de errores:
   ```
   logs/error.log
   ```

2. تحقق من console المتصفح / Revisa la consola del navegador:
   ```
   F12 → Console
   ```

3. تأكد من تنفيذ migration / Asegúrate de ejecutar el migration

---

## ✅ قائمة التحقق / Lista de Verificación

قبل استخدام النظام، تأكد من:

Antes de usar el sistema, asegúrate de:

- [ ] ✅ تنفيذ migration على قاعدة البيانات / Ejecutar migration en la base de datos
- [ ] ✅ عمل نسخة احتياطية / Hacer copia de seguridad
- [ ] ✅ اختبار إضافة موديل مع معرّف / Probar agregar modelo con referencia
- [ ] ✅ اختبار الأوضاع الثلاثة في Add Repair / Probar los 3 modos en Add Repair
- [ ] ✅ اختبار البحث السريع / Probar búsqueda rápida
- [ ] ✅ اختبار الأجهزة المخصصة / Probar dispositivos personalizados

---

## 🎉 انتهى! / ¡Terminado!

الآن نظامك جاهز مع الميزات الجديدة!

¡Ahora tu sistema está listo con las nuevas funcionalidades!

---

**تم التحديث / Actualizado:** 2025-11-13
**الإصدار / Versión:** 1.0
**المطوّر / Desarrollado por:** Claude (Anthropic)
