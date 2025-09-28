/**
 * CSP-Compliant Bulk User Management System
 * Uses event delegation and data attributes instead of onclick handlers
 */

class BulkUserManager {
  constructor() {
    this.selectedUsers = new Set();
    this.allUsers = new Map();
    this.isProcessing = false;
    this.currentBulkConfirmResolve = null;
    this.currentBulkOperation = null;

    // DOM Elements
    this.elements = {
      selectAllCheckbox: document.getElementById("selectAllCheckbox"),
      bulkActionsContainer: document.getElementById("bulkActionsContainer"),
      bulkToolbar: document.getElementById("bulkToolbar"),
      selectionSummary: document.getElementById("selectionSummary"),
      selectionSummaryText: document.getElementById("selectionSummaryText"),
      selectionCount: document.getElementById("selectionCount"),
      bulkActionText: document.getElementById("bulkActionText"),

      // Modals
      bulkProgressModal: null,
      bulkResultsModal: null,
      confirmModal: null,

      // Modal elements
      progressOperation: document.getElementById("progressOperation"),
      bulkProgressBar: document.getElementById("bulkProgressBar"),
      progressStatus: document.getElementById("progressStatus"),
      resultsModalTitle: document.getElementById("resultsModalTitle"),
      resultsFinishButton: document.getElementById("resultsFinishButton"),
      successCount: document.getElementById("successCount"),
      skippedCount: document.getElementById("skippedCount"),
      failedCount: document.getElementById("failedCount"),
      resultsTable: document.getElementById("resultsTable"),
      confirmModalTitle: document.getElementById("confirmModalTitle2"),
      confirmModalMessage: document.getElementById("confirmModalMessage2"),
      confirmModalSubtext: document.getElementById("confirmModalSubtext2"),
      confirmModalBtnText: document.getElementById("confirmModalBtnText2"),
      confirmModalIcon: document.getElementById("confirmModalIcon"),
      confirmDeleteBtn: document.getElementById("confirmDeleteBtn2"),
    };

    this.init();
  }

  init() {

    // Initialize Bootstrap modals
    this.initializeModals();

    // Collect user data
    this.collectUserData();

    // Setup CSP-compliant event listeners
    this.setupEventListeners();
  }

  /**
   * Initialize Bootstrap modals safely
   */
  initializeModals() {
    const progressModal = document.getElementById("bulkProgressModal");
    const resultsModal = document.getElementById("bulkResultsModal");
    const confirmModal = document.getElementById("confirmDeleteModal2");

    if (progressModal) {
      this.elements.bulkProgressModal = new bootstrap.Modal(progressModal);
    }
    if (resultsModal) {
      this.elements.bulkResultsModal = new bootstrap.Modal(resultsModal);
    }
    if (confirmModal) {
      this.elements.confirmModal = new bootstrap.Modal(confirmModal);
    }
  }

  /**
   * Collect user data from table rows
   */
  collectUserData() {
    const userRows = document.querySelectorAll("tbody tr[data-user-id]");

    userRows.forEach((row) => {
      const userId = parseInt(row.dataset.userId);
      const username = row.dataset.username;

      // Extract user info from table cells
      const cells = row.querySelectorAll("td");

      this.allUsers.set(userId, {
        id: userId,
        username: username,
        element: row,
      });
    });
  }

  /**
   * Setup CSP-compliant event listeners using event delegation
   */
  setupEventListeners() {
    // Master event delegation handler
    document.addEventListener("click", this.handleClick.bind(this));
    document.addEventListener("change", this.handleChange.bind(this));

    // Results Modal Event: Progress Modal cleanup when results modal is closed
    const resultsModal = document.getElementById("bulkResultsModal");
    if (resultsModal) {
      resultsModal.addEventListener("hidden.bs.modal", () => {
        // Ensure progress modal is definitely closed when results modal closes
        if (this.elements.bulkProgressModal && this.elements.bulkProgressModal._element.classList.contains("show")) {
          this.elements.bulkProgressModal.hide();
        }
      });
    }

    const confirmModal = document.getElementById("confirmDeleteModal2");
    if (confirmModal) {
      confirmModal.addEventListener("hidden.bs.modal", () => {
        // Wenn Modal geschlossen wird ohne Bestätigung, resolve mit false
        if (this.currentBulkConfirmResolve) {
          this.currentBulkConfirmResolve(false);
          this.currentBulkConfirmResolve = null;
          this.currentBulkOperation = null;
        }
      });
    }
  }

