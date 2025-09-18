document.addEventListener("DOMContentLoaded", function () {
    const htmlElement = document.documentElement;
    const darkmodeToggle = document.getElementById("darkmodeToggle");
    const icon = document.getElementById("darkmodeIcon");

    if (!darkmodeToggle || !icon) {
    console.error("❌ Darkmode-Elemente nicht gefunden! Überprüfe deine HTML-IDs.");
    return;
    }

    // Darkmode-Status aus localStorage abrufen
    let isDarkMode = localStorage.getItem("darkmode");

    if (isDarkMode === null) {
    localStorage.setItem("darkmode", "disabled");
    isDarkMode = "disabled";
    }

    // Funktion zum Umschalten
    function applyDarkmode(dark) {
    if (dark) {
        htmlElement.setAttribute("data-bs-theme", "dark");
        icon.classList.remove("bi-moon-fill", "text-primary");
        icon.classList.add("bi-sun-fill", "text-white");
        localStorage.setItem("darkmode", "enabled");
    } else {
        htmlElement.setAttribute("data-bs-theme", "light");
        icon.classList.remove("bi-sun-fill", "text-primary");
        icon.classList.add("bi-moon-fill", "text-white");
        localStorage.setItem("darkmode", "disabled");
    }
    }

    // Darkmode beim Laden der Seite setzen
    applyDarkmode(isDarkMode === "enabled");

    // Toggle-Button für Darkmode
    darkmodeToggle.addEventListener("click", function () {
    const newState = htmlElement.getAttribute("data-bs-theme") !== "dark";
    applyDarkmode(newState); // Darkmode umschalten und Icon anpassen
    });    
});
