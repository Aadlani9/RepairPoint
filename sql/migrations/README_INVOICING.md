# Sistema de Facturación - Instrucciones de Instalación

## 📋 Descripción

Este módulo añade un sistema completo de facturación al sistema RepairPoint, incluyendo:

- **Gestión de Clientes**: CRUD completo de clientes con documentos de identidad
- **Sistema de Facturas**: Creación de facturas con IVA 21% automático
- **Generador PDF**: Facturas profesionales en español con logo de la empresa
- **Reportes Financieros**: Análisis completo de ventas, pagos y clientes

## 🗄️ Instalación de Base de Datos

### Paso 1: Aplicar la migración

Ejecuta el archivo SQL en tu base de datos MySQL:

```bash
mysql -u root -p repairpoint < sql/migrations/invoicing_system.sql
```

O desde phpMyAdmin:
1. Abre phpMyAdmin
2. Selecciona la base de datos `repairpoint`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido de `sql/migrations/invoicing_system.sql`
5. Haz clic en "Continuar"

### Paso 2: Verificar las tablas creadas

Verifica que se crearon las siguientes tablas:
- `customers` - Clientes
- `invoices` - Facturas
- `invoice_items` - Items de facturas

Y la vista:
- `invoice_details` - Vista con información completa de facturas

### Paso 3: Verificar los triggers

Verifica que se crearon los siguientes triggers:
- `generate_invoice_number` - Genera número de factura automáticamente
- `calculate_invoice_totals_insert` - Calcula totales al insertar items
- `calculate_invoice_totals_update` - Calcula totales al actualizar items
- `calculate_invoice_totals_delete` - Calcula totales al eliminar items

## 📱 Acceso al Sistema

Una vez instalada la migración, accede al sistema como **Administrador**:

1. **Ver Clientes**: `Administración > Facturación > Clientes`
2. **Crear Cliente**: Click en "Nuevo Cliente"
3. **Crear Factura**: Desde la página del cliente, click en "Nueva Factura"
4. **Ver Reportes**: `Administración > Facturación > Facturas e Informes`

## 🎯 Características Principales

### Gestión de Clientes
- ✅ Agregar, editar y eliminar clientes
- ✅ Información completa: DNI/NIE/Passport
- ✅ Ver historial de facturas por cliente
- ✅ Estado activo/inactivo
- ✅ Búsqueda rápida por nombre, teléfono o documento

### Sistema de Facturas
- ✅ Creación de facturas con múltiples items
- ✅ Tipos de items: Servicios, Productos, Repuestos
- ✅ Campo IMEI para dispositivos móviles
- ✅ Cálculo automático de IVA 21%
- ✅ Estados de pago: Pendiente, Parcial, Pagado
- ✅ Múltiples métodos de pago: Efectivo, Tarjeta, Transferencia, Bizum

### Generador PDF
- ✅ Diseño profesional en español
- ✅ Logo de la empresa automático
- ✅ Información completa del cliente y empresa
- ✅ Desglose detallado con IVA
- ✅ Botón de impresión/guardar PDF

### Reportes Financieros
- ✅ Estadísticas generales: Total facturado, cobrado, pendiente
- ✅ Análisis por estado de pago
- ✅ Análisis por método de pago
- ✅ Top 10 clientes
- ✅ Filtros por fecha y estado
- ✅ Desglose de IVA

## 🔐 Permisos

Este sistema está disponible **SOLO para Administradores**. Los usuarios con rol "staff" no tienen acceso.

## 📊 Estructura de Datos

### Tabla `customers`
- Información personal del cliente
- Tipo y número de documento (DNI/NIE/Passport)
- Teléfono, email y dirección
- Estado activo/inactivo

### Tabla `invoices`
- Número de factura (auto-generado: INV-YYYY-NNNN)
- Fecha de factura y vencimiento
- Subtotal, IVA y total
- Estado de pago y método
- Relación con cliente

### Tabla `invoice_items`
- Descripción del item
- Tipo: servicio, producto o repuesto
- IMEI (opcional para dispositivos)
- Cantidad y precio unitario
- Subtotal calculado

## 🛠️ Mantenimiento

### Verificar integridad de datos
```sql
-- Ver facturas sin items
SELECT * FROM invoices WHERE id NOT IN (SELECT DISTINCT invoice_id FROM invoice_items);

-- Ver clientes sin facturas
SELECT * FROM customers WHERE id NOT IN (SELECT DISTINCT customer_id FROM invoices);
```

### Backup recomendado
```bash
mysqldump -u root -p repairpoint customers invoices invoice_items > backup_invoicing_$(date +%Y%m%d).sql
```

## 🐛 Solución de Problemas

### Error: "Table already exists"
Si las tablas ya existen, elimínalas primero:
```sql
DROP VIEW IF EXISTS invoice_details;
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS customers;
```

### Triggers no funcionan
Verifica que los triggers existan:
```sql
SHOW TRIGGERS WHERE `Table` IN ('invoices', 'invoice_items');
```

### IVA no se calcula correctamente
Verifica que la tasa de IVA esté configurada (por defecto 21%):
```sql
SELECT iva_rate FROM invoices;
```

## 📞 Soporte

Para reportar errores o sugerencias, contacta al administrador del sistema.

---

**Versión**: 1.0
**Fecha**: Diciembre 2025
**Autor**: RepairPoint Team
