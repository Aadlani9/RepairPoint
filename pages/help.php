<?php
/**
 * RepairPoint - صفحة المساعدة الشاملة
 * Help Page System مع FAQ وأمثلة عملية
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Verificar autenticación
authMiddleware();

$page_title = 'Centro de Ayuda';
$current_user = getCurrentUser();

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

    <div class="container-fluid">
        <!-- Header de la página -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="help-header bg-gradient-primary text-white p-5 rounded-3">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="display-5 fw-bold mb-3">
                                <i class="bi bi-question-circle-fill me-3"></i>
                                Centro de Ayuda
                            </h1>
                            <p class="lead mb-4">
                                Encuentra respuestas rápidas, guías paso a paso y consejos para sacar el máximo provecho de RepairPoint
                            </p>
                            <div class="search-container">
                                <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                    <input type="text" class="form-control border-0"
                                           placeholder="¿Qué necesitas ayuda con...?"
                                           id="helpSearch">
                                    <button class="btn btn-light" type="button" id="searchBtn">
                                        Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 text-center">
                            <div class="help-illustration">
                                <i class="bi bi-lightbulb display-1 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación rápida -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="quick-nav">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <a href="#getting-started" class="quick-nav-item">
                                <i class="bi bi-play-circle"></i>
                                <span>Empezar</span>
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="#repairs-guide" class="quick-nav-item">
                                <i class="bi bi-tools"></i>
                                <span>Reparaciones</span>
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="#customers-guide" class="quick-nav-item">
                                <i class="bi bi-people"></i>
                                <span>Clientes</span>
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="#reports-guide" class="quick-nav-item">
                                <i class="bi bi-graph-up"></i>
                                <span>Informes</span>
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="#settings-guide" class="quick-nav-item">
                                <i class="bi bi-gear"></i>
                                <span>Configuración</span>
                            </a>
                        </div>
                        <div class="col-md-2">
                            <a href="#faq" class="quick-nav-item">
                                <i class="bi bi-question-circle"></i>
                                <span>FAQ</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Contenido principal -->
            <div class="col-lg-9">
                <!-- Guía de Inicio -->
                <section id="getting-started" class="help-section">
                    <div class="section-header">
                        <h2><i class="bi bi-play-circle me-2"></i>Primeros Pasos</h2>
                        <p>Todo lo que necesitas saber para empezar con RepairPoint</p>
                    </div>

                    <div class="guide-card">
                        <h4>1. Configuración Inicial</h4>
                        <p>Antes de empezar a usar RepairPoint, es importante configurar correctamente el sistema:</p>

                        <div class="step-by-step">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h6>Información del Taller</h6>
                                    <p>Ve a <strong>Configuración → Información del Taller</strong> y completa:</p>
                                    <ul>
                                        <li>Nombre del taller</li>
                                        <li>Dirección completa</li>
                                        <li>Teléfonos de contacto</li>
                                        <li>Email y sitio web</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h6>Marcas y Modelos</h6>
                                    <p>Configura las marcas y modelos de dispositivos que reparas:</p>
                                    <ul>
                                        <li>Agrega las marcas principales (Apple, Samsung, Xiaomi, etc.)</li>
                                        <li>Para cada marca, agrega los modelos más comunes</li>
                                        <li>Esto agilizará el registro de reparaciones</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h6>Problemas Comunes</h6>
                                    <p>Crea una lista de problemas frecuentes:</p>
                                    <ul>
                                        <li>Pantalla rota</li>
                                        <li>Batería agotada</li>
                                        <li>Problemas de carga</li>
                                        <li>Daños por agua</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="example-box">
                            <h6><i class="bi bi-lightbulb text-warning me-2"></i>Ejemplo Práctico</h6>
                            <p>Para un taller que principalmente repara iPhone y Samsung:</p>
                            <ol>
                                <li>Agregar marcas: "Apple", "Samsung"</li>
                                <li>Para Apple: iPhone 12, iPhone 13, iPhone 14, iPhone 15</li>
                                <li>Para Samsung: Galaxy S21, Galaxy S22, Galaxy S23, Galaxy S24</li>
                                <li>Problemas comunes: "Pantalla rota", "Batería no carga", "Botón home no funciona"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="guide-card">
                        <h4>2. Primera Reparación</h4>
                        <p>Aprende a registrar tu primera reparación paso a paso:</p>

                        <div class="video-placeholder">
                            <div class="video-thumbnail">
                                <i class="bi bi-play-circle display-1 text-primary"></i>
                                <p>Video Tutorial: Registro de Primera Reparación</p>
                                <small class="text-muted">Duración: 3:45 minutos</small>
                            </div>
                        </div>

                        <div class="quick-steps">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mini-step">
                                        <i class="bi bi-1-circle text-primary"></i>
                                        <div>
                                            <h6>Datos del Cliente</h6>
                                            <small>Nombre, teléfono, email</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mini-step">
                                        <i class="bi bi-2-circle text-primary"></i>
                                        <div>
                                            <h6>Información del Dispositivo</h6>
                                            <small>Marca, modelo, IMEI</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mini-step">
                                        <i class="bi bi-3-circle text-primary"></i>
                                        <div>
                                            <h6>Descripción del Problema</h6>
                                            <small>Detalle específico del fallo</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mini-step">
                                        <i class="bi bi-4-circle text-primary"></i>
                                        <div>
                                            <h6>Presupuesto</h6>
                                            <small>Costo estimado y tiempo</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Guía de Reparaciones -->
                <section id="repairs-guide" class="help-section">
                    <div class="section-header">
                        <h2><i class="bi bi-tools me-2"></i>Gestión de Reparaciones</h2>
                        <p>Domina el flujo completo de reparaciones desde registro hasta entrega</p>
                    </div>

                    <div class="guide-card">
                        <h4>Estados de Reparación</h4>
                        <div class="states-flow">
                            <div class="state-item">
                                <div class="state-badge pending">Pendiente</div>
                                <p>Reparación registrada, esperando diagnóstico</p>
                            </div>
                            <div class="flow-arrow">→</div>
                            <div class="state-item">
                                <div class="state-badge in-progress">En Proceso</div>
                                <p>Reparación en curso, trabajando en el dispositivo</p>
                            </div>
                            <div class="flow-arrow">→</div>
                            <div class="state-item">
                                <div class="state-badge completed">Completada</div>
                                <p>Reparación terminada, lista para entrega</p>
                            </div>
                            <div class="flow-arrow">→</div>
                            <div class="state-item">
                                <div class="state-badge delivered">Entregada</div>
                                <p>Dispositivo entregado al cliente</p>
                            </div>
                        </div>
                    </div>

                    <div class="guide-card">
                        <h4>Casos de Uso Comunes</h4>
                        <div class="accordion" id="casesAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#case1">
                                        <i class="bi bi-phone me-2"></i>Reparación de Pantalla Rota
                                    </button>
                                </h2>
                                <div id="case1" class="accordion-collapse collapse show" data-bs-parent="#casesAccordion">
                                    <div class="accordion-body">
                                        <h6>Proceso Completo:</h6>
                                        <ol>
                                            <li><strong>Recepción:</strong> Cliente trae iPhone 14 con pantalla rota</li>
                                            <li><strong>Diagnóstico:</strong> Verificar que solo sea la pantalla, no el LCD</li>
                                            <li><strong>Presupuesto:</strong> Pantalla original €180, compatible €120</li>
                                            <li><strong>Confirmación:</strong> Cliente acepta pantalla compatible</li>
                                            <li><strong>Reparación:</strong> Cambio de pantalla (2-3 horas)</li>
                                            <li><strong>Pruebas:</strong> Verificar touch, brillo, colores</li>
                                            <li><strong>Entrega:</strong> Dispositivo funcionando + garantía 30 días</li>
                                        </ol>
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>Consejo:</strong> Siempre toma fotos del dispositivo antes y después de la reparación.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#case2">
                                        <i class="bi bi-battery me-2"></i>Cambio de Batería
                                    </button>
                                </h2>
                                <div id="case2" class="accordion-collapse collapse" data-bs-parent="#casesAccordion">
                                    <div class="accordion-body">
                                        <h6>Proceso Completo:</h6>
                                        <ol>
                                            <li><strong>Síntomas:</strong> Batería se agota rápidamente</li>
                                            <li><strong>Diagnóstico:</strong> Verificar salud de batería (<80%)</li>
                                            <li><strong>Presupuesto:</strong> Batería €45 + mano de obra €25</li>
                                            <li><strong>Tiempo:</strong> 1-2 horas</li>
                                            <li><strong>Pruebas:</strong> Verificar carga y duración</li>
                                            <li><strong>Garantía:</strong> 6 meses en batería</li>
                                        </ol>
                                        <div class="alert alert-warning">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>Importante:</strong> Calibra la batería después del cambio.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#case3">
                                        <i class="bi bi-droplet me-2"></i>Daño por Agua
                                    </button>
                                </h2>
                                <div id="case3" class="accordion-collapse collapse" data-bs-parent="#casesAccordion">
                                    <div class="accordion-body">
                                        <h6>Proceso de Urgencia:</h6>
                                        <ol>
                                            <li><strong>Recepción Inmediata:</strong> No encender el dispositivo</li>
                                            <li><strong>Desmontaje:</strong> Desarmar completamente</li>
                                            <li><strong>Limpieza:</strong> Alcohol isopropílico + ultrasonidos</li>
                                            <li><strong>Secado:</strong> 24-48 horas en deshumidificador</li>
                                            <li><strong>Diagnóstico:</strong> Verificar componentes dañados</li>
                                            <li><strong>Presupuesto:</strong> Según daños encontrados</li>
                                        </ol>
                                        <div class="alert alert-danger">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <strong>Crítico:</strong> Tiempo es crucial. Actuar en primeras 24 horas.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Guía de Clientes -->
                <section id="customers-guide" class="help-section">
                    <div class="section-header">
                        <h2><i class="bi bi-people me-2"></i>Gestión de Clientes</h2>
                        <p>Mantén excelente relación con tus clientes y fidelízalos</p>
                    </div>

                    <div class="guide-card">
                        <h4>Comunicación Efectiva</h4>
                        <div class="communication-tips">
                            <div class="tip-item">
                                <div class="tip-icon">
                                    <i class="bi bi-telephone text-primary"></i>
                                </div>
                                <div class="tip-content">
                                    <h6>Llamadas de Seguimiento</h6>
                                    <p>Contacta al cliente cuando:</p>
                                    <ul>
                                        <li>El diagnóstico esté listo</li>
                                        <li>Necesites autorización para proceder</li>
                                        <li>La reparación esté completada</li>
                                        <li>Haya retrasos inesperados</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="tip-item">
                                <div class="tip-icon">
                                    <i class="bi bi-chat-dots text-success"></i>
                                </div>
                                <div class="tip-content">
                                    <h6>Mensajes de WhatsApp</h6>
                                    <p>Usa mensajes para:</p>
                                    <ul>
                                        <li>Confirmar recepción del dispositivo</li>
                                        <li>Enviar presupuestos</li>
                                        <li>Notificar cuando esté listo</li>
                                        <li>Recordar garantías</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="guide-card">
                        <h4>Plantillas de Mensajes</h4>
                        <div class="message-templates">
                            <div class="template-item">
                                <h6>📱 Recepción del Dispositivo</h6>
                                <div class="template-content">
                                    <p>"Hola [Cliente], hemos recibido tu [Dispositivo] con [Problema]. Le asignaremos el número de referencia [#REF]. Te contactaremos en 24h con el diagnóstico y presupuesto. ¡Gracias por confiar en nosotros!"</p>
                                </div>
                            </div>

                            <div class="template-item">
                                <h6>💰 Presupuesto</h6>
                                <div class="template-content">
                                    <p>"Hola [Cliente], hemos diagnosticado tu [Dispositivo] #[REF]. El problema es: [Diagnóstico]. Presupuesto: [Precio]€. Tiempo estimado: [Tiempo]. ¿Deseas proceder? Responde SÍ para confirmar."</p>
                                </div>
                            </div>

                            <div class="template-item">
                                <h6>✅ Reparación Completada</h6>
                                <div class="template-content">
                                    <p>"¡Genial! Tu [Dispositivo] #[REF] está listo. La reparación ha sido exitosa y funciona perfectamente. Puedes pasar a recogerlo. Horario: [Horario]. Incluye garantía de [Garantía] días."</p>
                                </div>
                            </div>

                            <div class="template-item">
                                <h6>📞 Seguimiento Post-Entrega</h6>
                                <div class="template-content">
                                    <p>"Hola [Cliente], esperamos que tu [Dispositivo] funcione perfectamente. Recuerda que tienes garantía hasta [Fecha]. Si tienes algún problema, no dudes en contactarnos. ¡Gracias por elegirnos!"</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Guía de Informes -->
                <section id="reports-guide" class="help-section">
                    <div class="section-header">
                        <h2><i class="bi bi-graph-up me-2"></i>Informes y Análisis</h2>
                        <p>Entiende tu negocio con datos precisos y toma decisiones informadas</p>
                    </div>

                    <div class="guide-card">
                        <h4>Métricas Clave</h4>
                        <div class="metrics-grid">
                            <div class="metric-card">
                                <div class="metric-icon">
                                    <i class="bi bi-currency-euro text-success"></i>
                                </div>
                                <div class="metric-content">
                                    <h6>Ingresos Mensuales</h6>
                                    <p>Suma de todas las reparaciones entregadas y pagadas en el mes</p>
                                    <small class="text-muted">Objetivo: Crecimiento del 10% mensual</small>
                                </div>
                            </div>

                            <div class="metric-card">
                                <div class="metric-icon">
                                    <i class="bi bi-speedometer2 text-primary"></i>
                                </div>
                                <div class="metric-content">
                                    <h6>Tiempo Promedio</h6>
                                    <p>Tiempo promedio desde recepción hasta entrega</p>
                                    <small class="text-muted">Objetivo: Menos de 3 días</small>
                                </div>
                            </div>

                            <div class="metric-card">
                                <div class="metric-icon">
                                    <i class="bi bi-star text-warning"></i>
                                </div>
                                <div class="metric-content">
                                    <h6>Satisfacción Cliente</h6>
                                    <p>Porcentaje de clientes satisfechos y que recomiendan</p>
                                    <small class="text-muted">Objetivo: Más del 95%</small>
                                </div>
                            </div>

                            <div class="metric-card">
                                <div class="metric-icon">
                                    <i class="bi bi-repeat text-info"></i>
                                </div>
                                <div class="metric-content">
                                    <h6>Clientes Recurrentes</h6>
                                    <p>Porcentaje de clientes que regresan</p>
                                    <small class="text-muted">Objetivo: Más del 40%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Guía de Configuración -->
                <section id="settings-guide" class="help-section">
                    <div class="section-header">
                        <h2><i class="bi bi-gear me-2"></i>Configuración Avanzada</h2>
                        <p>Personaliza RepairPoint según las necesidades de tu taller</p>
                    </div>

                    <div class="guide-card">
                        <h4>Configuraciones Importantes</h4>
                        <div class="settings-list">
                            <div class="setting-item">
                                <div class="setting-icon">
                                    <i class="bi bi-shop text-primary"></i>
                                </div>
                                <div class="setting-content">
                                    <h6>Información del Taller</h6>
                                    <p>Configura nombre, dirección, teléfonos y logo. Esta información aparecerá en los recibos y presupuestos.</p>
                                    <span class="badge bg-success">Esencial</span>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-icon">
                                    <i class="bi bi-tags text-warning"></i>
                                </div>
                                <div class="setting-content">
                                    <h6>Marcas y Modelos</h6>
                                    <p>Mantén actualizada la lista de dispositivos que reparas. Añade nuevos modelos cuando salgan al mercado.</p>
                                    <span class="badge bg-warning">Importante</span>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-icon">
                                    <i class="bi bi-people text-info"></i>
                                </div>
                                <div class="setting-content">
                                    <h6>Usuarios y Permisos</h6>
                                    <p>Gestiona quién puede acceder al sistema y qué acciones puede realizar cada usuario.</p>
                                    <span class="badge bg-info">Seguridad</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Preguntas Frecuentes -->
                <section id="faq" class="help-section">
                    <div class="section-header">
                        <h2><i class="bi bi-question-circle me-2"></i>Preguntas Frecuentes</h2>
                        <p>Respuestas a las dudas más comunes sobre RepairPoint</p>
                    </div>

                    <div class="faq-container">
                        <div class="faq-category">
                            <h5>🚀 Primeros Pasos</h5>
                            <div class="faq-item">
                                <h6>¿Cómo empiezo a usar RepairPoint?</h6>
                                <p>Sigue estos pasos: 1) Configura la información de tu taller, 2) Añade marcas y modelos, 3) Crea tu primera reparación. El sistema te guiará paso a paso.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Qué información necesito para registrar una reparación?</h6>
                                <p>Necesitas: datos del cliente (nombre, teléfono), información del dispositivo (marca, modelo, IMEI), descripción del problema y presupuesto estimado.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Puedo personalizar los estados de reparación?</h6>
                                <p>Sí, puedes personalizar los estados según tu flujo de trabajo. Los estados por defecto son: Pendiente, En Proceso, Completada y Entregada.</p>
                            </div>
                        </div>

                        <div class="faq-category">
                            <h5>💰 Precios y Presupuestos</h5>
                            <div class="faq-item">
                                <h6>¿Cómo calculo el presupuesto de una reparación?</h6>
                                <p>Considera: costo de repuestos + mano de obra + tiempo invertido + margen de ganancia. El sistema te ayuda a llevar control de costos.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Puedo cambiar el precio después de crear la reparación?</h6>
                                <p>Sí, puedes modificar precios en cualquier momento. Se recomienda confirmar cambios importantes con el cliente.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Cómo manejo los descuentos?</h6>
                                <p>Puedes aplicar descuentos directamente en el campo de precio final. También puedes añadir notas explicando el descuento.</p>
                            </div>
                        </div>

                        <div class="faq-category">
                            <h5>🔧 Reparaciones</h5>
                            <div class="faq-item">
                                <h6>¿Cómo gestiono las garantías?</h6>
                                <p>Cada reparación puede tener días de garantía personalizados. El sistema te recuerda cuando una garantía está por vencer.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Qué hago si necesito reabrir una reparación?</h6>
                                <p>Puedes reabrir reparaciones desde el botón "Reabrir" en los detalles. Esto es útil para garantías o problemas relacionados.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Cómo busco reparaciones específicas?</h6>
                                <p>Usa el buscador principal por referencia, nombre de cliente, teléfono o modelo de dispositivo. También puedes filtrar por estado.</p>
                            </div>
                        </div>

                        <div class="faq-category">
                            <h5>📊 Informes y Datos</h5>
                            <div class="faq-item">
                                <h6>¿Qué informes puedo generar?</h6>
                                <p>Puedes generar informes de: ingresos mensuales, reparaciones por estado, dispositivos más reparados, clientes frecuentes y análisis de rentabilidad.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Cómo exporto mis datos?</h6>
                                <p>Todos los informes se pueden exportar a Excel o PDF. También puedes imprimir directamente desde el navegador.</p>
                            </div>

                            <div class="faq-item">
                                <h6>¿Los datos están seguros?</h6>
                                <p>Sí, todos los datos se almacenan de forma segura. Se recomienda hacer respaldos regulares desde la sección de configuración.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Sidebar con navegación -->
            <div class="col-lg-3">
                <div class="help-sidebar">
                    <div class="sidebar-card">
                        <h6><i class="bi bi-bookmark-star me-2"></i>Acceso Rápido</h6>
                        <ul class="sidebar-links">
                            <li><a href="#getting-started">Primeros Pasos</a></li>
                            <li><a href="#repairs-guide">Reparaciones</a></li>
                            <li><a href="#customers-guide">Clientes</a></li>
                            <li><a href="#reports-guide">Informes</a></li>
                            <li><a href="#settings-guide">Configuración</a></li>
                            <li><a href="#faq">Preguntas Frecuentes</a></li>
                        </ul>
                    </div>

                    <div class="sidebar-card">
                        <h6><i class="bi bi-lightbulb me-2"></i>Consejo del Día</h6>
                        <div class="daily-tip">
                            <p><strong>Organiza tu espacio:</strong> Mantén un área limpia y organizada para trabajar. Esto mejora la eficiencia y da mejor impresión a los clientes.</p>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <h6><i class="bi bi-headset me-2"></i>¿Necesitas Ayuda?</h6>
                        <div class="contact-info">
                            <p>Si no encuentras la respuesta que buscas, contáctanos:</p>
                            <div class="contact-methods">
                                <a href="mailto:aadlani9@gmail.com" class="contact-btn">
                                    <i class="bi bi-envelope"></i>
                                    Email Soporte
                                </a>
                                <a href="tel:+34637925082" class="contact-btn">
                                    <i class="bi bi-telephone"></i>
                                    Llamar Ahora
                                </a>
                                <a href="#" class="contact-btn" id="startInteractiveTour">
                                    <i class="bi bi-play-circle"></i>
                                    Guía Interactiva
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <h6><i class="bi bi-star me-2"></i>Califica la Ayuda</h6>
                        <div class="rating-section">
                            <p>¿Te ha sido útil esta información?</p>
                            <div class="rating-stars">
                                <i class="bi bi-star" data-rating="1"></i>
                                <i class="bi bi-star" data-rating="2"></i>
                                <i class="bi bi-star" data-rating="3"></i>
                                <i class="bi bi-star" data-rating="4"></i>
                                <i class="bi bi-star" data-rating="5"></i>
                            </div>
                            <small class="text-muted">Tu opinión nos ayuda a mejorar</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ===================================================
         * Estilos para el Sistema de Ayuda
         * ================================================= */

        :root {
            --help-primary: #0d6efd;
            --help-secondary: #6c757d;
            --help-success: #198754;
            --help-warning: #ffc107;
            --help-danger: #dc3545;
            --help-info: #0dcaf0;
            --help-light: #f8f9fa;
            --help-dark: #212529;
            --help-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Header Principal */
        .help-header {
            background: var(--help-gradient);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .help-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: floatBackground 20s ease-in-out infinite;
        }

        @keyframes floatBackground {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(5deg); }
        }

        .search-container {
            position: relative;
            z-index: 2;
        }

        .search-container .form-control {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 25px;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
        }

        .search-container .btn {
            border-radius: 0 25px 25px 0;
            padding: 1rem 2rem;
            font-weight: 600;
        }

        /* Navegación Rápida */
        .quick-nav {
            margin: 2rem 0;
        }

        .quick-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-decoration: none;
            color: var(--help-dark);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .quick-nav-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
            border-color: var(--help-primary);
            color: var(--help-primary);
        }

        .quick-nav-item i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--help-primary);
        }

        .quick-nav-item span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Secciones de Ayuda */
        .help-section {
            margin-bottom: 4rem;
            scroll-margin-top: 100px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, var(--help-light) 0%, #ffffff 100%);
            border-radius: 12px;
            border-left: 5px solid var(--help-primary);
        }

        .section-header h2 {
            color: var(--help-primary);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: var(--help-secondary);
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        /* Tarjetas de Guía */
        .guide-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .guide-card h4 {
            color: var(--help-primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        /* Pasos del Tutorial */
        .step-by-step {
            margin: 2rem 0;
        }

        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--help-light);
            border-radius: 12px;
            position: relative;
        }

        .step::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 60px;
            width: 2px;
            height: calc(100% + 1rem);
            background: var(--help-primary);
            opacity: 0.3;
        }

        .step:last-child::before {
            display: none;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: var(--help-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .step-content h6 {
            color: var(--help-primary);
            margin-bottom: 0.5rem;
        }

        .step-content ul {
            margin-bottom: 0;
            padding-left: 1rem;
        }

        /* Cajas de Ejemplo */
        .example-box {
            background: rgba(13, 110, 253, 0.05);
            border: 1px solid rgba(13, 110, 253, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .example-box h6 {
            color: var(--help-primary);
            margin-bottom: 1rem;
        }

        /* Flujo de Estados */
        .states-flow {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 2rem 0;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .state-item {
            text-align: center;
            flex: 1;
            min-width: 150px;
        }

        .state-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .state-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .state-badge.in-progress {
            background: #cff4fc;
            color: #055160;
        }

        .state-badge.completed {
            background: #d1e7dd;
            color: #0a3622;
        }

        .state-badge.delivered {
            background: #e2e3f1;
            color: #383d41;
        }

        .flow-arrow {
            font-size: 1.5rem;
            color: var(--help-primary);
            font-weight: bold;
        }

        /* Pasos Mini */
        .quick-steps {
            margin: 2rem 0;
        }

        .mini-step {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }

        .mini-step i {
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .mini-step h6 {
            margin-bottom: 0.25rem;
            color: var(--help-primary);
        }

        .mini-step small {
            color: var(--help-secondary);
        }

        /* Placeholder de Video */
        .video-placeholder {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            margin: 2rem 0;
            border: 2px dashed #dee2e6;
        }

        .video-thumbnail {
            color: var(--help-secondary);
        }

        .video-thumbnail i {
            margin-bottom: 1rem;
        }

        .video-thumbnail p {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Consejos de Comunicación */
        .communication-tips {
            margin: 2rem 0;
        }

        .tip-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--help-primary);
        }

        .tip-icon {
            width: 50px;
            height: 50px;
            background: var(--help-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .tip-icon i {
            font-size: 1.5rem;
        }

        .tip-content h6 {
            color: var(--help-primary);
            margin-bottom: 0.5rem;
        }

        .tip-content ul {
            margin-bottom: 0;
        }

        /* Plantillas de Mensajes */
        .message-templates {
            margin: 2rem 0;
        }

        .template-item {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: var(--help-light);
            border-radius: 12px;
            border-left: 4px solid var(--help-success);
        }

        .template-item h6 {
            color: var(--help-primary);
            margin-bottom: 1rem;
        }

        .template-content {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .template-content p {
            margin-bottom: 0;
            font-style: italic;
            color: var(--help-dark);
        }

        /* Grid de Métricas */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            background: var(--help-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .metric-icon i {
            font-size: 1.8rem;
        }

        .metric-content h6 {
            color: var(--help-primary);
            margin-bottom: 0.5rem;
        }

        .metric-content p {
            color: var(--help-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        /* Lista de Configuraciones */
        .settings-list {
            margin: 2rem 0;
        }

        .setting-item {
            display: flex;
            align-items: flex-start;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
        }

        .setting-icon {
            width: 50px;
            height: 50px;
            background: var(--help-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .setting-icon i {
            font-size: 1.5rem;
        }

        .setting-content h6 {
            color: var(--help-primary);
            margin-bottom: 0.5rem;
        }

        .setting-content p {
            color: var(--help-secondary);
            margin-bottom: 0.5rem;
        }

        /* FAQ */
        .faq-container {
            margin: 2rem 0;
        }

        .faq-category {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }

        .faq-category h5 {
            color: var(--help-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--help-light);
        }

        .faq-item {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--help-light);
            border-radius: 8px;
            border-left: 4px solid var(--help-primary);
        }

        .faq-item h6 {
            color: var(--help-primary);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .faq-item p {
            color: var(--help-secondary);
            margin-bottom: 0;
        }

        /* Sidebar */
        .help-sidebar {
            position: sticky;
            top: 100px;
        }

        .sidebar-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .sidebar-card h6 {
            color: var(--help-primary);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .sidebar-links {
            list-style: none;
            padding: 0;
        }

        .sidebar-links li {
            margin-bottom: 0.5rem;
        }

        .sidebar-links a {
            color: var(--help-secondary);
            text-decoration: none;
            padding: 0.5rem 0;
            display: block;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .sidebar-links a:hover {
            color: var(--help-primary);
            background: var(--help-light);
            padding-left: 1rem;
        }

        /* Consejo Diario */
        .daily-tip {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid var(--help-warning);
        }

        .daily-tip p {
            margin-bottom: 0;
            color: var(--help-dark);
        }

        /* Información de Contacto */
        .contact-info p {
            color: var(--help-secondary);
            margin-bottom: 1rem;
        }

        .contact-methods {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .contact-btn {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--help-light);
            color: var(--help-primary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .contact-btn:hover {
            background: var(--help-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .contact-btn i {
            margin-right: 0.5rem;
        }

        /* Sistema de Calificación */
        .rating-section {
            text-align: center;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .rating-stars i {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .rating-stars i:hover,
        .rating-stars i.active {
            color: var(--help-warning);
            transform: scale(1.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .help-header {
                padding: 2rem 1rem;
                text-align: center;
            }

            .help-header .display-5 {
                font-size: 2rem;
            }

            .search-container .form-control {
                font-size: 1rem;
                padding: 0.75rem 1rem;
            }

            .states-flow {
                flex-direction: column;
                gap: 1rem;
            }

            .flow-arrow {
                transform: rotate(90deg);
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .quick-nav .row {
                gap: 1rem;
            }

            .help-sidebar {
                position: static;
                margin-top: 2rem;
            }

            .step {
                flex-direction: column;
                text-align: center;
            }

            .step-number {
                margin-bottom: 1rem;
            }

            .tip-item,
            .setting-item {
                flex-direction: column;
                text-align: center;
            }

            .tip-icon,
            .setting-icon {
                margin: 0 auto 1rem;
            }

            .contact-methods {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .guide-card {
                padding: 1rem;
            }

            .step {
                padding: 1rem;
            }

            .faq-category {
                padding: 1rem;
            }

            .sidebar-card {
                padding: 1rem;
            }
        }

        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Scroll suave */
        html {
            scroll-behavior: smooth;
        }

        /* Highlight de búsqueda */
        .search-highlight {
            background: rgba(255, 255, 0, 0.3);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>

    <script>
        // ===================================================
        // Sistema de Búsqueda en la Ayuda
        // ===================================================

        class HelpSearch {
            constructor() {
                this.searchInput = document.getElementById('helpSearch');
                this.searchBtn = document.getElementById('searchBtn');
                this.init();
            }

            init() {
                this.searchBtn.addEventListener('click', () => this.performSearch());
                this.searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.performSearch();
                    }
                });

                // Búsqueda en tiempo real
                this.searchInput.addEventListener('input', () => {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        this.performSearch();
                    }, 500);
                });
            }

            performSearch() {
                const query = this.searchInput.value.toLowerCase().trim();

                // Limpiar highlights previos
                this.clearHighlights();

                if (query === '') {
                    this.showAllSections();
                    return;
                }

                let foundResults = false;
                const sections = document.querySelectorAll('.help-section');

                sections.forEach(section => {
                    const content = section.textContent.toLowerCase();
                    const hasMatch = content.includes(query);

                    if (hasMatch) {
                        section.style.display = 'block';
                        this.highlightText(section, query);
                        foundResults = true;
                    } else {
                        section.style.display = 'none';
                    }
                });

                if (!foundResults) {
                    this.showNoResults();
                }
            }

            highlightText(element, query) {
                const walker = document.createTreeWalker(
                    element,
                    NodeFilter.SHOW_TEXT,
                    null,
                    false
                );

                const textNodes = [];
                let node;

                while (node = walker.nextNode()) {
                    textNodes.push(node);
                }

                textNodes.forEach(textNode => {
                    if (textNode.textContent.toLowerCase().includes(query)) {
                        const parent = textNode.parentNode;
                        const regex = new RegExp(`(${query})`, 'gi');
                        const newHTML = textNode.textContent.replace(regex, '<span class="search-highlight">$1</span>');

                        const wrapper = document.createElement('span');
                        wrapper.innerHTML = newHTML;
                        parent.replaceChild(wrapper, textNode);
                    }
                });
            }

            clearHighlights() {
                const highlights = document.querySelectorAll('.search-highlight');
                highlights.forEach(highlight => {
                    const parent = highlight.parentNode;
                    parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                    parent.normalize();
                });
            }

            showAllSections() {
                const sections = document.querySelectorAll('.help-section');
                sections.forEach(section => {
                    section.style.display = 'block';
                });
            }

            showNoResults() {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-results text-center py-5';
                noResultsDiv.innerHTML = `
                <i class="bi bi-search display-1 text-muted mb-3"></i>
                <h4>No se encontraron resultados</h4>
                <p class="text-muted">Intenta con otros términos de búsqueda</p>
                <button class="btn btn-primary" onclick="document.getElementById('helpSearch').value = ''; helpSearch.showAllSections();">
                    <i class="bi bi-arrow-clockwise me-2"></i>Ver Todo
                </button>
            `;

                document.querySelector('.col-lg-9').appendChild(noResultsDiv);
            }
        }

        // ===================================================
        // Sistema de Calificación
        // ===================================================

        class RatingSystem {
            constructor() {
                this.stars = document.querySelectorAll('.rating-stars i');
                this.init();
            }

            init() {
                this.stars.forEach((star, index) => {
                    star.addEventListener('click', () => this.setRating(index + 1));
                    star.addEventListener('mouseenter', () => this.highlightStars(index + 1));
                    star.addEventListener('mouseleave', () => this.resetHighlight());
                });
            }

            setRating(rating) {
                this.currentRating = rating;
                this.updateStars(rating);
                this.sendRating(rating);
            }

            highlightStars(rating) {
                this.stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }

            resetHighlight() {
                if (this.currentRating) {
                    this.updateStars(this.currentRating);
                } else {
                    this.stars.forEach(star => star.classList.remove('active'));
                }
            }

            updateStars(rating) {
                this.stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }

            sendRating(rating) {
                // Aquí enviarías la calificación al servidor
                console.log(`Calificación enviada: ${rating} estrellas`);

                // Mostrar mensaje de agradecimiento
                const ratingSection = document.querySelector('.rating-section');
                ratingSection.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>¡Gracias por tu calificación!</strong>
                    <p class="mb-0">Tu opinión nos ayuda a mejorar el sistema de ayuda.</p>
                </div>
            `;
            }
        }

        // ===================================================
        // Navegación Suave
        // ===================================================

        class SmoothNavigation {
            constructor() {
                this.init();
            }

            init() {
                // Enlaces de navegación rápida
                document.querySelectorAll('a[href^="#"]').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const target = document.querySelector(link.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });

                            // Actualizar URL sin recargar
                            history.pushState(null, null, link.getAttribute('href'));
                        }
                    });
                });

                // Navegación desde URL directa
                if (window.location.hash) {
                    setTimeout(() => {
                        const target = document.querySelector(window.location.hash);
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }, 100);
                }
            }
        }

        // ===================================================
        // Inicialización
        // ===================================================

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Help System Loading...');

            // Inicializar sistemas
            const helpSearch = new HelpSearch();
            const ratingSystem = new RatingSystem();
            const smoothNavigation = new SmoothNavigation();

            // Hacer accesible globalmente para funciones onclick
            window.helpSearch = helpSearch;

            // Botón de guía interactiva
            const tourBtn = document.getElementById('startInteractiveTour');
            if (tourBtn) {
                tourBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Redirigir a la página principal con parámetro para iniciar tour
                    window.location.href = '<?= url('pages/dashboard.php') ?>?start_tour=1';
                });
            }

            // Acordeón personalizado para casos de uso
            const accordionButtons = document.querySelectorAll('.accordion-button');
            accordionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    setTimeout(() => {
                        if (this.getAttribute('aria-expanded') === 'true') {
                            icon.style.transform = 'rotate(90deg)';
                        } else {
                            icon.style.transform = 'rotate(0deg)';
                        }
                    }, 150);
                });
            });

            // Animaciones de entrada
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            });

            // Observar elementos para animaciones
            document.querySelectorAll('.guide-card, .faq-category, .sidebar-card').forEach(el => {
                observer.observe(el);
            });

            console.log('✅ Help System Fully Loaded');
        });
    </script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>