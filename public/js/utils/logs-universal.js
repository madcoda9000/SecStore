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

function populateCards(logs) {
  const logCardsBody = document.getElementById("logCardsBody");
  if (!logCardsBody) return;

  logCardsBody.innerHTML = "";

  if (logs.length === 0) {
    logCardsBody.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No logs found
            </div>
        `;
    return;
  }

  logs.forEach((log) => {
    const card = document.createElement("div");
    card.className = "card mb-2 p-2";
    card.innerHTML = `
            <div><strong>#${escapeHtml(log.id || "")}</strong></div>
            <div><strong>Type:</strong> <span class="badge bg-secondary">${escapeHtml(log.type || "")}</span></div>
            <div><strong>Date:</strong> ${escapeHtml(log.datum_zeit || "")}</div>
            <div><strong>User:</strong> ${escapeHtml(log.user || "")}</div>
            <div><strong>Context:</strong> ${escapeHtml(log.context || "")}</div>
            <div><strong>Message:</strong> ${escapeHtml(log.message || "")}</div>
        `;
    logCardsBody.appendChild(card);
  });
}

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
    console.log("📡 Network back online - retrying logs");
    showSuccessMessage("Connection restored. Refreshing logs...");
    setTimeout(fetchLogs, 500);
  });

  window.addEventListener("offline", () => {
    console.log("📡 Network offline detected");
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
