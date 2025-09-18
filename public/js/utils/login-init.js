/**
 * Login Initialization Script
 * Ersetzt ALLE inline Scripts aus login.latte für CSP-Konformität
 * Datei: public/js/utils/login-init.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Login initialization started');
    
    // =============================================
    // SCHRITT 1: Messages aus data-Attributen laden
    // =============================================
    const messagesElement = document.getElementById('login-messages');
    if (messagesElement) {
        // Messages global verfügbar machen (für login.latte-min.js)
        window.messages = {
            val1: messagesElement.getAttribute('data-val1'),
            val2: messagesElement.getAttribute('data-val2'),
            val3: messagesElement.getAttribute('data-val3')
        };
        console.log('✅ Login messages loaded:', window.messages);
    } else {
        console.warn('⚠️ Login messages element not found');
    }
    
    // =============================================
    // SCHRITT 2: Session-Timeout initialisieren
    // Ersetzt das komplexe inline AuthSessionManager Script
    // =============================================
    const sessionElement = document.getElementById('login-session');
    if (sessionElement) {
        const sessionTimeout = parseInt(sessionElement.getAttribute('data-timeout'));
        
        if (sessionTimeout && sessionTimeout > 0) {
            console.log('📊 Session timeout received:', sessionTimeout + 's');
            
            // =============================================
            // SCHRITT 3: Script-Detection für Debug-Modus
            // Repliziert die ursprüngliche inline Logic
            // =============================================
            const scripts = document.querySelectorAll('script[src]');
            const hasMinifiedScript = Array.from(scripts).some(script => 
                script.src.includes('auth-session-manager-min.js')
            );
            
            const debugMode = !hasMinifiedScript;
            console.log('🔍 Script detection - Debug mode:', debugMode);
            
            // AuthSessionManager für Login-Seite initialisieren
            // Repliziert exakt die ursprüngliche inline Konfiguration
            window.authSessionManager = new AuthSessionManager(sessionTimeout, {
                refreshBuffer: 60,      // 60 Sekunden Puffer
                showWarning: true,      // Warnung anzeigen
                warningTime: 15,        // 15 Sekunden Warnung
                debugLog: debugMode     // Debug nur bei non-minified Version
            });
            
            console.log('✅ AuthSessionManager initialized with debug mode:', debugMode);
            
        } else {
            console.warn('⚠️ Invalid session timeout:', sessionTimeout);
        }
    } else {
        console.warn('⚠️ Login session element not found');
    }
    
    // =============================================
    // SCHRITT 4: Debug-Info für Entwicklung
    // =============================================
    if (window.location.search.includes('debug=1')) {
        console.log('=== LOGIN PAGE DEBUG INFO ===');
        console.log('Messages available:', !!window.messages);
        console.log('Messages object:', window.messages);
        console.log('AuthSessionManager available:', !!window.authSessionManager);
        console.log('AuthSessionManager config:', window.authSessionManager?.options);
        console.log('login.latte-min.js loaded:', typeof window.messages !== 'undefined');
        console.log('Current session timeout:', sessionElement?.getAttribute('data-timeout'));
        console.log('==============================');
    }
    
    console.log('✅ Login initialization completed successfully');
});