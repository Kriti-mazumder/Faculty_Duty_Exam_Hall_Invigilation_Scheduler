<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$redir       = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/manual_duty_reassignment.php';
$assignId    = (int)($_POST['assignment_id']   ?? 0);
$newFacultyId= (int)($_POST['new_faculty_id']  ?? 0);

if (!$assignId || !$newFacultyId) {
    header('Location: ' . $redir . '?error=missing_fields'); exit();
}

try {
    $stmt = db()->prepare('UPDATE invigilation_assignment SET faculty_id = ? WHERE assignment_id = ?');
    $stmt->execute([$newFacultyId, $assignId]);
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
