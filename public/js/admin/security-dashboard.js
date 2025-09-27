/**
 * Security Dashboard JavaScript
 * Ersetzt ALLE inline Scripts aus admin/security_dashboard.latte
 * Datei: public/js/admin/security-dashboard.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🛡️ Security Dashboard initialization started');
    
    // =============================================
    // SCHRITT 1: Konfiguration aus data-Attributen laden
    // =============================================
    const configElement = document.getElementById('security-dashboard-config');
    let config = {
        refreshUrl: '/admin/security/metrics',
        refreshInterval: 300000  // 5 Minuten Default
    };
    
    if (configElement) {
        config.refreshUrl = configElement.getAttribute('data-refresh-url') || config.refreshUrl;
        config.refreshInterval = parseInt(configElement.getAttribute('data-refresh-interval')) || config.refreshInterval;
        console.log('✅ Dashboard config loaded:', config);
    }
    
    // =============================================
    // SCHRITT 2: Refresh Metrics Function
    // Repliziert die ursprüngliche refreshMetrics() Funktion
    // =============================================
    function refreshMetrics() {
        console.log('🔄 Refreshing security metrics...');
        
        fetch(config.refreshUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Metrics refreshed successfully');
                // Seite neu laden um die neuen Daten anzuzeigen
                location.reload();
            })
            .catch(error => {
                console.error('❌ Error refreshing metrics:', error);
                // Benutzer über Fehler informieren
                showErrorMessage('Failed to refresh security metrics. Please try again.');
            });
    }
    
    // =============================================
    // SCHRITT 3: Error Message Function
    // =============================================
    function showErrorMessage(message) {
        // Erstelle eine temporäre Toast-Nachricht
        const toast = document.createElement('div');
        toast.className = 'alert alert-danger alert-dismissible fade show position-fixed';
        toast.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        `;
        toast.innerHTML = `
            <strong>Error:</strong> ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove nach 5 Sekunden
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
    
    // =============================================
    // SCHRITT 4: Refresh Button Event Listener
    // =============================================
    const refreshBtn = document.getElementById('refreshMetricsBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Button-Zustand ändern
            const originalText = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refreshing...';
            refreshBtn.disabled = true;
            
            // Metrics aktualisieren
            refreshMetrics();
            
            // Button nach kurzer Zeit wieder aktivieren (falls reload nicht funktioniert)
            setTimeout(() => {
                refreshBtn.innerHTML = originalText;
                refreshBtn.disabled = false;
            }, 3000);
        });
        console.log('✅ Refresh button event listener attached');
    }
    
    // =============================================
    // SCHRITT 5: Auto-Refresh Timer
    // Repliziert das ursprüngliche setInterval
    // =============================================
    let autoRefreshTimer;
    
    function startAutoRefresh() {
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
        }
        
        autoRefreshTimer = setInterval(() => {
            console.log('🔄 Auto-refreshing metrics (every ' + (config.refreshInterval / 1000 / 60) + ' minutes)');
            refreshMetrics();
        }, config.refreshInterval);
        
        console.log('✅ Auto-refresh timer started (interval: ' + (config.refreshInterval / 1000 / 60) + ' minutes)');
    }
    
    // Auto-refresh starten
    startAutoRefresh();
    
    // =============================================
    // SCHRITT 6: Timestamp hinzufügen
    // Repliziert die ursprüngliche Timestamp-Logik
    // =============================================
    function addTimestamp() {
        try {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const h1 = document.querySelector('h1');
            
            if (h1) {
                // Prüfen ob bereits ein Timestamp existiert
                const existingTimestamp = h1.querySelector('.dashboard-timestamp');
                if (existingTimestamp) {
                    existingTimestamp.remove();
                }
                
                const timestamp = document.createElement('small');
                timestamp.className = 'text-muted ms-2 dashboard-timestamp';
                timestamp.textContent = '(Updated: ' + timeString + ')';
                h1.appendChild(timestamp);
                
                console.log('✅ Timestamp added:', timeString);
            }
        } catch (e) {
            console.log('⚠️ Error adding timestamp:', e);
        }
    }
    
    // Timestamp beim Laden hinzufügen
    addTimestamp();
    
    // =============================================
    // SCHRITT 7: Cleanup bei Seitenwechsel
    // =============================================
    window.addEventListener('beforeunload', function() {
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
            console.log('🧹 Auto-refresh timer cleared');
        }
    });
    
    // =============================================
    // SCHRITT 8: Debug-Info
    // =============================================
    console.log(window.location.search);
    if (window.location.search.includes('debug=1')) {
        console.log('=== SECURITY DASHBOARD DEBUG INFO ===');
        console.log('Config:', config);
        console.log('Refresh button found:', !!refreshBtn);
        console.log('Auto-refresh active:', !!autoRefreshTimer);
        console.log('Timestamp added:', !!document.querySelector('.dashboard-timestamp'));
        console.log('=====================================');
    }
    
    // Global verfügbar machen für Console-Testing
    window.securityDashboard = {
        refreshMetrics,
        config,
        startAutoRefresh,
        addTimestamp
    };
    
    console.log('✅ Security Dashboard initialization completed');
});