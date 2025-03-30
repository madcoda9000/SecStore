<?php

use Latte\Runtime as LR;

/** source: admin/settings.latte */
final class Template_810aee391b extends Latte\Runtime\Template
{
	public const Source = 'admin/settings.latte';

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

		$this->parentName = '../_mainLayout.latte';
		return get_defined_vars();
	}


	/** {block content} on line 3 */
	public function blockContent(array $ʟ_args): void
	{
		extract($this->params);
		extract($ʟ_args);
		unset($ʟ_args);

		echo '
   <!-- Main Content -->
    <div class="container py-4" style="flex:1">
        <h1 class="mb-5">Change Application Settings..</h1>
';
		if (isset($success)) /* line 8 */ {
			echo '            <div class="alert alert-success">
                <i data-lucide="check-circle"></i> ';
			echo LR\Filters::escapeHtmlText($success) /* line 10 */;
			echo '
            </div>
';
		}
		echo "\n";
		if (isset($error)) /* line 14 */ {
			echo '            <div class="alert alert-error">
                <i data-lucide="x-circle"></i> ';
			echo LR\Filters::escapeHtmlText($error) /* line 16 */;
			echo '
            </div>
';
		}
		echo "\n";
		if (isset($message)) /* line 20 */ {
			echo '            <div class="alert alert-info">
                <i data-lucide="info"></i> ';
			echo LR\Filters::escapeHtmlText($message) /* line 22 */;
			echo '
            </div>
';
		}
		echo '
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                    <i class="bi-envelope" style="margin-right:10px;"></i>Email Settings...
                </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <form id="mailForm" method="post" action="/admin/updateMailSettings" style="padding:10px;">
                        <div class="mb-3">
                            <label for="host">SMTP-Host:</label>
                            <input class="form-control" type="text" name="host" id="host" value="';
		echo LR\Filters::escapeHtmlAttr($mail['host']) /* line 38 */;
		echo '" required>
                        </div>

                        <div class="mb-3">
                            <label for="username">Benutzername:</label>
                            <input type="text" class="form-control" id="username" name="username" value="';
		echo LR\Filters::escapeHtmlAttr($mail['username']) /* line 43 */;
		echo '" autocomplete="on" required>
                        </div>

                        <label for="mailpw">Passwort:</label>
                        <div class="input-group mb-3">                                
                            <input type="password" id="mailpw" name="password" value="';
		echo LR\Filters::escapeHtmlAttr($mail['password']) /* line 48 */;
		echo '" class="form-control" required>
                            <button type="button" id="togglePassword" class="btn btn-outline-secondary" title="toggle password visibilty..">
                                <i id="eyeIcon" class="bi-eye text-success"></i>
                            </button>
                        </div>

                        <div class="mb-3">

                        <label for="encryption">Verschlüsselung:</label>
                        <select class="form-select" aria-label="smtp encryption" id="encryption" name="encryption">
                            <option value="tls"';
		$ʟ_tmp = ['selected' => $mail['encryption'] === 'tls'];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 58 */;
		echo '>TLS</option>
                            <option value="ssl"';
		$ʟ_tmp = ['selected' => $mail['encryption'] === 'ssl'];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 59 */;
		echo '>SSL</option>
                        </select>
                        </div>

                        <div class="mb-3">
                            <label for="port">Port:</label>
                            <input class="form-control" type="number" name="port" id="port" value="';
		echo LR\Filters::escapeHtmlAttr($mail['port']) /* line 65 */;
		echo '" required>
                        </div>

                        <div class="mb-3">
                            <label for="fromEmail">Absender-E-Mail:</label>
                            <input class="form-control" type="email" name="fromEmail" id="fromEmail" value="';
		echo LR\Filters::escapeHtmlAttr($mail['fromEmail']) /* line 70 */;
		echo '" required>
                        </div>

                        <div class="mb-3">
                            <label for="fromName">Absender-Name:</label>
                            <input class="form-control" type="text" name="fromName" id="fromName" value="';
		echo LR\Filters::escapeHtmlAttr($mail['fromName']) /* line 75 */;
		echo '" required>
                        </div>

                        <div class="form-check mb-3">                                
                            <input class="form-check-input" type="checkbox" name="enableWelcomeMail" id="enableWelcomeMail"';
		$ʟ_tmp = ['checked' => $mail['enableWelcomeMail']];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 79 */;
		echo '>
                            <label for="enableWelcomeMail" class="form-check-label">
                            Willkommens-Mail aktivieren
                            </label>
                        </div>
                        <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 84 */;
		echo '">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save Mail Settings</button>
                    </form>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    <i class="bi-window" style="margin-right:10px;"></i>Application Settings...
                </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <form id="applicationForm" method="post" action="/admin/updateApplicationSettings" style="padding:10px;">
                            <div class="mb-3">
                                <label for="appUrl" appUrl>Application-URL:</label>
                                <input class="form-control" type="text" name="appUrl" id="appUrl" value="';
		echo LR\Filters::escapeHtmlAttr($application['appUrl']) /* line 101 */;
		echo '" required>
                            </div>
                            <div class="mb-3">
                                <label for="sessionTimeout">Sessiontimeout (in seconds):</label>
                                <input class="form-control" type="number" name="sessionTimeout" id="sessionTimeout" value="';
		echo LR\Filters::escapeHtmlAttr($application['sessionTimeout']) /* line 105 */;
		echo '" required>
                            </div>
                            <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 107 */;
		echo '">
                            <button class="btn btn-primary w-100" type="submit">Save Application Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                    <i class="bi-shield-exclamation" style="margin-right:10px;"></i>BruteForce Settings...
                </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <form id="bruteForceForm" method="post" action="/admin/updateBruteforceSettings" style="padding:10px;">
                        <div class="form-check mb-3">                                
                            <input class="form-check-input" type="checkbox" name="enableBruteForce" id="enableBruteForce"';
		$ʟ_tmp = ['checked' => $bruteForceSettings['enableBruteForce']];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 123 */;
		echo '>
                            <label for="enableBruteForce" class="form-check-label">
                                Enable BruteForce Protection
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="lockTime">Lockout time (in seconds):</label>
                            <input class="form-control" type="number" name="lockTime" id="lockTime" value="';
		echo LR\Filters::escapeHtmlAttr($bruteForceSettings['lockTime']) /* line 131 */;
		echo '" required>
                        </div>

                        <div class="mb-3">
                            <label for="maxAttempts">Max. failed Login Attempts:</label>
                            <input class="from-control" type="number" name="maxAttempts" id="maxAttempts" value="';
		echo LR\Filters::escapeHtmlAttr($bruteForceSettings['maxAttempts']) /* line 136 */;
		echo '" required>
                        </div>

                        <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 139 */;
		echo '">
                        <button class="btn btn-primary w-100 ,t-2" type="submit">Save BruteForce Settings</button>
                    </form>
                </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsefour" aria-expanded="false" aria-controls="collapsefour">
                    <i class="bi-card-text" style="margin-right:10px;"></i>Log Settings...
                </button>
                </h2>
                <div id="collapsefour" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <form id="loggingForm" method="post" action="/admin/updateLogSettings" style="padding:10px;">
                        <div class="form-check mb-3">                                
                            <input class="form-check-input" type="checkbox" name="enableSystemLogging" id="enableSystemLogging"';
		$ʟ_tmp = ['checked' => $logging['enableSystemLogging']];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 155 */;
		echo '>
                            <label for="enableSystemLogging" class="form-check-label">
                                Enable Logging for System messages?
                            </label>
                        </div>
                        <div class="form-check mb-3">                                
                            <input class="form-check-input" type="checkbox" name="enableMailLogging" id="enableMailLogging"';
		$ʟ_tmp = ['checked' => $logging['enableMailLogging']];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 161 */;
		echo '>
                            <label for="enableMailLogging" class="form-check-label">
                                Enable Logging for Mail messages?
                            </label>
                        </div>
                        <div class="form-check mb-3">                                
                            <input class="form-check-input" type="checkbox" name="enableAuditLogging" id="enableAuditLogging"';
		$ʟ_tmp = ['checked' => $logging['enableAuditLogging']];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 167 */;
		echo '>
                            <label for="enableAuditLogging" class="form-check-label">
                                Enable Logging for Audit messages?
                            </label>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"></use></svg>
                            <div>
                                NOTE: Request Logging can produce a huge amount of log entries in a very short time! Use only for debugging.
                            </div>
                        </div>
                        <div class="form-check mb-3">                                
                            <input class="form-check-input" type="checkbox" name="enableRequestLogging" id="enableRequestLogging"';
		$ʟ_tmp = ['checked' => $logging['enableRequestLogging']];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 179 */;
		echo '>
                            <label for="enableRequestLogging" class="form-check-label">
                                Enable Logging for Request messages?
                            </label>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"></use></svg>
                            <div>
                                NOTE: Database Logging can produce a huge amount of log entries in a very short time! Use only for debugging.
                            </div>
                        </div>
                        <div class="form-check mb-3">    
                            <input class="form-check-input" type="checkbox" name="enableSqlLogging" id="enableSqlLogging"';
		$ʟ_tmp = ['checked' => $logging['enableSqlLogging']];
		echo Latte\Essential\Nodes\NAttrNode::attrs(isset($ʟ_tmp[0]) && is_array($ʟ_tmp[0]) ? $ʟ_tmp[0] : $ʟ_tmp, false) /* line 191 */;
		echo '>                            
                            <label for="enableSqlLogging" class="form-check-label">
                                Enable Logging for Database queries?
                            </label>
                        </div>
                        <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 196 */;
		echo '">
                        <button class="btn btn-primary w-100 ,t-2" type="submit">Save Log Settings</button>
                    </form>
                </div>
                </div>
            </div>
        </div>    
    </div>
<script src="/js/admin/settings.latte-min.js"></script>
';
	}
}
