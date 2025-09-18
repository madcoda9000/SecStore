/**
 * Roles Initialization Script
 * Ersetzt inline Scripts aus admin/roles.latte für CSP-Konformität
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Roles initialization started');
    
    // =============================================
    // SCHRITT 1: Messages aus data-Attributen laden
    // =============================================
    const messagesElement = document.getElementById('roles-messages');
    if (messagesElement) {
        // Messages global verfügbar machen (für roles.latte-min.js)
        window.messages = {
            msg1: messagesElement.getAttribute('data-msg1'),
            msg2: messagesElement.getAttribute('data-msg2'),
            msg3: messagesElement.getAttribute('data-msg3'),
            msg4: messagesElement.getAttribute('data-msg4'),
            msg5: messagesElement.getAttribute('data-msg5'),
            msg6: messagesElement.getAttribute('data-msg6'),
            msg7: messagesElement.getAttribute('data-msg7'),
            msg8: messagesElement.getAttribute('data-msg8'),
            msg9: messagesElement.getAttribute('data-msg9'),
            msg10: messagesElement.getAttribute('data-msg10'),
            msg11: messagesElement.getAttribute('data-msg11')
        };
        console.log('✅ Roles messages loaded:', window.messages);
    } else {
        console.warn('⚠️ Roles messages element not found');
    }
    
    // =============================================
    // SCHRITT 2: fetchRoles() initialisieren
    // Ersetzt den inline fetchRoles() Aufruf
    // =============================================
    if (typeof fetchRoles === 'function') {
        console.log('📊 Initializing roles data fetch...');
        fetchRoles();
        console.log('✅ Roles initialization completed');
    } else {
        console.error('❌ fetchRoles function not found! Ensure roles.latte-min.js is loaded first.');
    }
});