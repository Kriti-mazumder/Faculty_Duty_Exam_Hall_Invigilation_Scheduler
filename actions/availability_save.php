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

if ($userRole === 'admin') {
    $redir = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_availability.php';
    header('Location: ' . $redir . '?error=' . urlencode('Admins cannot modify faculty availability.'));
    exit();
}

// Resolve current faculty id for non-admin users
$r = $pdo->prepare('SELECT faculty_id FROM faculty WHERE user_id = ?');
$r->execute([$userId]);
$myFacultyId = (int) ($r->fetchColumn() ?: 0);
if (!$myFacultyId) {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_availability.php?error=' . urlencode('Faculty profile not found.'));
    exit();
}

$action = $_POST['action'] ?? 'save';
$avId   = (int) ($_POST['availability_id'] ?? 0);
$facId  = $myFacultyId;
$retFac = isset($_POST['return_faculty_id']) ? '&faculty_id=' . urlencode($_POST['return_faculty_id']) : '';

$redir = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_availability.php';

// Handle Deletion
if ($action === 'delete') {
    if (!$avId) {
        header('Location: ' . $redir . '?error=invalid_id' . $retFac);
        exit();
    }
    try {
        $stmt = $pdo->prepare('DELETE FROM faculty_availability WHERE availability_id = ? AND faculty_id = ?');
        $stmt->execute([$avId, $myFacultyId]);
        header('Location: ' . $redir . '?success=deleted' . $retFac);
    } catch (PDOException $e) {
        header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()) . $retFac);
    }
    exit();
}

// Handle Save / Update
$date   = trim($_POST['available_date'] ?? '');
$start  = trim($_POST['start_time'] ?? '');
$end    = trim($_POST['end_time'] ?? '');
$status = in_array($_POST['status'] ?? '', ['available', 'unavailable']) ? $_POST['status'] : 'available';

if (!$facId || $date === '' || $start === '' || $end === '') {
    header('Location: ' . $redir . '?error=' . urlencode('Please fill in all required fields.') . $retFac);
    exit();
}

try {
    if ($avId > 0) {
        $stmt = $pdo->prepare('UPDATE faculty_availability SET available_date=?, start_time=?, end_time=?, status=? WHERE availability_id=? AND faculty_id=?');
        $stmt->execute([$date, $start, $end, $status, $avId, $myFacultyId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO faculty_availability (faculty_id, available_date, start_time, end_time, status) VALUES (?,?,?,?,?)');
        $stmt->execute([$facId, $date, $start, $end, $status]);
    }
    header('Location: ' . $redir . '?success=1' . $retFac);
} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode($e->getMessage()) . $retFac);
}
exit();

