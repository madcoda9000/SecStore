/**
 * Privacy Overview Page JavaScript
 * Handles GDPR data export and account deletion requests
 */

document.addEventListener('DOMContentLoaded', function() {
    const exportDataBtn = document.getElementById('exportDataBtn');
    const requestDeleteBtn = document.getElementById('requestDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    const cancelDeleteModal = new bootstrap.Modal(document.getElementById('cancelDeleteModal'));
    const csrfToken = document.getElementById('csrf-token').value;

    // Data Export Handler
    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', function() {
            // Show loading state
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';

            // Trigger download
            window.location.href = '/privacy/export';

            // Reset button after 2 seconds
            setTimeout(() => {
                this.disabled = false;
                this.innerHTML = originalText;
                showToast('success', 'Data export started', 'Your data export has been downloaded.');
            }, 2000);
        });
    }

    // Request Deletion Handler
    if (requestDeleteBtn) {
        requestDeleteBtn.addEventListener('click', function() {
            deleteConfirmModal.show();
        });
    }

    // Confirm Deletion
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            fetch('/privacy/request-deletion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    deleteConfirmModal.hide();
                    showToast('success', 'Deletion Request Sent', data.message);
                    
                    // Reload page after 2 seconds to show pending deletion alert
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showToast('error', 'Error', data.message);
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error', 'An unexpected error occurred. Please try again.');
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    }

    // Cancel Deletion Handler
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            cancelDeleteModal.show();
        });
    }

    // Confirm Cancellation
    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            fetch('/privacy/cancel-deletion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf_token: csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cancelDeleteModal.hide();
                    showToast('success', 'Deletion Cancelled', data.message);
                    
                    // Reload page after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showToast('error', 'Error', data.message);
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error', 'An unexpected error occurred. Please try again.');
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    }

    /**
     * Show Bootstrap Toast Notification
     * @param {string} type - success, error, warning, info
     * @param {string} title - Toast title
     * @param {string} message - Toast message
     */
    function showToast(type, title, message) {
        const toastContainer = document.querySelector('.toast-container') || createToastContainer();
        
        const iconMap = {
            success: 'check-circle-fill',
            error: 'exclamation-triangle-fill',
            warning: 'exclamation-triangle-fill',
            info: 'info-circle-fill'
        };

        const bgMap = {
            success: 'bg-success',
            error: 'bg-danger',
            warning: 'bg-warning',
            info: 'bg-info'
        };

        const toastHtml = `
            <div class="toast align-items-center text-white ${bgMap[type]} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${iconMap[type]} me-2"></i>
                        <strong>${title}</strong>
                        <br>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();

        // Remove toast after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }

    /**
     * Create toast container if it doesn't exist
     * @returns {HTMLElement}
     */
    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
});
