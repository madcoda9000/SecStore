/**
 * Create User Initialization Script
 * Ersetzt inline Scripts aus admin/createUser.latte für CSP-Konformität
 */

document.addEventListener('DOMContentLoaded', function() {    
    // =============================================
    // SCHRITT 1: Messages aus data-Attributen laden
    // =============================================
    const messagesElement = document.getElementById('createuser-messages');
    if (messagesElement) {
        // Messages global verfügbar machen (für createUser.latte-min.js)
        window.messages = {
            error1: messagesElement.getAttribute('data-error1'),
            error2: messagesElement.getAttribute('data-error2'),
            error3: messagesElement.getAttribute('data-error3'),
            error4: messagesElement.getAttribute('data-error4')
        };
    } else {
        console.warn('⚠️ CreateUser messages element not found');
    }
    
    // =============================================
    // SCHRITT 2: Back Button Event-Listener
    // Ersetzt onclick="history.back()"
    // =============================================
    const backBtn = document.getElementById('backBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Sicherheitscheck: Nur wenn es eine History gibt
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Fallback: Zur User-Liste navigieren
                window.location.href = '/admin/users';
            }
        });
    } else {
        console.warn('⚠️ Back button element not found');
    }
});