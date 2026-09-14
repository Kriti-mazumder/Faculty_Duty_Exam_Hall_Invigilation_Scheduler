<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$redir      = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_room_management.php';
$examRoomId = (int)($_POST['exam_room_id'] ?? 0);
$facultyId  = (int)($_POST['faculty_id']   ?? 0);
$role       = $_POST['duty_role']          ?? 'assistant';

if (!$examRoomId || !$facultyId) {
    header('Location: ' . $redir . '?error=missing_fields'); exit();
}

try {
    // Prevent duplicate assignment
    $exists = db()->prepare('SELECT assignment_id FROM invigilation_assignment WHERE exam_room_id=? AND faculty_id=?');
    $exists->execute([$examRoomId, $facultyId]);
    if ($exists->fetch()) {
        header('Location: ' . $redir . '?error=already_assigned'); exit();
    }
    $stmt = db()->prepare('INSERT INTO invigilation_assignment (exam_room_id,faculty_id,duty_role,assignment_status) VALUES (?,?,?,\'assigned\')');
    $stmt->execute([$examRoomId,$facultyId,$role]);
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
