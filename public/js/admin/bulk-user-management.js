/**
 * CSP-Compliant Bulk User Management System
 * Uses event delegation and data attributes instead of onclick handlers
 */

class BulkUserManager {
  constructor() {
    this.selectedUsers = new Set();
    this.allUsers = new Map();
    this.isProcessing = false;

    // DOM Elements
    this.elements = {
      selectAllCheckbox: document.getElementById("selectAllCheckbox"),
      bulkActionsContainer: document.getElementById("bulkActionsContainer"),
      selectionSummary: document.getElementById("selectionSummary"),
      selectionSummaryText: document.getElementById("selectionSummaryText"),
      selectionCount: document.getElementById("selectionCount"),
      bulkActionText: document.getElementById("bulkActionText"),

      // Modals
      roleAssignmentModal: null,
      bulkProgressModal: null,
      bulkResultsModal: null,

      // Modal elements
      roleSelect: document.getElementById("roleSelect"),
      selectedUsersCount: document.getElementById("selectedUsersCount"),
      progressOperation: document.getElementById("progressOperation"),
      bulkProgressBar: document.getElementById("bulkProgressBar"),
      progressStatus: document.getElementById("progressStatus"),
      resultsModalTitle: document.getElementById("resultsModalTitle"),
      successCount: document.getElementById("successCount"),
      skippedCount: document.getElementById("skippedCount"),
      failedCount: document.getElementById("failedCount"),
      resultsTable: document.getElementById("resultsTable"),
    };

    this.init();
  }

  init() {
    console.log("🚀 CSP-Compliant BulkUserManager initializing...");

    // Initialize Bootstrap modals
    this.initializeModals();

    // Collect user data
    this.collectUserData();

    // Setup CSP-compliant event listeners
    this.setupEventListeners();

    // Load available roles
    this.loadAvailableRoles();

    console.log("✅ BulkUserManager initialized with", this.allUsers.size, "users");
  }

