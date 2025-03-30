<?php

use Latte\Runtime as LR;

/** source: mainLayout.latte */
final class Template_787b4df45f extends Latte\Runtime\Template
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
        <link rel="stylesheet" href="/css/style-min.css">
        <script src="/js/lucide.min.js"></script>
        <script>
            // method to check wether to enable drak mode or not
            (function () {
                if (localStorage.getItem("darkMode") === "enabled") {
                    document.documentElement.setAttribute("data-theme", "dark");
                }
            })();
        </script>
    </head>
    <body style="padding: 15px;">
   
        <!-- The Modal -->
        <div id="sessionModal" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <div class="modal-header">
            <h2>Sitzung läuft ab!</h2>
            </div>
            <div class="modal-body">                
                <p>Deine Sitzung läuft in <span id="dialogTmr"></span> ab. Möchtest du sie verlängern?</p>
            </div>
            <div class="modal-footer">
                <div class="modal-buttons">
                    <button id="extendSessionBtn">Ja, verlängern</button>
                    <button id="closeModalBtn">Nein</button>
                </div>
            </div>
        </div>

        </div>
';
		$this->renderBlock('content', get_defined_vars()) /* line 40 */;
		echo '          
        <script>
            lucide.createIcons();
        </script>
        <script src="/js/darkmode-min.js"></script>             
    </body>
</html>';
	}


	/** {block content} on line 40 */
	public function blockContent(array $ʟ_args): void
	{
	}
}
