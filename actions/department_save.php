<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$redir = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/department_management.php';
$id    = (int)($_POST['department_id'] ?? 0);
$name  = trim($_POST['department_name'] ?? '');

if ($name === '') { header('Location: ' . $redir . '?error=empty_name'); exit(); }

try {
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE department SET department_name = ? WHERE department_id = ?');
        $stmt->execute([$name, $id]);
    } else {
        $stmt = db()->prepare('INSERT INTO department (department_name) VALUES (?)');
        $stmt->execute([$name]);
    }
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