  /**
   * Central click event handler (CSP-compliant)
   */
  handleClick(event) {
    const target = event.target;
    const action = target.dataset.action || target.closest("[data-action]")?.dataset.action;
    const bulkAction = target.dataset.bulkAction || target.closest("[data-bulk-action]")?.dataset.bulkAction;
    const exportAction = target.dataset.export || target.closest("[data-export]")?.dataset.export;

    // Skip checkbox actions - they should be handled by handleChange
    if (action === "toggle-select-all" || action === "update-selection") {
      return; // Let handleChange deal with these
    }
    // Bulk actions
    if (bulkAction) {
      event.preventDefault();
      this.executeBulkAction(bulkAction);
      return;
    }

    // Regular actions
    if (action) {
      event.preventDefault();
      this.handleAction(action);
      return;
    }

    // Export actions
    if (exportAction) {
      event.preventDefault();
      this.handleExport(exportAction);
      return;
    }
  }

  /**
   * Handle regular actions
   */
  handleAction(action) {
    switch (action) {
      case "clear-selection":
        this.clearSelection();
        break;

      case "refresh-user-list":
        this.refreshUserList();
        break;

      case "confirm-delete":
        if (this.currentBulkConfirmResolve) {
          if (this.elements.confirmModal) {
            this.elements.confirmModal.hide();
          }
          this.currentBulkConfirmResolve(true);
          this.currentBulkConfirmResolve = null;
          this.currentBulkOperation = null;
          // Modal wird automatisch geschlossen durch die Promise-Resolution
        }
        break;

      default:
        console.warn("Unknown action:", action);
    }
  }

  /**
   * Handle regular actions
   */
  handleChange(event) {
    const target = event.target;
    const action = target.dataset.action;

    // Only handle checkbox-related actions
    if (action === "toggle-select-all") {
      this.toggleSelectAll(target.checked);
    } else if (action === "update-selection") {
      this.updateSelection();
    }
  }

  /**
   * Handle export actions
   */
  handleExport(exportAction) {
    const [format, scope] = exportAction.split("-");
    this.exportUsers(format, scope);
  }

  /**
   * Execute bulk actions
   */
  /**
   * Execute bulk actions
   */
  async executeBulkAction(operation) {

    if (this.selectedUsers.size === 0) {
      console.warn("❌ No users selected - showing alert");
      this.showAlert("No users selected", "warning");
      return;
    }

    if (this.isProcessing) {
      console.warn("⏸️ Already processing, skipping");
      return;
    }

    // Confirmation for dangerous operations
    if (operation === "delete") {
      const confirmed = await this.confirmDangerousAction(
        "Delete Users",
        `Are you sure you want to delete ${this.selectedUsers.size} user${this.selectedUsers.size !== 1 ? "s" : ""}?`,
        operation
      );
      if (!confirmed) return;
    } else if (operation === "mfa_enforce") {
      const confirmed = await this.confirmDangerousAction(
        "Enforce 2FA",
        `Enforce 2FA for ${this.selectedUsers.size} user${this.selectedUsers.size !== 1 ? "s" : ""}?`,
        operation
      );
      if (!confirmed) return;
    } else if (operation === "mfa_unenforce") {
      const confirmed = await this.confirmDangerousAction(
        "Remove 2FA Enforcement",
        `Remove 2FA enforcement for ${this.selectedUsers.size} user${this.selectedUsers.size !== 1 ? "s" : ""}?`,
        operation
      );
      if (!confirmed) return;
    } else if (operation === "enable") {
      const confirmed = await this.confirmDangerousAction(
        "Enable Users",
        `Enable ${this.selectedUsers.size} user${this.selectedUsers.size !== 1 ? "s" : ""}?`,
        operation
      );
      if (!confirmed) return;
    } else if (operation === "disable") {
      const confirmed = await this.confirmDangerousAction(
        "Disable Users",
        `Disable ${this.selectedUsers.size} user${this.selectedUsers.size !== 1 ? "s" : ""}?`,
        operation
      );
      if (!confirmed) return;
    }

    await this.processBulkOperation(operation);
  }

