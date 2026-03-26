<?php
/**
 * admin/export-mailing.php — Exports the student interest list as a CSV file.
 * Optionally filtered by programme_id via GET parameter.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = getDB();

$filterProgramme = isset($_GET['programme_id']) && $_GET['programme_id'] !== ''
                     ? (int)$_GET['programme_id']
                     : null;

// Build query
$sql = "SELECT i.StudentName, i.Email, p.ProgrammeName,
               l.LevelName, i.RegisteredAt
        FROM InterestedStudents i
        JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
        JOIN Levels l ON p.LevelID = l.LevelID";
$params = [];

if ($filterProgramme) {
    $sql    .= " WHERE i.ProgrammeID = ?";
    $params[] = $filterProgramme;
}
$sql .= " ORDER BY p.ProgrammeName, i.RegisteredAt DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Build a descriptive filename
$suffix   = $filterProgramme ? '_programme_' . $filterProgramme : '_all';
$filename = 'mailing_list' . $suffix . '_' . date('Y-m-d') . '.csv';

// Stream CSV headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM for Excel UTF-8 compatibility
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// CSV column headers
fputcsv($out, ['Student Name', 'Email', 'Programme', 'Level', 'Registered At']);

foreach ($records as $row) {
    fputcsv($out, [
        $row['StudentName'],
        $row['Email'],
        $row['ProgrammeName'],
        $row['LevelName'],
        date('d/m/Y H:i', strtotime($row['RegisteredAt'])),
    ]);
}

fclose($out);
exit;