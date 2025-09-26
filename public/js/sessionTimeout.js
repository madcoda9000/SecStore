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

  /**
   * Updates the countdown timer displayed on the page.
   * Calculates the remaining time from the current moment until session end,
   * formats it as MM:SS, and updates the appropriate DOM elements with the remaining time.
   * If the countdown reaches zero, it clears the countdown interval.
   */
  const updateCountdown = () => {
    let remainingSeconds = Math.max(0, Math.round((sessionEndTime - Date.now()) / 1000));
    let minutes = Math.floor(remainingSeconds / 60);
    let seconds = remainingSeconds % 60;
    let timeString = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;

    if (countdownSpan) countdownSpan.innerText = timeString;
    if (options.showCountDownTimerInDialog && countdownSpanInDialog) countdownSpanInDialog.innerText = timeString;

    if (remainingSeconds <= 0) clearInterval(countdownTimer);
  };

  /**
   * Displays the session timeout warning dialog by removing the hidden class
   * from the container element. This informs the user that their session is
   * about to expire and provides options to stay connected or log out.
   */
  const warn = () => {
    container.classList.remove("sessionTimeout--hidden");
  };

  /**
   * Redirects the user to the specified timeout URL when their session has expired.
   * This function is called when the session timeout period has been reached.
   */
  const timeOut = () => {
    window.location = options.timeOutUrl;
  };

  /**
   * Logs the user out by redirecting them to the specified logout URL.
   * This function is triggered when the user chooses to log out manually
   * or when the session timeout dialog's "Log out now" button is clicked.
   */
  const logOut = () => {
    window.location = options.logOutUrl;
  };

  /**
   * Handles successful keep-alive requests by resetting timers and updating the UI.
   * This function is called when the server confirms that the session has been extended.
   * It hides the session timeout dialog, resets the warning and timeout timers,
   * updates the session end time, and re-enables the "Stay connected" button.
   */
  const stayConnected = () => {
    // 1. Sofort Dialog ausblenden
    container.classList.add("sessionTimeout--hidden");

    // 2. Button während Request deaktivieren
    stayConnectedBtn.disabled = true;
    stayConnectedBtn.innerText = "Connecting...";

    // 3. Request URL vorbereiten
    const url = options.appendTimestamp ? `${options.keepAliveUrl}?time=${Date.now()}` : options.keepAliveUrl;

    // 4. XMLHttpRequest mit vollständiger Fehlerbehandlung
    const req = new XMLHttpRequest();

    // 5. SUCCESS Handler - nur bei erfolgreichem Server-Response
    req.onload = function () {
      if (req.status === 200) {
        try {
          // Versuche JSON Response zu parsen
          const response = JSON.parse(req.responseText);

          if (response.success === true) {
            // ✅ Server bestätigt Session-Verlängerung
            handleKeepAliveSuccess();
          } else {
            // ❌ Server meldet Fehler (z.B. Session abgelaufen)
            handleKeepAliveFailure("Server rejected session extension");
          }
        } catch (parseError) {
          // ❌ Ungültige JSON Response
          handleKeepAliveFailure("Invalid server response");
        }
      } else {
        // ❌ HTTP Error Status (401, 403, 500, etc.)
        handleKeepAliveFailure(`Server error: ${req.status}`);
      }
    };

    // 6. NETWORK Error Handler
    req.onerror = function () {
      handleKeepAliveFailure("Network error - check connection");
    };

    // 7. TIMEOUT Handler
    req.ontimeout = function () {
      handleKeepAliveFailure("Request timeout - server slow");
    };

    // 8. Request konfigurieren und senden
    req.open(options.keepAliveMethod, url);
    req.timeout = 10000; // 10 Sekunden Timeout
    req.send();
  };

  /**
   * Handler für erfolgreiche Session-Verlängerung
   */
  const handleKeepAliveSuccess = () => {
    // Timer zurücksetzen - NUR bei erfolgreichem Server-Response
    sessionEndTime = Date.now() + options.timeOutAfter;

    // Neue Timer starten
    warnTimer = setTimeout(warn, options.warnAfter);
    clearTimeout(timeOutTimer);
    timeOutTimer = setTimeout(timeOut, options.timeOutAfter);

    // Button wieder aktivieren
    stayConnectedBtn.disabled = false;
    stayConnectedBtn.innerText = options.stayConnectedBtnText;

    // Optional: Erfolgs-Feedback
    showFeedback("Session extended successfully", "success");
  };

  /**
   * Handler für fehlgeschlagene Session-Verlängerung
   */
  const handleKeepAliveFailure = (errorMessage) => {
    // Bei Fehlern: SOFORT ausloggen (sicherste Option)
    window.location = options.logOutUrl;

    // Alternative: Warnung anzeigen und schnell erneut warnen
    // showFeedback(errorMessage, 'error');
    // container.classList.remove("sessionTimeout--hidden");
    // warnTimer = setTimeout(warn, 5000); // Schnell erneut warnen
  };

  /**
   * Hilfsfunktion für User-Feedback (optional)
   */
  const showFeedback = (message, type) => {
    // Einfache Feedback-Implementierung
    const feedback = document.createElement("div");
    feedback.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === "success" ? "#28a745" : "#dc3545"};
        color: white;
        border-radius: 4px;
        z-index: 10000;
        font-size: 14px;
    `;
    feedback.textContent = message;
    document.body.appendChild(feedback);

    // Auto-remove nach 3 Sekunden
    setTimeout(() => {
      if (feedback.parentNode) {
        feedback.parentNode.removeChild(feedback);
      }
    }, 3000);
  };

  // create html elements
  const container = document.createElement("div");
  const modal = document.createElement("div");
  const content = document.createElement("div");
  const title = document.createElement("div");
  const buttons = document.createElement("div");
  const logOutBtn = document.createElement("button");
  const stayConnectedBtn = document.createElement("button");

  // add event listeners
  logOutBtn.addEventListener("click", logOut);
  stayConnectedBtn.addEventListener("click", stayConnected);

  // add css classes
  container.classList.add("sessionTimeout", "sessionTimeout--hidden");
  modal.classList.add("sessionTimeout-modal");
  title.classList.add("sessionTimeout-title");
  content.classList.add("sessionTimeout-content");
  buttons.classList.add("sessionTimeout-buttons");
  logOutBtn.classList.add("btn", "btn-secondary");
  logOutBtn.style.marginRight = "15px";
  stayConnectedBtn.classList.add("btn", "btn-primary");

  // add content
  title.innerText = options.titleText;
  content.innerText = options.message;
  logOutBtn.innerText = options.logOutBtnText;
  stayConnectedBtn.innerText = options.stayConnectedBtnText;

  // add countdown timer span
  let countdownSpanInDialog;
  if (options.showCountDownTimerInDialog) {
    countdownSpanInDialog = document.createElement("span");
    countdownSpanInDialog.id = options.countDownTimerInDialogId;
    countdownSpanInDialog.style.display = "block";
    countdownSpanInDialog.style.marginTop = "10px";
    countdownSpanInDialog.style.fontWeight = "bold";
    content.appendChild(countdownSpanInDialog);
  }

  // append created html elements
  modal.appendChild(title);
  modal.appendChild(content);
  modal.appendChild(buttons);
  buttons.appendChild(logOutBtn);
  buttons.appendChild(stayConnectedBtn);
  container.appendChild(modal);
  document.body.appendChild(container);

  // set timers
  warnTimer = setTimeout(warn, options.warnAfter);
  timeOutTimer = setTimeout(timeOut, options.timeOutAfter);

  // set countdown timer
  if (options.showCountDownTimer) {
    countdownTimer = setInterval(updateCountdown, 1000);
    updateCountdown();
  }
};
