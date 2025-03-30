<?php

use Latte\Runtime as LR;

/** source: _authLayout.latte */
final class Template_89d169c0d9 extends Latte\Runtime\Template
{
	public const Source = '_authLayout.latte';

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
        <link rel="stylesheet" href="/css/_authLayout.latte-min.css" />
        <script src="/js/startDarkmode-min.js"></script>        
    </head>
    <body>
';
		$this->renderBlock('content', get_defined_vars()) /* line 13 */;
		echo '
        <!-- Darkmode Toggle -->
        <button id="darkmodeToggle" class="darkmode-toggle">
            <i id="darkmodeIcon" class="bi bi-moon"></i>
        </button>
        
        <!-- Bootstrap JS  -->
        <script src="/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js" defer></script>

        <!-- darkmode script -->
        <script src="/js/darkmode-min.js"></script>>
    </body>
</html>';
	}


	/** {block content} on line 13 */
	public function blockContent(array $ʟ_args): void
	{
	}
}
