/**
 * sessionTimeout
 *
 * A JavaScript function that will show a warning dialog when the user's session is about to expire.
 * The dialog will display a countdown timer and two buttons: "Log out now" and "Stay connected".
 *
 * @param {Object} [options] - Object with options.
 *
 * @prop {boolean} [options.appendTimestamp=false] - Whether to append the current timestamp to the keep alive URL.
 * @prop {string} [options.keepAliveMethod="POST"] - The HTTP method to use for the keep alive request.
 * @prop {string} [options.keepAliveUrl="/keep-alive"] - The URL to send the keep alive request to.
 * @prop {string} [options.logOutBtnText="Log out now"] - The text for the "Log out now" button.
 * @prop {string} [options.logOutUrl="/log-out"] - The URL to redirect to when the user clicks the "Log out now" button.
 * @prop {string} [options.message="Your session is about to expire."] - The message to display in the dialog.
 * @prop {string} [options.stayConnectedBtnText="Stay connected"] - The text for the "Stay connected" button.
 * @prop {number} [options.timeOutAfter=1200000] - The time in milliseconds after which the session will expire.
 * @prop {string} [options.timeOutUrl="/timed-out"] - The URL to redirect to when the session has expired.
 * @prop {string} [options.titleText="Session Timeout"] - The title to display in the dialog.
 * @prop {number} [options.warnAfter=900000] - The time in milliseconds after which the warning dialog will appear.
 * @prop {boolean} [options.showCountDownTimer=false] - Whether to display the countdown timer on the page.
 * @prop {boolean} [options.showCountDownTimerInDialog=false] - Whether to display the countdown timer in the dialog.
 * @prop {string} [options.countDownTimerInDialogId="remainingTimeInDialog"] - The id of the element that will display the countdown timer in the dialog.
 * @prop {string} [options.countDownTimerId="remainingTime"] - The id of the element that will display the countdown timer on the page.
 */
/**
 * sessionTimeout - Bootstrap Modal Version
 * 
 * A JavaScript function that will show a Bootstrap modal when the user's session is about to expire.
 */
