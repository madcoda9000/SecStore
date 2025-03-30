<?php

use Latte\Runtime as LR;

/** source: enable_2fa.latte */
final class Template_0dc16b3c34 extends Latte\Runtime\Template
{
	public const Source = 'enable_2fa.latte';

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

		echo '<!-- Bootstrap Modal -->
<div class="modal fade" id="copySuccessModal" tabindex="-1" aria-labelledby="copySuccessModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="copySuccessModalLabel">Erfolgreich kopiert</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body">
        ✅ Der Schlüssel wurde erfolgreich in die Zwischenablage kopiert!
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<div class="container">
    <div class="card p-4 shadow-sm login-card" style="width: 100%; max-width: 400px;">
        <h3>2FA Einrichten</h3>
        <p>Scanne diesen QR-Code mit einer Authenticator-App:</p>
        <img';
		$ʟ_tmp = ['src' => $qrCodeUrl];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 26 */;
		echo '>
        <p class="mt-3">Oder gib diesen Schlüssel manuell ein: <strong><span id="mfasec">';
		echo LR\Filters::escapeHtmlText($secret) /* line 27 */;
		echo '</span></strong>&nbsp;&nbsp;<i class="bi-clipboard text-info" id="cpclpd" style="cursor:pointer;" title="copy to clipboard.."></i></p>
        <div class="p-3" style="display:flex; flex-direction:column; justify-content:center; align-items:center; gap:5;">
            <p><a href="/2fa-verify/true">Weiter zur 2fa verifizierung..</a></p>
            <p><a href="/logout">Abbrechen: Zurück zur Anmeldung</a></p>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const copyButton = document.getElementById("cpclpd");
    const secretText = document.getElementById("mfasec");
    const modal = new bootstrap.Modal(document.getElementById("copySuccessModal"));

    if (copyButton && secretText) {
        copyButton.addEventListener("click", function () {
            navigator.clipboard.writeText(secretText.innerText)
                .then(() => modal.show()) // Zeigt das Modal bei Erfolg an
                .catch(() => alert("❌ Fehler beim Kopieren!"));
        });
    }
});
</script>


';
	}
}
