<?php

use Latte\Runtime as LR;

/** source: authLayout.latte */
final class Template_d6631cf097 extends Latte\Runtime\Template
{
	public const Source = 'authLayout.latte';

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
        <style>
            html, body {
            height: 100%;
            }
            .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            }
            .darkmode-toggle {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            }
            .login-card {
                background-color:var(--bs-tertiary-bg);

            }
            .darkmode-toggle {
                background-color:transparent;
                border:none;
                box-shadow:none;
            }
            .invalid-feedback {
                display:block;
            }
            @media (max-width: 576px) {
                .login-card {
                    max-width: 90%;
                    padding: 1.5rem;
                    border:none;
                    box-shadow:none !important;
                    background-color:var(--bs-body-bg);
                }
            }
        </style>
        </head>
        <body>
';
		$this->renderBlock('content', get_defined_vars()) /* line 49 */;
		echo '
        <!-- Darkmode Toggle -->
        <button id="darkmodeToggle" class="darkmode-toggle">
            <i id="darkmodeIcon" class="bi bi-moon"></i>
        </button>
        
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
            });
        </script>
    </body>
</html>';
	}


	/** {block content} on line 49 */
	public function blockContent(array $ʟ_args): void
	{
	}
}
