/**
 * نظام التملؤ التلقائي للعملاء
 * يتم إضافته لـ add_repair.php
 */

// متغيرات للتحكم في البحث
let searchTimeout;
let currentSuggestions = [];
let selectedSuggestionIndex = -1;

// إعداد نظام التملؤ التلقائي
function setupCustomerAutocomplete() {
    console.log('🔍 Setting up customer autocomplete...');

    const nameInput = document.getElementById('customer_name');
    const phoneInput = document.getElementById('customer_phone');

    if (nameInput) {
        setupAutocompleteForInput(nameInput, 'name');
    }

    if (phoneInput) {
        setupAutocompleteForInput(phoneInput, 'phone');
    }
}

/**
 * إعداد التملؤ التلقائي لحقل معين
 */
function setupAutocompleteForInput(input, type) {
    // إنشاء container للاقتراحات
    const suggestionsContainer = createSuggestionsContainer(input);

    // معالج الكتابة
    input.addEventListener('input', function(e) {
        const value = e.target.value.trim();

        // إخفاء الاقتراحات إذا كان النص قصير
        if (value.length < 3) {
            hideSuggestions(suggestionsContainer);
            return;
        }

        // تأخير البحث لتجنب الطلبات المتكررة
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchCustomers(value, suggestionsContainer, input, type);
        }, 300);
    });

    // معالج لوحة المفاتيح
    input.addEventListener('keydown', function(e) {
        handleKeyboardNavigation(e, suggestionsContainer, input);
    });

    // إخفاء الاقتراحات عند النقر خارجاً
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            hideSuggestions(suggestionsContainer);
        }
    });
}

/**
 * إنشاء container للاقتراحات
 */
function createSuggestionsContainer(input) {
    const container = document.createElement('div');
    container.className = 'customer-suggestions';
    container.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    `;

    // إضافة Container بعد الـ input
    const wrapper = input.parentNode;
    wrapper.style.position = 'relative';
    wrapper.appendChild(container);

    return container;
}

/**
 * البحث عن العملاء
 */
async function searchCustomers(searchTerm, container, input, type) {
    console.log('🔍 Searching customers for:', searchTerm);

    try {
        // إظهار مؤشر التحميل
        showLoadingInSuggestions(container);

        // طلب البحث
        const response = await fetch(`<?= url('api/customer_search.php') ?>?search=${encodeURIComponent(searchTerm)}&limit=8`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('📊 Search results:', data);

        if (data.success && data.data && data.data.length > 0) {
            currentSuggestions = data.data;
            showSuggestions(container, data.data, input, type);
        } else {
            showNoResults(container);
        }

    } catch (error) {
        console.error('❌ Error searching customers:', error);
        showErrorInSuggestions(container);
    }
}

/**
 * عرض الاقتراحات
 */
function showSuggestions(container, customers, input, type) {
    let html = '';

    customers.forEach((customer, index) => {
        const isFrequent = customer.customer_type === 'frequent';
        const frequentBadge = isFrequent ? '<span class="badge bg-primary ms-2">Cliente frecuente</span>' : '';

        html += `
            <div class="suggestion-item" data-index="${index}" data-customer='${JSON.stringify(customer)}'>
                <div class="d-flex justify-content-between align-items-start p-3 border-bottom">
                    <div class="customer-info flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-person-circle text-primary me-2"></i>
                            <strong class="customer-name">${escapeHtml(customer.name)}</strong>
                            ${frequentBadge}
                        </div>
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-telephone text-muted me-2"></i>
                            <span class="customer-phone">${escapeHtml(customer.phone_formatted)}</span>
                        </div>
                        ${customer.recent_devices ? `
                            <div class="d-flex align-items-center">
                                <i class="bi bi-phone text-muted me-2"></i>
                                <small class="text-muted">${escapeHtml(customer.recent_devices)}</small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="customer-stats text-end">
                        <small class="text-muted d-block">
                            <i class="bi bi-tools me-1"></i>${customer.total_repairs} reparaciones
                        </small>
                        <small class="text-muted d-block">
                            <i class="bi bi-calendar me-1"></i>${customer.last_repair_formatted}
                        </small>
                        <small class="text-success d-block">
                            <i class="bi bi-cash me-1"></i>${customer.total_spent_formatted}
                        </small>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    container.style.display = 'block';

    // إضافة event listeners للاقتراحات
    container.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', function() {
            const customerData = JSON.parse(this.getAttribute('data-customer'));
            selectCustomer(customerData, input, container);
        });

        item.addEventListener('mouseenter', function() {
            // إزالة التحديد السابق
            container.querySelectorAll('.suggestion-item').forEach(i => i.classList.remove('active'));
            // إضافة التحديد الحالي
            this.classList.add('active');
            selectedSuggestionIndex = parseInt(this.getAttribute('data-index'));
        });
    });
}

/**
 * تحديد عميل من الاقتراحات
 */
function selectCustomer(customer, input, container) {
    console.log('✅ Customer selected:', customer);

    // ملء الحقول
    const nameInput = document.getElementById('customer_name');
    const phoneInput = document.getElementById('customer_phone');

    if (nameInput) {
        nameInput.value = customer.name;
        nameInput.classList.add('is-valid');
    }

    if (phoneInput) {
        phoneInput.value = customer.phone_formatted;
        phoneInput.classList.add('is-valid');
    }

    // إخفاء الاقتراحات
    hideSuggestions(container);

    // إظهار معلومات العميل
    showCustomerInfo(customer);

    // التركيز على الحقل التالي (وصف المشكلة)
    const issueField = document.getElementById('issue_description');
    if (issueField) {
        issueField.focus();
    }
}

/**
 * إظهار معلومات العميل
 */
function showCustomerInfo(customer) {
    // البحث عن مكان لإظهار معلومات العميل
    let infoContainer = document.getElementById('customer-info-display');

    if (!infoContainer) {
        // إنشاء container جديد
        infoContainer = document.createElement('div');
        infoContainer.id = 'customer-info-display';
        infoContainer.className = 'alert alert-info fade show mt-3';

        // إضافته بعد حقل الهاتف
        const phoneInput = document.getElementById('customer_phone');
        if (phoneInput && phoneInput.parentNode) {
            phoneInput.parentNode.insertBefore(infoContainer, phoneInput.nextSibling);
        }
    }

    infoContainer.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">
                    <i class="bi bi-person-check text-success me-2"></i>
                    Cliente encontrado
                </h6>
                <div class="d-flex align-items-center gap-3">
                    <small><i class="bi bi-tools me-1"></i>${customer.total_repairs} reparaciones</small>
                    <small><i class="bi bi-calendar me-1"></i>Última: ${customer.last_repair_formatted}</small>
                    <small><i class="bi bi-cash me-1"></i>Total: ${customer.total_spent_formatted}</small>
                </div>
            </div>
            <button type="button" class="btn-close" onclick="hideCustomerInfo()"></button>
        </div>
    `;

    // Auto-hide بعد 10 ثواني
    setTimeout(() => {
        hideCustomerInfo();
    }, 10000);
}

/**
 * إخفاء معلومات العميل
 */
function hideCustomerInfo() {
    const infoContainer = document.getElementById('customer-info-display');
    if (infoContainer) {
        infoContainer.remove();
    }
}

/**
 * معالجة التنقل بلوحة المفاتيح
 */
function handleKeyboardNavigation(e, container, input) {
    const suggestions = container.querySelectorAll('.suggestion-item');

    if (suggestions.length === 0) return;

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            selectedSuggestionIndex = Math.min(selectedSuggestionIndex + 1, suggestions.length - 1);
            updateSelectedSuggestion(suggestions);
            break;

        case 'ArrowUp':
            e.preventDefault();
            selectedSuggestionIndex = Math.max(selectedSuggestionIndex - 1, 0);
            updateSelectedSuggestion(suggestions);
            break;

        case 'Enter':
            e.preventDefault();
            if (selectedSuggestionIndex >= 0 && suggestions[selectedSuggestionIndex]) {
                const customerData = JSON.parse(suggestions[selectedSuggestionIndex].getAttribute('data-customer'));
                selectCustomer(customerData, input, container);
            }
            break;

        case 'Escape':
            hideSuggestions(container);
            break;
    }
}

