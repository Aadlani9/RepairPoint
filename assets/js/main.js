/**
 * RepairPoint - JavaScript Principal
 * Funcionalidades generales del sistema
 * نسخة مُصححة نهائياً - بدون تعارضات
 */

// منع التحميل المتعدد
if (typeof window.RepairPointMainLoaded !== 'undefined') {
    console.log('⚠️ Main.js already loaded, skipping...');
} else {
    window.RepairPointMainLoaded = true;
    console.log('✅ RepairPoint Main JS - Clean Version 3.0 Loaded');

    // ===================================================
    // استخدام IIFE لتجنب التعارضات العامة
    // ===================================================
    (function() {
        'use strict';

        // ===================================================
        // CONFIGURACIÓN GLOBAL
        // ===================================================
        const Config = {
            baseURL: window.location.origin + '/repairpoint/',
            apiURL: window.location.origin + '/repairpoint/api/',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            language: 'es',
            dateFormat: 'dd/mm/yyyy'
        };

        // Cache para datos frecuentemente utilizados
        const DataCache = {
            brands: [],
            models: {},
            commonIssues: []
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
             * Formatear moneda
             */
            formatCurrency(amount, currency = '€') {
                if (isNaN(amount)) return `0.00 ${currency}`;
                return `${parseFloat(amount).toFixed(2)} ${currency}`;
            },

            /**
             * Validar email
             */
            isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            },

            /**
             * Generar ID único
             */
            generateID() {
                return Date.now().toString(36) + Math.random().toString(36).substr(2);
            },

            /**
             * Debounce function
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
             * Configuración base para fetch
             */
            getBaseConfig() {
                return {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': Config.csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                };
            },

            /**
             * GET request
             */
            async get(url) {
                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        ...this.getBaseConfig()
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return await response.json();
                } catch (error) {
                    console.error('Ajax GET Error:', error);
                    throw error;
                }
            },

            /**
             * POST request
             */
            async post(url, data) {
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: JSON.stringify(data),
                        ...this.getBaseConfig()
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return await response.json();
                } catch (error) {
                    console.error('Ajax POST Error:', error);
                    throw error;
                }
            }
        };

        // ===================================================
        // MANEJO DE FORMULARIOS
        // ===================================================
        const FormHandler = {
            /**
             * Validar formulario
             */
            validateForm(form) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                let firstInvalidField = null;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        this.showFieldError(field, 'Este campo es obligatorio');
                        isValid = false;
                        if (!firstInvalidField) {
                            firstInvalidField = field;
                        }
                    } else {
                        this.clearFieldError(field);
                    }
                });

                if (!isValid && firstInvalidField) {
                    firstInvalidField.focus();
                }

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
             * Envío con loading
             */
            async submitWithLoading(form, button) {
                if (!this.validateForm(form)) {
                    return false;
                }

                UI.setButtonLoading(button, true);

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showSuccess(result.message || 'Operación completada con éxito');
                        if (result.redirect) {
                            setTimeout(() => {
                                window.location.href = result.redirect;
                            }, 1500);
                        }
                    } else {
                        this.showError(result.message || 'Error al procesar la solicitud');
                    }

                    return result.success;
                } catch (error) {
                    console.error('Form submission error:', error);
                    this.showError('Error de conexión. Por favor, inténtalo de nuevo.');
                    return false;
                } finally {
                    UI.setButtonLoading(button, false);
                }
            },

            /**
             * Mostrar mensaje de éxito
             */
            showSuccess(message) {
                this.showAlert(message, 'success');
            },

            /**
             * Mostrar mensaje de error
             */
            showError(message) {
                this.showAlert(message, 'danger');
            },

            /**
             * Mostrar alerta
             */
            showAlert(message, type = 'info') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                alertDiv.innerHTML = `
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                // Insertar al inicio del contenido principal
                const mainContent = document.querySelector('.main-content, .container, body');
                if (mainContent) {
                    mainContent.insertBefore(alertDiv, mainContent.firstChild);
                }

                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
        };

        // ===================================================
        // MANEJO DE UI
        // ===================================================
        const UI = {
            /**
             * Mostrar/ocultar loading en botón
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
            },

            /**
             * Mostrar modal de confirmación
             */
            confirm(message, onConfirm, onCancel = null) {
                const modalHTML = `
                    <div class="modal fade" id="confirmModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmar Acción</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>${message}</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-danger" id="confirmBtn">Confirmar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.body.insertAdjacentHTML('beforeend', modalHTML);
                const modal = new bootstrap.Modal(document.getElementById('confirmModal'));

                document.getElementById('confirmBtn').addEventListener('click', () => {
                    modal.hide();
                    if (onConfirm) onConfirm();
                });

                modal.show();

                // Limpiar después de cerrar
                document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {
                    document.getElementById('confirmModal').remove();
                    if (onCancel) onCancel();
                });
            }
        };

        // ===================================================
        // BÚSQUEDA EN TABLAS
        // ===================================================
        const TableSearch = {
            /**
             * Inicializar búsqueda en tabla
             */
            init(searchInput, table) {
                if (!searchInput || !table) return;

                const debouncedSearch = Utils.debounce((term) => {
                    this.performSearch(term, table);
                }, 300);

                searchInput.addEventListener('input', (e) => {
                    debouncedSearch(e.target.value);
                });
            },

            /**
             * Realizar búsqueda
             */
            performSearch(searchTerm, table) {
                const rows = table.querySelectorAll('tbody tr');
                const term = searchTerm.toLowerCase().trim();
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const isMatch = !term || text.includes(term);

                    row.style.display = isMatch ? '' : 'none';

                    if (isMatch) {
                        visibleCount++;
                        row.classList.add('search-highlight');
                    } else {
                        row.classList.remove('search-highlight');
                    }
                });

                this.updateSearchCounter(table, visibleCount, rows.length);
            },

            /**
             * Actualizar contador de búsqueda
             */
            updateSearchCounter(table, visible, total) {
                let counter = table.parentNode.querySelector('.search-counter');
                if (!counter) {
                    counter = document.createElement('div');
                    counter.className = 'search-counter text-muted small mt-2';
                    table.parentNode.appendChild(counter);
                }

                if (visible === total) {
                    counter.textContent = `${total} resultado(s)`;
                } else {
                    counter.textContent = `${visible} de ${total} resultado(s)`;
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
                    if (DataCache.models[brandId]) {
                        this.populateModels(modelSelect, DataCache.models[brandId]);
                        return;
                    }

                    const response = await Ajax.get(`${Config.apiURL}models.php?brand_id=${brandId}`);

                    if (response.success) {
                        DataCache.models[brandId] = response.data;
                        this.populateModels(modelSelect, response.data);
                    } else {
                        modelSelect.innerHTML = '<option value="">Error cargando modelos</option>';
                    }
                } catch (error) {
                    console.error('Error loading models:', error);
                    modelSelect.innerHTML = '<option value="">Error cargando modelos</option>';
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

                modelSelect.disabled = false;
            }
        };

        // ===================================================
        // INICIALIZACIÓN AUTOMÁTICA
        // ===================================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 RepairPoint Main - DOM Ready');

            // Auto-inicializar búsquedas en tablas
            const searchInputs = document.querySelectorAll('[data-table-search]');
            searchInputs.forEach(input => {
                const tableId = input.getAttribute('data-table-search');
                const table = document.getElementById(tableId);
                if (table) {
                    TableSearch.init(input, table);
                }
            });

            // Auto-inicializar brand-model selects
            const brandSelects = document.querySelectorAll('[data-load-models]');
            brandSelects.forEach(brandSelect => {
                const modelSelectId = brandSelect.getAttribute('data-load-models');
                const modelSelect = document.getElementById(modelSelectId);

                if (modelSelect) {
                    brandSelect.addEventListener('change', (e) => {
                        BrandModelHandler.loadModels(e.target.value, modelSelect);
                    });
                }
            });

            // Auto-validación de formularios
            const formsWithValidation = document.querySelectorAll('form[data-validate]');
            formsWithValidation.forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!FormHandler.validateForm(form)) {
                        e.preventDefault();
                    }
                });
            });

            console.log('✅ RepairPoint Main - All initialized');
        });

        // ===================================================
        // EXPOSICIÓN GLOBAL SEGURA
        // ===================================================
        window.RepairPoint = {
            Utils,
            Ajax,
            FormHandler,
            UI,
            TableSearch,
            BrandModelHandler,
            version: '3.0'
        };

    })(); // End IIFE

} // End if not loaded