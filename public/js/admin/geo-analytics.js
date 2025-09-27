/**
 * Geo-Analytics JavaScript - CSP-konform
 * File: public/js/admin/geo-analytics.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🌍 Geo-Analytics initialized');
    
    // Load data from data attributes
    const configElement = document.getElementById('geo-analytics-config');
    let geoData = {};
    let topCountriesData = {};
    
    if (configElement) {
        try {
            geoData = JSON.parse(configElement.getAttribute('data-geo-data') || '{}');
            topCountriesData = JSON.parse(configElement.getAttribute('data-top-countries') || '{}');
            console.log('✅ Geo-analytics data loaded from data attributes');
        } catch (e) {
            console.error('❌ Failed to parse geo-analytics data:', e);
            geoData = {};
            topCountriesData = {};
        }
    }
    
    // Initialize interactive features
    initializeTooltips();
    setupAutoRefresh();
    
    /**
     * Initialize tooltips for reason badges
     */
    function initializeTooltips() {
        const reasonBadges = document.querySelectorAll('[title]');
        reasonBadges.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                console.log('Suspicious reason:', this.getAttribute('title'));
            });
            
            // Enhanced tooltip display
            badge.addEventListener('mouseover', function() {
                const title = this.getAttribute('title');
                if (title) {
                    // Could implement custom tooltip here
                    this.setAttribute('data-bs-toggle', 'tooltip');
                    this.setAttribute('data-bs-placement', 'top');
                }
            });
        });
    }
    
    /**
     * Setup auto-refresh for geo data
     */
    function setupAutoRefresh() {
        // Auto-refresh Geo-Daten alle 10 Minuten
        const refreshInterval = 600000; // 10 minutes
        
        const refreshGeoData = function() {
            fetch('/admin/geo/data?type=countries&days=30', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('✅ Geo-data refreshed:', data.data);
                    
                    // Update UI with new data if needed
                    updateGeoTables(data.data);
                } else {
                    console.warn('⚠️ Geo-data refresh returned no success flag');
                }
            })
            .catch(error => {
                console.error('❌ Geo-refresh error:', error);
                showGeoRefreshError('Failed to refresh geographic data');
            });
        };
        
        // Start auto-refresh
        setInterval(refreshGeoData, refreshInterval);
        console.log(`🔄 Geo-analytics auto-refresh enabled (${refreshInterval/1000/60} minutes)`);
    }
    
    /**
     * Update geo tables with new data
     */
    function updateGeoTables(newData) {
        // Find country table and update if exists
        const countryTable = document.querySelector('table tbody');
        if (countryTable && newData.countries) {
            // Could implement live table updates here
            console.log('📊 Table update available, but reload recommended for consistency');
        }
    }
    
    /**
     * Show geo refresh error
     */
    function showGeoRefreshError(message) {
        // Create temporary error notification
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed';
        errorDiv.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
        `;
        errorDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Geo-Analytics:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(errorDiv);
        
        // Auto-remove after 8 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 8000);
    }
    
    /**
     * Initialize manual refresh buttons if available
     */
    function initializeRefreshButtons() {
        const refreshButtons = document.querySelectorAll('[data-action="refresh-geo"]');
        refreshButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Refreshing...';
                this.disabled = true;
                
                // Manual refresh
                fetch('/admin/geo/data?type=full&days=30', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page to show new data
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        throw new Error('Refresh failed');
                    }
                })
                .catch(error => {
                    console.error('Manual geo-refresh error:', error);
                    showGeoRefreshError('Manual refresh failed');
                })
                .finally(() => {
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                });
            });
        });
    }
    
    /**
     * Initialize country filtering
     */
    function initializeCountryFilter() {
        const filterInput = document.getElementById('countryFilter');
        if (filterInput) {
            filterInput.addEventListener('input', function() {
                const filterValue = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('table tbody tr');
                
                tableRows.forEach(row => {
                    const countryCell = row.querySelector('td:first-child');
                    if (countryCell) {
                        const countryName = countryCell.textContent.toLowerCase();
                        row.style.display = countryName.includes(filterValue) ? '' : 'none';
                    }
                });
            });
        }
    }
    
    /**
     * Initialize risk level indicators
     */
    function initializeRiskIndicators() {
        const riskBadges = document.querySelectorAll('.badge');
        riskBadges.forEach(badge => {
            const text = badge.textContent.toLowerCase();
            if (text.includes('high')) {
                badge.addEventListener('click', function() {
                    console.log('High risk item clicked:', this.closest('tr'));
                });
            }
        });
    }
    
    // Initialize all features
    initializeRefreshButtons();
    initializeCountryFilter();
    initializeRiskIndicators();
    
    // Export for debugging
    window.geoAnalytics = {
        data: { geoData, topCountriesData },
        refresh: setupAutoRefresh,
        config: configElement
    };
    
    console.log('🌍 Geo-Analytics fully initialized');
});