/**
 * Rate Limit Violations JavaScript
 * SecStore Admin Panel
 */

document.addEventListener("DOMContentLoaded", function () {
  // Elements
  const typeFilter = document.getElementById("typeFilter");
  const timeFilter = document.getElementById("timeFilter");
  const searchFilter = document.getElementById("searchFilter");
  const applyFilters = document.getElementById("applyFilters");
  const refreshData = document.getElementById("refreshData");
  const clearViolations = document.getElementById("clearViolations");
  const autoRefresh = document.getElementById("autoRefresh");
  const liveUpdate = document.getElementById("liveUpdate");
  const violationsTableBody = document.getElementById("violationsTableBody");
  const detailsModal = new bootstrap.Modal(document.getElementById("detailsModal"));

  // NEU: Bootstrap Modal für Clear Confirmation
  const confirmClearModal = new bootstrap.Modal(document.getElementById("confirmClearModal"));
  const confirmClearBtn = document.getElementById("confirmClearBtn");

  // Toasts
  const successToast = new bootstrap.Toast(document.getElementById("successToast"));
  const errorToast = new bootstrap.Toast(document.getElementById("errorToast"));

  // Auto-refresh intervals
  let autoRefreshInterval = null;
  let liveUpdateInterval = null;

  // Current filter state
  let currentFilters = {
    type: "",
    time: "24h",
    search: "",
  };

  // Initialize
  init();

  function init() {
    // Event listeners
    applyFilters.addEventListener("click", handleApplyFilters);
    refreshData.addEventListener("click", handleRefreshData);
    clearViolations.addEventListener("click", handleClearViolations);
    autoRefresh.addEventListener("change", handleAutoRefreshToggle);
    liveUpdate.addEventListener("change", handleLiveUpdateToggle);

    // NEU: Modal Event Listeners
    if (confirmClearBtn) {
      confirmClearBtn.addEventListener("click", performClearViolations);
    }

    // Modal Event: Button-Status zurücksetzen nach dem Schließen
    document.getElementById("confirmClearModal").addEventListener("hidden.bs.modal", function () {
      resetConfirmButton();
    });

    // Filter inputs
    searchFilter.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        handleApplyFilters();
      }
    });

    // Reset limit buttons
    document.addEventListener("click", function (e) {
      if (e.target.closest(".reset-limit-btn")) {
        handleResetLimit(e.target.closest(".reset-limit-btn"));
      }
      if (e.target.closest(".view-details-btn")) {
        handleViewDetails(e.target.closest(".view-details-btn"));
      }
    });

    // Initialize auto-refresh if enabled
    if (autoRefresh.checked) {
      startAutoRefresh();
    }

    // Initialize live updates if enabled
    if (liveUpdate.checked) {
      startLiveUpdate();
    }

    // Apply initial filters
    applyCurrentFilters();
  }

  function handleApplyFilters() {
    currentFilters = {
      type: typeFilter.value,
      time: timeFilter.value,
      search: searchFilter.value.trim(),
    };

    applyCurrentFilters();
  }

  function applyCurrentFilters() {
    // ⭐ KRITISCHER FIX: Prüfen ob Element existiert
    if (!violationsTableBody) {
        console.warn('ℹ️ No violations table found - skipping filters');
        return;
    }

    const rows = violationsTableBody.querySelectorAll("tr");
    let visibleCount = 0;

    rows.forEach((row) => {
      const identifier = row.dataset.identifier;
      const type = row.dataset.type;
      const timeCell = row.querySelector("td:first-child small");
      const timestamp = timeCell ? timeCell.textContent : "";

      let visible = true;

      // Type filter
      if (currentFilters.type && type !== currentFilters.type) {
        visible = false;
      }

      // Search filter
      if (currentFilters.search && !identifier.toLowerCase().includes(currentFilters.search.toLowerCase())) {
        visible = false;
      }

      // Time filter
      if (currentFilters.time !== "all") {
        const now = new Date();
        const rowTime = new Date(timestamp);
        const timeDiff = now - rowTime;

        switch (currentFilters.time) {
          case "1h":
            if (timeDiff > 3600000) visible = false;
            break;
          case "24h":
            if (timeDiff > 86400000) visible = false;
            break;
          case "7d":
            if (timeDiff > 604800000) visible = false;
            break;
        }
      }

      row.style.display = visible ? "" : "none";
      if (visible) visibleCount++;
    });

    // Update visible count
    const countElement = document.querySelector(".card-header small");
    if (countElement) {
      countElement.textContent = `Showing ${visibleCount} violations`;
    }
  }

  function handleRefreshData() {
    // Add loading indicator
    const originalText = refreshData.innerHTML;
    refreshData.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
    refreshData.disabled = true;

    // Simulate refresh (in real implementation, you'd fetch new data)
    setTimeout(() => {
      refreshData.innerHTML = originalText;
      refreshData.disabled = false;
      showSuccessMessage("Data refreshed successfully");

      // Reapply filters after refresh
      applyCurrentFilters();
    }, 1000);
  }

  function handleClearViolations() {
    // Bootstrap Modal anzeigen statt Browser-Confirm
    confirmClearModal.show();
  }

  // NEU: Tatsächliches Löschen der Violations (ohne Confirm Dialog)
  function performClearViolations() {
    // Modal verstecken
    confirmClearModal.hide();

    // Loading state für beide Buttons setzen
    setLoadingState(clearViolations, true);
    setLoadingState(confirmClearBtn, true);

    // CSRF Token holen
    const csrfToken =
      document.getElementById("csrf_token")?.value ||
      document.getElementById("csrfToken")?.value ||
      document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

    if (!csrfToken) {
      showErrorMessage("CSRF Token nicht gefunden");
      resetAllButtons();
      return;
    }

    // Clear violations request
    fetch("/admin/rate-limits/clear", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-Token": csrfToken,
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Erfolgsmeldung anzeigen
          showSuccessMessage(messages.clearSuccess);

          // Nach kurzer Verzögerung Seite neu laden
          setTimeout(() => {
            window.location.reload();
          }, 1500); // 1.5 Sekunden warten, damit Toast sichtbar ist
        } else {
          throw new Error(data.message);
        }
      })
      .catch((error) => {
        console.error("Clear error:", error);
        showErrorMessage(messages.clearError);
        resetAllButtons();
      });
  }

  function setLoadingState(button, isLoading) {
    if (!button) return;

    if (isLoading) {
      button.disabled = true;
      if (!button.dataset.originalText) {
        button.dataset.originalText = button.innerHTML;
      }
      button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Lösche...';
    } else {
      button.disabled = false;
      if (button.dataset.originalText) {
        button.innerHTML = button.dataset.originalText;
      }
    }
  }

  // NEU: Alle Buttons zurücksetzen
  function resetAllButtons() {
    setLoadingState(clearViolations, false);
    setLoadingState(confirmClearBtn, false);
  }

  // NEU: Confirm Button zurücksetzen
  function resetConfirmButton() {
    setLoadingState(confirmClearBtn, false);
  }

  async function handleResetLimit(button) {
    const identifier = button.dataset.identifier;
    const type = button.dataset.type;

    if (!identifier) return;

    // Add loading state
    button.disabled = true;
    const originalIcon = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
      const response = await fetch("/admin/rate-limits/reset", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
          identifier: identifier,
          limit_type: type,
        }),
      });

      const result = await response.json();

      if (result.success) {
        showSuccessMessage(messages.resetSuccess);

        // Update the button visual state
        button.classList.remove("btn-outline-primary");
        button.classList.add("btn-outline-success");

        // Reset after 2 seconds
        setTimeout(() => {
          button.classList.remove("btn-outline-success");
          button.classList.add("btn-outline-primary");
        }, 2000);
      } else {
        throw new Error(result.message);
      }
    } catch (error) {
      console.error("Reset error:", error);
      showErrorMessage(messages.resetError);
    } finally {
      button.disabled = false;
      button.innerHTML = originalIcon;
    }
  }

  function handleViewDetails(button) {
    const identifier = button.dataset.identifier;

    if (!identifier) return;

    // Load detailed information
    const modalBody = document.getElementById("detailsModalBody");
    modalBody.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading violation details...</p>
            </div>
        `;

    detailsModal.show();

    // Simulate loading details
    setTimeout(() => {
      modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Identifier Information</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Full Hash:</dt>
                            <dd class="col-sm-8"><code>${identifier}</code></dd>
                            <dt class="col-sm-4">Short ID:</dt>
                            <dd class="col-sm-8"><code>${identifier.substring(0, 12)}...</code></dd>
                            <dt class="col-sm-4">First Seen:</dt>
                            <dd class="col-sm-8">2 hours ago</dd>
                            <dt class="col-sm-4">Total Violations:</dt>
                            <dd class="col-sm-8"><span class="badge bg-danger">15</span></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6>Recent Activity</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between">
                                <span>Login attempts</span>
                                <span class="badge bg-danger">8</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span>2FA attempts</span>
                                <span class="badge bg-warning">4</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between">
                                <span>API calls</span>
                                <span class="badge bg-info">3</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h6>Recommended Actions</h6>
                <div class="alert alert-warning">
                    <span class="bi bi-exclamation-triangle"></span>
                    This identifier shows suspicious activity patterns. Consider:
                    <ul class="mb-0 mt-2">
                        <li>Temporarily blocking this identifier</li>
                        <li>Reducing rate limits for this specific user</li>
                        <li>Monitoring for continued violations</li>
                    </ul>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-warning btn-sm me-2">
                        <span class="bi bi-shield"></span> Block Identifier
                    </button>
                    <button type="button" class="btn btn-primary btn-sm">
                        <span class="bi bi-arrow-clockwise"></span> Reset Limits
                    </button>
                </div>
            `;
    }, 1000);
  }

  function handleAutoRefreshToggle() {
    if (autoRefresh.checked) {
      startAutoRefresh();
    } else {
      stopAutoRefresh();
    }
  }

  function handleLiveUpdateToggle() {
    if (liveUpdate.checked) {
      startLiveUpdate();
    } else {
      stopLiveUpdate();
    }
  }

  function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
      handleRefreshData();
    }, 30000); // Refresh every 30 seconds
  }

  function stopAutoRefresh() {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval);
      autoRefreshInterval = null;
    }
  }

  function startLiveUpdate() {
    liveUpdateInterval = setInterval(() => {
      // Simulate live updates by highlighting new violations
      updateLiveStatus();
    }, 5000); // Check every 5 seconds
  }

  function stopLiveUpdate() {
    if (liveUpdateInterval) {
      clearInterval(liveUpdateInterval);
      liveUpdateInterval = null;
    }
  }

  function updateLiveStatus() {
    // Add subtle animation to indicate live updates are active
    const indicator = document.querySelector("#liveUpdate + label");
    if (indicator && liveUpdate.checked) {
      indicator.style.color = "#28a745";
      setTimeout(() => {
        indicator.style.color = "";
      }, 200);
    }
  }

  function showSuccessMessage(message) {
    document.getElementById("successMessage").textContent = message;
    successToast.show();
  }

  function showErrorMessage(message) {
    document.getElementById("errorMessage").textContent = message;
    errorToast.show();
  }

  // Cleanup intervals when page unloads
  window.addEventListener("beforeunload", function () {
    stopAutoRefresh();
    stopLiveUpdate();
  });
});
