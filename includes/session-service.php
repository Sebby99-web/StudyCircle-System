<?php
declare(strict_types=1);

// Shared helpers for StudyCircle-System session pages.

function sc_field(array $data, string $key): string
{
    $value = $data[$key] ?? null;

    return is_string($value) || is_int($value)
        ? trim((string) $value)
        : '';
}

function sc_form_values(array $data = []): array
{
    return [
        'subject_id' => sc_field($data, 'subject_id'),
        'topic' => sc_field($data, 'topic'),
        'venue' => sc_field($data, 'venue'),
        'session_date' => sc_field($data, 'session_date'),
        'start_time' => substr(sc_field($data, 'start_time'), 0, 5),
        'end_time' => substr(sc_field($data, 'end_time'), 0, 5),
        'capacity' => array_key_exists('capacity', $data)
            ? sc_field($data, 'capacity')
            : '6',
    ];
}

function sc_date_time(
    string $date,
    string $time
): ?DateTimeImmutable {
    if (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) ||
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time)
    ) {
        return null;
    }

    $value = DateTimeImmutable::createFromFormat(
        '!Y-m-d H:i',
        $date . ' ' . $time,
        new DateTimeZone('Asia/Manila')
    );

    return $value &&
        $value->format('Y-m-d H:i') === "$date $time"
        ? $value
        : null;
}

function sc_session_started(array $session): bool
{
    $start = sc_date_time(
        (string) $session['session_date'],
        substr((string) $session['start_time'], 0, 5)
    );

    return $start === null ||
        $start <= new DateTimeImmutable(
            'now',
            new DateTimeZone('Asia/Manila')
        );
}

/**
 * Validate session details on the server.
 * Throws DomainException for invalid data.
 */
function sc_validate_session(
    PDO $pdo,
    array $data
): array {
    $values = sc_form_values($data);

    $subjectId = filter_var(
        $values['subject_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $capacity = filter_var(
        $values['capacity'],
        FILTER_VALIDATE_INT,
        ['options' => [
            'min_range' => 1,
            'max_range' => 50
        ]]
    );

    $length = static fn(string $text): int =>
        function_exists('mb_strlen')
            ? mb_strlen($text, 'UTF-8')
            : strlen($text);

    if ($subjectId === false) {
        throw new DomainException(
            'Select a valid subject.'
        );
    }

    if (
        $length($values['topic']) < 3 ||
        $length($values['topic']) > 160
    ) {
        throw new DomainException(
            'Topic must be between 3 and 160 characters.'
        );
    }

    if (
        $length($values['venue']) < 2 ||
        $length($values['venue']) > 180
    ) {
        throw new DomainException(
            'Venue must be between 2 and 180 characters.'
        );
    }

    if ($capacity === false) {
        throw new DomainException(
            'Capacity must be between 1 and 50 students.'
        );
    }

    $start = sc_date_time(
        $values['session_date'],
        $values['start_time']
    );

    $end = sc_date_time(
        $values['session_date'],
        $values['end_time']
    );

    if ($start === null || $end === null) {
        throw new DomainException(
            'Enter a valid date and start/end time.'
        );
    }

    if ($end <= $start) {
        throw new DomainException(
            'End time must be after start time on the same day.'
        );
    }

    if (
        $start <= new DateTimeImmutable(
            'now',
            new DateTimeZone('Asia/Manila')
        )
    ) {
        throw new DomainException(
            'The session must start in the future.'
        );
    }

    $check = $pdo->prepare(
        'SELECT 1 FROM subjects WHERE id = ?'
    );

    $check->execute([$subjectId]);

    if (!$check->fetchColumn()) {
        throw new DomainException(
            'That subject is no longer available.'
        );
    }

    return [
        'subject_id' => (int) $subjectId,
        'topic' => $values['topic'],
        'venue' => $values['venue'],
        'session_date' => $values['session_date'],
        'start_time' => $values['start_time'],
        'end_time' => $values['end_time'],
        'capacity' => (int) $capacity,
    ];
}
