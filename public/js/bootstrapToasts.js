document.addEventListener("DOMContentLoaded", function () {
  // funktion zum anzeigen einer toast Nachricht
  window.showToast = function (message, type = "info", title = "") {
    // Prüfen, ob Dark Mode aktiv ist
    const isDarkMode = document.documentElement.getAttribute("data-bs-theme") === "dark";

    // Bootstrap-Farben je nach Typ und Theme
    const toastTypes = {
      success: isDarkMode ? "bg-success text-white" : "bg-success text-white",
      error: isDarkMode ? "bg-danger text-white" : "bg-danger text-white",
      warning: isDarkMode ? "bg-warning text-dark" : "bg-warning text-white",
      info: isDarkMode ? "bg-primary text-white" : "bg-primary text-white",
    };

    // Hintergrund für den Header anpassen (Dark Mode = dunkler, Light Mode = Standard)
    const headerBg = {
      success: isDarkMode ? "bg-success text-white" : "bg-dark text-white",
      error: isDarkMode ? "bg-danger text-white" : "bg-dark text-white",
      warning: isDarkMode ? "bg-warning text-white" : "bg-dark text-white",
      info: isDarkMode ? "bg-primary text-white" : "bg-dark text-white",
    };

    // Einzigartige ID für den Toast erstellen
    const toastId = `toast-${Date.now()}`;

    // Aktuelles Datum & Uhrzeit formatieren (z. B. 11.03.2025, 14:30:12)
    const now = new Date();
    const timestamp = now.toLocaleString("de-DE", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });

    // Toast-HTML mit Header und Darkmode-Unterstützung
    const toastHtml = `
        <div id="${toastId}" class="toast flyout align-items-center border-0 ${
      toastTypes[type]
    }" role="alert" aria-live="assertive" aria-atomic="true"  data-bs-autohide="true">
            <div class="toast-header ${headerBg[type]}">
                <strong class="me-auto">${title || "Benachrichtigung"}</strong>
                <small>${timestamp}</small>
                <button type="button" class="btn-close bg-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;

    // Toast in den Container einfügen
    const toastContainer = document.getElementById("toastContainer");
    toastContainer.insertAdjacentHTML("beforeend", toastHtml);

    // Bootstrap Toast initialisieren und anzeigen
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    // Optional: Toast nach 5 Sekunden automatisch entfernen
    setTimeout(() => {
      toastElement.remove();
    }, 5000);
  };
});
