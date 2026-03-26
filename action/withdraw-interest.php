<?php
/**
 * actions/withdraw-interest.php
 *
 * Allows a student to withdraw their registered interest in a programme,
 * or allows an admin to remove an interest record from the mailing list.
 *
 * Called from:
 *   - public/register-interest.php  (student withdraws their own interest)
 *   - public/programme-detail.php   (student withdraws from sidebar)
 *   - admin/students.php            (admin removes invalid/duplicate record)
 *
 * Expects POST fields:
 *   email           (string)          — student's email address (student-facing)
 *   programme_id    (int)             — programme to withdraw from (student-facing)
 *   interest_id     (int, optional)   — direct record ID (admin-facing, bypasses email lookup)
 *   redirect_to     (string, opt)     — URL to redirect back to after action
 *
 * Caller context is detected automatically:
 *   - If interest_id is provided AND admin session is active → admin delete by ID
 *   - Otherwise                                             → student withdraw by email + programme
 *
 * On success: redirects to redirect_to with ?withdrawn=1
 * On error:   redirects to redirect_to with ?error=<code>
 *
 * Error codes:
 *   missing_fields      — required fields not provided
 *   invalid_email       — email fails validation (student path)
 *   not_found           — no matching interest record found
 *   auth_error          — interest_id used without valid admin session
 *   db_error            — unexpected database failure
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

// ── Redirect helper ────────────────────────────────────────────
function redirectWith(string $base, array $params): never {
    $sep = str_contains($base, '?') ? '&' : '?';
    header('Location: ' . $base . $sep . http_build_query($params));
    exit;
}

// ── Collect inputs ─────────────────────────────────────────────
$interestId  = isset($_POST['interest_id']) && $_POST['interest_id'] !== ''
                 ? (int)$_POST['interest_id']
                 : null;

$programmeId = isset($_POST['programme_id']) && $_POST['programme_id'] !== ''
                 ? (int)$_POST['programme_id']
                 : null;

$email      = sanitizeEmail($_POST['email'] ?? '');
$redirectTo = $_POST['redirect_to'] ?? BASE_URL . '/public/programmes.php';

// ── Determine caller context ───────────────────────────────────
$isAdminDelete = ($interestId !== null);

try {
    $db = getDB();

    // ── ADMIN PATH: delete by interest_id ─────────────────────
    if ($isAdminDelete) {

        // Must have a valid admin session to use this path
        if (!isAdminLoggedIn()) {
            redirectWith($redirectTo, ['error' => 'auth_error']);
        }

        // Verify the record exists
        $check = $db->prepare(
            "SELECT InterestID FROM InterestedStudents WHERE InterestID = ?"
        );
        $check->execute([$interestId]);

        if (!$check->fetch()) {
            redirectWith($redirectTo, ['error' => 'not_found']);
        }

        $stmt = $db->prepare(
            "DELETE FROM InterestedStudents WHERE InterestID = ?"
        );
        $stmt->execute([$interestId]);

    // ── STUDENT PATH: withdraw by email + programme_id ─────────
    } else {

        // Both email and programme_id are required
        if (!$email || !$programmeId) {
            redirectWith($redirectTo, ['error' => 'missing_fields']);
        }

        if (!$email) {
            redirectWith($redirectTo, ['error' => 'invalid_email']);
        }

        // Find the matching record
        $check = $db->prepare(
            "SELECT InterestID FROM InterestedStudents
             WHERE ProgrammeID = ? AND Email = ?"
        );
        $check->execute([$programmeId, $email]);

        if (!$check->fetch()) {
            redirectWith($redirectTo, ['error' => 'not_found']);
        }

        $stmt = $db->prepare(
            "DELETE FROM InterestedStudents
             WHERE ProgrammeID = ? AND Email = ?"
        );
        $stmt->execute([$programmeId, $email]);
    }

    redirectWith($redirectTo, ['withdrawn' => '1']);

} catch (PDOException $e) {
    error_log('withdraw-interest DB error: ' . $e->getMessage());
    redirectWith($redirectTo, ['error' => 'db_error']);
}