/**
 * Ajoute un bouton afficher/masquer sur tous les champs password de la page.
 */
(function () {
    function createToggleButton(input) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary password-toggle-btn';
        button.setAttribute('aria-label', 'Afficher le mot de passe');
        button.setAttribute('title', 'Afficher le mot de passe');
        button.innerHTML = '<em class="fa fa-eye" aria-hidden="true"></em>';

        button.addEventListener('click', function () {
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            const icon = button.querySelector('em');
            if (icon) {
                icon.className = visible ? 'fa fa-eye' : 'fa fa-eye-slash';
            }
            button.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
            button.setAttribute('title', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        });

        return button;
    }

    function initPasswordToggle(input) {
        if (input.dataset.passwordToggle === '1') {
            return;
        }
        input.dataset.passwordToggle = '1';

        if (!input.classList.contains('form-control')) {
            input.classList.add('form-control');
        }

        const existingGroup = input.closest('.input-group');

        if (existingGroup) {
            input.classList.add('border-right-0');
            let append = existingGroup.querySelector('.input-group-append');
            if (!append) {
                append = document.createElement('div');
                append.className = 'input-group-append';
                existingGroup.appendChild(append);
            }
            append.insertBefore(createToggleButton(input), append.firstChild);
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'input-group with-focus password-toggle-wrapper';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.classList.add('border-right-0');

        const append = document.createElement('div');
        append.className = 'input-group-append';
        append.appendChild(createToggleButton(input));
        wrapper.appendChild(append);
    }

    function initAll() {
        document.querySelectorAll('input[type="password"]').forEach(initPasswordToggle);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
