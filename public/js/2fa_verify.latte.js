/**
 * 2FA Verification Script
 * Handles OTP input and backup code toggle
 * File: public/js/2fa_verify.latte.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // OTP input handling
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpHidden = document.getElementById('otp-hidden');
    const otpSubmit = document.getElementById('otp-submit');
    const otpForm = document.getElementById('otp-form');
    const backupForm = document.getElementById('backup-form');
    const toggleLink = document.getElementById('toggle-backup-code');
    const subtitle = document.getElementById('verify-subtitle');
    const messagesElement = document.getElementById('2fa-verify-messages');
    
    let isBackupMode = false;

    // OTP Input Logic
    if (otpInputs.length > 0) {
        otpInputs.forEach((input, index) => {
            // Auto-focus next input
            input.addEventListener('input', function(e) {
                if (this.value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                    checkOtpComplete();
                }
            });

            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Only allow numbers
            input.addEventListener('keypress', function(e) {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });

            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').trim();
                const digits = pastedData.replace(/\D/g, '').slice(0, 6);
                
                digits.split('').forEach((digit, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = digit;
                    }
                });
                
                if (digits.length === 6) {
                    otpInputs[5].focus();
                }
                
                checkOtpComplete();
            });
        });

        // Check if all OTP inputs are filled
        function checkOtpComplete() {
            const allFilled = Array.from(otpInputs).every(input => input.value.length === 1);
            
            if (allFilled) {
                const otpCode = Array.from(otpInputs).map(input => input.value).join('');
                otpHidden.value = otpCode;
                otpSubmit.disabled = false;
            } else {
                otpSubmit.disabled = true;
            }
        }

        // Auto-submit on complete
        otpForm.addEventListener('submit', function(e) {
            const otpCode = Array.from(otpInputs).map(input => input.value).join('');
            otpHidden.value = otpCode;
        });
    }

    // Backup Code Toggle Logic
    if (toggleLink && otpForm && backupForm) {
        toggleLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            isBackupMode = !isBackupMode;
            
            if (isBackupMode) {
                // Switch to backup code mode
                otpForm.style.display = 'none';
                backupForm.style.display = 'block';
                
                // Update link text
                toggleLink.innerHTML = '<i class="bi bi-phone"></i> ' + 
                    (messagesElement ? messagesElement.getAttribute('data-back-to-code') : 'Back to authenticator code');
                
                // Update subtitle
                if (subtitle && messagesElement) {
                    subtitle.textContent = messagesElement.getAttribute('data-subtitle-backup');
                }
                
                // Focus backup input
                const backupInput = document.getElementById('backup-code-input');
                if (backupInput) {
                    backupInput.focus();
                }
            } else {
                // Switch back to OTP mode
                otpForm.style.display = 'block';
                backupForm.style.display = 'none';
                
                // Update link text
                toggleLink.innerHTML = '<i class="bi bi-key"></i> ' + 
                    (messagesElement ? messagesElement.getAttribute('data-use-backup') : 'Use backup code instead');
                
                // Update subtitle
                if (subtitle && messagesElement) {
                    subtitle.textContent = messagesElement.getAttribute('data-subtitle-otp');
                }
                
                // Focus first OTP input
                if (otpInputs[0]) {
                    otpInputs[0].focus();
                }
            }
        });
    }

    // Backup Code Input Formatting
    const backupInput = document.getElementById('backup-code-input');
    if (backupInput) {
        backupInput.addEventListener('input', function(e) {
            let value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            let err = "ljköl";
            // Auto-format with dash
            if (value.length > 4) {
                value = value.slice(0, 4) + '-' + value.slice(4, 8);
            }
            
            this.value = value;
        });

        // Handle paste
        backupInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').trim().toUpperCase();
            const cleaned = pastedData.replace(/[^A-Za-z0-9]/g, '');
            
            if (cleaned.length >= 4) {
                this.value = cleaned.slice(0, 4) + '-' + cleaned.slice(4, 8);
            } else {
                this.value = cleaned;
            }
        });
    }
});