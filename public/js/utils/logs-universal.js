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
document.addEventListener("DOMContentLoaded", function () {
  initializeLogPage();
});

function initializeLogPage() {
  loadMessages();
  loadConfig();
  setupEventListeners();
  setupNetworkMonitoring(); // NEU

  if (config.autoFetch) {
    fetchLogs();
  }
}

function loadMessages() {
  const messageContainer = document.getElementById("log-messages");
  if (messageContainer) {
    messages = {
      msg4: messageContainer.dataset.msg4 || "Page",
      msg5: messageContainer.dataset.msg5 || "of",
      msg6: messageContainer.dataset.msg6 || "Error loading logs",
    };
  } else {
    // Fallback Messages
    messages = {
      msg4: "Page",
      msg5: "of",
      msg6: "Error loading logs",
    };
  }
}

function loadConfig() {
  const configContainer = document.getElementById("log-config");
  if (configContainer) {
    config = {
      type: configContainer.dataset.type || "unknown",
      fetchUrl: configContainer.dataset.fetchUrl || "/admin/logs/fetch",
      autoFetch: configContainer.dataset.autoFetch === "true",
    };
  } else {
    console.warn("Log config container not found - using defaults");
    config = {
      type: "unknown",
      fetchUrl: "/admin/logs/fetch",
      autoFetch: true,
    };
  }
}

function setupEventListeners() {
  // Event Listeners für Search und PageSize
  const searchElement = document.getElementById("search");
  const pageSizeElement = document.getElementById("pageSize");

  if (searchElement) {
    searchElement.addEventListener("input", debounce(fetchLogs, 300));
  }

  if (pageSizeElement) {
    pageSizeElement.addEventListener("change", function () {
      currentPage = 1; // Reset to first page when changing page size
      fetchLogs();
    });
  }

  // CSP-kompatible Event-Delegation für Pagination
  setupPaginationEventDelegation();
}

function setupPaginationEventDelegation() {
  const paginationContainer = document.getElementById("pagination");
  if (paginationContainer) {
    paginationContainer.addEventListener("click", function (e) {
      e.preventDefault();

      // Nur auf Links reagieren, die eine data-page haben
      if (e.target.tagName === "A" && e.target.hasAttribute("data-page")) {
        const page = parseInt(e.target.getAttribute("data-page"));
        if (!isNaN(page) && page > 0) {
          goToPage(page);
        }
      }
    });
  }
}

/**
 * Enhanced fetchLogs with comprehensive error handling
 */
function fetchLogs() {
  const searchValue = document.getElementById("search")?.value || "";
  const pageSizeValue = document.getElementById("pageSize")?.value || 10;
  const logTableBody = document.getElementById("logTableBody");
  const logCardsBody = document.getElementById("logCardsBody");

  if (!config.fetchUrl) {
    showError("Configuration error: Fetch URL not found", "config");
    return;
  }

  // Loading anzeigen
  showLoading(true);

  // Tabelle/Cards leeren
  if (logTableBody) logTableBody.innerHTML = "";
  if (logCardsBody) logCardsBody.innerHTML = "";

  const url = `${config.fetchUrl}?search=${encodeURIComponent(
    searchValue
  )}&pageSize=${pageSizeValue}&page=${currentPage}`;

  // Erweiterte Fetch-Konfiguration
  const fetchOptions = {
    method: "GET",
    headers: {
      Accept: "application/json",
      "Cache-Control": "no-cache",
    },
    // 10 Sekunden Timeout
    signal: AbortSignal.timeout ? AbortSignal.timeout(10000) : undefined,
  };

  fetch(url, fetchOptions)
    .then((response) => {
      if (!response.ok) {
        return handleHTTPError(response);
      }
      return response.json();
    })
    .then((data) => {
      if (data.logs && Array.isArray(data.logs)) {
        populateTable(data.logs);
        populateCards(data.logs);
        updatePagination(data.page || 1, data.totalPages || 1);
        clearErrorState();
      } else {
        throw new Error("Invalid API response format");
      }
    })
    .catch((error) => {
      handleFetchError(error);
    })
    .finally(() => {
      showLoading(false);
    });
}

/**
 * Handle HTTP-specific errors with status codes
 */
