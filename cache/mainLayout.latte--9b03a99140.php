<?php

use Latte\Runtime as LR;

/** source: mainLayout.latte */
final class Template_9b03a99140 extends Latte\Runtime\Template
{
	public const Source = 'mainLayout.latte';

	public const Blocks = [
		['content' => 'blockContent'],
	];


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo '<!doctype html>
<html lang="en">
    <head>        
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>';
		echo LR\Filters::escapeHtmlText($title ? $title . ' - ' : null) /* line 6 */;
		echo '</title>
        <link rel="stylesheet" href="/bootstrap-5.3.3-dist/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css" /> 
        <script>
        (function() {
            let darkMode = localStorage.getItem("darkmode");
            if (darkMode === "enabled") {
            document.documentElement.setAttribute("data-bs-theme", "dark");
            }
        })();
        </script>
        <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            width: 75%;
        }
        .footer {
            width: 100%;
            text-align: left;
            padding: 10px 0;
        }
        .footer .container {
            width: 75%;
            margin: auto;
        }
        .qr-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 15px;
        }
        .actions-column {
            white-space: nowrap; /* Verhindert Zeilenumbrüche in der Aktionen-Spalte */
            width: 1%; /* Hält die Spalte so klein wie möglich */
        }

        /* Darkmode Fix für Navbar & Footer */
        [data-bs-theme="dark"] .navbar {
            background-color: #111 !important;
        }
        [data-bs-theme="dark"] .footer {
            background-color: #111 !important;
            color: #ddd;
        }
        [data-bs-theme="dark"] .toast-header {
            background-color: var(--bs-success-bg-subtle) !important;
        }
        /* alert icons */
        svg.bi {
            width:16px;
            height:16px;
        }

        /* Medienabfrage für mobile Ansicht */
        @media (max-width: 767px) {
            .footer .container {
            width: 95%;
            }
            .container {
            width: 95%;
            }
            .desktop-table {
                display: none;
            }
            .qr-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
        }
        </style>
    </head>
    <body> 
        <!-- toast container -->
        <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

';
		$this->createTemplate('_topbar.latte', ['user' => $user] + $this->params, 'include')->renderToContentType('html') /* line 86 */;
		echo '        
';
		$this->renderBlock('content', get_defined_vars()) /* line 88 */;
		echo '
        <!-- Footer -->
        <footer class="footer bg-dark text-light">
            <div class="container py-3">
                <p>&copy; 2025 Fancy Website. Alle Rechte vorbehalten.</p>
            </div>
        </footer>
            
        <!-- Bootstrap JS  -->
        <script src="/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js" defer></script>

        <!-- own script section -->
        <script>
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
                icon.classList.remove("bi-moon");
                icon.classList.add("bi-sun");
                localStorage.setItem("darkmode", "enabled");
            } else {
                htmlElement.setAttribute("data-bs-theme", "light");
                icon.classList.remove("bi-sun");
                icon.classList.add("bi-moon");
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

            // enable bootstrap popups
            var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // funktion zum anzeigen einer toast Nachricht
            window.showToast = function (message, type = "info", title = "") {
                // Prüfen, ob Dark Mode aktiv ist
                const isDarkMode = document.documentElement.getAttribute("data-bs-theme") === "dark";

                // Bootstrap-Farben je nach Typ und Theme
                const toastTypes = {
                    success: isDarkMode ? "bg-success text-white" : "bg-success text-white",
                    error: isDarkMode ? "bg-danger text-white" : "bg-danger text-white",
                    warning: isDarkMode ? "bg-warning text-white" : "bg-warning text-white",
                    info: isDarkMode ? "bg-primary text-white" : "bg-primary text-white",
                };

                // Hintergrund für den Header anpassen (Dark Mode = dunkler, Light Mode = Standard)
                const headerBg = {
                    success: isDarkMode ? "bg-success-subtle text-white" : "bg-dark text-white",
                    error: isDarkMode ? "bg-danger-subtle text-white" : "bg-dark text-white",
                    warning: isDarkMode ? "bg-warning-subtle text-white" : "bg-dark text-white",
                    info: isDarkMode ? "bg-primary-subtle text-white" : "bg-dark text-white",
                }
                

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
                    <div id="${toastId}" class="toast align-items-center border-0 ${toastTypes[type]}" role="alert" aria-live="assertive" aria-atomic="true"  data-bs-autohide="true">
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
        </script>
        
    </body>
</html>';
	}


	/** {block content} on line 88 */
	public function blockContent(array $ʟ_args): void
	{
	}
}
