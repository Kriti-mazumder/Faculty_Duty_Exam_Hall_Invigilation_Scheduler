<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$redir     = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_room_management.php';
$examId    = (int)($_POST['exam_id']            ?? 0);
$roomId    = (int)($_POST['room_id']            ?? 0);
$allocated = (int)($_POST['allocated_students'] ?? 0);

if (!$examId || !$roomId) {
    header('Location: ' . $redir . '?error=missing_fields'); exit();
}

try {
    $stmt = db()->prepare('INSERT INTO exam_room (exam_id, room_id, allocated_students) VALUES (?,?,?)');
    $stmt->execute([$examId, $roomId, $allocated]);
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
