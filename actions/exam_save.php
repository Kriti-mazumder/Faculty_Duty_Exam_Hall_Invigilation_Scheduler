<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

$redir    = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_management.php';
$id       = (int)($_POST['exam_id']               ?? 0);
$courseId = (int)($_POST['course_id']             ?? 0);
$name     = trim($_POST['exam_name']              ?? '');
$date     = $_POST['exam_date']                   ?? '';
$start    = $_POST['start_time']                  ?? '';
$end      = $_POST['end_time']                    ?? '';
$req      = (int)($_POST['required_invigilators'] ?? 1);
$students = (int)($_POST['student_count']         ?? 0);
$status   = $_POST['status']                      ?? 'scheduled';

if (!$courseId || $name === '' || $date === '' || $start === '' || $end === '') {
    header('Location: ' . $redir . '?error=missing_fields'); exit();
}

try {
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE exam SET course_id=?,exam_name=?,exam_date=?,start_time=?,end_time=?,required_invigilators=?,student_count=?,status=? WHERE exam_id=?');
        $stmt->execute([$courseId,$name,$date,$start,$end,$req,$students,$status,$id]);
    } else {
        $stmt = db()->prepare('INSERT INTO exam (course_id,exam_name,exam_date,start_time,end_time,required_invigilators,student_count,status) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$courseId,$name,$date,$start,$end,$req,$students,$status]);
    }
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();
