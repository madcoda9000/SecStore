document.addEventListener("DOMContentLoaded",(function(){const e=document.querySelector("form"),n=e.querySelector("input[name='username']"),t=e.querySelector("input[name='password']");function i(e,n){let t=e.nextElementSibling;t&&t.classList.contains("invalid-feedback")||(t=document.createElement("div"),t.classList.add("invalid-feedback"),e.parentNode.insertBefore(t,e.nextSibling)),t.textContent=n}function a(e){let n=e.nextElementSibling;n&&n.classList.contains("invalid-feedback")&&n.remove()}e.addEventListener("submit",(function(e){let o=!0;""===n.value.trim()?(i(n,"Bitte gib deinen Benutzernamen ein."),o=!1):a(n),""===t.value.trim()?(i(t,"Bitte gib ein Passwort ein."),o=!1):a(t),o||e.preventDefault()}))}));