/**
 * 2FA Setup Initialization Script
 * Ersetzt inline Script aus enable_2fa.latte für CSP-Konformität
 * Datei: public/js/utils/2fa-init.js
 */

document.addEventListener('DOMContentLoaded', function() {
        
    // =============================================
    // SCHRITT 1: Messages aus data-Attributen laden
    // =============================================
    const messagesElement = document.getElementById('2fa-messages');
    if (messagesElement) {
        // Messages global verfügbar machen (für enable_2fa.latte-min.js)
        window.messages = {
            error1: messagesElement.getAttribute('data-error1')
        };
    }
});