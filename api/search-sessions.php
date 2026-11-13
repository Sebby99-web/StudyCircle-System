<?php
declare(strict_types=1);

/**
 * StudyCircle-System
 * Fetch API search endpoint.
 */

header(
    'Content-Type: application/json; charset=utf-8'
);

header('Cache-Control: no-store');

header('X-Content-Type-Options: nosniff');

// Only allow GET requests.
if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
) {

    header('Allow: GET');

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'GET required.'
    ]);

    exit;
}

// LOAD SHARED FUNCTIONS
require __DIR__ . '/../includes/app.php';

require __DIR__ . '/../includes/session-service.php';

require __DIR__ . '/../includes/session-search.php';

$startedBuffer = false;

try {

    // Get currently logged-in account.
    $viewer = current_user();

    // Validate search filters.
    $filters = sc_search_filters($_GET);

    // Retrieve matching sessions.
    $sessions = sc_browse_sessions(
        $pdo,
        $viewer,
        $filters
    );

    // Render the existing session cards.
    $isMine = false;

    ob_start();
    $startedBuffer = true;

    require __DIR__ .
        '/../includes/session-card.php';

    $html = ob_get_clean();

    $startedBuffer = false;

    // Send results to JavaScript.
    echo json_encode(
        [
            'success' => true,
            'count' => count($sessions),
            'html' => $html
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

} catch (DomainException $e) {

    if ($startedBuffer) {
        ob_end_clean();
    }

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} catch (Throwable $e) {

    if ($startedBuffer) {
        ob_end_clean();
    }

    error_log(
        'StudyCircle-System search error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Search is unavailable right now. Please try again.'
    ]);
}
