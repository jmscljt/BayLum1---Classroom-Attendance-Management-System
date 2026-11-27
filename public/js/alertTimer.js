(() => {
    document.querySelectorAll('.alert').forEach((notification) => {
        notification.style.transition = 'opacity 0.3s ease';

        window.setTimeout(() => {
            notification.style.opacity = '0';
            window.setTimeout(() => notification.remove(), 300);
        }, 5000);
    });
})();
