<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$redir   = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/course_management.php';
$id      = (int)($_POST['course_id']      ?? 0);
$deptId  = (int)($_POST['department_id']  ?? 0);
$code    = trim($_POST['course_code']     ?? '');
$title   = trim($_POST['course_title']    ?? '');

if (!$deptId || $code === '' || $title === '') {
    header('Location: ' . $redir . '?error=missing_fields'); exit();
}

try {
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE course SET department_id=?, course_code=?, course_title=? WHERE course_id=?');
        $stmt->execute([$deptId, $code, $title, $id]);
    } else {
        $stmt = db()->prepare('INSERT INTO course (department_id, course_code, course_title) VALUES (?,?,?)');
        $stmt->execute([$deptId, $code, $title]);
    }
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
