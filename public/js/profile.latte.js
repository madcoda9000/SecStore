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

// enable 2fa button mit Bootstrap Toast Fehlerbehandlung
document.getElementById("setEnable2faBtn").addEventListener("click", function () {
  const button = this;
  const originalText = button.innerHTML;
  const csrfToken = document.getElementById("csrf_token")?.value;

  if (!csrfToken) {
    throw new Error("CSRF token not found");
  }

  // Button während der Anfrage deaktivieren (verhindert Doppelklicks)
  button.disabled = true;
  button.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Wird aktiviert...';

  // Timeout für lange Anfragen (nach 10 Sekunden)
  const timeoutId = setTimeout(() => {
    if (button.disabled) {
      showToast(
        "Die Anfrage dauert ungewöhnlich lange. Bitte überprüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.",
        "warning",
        "Zeitüberschreitung"
      );
      resetButton();
    }
  }, 10000);

  fetch("/initiate2faSetup", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      "X-Requested-With": "XMLHttpRequest", // Explizit als AJAX markieren
    },
      body: new URLSearchParams({
        csrf_token: csrfToken,
      }),
  })
    .then((response) => {
      clearTimeout(timeoutId); // Timeout löschen

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        // Erfolgs-Toast anzeigen
        showToast(
          data.message || "2FA Setup wurde erfolgreich initiiert. Sie werden ausgeloggt...",
          "success",
          "2FA Setup"
        );

        // Kurz warten, damit der Toast sichtbar ist, dann ausloggen
        setTimeout(() => {
          window.location.href = "/logout";
        }, 1500);
      } else {
        // Fehler-Toast anzeigen
        showToast(data.message || "Fehler beim Initiieren des 2FA Setups.", "error", "2FA Setup Fehler");

        resetButton();
      }
    })
    .catch((error) => {
      clearTimeout(timeoutId); // Timeout löschen
      console.error("Error:", error);

      // Netzwerk- oder andere unerwartete Fehler
      showToast(
        "Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut oder kontaktieren Sie den Administrator.",
        "error",
        "Verbindungsfehler"
      );

      resetButton();
    });
});

// Hilfsfunktion zum Zurücksetzen des Buttons
function resetButton() {
  button.disabled = false;
  button.innerHTML = originalText;
}

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
