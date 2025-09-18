/**
 * Universal Log Pagination JavaScript (CSP-kompatibel)
 * Datei: public/js/utils/logs-universal.js
 * 
 * Ersetzt alle spezifischen Log-JavaScript-Dateien:
 * - logsAudit.latte.js
 * - logsDb.latte.js  
 * - logsError.latte.js
 * - logsMail.latte.js
 * - logsRequest.latte.js
 * - logsSecurity.js
 * - logsSystem.latte.js
 */

// Globale Variablen
let currentPage = 1;
let messages = {};
let config = {};

// Beim DOM-Load initialisieren
document.addEventListener('DOMContentLoaded', function() {
    initializeLogPage();
});

function initializeLogPage() {
    // Messages aus Data-Attributen laden
    loadMessages();
    
    // Config aus Data-Attributen laden
    loadConfig();
    
    // Event Listeners einrichten
    setupEventListeners();
    
    // Auto-Fetch wenn konfiguriert
    if (config.autoFetch) {
        fetchLogs();
    }
}

function loadMessages() {
    const messageContainer = document.getElementById('log-messages');
    if (messageContainer) {
        messages = {
            msg4: messageContainer.dataset.msg4 || 'Page',
            msg5: messageContainer.dataset.msg5 || 'of', 
            msg6: messageContainer.dataset.msg6 || 'Error loading logs'
        };
    } else {
        // Fallback Messages
        messages = {
            msg4: 'Page',
            msg5: 'of',
            msg6: 'Error loading logs'
        };
    }
}

function loadConfig() {
    const configContainer = document.getElementById('log-config');
    if (configContainer) {
        config = {
            type: configContainer.dataset.type || 'unknown',
            fetchUrl: configContainer.dataset.fetchUrl || '/admin/logs/fetch',
            autoFetch: configContainer.dataset.autoFetch === 'true'
        };
    } else {
        console.warn('Log config container not found - using defaults');
        config = {
            type: 'unknown',
            fetchUrl: '/admin/logs/fetch',
            autoFetch: true
        };
    }
}

function setupEventListeners() {
    // Event Listeners für Search und PageSize
    const searchElement = document.getElementById('search');
    const pageSizeElement = document.getElementById('pageSize');
    
    if (searchElement) {
        searchElement.addEventListener('input', debounce(fetchLogs, 300));
    }
    
    if (pageSizeElement) {
        pageSizeElement.addEventListener('change', function() {
            currentPage = 1; // Reset to first page when changing page size
            fetchLogs();
        });
    }

    // CSP-kompatible Event-Delegation für Pagination
    setupPaginationEventDelegation();
}

function setupPaginationEventDelegation() {
    const paginationContainer = document.getElementById('pagination');
    if (paginationContainer) {
        paginationContainer.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Nur auf Links reagieren, die eine data-page haben
            if (e.target.tagName === 'A' && e.target.hasAttribute('data-page')) {
                const page = parseInt(e.target.getAttribute('data-page'));
                if (!isNaN(page) && page > 0) {
                    goToPage(page);
                }
            }
        });
    }
}

