<?php

use Latte\Runtime as LR;

/** source: errors\_errorLayout.latte */
final class Template_36521a3931 extends Latte\Runtime\Template
{
	public const Source = 'errors\\_errorLayout.latte';

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
        <title>Error</title>
        <link rel="stylesheet" href="/bootstrap-5.3.3-dist/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css" /> 
        <style>
            .container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100%;
                margin:auto;
                }
            .error-container {
                padding: 100px 15px;
                text-align: center;
            }

            .error-actions {
                margin-top: 15px;
                margin-bottom: 15px;
            }

            .error-actions .btn {
                margin-right: 10px;
            }
        </style>
        <script>
        (function() {
            let darkMode = localStorage.getItem("darkmode");
            if (darkMode === "enabled") {
            document.documentElement.setAttribute("data-bs-theme", "dark");
            }
        })();
        </script>
    </head>
    <body>
';
		$this->renderBlock('content', get_defined_vars()) /* line 41 */;
		echo '    </body>';
	}


	/** {block content} on line 41 */
	public function blockContent(array $ʟ_args): void
	{
	}
}
