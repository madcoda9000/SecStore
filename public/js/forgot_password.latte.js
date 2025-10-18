/**
 * Forgot Password Form Validation
 * CSP-compliant external JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    
    if (!forgotPasswordForm) {
        return; // Form not found, exit
    }
    
    forgotPasswordForm.addEventListener('submit', function(event) {
        let isValid = true;
        const emailField = document.getElementById('email');
        const emailError = document.getElementById('emailError');
        const loader = document.getElementById('loader');
        const submitButton = document.getElementById('submitButton');
        
        // Simple email validation with regex
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailPattern.test(emailField.value.trim())) {
            emailError.style.display = 'block';
            isValid = false;
        } else {
            emailError.style.display = 'none';
        }
        
        if (!isValid) {
            event.preventDefault(); // Don't submit form if validation fails
        } else {
            // Show loader and disable button to prevent double submissions
            if (loader) {
                loader.style.display = 'inline-block';
            }
            if (submitButton) {
                submitButton.disabled = true;
            }
        }
    });
});