  /**
   * Initialize Bootstrap modals safely
   */
  initializeModals() {
    const roleModal = document.getElementById("roleAssignmentModal");
    const progressModal = document.getElementById("bulkProgressModal");
    const resultsModal = document.getElementById("bulkResultsModal");

    if (roleModal) {
      this.elements.roleAssignmentModal = new bootstrap.Modal(roleModal);
    }
    if (progressModal) {
      this.elements.bulkProgressModal = new bootstrap.Modal(progressModal);
    }
    if (resultsModal) {
      this.elements.bulkResultsModal = new bootstrap.Modal(resultsModal);
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

    console.log("📊 Collected", this.allUsers.size, "users from table");
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
          console.log("🧹 Cleaning up progress modal after results closed");
          this.elements.bulkProgressModal.hide();
        }
      });
    }

    console.log("📡 CSP-compliant event listeners setup");
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
      case 'clear-selection':
        this.clearSelection();
        break;
        
      case 'show-role-modal':
        this.showRoleAssignmentModal();
        break;
        
      case 'confirm-role-assignment':
        this.confirmRoleAssignment();
        break;
        
      case 'refresh-user-list':
        this.refreshUserList();
        break;
        
      default:
        console.warn('Unknown action:', action);
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
      console.log("🔄 Select all checkbox changed:", target.checked);
      this.toggleSelectAll(target.checked);
    } else if (action === "update-selection") {
      console.log("🔄 Individual checkbox changed");
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
    console.log("⚡ ExecuteBulkAction called with:", operation);
    console.log("📊 Current selection size:", this.selectedUsers.size);
    console.log("📋 Selected users:", Array.from(this.selectedUsers));

    if (this.selectedUsers.size === 0) {
      console.warn("❌ No users selected - showing alert");
      this.showAlert("No users selected", "warning");
      return;
    }

    if (this.isProcessing) {
      console.warn("⏸️ Already processing, skipping");
      return;
    }

    console.log("✅ Proceeding with bulk action:", operation, "for users:", Array.from(this.selectedUsers));

    // Rest der Methode bleibt gleich...

    // Confirmation for dangerous operations
    if (operation === "delete") {
      const confirmed = await this.confirmDangerousAction(
        "Delete Users",
        `Are you sure you want to delete ${this.selectedUsers.size} user${this.selectedUsers.size !== 1 ? "s" : ""}?`
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
    console.log("🔄 Toggle select all:", checked);

    const userCheckboxes = document.querySelectorAll(".user-select-checkbox");
    console.log("📋 Found checkboxes:", userCheckboxes.length);

    userCheckboxes.forEach((checkbox, index) => {
      checkbox.checked = checked;
      const userId = parseInt(checkbox.value);
      const row = checkbox.closest("tr");

      if (checked) {
        this.selectedUsers.add(userId);
        row.classList.add("selected");
        console.log(`✅ [${index}] Selected user:`, userId, checkbox.dataset.username);
      } else {
        this.selectedUsers.delete(userId);
        row.classList.remove("selected");
        console.log(`❌ [${index}] Deselected user:`, userId);
      }
    });

    console.log("📊 Toggle complete - Total selected:", this.selectedUsers.size);
    console.log("📋 Selected IDs:", Array.from(this.selectedUsers));

    this.updateUI();
  }

  /**
   * Update selection based on individual changes
   */
  /**
   * Update selection based on individual changes
   */
  updateSelection() {
    console.log("🔄 Updating selection...");

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
        console.log("✅ Added user to selection:", userId, checkbox.dataset.username);
      } else {
        row.classList.remove("selected");
        console.log("❌ Removed user from selection:", userId);
      }
    });

    // Debug logging
    console.log("📊 Selection updated - Total selected:", this.selectedUsers.size);
    console.log("📋 Selected IDs:", Array.from(this.selectedUsers));

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
    } else {
      this.elements.bulkActionsContainer?.classList.add("d-none");
      this.elements.selectionSummary?.classList.add("d-none");
    }

    // Update button text
    if (this.elements.bulkActionText) {
      if (selectedCount === 0) {
        this.elements.bulkActionText.textContent = "Bulk Actions";
      } else {
        this.elements.bulkActionText.textContent = `${selectedCount} User${selectedCount !== 1 ? "s" : ""} Selected`;
      }
    }

    console.log("🎨 UI updated - Selected:", selectedCount);
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
   * Show role assignment modal
   */
  showRoleAssignmentModal() {
    if (this.selectedUsers.size === 0) {
      this.showAlert("No users selected", "warning");
      return;
    }

    if (this.elements.roleAssignmentModal) {
      this.elements.roleAssignmentModal.show();
    }
  }

  /**
   * Confirm role assignment
   */
  async confirmRoleAssignment() {
    const selectedRole = this.elements.roleSelect?.value;

    if (!selectedRole) {
      this.showAlert("Please select a role", "warning");
      return;
    }

    // Hide role modal
    if (this.elements.roleAssignmentModal) {
      this.elements.roleAssignmentModal.hide();
    }

    // Execute role assignment
    await this.processBulkOperation("assign_role", { role: selectedRole });
  }

  /**
   * Load available roles
   */
  async loadAvailableRoles() {
    // Extract roles from existing page or use default set
    const defaultRoles = ["User", "Admin", "Manager", "Editor"];

    if (this.elements.roleSelect) {
      this.elements.roleSelect.innerHTML = '<option value="">Choose a role...</option>';

      defaultRoles.forEach((role) => {
        const option = document.createElement("option");
        option.value = role;
        option.textContent = role;
        this.elements.roleSelect.appendChild(option);
      });
    }
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
      assign_role: "Assigning Roles",
      mfa_enable: "Enabling 2FA",
      mfa_disable: "Disabling 2FA",
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
    console.log("📊 Showing results:", response);

    // WICHTIG: Progress Modal zuerst schließen
    if (this.elements.bulkProgressModal) {
      this.elements.bulkProgressModal.hide();
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

    console.log("✅ Results modal displayed, progress modal hidden");
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
   * Confirm dangerous actions
   */
  confirmDangerousAction(title, message) {
    return Promise.resolve(confirm(`${title}\n\n${message}`));
  }

  /**
   * Generate detailed results table
   */
  generateResultsTable(details) {
    console.log("📋 Generating results table with", details.length, "items");

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

    console.log("✅ Results table generated successfully");
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
  console.log("🚀 Initializing CSP-compliant Bulk User Manager...");
  window.bulkManager = new BulkUserManager();
});
