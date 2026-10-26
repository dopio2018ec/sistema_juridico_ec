document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var mensaje = form.getAttribute('data-confirm');
            if (!window.confirm(mensaje)) {
                event.preventDefault();
            }
        });
    });
});
