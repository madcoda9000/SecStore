document.addEventListener("DOMContentLoaded",(function(){let e=document.getElementById("session-data"),t=parseInt(e.getAttribute("data-session-timeout"));isNaN(t)||"undefined"==typeof sessionTimeout||sessionTimeout({warnAfter:1e3*(t-30),message:"Are you still there?",timeOutUrl:"/logout",logOutUrl:"/logout",appendTimestamp:!1,timeOutAfter:1e3*t,message:"Your session is about to expire in:",stayConnectedBtnText:"Stay connected",titleText:"Session Timeout",logOutBtnText:"Log out now",keepAliveUrl:"/extend-session",showCountDownTimer:!0,countDownTimerId:"remainingSessionTime",showCountDownTimerInDialog:!0,countDownTimerInDialogId:"remainingTimeInDialog"})}));