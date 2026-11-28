document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-visibility-toggle').forEach(button => {
        const passwordInput = document.getElementById(button.getAttribute('aria-controls'));
        const icon = button.querySelector('i');

        button.addEventListener('click', function () {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(showPassword));
            button.setAttribute(
                'aria-label',
                `${showPassword ? 'Hide' : 'Show'} ${button.dataset.passwordLabel}`
            );
            icon.classList.toggle('fa-eye', !showPassword);
            icon.classList.toggle('fa-eye-slash', showPassword);
        });
    });
});
