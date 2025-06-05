
<?php
/**
 * RepairPoint - Página Principal
 * Página de bienvenida y login
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once 'config/config.php';

// Si ya hay sesión activa, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('pages/dashboard.php'));
    exit;
}

$page_title = 'Bienvenido';
$body_class = 'landing-page';

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="display-4 fw-bold mb-4 fade-in">
                        <i class="bi bi-tools me-3"></i>
                        <?= APP_NAME ?>
                    </h1>
                    <p class="lead mb-4 fade-in">
                        <?= APP_DESCRIPTION ?>
                    </p>
                    <div class="hero-features mb-4 fade-in">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-3 fs-4"></i>
                                    <span>Gestión completa de reparaciones</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="bi bi-printer-fill text-success me-3 fs-4"></i>
                                    <span>Impresión de tickets POS</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="bi bi-search text-success me-3 fs-4"></i>
                                    <span>Búsqueda rápida y eficiente</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="bi bi-phone text-success me-3 fs-4"></i>
                                    <span>Optimizado para móviles</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-cta fade-in">
                        <a href="<?= url('pages/login.php') ?>" class="btn btn-light btn-lg me-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Iniciar Sesión
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-info-circle me-2"></i>
                            Más Información
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="hero-image text-center fade-in">
                    <div class="mockup-container position-relative">
                        <!-- Mockup de la aplicación -->
                        <div class="phone-mockup mx-auto">
                            <div class="phone-screen bg-white rounded p-3 shadow-lg">
                                <div class="mockup-header bg-primary text-white p-2 rounded-top d-flex align-items-center">
                                    <i class="bi bi-tools me-2"></i>
                                    <small class="fw-bold"><?= APP_NAME ?></small>
                                </div>
                                <div class="mockup-content p-3">
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <div class="stat-mini bg-primary text-white p-2 rounded text-center">
                                                <div class="h5 mb-0">25</div>
                                                <small>Activas</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <div class="stat-mini bg-success text-white p-2 rounded text-center">
                                                <div class="h5 mb-0">12</div>
                                                <small>Completadas</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mockup-list">
                                        <div class="list-item d-flex justify-content-between p-2 bg-light rounded mb-1">
                                            <span class="small"><strong>iPhone 14</strong></span>
                                            <span class="badge bg-warning">Pendiente</span>
                                        </div>
                                        <div class="list-item d-flex justify-content-between p-2 bg-light rounded mb-1">
                                            <span class="small"><strong>Xiaomi Note</strong></span>
                                            <span class="badge bg-success">Completado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="features-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center mb-5">
                <h2 class="h1 fw-bold">Características Principales</h2>
                <p class="lead text-muted">Todo lo que necesitas para gestionar tu taller de reparación</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card text-center p-4 h-100 bg-white rounded shadow-sm">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-plus-circle-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Registro Rápido</h4>
                    <p class="text-muted">
                        Registra nuevas reparaciones en segundos con formularios intuitivos y 
                        generación automática de referencias únicas.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card text-center p-4 h-100 bg-white rounded shadow-sm">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-kanban-fill text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Control de Estados</h4>
                    <p class="text-muted">
                        Gestiona el flujo de trabajo con estados claros: Pendiente, En Proceso, 
                        Completado y Entregado.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card text-center p-4 h-100 bg-white rounded shadow-sm">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-printer-fill text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Tickets POS</h4>
                    <p class="text-muted">
                        Imprime tickets profesionales compatibles con impresoras térmicas 
                        de 58mm y 80mm.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card text-center p-4 h-100 bg-white rounded shadow-sm">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-search text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Búsqueda Avanzada</h4>
                    <p class="text-muted">
                        Encuentra cualquier reparación por nombre, teléfono, referencia 
                        o dispositivo de forma instantánea.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card text-center p-4 h-100 bg-white rounded shadow-sm">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-people-fill text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Multi-Usuario</h4>
                    <p class="text-muted">
                        Soporte para múltiples empleados con roles diferenciados: 
                        Administrador y Personal.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card text-center p-4 h-100 bg-white rounded shadow-sm">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-phone-fill text-secondary" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-3">Responsive</h4>
                    <p class="text-muted">
                        Funciona perfectamente en móviles, tablets y ordenadores. 
                        Accede desde cualquier dispositivo.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section py-5 bg-primary text-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold mb-2">
                        <i class="bi bi-tools me-2"></i>
                        100%
                    </div>
                    <div class="stat-label h5">
                        Gestión Completa
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold mb-2">
                        <i class="bi bi-lightning-fill me-2"></i>
                        ⚡
                    </div>
                    <div class="stat-label h5">
                        Súper Rápido
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold mb-2">
                        <i class="bi bi-shield-check me-2"></i>
                        🔒
                    </div>
                    <div class="stat-label h5">
                        100% Seguro
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stat-item">
                    <div class="stat-number display-4 fw-bold mb-2">
                        <i class="bi bi-heart-fill me-2"></i>
                        ❤️
                    </div>
                    <div class="stat-label h5">
                        Fácil de Usar
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Demo Section -->
<section class="demo-section py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4">
                <h2 class="h1 fw-bold mb-4">¿Listo para empezar?</h2>
                <p class="lead mb-4">
                    Simplifica la gestión de tu taller de reparación con <?= APP_NAME ?>. 
                    Sistema completo, intuitivo y diseñado específicamente para talleres de móviles.
                </p>
                
                <div class="demo-features mb-4">
                    <div class="demo-feature d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                        <span>Configuración en menos de 5 minutos</span>
                    </div>
                    <div class="demo-feature d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                        <span>No requiere conocimientos técnicos</span>
                    </div>
                    <div class="demo-feature d-flex align-items-center mb-3">
                        <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                        <span>Soporte técnico incluido</span>
                    </div>
                </div>
                
                <div class="demo-cta">
                    <a href="<?= url('pages/login.php') ?>" class="btn btn-primary btn-lg me-3">
                        <i class="bi bi-play-fill me-2"></i>
                        Comenzar Ahora
                    </a>
                    <a href="#contact" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-envelope me-2"></i>
                        Contactar
                    </a>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="demo-video-container position-relative">
                    <div class="demo-placeholder bg-light rounded p-5 text-center">
                        <i class="bi bi-play-circle text-primary" style="font-size: 5rem;"></i>
                        <h4 class="mt-3">Video Demo</h4>
                        <p class="text-muted">Ver <?= APP_NAME ?> en acción</p>
                        <button class="btn btn-primary" onclick="Utils.showNotification('Demo próximamente disponible', 'info')">
                            <i class="bi bi-play me-2"></i>
                            Reproducir Demo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="contact-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center mb-5">
                <h2 class="h1 fw-bold">¿Necesitas ayuda?</h2>
                <p class="lead text-muted">Estamos aquí para ayudarte a configurar y usar <?= APP_NAME ?></p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="contact-card text-center p-4 bg-white rounded shadow-sm h-100">
                    <i class="bi bi-envelope-fill text-primary mb-3" style="font-size: 3rem;"></i>
                    <h4 class="fw-bold mb-3">Email</h4>
                    <p class="text-muted mb-3">
                        Contáctanos por email para soporte técnico o preguntas generales.
                    </p>
                    <a href="mailto:soporte@repairpoint.es" class="btn btn-outline-primary">
                        soporte@repairpoint.es
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="contact-card text-center p-4 bg-white rounded shadow-sm h-100">
                    <i class="bi bi-telephone-fill text-success mb-3" style="font-size: 3rem;"></i>
                    <h4 class="fw-bold mb-3">Teléfono</h4>
                    <p class="text-muted mb-3">
                        Llámanos para soporte inmediato y configuración personalizada.
                    </p>
                    <a href="tel:+34666123456" class="btn btn-outline-success">
                        +34 666 123 456
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="contact-card text-center p-4 bg-white rounded shadow-sm h-100">
                    <i class="bi bi-chat-dots-fill text-info mb-3" style="font-size: 3rem;"></i>
                    <h4 class="fw-bold mb-3">Chat en Vivo</h4>
                    <p class="text-muted mb-3">
                        Chat directo con nuestro equipo de soporte técnico especializado.
                    </p>
                    <button class="btn btn-outline-info" onclick="Utils.showNotification('Chat próximamente disponible', 'info')">
                        Iniciar Chat
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Estilos específicos para la landing page */
.landing-page {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hero-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    z-index: 1;
}

.hero-content,
.hero-image {
    position: relative;
    z-index: 2;
}

.phone-mockup {
    max-width: 300px;
    margin: 0 auto;
}

.phone-screen {
    border: 3px solid #333;
    border-radius: 20px;
    transform: rotate(-5deg);
    transition: transform 0.3s ease;
}

.phone-screen:hover {
    transform: rotate(0deg) scale(1.05);
}

.stat-mini {
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.stat-mini:hover {
    transform: translateY(-2px);
}

.feature-card {
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.feature-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary-color);
}

.contact-card {
    transition: all 0.3s ease;
}

.contact-card:hover {
    transform: translateY(-3px);
}

.fade-in {
    animation: fadeIn 0.8s ease-out forwards;
}

.fade-in:nth-child(2) { animation-delay: 0.2s; }
.fade-in:nth-child(3) { animation-delay: 0.4s; }
.fade-in:nth-child(4) { animation-delay: 0.6s; }

@media (max-width: 768px) {
    .hero-section {
        padding: 3rem 0;
    }
    
    .phone-mockup {
        max-width: 250px;
    }
    
    .display-4 {
        font-size: 2.5rem;
    }
}
</style>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>