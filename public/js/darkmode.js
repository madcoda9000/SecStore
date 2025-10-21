/**
 * Darkmode Toggle with SVG Icons
 * CSP-compliant implementation
 */
document.addEventListener("DOMContentLoaded", function () {
    const htmlElement = document.documentElement;
    const darkmodeToggle = document.getElementById("darkmodeToggle");
    const iconContainer = document.getElementById("darkmodeIcon");

    if (!darkmodeToggle || !iconContainer) {
        console.error("❌ Darkmode elements not found!");
        return;
    }

    // SVG Icons as constants
    const sunIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sun text-light" viewBox="0 0 16 16">
  <path d="M8 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 1a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
</svg>`;

    const moonIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-moon-fill text-light" viewBox="0 0 16 16">
  <path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/>
</svg>`;

    // Get darkmode status from localStorage
    let isDarkMode = localStorage.getItem("darkmode");

    if (isDarkMode === null) {
        localStorage.setItem("darkmode", "disabled");
        isDarkMode = "disabled";
    }

    /**
     * Apply darkmode and update icon
     */
    function applyDarkmode(dark) {
        if (dark) {
            htmlElement.setAttribute("data-bs-theme", "dark");
            iconContainer.innerHTML = sunIcon;
            localStorage.setItem("darkmode", "enabled");
        } else {
            htmlElement.setAttribute("data-bs-theme", "light");
            iconContainer.innerHTML = moonIcon;
            localStorage.setItem("darkmode", "disabled");
        }
    }

    // Apply darkmode on page load
    applyDarkmode(isDarkMode === "enabled");

    // Toggle button event listener
    darkmodeToggle.addEventListener("click", function () {
        const newState = htmlElement.getAttribute("data-bs-theme") !== "dark";
        applyDarkmode(newState);
    });
});
