<?php

use Latte\Runtime as LR;

/** source: admin/editUser.latte */
final class Template_c8dd2df868 extends Latte\Runtime\Template
{
	public const Source = 'admin/editUser.latte';

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
			foreach (array_intersect_key(['role' => '65'], $this->params) as $ʟ_v => $ʟ_l) {
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

		echo '<div class="container py-4" style="flex:1">
<h1 class="mb-5">Benutzer bearbeiten</h1>
    <form id="userForm" class="needs-validation" novalidate>
        <input type="hidden" name="id" value="';
		echo LR\Filters::escapeHtmlAttr($userToEdit['id']) /* line 7 */;
		echo '">
        <input type="hidden" name="csrf_token" value="';
		echo LR\Filters::escapeHtmlAttr($_SESSION['csrf_token']) /* line 8 */;
		echo '">
        
        <div class="mb-3">
            <label for="firstname" class="form-label">Vorname</label>
            <input type="text" class="form-control" id="firstname" name="firstname" value="';
		echo LR\Filters::escapeHtmlAttr($userToEdit['firstname']) /* line 12 */;
		echo '" required>
            <div class="invalid-feedback">Bitte geben Sie einen Vornamen ein.</div>
        </div>
        
        <div class="mb-3">
            <label for="lastname" class="form-label">Nachname</label>
            <input type="text" class="form-control" id="lastname" name="lastname" value="';
		echo LR\Filters::escapeHtmlAttr($userToEdit['lastname']) /* line 18 */;
		echo '" required>
            <div class="invalid-feedback">Bitte geben Sie einen Nachnamen ein.</div>
        </div>
        
        <div class="mb-3">
            <label for="email" class="form-label">E-Mail</label>
            <input type="email" class="form-control" id="email" name="email" value="';
		echo LR\Filters::escapeHtmlAttr($userToEdit['email']) /* line 24 */;
		echo '" required>
            <div class="invalid-feedback">Bitte geben Sie eine gültige E-Mail-Adresse ein.</div>
        </div>
        
        <div class="mb-3">
            <label for="username" class="form-label">Benutzername</label>
            <input type="text" class="form-control" id="username" name="username" value="';
		echo LR\Filters::escapeHtmlAttr($userToEdit['username']) /* line 30 */;
		echo '" required>
            <div class="invalid-feedback">Bitte geben Sie einen Benutzernamen ein.</div>
        </div>

        <div class="alert alert-info d-flex align-items-center" role="alert">
            <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Info:"><use xlink:href="#info-fill"></use></svg>
            <div>
                NOTE: if you don\'t want to change the users password, simply leave the field empty.
            </div>
        </div>

        <label for="password" class="form-label">Passwort</label>
        <div class="input-group mb-3">            
            <input type="password" class="form-control" id="password" name="password">   
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i id="togglePasswordIcon" class="bi-eye text-success"></i>
            </button>
            <button class="btn btn-outline-secondary" type="button" id="generatePassword">
                <i id="generatePasswordIcon" class="bi-arrow-clockwise text-info"></i>
            </button>         
        </div>
        
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="1" ';
		if ($userToEdit['status'] == 1) /* line 55 */ {
			echo 'selected';
		}
		echo '>Aktiv</option>
                <option value="0" ';
		if ($userToEdit['status'] == 0) /* line 56 */ {
			echo 'selected';
		}
		echo '>Inaktiv</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Rollen</label>
            <div class="invalid-feedback d-block text-info" id="roleError" style="display: none;">Bitte wählen Sie eine oder mehrere Rollen.</div>
            <div id="rolesContainer" class="d-flex flex-wrap gap-2">
';
		$userRoles = explode(',', $userToEdit['roles']) /* line 64 */;
		foreach ($roles as $role) /* line 65 */ {
			echo '                    <span class="role-badge badge ';
			if (in_array($role['roleName'], $userRoles)) /* line 66 */ {
				echo 'bg-primary selected';
			} else /* line 66 */ {
				echo 'bg-secondary';
			}
			echo ' p-2" data-value="';
			echo LR\Filters::escapeHtmlAttr($role['roleName']) /* line 66 */;
			echo '" style="cursor:pointer;">
                        ';
			echo LR\Filters::escapeHtmlText($role['roleName']) /* line 67 */;
			echo '
                    </span>
';

		}

		echo '            </div>
            <input type="hidden" id="rolesInput" name="roles" value="';
		echo LR\Filters::escapeHtmlAttr($userToEdit['roles']) /* line 71 */;
		echo '">        
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

<script src="/js/admin/editUser.latte-min.js"></script>
';
	}
}
