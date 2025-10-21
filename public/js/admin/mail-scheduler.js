/**
 * Mail Scheduler Management Script
 * CSP-compliant implementation using data-action attributes
 */

(function () {
  "use strict";

  // Configuration
  const CONFIG = {
    refreshInterval: 10000, // 10 seconds
    apiEndpoints: {
      status: "/admin/mail-scheduler/status",
      start: "/admin/mail-scheduler/start",
      stop: "/admin/mail-scheduler/stop",
      jobs: "/admin/mail-scheduler/jobs",
      deleteJob: "/admin/mail-scheduler/jobs/delete",
    },
  };

  // State
  let currentPage = 1;
  let pageSize = 6;
  let statusFilter = "";
  let refreshTimer = null;
  let deleteJobId = null;

  // Initialize
  document.addEventListener("DOMContentLoaded", function () {
    initializeEventListeners();
    loadJobs();
    startAutoRefresh();
  });

  /**
   * Initialize all event listeners using data-action attributes
   */
  function initializeEventListeners() {
    // Scheduler control buttons
    document.addEventListener("click", function (e) {
      const action = e.target.closest("[data-action]")?.dataset.action;

      switch (action) {
        case "start-scheduler":
          startScheduler();
          break;
        case "stop-scheduler":
          stopScheduler();
          break;
        case "change-page-size":
          handlePageSizeChange(e.target);
          break;
        case "filter-status":
          handleStatusFilter(e.target);
          break;
        case "delete-job":
          showDeleteConfirmation(e.target.closest("[data-job-id]").dataset.jobId);
          break;
        case "show-details":
          showJobDetails(e.target.closest("[data-job-id]").dataset.jobId);
          break;
        case "confirm-delete":
          confirmDeleteJob();
          break;
      }
    });

    // Page size change
    document.getElementById("pageSize")?.addEventListener("change", function () {
      pageSize = parseInt(this.value);
      currentPage = 1;
      loadJobs();
    });

    // Status filter change
    document.getElementById("statusFilter")?.addEventListener("change", function () {
      statusFilter = this.value;
      currentPage = 1;
      loadJobs();
    });
  }

  /**
   * Start scheduler worker
   */
  function startScheduler() {
    showSchedulerLoading(true);

    const csrfToken = document.getElementById("csrf_token")?.value;

    if (!csrfToken) {
      throw new Error("CSRF token not found");
    }

    fetch(CONFIG.apiEndpoints.start, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: new URLSearchParams({
        csrf_token: csrfToken,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.showToast(getMessageText("scheduler-started"), "success", "Scheduler");
          updateSchedulerStatus(true);
        } else {
          window.showToast(data.message || getMessageText("scheduler-error"), "error", "Error");
        }
      })
      .catch((error) => {
        console.error("Error starting scheduler:", error);
        window.showToast(getMessageText("scheduler-error"), "error", "Error");
      })
      .finally(() => {
        showSchedulerLoading(false);
      });
  }

  /**
   * Stop scheduler worker
   */
  function stopScheduler() {
    showSchedulerLoading(true);

    const csrfToken = document.getElementById("csrf_token")?.value;

    if (!csrfToken) {
      throw new Error("CSRF token not found");
    }

    fetch(CONFIG.apiEndpoints.stop, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({
        csrf_token: csrfToken,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.showToast(getMessageText("scheduler-stopped"), "success", "Scheduler");
          updateSchedulerStatus(false);
        } else {
          window.showToast(data.message || getMessageText("scheduler-error"), "error", "Error");
        }
      })
      .catch((error) => {
        console.error("Error stopping scheduler:", error);
        window.showToast(getMessageText("scheduler-error"), "error", "Error");
      })
      .finally(() => {
        showSchedulerLoading(false);
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
      status: statusFilter,
    });

    fetch(`${CONFIG.apiEndpoints.jobs}?${params}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          renderJobs(data.jobs);
          renderPagination(data.pagination);
          updateStatistics(data.stats);
        } else {
          window.showToast(getMessageText("load-error"), "error", "Error");
        }
      })
      .catch((error) => {
        console.error("Error loading jobs:", error);
        window.showToast(getMessageText("load-error"), "error", "Error");
      })
      .finally(() => {
        showLoading(false);
      });
  }

  /**
   * Render jobs table
   */
  function renderJobs(jobs) {
    const tbody = document.getElementById("jobTableBody");
    const mobileContainer = document.getElementById("jobCardsBody");

    if (!jobs || jobs.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center">No jobs found</td></tr>';
      mobileContainer.innerHTML = '<p class="text-center">No jobs found</p>';
      return;
    }

    // Desktop table
    tbody.innerHTML = jobs
      .map(
        (job) => `
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                          <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                          <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                        </svg>
                    </button>
                    ${
                      job.status === "pending" || job.status === "failed"
                        ? `
                        <button class="btn btn-sm btn-danger" data-action="delete-job" data-job-id="${job.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                              <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                              <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                            </svg>
                        </button>
                    `
                        : ""
                    }
                </td>
            </tr>
        `
      )
      .join("");

    // Mobile cards
    mobileContainer.innerHTML = jobs
      .map(
        (job) => `
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                          <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                          <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                        </svg> Details
                        </button>
                        ${
                          job.status === "pending" || job.status === "failed"
                            ? `
                            <button class="btn btn-sm btn-danger" data-action="delete-job" data-job-id="${job.id}">
                                <button class="btn btn-sm btn-danger" data-action="delete-job" data-job-id="${job.id}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                              <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                              <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                            </svg>‚ Delete
                            </button>
                        `
                            : ""
                        }
                    </div>
                </div>
            </div>
        `
      )
      .join("");
  }

  /**
   * Render pagination
   */
  function renderPagination(pagination) {
    const paginationInfo = document.getElementById("paginationInfo");
    const paginationNav = document.getElementById("pagination");

    paginationInfo.textContent = `${getMessageText("page")} ${pagination.page} ${getMessageText("of")} ${
      pagination.totalPages
    }`;

    let html = "";
    if (pagination.page > 1) {
      html += `<li class="page-item"><a class="page-link" href="#" data-page="${
        pagination.page - 1
      }">Previous</a></li>`;
    }
    if (pagination.page < pagination.totalPages) {
      html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.page + 1}">Next</a></li>`;
    }

    paginationNav.innerHTML = html;

    // Add click handlers
    paginationNav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", function (e) {
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

    document.getElementById("statPending").textContent = stats.pending || 0;
    document.getElementById("statProcessing").textContent = stats.processing || 0;
    document.getElementById("statCompleted").textContent = stats.completed || 0;
    document.getElementById("statFailed").textContent = stats.failed || 0;
  }

  /**
   * Show delete confirmation
   */
  function showDeleteConfirmation(jobId) {
    deleteJobId = jobId;
    const modal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));
    modal.show();
  }

  /**
   * Show job details in modal
   */
  function showJobDetails(jobId) {
    if (!jobId) return;

    // Find the job in the current loaded data
    const params = new URLSearchParams({
      page: currentPage,
      pageSize: pageSize,
      status: statusFilter,
    });

    fetch(`${CONFIG.apiEndpoints.jobs}?${params}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const job = data.jobs.find((j) => j.id == jobId);
          if (job) {
            renderJobDetails(job);
            const modal = new bootstrap.Modal(document.getElementById("detailsModal"));
            modal.show();
          }
        }
      })
      .catch((error) => {
        console.error("Error loading job details:", error);
        window.showToast("Error loading job details", "error", "Error");
      });
  }

  /**
   * Render job details in modal
   */
  function renderJobDetails(job) {
    const container = document.getElementById("jobDetailsContent");
    if (!container) return;

    let templateData = "N/A";
    try {
      const parsed = JSON.parse(job.template_data);
      templateData = `<pre>${JSON.stringify(parsed, null, 2)}</pre>`;
    } catch (e) {
      templateData = escapeHtml(job.template_data);
    }

    container.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Job ID</dt>
                        <dd>#${job.id}</dd>
                        
                        <dt>Status</dt>
                        <dd><span class="badge bg-${getStatusColor(job.status)}">${job.status}</span></dd>
                        
                        <dt>Recipient</dt>
                        <dd>${escapeHtml(job.recipient)}</dd>
                        
                        <dt>Subject</dt>
                        <dd>${escapeHtml(job.subject)}</dd>
                        
                        <dt>Template</dt>
                        <dd><code>${escapeHtml(job.template)}</code></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Attempts</dt>
                        <dd>${job.attempts} / ${job.max_attempts}</dd>
                        
                        <dt>Created</dt>
                        <dd>${formatDateTime(job.created_at)}</dd>
                        
                        <dt>Scheduled</dt>
                        <dd>${formatDateTime(job.scheduled_at)}</dd>
                        
                        <dt>Started</dt>
                        <dd>${formatDateTime(job.started_at)}</dd>
                        
                        <dt>Completed</dt>
                        <dd>${formatDateTime(job.completed_at)}</dd>
                    </dl>
                </div>
            </div>
            
            ${
              job.last_error
                ? `
                <div class="alert alert-danger mt-3">
                    <strong>Last Error:</strong><br>
                    ${escapeHtml(job.last_error)}
                </div>
            `
                : ""
            }
            
            <div class="mt-3">
                <strong>Template Data:</strong>
                ${templateData}
            </div>
        `;
  }

  /**
   * Confirm delete job
   */
  function confirmDeleteJob() {
    if (!deleteJobId) return;

    const csrfToken = document.getElementById("csrf_token")?.value;

    if (!csrfToken) {
      throw new Error("CSRF token not found");
    }

    fetch(CONFIG.apiEndpoints.deleteJob, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({
        id: deleteJobId,
        csrf_token: csrfToken,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.showToast(getMessageText("job-deleted"), "success", "Job Deleted");
          loadJobs();
          bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal")).hide();
        } else {
          window.showToast(data.message || getMessageText("delete-error"), "error", "Error");
          bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal")).hide();
        }
      })
      .catch((error) => {
        console.error("Error deleting job:", error);
        window.showToast(getMessageText("delete-error"), "error", "Error");
        bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal")).hide();
      })
      .finally(() => {
        deleteJobId = null;
      });
  }

  /**
   * Update scheduler status display
   */
  function updateSchedulerStatus(running) {
    const indicator = document.getElementById("statusIndicator");
    const html = running
      ? 'Status:&nbsp;<span class="p-2"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>&nbsp;Running</span></span>'
      : 'Status:&nbsp;<span class="p-2"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-x-circle-fill text-danger" viewBox="0 0 16 16"> <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z"/></svg>&nbsp;Stopped</span></span>';

    indicator.innerHTML = html;

    // Update button container
    const buttonContainer = document.getElementById("schedulerButtonContainer");
    const buttonHtml = running
      ? '<button type="button" class="btn btn-danger" data-action="stop-scheduler"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-stop-circle" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M5 6.5A1.5 1.5 0 0 1 6.5 5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5z"/></svg>&nbsp;Stop Scheduler</button>'
      : '<button type="button" class="btn btn-success" data-action="start-scheduler"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-play-circle" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M6.271 5.055a.5.5 0 0 1 .52.038l3.5 2.5a.5.5 0 0 1 0 .814l-3.5 2.5A.5.5 0 0 1 6 10.5v-5a.5.5 0 0 1 .271-.445"/></svg>&nbsp;Start Scheduler</button>';

    buttonContainer.innerHTML = buttonHtml;
  }

  /**
   * Auto-refresh status and jobs
   */
  function startAutoRefresh() {
    refreshTimer = setInterval(() => {
      // Refresh scheduler status and stats
      fetch(CONFIG.apiEndpoints.status)
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            updateSchedulerStatus(data.status.running);
            document.getElementById("lastTick").textContent = data.status.lastTick || "Never";
            updateStatistics(data.stats);
          }
        })
        .catch((error) => console.error("Error refreshing status:", error));

      // Also refresh the job list
      loadJobs();
    }, CONFIG.refreshInterval);
  }

  /**
   * Helper functions
   */
  function showSchedulerLoading(show) {
    const buttonContainer = document.getElementById("schedulerButtonContainer");
    const loadingSpinner = document.getElementById("schedulerLoadingSpinner");

    if (show) {
      buttonContainer.classList.add("d-none");
      loadingSpinner.classList.remove("d-none");
    } else {
      buttonContainer.classList.remove("d-none");
      loadingSpinner.classList.add("d-none");
    }
  }

  function showLoading(show) {
    const spinner = document.getElementById("loadingSpinner");
    if (spinner) {
      spinner.classList.toggle("d-none", !show);
    }
  }

  function getStatusColor(status) {
    const colors = {
      pending: "warning",
      processing: "info",
      completed: "success",
      failed: "danger",
    };
    return colors[status] || "secondary";
  }

  function formatDateTime(datetime) {
    if (!datetime) return "N/A";
    return new Date(datetime).toLocaleString();
  }

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  function getMessageText(key) {
    const messages = document.getElementById("scheduler-messages");
    return messages?.dataset[key] || key;
  }

  // Cleanup on page unload
  window.addEventListener("beforeunload", function () {
    if (refreshTimer) {
      clearInterval(refreshTimer);
    }
  });
})();
