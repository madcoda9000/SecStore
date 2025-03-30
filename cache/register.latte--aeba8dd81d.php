<?php

use Latte\Runtime as LR;

/** source: register.latte */
final class Template_aeba8dd81d extends Latte\Runtime\Template
{
	public const Source = 'register.latte';

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

		echo '<div class="container">
        <div class="card p-4 shadow-sm login-card" style="width: 100%; max-width: 500px;">
            <h3 class="text-center">';
		echo LR\Filters::escapeHtmlText($title) /* line 6 */;
		echo '</h3>
            <form method="POST" action="/register" id="registerForm" class="mt-3">
                <div class="mb-3">
                <input class="form-control" type="text" name="username" id="username" placeholder="Username">
                </div>
                <div class="mb-3">
                <input class="form-control" type="email" name="email" id="email" placeholder="E-Mail">
                </div>
                <div class="mb-3">
                <input class="form-control" type="text" name="firstname" id="firstname" placeholder="Vorname">
                </div>
                <div class="mb-3">
                <input class="form-control" type="text" name="lastname" id="lastname" placeholder="Nachname">
                </div>
                <div class="input-group mb-3">
                    <input class="form-control" type="password" name="password" id="password" placeholder="Passwort">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i id="togglePasswordIcon" class="bi-eye text-success"></i>
                    </button>
                    <button class="btn btn-outline-secondary" type="button" id="generatePassword">
                        <i id="generatePasswordIcon" class="bi-arrow-clockwise text-info"></i>
                    </button>
                </div>
                <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 29 */;
		echo '">
                <button type="submit" class="btn btn-primary w-100">Registrieren</button>
            </form>
            <div id="error-message" style="color: #fff; display: none;"></div>
';
		if (isset($error)) /* line 33 */ {
			echo '            <p>';
			echo LR\Filters::escapeHtmlText($error) /* line 34 */;
			echo '</p>
';
		}
		if (isset($message)) /* line 36 */ {
			echo '                <p>';
			echo LR\Filters::escapeHtmlText($message) /* line 37 */;
			echo '</p>
';
		}
		echo '            <div class="p-3" style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:5;">
            <a href="/login">Login</a>
            </div>
        </div>
    </div>
</div>  
<script src="/js/register.latte-min.js"></script>

';
	}
}
