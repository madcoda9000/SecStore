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
  let passwordField = document.getElementById("new_password");
  let eyeIcon = document.getElementById("eyeicon");

  if (passwordField.type === "password") {
    passwordField.type = "text";
    eyeIcon.classList.remove("bi-eye");
    eyeIcon.classList.remove("text-success");
    eyeIcon.classList.add("bi-eye-slash");
    eyeIcon.classList.add("text-danger");
  } else {
    passwordField.type = "password";
    eyeIcon.classList.remove("bi-eye-slash");
    eyeIcon.classList.remove("text-danger");
    eyeIcon.classList.add("bi-eye");
    eyeIcon.classList.add("text-success");
  }
});

// Passwort generieren
document.getElementById("generatePassword").addEventListener("click", function () {
  let passwordField = document.getElementById("new_password");
  let newPassword = generatePassword(); // Zufälliges Passwort generieren
  passwordField.value = newPassword;
});

// show enable modal dialog
document.addEventListener("DOMContentLoaded", function () {
  const modal = new bootstrap.Modal(document.getElementById("2faModal"));

  enable2faBtn = document.getElementById("enable2fabutton");
  if (enable2faBtn) {
    enable2faBtn.addEventListener("click", function () {
      modal.show();
    });
  }
});

// enable 2fa button
document.getElementById("setEnable2faBtn").addEventListener("click", function () {
  fetch("/initiate2faSetup", { method: "POST" })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        window.location.href = "/logout";
      }
    });
});

// enable / disable 2fa switch
function switch2fa(mfaEnabled) {
  faSwitch = document.getElementById("mfaSwitch");
  const modal = new bootstrap.Modal(document.getElementById("2faModale"));
  if (faSwitch) {
    fetchAction = "/disable-2fa";
    if (faSwitch.checked === true) {
      fetchAction = "/enable-2fa";
    }
    fetch(fetchAction, { method: "POST" })
      .then((response) => response.json())
      .then((data) => {
        console.log(data);
        if (data.success === true && fetchAction == "/disable-2fa") {
          document.getElementById("2faModaleText").innerText = "2FA Authentication disabled successfully!";
          modal.show();
        } else if (data.success === true && fetchAction == "/enable-2fa") {
          document.getElementById("2faModaleText").innerText = "2FA Authentication enabled successfully!";
          modal.show();
        }
      });
  }
}
