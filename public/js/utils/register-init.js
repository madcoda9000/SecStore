/**
 * Register Initialization Script
 * Ersetzt alle inline Scripts aus register.latte
 * Datei: public/js/utils/register-init.js
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // SCHRITT 1: Messages aus data-Attributen laden
    const messagesElement = document.getElementById('register-messages');
    if (messagesElement) {
        // Messages global verfügbar machen (für register.latte-min.js)
        window.messages = {
            val1: messagesElement.getAttribute('data-val1'),
            val2: messagesElement.getAttribute('data-val2'), 
            val3: messagesElement.getAttribute('data-val3'),
            val4: messagesElement.getAttribute('data-val4'),
            val5: messagesElement.getAttribute('data-val5'),
            val6: messagesElement.getAttribute('data-val6')
        };
        
        console.log('Register messages loaded successfully');
    } else {
        console.warn('Register messages element not found');
    }
    
    // SCHRITT 2: Debug-Info für Entwicklung
    if (window.location.search.includes('debug=1')) {
        console.log('=== REGISTER PAGE DEBUG INFO ===');
        console.log('Messages available:', !!window.messages);
        console.log('Messages object:', window.messages);
        console.log('register.latte-min.js loaded:', typeof window.messages !== 'undefined');
    }
});