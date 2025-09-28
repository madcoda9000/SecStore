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
    } else {
        console.warn('Register messages element not found');
    }
});