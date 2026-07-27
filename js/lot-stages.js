document.addEventListener('DOMContentLoaded', function () {
    const message = document.querySelector('[data-stage-message]');

    document.querySelectorAll('[data-stage-locked]').forEach((button) => {
        button.addEventListener('click', function () {
            if (!message) return;
            message.textContent = button.dataset.message || 'Complete la fase anterior antes de continuar.';
            message.classList.remove('d-none');
            message.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
});
