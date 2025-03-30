<?php

use Latte\Runtime as LR;

/** source: errors/error.latte */
final class Template_13766b268d extends Latte\Runtime\Template
{
	public const Source = 'errors/error.latte';

	public const Blocks = [
		['content' => 'blockContent'],
	];


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		$this->renderBlock('content', get_defined_vars()) /* line 3 */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$this->parentName = '_errorLayout.latte';
		return get_defined_vars();
	}


	/** {block content} on line 3 */
	public function blockContent(array $ʟ_args): void
	{
		extract($this->params);
		extract($ʟ_args);
		unset($ʟ_args);

		$title = $code !== null ? 'Error ' . $code : 'Unknown Error' /* line 4 */;
		echo '    <div class="container">
    <div class="error-container">
        <h1 class="display-1">Oops!</h1>
        <h2 class="display-4">';
		echo LR\Filters::escapeHtmlText($title) /* line 8 */;
		echo '</h2>
        <div class="error-details mb-4">
            Sorry, an error has occurred. 
            <pre class="mb-3">
';
		if (isset($message)) /* line 12 */ {
			echo '                    ';
			echo LR\Filters::escapeHtmlText($message) /* line 13 */;
			echo "\n";
		}
		echo '            </pre>
        </div>
        <div class="error-actions d-flex flex-wrap gap-2 justify-content-center">
            <a href="/logout" class="btn btn-primary btn-lg" style="width:240px">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-house me-2" viewBox="0 0 16 16">
                    <path
                        d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5ZM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5 5 5Z"></path>
                </svg>
                Take Me the login
            </a>
            <a href="/contact" class="btn btn-outline-secondary btn-lg" style="width:240px">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    class="bi bi-envelope me-2" viewBox="0 0 16 16">
                    <path
                        d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"></path>
                </svg>
                Contact Support
            </a>
        </div>
    </div>
</div>
';
	}
}
