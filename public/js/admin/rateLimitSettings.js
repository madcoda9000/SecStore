/**
 * Rate Limit Settings JavaScript
 * SecStore Admin Panel - CSP-kompatible Version mit vollständigem Live-Status
 */

document.addEventListener("DOMContentLoaded", function () {
  // Messages aus Data-Attributen lesen
  const container = document.querySelector('.container[data-messages-save-success]');
  const messages = {
    saveSuccess: container?.dataset.messagesSaveSuccess || 'Einstellungen gespeichert',
    saveError: container?.dataset.messagesSaveError || 'Fehler beim Speichern',
    resetConfirm: container?.dataset.messagesResetConfirm || 'Wirklich zurücksetzen?',
    testStarted: container?.dataset.messagesTestStarted || 'Test gestartet',
    loadError: container?.dataset.messagesLoadError || 'Fehler beim Laden'
  };

  // Elements
  const form = document.getElementById("rateLimitForm");
  const enableRateLimit = document.getElementById("enableRateLimit");
  const resetToDefaults = document.getElementById("resetToDefaults");
  const testLimits = document.getElementById("testLimits");
  const liveStatusCollapse = document.getElementById("liveStatus");

  // Toasts
  const successToast = new bootstrap.Toast(document.getElementById("successToast"));
  const errorToast = new bootstrap.Toast(document.getElementById("errorToast"));

  // Auto-refresh interval
  let refreshInterval = null;

  // Default values for reset functionality
  const defaultLimits = {
    login: { requests: 5, window: 300 },
    register: { requests: 3, window: 3600 },
    "2fa": { requests: 10, window: 300 },
    "forgot-password": { requests: 3, window: 3600 },
    "reset-password": { requests: 5, window: 3600 },
    admin: { requests: 50, window: 3600 },
    global: { requests: 500, window: 3600 },
  };

  // Initialize
  init();

  function init() {
    // Enable/disable form based on rate limiting toggle
    toggleFormElements();

    // Event listeners
    enableRateLimit?.addEventListener("change", toggleFormElements);
    form?.addEventListener("submit", handleSubmit);
    resetToDefaults?.addEventListener("click", handleResetToDefaults);
    testLimits?.addEventListener("click", handleTestLimits);

    // Live status auto-refresh when expanded
    if (liveStatusCollapse) {
      liveStatusCollapse.addEventListener("shown.bs.collapse", startLiveStatusRefresh);
      liveStatusCollapse.addEventListener("hidden.bs.collapse", stopLiveStatusRefresh);

      // Load initial live status if already expanded
      if (liveStatusCollapse.classList.contains("show")) {
        loadLiveStatus();
      }
    }
  }

  function showSuccessMessage(message) {
    const element = document.getElementById("successMessage");
    if (element) {
      element.textContent = message;
      successToast.show();
    }
  }

  function showErrorMessage(message) {
    const element = document.getElementById("errorMessage");
    if (element) {
      element.textContent = message;
      errorToast.show();
    }
  }

  function toggleFormElements() {
    const isEnabled = enableRateLimit?.checked;
    const formElements = form?.querySelectorAll("input:not(#enableRateLimit), select, button");

    formElements?.forEach((element) => {
      if (element.id !== "enableRateLimit") {
        element.disabled = !isEnabled;
      }
    });

    // Update visual feedback
    const card = form?.querySelector(".card-body");
    if (card) {
      card.style.opacity = isEnabled ? "1" : "0.5";
    }

    // Automatisch speichern durch Aufruf der bestehenden Submit-Methode
    handleSubmit(new Event("submit"));
  }

  async function handleSubmit(e) {
    e.preventDefault();

    const csrfTokenElement = document.getElementById("csrfToken");
    if (!csrfTokenElement || !csrfTokenElement.value) {
      showErrorMessage("CSRF Token nicht gefunden!");
      return;
    }

    const csrfToken = csrfTokenElement.value;
    const formData = new FormData(form);
    const data = {};

    // Process form data
    for (let [key, value] of formData.entries()) {
      if (key.includes("[") && key.includes("]")) {
        // Handle nested arrays (limits and settings)
        const matches = key.match(/(\w+)\[(\w+)\](?:\[(\w+)\])?/);
        if (matches) {
          const [, section, subsection, field] = matches;
          if (!data[section]) data[section] = {};

          if (field) {
            if (!data[section][subsection]) data[section][subsection] = {};
            data[section][subsection][field] = isNaN(value) ? value : parseInt(value);
          } else {
            data[section][subsection] = key.includes("log_violations")
              ? value === "on"
              : isNaN(value)
              ? value
              : parseInt(value);
          }
        }
      } else {
        data[key] = key === "enabled" ? true : value;
      }
    }

    const headers = {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-Token": csrfToken,
    };

    try {
      const response = await fetch("/admin/rate-limits/update", {
        method: "POST",
        headers: headers,
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (result.success) {
        showSuccessMessage(messages.saveSuccess);
        // Refresh live status if visible
        if (liveStatusCollapse?.classList.contains("show")) {
          loadLiveStatus();
        }
      } else {
        throw new Error(result.message || messages.saveError);
      }
    } catch (error) {
      console.error("Request failed:", error);
      showErrorMessage(messages.saveError);
    }
  }

  function handleResetToDefaults() {
    if (confirm(messages.resetConfirm)) {
      // Reset all limit inputs to default values
      Object.entries(defaultLimits).forEach(([limitType, values]) => {
        const requestsInput = form?.querySelector(`input[name="limits[${limitType}][requests]"]`);
        const windowInput = form?.querySelector(`input[name="limits[${limitType}][window]"]`);

        if (requestsInput) requestsInput.value = values.requests;
        if (windowInput) windowInput.value = values.window;
      });

      // Reset settings to defaults
      const maxViolations = document.getElementById("maxViolations");
      const cleanupInterval = document.getElementById("cleanupInterval");
      const logViolations = document.getElementById("logViolations");
      
      if (maxViolations) maxViolations.value = 10;
      if (cleanupInterval) cleanupInterval.value = 3600;
      if (logViolations) logViolations.checked = true;

      showSuccessMessage("Settings reset to defaults");
    }
  }

  async function handleTestLimits() {
    showSuccessMessage(messages.testStarted);

    // Simulate test requests to various endpoints
    const testEndpoints = ["/login", "/register", "/admin/settings"];
    const results = [];

    for (const endpoint of testEndpoints) {
      try {
        const response = await fetch(endpoint, {
          method: "HEAD",
          headers: { "X-Test-Request": "true" },
        });
        results.push(`${endpoint}: ${response.status}`);
      } catch (error) {
        results.push(`${endpoint}: Error`);
      }
    }

    console.log("Rate limit test results:", results);

    // Refresh live status to show test results
    if (liveStatusCollapse?.classList.contains("show")) {
      loadLiveStatus();
    }
  }

  function startLiveStatusRefresh() {
    loadLiveStatus();
    refreshInterval = setInterval(loadLiveStatus, 5000); // Refresh every 5 seconds
  }

  function stopLiveStatusRefresh() {
    if (refreshInterval) {
      clearInterval(refreshInterval);
      refreshInterval = null;
    }
  }

  async function loadLiveStatus() {
    const container = document.getElementById("liveStatusContent");
    if (!container) return;

    try {
      const response = await fetch("/admin/rate-limits/status");
      const data = await response.json();

      if (data) {
        renderLiveStatus(data);
      } else {
        throw new Error("No data received");
      }
    } catch (error) {
      console.error("Failed to load live status:", error);
      container.innerHTML = `
        <div class="alert alert-warning">
          <span class="bi bi-exclamation-triangle"></span>
          ${messages.loadError}
        </div>
      `;
    }
  }

  function renderLiveStatus(data) {
    const container = document.getElementById("liveStatusContent");
    if (!container) return;

    let html = `
      <div class="row mb-3">
        <div class="col-md-12">
          <h6>Current Status: 
            <span class="badge bg-${data.enabled ? "success" : "warning"}">
              ${data.enabled ? "Enabled" : "Disabled"}
            </span>
          </h6>
        </div>
      </div>
      
      <div class="row">
        <div class="col-md-6">
          <h6><span class="bi bi-activity"></span> Active Limits</h6>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Current</th>
                  <th>Max</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
    `;

    // Process active limits if available
    if (data.active_limits && data.active_limits.length > 0) {
      data.active_limits.forEach((limit) => {
        const percentage = limit.max_requests > 0 ? (limit.requests / limit.max_requests) * 100 : 0;
        const status = percentage > 80 ? "danger" : percentage > 60 ? "warning" : "success";

        html += `
          <tr>
            <td><small>${limit.type}</small></td>
            <td>${limit.requests || 0}</td>
            <td>${limit.max_requests || '-'}</td>
            <td>
              <span class="badge bg-${status}">
                ${Math.round(percentage)}%
              </span>
            </td>
          </tr>
        `;
      });
    } else {
      html += `
        <tr>
          <td colspan="4" class="text-center text-muted">
            <small>No active limits found</small>
          </td>
        </tr>
      `;
    }

    html += `
              </tbody>
            </table>
          </div>
        </div>
        <div class="col-md-6">
          <h6><span class="bi bi-exclamation-triangle"></span> Recent Violations</h6>
          <div class="list-group list-group-flush">
    `;

    // Process recent violations
    if (data.recent_violations && data.recent_violations.length > 0) {
      data.recent_violations.slice(0, 5).forEach((violation) => {
        html += `
          <div class="list-group-item list-group-item-action py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <small class="text-muted">${violation.timestamp || "Unknown time"}</small>
                <br>
                <span class="badge bg-secondary">${violation.type || "Unknown"}</span>
              </div>
              <small class="text-muted">${
                violation.identifier 
                  ? violation.identifier.substring(0, 8) + "..." 
                  : "Unknown"
              }</small>
            </div>
          </div>
        `;
      });
    } else {
      html += `
        <div class="list-group-item text-center text-muted">
          <span class="bi bi-info-circle"></span>
          <p class="mb-0">No recent violations</p>
        </div>
      `;
    }

    html += `
          </div>
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-md-12">
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
              Last updated: ${new Date().toLocaleTimeString()}
            </small>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadLiveStatus()">
              <span class="bi bi-arrow-clockwise"></span> Refresh
            </button>
          </div>
        </div>
      </div>
    `;

    container.innerHTML = html;
  }

  // Make loadLiveStatus available globally for the refresh button
  window.loadLiveStatus = loadLiveStatus;
});