function handleHTTPError(response) {
  const statusHandlers = {
    401: () => {
      showError("Session expired. Please log in again.", "auth", {
        action: "login",
        actionText: "Go to Login",
        actionHandler: () => (window.location.href = "/login"),
      });
    },
    403: () => {
      showError("Access denied. You don't have permission to view these logs.", "permission");
    },
    404: () => {
      showError("Log endpoint not found. Please check the configuration.", "config");
    },
    429: () => {
      showError("Too many requests. Please wait a moment and try again.", "rate-limit", {
        retryAfter: parseInt(response.headers.get("Retry-After") || "60"),
      });
    },
    500: () => {
      showError("Server error. Please try again later.", "server", {
        action: "retry",
        actionText: "Retry",
        actionHandler: () => fetchLogs(),
      });
    },
    default: () => {
      showError(`HTTP Error ${response.status}: ${response.statusText}`, "http", {
        action: "retry",
        actionText: "Retry",
        actionHandler: () => fetchLogs(),
      });
    },
  };

  const handler = statusHandlers[response.status] || statusHandlers.default;
  handler();

  throw new Error(`HTTP ${response.status}: ${response.statusText}`);
}

/**
 * Handle fetch-specific errors (network, timeout, etc.)
 */
function handleFetchError(error) {
  console.error("Log fetch error:", error);

  if (error.name === "AbortError") {
    showError("Request timed out. Please check your internet connection.", "timeout", {
      action: "retry",
      actionText: "Retry",
      actionHandler: () => fetchLogs(),
    });
  } else if (error.message.includes("Failed to fetch") || !navigator.onLine) {
    showError("Network error. Please check your internet connection.", "network", {
      action: "retry",
      actionText: "Retry Now",
      actionHandler: () => {
        // Warte kurz und versuche erneut
        setTimeout(fetchLogs, 1000);
      },
    });
  } else if (error.message.includes("Invalid API response")) {
    showError("Server returned invalid data. Please refresh the page.", "data", {
      action: "refresh",
      actionText: "Refresh Page",
      actionHandler: () => window.location.reload(),
    });
  } else {
    showError(error.message || messages.msg6 || "Error loading logs", "unknown", {
      action: "retry",
      actionText: "Try Again",
      actionHandler: () => fetchLogs(),
    });
  }
}

function populateTable(logs) {
  const logTableBody = document.getElementById("logTableBody");
  if (!logTableBody) return;

  logTableBody.innerHTML = "";

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
                <i class="bi bi-info-circle" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
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
    
    // Status-specific styling
    const logTypeColors = {
        'AUDIT': { bg: 'bg-primary', text: 'text-primary', border: 'border-primary' },
        'SECURITY': { bg: 'bg-danger', text: 'text-danger', border: 'border-danger' },
        'ERROR': { bg: 'bg-warning', text: 'text-warning', border: 'border-warning' },
        'SYSTEM': { bg: 'bg-info', text: 'text-info', border: 'border-info' },
        'SQL': { bg: 'bg-success', text: 'text-success', border: 'border-success' },
        'MAIL': { bg: 'bg-secondary', text: 'text-secondary', border: 'border-secondary' },
        'REQUEST': { bg: 'bg-dark', text: 'text-dark', border: 'border-dark' }
    };
    
    const logType = (log.type || '').toUpperCase();
    const colors = logTypeColors[logType] || logTypeColors['SYSTEM'];
    
    // Truncate long messages for preview
    const message = log.message || '';
    const isLongMessage = message.length > 100;
    const shortMessage = isLongMessage ? message.substring(0, 100) + '...' : message;
    
    // Format date for mobile
    const date = formatMobileDate(log.datum_zeit);
    
    card.className = `card mb-3 shadow-sm log-mobile-card ${colors.border} border-start border-3`;
    card.style.cssText = `
        border-radius: 12px;
        transition: all 0.2s ease;
        cursor: pointer;
        min-height: 140px;
    `;
    
    card.innerHTML = `
        <div class="card-body p-3">
            <!-- Header Row -->
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center">
                    <span class="badge ${colors.bg} badge-lg me-2 px-2 py-1">
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
                        <span class="message-full d-none">${escapeHtml(message)}</span>
                        <button class="btn btn-link btn-sm p-0 ms-1 expand-btn ${colors.text}" 
                                type="button" onclick="toggleMessage('${cardId}')">
                            <i class="bi bi-chevron-down expand-icon"></i>
                        </button>
                    ` : ''}
                </div>
            </div>
            
            <!-- Touch Actions -->
            <div class="card-actions mt-2 pt-2 border-top d-none">
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="copyLogEntry('${cardId}')">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="shareLogEntry('${cardId}')">
                        <i class="bi bi-share"></i> Share
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Touch-optimierte Event Listeners
    setupCardInteractions(card, cardId);
    
    return card;
}

/**
 * Format date for mobile display
 */
