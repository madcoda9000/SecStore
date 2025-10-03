/**
 * Profile Backup Codes Management Script
 * Handles displaying, regenerating, and managing backup codes in user profile
 * File: public/js/profile-backup-codes.js
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const countElement = document.getElementById('backup-codes-count');
    const regenerateButton = document.getElementById('regenerate-backup-codes');
    const warningElement = document.getElementById('backup-codes-warning');
    const warningText = document.getElementById('backup-codes-warning-text')  || 'You only have {count} backup codes left';
    const translationsElement = document.getElementById('backup-codes-translations');
    const modal = new bootstrap.Modal(document.getElementById('backupCodesModal'));
    const modalDisplay = document.getElementById('modal-backup-codes-display');
    
    // Only execute if we're on profile page with backup codes section
    if (!countElement) {
        return;
    }
    
    /**
     * Load backup codes count on page load
     */
    loadBackupCodesCount();
    
    /**
     * Fetch and display backup codes count
     */
    function loadBackupCodesCount() {
        fetch('/getBackupCodesCount', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCountDisplay(data.count);
            } else {
                countElement.innerHTML = '<span class="text-danger">Error loading count</span>';
            }
        })
        .catch(error => {
            console.error('Error fetching backup codes count:', error);
            countElement.innerHTML = '<span class="text-danger">Error loading count</span>';
        });
    }
    
    /**
     * Update the count display
     */
    function updateCountDisplay(count) {
        const translations = translationsElement || {};
        
        if (count === 0) {
            countElement.innerHTML = `<span class="badge bg-danger">${count} ${translations.getAttribute('data-none') || 'codes'}</span>`;
            
            // Show warning
            if (warningElement && warningText) {
                warningText.textContent = translations.getAttribute('data-none') || 'No backup codes available';
                warningElement.classList.remove('d-none');
                warningElement.classList.add('alert-danger');
                warningElement.classList.remove('alert-warning');
            }
        } else if (count <= 3) {
            countElement.innerHTML = `<span class="badge bg-warning text-dark">${count} codes</span>`;
            
            // Show low warning
            if (warningElement && warningText && translations) {
                const warningMessage = translations.getAttribute('data-warning-low') || 'You only have {count} backup codes left';
                warningText.textContent = warningMessage.replace('{count}', count);
                warningElement.classList.remove('d-none');
                warningElement.classList.add('alert-warning');
                warningElement.classList.remove('alert-danger');
            }
        } else {
            countElement.innerHTML = `<span class="badge bg-success">${count} codes</span>`;
            
            // Hide warning
            if (warningElement) {
                warningElement.classList.add('d-none');
            }
        }
    }
    
    /**
     * Regenerate backup codes
     */
    if (regenerateButton) {
        regenerateButton.addEventListener('click', function() {
            const translations = translationsElement || {};
            const confirmMessage = translations.getAttribute('data-regenerate-confirm') || 
                'Do you really want to generate new backup codes? All old codes will become invalid!';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Disable button and show loading
            regenerateButton.disabled = true;
            const originalText = regenerateButton.innerHTML;
            regenerateButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
            
            // Get CSRF token
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            
            fetch('/regenerateBackupCodes', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.codes) {
                    // Format codes for display
                    const formattedCodes = data.codes.map((code, index) => {
                        return `${String(index + 1).padStart(2, ' ')}. ${code}`;
                    }).join('\n');
                    
                    // Show codes in modal
                    modalDisplay.textContent = formattedCodes;
                    modal.show();
                    
                    // Update count
                    updateCountDisplay(data.codes.length);
                    
                    // Show success toast if available
                    if (typeof showToast === 'function') {
                        const successMessage = translations.getAttribute('data-regenerate-success') || 
                            'New backup codes generated successfully';
                        showToast(successMessage, 'success', 'Success');
                    }
                } else {
                    alert(data.message || (translations.getAttribute('data-regenerate-error') || 'Error generating backup codes'));
                }
            })
            .catch(error => {
                console.error('Error regenerating backup codes:', error);
                alert(translations.getAttribute('data-regenerate-error') || 'Error generating backup codes');
            })
            .finally(() => {
                // Re-enable button
                regenerateButton.disabled = false;
                regenerateButton.innerHTML = originalText;
            });
        });
    }
    
    /**
     * Modal: Copy codes to clipboard
     */
    const modalCopyButton = document.getElementById('modal-copy-codes');
    if (modalCopyButton && modalDisplay) {
        modalCopyButton.addEventListener('click', function() {
            const codesText = modalDisplay.textContent;
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(codesText)
                    .then(() => {
                        showCopySuccess(modalCopyButton);
                    })
                    .catch(err => {
                        console.error('Failed to copy:', err);
                        fallbackCopy(codesText, modalCopyButton);
                    });
            } else {
                fallbackCopy(codesText, modalCopyButton);
            }
        });
    }
    
    /**
     * Modal: Download codes
     */
    const modalDownloadButton = document.getElementById('modal-download-codes');
    if (modalDownloadButton && modalDisplay) {
        modalDownloadButton.addEventListener('click', function() {
            const codesText = modalDisplay.textContent;
            const filename = '2FA_Backup_Codes_' + new Date().toISOString().split('T')[0] + '.txt';
            
            const fileContent = '2FA BACKUP CODES\n' +
                               'Generated: ' + new Date().toLocaleString() + '\n' +
                               '=====================================\n\n' +
                               codesText + '\n\n' +
                               '⚠️ Keep these codes in a secure location!\n' +
                               'Each code can only be used once.\n';
            
            const blob = new Blob([fileContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        });
    }
    
    /**
     * Modal: Print codes
     */
    const modalPrintButton = document.getElementById('modal-print-codes');
    if (modalPrintButton && modalDisplay) {
        modalPrintButton.addEventListener('click', function() {
            const codesText = modalDisplay.textContent;
            
            const printWindow = window.open('', '_blank', 'width=600,height=400');
            
            if (printWindow) {
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>2FA Backup Codes</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
                            h1 { border-bottom: 2px solid #333; padding-bottom: 10px; }
                            .codes { font-family: 'Courier New', monospace; font-size: 14px; background: #f5f5f5; padding: 15px; border: 1px solid #ddd; white-space: pre-wrap; }
                            .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin-top: 20px; border-radius: 4px; }
                        </style>
                    </head>
                    <body>
                        <h1>🔐 2FA Backup Codes</h1>
                        <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                        <div class="codes">${escapeHtml(codesText)}</div>
                        <div class="warning">
                            <strong>⚠️ Important:</strong>
                            <ul>
                                <li>Keep these codes in a secure location</li>
                                <li>Each code can only be used once</li>
                                <li>Use them when you don't have access to your authenticator device</li>
                            </ul>
                        </div>
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
                printWindow.onload = function() {
                    printWindow.focus();
                    printWindow.print();
                };
            }
        });
    }
    
    /**
     * Helper functions
     */
    function showCopySuccess(button) {
        const originalText = button.innerHTML;
        const translations = translationsElement || {};
        const successMessage = translations.getAttribute('data-codes-copied') || 'Copied!';
        
        button.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + successMessage;
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-primary');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 2000);
    }
    
    function fallbackCopy(text, button) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        if(textarea) {
            let err = textarea;
        }
        
        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            console.error('Fallback copy failed:', err);
            alert('Failed to copy to clipboard. Please copy manually.');
        }
        
        document.body.removeChild(textarea);
    }

    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});