  /**
   * Process bulk operation with progress tracking
   */
  /**
   * Process bulk operation with progress tracking
   */
  async processBulkOperation(operation, options = {}) {
    this.showProgressModal(operation);

    try {
      this.isProcessing = true;

      const userIds = Array.from(this.selectedUsers);
      const response = await this.sendBulkRequest(operation, userIds, options);

      if (response.success) {
        // Progress Modal wird in showResults() geschlossen
        this.showResults(response);
        this.clearSelection();
      } else {
        throw new Error(response.message || "Operation failed");
      }
    } catch (error) {
      console.error("❌ Bulk operation failed:", error);

      // Bei Fehler: Progress Modal explizit schließen
      if (this.elements.bulkProgressModal) {
        this.elements.bulkProgressModal.hide();
      }

      this.showAlert("Operation failed: " + error.message, "danger");
    } finally {
      this.isProcessing = false;

      // Sicherheitscheck: Falls Progress Modal immer noch offen
      setTimeout(() => {
        if (this.elements.bulkProgressModal && this.elements.bulkProgressModal._element.classList.contains("show")) {
          console.warn("⚠️ Force closing stuck progress modal");
          this.elements.bulkProgressModal.hide();
        }
      }, 500);
    }
  }

  /**
   * Toggle select all functionality
   */
  /**
   * Toggle select all functionality
   */
  toggleSelectAll(checked) {

    const userCheckboxes = document.querySelectorAll(".user-select-checkbox");

    userCheckboxes.forEach((checkbox, index) => {
      checkbox.checked = checked;
      const userId = parseInt(checkbox.value);
      const row = checkbox.closest("tr");

      if (checked) {
        this.selectedUsers.add(userId);
        row.classList.add("selected");
      } else {
        this.selectedUsers.delete(userId);
        row.classList.remove("selected");
      }
    });

    this.updateUI();
  }

  /**
   * Update selection based on individual changes
   */
  /**
   * Update selection based on individual changes
   */
  updateSelection() {

    // Clear current selection
    this.selectedUsers.clear();

    // Get all user checkboxes
    const userCheckboxes = document.querySelectorAll(".user-select-checkbox");

    userCheckboxes.forEach((checkbox) => {
      const userId = parseInt(checkbox.value);
      const row = checkbox.closest("tr");

      if (checkbox.checked) {
        this.selectedUsers.add(userId);
        row.classList.add("selected");
      } else {
        row.classList.remove("selected");
      }
    });

    // Update select all checkbox state
    this.updateSelectAllState();

    // Update UI
    this.updateUI();
  }

  /**
   * Update select all checkbox state
   */
  updateSelectAllState() {
    const userCheckboxes = document.querySelectorAll(".user-select-checkbox");
    const totalCheckboxes = userCheckboxes.length;
    const selectedCheckboxes = this.selectedUsers.size;

    if (this.elements.selectAllCheckbox) {
      this.elements.selectAllCheckbox.checked = selectedCheckboxes === totalCheckboxes;
      this.elements.selectAllCheckbox.indeterminate = selectedCheckboxes > 0 && selectedCheckboxes < totalCheckboxes;
    }
  }

