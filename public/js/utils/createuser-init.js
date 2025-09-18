/**
 * Create User Initialization Script
 * Ersetzt inline Scripts aus admin/createUser.latte für CSP-Konformität
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 CreateUser initialization started');
    
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
        console.log('✅ CreateUser messages loaded:', window.messages);
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
            console.log('📱 Back button clicked - navigating back');
            
            // Sicherheitscheck: Nur wenn es eine History gibt
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Fallback: Zur User-Liste navigieren
                window.location.href = '/admin/users';
            }
        });
        console.log('✅ Back button event listener attached');
    } else {
        console.warn('⚠️ Back button element not found');
    }
    
    console.log('✅ CreateUser initialization completed');
});