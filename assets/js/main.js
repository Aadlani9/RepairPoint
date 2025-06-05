
/**
 * RepairPoint - JavaScript Principal
 * Funcionalidades generales del sistema
 */

// ===================================================
// CONFIGURACIÓN GLOBAL
// ===================================================
const RepairPoint = {
    config: {
        baseURL: window.location.origin + '/repairpoint/',
        apiURL: window.location.origin + '/repairpoint/api/',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        language: 'es',
        dateFormat: 'dd/mm/yyyy'
    },
    
    // Cache para datos frecuentemente utilizados
    cache: {
        brands: [],
        models: {},
        commonIssues: []
    }
};

// ===================================================
// UTILIDADES GENERALES
// ===================================================
const Utils = {
    /**
     * Formatear fecha
     */
    formatDate(date, format = 'dd/mm/yyyy hh:mm') {
        if (!date) return '';
        
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        
        return format
            .replace('dd', day)
            .replace('mm', month)
            .replace('yyyy', year)
            .replace('hh', hours)
            .replace('mm', minutes);
    },
    
    /**
     * Escapar HTML para prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    /**
     * Mostrar notificación
     */
    showNotification(message, type = 'info', duration = 5000) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        const iconClass = {
            'success': 'bi-check-circle',
            'error': 'bi-exclamation-triangle',
            'warning': 'bi-exclamation-triangle',
            'info': 'bi-info-circle'
        }[type] || 'bi-info-circle';
        
        alertDiv.innerHTML = `
            <i class="bi ${iconClass} me-2"></i>
            ${this.escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-remover después del tiempo especificado
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, duration);
    },
    
    /**
     * Confirmar acción
     */
    confirm(message, callback) {
        if (window.confirm(message)) {
            callback();
        }
    },
    
    /**
     * Debounce para búsquedas
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// ===================================================
// MANEJO DE AJAX
// ===================================================
const Ajax = {
    /**
     * Realizar petición AJAX
     */
    request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': RepairPoint.config.csrfToken
            }
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        // Agregar token CSRF a datos POST
        if (finalOptions.method === 'POST' && finalOptions.body) {
            const data = JSON.parse(finalOptions.body);
            data.csrf_token = RepairPoint.config.csrfToken;
            finalOptions.body = JSON.stringify(data);
        }
        
        return fetch(url, finalOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Error en petición AJAX:', error);
                Utils.showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
                throw error;
            });
    },
    
    /**
     * GET request
     */
    get(url) {
        return this.request(url);
    },
    
    /**
     * POST request
     */
    post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
};

// ===================================================
// MANEJO DE FORMULARIOS
// ===================================================
const FormHandler = {
    /**
     * Validar formulario
     */
    validate(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Este campo es obligatorio');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });
        
        return isValid;
    },
    
    /**
     * Mostrar error en campo
     */
    showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    },
    
    /**
     * Limpiar error de campo
     */
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    },
    
    /**
     * Serializar formulario a objeto
     */
    serialize(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },
    
    /**
     * Mostrar estado de carga en botón
     */
    setButtonLoading(button, loading = true) {
        if (loading) {
            button.disabled = true;
            button.classList.add('loading');
            button.dataset.originalText = button.textContent;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            button.textContent = button.dataset.originalText || button.textContent;
        }
    }
};

// ===================================================
// MANEJO DE MODELOS POR MARCA
// ===================================================
const BrandModelHandler = {
    /**
     * Cargar modelos por marca
     */
    async loadModels(brandId, modelSelect) {
        if (!brandId) {
            modelSelect.innerHTML = '<option value="">Selecciona una marca primero</option>';
            modelSelect.disabled = true;
            return;
        }
        
        try {
            modelSelect.innerHTML = '<option value="">Cargando...</option>';
            modelSelect.disabled = true;
            
            // Verificar cache
            if (RepairPoint.cache.models[brandId]) {
                this.populateModels(modelSelect, RepairPoint.cache.models[brandId]);
                return;
            }
            
            const response = await Ajax.get(`${RepairPoint.config.apiURL}models.php?brand_id=${brandId}`);
            
            if (response.success) {
                RepairPoint.cache.models[brandId] = response.data;
                this.populateModels(modelSelect, response.data);
            } else {
                throw new Error(response.message || 'Error al cargar modelos');
            }
        } catch (error) {
            console.error('Error cargando modelos:', error);
            modelSelect.innerHTML = '<option value="">Error al cargar modelos</option>';
            Utils.showNotification('Error al cargar los modelos', 'error');
        } finally {
            modelSelect.disabled = false;
        }
    },
    
    /**
     * Poblar select de modelos
     */
    populateModels(modelSelect, models) {
        modelSelect.innerHTML = '<option value="">Selecciona un modelo</option>';
        
        models.forEach(model => {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = model.name;
            modelSelect.appendChild(option);
        });
    },
    
    /**
     * Inicializar manejador de marca-modelo
     */
    init() {
        const brandSelects = document.querySelectorAll('[data-target-model]');
        
        brandSelects.forEach(brandSelect => {
            const modelSelectId = brandSelect.dataset.targetModel;
            const modelSelect = document.getElementById(modelSelectId);
            
            if (modelSelect) {
                brandSelect.addEventListener('change', () => {
                    this.loadModels(brandSelect.value, modelSelect);
                });
            }
        });
    }
};

// ===================================================
// BÚSQUEDA EN TIEMPO REAL
// ===================================================
const SearchHandler = {
    /**
     * Inicializar búsqueda
     */
    init() {
        const searchInputs = document.querySelectorAll('[data-search-target]');
        
        searchInputs.forEach(input => {
            const targetTable = document.querySelector(input.dataset.searchTarget);
            if (targetTable) {
                input.addEventListener('input', Utils.debounce((e) => {
                    this.performSearch(e.target.value, targetTable);
                }, 300));
            }
        });
    },
    
    /**
     * Realizar búsqueda
     */
    performSearch(query, table) {
        const rows = table.querySelectorAll('tbody tr');
        const searchTerm = query.toLowerCase().trim();
        
        if (!searchTerm) {
            rows.forEach(row => row.style.display = '');
            return;
        }
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    }
};

// ===================================================
// MANEJO DE PROBLEMAS COMUNES
// ===================================================
const IssueHandler = {
    /**
     * Cargar problemas comunes
     */
    async loadCommonIssues() {
        try {
            const response = await Ajax.get(`${RepairPoint.config.apiURL}common-issues.php`);
            if (response.success) {
                RepairPoint.cache.commonIssues = response.data;
                this.setupIssueSuggestions();
            }
        } catch (error) {
            console.error('Error cargando problemas comunes:', error);
        }
    },
    
    /**
     * Configurar sugerencias de problemas
     */
    setupIssueSuggestions() {
        const issueTextareas = document.querySelectorAll('[data-issue-suggestions]');
        
        issueTextareas.forEach(textarea => {
            this.createSuggestionButtons(textarea);
        });
    },
    
    /**
     * Crear botones de sugerencias
     */
    createSuggestionButtons(textarea) {
        const container = document.createElement('div');
        container.className = 'mb-2';
        
        const label = document.createElement('small');
        label.className = 'text-muted';
        label.textContent = 'Problemas comunes:';
        container.appendChild(label);
        
        const buttonGroup = document.createElement('div');
        buttonGroup.className = 'btn-group-sm mt-1';
        
        RepairPoint.cache.commonIssues.forEach(issue => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-secondary btn-sm me-1 mb-1';
            button.textContent = issue.issue_text;
            button.addEventListener('click', () => {
                textarea.value = issue.issue_text;
                textarea.focus();
            });
            buttonGroup.appendChild(button);
        });
        
        container.appendChild(buttonGroup);
        textarea.parentNode.insertBefore(container, textarea);
    }
};

// ===================================================
// INICIALIZACIÓN
// ===================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('RepairPoint iniciado');
    
    // Inicializar módulos
    BrandModelHandler.init();
    SearchHandler.init();
    IssueHandler.loadCommonIssues();
    
    // Configurar tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar popovers de Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-hide alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });
    
    // Confirmar acciones de eliminación
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.dataset.confirmDelete || '¿Estás seguro de que quieres eliminar este elemento?';
            Utils.confirm(message, () => {
                if (this.tagName === 'A') {
                    window.location.href = this.href;
                } else if (this.form) {
                    this.form.submit();
                }
            });
        });
    });
    
    // Manejar formularios con AJAX
    const ajaxForms = document.querySelectorAll('[data-ajax-form]');
    ajaxForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!FormHandler.validate(this)) {
                return;
            }
            
            const submitButton = this.querySelector('[type="submit"]');
            FormHandler.setButtonLoading(submitButton, true);
            
            try {
                const data = FormHandler.serialize(this);
                const response = await Ajax.post(this.action, data);
                
                if (response.success) {
                    Utils.showNotification(response.message || 'Operación completada exitosamente', 'success');
                    
                    // Redirigir si se especifica
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                } else {
                    Utils.showNotification(response.message || 'Error en la operación', 'error');
                }
            } catch (error) {
                Utils.showNotification('Error de conexión', 'error');
            } finally {
                FormHandler.setButtonLoading(submitButton, false);
            }
        });
    });
});

// Exponer objetos globalmente para uso en otras páginas
window.RepairPoint = RepairPoint;
window.Utils = Utils;
window.Ajax = Ajax;
window.FormHandler = FormHandler;
window.BrandModelHandler = BrandModelHandler;