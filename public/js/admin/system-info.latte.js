/**
 * System Info Page JavaScript
 * CSP-compliant implementation with live updates
 */

document.addEventListener('DOMContentLoaded', function() {
    const refreshBtn = document.getElementById('refreshBtn');
    const refreshBtnMobile = document.getElementById('refreshBtnMobile');
    const toggleAutoRefreshBtn = document.getElementById('toggleAutoRefresh');
    const toggleAutoRefreshBtnMobile = document.getElementById('toggleAutoRefreshMobile');
    const showPhpInfoBtn = document.getElementById('showPhpInfoBtn');
    const phpInfoModal = new bootstrap.Modal(document.getElementById('phpInfoModal'));
    const phpInfoFrame = document.getElementById('phpInfoFrame');
    const lastUpdatedSpan = document.getElementById('lastUpdated');
    const autoRefreshText = document.getElementById('autoRefreshText');
    
    let autoRefreshEnabled = false;
    let autoRefreshInterval = null;

    document.querySelectorAll('.progress-bar[data-width]').forEach(bar => {
        const width = bar.getAttribute('data-width');
        bar.style.width = width + '%';
    });

    /**
     * Manual refresh (Desktop + Mobile)
     */
    function setupRefreshButton(btn) {
        if (btn) {
            btn.addEventListener('click', function() {
                updateSystemInfo();
            });
        }
    }

    setupRefreshButton(refreshBtn);
    setupRefreshButton(refreshBtnMobile);

    /**
     * Toggle auto-refresh (Desktop + Mobile)
     */
    function setupAutoRefreshToggle(btn, isMobile = false) {
        if (btn) {
            btn.addEventListener('click', function() {
                autoRefreshEnabled = !autoRefreshEnabled;
                
                if (autoRefreshEnabled) {
                    // Start auto-refresh every 30 seconds
                    autoRefreshInterval = setInterval(updateSystemInfo, 30000);
                    
                    // Desktop button styling
                    if (!isMobile && toggleAutoRefreshBtn) {
                        toggleAutoRefreshBtn.classList.remove('btn-outline-secondary');
                        toggleAutoRefreshBtn.classList.add('btn-success');
                        if (autoRefreshText) {
                            autoRefreshText.textContent = getTranslation('system_info.auto_refresh_enabled');
                        }
                    }
                    
                    // Mobile button styling
                    if (isMobile) {
                        btn.classList.remove('btn-outline-secondary');
                        btn.classList.add('btn-success');
                    }
                    
                    showToast(getTranslation('system_info.auto_refresh') + ' ' + getTranslation('system_info.auto_refresh_enabled'), 'success');
                } else {
                    // Stop auto-refresh
                    if (autoRefreshInterval) {
                        clearInterval(autoRefreshInterval);
                        autoRefreshInterval = null;
                    }
                    
                    // Desktop button styling
                    if (!isMobile && toggleAutoRefreshBtn) {
                        toggleAutoRefreshBtn.classList.remove('btn-success');
                        toggleAutoRefreshBtn.classList.add('btn-outline-secondary');
                        if (autoRefreshText) {
                            autoRefreshText.textContent = getTranslation('system_info.auto_refresh');
                        }
                    }
                    
                    // Mobile button styling
                    if (isMobile) {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-secondary');
                    }
                    
                    showToast(getTranslation('system_info.auto_refresh') + ' ' + getTranslation('system_info.auto_refresh_disabled'), 'info');
                }
            });
        }
    }
    
    setupAutoRefreshToggle(toggleAutoRefreshBtn, false);
    setupAutoRefreshToggle(toggleAutoRefreshBtnMobile, true);

    /**
     * Show PHP Info in modal
     */
    if (showPhpInfoBtn) {
        showPhpInfoBtn.addEventListener('click', function() {
            phpInfoModal.show();
            phpInfoFrame.src = '/admin/phpinfo';
        });
    }

    /**
     * Update system information via AJAX
     */
    function updateSystemInfo() {
        // Show loading indicator on both buttons
        const buttons = [refreshBtn, refreshBtnMobile].filter(btn => btn);
        buttons.forEach(btn => {
            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.dataset.originalHtml = originalHtml;
        });

        fetch('/admin/system-info/json', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFields(data.data);
                lastUpdatedSpan.textContent = getTranslation('system_info.last_updated') + ': ' + data.timestamp;
                
                if (!autoRefreshEnabled) {
                    showToast('System information updated', 'success');
                }
            } else {
                showToast('Error updating system info', 'danger');
            }
        })
        .catch(error => {
            console.error('Error fetching system info:', error);
            showToast('Failed to update system info', 'danger');
        })
        .finally(() => {
            // Restore buttons
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> ' + getTranslation('system_info.refresh');
            }
            if (refreshBtnMobile) {
                refreshBtnMobile.disabled = false;
                refreshBtnMobile.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
            }
        });
    }

    /**
     * Update all fields with new data
     */
    function updateFields(data) {
        // Update all elements with data-field attributes
        document.querySelectorAll('[data-field]').forEach(element => {
            const field = element.getAttribute('data-field');
            const value = getNestedValue(data, field);
            
            if (value !== undefined && value !== null) {
                element.textContent = value;
            }
        });

        // Update disk usage progress bar
        const diskProgressBar = document.getElementById('diskProgressBar');
        if (diskProgressBar && data.disk) {
            const usage = parseFloat(data.disk.usage_percent);
            diskProgressBar.style.width = usage + '%';
            diskProgressBar.setAttribute('aria-valuenow', usage);
            
            // Update color based on usage
            diskProgressBar.className = 'progress-bar';
            if (usage > 90) {
                diskProgressBar.classList.add('bg-danger');
            } else if (usage > 75) {
                diskProgressBar.classList.add('bg-warning');
            } else {
                diskProgressBar.classList.add('bg-success');
            }
        }

        // Update environment badge color
        const envBadge = document.getElementById('envBadge');
        if (envBadge && data.app) {
            envBadge.className = 'badge';
            envBadge.classList.add(data.app.environment === 'production' ? 'bg-success' : 'bg-warning');
        }
    }

    /**
     * Get nested object value by dot notation
     */
    function getNestedValue(obj, path) {
        return path.split('.').reduce((current, prop) => current?.[prop], obj);
    }

    /**
     * Get translation (fallback to key if not found)
     */
    function getTranslation(key) {
        const translations = {
            'system_info.auto_refresh': 'Auto-Refresh',
            'system_info.auto_refresh_enabled': 'Enabled (30s)',
            'system_info.auto_refresh_disabled': 'Disabled',
            'system_info.refresh': 'Refresh',
            'system_info.last_updated': 'Last Updated'
        };
        return translations[key] || key;
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }
});