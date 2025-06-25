// js/script.js

document.addEventListener('DOMContentLoaded', () => {

    // --- Logique pour afficher/cacher le mot de passe ---
    const passwordToggles = document.querySelectorAll('.toggle-password');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const passwordInput = toggle.previousElementSibling;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggle.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggle.textContent = '👁️';
            }
        });
    });

    // --- Générateur de mot de passe et vérificateur de robustesse ---
    const passwordField = document.getElementById('password');
    const generatePasswordBtn = document.getElementById('generatePasswordBtn');
    const passwordStrengthSpan = document.getElementById('passwordStrength');

    if (generatePasswordBtn && passwordField) {
        generatePasswordBtn.addEventListener('click', () => {
            const newPassword = generateStrongPassword();
            passwordField.value = newPassword;
            updatePasswordStrength(newPassword);
        });
        passwordField.addEventListener('input', () => {
            updatePasswordStrength(passwordField.value);
        });
    }

    function generateStrongPassword(length = 16) {
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?";
        let password = "";
        password += getRandomChar("abcdefghijklmnopqrstuvwxyz");
        password += getRandomChar("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        password += getRandomChar("0123456789");
        password += getRandomChar("!@#$%^&*()-_=+[]{}|;:,.<>?");
        for (let i = password.length; i < length; i++) {
            password += getRandomChar(charset);
        }
        return password.split('').sort(() => 0.5 - Math.random()).join('');
    }

    function getRandomChar(charset) {
        return charset[Math.floor(Math.random() * charset.length)];
    }

    function updatePasswordStrength(password) {
        let strength = 0;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[!@#$%^&*()-_=+[]{}|;:,.<>?]/.test(password)) strength++;
        
        let strengthText = '';
        let strengthColor = '';

        switch (strength) {
            case 0: case 1: case 2:
                strengthText = 'Faible';
                strengthColor = 'var(--danger-color)';
                break;
            case 3:
                strengthText = 'Moyen';
                strengthColor = 'orange';
                break;
            case 4:
                strengthText = 'Fort';
                strengthColor = '#52a552';
                break;
            case 5:
                strengthText = 'Très Fort';
                strengthColor = 'var(--success-color)';
                break;
        }
        
        if (password.length === 0) {
            passwordStrengthSpan.innerHTML = '';
        } else {
            passwordStrengthSpan.innerHTML = `Robustesse: <span style="color: ${strengthColor}; font-weight: bold;">${strengthText}</span>`;
        }
    }

    // --- Animations au chargement de la page ---
    const fadeInElements = document.querySelectorAll('.fade-in-on-load');
    fadeInElements.forEach((el, index) => {
        const delay = index * 100;
        setTimeout(() => {
            el.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
            el.style.opacity = 1;
            el.style.transform = 'translateY(0)';
        }, 100 + delay);
    });

    // --- Animation des boutons au clic ---
    const animatedButtons = document.querySelectorAll('.animated-button, .animated-button-small');
    animatedButtons.forEach(button => {
        button.addEventListener('mousedown', () => button.classList.add('pressed'));
        button.addEventListener('mouseup', () => button.classList.remove('pressed'));
        button.addEventListener('mouseleave', () => button.classList.remove('pressed'));
        button.addEventListener('touchstart', () => button.classList.add('pressed'));
        button.addEventListener('touchend', () => button.classList.remove('pressed'));
    });

    // --- Animation du formulaire ---
    const animatedForm = document.querySelector('.animated-form');
    if (animatedForm) {
        animatedForm.style.opacity = 0;
        animatedForm.style.transform = 'translateY(30px)';
        setTimeout(() => {
            animatedForm.style.transition = 'opacity 0.7s ease-out, transform 0.7s ease-out';
            animatedForm.style.opacity = 1;
            animatedForm.style.transform = 'translateY(0)';
        }, 300);
    }
});