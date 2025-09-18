/**
 * Verbesserter AuthSessionManager mit optionalem Debug-Logging
 * Datei: public/js/utils/auth-session-manager.js
 */

class AuthSessionManager {
    constructor(sessionTimeout, options = {}) {
        // SCHRITT 1: Debug-Option hinzufügen und standardmäßig auf false setzen
        this.options = {
            refreshBuffer: 30,        // Sekunden vor Ablauf refreshen
            warningTime: 10,          // Sekunden Warnung vor Refresh
            minWaitTime: 60,          // Mindestens X Sekunden warten
            showWarning: true,        // Warnung anzeigen oder nicht
            logActivity: true,        // DEPRECATED: Wird durch debugLog ersetzt
            debugLog: false,          // NEU: Debug-Logging aktivieren/deaktivieren
            ...options
        };
        
        // SCHRITT 2: sessionTimeout verarbeiten
        this.sessionTimeout = parseInt(sessionTimeout) || 0;
        
        // SCHRITT 3: Debug-Logging für Constructor
        this.debugLog('🔧 AuthSessionManager constructor called');
        this.debugLog('📊 sessionTimeout received:', sessionTimeout, typeof sessionTimeout);
        this.debugLog('⚙️ options received:', options);
        this.debugLog('✅ AuthSessionManager initialized with:');
        this.debugLog('   sessionTimeout:', this.sessionTimeout);
        this.debugLog('   options:', this.options);
        
        // SCHRITT 4: Initialization starten
        this.init();
    }
    
    /**
     * SCHRITT 5: Zentrale Debug-Log Methode
     * Alle Console-Logs gehen durch diese Methode
     */
    debugLog(...args) {
        if (this.options.debugLog === true) {
            console.log(...args);
        }
    }
    
    /**
     * SCHRITT 6: Debug-Warn Methode für Warnungen
     */
    debugWarn(...args) {
        if (this.options.debugLog === true) {
            console.warn(...args);
        }
    }
    
    init() {
        this.debugLog('🚀 AuthSessionManager.init() started');
        
        // SCHRITT 7: Prüfung ob Session-Timeout gültig ist
        if (this.sessionTimeout <= 0) {
            this.debugWarn('⚠️ Session timeout is 0 or negative - no auto refresh');
            this.debugLog('   sessionTimeout value:', this.sessionTimeout);
            return;
        }
        
        // SCHRITT 8: Timing-Berechnungen
        const refreshTime = Math.max(this.sessionTimeout - this.options.refreshBuffer, this.options.minWaitTime);
        const warningTime = Math.max(refreshTime - this.options.warningTime, 30);
        
        this.debugLog('⏰ Timing calculations:');
        this.debugLog('   Original session timeout:', this.sessionTimeout + 's');
        this.debugLog('   Refresh buffer:', this.options.refreshBuffer + 's');
        this.debugLog('   Calculated refresh time:', refreshTime + 's');
        this.debugLog('   Warning time:', warningTime + 's');
        this.debugLog('   Warning will show in:', warningTime + 's');
        this.debugLog('   Page will refresh in:', refreshTime + 's');
        
        // SCHRITT 9: Warnung planen (falls aktiviert)
        if (this.options.showWarning && warningTime < refreshTime) {
            this.debugLog('📅 Scheduling warning for ' + warningTime + 's from now');
            setTimeout(() => {
                this.debugLog('⚠️ Showing session warning now');
                this.showWarning(this.options.warningTime);
            }, warningTime * 1000);
        } else {
            this.debugLog('❌ Warning not scheduled (showWarning=' + this.options.showWarning + ', warningTime=' + warningTime + ', refreshTime=' + refreshTime + ')');
        }
        
        // SCHRITT 10: Refresh planen
        this.debugLog('📅 Scheduling page refresh for ' + refreshTime + 's from now');
        setTimeout(() => {
            this.debugLog('🔄 Performing page refresh now');
            this.performRefresh();
        }, refreshTime * 1000);
        
        this.debugLog('✅ AuthSessionManager.init() completed successfully');
    }
    
    showWarning(countdownSeconds) {
        this.debugLog('⚠️ showWarning() called with countdown:', countdownSeconds);
        
        // SCHRITT 11: Prüfen ob bereits eine Warnung existiert
        if (document.getElementById('session-warning')) {
            this.debugLog('⚠️ Warning already exists, skipping');
            return;
        }
        
        // SCHRITT 12: Warning-Element erstellen
        const alertDiv = document.createElement('div');
        alertDiv.id = 'session-warning';
        alertDiv.className = 'alert alert-info alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = `
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            min-width: 350px;
            max-width: 90%;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: none;
        `;
        
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center justify-content-center">
                <i class="bi bi-clock me-2"></i>
                <span>Page refreshes in <strong><span id="session-countdown">${countdownSeconds}</span>s</strong> to maintain security</span>
                <button type="button" class="btn-close ms-3" onclick="window.authSessionManager.dismissWarning()"></button>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        this.debugLog('✅ Warning alert added to DOM');
        
        // SCHRITT 13: Countdown starten
        this.startCountdown(countdownSeconds);
        
        // SCHRITT 14: Auto-Entfernung planen
        setTimeout(() => {
            this.debugLog('🗑️ Auto-removing warning after countdown');
            this.dismissWarning();
        }, countdownSeconds * 1000);
    }
    
    startCountdown(seconds) {
        this.debugLog('⏱️ Starting countdown from', seconds);
        const countdownElement = document.getElementById('session-countdown');
        let remaining = seconds;
        
        const interval = setInterval(() => {
            remaining--;
            this.debugLog('⏱️ Countdown:', remaining);
            if (countdownElement) {
                countdownElement.textContent = remaining;
                
                // SCHRITT 15: Farbe ändern wenn nur noch wenig Zeit
                if (remaining <= 3) {
                    countdownElement.style.color = '#dc3545'; // Bootstrap danger
                }
            }
            
            if (remaining <= 0) {
                this.debugLog('⏱️ Countdown finished');
                clearInterval(interval);
            }
        }, 1000);
    }
    
    dismissWarning() {
        this.debugLog('🗑️ dismissWarning() called');
        const warning = document.getElementById('session-warning');
        if (warning) {
            warning.remove();
            this.debugLog('✅ Warning removed from DOM');
        } else {
            this.debugLog('⚠️ No warning found to dismiss');
        }
    }
    
    performRefresh() {
        this.debugLog('🔄 performRefresh() called');
        
        // SCHRITT 16: Backwards-Kompatibilität für logActivity option
        if (this.options.logActivity || this.options.debugLog) {
            this.debugLog('🔄 Refreshing page to prevent session expiry...');
        }
        
        // SCHRITT 17: Kurze Verzögerung für Log-Ausgabe
        setTimeout(() => {
            window.location.reload(1);
        }, 100);
    }
}

// SCHRITT 18: Globale Instanz für externe Verwendung
window.AuthSessionManager = AuthSessionManager;

// SCHRITT 19: Script-Loading nur bei Debug-Mode loggen
// Da wir hier noch keine Instanz haben, verwenden wir direktes console.log
// Diese Logs sind minimal und stören nicht

// DOM ready listener für zusätzliche Debug-Info
document.addEventListener('DOMContentLoaded', function() {
    // Nur loggen wenn eine Instanz mit debugLog existiert
    if (window.authSessionManager && window.authSessionManager.options.debugLog) {
        console.log('📄 DOM ready - AuthSessionManager ready for use');
    }
});