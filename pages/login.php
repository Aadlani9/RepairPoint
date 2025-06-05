<?php
/**
 * RepairPoint - Página de Inicio de Sesión
 */

// Definir acceso seguro
define('SECURE_ACCESS', true);

// Incluir configuración
require_once '../config/config.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'auth.php';

// Si ya hay sesión activa, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: ' . url('pages/dashboard.php'));
    exit;
}

$page_title = 'Iniciar Sesión';
$body_class = 'login-page bg-primary';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanString($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Verificar CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de seguridad inválido', MSG_ERROR);
    } else {
        $auth = getAuth();
        $result = $auth->login($email, $password, $remember_me);
        
        if ($result['success']) {
            setMessage($result['message'], MSG_SUCCESS);
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            setMessage($result['message'], MSG_ERROR);
        }
    }
}

// Incluir header
require_once INCLUDES_PATH . 'header.php';
?>

<div class="login-container">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Lado izquierdo - Información -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center bg-primary text-white">
                <div class="login-info text-center">
                    <div class="login-logo mb-4">
                        <i class="bi bi-tools" style="font-size: 5rem; opacity: 0.9;"></i>
                    </div>
                    <h1 class="display-4 fw-bold mb-4"><?= APP_NAME ?></h1>
                    <p class="lead mb-4">
                        Sistema de Gestión para Talleres de Reparación de Móviles
                    </p>
                    <div class="login-features">
                        <div class="feature-item d-flex align-items-center justify-content-center mb-3">
                            <i class="bi bi-check-circle-fill me-3 fs-5"></i>
                            <span>Gestión completa de reparaciones</span>
                        </div>
                        <div class="feature-item d-flex align-items-center justify-content-center mb-3">
                            <i class="bi bi-printer-fill me-3 fs-5"></i>
                            <span>Impresión de tickets POS</span>
                        </div>
                        <div class="feature-item d-flex align-items-center justify-content-center mb-3">
                            <i class="bi bi-phone me-3 fs-5"></i>
                            <span>Optimizado para móviles</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lado derecho - Formulario de login -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center bg-white">
                <div class="login-form-container w-100" style="max-width: 400px;">
                    <!-- Logo para móviles -->
                    <div class="text-center mb-4 d-lg-none">
                        <i class="bi bi-tools text-primary" style="font-size: 3rem;"></i>
                        <h2 class="fw-bold text-primary"><?= APP_NAME ?></h2>
                    </div>
                    
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-4">
                            <h3 class="card-title text-center mb-4 fw-bold">Iniciar Sesión</h3>
                            
                            <!-- Mostrar mensajes -->
                            <?php displayMessage(); ?>
                            
                            <!-- Formulario de login -->
                            <form method="POST" action="" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="bi bi-envelope me-2"></i>Email
                                    </label>
                                    <input 
                                        type="email" 
                                        class="form-control form-control-lg" 
                                        id="email" 
                                        name="email" 
                                        placeholder="tu@email.com"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                        required
                                        autocomplete="email"
                                    >
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock me-2"></i>Contraseña
                                    </label>
                                    <div class="input-group">
                                        <input 
                                            type="password" 
                                            class="form-control form-control-lg" 
                                            id="password" 
                                            name="password" 
                                            placeholder="Tu contraseña"
                                            required
                                            autocomplete="current-password"
                                        >
                                        <button 
                                            class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword"
                                            title="Mostrar/Ocultar contraseña"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            id="remember_me" 
                                            name="remember_me"
                                            <?= isset($_POST['remember_me']) ? 'checked' : '' ?>
                                        >
                                        <label class="form-check-label" for="remember_me">
                                            Recordarme
                                        </label>
                                    </div>
                                    <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>
                                        Iniciar Sesión
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Información de prueba (solo en desarrollo) -->
                            <?php if (isDebugMode()): ?>
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6 class="fw-bold text-muted">Usuarios de Prueba:</h6>
                                <small class="d-block">
                                    <strong>Admin:</strong> admin@tecnofix.es / password123
                                </small>
                                <small class="d-block">
                                    <strong>Staff:</strong> empleado@tecnofix.es / password123
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Enlaces adicionales -->
                    <div class="text-center mt-4">
                        <p class="text-muted">
                            <small>
                                ¿Necesitas ayuda? 
                                <a href="mailto:soporte@repairpoint.es" class="text-decoration-none">
                                    Contacta soporte
                                </a>
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de recuperación de contraseña -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordModalLabel">
                    <i class="bi bi-key me-2"></i>Recuperar Contraseña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="forgotPasswordForm">
                <div class="modal-body">
                    <p class="text-muted">
                        Introduce tu email y te enviaremos las instrucciones para recuperar tu contraseña.
                    </p>
                    <div class="mb-3">
                        <label for="forgot_email" class="form-label">Email</label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="forgot_email" 
                            name="forgot_email" 
                            placeholder="tu@email.com"
                            required
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-envelope me-2"></i>Enviar Instrucciones
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para la página de login */
.login-page {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%);
    min-height: 100vh;
}

.login-container {
    min-height: 100vh;
}

.login-info {
    max-width: 500px;
    padding: 2rem;
}

.login-logo i {
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.feature-item {
    opacity: 0.9;
    transition: opacity 0.3s ease;
}

.feature-item:hover {
    opacity: 1;
}

.login-form-container {
    padding: 2rem;
}

.card {
    border-radius: 1rem;
    overflow: hidden;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn-primary {
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.4);
}

/* Efectos de animación */
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.login-info {
    animation: slideInLeft 0.8s ease-out;
}

.login-form-container {
    animation: slideInRight 0.8s ease-out;
}

/* Responsive */
@media (max-width: 991.98px) {
    .login-form-container {
        padding: 1rem;
    }
    
    .card-body {
        padding: 2rem 1.5rem !important;
    }
}

@media (max-width: 576px) {
    .login-form-container {
        padding: 0.5rem;
    }
    
    .card-body {
        padding: 1.5rem 1rem !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle de mostrar/ocultar contraseña
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    
    // Manejo del formulario de recuperación de contraseña
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('forgot_email').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            
            FormHandler.setButtonLoading(submitBtn, true);
            
            try {
                const response = await Ajax.post('<?= url("api/forgot-password.php") ?>', {
                    email: email
                });
                
                if (response.success) {
                    Utils.showNotification(response.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
                    forgotPasswordForm.reset();
                } else {
                    Utils.showNotification(response.message, 'error');
                }
            } catch (error) {
                Utils.showNotification('Error de conexión', 'error');
            } finally {
                FormHandler.setButtonLoading(submitBtn, false);
            }
        });
    }
    
    // Auto-focus en el campo email
    const emailInput = document.getElementById('email');
    if (emailInput && !emailInput.value) {
        emailInput.focus();
    }
});
</script>

<?php
// Incluir footer
require_once INCLUDES_PATH . 'footer.php';
?>