  /**
   * Update UI based on selection
   */
  updateUI() {
    const selectedCount = this.selectedUsers.size;
    const hasSelection = selectedCount > 0;

    // Update count displays
    if (this.elements.selectionCount) {
      this.elements.selectionCount.textContent = selectedCount;
    }

    if (this.elements.selectionSummaryText) {
      this.elements.selectionSummaryText.textContent = `${selectedCount} user${
        selectedCount !== 1 ? "s" : ""
      } selected`;
    }

    if (this.elements.selectedUsersCount) {
      this.elements.selectedUsersCount.textContent = selectedCount;
    }

    // Show/hide bulk toolbar
    if (hasSelection) {
      this.elements.bulkActionsContainer?.classList.remove("d-none");
      this.elements.selectionSummary?.classList.remove("d-none");
      this.elements.bulkToolbar?.classList.remove("d-none");
    } else {
      this.elements.bulkActionsContainer?.classList.add("d-none");
      this.elements.selectionSummary?.classList.add("d-none");
      this.elements.bulkToolbar?.classList.add("d-none");
    }

    // Update button text
    if (this.elements.bulkActionText) {
      if (selectedCount === 0) {
        this.elements.bulkActionText.textContent = "Bulk Actions";
      } else {
        this.elements.bulkActionText.textContent = "Bulk Actions"; //`${selectedCount} User${selectedCount !== 1 ? "s" : ""} Selected`;
      }
    }
  }

  /**
   * Clear all selections
   */
  clearSelection() {
    this.selectedUsers.clear();

    const userCheckboxes = document.querySelectorAll(".user-select-checkbox");
    userCheckboxes.forEach((checkbox) => {
      checkbox.checked = false;
      checkbox.closest("tr").classList.remove("selected");
    });

    if (this.elements.selectAllCheckbox) {
      this.elements.selectAllCheckbox.checked = false;
      this.elements.selectAllCheckbox.indeterminate = false;
    }

    this.updateUI();
  }

