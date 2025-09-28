/**
 * Login Analytics JavaScript - FINALE CSP-KONFORME VERSION
 * Phase 1: Heatmaps und Charts für Login-Visualisierung
 * Requires: Chart.js (loaded separately)
 * File: public/js/admin/login-analytics.js
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Configuration from data attributes (CSP-compliant)
    const configElement = document.getElementById('analytics-config');
    const config = {
        analyticsUrl: configElement?.getAttribute('data-analytics-url') || '/admin/analytics/data',
        refreshInterval: parseInt(configElement?.getAttribute('data-refresh-interval')) || 300000, // 5 min
        autoRefresh: configElement?.getAttribute('data-auto-refresh') === 'true',
        csrfToken: configElement?.getAttribute('data-csrf-token') || ''
    };
    
    // Data from data attributes (CSP-compliant)
    let analyticsData = {};
    if (configElement) {
        try {
            analyticsData = {
                heatmap: JSON.parse(configElement.getAttribute('data-heatmap-data') || '{}'),
                hourly: JSON.parse(configElement.getAttribute('data-hourly-data') || '[]'),
                weekly: JSON.parse(configElement.getAttribute('data-weekly-data') || '[]'),
                patterns: JSON.parse(configElement.getAttribute('data-patterns-data') || '[]')
            };
        } catch (e) {
            console.error('❌ Failed to parse analytics data:', e);
            analyticsData = { heatmap: {}, hourly: [], weekly: [], patterns: [] };
        }
    }
    
    // Chart instances storage
    const charts = {
        heatmap: null,
        hourly: null,
        weekly: null
    };
    
    // Initialize all charts
    initializeCharts();
    
    // Setup refresh button event listener (CSP-compliant)
    const refreshBtn = document.getElementById('refreshAnalyticsBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function(e) {
            e.preventDefault();
            refreshAllCharts();
        });
    }
    
    // Setup auto-refresh if enabled
    if (config.autoRefresh) {
        setInterval(refreshAllCharts, config.refreshInterval);
    }
    
    /**
     * Initialize all analytics charts
     */
    function initializeCharts() {
        initializeHeatmapChart();
        initializeHourlyChart();
        initializeWeeklyChart();
        initializePatternAlerts();
    }
    
    
    /**
     * Login Heatmap Chart (24h x 7 days)
     */
    function initializeHeatmapChart() {
        const canvas = document.getElementById('loginHeatmapChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Get heatmap data from data attributes
        const heatmapData = analyticsData.heatmap || null;
        if (!heatmapData || !heatmapData.heatmap_matrix) {
            console.warn('No heatmap data available');
            return;
        }
        
        // Transform matrix data for Chart.js
        const chartData = transformHeatmapData(heatmapData.heatmap_matrix);        
        
        charts.heatmap = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Login Activity',
                    data: chartData,
                    /*
                    backgroundColor: function(context) {
                        const value = context.parsed.v || 0;
                        return getHeatmapColor(value, heatmapData.total_logins);
                    },
                    pointRadius: function(context) {
                        const value = context.parsed.v || 0;
                        return Math.max(3, Math.min(15, value * 2)); // 3-15px radius
                    }
                        */
                    backgroundColor: function(context) {
                        const value = context.raw.v || 0;
                        return getImprovedHeatmapColor(value, heatmapData.total_logins);
                    },
                    borderColor: function(context) {
                        const value = context.raw.v || 0;
                        return getHeatmapBorderColor(value, heatmapData.total_logins);
                    },
                    borderWidth: 2,
                    pointRadius: function(context) {
                        const value = context.raw.v || 0;
                        // VERBESSERT: Mindestgröße 6px, bessere Skalierung
                        return Math.max(6, Math.min(20, 4 + value * 3));
                    },
                    pointHoverRadius: function(context) {
                        const value = context.raw.v || 0;
                        return Math.max(8, Math.min(25, 6 + value * 4));
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        min: 0,
                        max: 23,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value + ':00';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Hour of Day'
                        }
                    },
                    y: {
                        type: 'linear',
                        min: 0,
                        max: 6,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                return days[value] || '';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Day of Week'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Login Activity Heatmap (Last 30 Days)'
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                const item = tooltipItems[0];
                                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                const hour = item.parsed.x;
                                const day = days[item.parsed.y];
                                return `${day} ${hour}:00`;
                            },
                            label: function(context) {
                                const logins = context.raw.v || 0;
                                return `${logins} login${logins !== 1 ? 's' : ''}`;
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Add peak activity info
        if (heatmapData.peak_activity) {
            addHeatmapInsights(heatmapData);
        }
    }
    
    /**
     * Hourly Distribution Chart
     */
    function initializeHourlyChart() {
        const canvas = document.getElementById('hourlyDistributionChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const hourlyData = analyticsData.hourly || [];
        
        if (hourlyData.length === 0) {
            console.warn('No hourly data available');
            return;
        }
        
        charts.hourly = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: hourlyData.map(d => d.hour_label),
                datasets: [
                    {
                        label: 'Successful Logins',
                        data: hourlyData.map(d => d.successful_logins),
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Failed Logins',
                        data: hourlyData.map(d => d.failed_logins),
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour of Day'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Logins'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Hourly Login Distribution (Last 7 Days)'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    /**
     * Weekly Trends Chart
     */
    function initializeWeeklyChart() {
        const canvas = document.getElementById('weeklyTrendsChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const weeklyData = analyticsData.weekly || [];
        
        if (weeklyData.length === 0) {
            console.warn('No weekly data available');
            return;
        }
        
        charts.weekly = new Chart(ctx, {
            type: 'line',
            data: {
                labels: weeklyData.map(d => `${d.week_start} - ${d.week_end}`),
                datasets: [
                    {
                        label: 'Successful Logins',
                        data: weeklyData.map(d => d.successful_logins),
                        borderColor: 'rgba(34, 197, 94, 1)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Failed Logins',
                        data: weeklyData.map(d => d.failed_logins),
                        borderColor: 'rgba(239, 68, 68, 1)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.1,
                        fill: true
                    },
                    {
                        label: 'Success Rate (%)',
                        data: weeklyData.map(d => d.success_rate),
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Week'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Logins'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        min: 0,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Success Rate (%)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Weekly Login Trends (Last 4 Weeks)'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
    
    /**
     * Pattern Alerts Display
     */
    function initializePatternAlerts() {
        const container = document.getElementById('patternAlerts');
        if (!container) return;
        
        const patterns = analyticsData.patterns || [];
        
        if (patterns.length === 0) {
            container.innerHTML = '<div class="alert alert-success">✅ No unusual login patterns detected</div>';
            return;
        }
        
        let alertsHtml = '';
        patterns.forEach(pattern => {
            const alertClass = pattern.severity === 'high' ? 'alert-danger' : 
                             pattern.severity === 'medium' ? 'alert-warning' : 'alert-info';
            
            alertsHtml += `
                <div class="alert ${alertClass} d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <strong>${pattern.type.replace('_', ' ').toUpperCase()}:</strong>
                        ${pattern.description}
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = alertsHtml;
    }
    
    /**
     * Utility Functions
     */
    
    function transformHeatmapData(matrix) {
        const data = [];
        for (let day = 0; day <= 6; day++) {
            for (let hour = 0; hour <= 23; hour++) {
                const value = matrix[day][hour] || 0;
                if (value > 0) { // Only show points with activity
                    data.push({
                        x: hour,
                        y: day,
                        v: value
                    });
                }
            }
        }
        return data;
    }
    
    function getHeatmapColor(value, totalLogins) {
        if (value === 0) return 'rgba(0, 0, 0, 0)';
        
        const intensity = Math.min(1, value / (totalLogins * 0.1)); // Max at 10% of total
        
        // Color scale: blue (low) -> yellow (medium) -> red (high)
        if (intensity < 0.3) {
            return `rgba(59, 130, 246, ${0.3 + intensity * 0.7})`; // Blue
        } else if (intensity < 0.7) {
            return `rgba(251, 191, 36, ${0.5 + intensity * 0.5})`; // Yellow
        } else {
            return `rgba(239, 68, 68, ${0.7 + intensity * 0.3})`; // Red
        }
    }

    /**
 * VERBESSERTE FARBGEBUNG - Bessere Sichtbarkeit
 */
function getImprovedHeatmapColor(value, totalLogins) {
    if (value === 0) return 'rgba(200, 200, 200, 0.3)'; // Grau für leere Felder
    
    // Bessere Intensitätsskalierung
    const maxExpected = Math.max(1, totalLogins * 0.05); // 5% als Maximum
    const intensity = Math.min(1, value / maxExpected);
    
    // Deutlichere Farben mit besserer Sichtbarkeit
    if (intensity < 0.2) {
        return `rgba(59, 130, 246, 0.6)`; // Helles Blau (besser sichtbar)
    } else if (intensity < 0.5) {
        return `rgba(34, 197, 94, 0.7)`; // Grün
    } else if (intensity < 0.8) {
        return `rgba(251, 191, 36, 0.8)`; // Gelb/Orange
    } else {
        return `rgba(239, 68, 68, 0.9)`; // Rot
    }
}

/**
 * BORDER-FARBEN für bessere Abgrenzung
 */
function getHeatmapBorderColor(value, totalLogins) {
    if (value === 0) return 'rgba(150, 150, 150, 0.5)';
    
    const maxExpected = Math.max(1, totalLogins * 0.05);
    const intensity = Math.min(1, value / maxExpected);
    
    if (intensity < 0.2) {
        return `rgba(37, 99, 235, 0.9)`; // Dunkles Blau
    } else if (intensity < 0.5) {
        return `rgba(22, 163, 74, 0.9)`; // Dunkles Grün
    } else if (intensity < 0.8) {
        return `rgba(245, 158, 11, 0.9)`; // Dunkles Gelb
    } else {
        return `rgba(220, 38, 38, 0.9)`; // Dunkles Rot
    }
}
    
    function addHeatmapInsights(heatmapData) {
        const container = document.getElementById('heatmapInsights');
        if (!container) return;
        
        const insights = heatmapData.peak_activity;
        let insightsHtml = `
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title">📊 Activity Insights</h6>
                    <p><strong>Peak Activity:</strong> ${insights.peak_description}</p>
                    <p><strong>Total Logins Analyzed:</strong> ${heatmapData.total_logins}</p>
                    <p><strong>Analysis Period:</strong> Last ${heatmapData.analysis_period_days} days</p>
                </div>
            </div>
        `;
        
        container.innerHTML = insightsHtml;
    }
    
    /**
     * Refresh all charts with new data
     */
    function refreshAllCharts() {
        
        // Show loading state on refresh button
        const refreshBtn = document.getElementById('refreshAnalyticsBtn');
        if (refreshBtn) {
            const originalHTML = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Refreshing...';
            refreshBtn.disabled = true;
            
            // Reset button after timeout
            setTimeout(() => {
                refreshBtn.innerHTML = originalHTML;
                refreshBtn.disabled = false;
            }, 3000);
        }
        
        const headers = {
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        // Add CSRF token if available
        if (config.csrfToken) {
            headers['X-CSRF-Token'] = config.csrfToken;
        }
        
        fetch(config.analyticsUrl + '?type=heatmap&days=30', {
            method: 'GET',
            headers: headers
        })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success && charts.heatmap) {
                    // Update heatmap chart with new data
                    const chartData = transformHeatmapData(data.data.heatmap_matrix);
                    charts.heatmap.data.datasets[0].data = chartData;
                    charts.heatmap.update();
                }
            })
            .catch(error => {
                console.error('❌ Heatmap refresh error:', error);
                showRefreshError('Failed to refresh heatmap data');
            });
        
        // Refresh hourly data
        fetch(config.analyticsUrl + '?type=hourly&days=7', {
            method: 'GET',
            headers: headers
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && charts.hourly) {
                    charts.hourly.data.datasets[0].data = data.data.map(d => d.successful_logins);
                    charts.hourly.data.datasets[1].data = data.data.map(d => d.failed_logins);
                    charts.hourly.update();
                }
            })
            .catch(error => {
                console.error('❌ Hourly refresh error:', error);
            });
        
        // Refresh weekly data
        fetch(config.analyticsUrl + '?type=weekly&weeks=4', {
            method: 'GET',
            headers: headers
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && charts.weekly) {
                    charts.weekly.data.datasets[0].data = data.data.map(d => d.successful_logins);
                    charts.weekly.data.datasets[1].data = data.data.map(d => d.failed_logins);
                    charts.weekly.data.datasets[2].data = data.data.map(d => d.success_rate);
                    charts.weekly.update();
                }
            })
            .catch(error => {
                console.error('❌ Weekly refresh error:', error);
            });
    }
    
    /**
     * Show refresh error message
     */
    function showRefreshError(message) {
        // Create temporary error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed';
        errorDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        errorDiv.innerHTML = `
            <strong>Refresh Error:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(errorDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 5000);
    }
    
    // Export for debugging
    window.loginAnalytics = {
        charts: charts,
        config: config,
        refresh: refreshAllCharts,
        data: analyticsData
    };
});