<?php
declare(strict_types=1);

/**
 * StudyCircle-System
 * Reusable live-search functions.
 */

function sc_search_filters(array $input): array
{
    $read = static function (
        string $key,
        int $max
    ) use ($input): string {

        $value = $input[$key] ?? '';

        if (!is_string($value)) {
            throw new DomainException(
                'Invalid search filter.'
            );
        }

        $value = trim($value);

        $length = function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);

        if ($length > $max) {
            throw new DomainException(
                'One of the search filters is too long.'
            );
        }

        return $value;
    };

    $subjectText = $read('subject', 12);
    $subjectId = null;

    if ($subjectText !== '') {

        $validId = filter_var(
            $subjectText,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1
                ]
            ]
        );

        if ($validId === false) {
            throw new DomainException(
                'Select a valid subject.'
            );
        }

        $subjectId = (int) $validId;
    }

    $date = $read('date', 10);

    if ($date !== '') {

        $parsed = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if (
            !preg_match(
                '/^\d{4}-\d{2}-\d{2}$/D',
                $date
            ) ||
            !$parsed ||
            $parsed->format('Y-m-d') !== $date
        ) {
            throw new DomainException(
                'Choose a valid date.'
            );
        }
    }

    return [
        'q' => $read('q', 120),
        'subject' => $subjectId,
        'date' => $date,
        'venue' => $read('venue', 180),
    ];
}


/**
 * Find upcoming sessions matching the filters.
 *
 * Includes membership counts and whether the
 * logged-in student has joined each session.
 */
function sc_browse_sessions(
    PDO $pdo,
    ?array $viewer,
    array $filters
): array {

    $sql = "
        SELECT
            s.*,
            sub.code AS subject_code,
            sub.name AS subject_name,
            host.full_name AS host_name,

            (
                SELECT COUNT(*)
                FROM session_members m
                WHERE m.session_id = s.id
            ) AS member_count,

            EXISTS(
                SELECT 1
                FROM session_members m
                WHERE m.session_id = s.id
                  AND m.user_id = ?
            ) AS joined

        FROM study_sessions s

        JOIN subjects sub
            ON sub.id = s.subject_id

        JOIN users host
            ON host.id = s.host_id

        WHERE s.status <> 'cancelled'

          AND TIMESTAMP(
              s.session_date,
              s.start_time
          ) > NOW()
    ";

    $params = [
        (int) ($viewer['id'] ?? 0)
    ];

    // Escape MySQL LIKE wildcard characters.
    $wildcard = static fn(string $value): string =>
        '%' . str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $value
        ) . '%';

    // SEARCH BY TOPIC, SUBJECT, OR HOST
    if ($filters['q'] !== '') {

        $sql .= "
            AND (
                s.topic LIKE ? ESCAPE '!'
                OR sub.code LIKE ? ESCAPE '!'
                OR sub.name LIKE ? ESCAPE '!'
                OR host.full_name LIKE ? ESCAPE '!'
            )
        ";

        $value = $wildcard($filters['q']);

        array_push(
            $params,
            $value,
            $value,
            $value,
            $value
        );
    }

    // SUBJECT FILTER
    if ($filters['subject'] !== null) {

        $sql .= ' AND s.subject_id = ?';

        $params[] = $filters['subject'];
    }

    // DATE FILTER
    if ($filters['date'] !== '') {

        $sql .= ' AND s.session_date = ?';

        $params[] = $filters['date'];
    }

    // VENUE FILTER
    if ($filters['venue'] !== '') {

        $sql .= "
            AND s.venue LIKE ? ESCAPE '!'
        ";

        $params[] = $wildcard(
            $filters['venue']
        );
    }

    // SHOW UPCOMING SESSIONS FIRST
    $sql .= '
        ORDER BY
            s.session_date,
            s.start_time,
            s.id
        LIMIT 100
    ';

    $statement = $pdo->prepare($sql);

    $statement->execute($params);

    return $statement->fetchAll();
}
