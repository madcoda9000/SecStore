document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const usernameInput = form.querySelector("input[name='username']");
    const passwordInput = form.querySelector("input[name='password']");

    function showError(input, message) {
        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains("invalid-feedback")) {
            errorElement = document.createElement("div");
            errorElement.classList.add("invalid-feedback");
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }
        errorElement.textContent = message;
    }

    function clearError(input) {
        let errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains("invalid-feedback")) {
            errorElement.remove();
        }
    }

    form.addEventListener("submit", function (event) {
        let isValid = true;

        // username Validierung
        if (usernameInput.value.trim() === "") {
            showError(usernameInput, "Bitte gib deinen Benutzernamen ein.");
            isValid = false;
        } else {
            clearError(usernameInput);
        }

        // Passwort Validierung
        if (passwordInput.value.trim() === "") {
            showError(passwordInput, "Bitte gib ein Passwort ein.");
            isValid = false;
        } else {
            clearError(passwordInput);
        }

        if (!isValid) {
            event.preventDefault(); // Verhindert das Absenden des Formulars, wenn Fehler vorliegen
        }
    });
});