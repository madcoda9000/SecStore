<?php

use Latte\Runtime as LR;

/** source: admin/createUser.latte */
final class Template_f24e80b5f7 extends Latte\Runtime\Template
{
	public const Source = 'admin/createUser.latte';

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

		if (!$this->getReferringTemplate() || $this->getReferenceType() === 'extends') {
			foreach (array_intersect_key(['role' => '58'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
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

<div class="container py-4" style="flex:1">
<h1 class="mb-5">Create new User..</h1>
    <form id="userForm" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 9 */;
		echo '">

        <div class="mb-3">
            <label for="firstname" class="form-label">Vorname</label>
            <input type="text" class="form-control" id="firstname" name="firstname" required>
            <div class="invalid-feedback">Bitte geben Sie einen Vornamen ein.</div>
        </div>
        
        <div class="mb-3">
            <label for="lastname" class="form-label">Nachname</label>
            <input type="text" class="form-control" id="lastname" name="lastname" required>
            <div class="invalid-feedback">Bitte geben Sie einen Nachnamen ein.</div>
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label">E-Mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="invalid-feedback">Bitte geben Sie eine gültige E-Mail-Adresse ein.</div>
        </div>
        
        <div class="mb-3">
            <label for="username" class="form-label">Benutzername</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback">Bitte geben Sie einen Benutzernamen ein.</div>
        </div>
        <label for="password" class="form-label">Passwort</label>
        <div class="input-group mb-3">            
            <input type="password" class="form-control" id="password" name="password" required>   
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i id="togglePasswordIcon" class="bi-eye text-success"></i>
            </button>
            <button class="btn btn-outline-secondary" type="button" id="generatePassword">
                <i id="generatePasswordIcon" class="bi-arrow-clockwise text-info"></i>
            </button>         
        </div>
        <div class="invalid-feedback">Bitte geben Sie ein Passwort ein.</div>
        
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="1" selected>Aktiv</option>
                <option value="0">Inaktiv</option>
            </select>
        </div>
        
        <div class="mb-3">
        <label class="form-label">Rollen</label>
        <div class="invalid-feedback d-block text-info" id="roleError" style="display: none;">Bitte wählen Sie eine oder mehrere Rollen.</div>
        <div id="rolesContainer" class="d-flex flex-wrap gap-2">
';
		foreach ($roles as $role) /* line 58 */ {
			echo '                <span class="role-badge badge bg-secondary p-2" data-value="';
			echo LR\Filters::escapeHtmlAttr($role['roleName']) /* line 59 */;
			echo '" style="cursor:pointer;">
                    ';
			echo LR\Filters::escapeHtmlText($role['roleName']) /* line 60 */;
			echo '
                </span>
';

		}

		echo '        </div>
        <input type="hidden" id="rolesInput" name="roles">        
    </div>
    <div class="d-flex justify-content-end gap-3">
        <button type="button" class="btn btn-secondary mr-3" id="backBtn" onclick="history.back()">Abbrechen</button>
        <button type="submit" class="btn btn-primary" style="width:200px;" id="submitBtn">
            <span id="btnText">Speichern</span>
            <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        </button>
    </div>    
        
    </form>
</div>

<script src="/js/admin/createUser.latte-min.js"></script>
';
	}
}
