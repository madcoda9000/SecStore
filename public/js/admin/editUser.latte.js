document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("userForm");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");
  const spinner = document.getElementById("spinner");
  const roleBadges = document.querySelectorAll(".role-badge");
  const rolesInput = document.getElementById("rolesInput");

  function updateRoles() {
    let selectedRoles = Array.from(document.querySelectorAll(".role-badge.selected")).map((el) => el.dataset.value);
    rolesInput.value = selectedRoles.join(",");
    validateRoles(selectedRoles);
  }

  function validateRoles(selectedRoles) {
    roleError.style.display = "none";

    if (selectedRoles.length === 0) {
      roleError.innerText = messages.error1;
      roleError.style.display = "block";
      roleError.classList.add("text-danger");
      roleError.classList.remove("text-info");
      return false;
    }
    if (selectedRoles.includes("User") && selectedRoles.length > 1) {
      roleError.innerText = messages.error2;
      roleError.style.display = "block";
      roleError.classList.add("text-danger");
      roleError.classList.remove("text-info");
      return false;
    }
    if (selectedRoles.includes("Admin") && selectedRoles.length > 1) {
      roleError.innerText = messages.error4;
      roleError.style.display = "block";
      roleError.classList.add("text-danger");
      roleError.classList.remove("text-info");
      return false;
    }
    roleError.classList.add("text-info");
    roleError.classList.remove("text-danger");
    roleError.innerText = messages.error4;
    return true;
  }

  roleBadges.forEach((badge) => {
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

    fetch("/admin/updateUser", {
      method: "POST",
      headers: { "X-Requested-With": "XMLHttpRequest" },
      body: new FormData(form),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast(`${data.message}`, "success", "Success");
        } else {
          showToast(`${data.message}`, "danger", "ERROR");
        }
      })
      .catch((error) => {
        showToast(`${error}`, "danger", "ERROR");
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
      eyeIcon.classList.add("text-danger");
      eyeIcon.classList.remove("text-success");
      eyeIcon.classList.remove("bi-eye");
      eyeIcon.classList.add("bi-eye-slash");
    } else {
      passwordField.type = "password";
      eyeIcon.classList.add("text-success");
      eyeIcon.classList.remove("text-danger");
      eyeIcon.classList.remove("bi-eye-slash");
      eyeIcon.classList.add("bi-eye");
    }
  });

  // Passwort generieren
  document.getElementById("generatePassword").addEventListener("click", function () {
    let passwordField = document.getElementById("password");
    let newPassword = generatePassword(); // Zufälliges Passwort generieren
    passwordField.value = newPassword;
  });
});
