<?php

use Latte\Runtime as LR;

/** source: login.latte */
final class Template_91b4e428fc extends Latte\Runtime\Template
{
	public const Source = 'login.latte';

	public const Blocks = [
		['content' => 'blockContent'],
	];


	public function main(array $ʟ_args): void
	{
		extract($ʟ_args);
		unset($ʟ_args);

		echo "\n";
		$this->renderBlock('content', get_defined_vars()) /* line 3 */;
	}


	public function prepare(): array
	{
		extract($this->params);

		$this->parentName = '_authLayout.latte';
		return get_defined_vars();
	}


	/** {block content} on line 3 */
	public function blockContent(array $ʟ_args): void
	{
		extract($this->params);
		extract($ʟ_args);
		unset($ʟ_args);

		echo '    <script src="/js/login.latte-min.js"></script>
    <div class="container">
        <div class="card p-4 shadow-sm login-card" style="width: 100%; max-width: 400px;">
            <h3 class="text-center">';
		echo LR\Filters::escapeHtmlText($title) /* line 7 */;
		echo '</h3>
            <form method="POST" action="/login" class="mt-3">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername</label>
                    <input type="text" class="form-control" id="username" name="username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 17 */;
		echo '">
                <button type="submit" class="btn btn-primary w-100">Anmelden</button>
            </form>
';
		if (isset($error)) /* line 20 */ {
			echo '                <div class="alert alert-danger mt-3" role="alert">
                    ';
			echo LR\Filters::escapeHtmlText($error) /* line 22 */;
			echo '
                </div>
';
		}
		if (isset($message)) /* line 25 */ {
			echo '                <div class="alert alert-primary" role="alert">
                    ';
			echo LR\Filters::escapeHtmlText($message) /* line 27 */;
			echo '
                </div>
';
		}
		echo '            <div class="p-3" style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:5;">
                <a href="/forgot-password">Passwort vergessen?</a>
                <a href="/register">Registrieren</a>
            </div>
        </div>
    </div>
    <script src="/js/login.latte-min.js"></script>
    <script>
        let sessTT = ';
		echo LR\Filters::escapeJs($sessionTimeout) /* line 38 */;
		echo ';
        console.log(\'Auto refresh in:\' + sessTT * 1000 + \'ms\');
        setTimeout(function(){
            window.location.reload(1);
        }, (sessTT * 1000));
    </script>
';
	}
}
