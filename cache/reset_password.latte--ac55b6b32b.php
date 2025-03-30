<?php

use Latte\Runtime as LR;

/** source: reset_password.latte */
final class Template_ac55b6b32b extends Latte\Runtime\Template
{
	public const Source = 'reset_password.latte';

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

		$this->parentName = 'authLayout.latte';
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
        <form method="POST" id="resetPasswordForm" action="/reset-password" class="mt-3">
            <input type="hidden" name="token" value="';
		echo LR\Filters::escapeHtmlAttr(htmlspecialchars($token)) /* line 8 */;
		echo '">
            <div class="input-group mb-3">
                <input type="password" name="new_password" id="new_password" placeholder="Neues Passwort" class="form-control">
                <button class="btn btn-outline-secondary" type="button" id="generatePassword">
                    <i id="generatePasswordIcon" class="bi-arrow-clockwise text-info"></i>
                </button>
            </div>
            <div class="mb-3">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Passwort bestätigen" class="form-control">
            </div>
            <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 18 */;
		echo '">
            <button type="submit" id="submitButton" class="btn btn-primary w-100">
                <span id="loader" style="display:none;" class="btnloader"></span> <!-- Ladeindikator -->
                Passwort ändern
            </button>
        </form>
        <div id="error-message" style="color: #fff; display: none;"></div> <!-- Fehlerbereich -->
';
		if (isset($error)) /* line 25 */ {
			echo '        <p>';
			echo LR\Filters::escapeHtmlText($error) /* line 26 */;
			echo '</p>
';
		}
		if (isset($message)) /* line 28 */ {
			echo '            <p>';
			echo LR\Filters::escapeHtmlText($message) /* line 29 */;
			echo '</p>
';
		}
		echo '        <div class="p-3" style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:5;">
            <a href="/login">Zurück zum Login</a>
        </div>
    </div>
</div>

<script>
    function validatePasswords() {
        var newPassword = document.getElementById(\'new_password\').value;
        var confirmPassword = document.getElementById(\'confirm_password\').value;
        var errorMessage = document.getElementById(\'error-message\');

        if (newPassword !== confirmPassword) {
            errorMessage.textContent = "Die Passwörter stimmen nicht überein."; // Fehlermeldung anzeigen
            errorMessage.style.display = \'block\'; // Fehlerbereich anzeigen
            return false; // Formular nicht absenden
        }

        errorMessage.style.display = \'none\'; // Fehlerbereich ausblenden, wenn die Passwörter übereinstimmen
        return true; // Formular absenden
    }

    document.getElementById(\'resetPasswordForm\').onsubmit = function(event) {
        if (!validatePasswords()) { // Validierung durchführen
            event.preventDefault(); // Verhindert das Absenden des Formulars
        } else {
            document.getElementById(\'loader\').style.display = \'inline-block\'; // Kreisel anzeigen
            document.getElementById(\'submitButton\').disabled = true; // Button deaktivieren
        }
    };

    // Funktion zur Generierung eines sicheren Passworts
    function generatePassword() {
        let charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*()-_";
        let password = "";
        for (let i = 0; i < 16; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        return password;
    }

    // Passwort generieren
    document.getElementById("generatePassword").addEventListener("click", function () {
        let passwordField = document.getElementById("new_password");
        let repPwField = document.getElementById("confirm_password");
        let newPassword = generatePassword(); // Zufälliges Passwort generieren
        passwordField.value = newPassword;
        repPwField.value = newPassword;
    });
</script>
';
	}
}
