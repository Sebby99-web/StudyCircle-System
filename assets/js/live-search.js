'use strict';

/**
 * StudyCircle-System
 * Live Search with Fetch API
 * Persistent filters using localStorage
 */

(() => {

    // ======================================
    // ELEMENTS
    // ======================================

    const form = document.getElementById(
        'sessionSearchForm'
    );

    const results = document.getElementById(
        'sessionResults'
    );

    const count = document.getElementById(
        'resultsCount'
    );

    const activity = document.getElementById(
        'searchActivity'
    );

    const errorBox = document.getElementById(
        'searchError'
    );

    const clear = document.getElementById(
        'clearSessionFilters'
    );

    if (
        !form ||
        !results ||
        !count ||
        !activity ||
        !errorBox ||
        !clear
    ) {
        return;
    }


    // ======================================
    // CONFIGURATION
    // ======================================

    const keys = [
        'q',
        'subject',
        'date',
        'venue'
    ];

    const storageKey = 'sc-system-last-filters';

    let debounce = null;

    let controller = null;

    let lastRequest = 0;


    // ======================================
    // READ CURRENT FILTERS
    // ======================================

    function values() {

        return Object.fromEntries(
            keys.map(key => [
                key,
                form.elements[key].value.trim()
            ])
        );
    }


    // ======================================
    // SAVE LAST-USED FILTERS
    // ======================================

    function remember(filters) {

        try {

            localStorage.setItem(
                storageKey,
                JSON.stringify(filters)
            );

        } catch (_) {

            // Live search still works without
            // localStorage support.

        }
    }


    // ======================================
    // RESTORE SAVED FILTERS
    // ======================================

    function restore() {

        // URL filters take priority.
        if (window.location.search !== '') {
            return false;
        }

        try {

            const saved = JSON.parse(
                localStorage.getItem(storageKey) || 'null'
            );

            if (
                !saved ||
                typeof saved !== 'object' ||
                Array.isArray(saved)
            ) {
                return false;
            }

            let changed = false;

            for (const key of keys) {

                if (typeof saved[key] !== 'string') {
                    continue;
                }

                const control = form.elements[key];

                // Ignore subjects removed by admin.
                if (
                    key === 'subject' &&
                    saved[key] &&
                    !Array.from(control.options).some(
                        option => option.value === saved[key]
                    )
                ) {
                    continue;
                }

                control.value = saved[key];

                changed ||= saved[key] !== '';
            }

            return changed;

        } catch (_) {

            return false;
        }
    }


    // ======================================
    // PERFORM FETCH SEARCH
    // ======================================

    async function runSearch() {

        const filters = values();

        // Save search preferences.
        remember(filters);

        const query = new URLSearchParams();

        for (
            const [key, value]
            of Object.entries(filters)
        ) {

            if (value) {
                query.set(key, value);
            }
        }

        const suffix = query.toString();

        // Update URL without refreshing page.
        history.replaceState(
            null,
            '',
            'sessions.php' +
            (suffix ? '?' + suffix : '')
        );

        // Cancel previous pending request.
        controller?.abort();

        const current = new AbortController();

        controller = current;

        const request = ++lastRequest;

        activity.textContent = 'Searching…';

        results.setAttribute(
            'aria-busy',
            'true'
        );

        errorBox.hidden = true;

        try {

            const response = await fetch(
                'api/search-sessions.php' +
                (suffix ? '?' + suffix : ''),
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',

                    headers: {
                        Accept: 'application/json'
                    },

                    signal: current.signal
                }
            );

            const payload = await response.json();

            if (
                !response.ok ||
                !payload.success
            ) {

                throw new Error(
                    payload.message ||
                    'Unable to search sessions.'
                );
            }

            // Ignore outdated results.
            if (
                request !== lastRequest ||
                current.signal.aborted
            ) {
                return;
            }

            // Replace only session cards.
            // The entire page does not reload.
            results.innerHTML = payload.html;

            count.textContent =
                `${payload.count} upcoming session(s)` +
                (
                    payload.count === 100
                        ? ' (first 100 shown)'
                        : ''
                );

            activity.textContent =
                'Results updated';

        } catch (error) {

            if (
                request !== lastRequest ||
                current.signal.aborted
            ) {
                return;
            }

            errorBox.textContent =
                error.message ||
                'Could not load sessions.';

            errorBox.hidden = false;

            activity.textContent =
                'Search failed';

        } finally {

            if (request === lastRequest) {

                results.setAttribute(
                    'aria-busy',
                    'false'
                );

                controller = null;
            }
        }
    }


    // ======================================
    // SEARCH DEBOUNCE
    // ======================================

    function queueSearch(delay) {

        clearTimeout(debounce);

        debounce = setTimeout(
            runSearch,
            delay
        );
    }


    // ======================================
    // SEARCH BUTTON
    // ======================================

    form.addEventListener(
        'submit',
        event => {

            event.preventDefault();

            queueSearch(0);
        }
    );


    // ======================================
    // SEARCH WHILE TYPING
    // ======================================

    for (const key of ['q', 'venue']) {

        form.elements[key].addEventListener(
            'input',
            () => queueSearch(300)
        );
    }


    // ======================================
    // SUBJECT AND DATE CHANGES
    // ======================================

    for (const key of ['subject', 'date']) {

        form.elements[key].addEventListener(
            'change',
            () => queueSearch(0)
        );
    }


    // ======================================
    // CLEAR FILTERS
    // ======================================

    clear.addEventListener(
        'click',
        event => {

            event.preventDefault();

            for (const key of keys) {

                form.elements[key].value = '';
            }

            queueSearch(0);
        }
    );


    // ======================================
    // RESTORE PREVIOUS FILTERS
    // ======================================

    if (restore()) {
        queueSearch(0);
    }

})();
