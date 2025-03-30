<?php

use Latte\Runtime as LR;

/** source: forgot_password.latte */
final class Template_3f977c08fb extends Latte\Runtime\Template
{
	public const Source = 'forgot_password.latte';

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
        <form method="POST" id="forgotPasswordForm" action="/forgot-password" class="mt-3">
            <div class="mb-3">
                <input type="email" name="email" id="email" placeholder="E-Mail" class="form-control">
                <p id="emailError" class="error-message text-danger" style="display: none;">Bitte eine gültige E-Mail-Adresse eingeben.</p>
            </div>
            
            <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 13 */;
		echo '">
            
            <button type="submit" id="submitButton" class="btn btn-primary w-100">
                <span id="loader" style="display:none;" class="btnloader"></span> <!-- Ladeindikator -->
                Passwort zurücksetzen
            </button>
        </form>

';
		if (isset($error)) /* line 21 */ {
			echo '            <p>';
			echo LR\Filters::escapeHtmlText($error) /* line 22 */;
			echo '</p>
';
		}
		if (isset($message)) /* line 24 */ {
			echo '            <p>';
			echo LR\Filters::escapeHtmlText($message) /* line 25 */;
			echo '</p>
';
		}
		echo '        <div class="p-3" style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:5;">
            <a href="/login">Zurück zum Login</a>
        </div>
    </div>
</div>

<script>
    document.getElementById(\'forgotPasswordForm\').onsubmit = function(event) {
        let isValid = true;
        let emailField = document.getElementById(\'email\');
        let emailError = document.getElementById(\'emailError\');

        // Einfache E-Mail-Validierung mit regulärem Ausdruck
        let emailPattern = /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/;

        if (!emailPattern.test(emailField.value.trim())) {
            emailError.style.display = \'block\';
            isValid = false;
        } else {
            emailError.style.display = \'none\';
        }

        if (!isValid) {
            event.preventDefault(); // Formular nicht absenden, falls Fehler vorliegen
        } else {
            document.getElementById(\'loader\').style.display = \'inline-block\'; // Kreisel anzeigen
            document.getElementById(\'submitButton\').disabled = true; // Button deaktivieren, um Doppelabsendungen zu verhindern
        }
    };
</script>
';
	}
}
