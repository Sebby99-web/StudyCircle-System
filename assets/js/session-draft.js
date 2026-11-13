'use strict';

// StudyCircle-System
// Automatically save unfinished session creation forms.

(() => {
    const form = document.getElementById('sessionForm');

    if (
        !form ||
        form.dataset.draftMode !== 'create'
    ) {
        return;
    }

    const userId = form.dataset.userId;

    if (
        !/^\d+$/.test(userId || '') ||
        userId === '0'
    ) {
        return;
    }

    const key = `sc-system-host-draft-${userId}`;

    const names = [
        'subject_id',
        'topic',
        'venue',
        'session_date',
        'start_time',
        'end_time',
        'capacity'
    ];

    const message = document.getElementById(
        'draftStatus'
    );

    const clear = document.getElementById(
        'clearHostDraft'
    );

    let timer;

    // CAPTURE FORM DATA

    function capture() {
        return Object.fromEntries(
            names.map(name => [
                name,
                String(form.elements[name]?.value ?? '')
            ])
        );
    }

    // SAVE DRAFT

    function save() {
        try {
            localStorage.setItem(
                key,
                JSON.stringify(capture())
            );

            if (message) {
                message.textContent =
                    'Draft saved on this device.';
            }

        } catch {
            if (message) {
                message.textContent =
                    'Browser draft storage unavailable.';
            }
        }
    }

    // DETECT SERVER-SIDE FORM POSTBACK

    const hasSubmittedValues = [
        'topic',
        'venue',
        'session_date',
        'start_time',
        'end_time'
    ].some(
        name => String(
            form.elements[name]?.value ?? ''
        ).trim() !== ''
    );

    if (hasSubmittedValues) {
        // Keep submitted values after a validation error.
        save();

    } else {
        // RESTORE SAVED DRAFT

        try {
            const saved = JSON.parse(
                localStorage.getItem(key) || 'null'
            );

            if (
                saved &&
                typeof saved === 'object' &&
                !Array.isArray(saved)
            ) {
                for (const name of names) {
                    const field = form.elements[name];

                    if (
                        !field ||
                        typeof saved[name] !== 'string'
                    ) {
                        continue;
                    }

                    // Ignore subjects removed by admin.
                    if (
                        name === 'subject_id' &&
                        saved[name] &&
                        !Array.from(field.options).some(
                            option =>
                                option.value === saved[name]
                        )
                    ) {
                        continue;
                    }

                    field.value = saved[name];
                }

                if (message) {
                    message.textContent =
                        'Saved draft restored.';
                }
            }

        } catch {
            // Ignore invalid or unavailable storage.
        }
    }

    // AUTO-SAVE ON INPUT

    const scheduleSave = () => {
        clearTimeout(timer);

        timer = setTimeout(save, 180);
    };

    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);

    // CLEAR FORM AND DRAFT

    clear?.addEventListener('click', () => {
        clearTimeout(timer);

        form.reset();

        form.elements.capacity.value = '6';

        try {
            localStorage.removeItem(key);
        } catch {}

        if (message) {
            message.textContent = 'Draft cleared.';
        }
    });

    // CLEAR DRAFT ON NORMAL SUBMISSION

    form.addEventListener('submit', () => {
        if (!form.checkValidity()) {
            return;
        }

        clearTimeout(timer);

        try {
            localStorage.removeItem(key);
        } catch {}

        // If PHP rejects the form, it re-renders the
        // submitted values and this script saves them again.
    });

})();
