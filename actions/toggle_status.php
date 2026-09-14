<?php
/**
 * toggle_status.php – Soft-delete / restore any record.
 * POST params: table, id_col, id, current_status, redirect
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method Not Allowed'); }

$allowed = ['faculty'=>'faculty_id', 'department'=>'department_id',
            'course'=>'course_id', 'room'=>'room_id', 'user_account'=>'user_id'];

$table  = $_POST['table']  ?? '';
$idCol  = $allowed[$table] ?? null;
$id     = (int)($_POST['id'] ?? 0);
$cur    = $_POST['current_status'] ?? 'active';
$redir  = $_POST['redirect'] ?? '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/admin_dashboard.php';

if (!$idCol || $id <= 0) {
    header('Location: ' . $redir . '?error=invalid_params'); exit();
}

$newStatus = ($cur === 'active') ? 'inactive' : 'active';

try {
    $stmt = db()->prepare("UPDATE `$table` SET status = ? WHERE `$idCol` = ?");
    $stmt->execute([$newStatus, $id]);
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
