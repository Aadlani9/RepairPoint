/**
 * RepairPoint - Utilities JavaScript Básicas
 * للاستخدام في الصفحات التي لا تحتوي على main.js
 */

// إنشاء Utils object بسيط إذا لم يكن موجوداً
if (typeof Utils === 'undefined') {
    window.Utils = {
        /**
         * عرض notification بسيطة
         */
        showNotification: function(message, type = 'info', duration = 5000) {
            // إنشاء element للـ notification
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

            // إزالة تلقائية بعد المدة المحددة
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
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
        }
    };
}

// إضافة animation للـ fade-in
document.addEventListener('DOMContentLoaded', function() {
    // إضافة CSS للـ animations إذا لم يكن موجوداً
    if (!document.querySelector('#fade-in-css')) {
        const style = document.createElement('style');
        style.id = 'fade-in-css';
        style.textContent = `
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
            
            .fade-in {
                animation: fadeIn 0.8s ease-out forwards;
                opacity: 0;
            }
        `;
        document.head.appendChild(style);
    }

    // تطبيق animation على العناصر
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach((element, index) => {
        setTimeout(() => {
            element.style.opacity = '1';
        }, index * 200);
    });
});