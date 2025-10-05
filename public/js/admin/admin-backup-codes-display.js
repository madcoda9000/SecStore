/**
 * Admin Backup Codes Display Script
 * Loads and displays backup codes information for users in admin panel
 * File: public/js/admin/admin-backup-codes-display.js
 */

// GLOBAL: Backup Codes laden für alle sichtbaren Zellen
window.loadAllBackupCodes = function() {
    const backupCodesCells = document.querySelectorAll('.backup-codes-cell');
    const backupCodesCellsMobile = document.querySelectorAll('.backup-codes-cell-mobile');
    const allCells = [...backupCodesCells, ...backupCodesCellsMobile];
    
    allCells.forEach(cell => {
        const userId = cell.getAttribute('data-user-id');
        if (userId) {
            loadBackupCodesInfo(userId, cell);
        }
    });
};

// GLOBAL: Backup Codes Info für einzelnen User laden
function loadBackupCodesInfo(userId, cell) {
    fetch(`/admin/getUserBackupCodesInfo?id=${userId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateBackupCodesDisplay(cell, data);
        } else {
            cell.innerHTML = '<i class="bi-exclamation-circle text-danger" data-bs-toggle="tooltip" title="Error loading info"></i>';
            initializeTooltips();
        }
    })
    .catch(error => {
        console.error('Error loading backup codes info:', error);
        cell.innerHTML = '<i class="bi-exclamation-circle text-danger" data-bs-toggle="tooltip" title="Error loading info"></i>';
        initializeTooltips();
    });
}

// Update display basierend auf Backup Codes Daten
function updateBackupCodesDisplay(cell, data) {
    if (!data.hasBackupCodes) {
        cell.innerHTML = '<i class="bi-x-circle text-warning" data-bs-toggle="tooltip" title="No backup codes generated"></i>';
    } else if (data.remainingCodes === 0) {
        cell.innerHTML = '<i class="bi-exclamation-triangle-fill text-danger" data-bs-toggle="tooltip" title="All backup codes used (0 remaining)"></i>';
    } else if (data.remainingCodes <= 3) {
        cell.innerHTML = `<i class="bi-exclamation-circle-fill text-warning" data-bs-toggle="tooltip" title="Low backup codes: ${data.remainingCodes} remaining"></i>`;
    } else {
        cell.innerHTML = `<i class="bi-check-circle-fill text-success" data-bs-toggle="tooltip" title="${data.remainingCodes} backup codes remaining"></i>`;
    }
    
    const icon = cell.querySelector('i');
    if (icon && (data.remainingCodes === 0 || data.remainingCodes <= 3)) {
        icon.style.cursor = 'pointer';
        icon.classList.add('backup-reset-action');
        icon.setAttribute('data-user-id', data.userId);
        icon.setAttribute('data-username', data.username);
    }
    
    initializeTooltips();
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Reset backup codes
function resetUserBackupCodes(userId, cell) {
    const csrfToken = document.getElementById('csrf_token').value;
    const formData = new FormData();
    formData.append('id', userId);
    formData.append('csrf_token', csrfToken);
    
    cell.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary" role="status"></span>';
    
    fetch('/admin/resetUserBackupCodes', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadBackupCodesInfo(userId, cell);
            if (typeof showToast === 'function') {
                showToast(data.message || 'Backup codes reset successfully', 'success', 'Success');
            }
        } else {
            cell.innerHTML = '<i class="bi-exclamation-circle text-danger" data-bs-toggle="tooltip" title="Reset failed"></i>';
            initializeTooltips();
            if (typeof showToast === 'function') {
                showToast(data.message || 'Error resetting backup codes', 'danger', 'Error');
            }
        }
    })
    .catch(error => {
        console.error('Error resetting backup codes:', error);
        cell.innerHTML = '<i class="bi-exclamation-circle text-danger" data-bs-toggle="tooltip" title="Reset failed"></i>';
        initializeTooltips();
        if (typeof showToast === 'function') {
            showToast('Error resetting backup codes', 'danger', 'Error');
        }
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    window.loadAllBackupCodes();
    
    // Backup reset click handler
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('backup-reset-action')) {
            const userId = e.target.getAttribute('data-user-id');
            const username = e.target.getAttribute('data-username');
            
            if (confirm(`Reset backup codes for user "${username}"?\n\nThis will clear all existing backup codes. The user will need to regenerate new codes.`)) {
                resetUserBackupCodes(userId, e.target.closest('.backup-codes-cell, .backup-codes-cell-mobile'));
            }
        }
    });
});