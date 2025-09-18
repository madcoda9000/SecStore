/**
 * Logs Initialization Script
 * Universelle Lösung für ALLE admin/logs*.latte Templates
 * Ersetzt alle inline Scripts für CSP-Konformität
 * Datei: public/js/utils/logs-init.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Logs initialization started');
    
    // =============================================
    // SCHRITT 1: Messages aus data-Attributen laden
    // =============================================
    const messagesElement = document.getElementById('log-messages');
    if (messagesElement) {
        // Messages global verfügbar machen (für logs*.latte-min.js)
        window.messages = {
            msg4: messagesElement.getAttribute('data-msg4'),
            msg5: messagesElement.getAttribute('data-msg5'),
            msg6: messagesElement.getAttribute('data-msg6')
        };
        console.log('✅ Log messages loaded:', window.messages);
    } else {
        console.warn('⚠️ Log messages element not found');
    }
    
    // =============================================
    // SCHRITT 2: Log-Konfiguration laden
    // =============================================
    const configElement = document.getElementById('log-config');
    if (configElement) {
        window.logConfig = {
            type: configElement.getAttribute('data-type'),
            fetchUrl: configElement.getAttribute('data-fetch-url'),
            autoFetch: configElement.getAttribute('data-auto-fetch') === 'true'
        };
        console.log('✅ Log config loaded:', window.logConfig);
    } else {
        console.warn('⚠️ Log config element not found');
    }
    
    // =============================================
    // SCHRITT 3: Automatisches fetchLogs() aufrufen
    // Ersetzt das inline <script>fetchLogs();</script>
    // =============================================
    function performAutoFetch() {
        // Prüfen ob fetchLogs() Funktion verfügbar ist
        if (typeof fetchLogs === 'function') {
            console.log('🔄 Calling fetchLogs() automatically...');
            
            try {
                fetchLogs();
                console.log('✅ fetchLogs() executed successfully');
            } catch (error) {
                console.error('❌ Error calling fetchLogs():', error);
                showErrorMessage('Failed to load log data. Please refresh the page.');
            }
        } else {
            console.warn('⚠️ fetchLogs() function not found. Make sure the corresponding logs*.latte-min.js is loaded.');
            
            // Retry nach kurzer Verzögerung (manchmal laden Scripts asynchron)
            setTimeout(() => {
                if (typeof fetchLogs === 'function') {
                    console.log('🔄 Retrying fetchLogs() after delay...');
                    fetchLogs();
                } else {
                    console.error('❌ fetchLogs() still not available after retry');
                    showErrorMessage('Log functionality not available. Please check if all scripts are loaded.');
                }
            }, 500);
        }
    }
    
    // Auto-fetch nur wenn konfiguriert
    if (window.logConfig?.autoFetch !== false) {
        performAutoFetch();
    }
    
    // =============================================
    // SCHRITT 4: Error Message Function
    // =============================================
    function showErrorMessage(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
        `;
        alertDiv.innerHTML = `
            <strong>Error:</strong> ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-remove nach 10 Sekunden
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 10000);
    }
    
    // =============================================
    // SCHRITT 5: Enhanced Error Handling
    // Überwacht Fehler in den Log-Funktionen
    // =============================================
    
    // Globale Fetch-Error Handler
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args)
            .catch(error => {
                console.error('❌ Fetch error in logs:', error);
                
                // Prüfen ob es ein Log-related Fetch ist
                const url = args[0];
                if (typeof url === 'string' && url.includes('/admin/logs/fetch')) {
                    showErrorMessage('Failed to fetch log data. Please check your connection.');
                }
                
                throw error; // Re-throw für normale Error-Behandlung
            });
    };
    
    // =============================================
    // SCHRITT 6: Utility Functions für Log-Templates
    // =============================================
    
    // Refresh-Funktion für alle Log-Templates
    window.refreshLogs = function() {
        console.log('🔄 Manual refresh triggered');
        if (typeof fetchLogs === 'function') {
            fetchLogs();
        } else {
            console.warn('⚠️ Cannot refresh: fetchLogs() not available');
            showErrorMessage('Refresh not available. Please reload the page.');
        }
    };
    
    // Search-Reset Funktion
    window.resetLogSearch = function() {
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.value = '';
            console.log('🔍 Search reset');
            if (typeof fetchLogs === 'function') {
                fetchLogs();
            }
        }
    };
    
    // =============================================
    // SCHRITT 7: Debug-Modus für Entwicklung
    // =============================================
    if (window.location.search.includes('debug=1')) {
        console.log('=== LOG PAGE DEBUG INFO ===');
        console.log('Messages available:', !!window.messages);
        console.log('Messages object:', window.messages);
        console.log('Config available:', !!window.logConfig);
        console.log('Config object:', window.logConfig);
        console.log('fetchLogs function available:', typeof fetchLogs === 'function');
        console.log('Current page type:', window.logConfig?.type || 'unknown');
        console.log('Auto-fetch enabled:', window.logConfig?.autoFetch !== false);
        console.log('Fetch URL:', window.logConfig?.fetchUrl);
        
        // Debug-Tools global verfügbar machen
        window.logDebug = {
            refreshLogs: window.refreshLogs,
            resetSearch: window.resetLogSearch,
            config: window.logConfig,
            messages: window.messages,
            testFetch: () => {
                if (window.logConfig?.fetchUrl) {
                    console.log('Testing fetch to:', window.logConfig.fetchUrl);
                    fetch(window.logConfig.fetchUrl + '?search=&pageSize=10&page=1')
                        .then(r => r.json())
                        .then(d => console.log('Test fetch result:', d))
                        .catch(e => console.error('Test fetch error:', e));
                }
            }
        };
        
        console.log('Debug tools available at: window.logDebug');
        console.log('=========================');
    }
    
    console.log('✅ Logs initialization completed successfully');
});