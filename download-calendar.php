<?php
declare(strict_types=1);

require __DIR__ . '/includes/app.php';

$user = require_login();

$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    [
        'options' => ['min_range' => 1]
    ]
);

if (!is_int($id)) {
    http_response_code(404);
    exit('Session not found.');
}

// Retrieve the session and check membership.
$stmt = $pdo->prepare(
    "SELECT
        s.*,
        sub.code AS subject_code,
        host.full_name AS host_name,

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

     WHERE s.id = ?
     LIMIT 1"
);

$stmt->execute([
    (int) $user['id'],
    $id
]);

$session = $stmt->fetch();

if (!$session) {
    http_response_code(404);
    exit('Session not found.');
}

$isHost =
    (int) $session['host_id'] === (int) $user['id'];

$isMember = (int) $session['joined'] === 1;

if (!$isHost && !$isMember) {
    http_response_code(403);

    exit(
        'Join this session to download its calendar event.'
    );
}

if ($session['status'] === 'cancelled') {
    http_response_code(410);

    exit(
        'Cancelled sessions cannot be added to your calendar.'
    );
}

// CONVERT SESSION TIME TO UTC

$manila = new DateTimeZone('Asia/Manila');
$utc = new DateTimeZone('UTC');

try {
    $start = new DateTimeImmutable(
        $session['session_date'] . ' ' .
        $session['start_time'],
        $manila
    );

    $end = new DateTimeImmutable(
        $session['session_date'] . ' ' .
        $session['end_time'],
        $manila
    );

    if ($end <= $start) {
        throw new RuntimeException(
            'Invalid session time.'
        );
    }

} catch (Throwable $error) {
    http_response_code(422);
    exit('The session schedule is invalid.');
}

// ESCAPE ICALENDAR TEXT

function sc_ics_escape(string $value): string
{
    $value = str_replace(
        ["\r\n", "\r"],
        "\n",
        $value
    );

    return str_replace(
        ['\\', ';', ',', "\n"],
        ['\\\\', '\;', '\,', '\n'],
        $value
    );
}

// FOLD LONG ICALENDAR LINES

function sc_ics_fold(string $line): string
{
    $chars = preg_split(
        '//u',
        $line,
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    if ($chars === false) {
        return $line;
    }

    $parts = [];
    $current = '';
    $bytes = 0;

    foreach ($chars as $char) {
        $charBytes = strlen($char);

        if ($bytes + $charBytes > 73) {
            $parts[] = $current;
            $current = ' ';
            $bytes = 1;
        }

        $current .= $char;
        $bytes += $charBytes;
    }

    $parts[] = $current;

    return implode("\r\n", $parts);
}

$utcStamp = static fn(DateTimeImmutable $time): string =>
    $time->setTimezone($utc)->format('Ymd\THis\Z');

// CREATE CALENDAR EVENT

$lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//StudyCircle System//Study Session//EN',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'BEGIN:VEVENT',

    'UID:studycircle-system-' .
        $id . '@studycircle.invalid',

    'DTSTAMP:' . $utcStamp(
        new DateTimeImmutable('now', $utc)
    ),

    'DTSTART:' . $utcStamp($start),
    'DTEND:' . $utcStamp($end),

    'SUMMARY:' . sc_ics_escape(
        $session['subject_code'] . ': ' .
        $session['topic']
    ),

    'LOCATION:' . sc_ics_escape(
        (string) $session['venue']
    ),

    'DESCRIPTION:' . sc_ics_escape(
        'Study session hosted by ' .
        $session['host_name']
    ),

    'END:VEVENT',
    'END:VCALENDAR',
];

// SEND .ICS FILE TO THE BROWSER

header(
    'Content-Type: text/calendar; charset=utf-8; method=PUBLISH'
);

header(
    'Content-Disposition: attachment; filename="StudyCircle-Session-' .
    $id . '.ics"'
);

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

echo implode(
    "\r\n",
    array_map('sc_ics_fold', $lines)
) . "\r\n";