function formatMobileDate(dateString) {
    if (!dateString) return { date: 'N/A', time: 'N/A' };
    
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
        
        let dateDisplay;
        if (diffDays === 0) {
            dateDisplay = 'Today';
        } else if (diffDays === 1) {
            dateDisplay = 'Yesterday';
        } else if (diffDays < 7) {
            dateDisplay = `${diffDays} days ago`;
        } else {
            dateDisplay = date.toLocaleDateString();
        }
        
        const timeDisplay = date.toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        return { date: dateDisplay, time: timeDisplay };
    } catch (e) {
        return { date: dateString.split(' ')[0] || 'N/A', time: dateString.split(' ')[1] || 'N/A' };
    }
}

/**
 * Setup touch-optimized interactions for cards
 */
function setupCardInteractions(card, cardId) {
    let touchStartY = 0;
    let touchStartTime = 0;
    
    // Hover effect für Desktop
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-2px)';
        card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    });
    
    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0)';
        card.style.boxShadow = '';
    });
    
    // Touch-Events für Mobile
    card.addEventListener('touchstart', (e) => {
        touchStartY = e.touches[0].clientY;
        touchStartTime = Date.now();
        card.style.transform = 'scale(0.98)';
    }, { passive: true });
    
    card.addEventListener('touchend', (e) => {
        const touchEndY = e.changedTouches[0].clientY;
        const touchDuration = Date.now() - touchStartTime;
        const touchDistance = Math.abs(touchEndY - touchStartY);
        
        card.style.transform = '';
        
        // Tap gesture (kurze Berührung, wenig Bewegung)
        if (touchDuration < 300 && touchDistance < 10) {
            toggleCardActions(cardId);
        }
        
        // Swipe up gesture (schnelle Bewegung nach oben)
        if (touchDistance > 50 && touchEndY < touchStartY - 30 && touchDuration < 500) {
            expandCard(cardId);
        }
        
        // Swipe down gesture (schnelle Bewegung nach unten)  
        if (touchDistance > 50 && touchEndY > touchStartY + 30 && touchDuration < 500) {
            collapseCard(cardId);
        }
    }, { passive: true });
    
    // Prevent context menu on long press
    card.addEventListener('contextmenu', (e) => {
        e.preventDefault();
    });
}

/**
 * Toggle message expansion in log cards
 */
function toggleMessage(cardId) {
    const card = document.querySelector(`[id="${cardId}"], .log-mobile-card:nth-child(${cardId.split('-').pop() - -1})`);
    if (!card) return;
    
    const preview = card.querySelector('.message-preview');
    const full = card.querySelector('.message-full');
    const icon = card.querySelector('.expand-icon');
    
    if (!preview || !full || !icon) return;
    
    const isExpanded = !full.classList.contains('d-none');
    
    if (isExpanded) {
        // Collapse
        full.classList.add('d-none');
        preview.classList.remove('d-none');
        icon.classList.remove('bi-chevron-up');
        icon.classList.add('bi-chevron-down');
    } else {
        // Expand
        full.classList.remove('d-none');
        preview.classList.add('d-none');
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-up');
    }
}

/**
 * Toggle card action buttons
 */
function toggleCardActions(cardId) {
    const card = document.querySelector(`[id="${cardId}"], .log-mobile-card:nth-child(${cardId.split('-').pop() - -1})`);
    if (!card) return;
    
    const actions = card.querySelector('.card-actions');
    if (!actions) return;
    
    const isVisible = !actions.classList.contains('d-none');
    
    // Hide all other card actions first
    document.querySelectorAll('.card-actions').forEach(el => {
        el.classList.add('d-none');
    });
    
    // Toggle current card actions
    if (!isVisible) {
        actions.classList.remove('d-none');
        card.style.backgroundColor = 'var(--bs-gray-50)';
    } else {
        actions.classList.add('d-none');
        card.style.backgroundColor = '';
    }
}

/**
 * Expand card (swipe up gesture)
 */
function expandCard(cardId) {
    const card = document.querySelector(`[id="${cardId}"], .log-mobile-card:nth-child(${cardId.split('-').pop() - -1})`);
    if (!card) return;
    
    // Expand message if available
    const expandBtn = card.querySelector('.expand-btn');
    if (expandBtn) {
        toggleMessage(cardId);
    }
    
    // Show actions
    toggleCardActions(cardId);
}

/**
 * Collapse card (swipe down gesture)
 */
function collapseCard(cardId) {
    const card = document.querySelector(`[id="${cardId}"], .log-mobile-card:nth-child(${cardId.split('-').pop() - -1})`);
    if (!card) return;
    
    // Collapse message
    const full = card.querySelector('.message-full');
    if (full && !full.classList.contains('d-none')) {
        toggleMessage(cardId);
    }
    
    // Hide actions
    const actions = card.querySelector('.card-actions');
    if (actions) {
        actions.classList.add('d-none');
        card.style.backgroundColor = '';
    }
}

/**
 * Copy log entry to clipboard
 */