function fetchLogs() {
    const searchValue = document.getElementById('search')?.value || '';
    const pageSizeValue = document.getElementById('pageSize')?.value || 10;
    const loadingSpinner = document.getElementById('loadingSpinner');
    const logTableBody = document.getElementById('logTableBody');
    const logCardsBody = document.getElementById('logCardsBody');
    
    if (!config.fetchUrl) {
        console.error('Fetch URL not configured');
        showError('Configuration error: Fetch URL not found');
        return;
    }

    // Loading anzeigen
    showLoading(true);
    
    // Tabelle/Cards leeren
    if (logTableBody) logTableBody.innerHTML = '';
    if (logCardsBody) logCardsBody.innerHTML = '';

    // API Call mit korrekter URL
    const url = `${config.fetchUrl}?search=${encodeURIComponent(searchValue)}&pageSize=${pageSizeValue}&page=${currentPage}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.logs && Array.isArray(data.logs)) {
                populateTable(data.logs);
                populateCards(data.logs);
                updatePagination(data.page || 1, data.totalPages || 1);
            } else {
                showError('Invalid data format received from server');
            }
        })
        .catch(error => {
            console.error('Error fetching logs:', error);
            showError(messages.msg6 || 'Error loading logs');
        })
        .finally(() => {
            showLoading(false);
        });
}

function populateTable(logs) {
    const logTableBody = document.getElementById('logTableBody');
    if (!logTableBody) return;
    
    logTableBody.innerHTML = '';
    
    if (logs.length === 0) {
        logTableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-info-circle"></i> No logs found
                </td>
            </tr>
        `;
        return;
    }
    
    logs.forEach(log => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(log.id || '')}</td>
            <td><span class="badge bg-secondary">${escapeHtml(log.type || '')}</span></td>
            <td>${escapeHtml(log.datum_zeit || '')}</td>
            <td>${escapeHtml(log.user || '')}</td>
            <td>${escapeHtml(log.context || '')}</td>
            <td>${escapeHtml(log.message || '')}</td>
        `;
        logTableBody.appendChild(row);
    });
}

function populateCards(logs) {
    const logCardsBody = document.getElementById('logCardsBody');
    if (!logCardsBody) return;
    
    logCardsBody.innerHTML = '';
    
    if (logs.length === 0) {
        logCardsBody.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No logs found
            </div>
        `;
        return;
    }
    
    logs.forEach(log => {
        const card = document.createElement('div');
        card.className = 'card mb-2 p-2';
        card.innerHTML = `
            <div><strong>#${escapeHtml(log.id || '')}</strong></div>
            <div><strong>Type:</strong> <span class="badge bg-secondary">${escapeHtml(log.type || '')}</span></div>
            <div><strong>Date:</strong> ${escapeHtml(log.datum_zeit || '')}</div>
            <div><strong>User:</strong> ${escapeHtml(log.user || '')}</div>
            <div><strong>Context:</strong> ${escapeHtml(log.context || '')}</div>
            <div><strong>Message:</strong> ${escapeHtml(log.message || '')}</div>
        `;
        logCardsBody.appendChild(card);
    });
}

function updatePagination(current, total) {
    const pagination = document.getElementById('pagination');
    const paginationInfo = document.getElementById('paginationInfo');
    
    if (!pagination) return;
    
    // Clear existing pagination
    pagination.innerHTML = '';
    
    // Update info
    if (paginationInfo) {
        paginationInfo.textContent = `${messages.msg4} ${current} ${messages.msg5} ${total}`;
    }
    
    // If only one page, don't show pagination
    if (total <= 1) return;

    const maxVisiblePages = 5;
    const startPage = Math.max(1, current - Math.floor(maxVisiblePages / 2));
    const endPage = Math.min(total, startPage + maxVisiblePages - 1);

    // "Previous" Button
    if (current > 1) {
        const prevLi = document.createElement('li');
        prevLi.className = 'page-item';
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current - 1}" title="Previous Page">&laquo;</a>`;
        pagination.appendChild(prevLi);
    }

    // First page if not in visible range
    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
        pagination.appendChild(firstLi);
        
        // Ellipsis if gap
        if (startPage > 2) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsisLi);
        }
    }

    // Visible page numbers
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === current ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        pagination.appendChild(li);
    }

    // Last page if not in visible range
    if (endPage < total) {
        // Ellipsis if gap
        if (endPage < total - 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(ellipsisLi);
        }
        
        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="#" data-page="${total}">${total}</a>`;
        pagination.appendChild(lastLi);
    }

    // "Next" Button
    if (current < total) {
        const nextLi = document.createElement('li');
        nextLi.className = 'page-item';
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${current + 1}" title="Next Page">&raquo;</a>`;
        pagination.appendChild(nextLi);
    }
}

function goToPage(page) {
    if (page && page > 0) {
        currentPage = page;
        fetchLogs();
        
        // Scroll to top for better UX
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function showLoading(show) {
    const loadingSpinner = document.getElementById('loadingSpinner');
    if (loadingSpinner) {
        loadingSpinner.style.display = show ? 'block' : 'none';
    }
}

function showError(message) {
    const logTableBody = document.getElementById('logTableBody');
    const logCardsBody = document.getElementById('logCardsBody');
    
    if (logTableBody) {
        logTableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle"></i> ${escapeHtml(message)}
                </td>
            </tr>
        `;
    }
    
    if (logCardsBody) {
        logCardsBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> ${escapeHtml(message)}
            </div>
        `;
    }
}

// Utility Functions

function escapeHtml(text) {
    if (typeof text !== 'string') return text || '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
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

// Export functions for backwards compatibility
window.goToPage = goToPage;
window.fetchLogs = fetchLogs;