/**
 * تحديث الاقتراح المحدد
 */
function updateSelectedSuggestion(suggestions) {
    suggestions.forEach((item, index) => {
        if (index === selectedSuggestionIndex) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
}

/**
 * إظهار مؤشر التحميل
 */
function showLoadingInSuggestions(container) {
    container.innerHTML = `
        <div class="p-3 text-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            Buscando clientes...
        </div>
    `;
    container.style.display = 'block';
}

/**
 * إظهار رسالة عدم وجود نتائج
 */
function showNoResults(container) {
    container.innerHTML = `
        <div class="p-3 text-center text-muted">
            <i class="bi bi-search mb-2"></i>
            <div>No se encontraron clientes</div>
            <small>Intenta con otro término de búsqueda</small>
        </div>
    `;
    container.style.display = 'block';
}

/**
 * إظهار رسالة خطأ
 */
function showErrorInSuggestions(container) {
    container.innerHTML = `
        <div class="p-3 text-center text-danger">
            <i class="bi bi-exclamation-triangle mb-2"></i>
            <div>Error al buscar clientes</div>
            <small>Inténtalo de nuevo</small>
        </div>
    `;
    container.style.display = 'block';
}

/**
 * إخفاء الاقتراحات
 */
function hideSuggestions(container) {
    container.style.display = 'none';
    selectedSuggestionIndex = -1;
}

/**
 * تأمين النص من XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// إضافة الـ CSS للاقتراحات
document.addEventListener('DOMContentLoaded', function() {
    // إضافة CSS للاقتراحات
    const style = document.createElement('style');
    style.textContent = `
        .customer-suggestions {
            font-size: 0.9rem;
        }
        
        .suggestion-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .suggestion-item:hover,
        .suggestion-item.active {
            background-color: #f8f9fa;
        }
        
        .suggestion-item:last-child .border-bottom {
            border-bottom: none !important;
        }
        
        .customer-info .customer-name {
            color: #495057;
        }
        
        .customer-info .customer-phone {
            color: #6c757d;
        }
        
        .customer-stats {
            min-width: 120px;
        }
        
        .badge {
            font-size: 0.7rem;
        }
        
        #customer-info-display {
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

    // إعداد نظام التملؤ التلقائي
    setupCustomerAutocomplete();
});