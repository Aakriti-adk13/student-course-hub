<?php
/**
 * actions/toggle-publish.php
 *
 * Toggles the is_published flag on a programme (1 → 0 or 0 → 1).
 * Called from:
 *   - admin/programmes.php  (toggle button in the programme list table)
 *   - admin/programme-edit.php (quick publish/unpublish button)
 *
 * Expects POST fields:
 *   programme_id  (int)            — ID of the programme to toggle, required
 *   redirect_to   (string, opt)    — URL to redirect back to after action
 *                                    defaults to admin/programmes.php
 *
 * On success: redirects to redirect_to with ?toggled=1&published=<new_state>
 * On error:   redirects to redirect_to with ?error=<code>
 *
 * Error codes:
 *   missing_id          — programme_id not provided
 *   invalid_programme   — programme_id does not exist in the database
 *   db_error            — unexpected database failure
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

// ── Redirect helper ────────────────────────────────────────────
function redirectWith(string $base, array $params): never {
    $sep = str_contains($base, '?') ? '&' : '?';
    header('Location: ' . $base . $sep . http_build_query($params));
    exit;
}

// ── Collect inputs ─────────────────────────────────────────────
$programmeId = isset($_POST['programme_id']) && $_POST['programme_id'] !== ''
                 ? (int)$_POST['programme_id']
                 : null;

$redirectTo = $_POST['redirect_to']
                ?? BASE_URL . '/admin/programmes.php';

// ── Validate ───────────────────────────────────────────────────
if (!$programmeId) {
    redirectWith($redirectTo, ['error' => 'missing_id']);
}

// ── Toggle in database ─────────────────────────────────────────
try {
    $db = getDB();

    // Fetch current published state
    $stmt = $db->prepare(
        "SELECT ProgrammeID, ProgrammeName, is_published
         FROM Programmes
         WHERE ProgrammeID = ?"
    );
    $stmt->execute([$programmeId]);
    $programme = $stmt->fetch();

    if (!$programme) {
        redirectWith($redirectTo, ['error' => 'invalid_programme']);
    }

    // Flip the flag
    $newState = $programme['is_published'] ? 0 : 1;

    $update = $db->prepare(
        "UPDATE Programmes SET is_published = ? WHERE ProgrammeID = ?"
    );
    $update->execute([$newState, $programmeId]);

    // Redirect back with result
    redirectWith($redirectTo, [
        'toggled'   => '1',
        'published' => $newState,
    ]);

} catch (PDOException $e) {
    error_log('toggle-publish DB error: ' . $e->getMessage());
    redirectWith($redirectTo, ['error' => 'db_error']);
}