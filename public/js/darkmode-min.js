document.addEventListener("DOMContentLoaded",(function(){const e=document.documentElement,t=document.getElementById("darkmodeToggle"),d=document.getElementById("darkmodeIcon");if(!t||!d)return;let a=localStorage.getItem("darkmode");function o(t){t?(e.setAttribute("data-bs-theme","dark"),d.classList.remove("bi-moon"),d.classList.add("bi-sun"),localStorage.setItem("darkmode","enabled")):(e.setAttribute("data-bs-theme","light"),d.classList.remove("bi-sun"),d.classList.add("bi-moon"),localStorage.setItem("darkmode","disabled"))}null===a&&(localStorage.setItem("darkmode","disabled"),a="disabled"),o("enabled"===a),t.addEventListener("click",(function(){o("dark"!==e.getAttribute("data-bs-theme"))}))}));