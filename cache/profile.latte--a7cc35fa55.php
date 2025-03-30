<?php

use Latte\Runtime as LR;

/** source: profile.latte */
final class Template_a7cc35fa55 extends Latte\Runtime\Template
{
	public const Source = 'profile.latte';

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

		$this->parentName = '_mainLayout.latte';
		return get_defined_vars();
	}


	/** {block content} on line 3 */
	public function blockContent(array $ʟ_args): void
	{
		extract($this->params);
		extract($ʟ_args);
		unset($ʟ_args);

		echo '
    <!-- The 2fa Modal -->
    <div class="modal fade" id="2faModal" tabindex="-1" aria-labelledby="copySuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copySuccessModalLabel">2FA Authentifizierung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <p>Möchten Sie für Ihren Account die 2FA Athentifizierung aktivieren? HINWEIS: nach dem klick auf "aktivieren" werden Sie automatisch abgemeldet. Beim nächsten Login startet dann der 2FA-Wizard automatisch.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="setEnable2faBtn">Ja, aktivieren!</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Nein</button>
            </div>
            </div>
        </div>
    </div>

    <!-- The 2fa enable / disable modal -->
    <div class="modal fade" id="2faModale" tabindex="-1" aria-labelledby="copySuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copySuccessModalLabel">2FA Authentifizierung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <p id="2faModaleText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-4" style="flex:1">  
        <h1 class="mb-5">Manage your data..</h1>        
';
		if (isset($success)) /* line 45 */ {
			echo '            <div class="alert alert-success d-flex align-items-center" role="alert">
                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Success:"><use xlink:href="#check-circle-fill"></use></svg>
                <div>
                    ';
			echo LR\Filters::escapeHtmlText($success) /* line 49 */;
			echo '
                </div>
            </div>
';
		}
		echo "\n";
		if (isset($error)) /* line 54 */ {
			echo '            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"></use></svg>
                <div>
                    ';
			echo LR\Filters::escapeHtmlText($error) /* line 58 */;
			echo '
                </div>
            </div>
';
		}
		echo "\n";
		if (isset($message)) /* line 63 */ {
			echo '            <div class="alert alert-primary d-flex align-items-center" role="alert">
                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Info:"><use xlink:href="#info-fill"></use></svg>
                <div>
                    ';
			echo LR\Filters::escapeHtmlText($message) /* line 67 */;
			echo '
                </div>
            </div>
';
		}
		echo '        <div class="accordion">
            <div class="accordion" id="accordionExample">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <i class="bi-key" style="margin-right:15px;;"></i>Change your password..
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <form id="pwResetForm" method="post" action="/profileChangePassword">
                            <div class="mb-3">
                                <input type="password" name="old_password" id="old_password" placeholder="Old Password" class="form-control">
                            </div>
                            <div class="input-group mb-3">
                                <input type="password" name="new_password" id="new_password" placeholder="New Password" class="form-control">
                                <button type="button" id="togglePassword" class="btn btn-outline-secondary" title="toggle password visibilty..">
                                    <i id="eyeicon" class="bi-eye text-success"></i>                                    
                                </button>
                                <button type="button" id="generatePassword" class="btn btn-outline-secondary" title="generate secure password..">
                                    <i class="bi-arrow-clockwise text-info"></i>
                                </button>
                            </div>
                            <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 94 */;
		echo '">
                            <button class="btn btn-primary w-100" type="submit">Change Password..</button>
                        </form>
                    </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            <i class="bi-envelope" style="margin-right:15px;"></i>Change your email address..
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <form id="changeEmailAddress" method="post" action="/profileChangeEmail">
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Info:"><use xlink:href="#info-fill"></use></svg>
                                <div>
                                    NOTE: on successful change, you\'ll be logged out automatically!
                                </div>
                            </div>
                            <div class="mb-3">
                                <input style="border:none !important;" type="text" class="form-control" readonly value="Current email: ';
		echo LR\Filters::escapeHtmlAttr($user['email'] ?? '') /* line 116 */;
		echo '">
                            </div>
                            <div class="mb-3">
                                <input type="text" placeholder="new email address.." name="new_email" id="new_email" class="form-control">
                            </div>
                            <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 121 */;
		echo '">
                            <button class="btn btn-primary w-100" type="submit">Change email address..</button>
                        </form>
                    </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        <i class="bi-shield-exclamation" style="margin-right:10px;margin-bottom:-2px;"></i>2FA Authentication
                    </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <form id="mfaSwitchForm" method="post" action="';
		echo LR\Filters::escapeHtmlAttr(LR\Filters::safeUrl($user['mfaEnabled'] === 1 ? '/disable-2fa' : '/enable-2fa')) /* line 135 */;
		echo '">
                            <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 136 */;
		echo '">
';
		if ($user['mfaEnabled'] === 1 && $user['mfaEnforced'] === 0) /* line 137 */ {
			echo '                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Success:"><use xlink:href="#info-fill"></use></svg>
                                    <div>
                                        Great! You\'ve configured 2fa successfully. It can be disabled (not recommended!!) bei using the switch below.
                                    </div>       
                                </div>                         
';
		}
		if ($user['mfaEnabled'] === 1 && $user['mfaEnforced'] === 1) /* line 145 */ {
			echo '                                <div class="alert alert-warning d-flex align-items-center" role="alert">
                                    <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Warning:"><use xlink:href="#info-fill"></use></svg>
                                    <div>
                                        Great! You\'ve configured 2fa successfully. It cannot be disabled as your Administrator has enforced 2FA Authentication for your account.
                                    </div>
                                </div>                          
';
		}
		if ($user['mfaEnabled'] === 0 && $user['mfaEnforced'] === 0 && !$user['mfaSecret']) /* line 153 */ {
			echo '                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Danger:"><use xlink:href="#info-fill"></use></svg>
                                    <div>
                                        2FA Authentication is disabled! It is highly recommended to protect your account using 2FA Authentication. Click the button below to enable 2FA authentication.
                                    </div>  
                                </div> 
                                <form id="enable2fa">
                                    <input type="hidden" name="csrf_token" value="';
			echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 161 */;
			echo '">
                                    <button id="enable2fabutton" type="button" class="btn btn-primary w-100">Enable 2FA authentication!</button>
                                </form>
';
		}
		if ($user['mfaSecret'] !== '') /* line 165 */ {
			echo '                                <h2>Your current 2FA Setup (';
			echo LR\Filters::escapeHtmlText($user['mfaEnabled'] === 1 ? '2fa is enabled' : '2fa is disabled') /* line 166 */;
			echo ')</h2>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" onchange="switch2fa(';
			echo LR\Filters::escapeHtmlAttr(LR\Filters::escapeJs($user['mfaEnabled'])) /* line 168 */;
			echo ');" type="checkbox" role="switch" id="mfaSwitch" ';
			if ($user['mfaEnabled'] === 1) /* line 168 */ {
				echo 'checked';
			}
			echo ' ';
			if ($user['mfaEnforced'] === 1) /* line 168 */ {
				echo 'disabled';
			}
			echo '>
                                    <label class="form-check-label" for="mfaSwitch">';
			echo LR\Filters::escapeHtmlText($user['mfaEnabled'] === 1 ? 'Disable 2FA' : 'Enable 2FA') /* line 169 */;
			echo '</label>
                                </div>
                                <div class="alert alert-info d-flex align-items-center" role="alert">
                                    <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Info:"><use xlink:href="#info-fill"></use></svg>
                                    <div>
                                        If you\'ve lost or damaged your device, you can here setup your 2FA authentication using the existing secret without the need of execunting the 2fa wizard again. Simply scan the barcode with your OTP-App or enter your secret manually.
                                    </div>
                                </div>  
                                <div class="qr-container">
                                    <div style="width:215px;">
                                        <img';
			$ʟ_tmp = ['src' => $qrCodeUrl];
			echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 179 */;
			echo '>
                                    </div>
                                    <div style="padding-top:15px;">
                                        <span><b>Your current 2FA Secret:</b></span><br><br>
                                        <span>';
			echo LR\Filters::escapeHtmlText($user['mfaSecret']) /* line 183 */;
			echo '</span>
                                    </div>
                                </div>  
';
		}
		echo '                        </form>                            
';
		if ($user['mfaEnforced'] === 0 && $user['mfaEnabled'] === 1 && !$user['mfaSecret']) /* line 188 */ {
			echo '                            <br>
                            <hr>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Danger:"><use xlink:href="#info-fill"></use></svg>
                                <div>
                                    By clicking the button below, you can reset and disable 2FA authentication. NOTE: your 2fa secret will be deleted permanently. This cannot be made undone! <br><br>After clicking the button, your 2fa setup will be resetted und you\'ll be logged out automatically!
                                </div>
                            </div>   
                            <form id="reset2fa" method="post" action="/disableAndReset2FA">
                                <div class="mb-3">
                                    <input type="hidden" name="csrf_token" value="';
			echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 199 */;
			echo '">
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Disable and reset my 2fa settings!</button>
                            </form>
';
		}
		echo '                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="/js/profile.latte-min.js"></script>
';
	}
}
