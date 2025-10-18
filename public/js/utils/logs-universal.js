/**
 * Universal Log Viewer
 * CSP-Compliant Version - NO inline styles
 */

// Global State
let currentPage = 1;
let totalPages = 1;
let itemsPerPage = 10;
let searchQuery = "";

// Configuration from data attributes
const configEl = document.getElementById("log-config");
const logType = configEl?.dataset.type || "system";
const fetchUrl = configEl?.dataset.fetchUrl || "/admin/logs/fetch";
const autoFetch = configEl?.dataset.autoFetch === "true";

// Messages from data attributes
const messagesEl = document.getElementById("log-messages");
const MSG_PAGE = messagesEl?.dataset.msg4 || "Page";
const MSG_OF = messagesEl?.dataset.msg5 || "of";
const MSG_ERROR = messagesEl?.dataset.msg6 || "Error loading logs";

/**
 * Initialize on page load
 */
document.addEventListener("DOMContentLoaded", function () {
  setupEventListeners();
  
  if (autoFetch) {
    fetchLogs();
  }
});

/**
 * Setup all event listeners
 */
function setupEventListeners() {
  // Pagination clicks
  document.addEventListener("click", function (e) {
    if (e.target.matches(".page-link, .page-link *")) {
      e.preventDefault();
      const link = e.target.closest(".page-link");
      const page = parseInt(link?.dataset.page);
      if (page) goToPage(page);
    }
  });

  // Items per page change
  const itemsPerPageSelect = document.getElementById("pageSize");
  if (itemsPerPageSelect) {
    itemsPerPageSelect.addEventListener("change", function () {
      itemsPerPage = parseInt(this.value);
      currentPage = 1;
      fetchLogs();
    });
  }

  // Search
  const searchInput = document.getElementById("search");
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener("input", function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        searchQuery = this.value.trim();
        currentPage = 1;
        fetchLogs();
      }, 500);
    });
  }

  // Online/Offline handling
  window.addEventListener("online", () => {
    console.log("Connection restored. Refreshing logs...");
    setTimeout(fetchLogs, 500);
  });

  window.addEventListener("offline", () => {
    showError("You appear to be offline. Please check your internet connection.", "network");
  });
}

/**
 * Fetch logs from server
 */
function fetchLogs() {
  showLoading(true);

  const params = new URLSearchParams({
    page: currentPage,
    itemsPerPage: itemsPerPage,
    search: searchQuery,
  });

  fetch(`${fetchUrl}?${params}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      showLoading(false);
      
      if (data) {
        populateTable(data.logs || []);
        populateCards(data.logs || []);
        totalPages = data.totalPages || 1;
        updatePagination();
      } else {
        showError(data.message || MSG_ERROR);
      }
    })
    .catch((error) => {
      showLoading(false);
      console.error("Error fetching logs:", error);
      showError(MSG_ERROR, "network");
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Populate table view (desktop)
 */
function populateTable(logs) {
  const logTableBody = document.getElementById("logTableBody");
  if (!logTableBody) return;

  logTableBody.innerHTML = "";

  if (logs.length === 0) {
    logTableBody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center py-5">
          <div class="no-logs-message">
            <i class="bi bi-info-circle icon-3rem text-muted"></i>
            <p class="fw-bold mt-3">No logs found</p>
            <p class="text-muted small mb-0">Try adjusting your search criteria</p>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  logs.forEach((log) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${escapeHtml(log.id || "")}</td>
      <td><span class="badge bg-secondary">${escapeHtml(log.type || "")}</span></td>
      <td>${escapeHtml(log.datum_zeit || "")}</td>
      <td>${escapeHtml(log.user || "")}</td>
      <td>${escapeHtml(log.context || "")}</td>
      <td>${escapeHtml(log.message || "")}</td>
    `;
    logTableBody.appendChild(row);
  });
}

/**
 * Enhanced mobile card layout with touch-optimized design
 */
function populateCards(logs) {
  const logCardsBody = document.getElementById('logCardsBody');
  if (!logCardsBody) return;
  
  logCardsBody.innerHTML = '';
  
  if (logs.length === 0) {
    logCardsBody.innerHTML = `
      <div class="alert alert-info text-center py-4">
        <i class="bi bi-info-circle icon-2rem d-block mb-2"></i>
        <strong>No logs found</strong>
        <p class="mb-0 text-muted small">Try adjusting your search criteria</p>
      </div>
    `;
    return;
  }
  
  logs.forEach((log, index) => {
    const card = createMobileLogCard(log, index);
    logCardsBody.appendChild(card);
  });
}

/**
 * Create touch-optimized log card for mobile devices
 */
