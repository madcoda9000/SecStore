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
    const testEndpoints = ["/login", "/register", "/admin/settings", "/reset-password/dwtfrkjswlgth", "/forgot-password", "/home", "/2fa-verify"];
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
          <h6><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-activity " viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M6 2a.5.5 0 0 1 .47.33L10 12.036l1.53-4.208A.5.5 0 0 1 12 7.5h3.5a.5.5 0 0 1 0 1h-3.15l-1.88 5.17a.5.5 0 0 1-.94 0L6 3.964 4.47 8.171A.5.5 0 0 1 4 8.5H.5a.5.5 0 0 1 0-1h3.15l1.88-5.17A.5.5 0 0 1 6 2"/>
</svg> Active Limits</h6>
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
          <h6><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
  <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
  <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
</svg> Recent Violations</h6>
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
          <span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
  <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
</svg></span>
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
              <span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
  <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
</svg></span> Refresh
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