(function() {
    let darkMode = localStorage.getItem("darkmode");
    if (darkMode === "enabled") {
    document.documentElement.setAttribute("data-bs-theme", "dark");
    }
})();