document.addEventListener("DOMContentLoaded",(function(){let e=window.location.pathname;document.querySelectorAll(".navbar-nav .nav-link, .dropdown-menu .dropdown-item").forEach((o=>{let t=new URL(o.href,window.location.origin).pathname;if("#"!==t&&""!==t&&t===e){o.classList.add("active");let e=o.closest(".dropdown-menu");if(e){let o=e.closest(".dropdown");o&&o.classList.add("has-active")}}})),document.querySelectorAll(".dropdown").forEach((e=>{let o=e.querySelector(".dropdown-toggle");e.classList.contains("has-active")?o.classList.add("active"):o.classList.remove("active")}))}));