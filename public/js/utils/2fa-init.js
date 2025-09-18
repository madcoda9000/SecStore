/**
 * 2FA Setup Initialization Script
 * Ersetzt inline Script aus enable_2fa.latte für CSP-Konformität
 * Datei: public/js/utils/2fa-init.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔐 2FA initialization started');
    
    // =============================================
    // SCHRITT 1: Messages aus data-Attributen laden
    // =============================================
    const messagesElement = document.getElementById('2fa-messages');
    if (messagesElement) {
        // Messages global verfügbar machen (für enable_2fa.latte-min.js)
        window.messages = {
            error1: messagesElement.getAttribute('data-error1')
        };
        console.log('✅ 2FA messages loaded:', window.messages);
    } else {
        console.warn('⚠️ 2FA messages element not found');
    }
    
    // =============================================
    // SCHRITT 2: Debug-Info für Entwicklung
    // =============================================
    if (window.location.search.includes('debug=1')) {
        console.log('=== 2FA SETUP DEBUG INFO ===');
        console.log('Messages available:', !!window.messages);
        console.log('Messages object:', window.messages);
        console.log('enable_2fa.latte-min.js loaded:', typeof window.messages !== 'undefined');
        console.log('Copy clipboard element found:', !!document.getElementById('cpclpd'));
        console.log('MFA secret element found:', !!document.getElementById('mfasec'));
        console.log('============================');
    }
    
    console.log('✅ 2FA initialization completed successfully');
});