<?php
/**
 * assignment_delete.php – Removes an assigned invigilator from an exam room.
 * Requires POST method and admin session auth (via auth_check.php).
 * Inserts a notification for the faculty member whose assignment was removed.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$redir = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_room_management.php';

// Enforce POST — reject GET/HEAD/prefetch
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redir . '?error=' . urlencode('Invalid request method.'));
    exit();
}

$assignmentId = (int)($_POST['assignment_id'] ?? 0);

if (!$assignmentId) {
    header('Location: ' . $redir . '?error=' . urlencode('Invalid assignment ID.'));
    exit();
}

try {
    $pdo = db();

    // Verify the assignment exists before deleting, and fetch info for the notification
    $stmt = $pdo->prepare('
        SELECT ia.assignment_id, ia.faculty_id, 
               e.exam_name, e.exam_date, r.room_no, r.building
        FROM invigilation_assignment ia
        JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
        JOIN exam e ON e.exam_id = er.exam_id
        JOIN room r ON r.room_id = er.room_id
        WHERE ia.assignment_id = ?
    ');
    $stmt->execute([$assignmentId]);
    $assignInfo = $stmt->fetch();

    if (!$assignInfo) {
        header('Location: ' . $redir . '?error=' . urlencode('Assignment not found.'));
        exit();
    }

    // Delete the assignment
    $del = $pdo->prepare('DELETE FROM invigilation_assignment WHERE assignment_id = ?');
    $del->execute([$assignmentId]);

    // Insert Notification for the faculty member
    $userLookup = $pdo->prepare('SELECT user_id FROM faculty WHERE faculty_id = ?');
    $userLookup->execute([$assignInfo['faculty_id']]);
    $facultyUserId = $userLookup->fetchColumn();

    if ($facultyUserId) {
        $notifTitle = 'Invigilation Duty Removed';
        $notifMsg   = "Your invigilation duty assignment for \"{$assignInfo['exam_name']}\" on {$assignInfo['exam_date']} in Room {$assignInfo['room_no']}, {$assignInfo['building']} has been removed.";
        
        $notifIns = $pdo->prepare('INSERT INTO notification (user_id, title, message) VALUES (?, ?, ?)');
        $notifIns->execute([(int)$facultyUserId, $notifTitle, $notifMsg]);
    }

    header('Location: ' . $redir . '?success=1');
    exit();

} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode('Database error: ' . $e->getMessage()));
    exit();
}
