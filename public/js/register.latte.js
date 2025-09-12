
document.getElementById("registerForm").onsubmit = function (event) {  
  let isValid = true;
  let errorMessage = "";

  // Eingabefelder abrufen
  let username = document.getElementById("username");
  let email = document.getElementById("email");
  let firstname = document.getElementById("firstname");
  let lastname = document.getElementById("lastname");
  let password = document.getElementById("password");

  // Fehlermeldungen zurücksetzen
  document.querySelectorAll(".error-message").forEach((el) => (el.textContent = ""));

  // Validierungen durchführen
  if (username.value.trim() === "" || username.value.length < 3) {
    showError(username, messages.val3);
    isValid = false;
  }

  if (email.value.trim() === "" || !validateEmail(email.value)) {
    showError(email, messages.val6);
    isValid = false;
  }

  if (firstname.value.trim() === "") {
    showError(firstname, messages.val1);
    isValid = false;
  }

  if (lastname.value.trim() === "") {
    showError(lastname, messages.val2);
    isValid = false;
  }

  if (password.value.length < 12) {
    showError(password, messages.val4);
    isValid = false;
  }

  // Falls Fehler vorhanden sind, Formular nicht absenden
  if (!isValid) {
    event.preventDefault();
  }

  document.getElementById('loader').style.display = 'inline-block'; // Kreisel anzeigen
  document.getElementById('submitButton').disabled = true; // Button deaktivieren
};

// Funktion zur Anzeige der Fehlermeldungen
function showError(inputElement, message) {
  let errorSpan;

  // Überprüfen, ob das Eingabefeld das Passwortfeld ist
  if (inputElement.id === "password") {
    let errorDiv = inputElement.closest("div"); // Das umgebende div abrufen
    errorSpan = errorDiv.querySelector(".error-message"); // Prüfen, ob bereits eine Fehlermeldung vorhanden ist

    if (!errorSpan) {
      errorSpan = document.createElement("span");
      errorSpan.classList.add("error-message");
      errorSpan.style.color = "red";
      errorSpan.style.display = "block";
      errorDiv.parentNode.insertBefore(errorSpan, errorDiv.nextSibling); // Fehlermeldung nach dem div einfügen
    }
  } else {
    // Für andere Eingabefelder die Fehlermeldung direkt nach dem Eingabefeld hinzufügen
    errorSpan = inputElement.nextElementSibling;

    if (!errorSpan || !errorSpan.classList.contains("error-message")) {
      errorSpan = document.createElement("span");
      errorSpan.classList.add("error-message");
      errorSpan.style.color = "red";
      errorSpan.style.display = "block";
      inputElement.parentNode.insertBefore(errorSpan, inputElement.nextSibling);
    }
  }

  errorSpan.textContent = message; // Fehlermeldung setzen
}

// Funktion zur E-Mail-Validierung
function validateEmail(email) {
  let re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

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
