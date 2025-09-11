document.addEventListener("DOMContentLoaded", function () {
    const inputs = document.querySelectorAll(".otp-input");
    const hiddenInput = document.getElementById("otp-hidden");
    const submitButton = document.getElementById("otp-submit");

    function updateHiddenInput() {
        hiddenInput.value = Array.from(inputs).map(i => i.value).join("");
        submitButton.disabled = hiddenInput.value.length !== 6;
        if (hiddenInput.value.length === 6) {
            
            document.getElementById("otp-form").submit();
        }
    }

    inputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            // Erlaubt nur Zahlen
            input.value = input.value.replace(/\D/, "");

            if (input.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateHiddenInput();
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !input.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = ""; // Löscht die vorherige Eingabe
                updateHiddenInput();
            }
        });
    });
});