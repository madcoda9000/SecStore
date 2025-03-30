document.addEventListener("DOMContentLoaded",(function(){window.showToast=function(t,e="info",n=""){const i="dark"===document.documentElement.getAttribute("data-bs-theme"),a={success:"bg-success text-white",error:"bg-danger text-white",warning:i?"bg-warning text-dark":"bg-warning text-white",info:"bg-primary text-white"},s={success:i?"bg-success text-white":"bg-dark text-white",error:i?"bg-danger text-white":"bg-dark text-white",warning:i?"bg-warning text-white":"bg-dark text-white",info:i?"bg-primary text-white":"bg-dark text-white"},o=`toast-${Date.now()}`,r=(new Date).toLocaleString("de-DE",{day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit"}),d=`\n        <div id="${o}" class="toast flyout align-items-center border-0 ${a[e]}" role="alert" aria-live="assertive" aria-atomic="true"  data-bs-autohide="true">\n            <div class="toast-header ${s[e]}">\n                <strong class="me-auto">${n||"Benachrichtigung"}</strong>\n                <small>${r}</small>\n                <button type="button" class="btn-close bg-white" data-bs-dismiss="toast" aria-label="Close"></button>\n            </div>\n            <div class="toast-body">\n                ${t}\n            </div>\n        </div>\n    `;document.getElementById("toastContainer").insertAdjacentHTML("beforeend",d);const g=document.getElementById(o);new bootstrap.Toast(g).show(),setTimeout((()=>{g.remove()}),5e3)}}));