function copyLogEntry(cardId) {
    const card = document.querySelector(`[id="${cardId}"], .log-mobile-card:nth-child(${cardId.split('-').pop() - -1})`);
    if (!card) return;
    
    // Extract log data from card
    const id = card.querySelector('.badge').nextElementSibling?.textContent || '';
    const type = card.querySelector('.badge').textContent;
    const message = card.querySelector('.message-full')?.textContent || 
                   card.querySelector('.message-preview').textContent;
    const user = card.querySelector('[title*="User"], .bi-person-circle').parentElement.textContent.replace('👤', '').trim();
    
    const logText = `Log Entry ${id}\nType: ${type}\nUser: ${user}\nMessage: ${message}`;
    
    navigator.clipboard.writeText(logText).then(() => {
        showSuccessMessage('Log entry copied to clipboard');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = logText;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showSuccessMessage('Log entry copied to clipboard');
    });
}

/**
 * Share log entry (Web Share API if available)
 */
function shareLogEntry(cardId) {
    const card = document.querySelector(`[id="${cardId}"], .log-mobile-card:nth-child(${cardId.split('-').pop() - -1})`);
    if (!card) return;
    
    const id = card.querySelector('.badge').nextElementSibling?.textContent || '';
    const type = card.querySelector('.badge').textContent;
    const message = card.querySelector('.message-full')?.textContent || 
                   card.querySelector('.message-preview').textContent;
    
    const shareData = {
        title: `Log Entry ${id}`,
        text: `${type}: ${message}`,
        url: window.location.href
    };
    
    if (navigator.share) {
        navigator.share(shareData).catch(err => {
            console.error('Share failed:', err);
        });
    } else {
        // Fallback: copy to clipboard
        copyLogEntry(cardId);
    }
}

// Global functions für onclick-Handler
window.toggleMessage = toggleMessage;
window.copyLogEntry = copyLogEntry;
window.shareLogEntry = shareLogEntry;

function updatePagination(current, total) {
  const pagination = document.getElementById("pagination");
  const paginationInfo = document.getElementById("paginationInfo");

  if (!pagination) return;

  // Clear existing pagination
  pagination.innerHTML = "";

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
    const prevLi = document.createElement("li");
    prevLi.className = "page-item";
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current - 1}" title="Previous Page">&laquo;</a>`;
    pagination.appendChild(prevLi);
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

  // Visible page numbers
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
    loadingSpinner.style.display = show ? "block" : "none";
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
            <i class="bi ${icon}" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h5>Oops! Something went wrong</h5>
            <p class="text-muted">${escapeHtml(message)}</p>
            ${actionButton}
            <div class="mt-3">
                <small class="text-muted">
                    Error Code: ${category.toUpperCase()} | Time: ${new Date().toLocaleTimeString()}
                </small>
            </div>
        </div>
    `;

  if (logTableBody) {
    logTableBody.innerHTML = `<tr><td colspan="6">${errorHtml}</td></tr>`;
  }

  if (logCardsBody) {
    logCardsBody.innerHTML = `<div class="alert alert-danger">${errorHtml}</div>`;
  }

  // Rate-Limit-spezifische Behandlung
  if (category === "rate-limit" && options.retryAfter) {
    startRetryCountdown(options.retryAfter);
  }
}

/**
 * Clear error state when successful request
 */
function clearErrorState() {
  // Remove any error-specific styling or states
  const errorElements = document.querySelectorAll('[id^="error-action-"]');
  errorElements.forEach((el) => el.remove());
}

/**
 * Rate limit countdown for automatic retry
 */
function startRetryCountdown(seconds) {
  let remaining = seconds;
  const countdownInterval = setInterval(() => {
    remaining--;
    const countdownEl = document.querySelector(".rate-limit-countdown");
    if (countdownEl) {
      countdownEl.textContent = remaining;
    }

    if (remaining <= 0) {
      clearInterval(countdownInterval);
      fetchLogs(); // Automatischer Retry
    }
  }, 1000);
}

// Utility Functions

function escapeHtml(text) {
  if (typeof text !== "string") return text || "";
  const div = document.createElement("div");
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

/**
 * Network status monitoring
 */
function setupNetworkMonitoring() {
  window.addEventListener("online", () => {
    showSuccessMessage("Connection restored. Refreshing logs...");
    setTimeout(fetchLogs, 500);
  });

  window.addEventListener("offline", () => {
    showError("You appear to be offline. Please check your internet connection.", "network");
  });
}

/**
 * Success message display
 */
function showSuccessMessage(message) {
  const alertDiv = document.createElement("div");
  alertDiv.className = "alert alert-success alert-dismissible fade show position-fixed";
  alertDiv.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
    `;
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