function createMobileLogCard(log, index) {
  const card = document.createElement('div');
  const cardId = `log-card-${index}`;
  
  // Status-specific styling via CSS classes
  const logTypeClasses = {
    'AUDIT': 'log-card-audit',
    'SECURITY': 'log-card-security',
    'ERROR': 'log-card-error',
    'SYSTEM': 'log-card-system',
    'SQL': 'log-card-sql',
    'MAILSCHEDULER': 'log-card-mail',
    'REQUEST': 'log-card-request'
  };
  
  const logType = (log.type || '').toUpperCase();
  const cardClass = logTypeClasses[logType] || 'log-card-system';
  
  // Truncate long messages for preview
  const message = log.message || '';
  const isLongMessage = message.length > 100;
  const shortMessage = isLongMessage ? message.substring(0, 100) + '...' : message;
  
  // Format date for mobile
  const date = formatMobileDate(log.datum_zeit);
  
  card.className = `card mb-3 shadow-sm log-mobile-card ${cardClass}`;
  
  card.innerHTML = `
    <div class="card-body p-3">
      <!-- Header Row -->
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="d-flex align-items-center">
          <span class="badge log-badge-${logType.toLowerCase()} badge-lg me-2 px-2 py-1">
            ${escapeHtml(log.type || 'UNKNOWN')}
          </span>
          <small class="text-muted fw-bold">#${escapeHtml(log.id || '')}</small>
        </div>
        <div class="text-end">
          <div class="text-muted small">${date.time}</div>
          <div class="text-muted x-small">${date.date}</div>
        </div>
      </div>
      
      <!-- User & Context Row -->
      <div class="row g-2 mb-2">
        <div class="col-6">
          <div class="text-muted x-small">User</div>
          <div class="fw-medium small text-truncate" title="${escapeHtml(log.user || 'N/A')}">
            <i class="bi bi-person-circle me-1"></i>${escapeHtml(log.user || 'System')}
          </div>
        </div>
        <div class="col-6">
          <div class="text-muted x-small">Context</div>
          <div class="fw-medium small text-truncate" title="${escapeHtml(log.context || 'N/A')}">
            <i class="bi bi-gear-wide me-1"></i>${escapeHtml(log.context || 'General')}
          </div>
        </div>
      </div>
      
      <!-- Message Section -->
      <div class="message-section">
        <div class="text-muted x-small mb-1">Message</div>
        <div class="message-content small">
          <span class="message-preview">${escapeHtml(shortMessage)}</span>
          ${isLongMessage ? `
            <a href="#" class="text-primary small ms-1 toggle-message" data-card-id="${cardId}">
              Show more <i class="bi bi-chevron-down"></i>
            </a>
            <span class="message-full d-none">${escapeHtml(message)}</span>
          ` : ''}
        </div>
      </div>
    </div>
  `;
  
  // Add toggle event listener
  if (isLongMessage) {
    setTimeout(() => {
      const toggleLink = card.querySelector('.toggle-message');
      if (toggleLink) {
        toggleLink.addEventListener('click', function(e) {
          e.preventDefault();
          const preview = card.querySelector('.message-preview');
          const full = card.querySelector('.message-full');
          const icon = this.querySelector('i');
          
          if (full.classList.contains('d-none')) {
            preview.classList.add('d-none');
            full.classList.remove('d-none');
            this.innerHTML = 'Show less <i class="bi bi-chevron-up"></i>';
          } else {
            preview.classList.remove('d-none');
            full.classList.add('d-none');
            this.innerHTML = 'Show more <i class="bi bi-chevron-down"></i>';
          }
        });
      }
    }, 10);
  }
  
  return card;
}

/**
 * Format date for mobile view
 */
function formatMobileDate(dateTimeStr) {
  if (!dateTimeStr) return { date: 'N/A', time: 'N/A' };
  
  try {
    const [datePart, timePart] = dateTimeStr.split(' ');
    const [year, month, day] = datePart.split('-');
    const [hour, minute] = timePart.split(':');
    
    return {
      date: `${day}.${month}.${year}`,
      time: `${hour}:${minute}`
    };
  } catch (e) {
    return { date: dateTimeStr, time: '' };
  }
}

/**
 * Update pagination UI
 */
