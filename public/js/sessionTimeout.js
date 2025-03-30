
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
   * Stays connected by keeping the session alive by sending a request to the
   * keep alive URL. This function is called when the user chooses to stay
   * connected manually or when the session timeout dialog's "Stay connected"
   * button is clicked.
   *
   * This function clears the countdown timer and sets the session end time to
   * the current time plus the time out after period. It also sets a new timer to
   * log the user out after the time out after period has been reached.
   */
  const stayConnected = () => {
    container.classList.add("sessionTimeout--hidden");

    const url = options.appendTimestamp ? `${options.keepAliveUrl}?time=${Date.now()}` : options.keepAliveUrl;
    const req = new XMLHttpRequest();
    req.open(options.keepAliveMethod, url);
    req.send();

    sessionEndTime = Date.now() + options.timeOutAfter;
    warnTimer = setTimeout(warn, options.warnAfter);
    clearTimeout(timeOutTimer);
    timeOutTimer = setTimeout(timeOut, options.timeOutAfter);
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
