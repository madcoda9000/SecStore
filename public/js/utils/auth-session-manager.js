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
        
        // sessionTimeout verarbeiten
        this.sessionTimeout = parseInt(sessionTimeout) || 0;
        
        // Initialization starten
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
        
        // Timing-Berechnungen
        const refreshTime = Math.max(this.sessionTimeout - this.options.refreshBuffer, this.options.minWaitTime);
        const warningTime = Math.max(refreshTime - this.options.warningTime, 30);
        
        // SCHRITT 9: Warnung planen (falls aktiviert)
        if (this.options.showWarning && warningTime < refreshTime) {
            setTimeout(() => {
                this.showWarning(this.options.warningTime);
            }, warningTime * 1000);
        } 
        
        // Refresh planen
        setTimeout(() => {
            this.performRefresh();
        }, refreshTime * 1000);
    }
    
    showWarning(countdownSeconds) {               
        // Warning-Element erstellen
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
        
        // SCHRITT 13: Countdown starten
        this.startCountdown(countdownSeconds);
        
        // SCHRITT 14: Auto-Entfernung planen
        setTimeout(() => {
            this.debugLog('🗑️ Auto-removing warning after countdown');
            this.dismissWarning();
        }, countdownSeconds * 1000);
    }
    
    startCountdown(seconds) {
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
                clearInterval(interval);
            }
        }, 1000);
    }
    
    dismissWarning() {
        const warning = document.getElementById('session-warning');
        if (warning) {
            warning.remove();
        } 
    }
    
    performRefresh() {        
        // Kurze Verzögerung für Log-Ausgabe
        setTimeout(() => {
            window.location.reload(1);
        }, 100);
    }
}

// SCHRITT 18: Globale Instanz für externe Verwendung
window.AuthSessionManager = AuthSessionManager;
