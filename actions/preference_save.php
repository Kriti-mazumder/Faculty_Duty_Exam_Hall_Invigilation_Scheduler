<?php
$required_role = 'any';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$pdo = db();
$userRole = $_SESSION['role'] ?? '';
$userId   = (int) ($_SESSION['user_id'] ?? 0);

$myFacultyId = 0;
if ($userRole !== 'admin') {
    $r = $pdo->prepare('SELECT faculty_id FROM faculty WHERE user_id = ?');
    $r->execute([$userId]);
    $myFacultyId = (int) ($r->fetchColumn() ?: 0);
    if (!$myFacultyId) {
        header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/duty_preference_management.php?error=' . urlencode('Faculty profile not found.'));
        exit();
    }
}

$redir     = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/duty_preference_management.php';
$facId     = $userRole === 'admin' ? (int)($_POST['faculty_id'] ?? 0) : $myFacultyId;
$prefId    = (int)($_POST['preference_id'] ?? 0);
$date      = trim($_POST['preferred_date'] ?? '');
$time      = !empty($_POST['preferred_time']) ? trim($_POST['preferred_time']) : null;
$prefType  = in_array($_POST['preference_type'] ?? '', ['preferred', 'neutral', 'avoid']) ? $_POST['preference_type'] : 'neutral';
$priority  = (int)($_POST['priority'] ?? 0);

if (!$facId || $date === '') {
    header('Location: ' . $redir . '?error=' . urlencode('Please provide a preferred date.'));
    exit();
}

try {
    if ($prefId > 0) {
        if ($userRole === 'admin') {
            $stmt = $pdo->prepare('UPDATE duty_preference SET preferred_date=?,preferred_time=?,preference_type=?,priority=? WHERE preference_id=?');
            $stmt->execute([$date,$time,$prefType,$priority,$prefId]);
        } else {
            $stmt = $pdo->prepare('UPDATE duty_preference SET preferred_date=?,preferred_time=?,preference_type=?,priority=? WHERE preference_id=? AND faculty_id=?');
            $stmt->execute([$date,$time,$prefType,$priority,$prefId,$myFacultyId]);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO duty_preference (faculty_id,preferred_date,preferred_time,preference_type,priority) VALUES (?,?,?,?,?)');
        $stmt->execute([$facId,$date,$time,$prefType,$priority]);
    }
    header('Location: ' . $redir . '?success=1');
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()));
}
exit();

