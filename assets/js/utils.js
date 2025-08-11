/**
 * RepairPoint - Utilities JavaScript المحسن
 * للاستخدام في صفحات التعديل والإصلاح
 */

// إنشاء Utils object محسن
if (typeof Utils === 'undefined') {
    window.Utils = {
        /**
         * عرض notification محسن مع أنواع مختلفة
         */
        showNotification: function(message, type = 'info', duration = 5000) {
            const alertDiv = document.createElement('div');
            const iconMap = {
                'success': 'bi-check-circle',
                'error': 'bi-exclamation-triangle',
                'danger': 'bi-exclamation-triangle',
                'warning': 'bi-exclamation-triangle',
                'info': 'bi-info-circle'
            };

            const colorMap = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'danger': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            };

            alertDiv.className = `alert ${colorMap[type] || 'alert-info'} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';

            alertDiv.innerHTML = `
                <i class="bi ${iconMap[type] || 'bi-info-circle'} me-2"></i>
                ${this.escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            // إضافة animation
            setTimeout(() => {
                alertDiv.classList.add('show');
            }, 100);

            // إزالة تلقائية
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 300);
                }
            }, duration);
        },

        /**
         * تأمين النص من XSS
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * تأكيد حذف أو تعديل
         */
        confirmAction: function(message, onConfirm, onCancel) {
            const confirmResult = confirm(message);
            if (confirmResult && onConfirm) {
                onConfirm();
            } else if (!confirmResult && onCancel) {
                onCancel();
            }
            return confirmResult;
        },

        /**
         * إظهار loader
         */
        showLoader: function(element, text = 'Cargando...') {
            if (element) {
                element.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </span>
                    ${text}
                `;
                element.disabled = true;
            }
        },

        /**
         * إخفاء loader
         */
        hideLoader: function(element, originalText = '') {
            if (element) {
                element.innerHTML = originalText;
                element.disabled = false;
            }
        },

        /**
         * تنسيق رقم الهاتف
         */
        formatPhoneNumber: function(phone) {
            if (!phone) return '';

            // إزالة كل شيء ما عدا الأرقام
            const clean = phone.replace(/\D/g, '');

            // إذا كان يبدأ بـ 34، إزالته
            let number = clean.startsWith('34') ? clean.substring(2) : clean;

            // تنسيق الرقم
            if (number.length === 9) {
                return `+34 ${number.substring(0, 3)} ${number.substring(3, 6)} ${number.substring(6)}`;
            }

            return phone;
        },

        /**
         * تحقق من صحة رقم الهاتف الإسباني
         */
        isValidSpanishPhone: function(phone) {
            if (!phone) return false;

            const clean = phone.replace(/[\s\-\.\(\)]/g, '');
            const patterns = [
                /^\+34[6789]\d{8}$/,    // +34xxxxxxxxx
                /^0034[6789]\d{8}$/,    // 0034xxxxxxxxx
                /^34[6789]\d{8}$/,      // 34xxxxxxxxx
                /^[6789]\d{8}$/,        // xxxxxxxxx
            ];

            return patterns.some(pattern => pattern.test(clean));
        },

        /**
         * تحديث عداد الأحرف
         */
        updateCharCounter: function(textarea, counter, maxLength) {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${remaining} caracteres restantes`;

            if (remaining < 50) {
                counter.className = 'text-warning';
            } else if (remaining < 10) {
                counter.className = 'text-danger';
            } else {
                counter.className = 'text-muted';
            }
        },

        /**
         * تحديث الوقت بشكل ديناميكي
         */
        updateTimeAgo: function(element, timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = now - time;

            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);

            let timeAgo = '';
            if (days > 0) {
                timeAgo = `hace ${days} día${days > 1 ? 's' : ''}`;
            } else if (hours > 0) {
                timeAgo = `hace ${hours} hora${hours > 1 ? 's' : ''}`;
            } else if (minutes > 0) {
                timeAgo = `hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
            } else {
                timeAgo = 'hace un momento';
            }

            element.textContent = timeAgo;
        },

        /**
         * تحميل صورة مع معاينة
         */
        previewImage: function(input, previewElement) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                };

                reader.readAsDataURL(input.files[0]);
            }
        },

        /**
         * نسخ النص إلى الحافظة
         */
        copyToClipboard: function(text, successCallback, errorCallback) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(
                    function() {
                        if (successCallback) successCallback();
                        Utils.showNotification('Texto copiado al portapapeles', 'success', 2000);
                    },
                    function(err) {
                        if (errorCallback) errorCallback(err);
                        Utils.showNotification('Error al copiar texto', 'error', 3000);
                    }
                );
            } else {
                // Fallback للمتصفحات القديمة
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();

                try {
                    document.execCommand('copy');
                    if (successCallback) successCallback();
                    Utils.showNotification('Texto copiado al portapapeles', 'success', 2000);
                } catch (err) {
                    if (errorCallback) errorCallback(err);
                    Utils.showNotification('Error al copiar texto', 'error', 3000);
                }

                document.body.removeChild(textArea);
            }
        },

        /**
         * تحديث badge العدد
         */
        updateBadge: function(badgeElement, count) {
            if (count > 0) {
                badgeElement.textContent = count > 99 ? '99+' : count;
                badgeElement.style.display = 'inline';
            } else {
                badgeElement.style.display = 'none';
            }
        }
    };
}

// إضافة CSS للـ animations المحسن
document.addEventListener('DOMContentLoaded', function() {
    if (!document.querySelector('#enhanced-animations-css')) {
        const style = document.createElement('style');
        style.id = 'enhanced-animations-css';
        style.textContent = `
            @keyframes fadeInRight {
                from {
                    opacity: 0;
                    transform: translateX(30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            @keyframes fadeOutRight {
                from {
                    opacity: 1;
                    transform: translateX(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(30px);
                }
            }
            
            .alert.position-fixed {
                animation: fadeInRight 0.5s ease-out;
            }
            
            .alert.position-fixed.fade:not(.show) {
                animation: fadeOutRight 0.3s ease-in;
            }
            
            .fade-in-up {
                animation: fadeInUp 0.8s ease-out forwards;
                opacity: 0;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .changed {
                border-left: 4px solid #ffc107 !important;
                background-color: rgba(255, 193, 7, 0.05) !important;
                transition: all 0.3s ease;
            }
            
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            }
            
            .spinner-border-lg {
                width: 3rem;
                height: 3rem;
            }
        `;
        document.head.appendChild(style);
    }

    // تطبيق fade-in على العناصر الموجودة
    const fadeElements = document.querySelectorAll('.fade-in-up');
    fadeElements.forEach((element, index) => {
        setTimeout(() => {
            element.style.opacity = '1';
        }, index * 100);
    });

    // إضافة معالجة لـ loading states
    const buttons = document.querySelectorAll('button[type="submit"]');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('form');
            if (form && form.checkValidity()) {
                Utils.showLoader(this, 'Procesando...');
            }
        });
    });
});