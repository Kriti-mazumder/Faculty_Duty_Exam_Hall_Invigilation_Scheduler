<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$redir    = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/room_management.php';
$id       = (int)($_POST['room_id']   ?? 0);
$roomNo   = trim($_POST['room_no']    ?? '');
$building = trim($_POST['building']   ?? '');
$capacity = (int)($_POST['capacity']  ?? 0);
$type     = trim($_POST['room_type']  ?? '');
$status   = $_POST['status']          ?? 'active';

if ($roomNo === '' || $building === '' || $capacity <= 0) {
    header('Location: ' . $redir . '?error=missing_fields'); exit();
}

try {
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE room SET room_no=?,building=?,capacity=?,room_type=?,status=? WHERE room_id=?');
        $stmt->execute([$roomNo,$building,$capacity,$type,$status,$id]);
    } else {
        $stmt = db()->prepare('INSERT INTO room (room_no,building,capacity,room_type,status) VALUES (?,?,?,?,?)');
        $stmt->execute([$roomNo,$building,$capacity,$type,$status]);
    }
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
