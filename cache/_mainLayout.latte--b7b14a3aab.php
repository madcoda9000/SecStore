<?php

use Latte\Runtime as LR;

/** source: _mainLayout.latte */
final class Template_b7b14a3aab extends Latte\Runtime\Template
{
	public const Source = '_mainLayout.latte';

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
        <link rel="stylesheet" href="/css/_mainLayout.latte.css" />
        <script src="/js/startDarkmode-min.js"></script>
    </head>
    <body> 
        <!-- toast container -->
        <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

';
		$this->createTemplate('_topbar.latte', ['user' => $user] + $this->params, 'include')->renderToContentType('html') /* line 16 */;
		echo '        
';
		$this->renderBlock('content', get_defined_vars()) /* line 18 */;
		echo '
        <!-- Footer -->
        <footer class="footer bg-dark text-light">
            <div class="container py-3">
                <div class="doubbleDivContainer">
                    <div class="divLeft text-secondary" >
                        <p>&copy; 2025 Fancy Website. Alle Rechte vorbehalten.</p>
                    </div>
                    <div class="divRight text-secondary" style="color:var(--bs-secondary-text);">
                        Sessiontimeout in:&nbsp;<span id="remainingSessionTime" class="text-warning">00:00:00</span>
                    </div>
                </div>                 
            </div>
        </footer>
            
        <!-- Bootstrap JS  -->
        <script src="/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js" defer></script>

        <!-- bootstrap toasts -->
        <script src="/js/bootstrapToasts-min.js"></script>

        <!-- bootstrap popups -->
        <script src="/js/bootstrapPopup-min.js"></script>

        <!-- darkmode script -->
        <script src="/js/darkmode-min.js"></script>

        <!-- session timeout script -->
        <script type="module" src="/js/sessionTimeout-min.js"></script>

        <!-- configure and run sessiontimeout notification -->
        <div id="session-data" data-session-timeout="';
		echo LR\Filters::escapeHtmlAttr($sessionTimeout) /* line 50 */;
		echo '"></div>
        <script src="/js/_mainLayout.latte-min.js"></script>
        
    </body>
</html>';
	}


	/** {block content} on line 18 */
	public function blockContent(array $ʟ_args): void
	{
	}
}
