document.addEventListener("DOMContentLoaded", function () {                
    let sessionElement = document.getElementById("session-data");
    let sessTimeout = parseInt(sessionElement.getAttribute("data-session-timeout"));

    if (!isNaN(sessTimeout) && typeof sessionTimeout !== "undefined") {
        sessionTimeout({
            warnAfter: (sessTimeout - 30) * 1000,
            message: "Are you still there?",
            timeOutUrl: "/logout",
            logOutUrl: "/logout",
            appendTimestamp: false,
            timeOutAfter: sessTimeout * 1000,
            message: "Your session is about to expire in:",
            stayConnectedBtnText: "Stay connected",
            titleText: "Session Timeout",
            logOutBtnText: "Log out now",
            keepAliveUrl: "/extend-session",
            showCountDownTimer: true,
            countDownTimerId: "remainingSessionTime",
            showCountDownTimerInDialog: true,
            countDownTimerInDialogId: "remainingTimeInDialog",
        });
    } else {
        console.error("sessionTimeout function is not defined or session timeout is missing.");
    }
});
