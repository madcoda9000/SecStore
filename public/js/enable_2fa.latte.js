document.addEventListener("DOMContentLoaded", function () {
    const copyButton = document.getElementById("cpclpd");
    const secretText = document.getElementById("mfasec");
    const modal = new bootstrap.Modal(document.getElementById("copySuccessModal"));

    if (copyButton && secretText) {
        copyButton.addEventListener("click", function () {
            navigator.clipboard.writeText(secretText.innerText)
                .then(() => modal.show()) // Zeigt das Modal bei Erfolg an
                .catch(() => alert(messages.error1));
        });
    }
});