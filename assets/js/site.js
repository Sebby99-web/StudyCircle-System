'use strict';

/**
 * StudyCircle-System
 * Global Website Interactions
 *
 * Features:
 * - Persistent dark mode
 * - Show / Hide password
 */

(() => {

    // ======================================
    // DARK MODE
    // ======================================

    const themeToggle = document.getElementById(
        'themeToggle'
    );

    if (themeToggle) {

        themeToggle.addEventListener('click', () => {

            const current =
                document.documentElement.dataset.bsTheme;

            const next = current === 'dark'
                ? 'light'
                : 'dark';

            document.documentElement.dataset.bsTheme = next;

            try {
                localStorage.setItem(
                    'sc-system-theme',
                    next
                );
            } catch (error) {
                // Theme continues to work without storage.
            }

        });
    }


    // ======================================
    // SHOW / HIDE PASSWORD
    // ======================================

    const passwordButtons = document.querySelectorAll(
        '[data-password-toggle]'
    );

    passwordButtons.forEach(button => {

        const inputId = button.dataset.passwordToggle;

        const input = document.getElementById(inputId);

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {

            const showing = input.type === 'password';

            input.type = showing
                ? 'text'
                : 'password';

            button.textContent = showing
                ? 'Hide'
                : 'Show';

            button.setAttribute(
                'aria-pressed',
                showing ? 'true' : 'false'
            );

            const fieldName = inputId === 'confirm_password'
                ? 'confirm password'
                : 'password';

            button.setAttribute(
                'aria-label',
                showing
                    ? `Hide ${fieldName}`
                    : `Show ${fieldName}`
            );

        });

    });

})();
