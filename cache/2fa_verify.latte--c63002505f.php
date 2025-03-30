<?php

use Latte\Runtime as LR;

/** source: 2fa_verify.latte */
final class Template_c63002505f extends Latte\Runtime\Template
{
	public const Source = '2fa_verify.latte';

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

		echo '    <style>
    .otp-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 15px 0;
    }
    </style>
    <div class="container">
        <div class="card p-4 shadow-sm login-card" style="width: 100%; max-width: 400px;">
            <h3 class="text-center">2fa Authentifizierung</h3>
            <h6 class="text-center text-body-secondary">bitte otp code eingeben..</h6>
            <form id="otp-form" method="POST" action="/2fa-verify" class="mt-3">
                <div class="otp-container">
                    <input type="text" maxlength="1" class="otp-input form-control" name="otp1" required autofocus>
                    <input type="text" maxlength="1" class="otp-input form-control" name="otp2" required>
                    <input type="text" maxlength="1" class="otp-input form-control" name="otp3" required>
                    <input type="text" maxlength="1" class="otp-input form-control" name="otp4" required>
                    <input type="text" maxlength="1" class="otp-input form-control" name="otp5" required>
                    <input type="text" maxlength="1" class="otp-input form-control" name="otp6" required>
                </div>
                <input type="hidden" name="otp" id="otp-hidden">
                <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 26 */;
		echo '">
                <button type="submit" class="btn btn-primary w-100 mt-8" id="otp-submit" disabled>Bestätigen</button>
            </form>

';
		if (isset($error)) /* line 30 */ {
			echo '                <p class="text-danger text-center mt-3">';
			echo LR\Filters::escapeHtmlText($error) /* line 31 */;
			echo '</p>
';
		}
		echo '
        <div class="p-3" style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:5;">
            <p><a href="/logout">Abbrechen und abmelden</a></p>
        </div>
    </div>
    

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const inputs = document.querySelectorAll(".otp-input");
            const hiddenInput = document.getElementById("otp-hidden");
            const submitButton = document.getElementById("otp-submit");

            function updateHiddenInput() {
                hiddenInput.value = Array.from(inputs).map(i => i.value).join("");
                submitButton.disabled = hiddenInput.value.length !== 6;
                if (hiddenInput.value.length === 6) {
                    document.getElementById("otp-form").submit();
                }
            }

            inputs.forEach((input, index) => {
                input.addEventListener("input", (e) => {
                    // Erlaubt nur Zahlen
                    input.value = input.value.replace(/\\D/, "");

                    if (input.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                    updateHiddenInput();
                });

                input.addEventListener("keydown", (e) => {
                    if (e.key === "Backspace" && !input.value && index > 0) {
                        inputs[index - 1].focus();
                        inputs[index - 1].value = ""; // Löscht die vorherige Eingabe
                        updateHiddenInput();
                    }
                });
            });
        });
    </script>
</div>
';
	}
}
