<?php

use Latte\Runtime as LR;

/** source: admin/createUser.latte */
final class Template_6ee651199a extends Latte\Runtime\Template
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
			foreach (array_intersect_key(['role' => '56'], $this->params) as $ʟ_v => $ʟ_l) {
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
		foreach ($roles as $role) /* line 56 */ {
			echo '                <span class="role-badge badge bg-secondary p-2" data-value="';
			echo LR\Filters::escapeHtmlAttr($role['roleName']) /* line 57 */;
			echo '" style="cursor:pointer;">
                    ';
			echo LR\Filters::escapeHtmlText($role['roleName']) /* line 58 */;
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("userForm");
    const submitBtn = document.getElementById("submitBtn");
    const btnText = document.getElementById("btnText");
    const spinner = document.getElementById("spinner");
    const roleCheckboxes = document.querySelectorAll(".role-checkbox");
    const roleError = document.getElementById("roleError");
    const roleBadges = document.querySelectorAll(".role-badge");
    const rolesInput = document.getElementById("rolesInput");

    function updateRoles() {
        let selectedRoles = Array.from(document.querySelectorAll(".role-badge.selected")).map(el => el.dataset.value);
        rolesInput.value = selectedRoles.join(",");
        validateRoles(selectedRoles);
    }


    function validateRoles(selectedRoles) {
        roleError.style.display = "none";

        if (selectedRoles.length === 0) {
            roleError.innerText = "Bitte wählen Sie mindestens eine Rolle.";
            roleError.style.display = "block";
            roleError.classList.add("text-danger");
            roleError.classList.remove("text-info");
            return false;
        }
        if (selectedRoles.includes("User") && selectedRoles.length > 1) {
            roleError.innerText = "Die Rolle \'User\' kann nicht mit anderen Rollen kombiniert werden.";
            roleError.style.display = "block";
            roleError.classList.add("text-danger");
            roleError.classList.remove("text-info");
            return false;
        }
        if (selectedRoles.includes("Admin") && selectedRoles.length > 1) {
            roleError.innerText = "Die Rolle \'Admin\' kann nicht mit anderen Rollen kombiniert werden.";
            roleError.style.display = "block";
            roleError.classList.add("text-danger");
            roleError.classList.remove("text-info");
            return false;
        }
        roleError.classList.add("text-info");
        roleError.classList.remove("text-danger");
        roleError.innerText = "Bitte wählen Sie eine oder mehrere Rollen.";
        return true;
    }

    roleBadges.forEach(badge => {
        badge.addEventListener("click", function () {
            this.classList.toggle("selected");
            this.classList.toggle("bg-primary");
            this.classList.toggle("bg-secondary");
            updateRoles();
        });
    });

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        const selectedRoles = rolesInput.value ? rolesInput.value.split(",") : [];
        if (!form.checkValidity() || !validateRoles(selectedRoles)) {
            form.classList.add("was-validated");
            return;
        }
                
        submitBtn.disabled = true;
        btnText.classList.add("d-none");
        spinner.classList.remove("d-none");

        fetch("/admin/createUser", {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: new FormData(form),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Benutzer erfolgreich erstellt!");
                form.reset();
            } else {
                alert("Fehler: " + data.message);
            }
        })
        .catch(error => {
            alert("Ein Fehler ist aufgetreten: " + error);
        })
        .finally(() => {
            submitBtn.disabled = false;
            btnText.classList.remove("d-none");
            spinner.classList.add("d-none");
        });
    });

    // Funktion zur Generierung eines sicheren Passworts
    function generatePassword() {
        let charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*()-_";
        let password = "";
        for (let i = 0; i < 16; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        return password;
    }

    // Passwort anzeigen/verstecken
    document.getElementById("togglePassword").addEventListener("click", function () {
        let passwordField = document.getElementById("password");
        let eyeIcon = document.getElementById("togglePasswordIcon");

        if (passwordField.type === "password") {
            passwordField.type = "text";
            eyeIcon.classList.add(\'text-danger\'); 
            eyeIcon.classList.remove(\'text-success\');
            eyeIcon.classList.remove(\'bi-eye\');
            eyeIcon.classList.add(\'bi-eye-slash\');
        } else {
            passwordField.type = "password";
            eyeIcon.classList.add(\'text-success\'); 
            eyeIcon.classList.remove(\'text-danger\');
            eyeIcon.classList.remove(\'bi-eye-slash\');
            eyeIcon.classList.add(\'bi-eye\');
        }

        lucide.createIcons(); // Icons neu rendern
    });

    // Passwort generieren
    document.getElementById("generatePassword").addEventListener("click", function () {
        let passwordField = document.getElementById("password");
        let newPassword = generatePassword(); // Zufälliges Passwort generieren
        passwordField.value = newPassword;
    });
});
</script>
';
	}
}
