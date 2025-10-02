/**
 * Backup Codes Handler Script
 * Handles copying, downloading, and printing of 2FA backup codes
 * File: public/js/utils/backup-codes-handler.js
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Get elements
    const copyButton = document.getElementById('copyBackupCodes');
    const downloadButton = document.getElementById('downloadBackupCodes');
    const printButton = document.getElementById('printBackupCodes');
    const codesDisplay = document.getElementById('backupCodesDisplay');
    const messagesElement = document.getElementById('2fa-messages');
    
    // Only execute if we're on a page with backup codes
    if (!codesDisplay) {
        return;
    }
    
    /**
     * Copy backup codes to clipboard
     */
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const codesText = codesDisplay.textContent;
            
            // Modern clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(codesText)
                    .then(() => {
                        showCopySuccess();
                    })
                    .catch(err => {
                        console.error('Failed to copy:', err);
                        fallbackCopy(codesText);
                    });
            } else {
                fallbackCopy(codesText);
            }
        });
    }
    
    /**
     * Download backup codes as text file
     */
    if (downloadButton) {
        downloadButton.addEventListener('click', function() {
            const codesText = codesDisplay.textContent;
            const filename = '2FA_Backup_Codes_' + new Date().toISOString().split('T')[0] + '.txt';
            
            // Add header to file
            const fileContent = '2FA BACKUP CODES\n' +
                               'Generated: ' + new Date().toLocaleString() + '\n' +
                               '=====================================\n\n' +
                               codesText + '\n\n' +
                               '⚠️ Keep these codes in a secure location!\n' +
                               'Each code can only be used once.\n';
            
            // Create blob and download
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
     * Print backup codes
     */
    if (printButton) {
        printButton.addEventListener('click', function() {
            const codesText = codesDisplay.textContent;
            
            // Create print window
            const printWindow = window.open('', '_blank', 'width=600,height=400');
            
            if (printWindow) {
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>2FA Backup Codes</title>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                padding: 20px;
                                max-width: 600px;
                                margin: 0 auto;
                            }
                            h1 {
                                border-bottom: 2px solid #333;
                                padding-bottom: 10px;
                            }
                            .codes {
                                font-family: 'Courier New', monospace;
                                font-size: 14px;
                                background: #f5f5f5;
                                padding: 15px;
                                border: 1px solid #ddd;
                                white-space: pre-wrap;
                            }
                            .warning {
                                background: #fff3cd;
                                border: 1px solid #ffc107;
                                padding: 10px;
                                margin-top: 20px;
                                border-radius: 4px;
                            }
                            @media print {
                                body { padding: 0; }
                                .warning { page-break-after: avoid; }
                            }
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
                
                // Wait for content to load, then print
                printWindow.onload = function() {
                    printWindow.focus();
                    printWindow.print();
                };
            }
        });
    }
    
    /**
     * Show copy success feedback
     */
    function showCopySuccess() {
        const originalText = copyButton.innerHTML;
        const successMessage = messagesElement ? 
            messagesElement.getAttribute('data-codes-copied') : 
            'Copied!';
        
        copyButton.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + successMessage;
        copyButton.classList.add('btn-success');
        copyButton.classList.remove('btn-outline-primary');
        
        setTimeout(() => {
            copyButton.innerHTML = originalText;
            copyButton.classList.remove('btn-success');
            copyButton.classList.add('btn-outline-primary');
        }, 2000);
    }
    
    /**
     * Fallback copy method for older browsers
     */
    function fallbackCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        
        try {
            document.execCommand('copy');
            showCopySuccess();
        } catch (err) {
            console.error('Fallback copy failed:', err);
            alert('Failed to copy to clipboard. Please copy manually.');
        }
        
        document.body.removeChild(textarea);
    }
    
    /**
     * Escape HTML for safe printing
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});