  /**
   * Send bulk request to backend
   */
  async sendBulkRequest(operation, userIds, options = {}) {
    const csrfToken = document.getElementById("csrf_token")?.value;

    if (!csrfToken) {
      throw new Error("CSRF token not found");
    }

    const response = await fetch("/admin/users/bulk", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({
        operation: operation,
        userIds: userIds,
        options: options,
        csrf_token: csrfToken,
      }),
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    return await response.json();
  }

  /**
   * Show progress modal
   */
  showProgressModal(operation) {
    const operationTexts = {
      delete: "Deleting Users",
      enable: "Enabling Users",
      disable: "Disabling Users",
      mfa_enforce: "Enforcing 2FA",
      mfa_unenforce: "Removing 2FA Enforcement",
    };

    if (this.elements.progressOperation) {
      this.elements.progressOperation.textContent = operationTexts[operation] || "Processing";
    }

    if (this.elements.bulkProgressModal) {
      this.elements.bulkProgressModal.show();
    }

    this.animateProgressBar();
  }

  /**
   * Animate progress bar
   */
  animateProgressBar() {
    if (!this.elements.bulkProgressBar) return;

    let progress = 0;
    const interval = setInterval(() => {
      progress += Math.random() * 10;
      if (progress > 90) progress = 90;

      this.elements.bulkProgressBar.style.width = `${progress}%`;

      if (!this.isProcessing) {
        clearInterval(interval);
        this.elements.bulkProgressBar.style.width = "100%";
      }
    }, 300);
  }

  /**
   * Show results modal
   */
  /**
   * Show results modal with detailed breakdown
   */
  showResults(response) {

    // WICHTIG: Progress Modal zuerst schließen
    if (this.elements.bulkProgressModal) {
      this.elements.bulkProgressModal.hide();
    }

    // onfirm modal schliessen
    if (this.elements.confirmModal) {
      this.elements.confirmModal.hide();
    }
    // Update summary cards
    if (this.elements.successCount) {
      this.elements.successCount.textContent = response.summary?.success || 0;
    }
    if (this.elements.skippedCount) {
      this.elements.skippedCount.textContent = response.summary?.skipped || 0;
    }
    if (this.elements.failedCount) {
      this.elements.failedCount.textContent = response.summary?.failed || 0;
    }

    // Update modal title based on results
    const hasFailures = (response.summary?.failed || 0) > 0;
    const titleIcon = hasFailures
      ? '<i class="bi bi-exclamation-triangle text-warning"></i>'
      : '<i class="bi bi-check-circle text-success"></i>';

    if (this.elements.resultsModalTitle) {
      this.elements.resultsModalTitle.innerHTML = `${titleIcon} ${
        response.operation.charAt(0).toUpperCase() + response.operation.slice(1)
      } Complete`;
    }

    // Generate detailed results table
    this.generateResultsTable(response.details || []);

    // Show results modal
    if (this.elements.bulkResultsModal) {
      this.elements.bulkResultsModal.show();
    }
  }

  /**
   * Export users
   */
  exportUsers(format, scope) {
    let url = `/admin/users/export?format=${format}`;

    if (scope === "selected") {
      if (this.selectedUsers.size === 0) {
        this.showAlert("No users selected", "warning");
        return;
      }
      url += `&userIds=${Array.from(this.selectedUsers).join(",")}`;
    }

    // Create download link
    const link = document.createElement("a");
    link.href = url;
    link.download = "";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    this.showAlert("Export started", "success");
  }

  /**
   * Refresh user list
   */
  refreshUserList() {
    window.location.reload();
  }

  /**
   * Show alert message
   */
  showAlert(message, type = "info") {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = `
            top: 20px; right: 20px; z-index: 9999; min-width: 300px;
        `;
    alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
      if (alertDiv.parentElement) {
        alertDiv.remove();
      }
    }, 5000);
  }

  /**
   * Get translations from data attributes based on operation
   */
  getBulkConfirmTranslations(operation, messagesDiv) {
    const count = this.selectedUsers.size;
    const plural = count !== 1 ? "s" : "";

    const configs = {
      delete: {
        title: messagesDiv.dataset.bulkDeleteTitle,
        message: messagesDiv.dataset.bulkDeleteMessage,
        subtext: messagesDiv.dataset.bulkDeleteSubtext,
        btnText: messagesDiv.dataset.bulkDeleteBtn,
        btnClass: "btn-danger",
        icon: "bi-trash-fill text-danger",
      },
      disable: {
        title: messagesDiv.dataset.bulkDisableTitle,
        message: messagesDiv.dataset.bulkDisableMessage,
        subtext: messagesDiv.dataset.bulkDisableSubtext,
        btnText: messagesDiv.dataset.bulkDisableBtn,
        btnClass: "btn-warning",
        icon: "bi-person-x text-warning",
      },
      enable: {
        title: messagesDiv.dataset.bulkEnableTitle,
        message: messagesDiv.dataset.bulkEnableMessage,
        subtext: messagesDiv.dataset.bulkEnableSubtext,
        btnText: messagesDiv.dataset.bulkEnableBtn,
        btnClass: "btn-success",
        icon: "bi-person-check text-success",
      },
      mfa_enforce: {
        title: messagesDiv.dataset.bulkMfaEnforceTitle,
        message: messagesDiv.dataset.bulkMfaEnforceMessage,
        subtext: messagesDiv.dataset.bulkMfaEnforceSubtext,
        btnText: messagesDiv.dataset.bulkMfaEnforceBtn,
        btnClass: "btn-warning",
        icon: "bi-shield-exclamation text-warning",
      },
      mfa_unenforce: {
        title: messagesDiv.dataset.bulkMfaUnenforceTitle,
        message: messagesDiv.dataset.bulkMfaUnenforceMessage,
        subtext: messagesDiv.dataset.bulkMfaUnenforceSubtext,
        btnText: messagesDiv.dataset.bulkMfaUnenforceBtn,
        btnClass: "btn-info",
        icon: "bi-shield-slash text-info",
      },
    };

    const config = configs[operation];

    // Fallback to delete if operation not found
    if (!config) {
      console.warn("Unknown operation:", operation, "falling back to delete config");
      return configs["delete"];
    }

    // Replace placeholders in message
    if (config.message) {
      config.message = config.message.replace("{count}", count.toString()).replace("{plural}", plural);
    }

    return config;
  }

  /**
   * Update modal with translated content
   */
  updateBulkConfirmModal(config) {
    // Update title
    if (this.elements.confirmModalTitle && config.title) {
      this.elements.confirmModalTitle.textContent = config.title;
    }

    // Update message
    if (this.elements.confirmModalMessage && config.message) {
      this.elements.confirmModalMessage.textContent = config.message;
    }

    // Update subtext
    if (this.elements.confirmModalSubtext && config.subtext) {
      this.elements.confirmModalSubtext.textContent = config.subtext;
    }

    // Update button
    if (this.elements.confirmDeleteBtn && config.btnClass) {
      this.elements.confirmDeleteBtn.className = `btn ${config.btnClass}`;
    }

    if (this.elements.confirmModalBtnText && config.btnText) {
      this.elements.confirmModalBtnText.textContent = config.btnText;
    }

    // Update icon
    if (this.elements.confirmModalIcon && config.icon) {
      this.elements.confirmModalIcon.className = `${config.icon} fs-1 me-3`;
    }
  }

  /**
   * Confirm dangerous actions
   */
  confirmDangerousAction(title, message, operation) {
    return new Promise((resolve) => {
      // Store resolve for later use
      this.currentBulkConfirmResolve = resolve;
      this.currentBulkOperation = operation;

      // Get translated texts from data attributes
      const messagesDiv = document.getElementById("users-messages");
      if (!messagesDiv) {
        console.warn("users-messages div not found, falling back to confirm()");
        resolve(confirm(`${title}\n\n${message}`));
        return;
      }

      // Get operation-specific translations
      const config = this.getBulkConfirmTranslations(operation, messagesDiv);

      // Update modal content
      this.updateBulkConfirmModal(config);

      // Show existing modal (reuse confirmDeleteModal)
      if (this.elements.confirmModal) {
        this.elements.confirmModal.show();
      }
    });
  }

  /**
   * Generate detailed results table
   */
  generateResultsTable(details) {

    if (!details || details.length === 0) {
      this.elements.resultsTable.innerHTML = '<p class="text-muted">No detailed results available.</p>';
      return;
    }

    const table = document.createElement("table");
    table.className = "table table-sm table-striped";

    table.innerHTML = `
        <thead>
            <tr>
                <th>User</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            ${details
              .map((result) => {
                const statusBadge = this.getStatusBadge(result.status);
                return `
                    <tr>
                        <td>
                            <strong>${result.username || "Unknown"}</strong>
                            <br><small class="text-muted">ID: ${result.userId}</small>
                        </td>
                        <td>${statusBadge}</td>
                        <td><small>${result.reason || "No details"}</small></td>
                    </tr>
                `;
              })
              .join("")}
        </tbody>
    `;

    this.elements.resultsTable.innerHTML = "";
    this.elements.resultsTable.appendChild(table);
  }

  /**
   * Get status badge HTML
   */
  getStatusBadge(status) {
    const badges = {
      success: '<span class="badge bg-success">Success</span>',
      failed: '<span class="badge bg-danger">Failed</span>',
      skipped: '<span class="badge bg-warning">Skipped</span>',
    };

    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
  }
}

// Initialize when DOM is ready (CSP-compliant)
document.addEventListener("DOMContentLoaded", function () {
  window.bulkManager = new BulkUserManager();
});
