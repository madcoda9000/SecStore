/**
 * System Info Page JavaScript
 * CSP-compliant implementation
 */

document.addEventListener('DOMContentLoaded', function() {
    const refreshBtn = document.getElementById('refreshBtn');
    const showPhpInfoBtn = document.getElementById('showPhpInfoBtn');
    const phpInfoModal = new bootstrap.Modal(document.getElementById('phpInfoModal'));
    const phpInfoFrame = document.getElementById('phpInfoFrame');

    /**
     * Refresh page data
     */
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            location.reload();
        });
    }

    /**
     * Show PHP Info in modal
     */
    if (showPhpInfoBtn) {
        showPhpInfoBtn.addEventListener('click', function() {
            // Öffne Modal
            phpInfoModal.show();
            
            // Lade PHP-Info in iframe
            phpInfoFrame.src = '/admin/phpinfo';
        });
    }
});