function updatePagination() {
  const pagination = document.getElementById("pagination");
  if (!pagination) return;

  pagination.innerHTML = "";

  if (totalPages <= 1) {
    return;
  }

  const current = currentPage;
  const total = totalPages;
  const maxVisible = 5;

  // "Previous" Button
  if (current > 1) {
    const prevLi = document.createElement("li");
    prevLi.className = "page-item";
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current - 1}" title="Previous Page">&laquo;</a>`;
    pagination.appendChild(prevLi);
  }

  // Calculate visible range
  let startPage = Math.max(1, current - Math.floor(maxVisible / 2));
  let endPage = Math.min(total, startPage + maxVisible - 1);

  if (endPage - startPage < maxVisible - 1) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }

  // First page if not in visible range
  if (startPage > 1) {
    const firstLi = document.createElement("li");
    firstLi.className = "page-item";
    firstLi.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
    pagination.appendChild(firstLi);

    // Ellipsis if gap
    if (startPage > 2) {
      const ellipsisLi = document.createElement("li");
      ellipsisLi.className = "page-item disabled";
      ellipsisLi.innerHTML = '<span class="page-link">...</span>';
      pagination.appendChild(ellipsisLi);
    }
  }

  // Page numbers
  for (let i = startPage; i <= endPage; i++) {
    const li = document.createElement("li");
    li.className = `page-item ${i === current ? "active" : ""}`;
    li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
    pagination.appendChild(li);
  }

  // Last page if not in visible range
  if (endPage < total) {
    // Ellipsis if gap
    if (endPage < total - 1) {
      const ellipsisLi = document.createElement("li");
      ellipsisLi.className = "page-item disabled";
      ellipsisLi.innerHTML = '<span class="page-link">...</span>';
      pagination.appendChild(ellipsisLi);
    }

    const lastLi = document.createElement("li");
    lastLi.className = "page-item";
    lastLi.innerHTML = `<a class="page-link" href="#" data-page="${total}">${total}</a>`;
    pagination.appendChild(lastLi);
  }

  // "Next" Button
  if (current < total) {
    const nextLi = document.createElement("li");
    nextLi.className = "page-item";
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${current + 1}" title="Next Page">&raquo;</a>`;
    pagination.appendChild(nextLi);
  }
}

function goToPage(page) {
  if (page && page > 0) {
    currentPage = page;
    fetchLogs();

    // Scroll to top for better UX
    window.scrollTo({ top: 0, behavior: "smooth" });
  }
}

function showLoading(show) {
  const loadingSpinner = document.getElementById("loadingSpinner");
  if (loadingSpinner) {
    // Use CSS classes instead of inline styles
    if (show) {
      loadingSpinner.classList.remove('d-none');
      loadingSpinner.classList.add('d-block');
    } else {
      loadingSpinner.classList.remove('d-block');
      loadingSpinner.classList.add('d-none');
    }
  }
}

/**
 * Enhanced error display with action buttons and categories
 */
function showError(message, category = "general", options = {}) {
  const logTableBody = document.getElementById("logTableBody");
  const logCardsBody = document.getElementById("logCardsBody");

  // Error-spezifische Icons
  const categoryIcons = {
    config: "bi-gear-fill",
    auth: "bi-lock-fill",
    permission: "bi-shield-x",
    "rate-limit": "bi-hourglass-split",
    server: "bi-server",
    network: "bi-wifi-off",
    timeout: "bi-clock-history",
    data: "bi-file-earmark-excel",
    general: "bi-exclamation-triangle",
  };

  const icon = categoryIcons[category] || categoryIcons.general;

  // Action Button HTML
  let actionButton = "";
  if (options.action && options.actionText && options.actionHandler) {
    const actionId = "error-action-" + Math.random().toString(36).substr(2, 9);
    actionButton = `
      <button id="${actionId}" class="btn btn-outline-primary btn-sm mt-2">
        <i class="bi bi-arrow-clockwise"></i> ${options.actionText}
      </button>
    `;

    // Event Handler nach DOM-Update registrieren
    setTimeout(() => {
      const button = document.getElementById(actionId);
      if (button && options.actionHandler) {
        button.addEventListener("click", options.actionHandler);
      }
    }, 10);
  }

  const errorHtml = `
    <div class="text-center text-danger py-5">
      <i class="bi ${icon} icon-3rem mb-3"></i>
      <h5>Oops! Something went wrong</h5>
      <p class="text-muted">${escapeHtml(message)}</p>
      ${actionButton}
    </div>
  `;

  if (logTableBody) {
    logTableBody.innerHTML = `<tr><td colspan="6">${errorHtml}</td></tr>`;
  }
  if (logCardsBody) {
    logCardsBody.innerHTML = errorHtml;
  }
}

/**
 * Success message display
 */
function showSuccessMessage(message) {
  const alertDiv = document.createElement("div");
  alertDiv.className = "alert alert-success alert-dismissible fade show position-fixed toast-notification";
  
  alertDiv.innerHTML = `
    <i class="bi bi-check-circle"></i> ${message}
    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
  `;

  document.body.appendChild(alertDiv);

  setTimeout(() => {
    if (alertDiv.parentElement) {
      alertDiv.remove();
    }
  }, 5000);
}

// Export functions for backwards compatibility
window.goToPage = goToPage;
window.fetchLogs = fetchLogs;