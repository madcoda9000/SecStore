/**
 * Mail Scheduler Management Script
 * CSP-compliant implementation using data-action attributes
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        refreshInterval: 10000, // 10 seconds
        apiEndpoints: {
            status: '/admin/mail-scheduler/status',
            start: '/admin/mail-scheduler/start',
            stop: '/admin/mail-scheduler/stop',
            jobs: '/admin/mail-scheduler/jobs',
            deleteJob: '/admin/mail-scheduler/jobs/delete'
        }
    };

    // State
    let currentPage = 1;
    let pageSize = 20;
    let statusFilter = '';
    let refreshTimer = null;
    let deleteJobId = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initializeEventListeners();
        loadJobs();
        startAutoRefresh();
    });

    /**
     * Initialize all event listeners using data-action attributes
     */
    function initializeEventListeners() {
        // Scheduler control buttons
        document.addEventListener('click', function(e) {
            const action = e.target.closest('[data-action]')?.dataset.action;
            
            switch(action) {
                case 'start-scheduler':
                    startScheduler();
                    break;
                case 'stop-scheduler':
                    stopScheduler();
                    break;
                case 'change-page-size':
                    handlePageSizeChange(e.target);
                    break;
                case 'filter-status':
                    handleStatusFilter(e.target);
                    break;
                case 'delete-job':
                    showDeleteConfirmation(e.target.closest('[data-job-id]').dataset.jobId);
                    break;
                case 'show-details':
                    showJobDetails(e.target.closest('[data-job-id]').dataset.jobId);
                    break;
                case 'confirm-delete':
                    confirmDeleteJob();
                    break;
            }
        });

        // Page size change
        document.getElementById('pageSize')?.addEventListener('change', function() {
            pageSize = parseInt(this.value);
            currentPage = 1;
            loadJobs();
        });

        // Status filter change
        document.getElementById('statusFilter')?.addEventListener('change', function() {
            statusFilter = this.value;
            currentPage = 1;
            loadJobs();
        });
    }

    /**
     * Start scheduler worker
     */
    function startScheduler() {
        fetch(CONFIG.apiEndpoints.start, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', getMessageText('scheduler-started'));
                updateSchedulerStatus(true);
            } else {
                showAlert('danger', data.message || getMessageText('scheduler-error'));
            }
        })
        .catch(error => {
            console.error('Error starting scheduler:', error);
            showAlert('danger', getMessageText('scheduler-error'));
        });
    }

    /**
     * Stop scheduler worker
     */
    function stopScheduler() {
        fetch(CONFIG.apiEndpoints.stop, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', getMessageText('scheduler-stopped'));
                updateSchedulerStatus(false);
            } else {
                showAlert('danger', data.message || getMessageText('scheduler-error'));
            }
        })
        .catch(error => {
            console.error('Error stopping scheduler:', error);
            showAlert('danger', getMessageText('scheduler-error'));
        });
    }

    /**
     * Load jobs list
     */
    function loadJobs() {
        showLoading(true);

        const params = new URLSearchParams({
            page: currentPage,
            pageSize: pageSize,
            status: statusFilter
        });

        fetch(`${CONFIG.apiEndpoints.jobs}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderJobs(data.jobs);
                    renderPagination(data.pagination);
                    updateStatistics(data.stats);
                } else {
                    showAlert('danger', getMessageText('load-error'));
                }
            })
            .catch(error => {
                console.error('Error loading jobs:', error);
                showAlert('danger', getMessageText('load-error'));
            })
            .finally(() => {
                showLoading(false);
            });
    }

    /**
     * Render jobs table
     */
    function renderJobs(jobs) {
        const tbody = document.getElementById('jobTableBody');
        const mobileContainer = document.getElementById('jobCardsBody');
        
        if (!jobs || jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No jobs found</td></tr>';
            mobileContainer.innerHTML = '<p class="text-center">No jobs found</p>';
            return;
        }

        // Desktop table
        tbody.innerHTML = jobs.map(job => `
            <tr>
                <td>${job.id}</td>
                <td><span class="badge bg-${getStatusColor(job.status)}">${job.status}</span></td>
                <td>${escapeHtml(job.recipient)}</td>
                <td>${escapeHtml(job.subject)}</td>
                <td>${escapeHtml(job.template)}</td>
                <td>${job.attempts} / ${job.max_attempts}</td>
                <td>${formatDateTime(job.scheduled_at)}</td>
                <td>
                    <button class="btn btn-sm btn-info" data-action="show-details" data-job-id="${job.id}">
                        <i class="bi-eye"></i>
                    </button>
                    ${job.status === 'pending' || job.status === 'failed' ? `
                        <button class="btn btn-sm btn-danger" data-action="delete-job" data-job-id="${job.id}">
                            <i class="bi-trash"></i>
                        </button>
                    ` : ''}
                </td>
            </tr>
        `).join('');

        // Mobile cards
        mobileContainer.innerHTML = jobs.map(job => `
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span><strong>#${job.id}</strong></span>
                    <span class="badge bg-${getStatusColor(job.status)}">${job.status}</span>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <strong>Recipient:</strong> ${escapeHtml(job.recipient)}<br>
                        <strong>Subject:</strong> ${escapeHtml(job.subject)}<br>
                        <strong>Template:</strong> ${escapeHtml(job.template)}<br>
                        <strong>Attempts:</strong> ${job.attempts} / ${job.max_attempts}<br>
                        <strong>Scheduled:</strong> ${formatDateTime(job.scheduled_at)}
                    </p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-info" data-action="show-details" data-job-id="${job.id}">
                            <i class="bi-eye"></i> Details
                        </button>
                        ${job.status === 'pending' || job.status === 'failed' ? `
                            <button class="btn btn-sm btn-danger" data-action="delete-job" data-job-id="${job.id}">
                                <i class="bi-trash"></i> Delete
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Render pagination
     */
    function renderPagination(pagination) {
        const paginationInfo = document.getElementById('paginationInfo');
        const paginationNav = document.getElementById('pagination');

        paginationInfo.textContent = `${getMessageText('page')} ${pagination.page} ${getMessageText('of')} ${pagination.totalPages}`;

        let html = '';
        if (pagination.page > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.page - 1}">Previous</a></li>`;
        }
        if (pagination.page < pagination.totalPages) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.page + 1}">Next</a></li>`;
        }

        paginationNav.innerHTML = html;

        // Add click handlers
        paginationNav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = parseInt(this.dataset.page);
                loadJobs();
            });
        });
    }

    /**
     * Update statistics
     */
    function updateStatistics(stats) {
        if (!stats) return;

        document.getElementById('statPending').textContent = stats.pending || 0;
        document.getElementById('statProcessing').textContent = stats.processing || 0;
        document.getElementById('statCompleted').textContent = stats.completed || 0;
        document.getElementById('statFailed').textContent = stats.failed || 0;
    }

    /**
     * Show delete confirmation
     */
    function showDeleteConfirmation(jobId) {
        deleteJobId = jobId;
        const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        modal.show();
    }

    /**
     * Confirm delete job
     */
    function confirmDeleteJob() {
        if (!deleteJobId) return;

        fetch(CONFIG.apiEndpoints.deleteJob, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: deleteJobId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', getMessageText('job-deleted'));
                loadJobs();
                bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal')).hide();
            } else {
                showAlert('danger', data.message || getMessageText('delete-error'));
            }
        })
        .catch(error => {
            console.error('Error deleting job:', error);
            showAlert('danger', getMessageText('delete-error'));
        })
        .finally(() => {
            deleteJobId = null;
        });
    }

    /**
     * Update scheduler status display
     */
    function updateSchedulerStatus(running) {
        const indicator = document.getElementById('statusIndicator');
        const html = running ? 
            '<span class="badge bg-success fs-5"><span class="bi-check-circle-fill"></span>&nbsp;Running</span>' :
            '<span class="badge bg-danger fs-5"><span class="bi-x-circle-fill"></span>&nbsp;Stopped</span>';
        
        indicator.innerHTML = html;

        // Update button
        const buttonHtml = running ?
            '<button type="button" class="btn btn-danger" data-action="stop-scheduler"><span class="bi-stop-circle"></span>&nbsp;Stop Scheduler</button>' :
            '<button type="button" class="btn btn-success" data-action="start-scheduler"><span class="bi-play-circle"></span>&nbsp;Start Scheduler</button>';
        
        indicator.nextElementSibling.innerHTML = buttonHtml;
    }

    /**
     * Auto-refresh status and jobs
     */
    function startAutoRefresh() {
        refreshTimer = setInterval(() => {
            fetch(CONFIG.apiEndpoints.status)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateSchedulerStatus(data.status.running);
                        document.getElementById('lastTick').textContent = data.status.lastTick || 'Never';
                        updateStatistics(data.stats);
                    }
                })
                .catch(error => console.error('Error refreshing status:', error));
        }, CONFIG.refreshInterval);
    }

    /**
     * Helper functions
     */
    function showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.classList.toggle('d-none', !show);
        }
    }

    function getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'processing': 'info',
            'completed': 'success',
            'failed': 'danger'
        };
        return colors[status] || 'secondary';
    }

    function formatDateTime(datetime) {
        if (!datetime) return 'N/A';
        return new Date(datetime).toLocaleString();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getMessageText(key) {
        const messages = document.getElementById('scheduler-messages');
        return messages?.dataset[key] || key;
    }

    function showAlert(type, message) {
        const container = document.querySelector('.container');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => alert.remove(), 5000);
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
        }
    });
})();
