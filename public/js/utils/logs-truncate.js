/**
 * Log Truncate Handler
 * CSP-compliant truncate functionality for all log pages
 */
document.addEventListener("DOMContentLoaded", function () {
  const truncateBtn = document.getElementById("truncateLogsBtn");
  const exportFirstBtn = document.getElementById("exportFirstBtn");
  const confirmTruncateBtn = document.getElementById("confirmTruncateBtn");
  const exportLogsBtn = document.getElementById("exportLogsBtn");
  
  if (!truncateBtn) {
    return;
  }

  // Get config and messages
  const configDiv = document.getElementById("log-config");
  const messagesDiv = document.getElementById("truncate-messages");
  const logType = configDiv.dataset.type.toUpperCase();
  
  // Get translation messages
  const confirmTemplate = messagesDiv.dataset.confirm;
  const successTemplate = messagesDiv.dataset.success;
  const errorMsg = messagesDiv.dataset.error;

  // Initialize Bootstrap modal
  const truncateModal = new bootstrap.Modal(document.getElementById("truncateModal"));

  // Show truncate confirmation modal
  truncateBtn.addEventListener("click", function () {
    // Set confirm text with log type
    const confirmText = confirmTemplate.replace("{type}", logType);
    document.getElementById("truncateConfirmText").textContent = confirmText;
    
    truncateModal.show();
  });

  // Export first button - triggers export and closes modal
  exportFirstBtn.addEventListener("click", function () {
    truncateModal.hide();
    // Trigger export button
    if (exportLogsBtn) {
      exportLogsBtn.click();
    }
  });

  // Confirm truncate button
  confirmTruncateBtn.addEventListener("click", function () {
    // Disable all buttons
    confirmTruncateBtn.disabled = true;
    exportFirstBtn.disabled = true;
    const cancelBtn = document.querySelector('#truncateModal .btn-secondary');
    if (cancelBtn) {
      cancelBtn.disabled = true;
    }
    
    // Show spinner in delete button
    const originalBtnContent = confirmTruncateBtn.innerHTML;
    confirmTruncateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Löschen...';

    // Send truncate request
    fetch("/admin/logs/truncate", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({
        logType: logType,
        csrf_token: getCSRFToken(),
      }),
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Show success message
          const successMsg = successTemplate.replace("{count}", data.count);
          showAlert(successMsg, "success");
          
          // Close modal and refresh page after short delay
          truncateModal.hide();
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          // Re-enable buttons on error
          showAlert(data.message || errorMsg, "danger");
          confirmTruncateBtn.disabled = false;
          exportFirstBtn.disabled = false;
          if (cancelBtn) {
            cancelBtn.disabled = false;
          }
          confirmTruncateBtn.innerHTML = originalBtnContent;
        }
      })
      .catch(error => {
        // Re-enable buttons on error
        console.error("Truncate error:", error);
        showAlert(errorMsg, "danger");
        confirmTruncateBtn.disabled = false;
        exportFirstBtn.disabled = false;
        if (cancelBtn) {
          cancelBtn.disabled = false;
        }
        confirmTruncateBtn.innerHTML = originalBtnContent;
      });
  });

  // Helper: Get CSRF token from meta tag or form
  function getCSRFToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
      return metaTag.getAttribute("content");
    }
    // Fallback: try to find in hidden input
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return csrfInput ? csrfInput.value : "";
  }

  // Helper: Show alert message
  function showAlert(message, type) {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
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
});