window.sessionTimeout = function (passedOptions) {
  const defaults = {
    appendTimestamp: false,
    keepAliveMethod: "POST",
    keepAliveUrl: "/keep-alive",
    logOutBtnText: "Log out now",
    logOutUrl: "/log-out",
    message: "Your session is about to expire.",
    stayConnectedBtnText: "Stay connected",
    timeOutAfter: 1200000,
    timeOutUrl: "/timed-out",
    titleText: "Session Timeout",
    warnAfter: 900000,
    showCountDownTimer: false,
    showCountDownTimerInDialog: false,
    countDownTimerInDialogId: "remainingTimeInDialog",
    countDownTimerId: "remainingTime",
  };

  const options = Object.assign(defaults, passedOptions);

  let warnTimer, timeOutTimer, countdownTimer;
  let countdownSpan = document.getElementById(options.countDownTimerId);
  let sessionEndTime = Date.now() + options.timeOutAfter;
  let bootstrapModal = null;

  /**
   * Updates the countdown timer displayed on the page and in dialog
   */
  const updateCountdown = () => {
    let remainingSeconds = Math.max(0, Math.round((sessionEndTime - Date.now()) / 1000));
    let minutes = Math.floor(remainingSeconds / 60);
    let seconds = remainingSeconds % 60;
    let timeString = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;

    // Update countdown on page (footer)
    if (countdownSpan) countdownSpan.innerText = timeString;
    
    // Update countdown in dialog
    if (options.showCountDownTimerInDialog) {
      const dialogCountdown = document.getElementById(options.countDownTimerInDialogId);
      if (dialogCountdown) dialogCountdown.innerText = timeString;
    }

    if (remainingSeconds <= 0) {
      clearInterval(countdownTimer);
    }
  };

  /**
   * Creates Bootstrap Modal HTML structure
   */
  const createBootstrapModal = () => {
    const modalHTML = `
      <div class="modal fade" id="sessionTimeoutModal" tabindex="-1" aria-labelledby="sessionTimeoutModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
              <h5 class="modal-title" id="sessionTimeoutModalLabel">
                <i class="bi bi-clock-history me-2"></i>${options.titleText}
              </h5>
            </div>
            <div class="modal-body text-center">
              <div class="mb-3">
                <i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-3"></i>
              </div>
              <p class="fs-5 mb-3">${options.message}</p>
              ${options.showCountDownTimerInDialog ? `
                <div class="alert alert-warning" role="alert">
                  <i class="bi bi-stopwatch me-2"></i>
                  Time remaining: <strong><span id="${options.countDownTimerInDialogId}" class="text-danger fs-4">00:00</span></strong>
                </div>
              ` : ''}
              <div class="text-muted">
                <small>Your session will expire automatically if no action is taken.</small>
              </div>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-outline-secondary me-3" id="sessionTimeoutLogoutBtn">
                <i class="bi bi-box-arrow-right me-2"></i>${options.logOutBtnText}
              </button>
              <button type="button" class="btn btn-primary" id="sessionTimeoutStayConnectedBtn">
                <i class="bi bi-arrow-clockwise me-2"></i>${options.stayConnectedBtnText}
              </button>
            </div>
          </div>
        </div>
      </div>
    `;

    // Remove existing modal if present
    const existingModal = document.getElementById('sessionTimeoutModal');
    if (existingModal) {
      existingModal.remove();
    }

    // Add new modal to DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Initialize Bootstrap Modal
    const modalElement = document.getElementById('sessionTimeoutModal');
    bootstrapModal = new bootstrap.Modal(modalElement, {
      backdrop: 'static',
      keyboard: false
    });

    // Add event listeners to buttons
    document.getElementById('sessionTimeoutLogoutBtn').addEventListener('click', logOut);
    document.getElementById('sessionTimeoutStayConnectedBtn').addEventListener('click', stayConnected);

    return modalElement;
  };

  /**
   * Shows the session timeout warning dialog
   */
  const warn = () => {
    if (!bootstrapModal) {
      createBootstrapModal();
    }
    bootstrapModal.show();
  };

  /**
   * Redirects to timeout URL when session has expired
   */
  const timeOut = () => {
    window.location = options.timeOutUrl;
  };

  /**
   * Logs the user out immediately
   */
  const logOut = () => {
    window.location = options.logOutUrl;
  };

  /**
   * Stays connected by extending the session with improved error handling
   */
  const stayConnected = () => {
    const stayBtn = document.getElementById('sessionTimeoutStayConnectedBtn');
    const logoutBtn = document.getElementById('sessionTimeoutLogoutBtn');
    
    // Disable buttons and show loading state
    stayBtn.disabled = true;
    logoutBtn.disabled = true;
    stayBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting...';

    const url = options.appendTimestamp ? 
        `${options.keepAliveUrl}?time=${Date.now()}` : 
        options.keepAliveUrl;

    const req = new XMLHttpRequest();

    // Success handler
    req.onload = function() {
      if (req.status === 200) {
        try {
          const response = JSON.parse(req.responseText);
          if (response.success === true) {
            handleKeepAliveSuccess();
          } else {
            handleKeepAliveFailure('Server rejected session extension');
          }
        } catch (parseError) {
          handleKeepAliveFailure('Invalid server response');
        }
      } else {
        handleKeepAliveFailure(`Server error: ${req.status}`);
      }
    };

    // Error handlers
    req.onerror = () => handleKeepAliveFailure('Network error - check connection');
    req.ontimeout = () => handleKeepAliveFailure('Request timeout - server slow');

    // Configure and send request
    req.timeout = 10000; // 10 second timeout
    req.open(options.keepAliveMethod, url);
    req.send();
  };

  /**
   * Handler for successful session extension
   */
  const handleKeepAliveSuccess = () => {
    // Reset session timer
    sessionEndTime = Date.now() + options.timeOutAfter;
    
    // Hide modal
    bootstrapModal.hide();
    
    // Reset timers
    warnTimer = setTimeout(warn, options.warnAfter);
    clearTimeout(timeOutTimer);
    timeOutTimer = setTimeout(timeOut, options.timeOutAfter);
    
    // Show success feedback
    showToast('Session extended successfully', 'success');
  };

  /**
   * Handler for failed session extension
   */
  const handleKeepAliveFailure = (errorMessage) => {
    // On any failure: immediately logout (safest option)
    window.location = options.logOutUrl;
  };

  /**
   * Shows toast notification (if available)
   */
  const showToast = (message, type) => {
    // Check if global showToast function exists (from your bootstrap toasts)
    if (typeof window.showToast === 'function') {
      window.showToast(message, type === 'success' ? 'success' : 'danger', 'Session');
    } else {
      // Fallback: simple alert or console
      if (type === 'success') {
        // Could create a simple notification
        console.log('Session: ' + message);
      }
    }
  };

  // Initialize: Create modal structure
  createBootstrapModal();

  // Set initial timers
  warnTimer = setTimeout(warn, options.warnAfter);
  timeOutTimer = setTimeout(timeOut, options.timeOutAfter);

  // Set countdown timer if enabled
  if (options.showCountDownTimer) {
    countdownTimer = setInterval(updateCountdown, 1000);
    updateCountdown();
  }
};