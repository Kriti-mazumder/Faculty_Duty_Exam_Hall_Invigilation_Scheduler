<?php
/**
 * reassign_duty.php – Reassigns or removes invigilation assignments.
 * Supports:
 *   - Reassign duty to replacement faculty with conflict checking.
 *   - Cancel / Remove duty assignment (e.g., in case of over-assignment or faculty unviability).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$redir    = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/manual_duty_reassignment.php';
$action   = $_POST['action'] ?? 'reassign';
$assignId = (int)($_POST['assignment_id'] ?? 0);

if (!$assignId) {
    header('Location: ' . $redir . '?error=' . urlencode('Missing assignment ID.'));
    exit();
}

try {
    $pdo = db();

    // Fetch existing assignment and exam schedule
    $stmt = $pdo->prepare('
        SELECT ia.assignment_id, ia.exam_room_id, ia.faculty_id,
               e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time,
               r.room_no
        FROM invigilation_assignment ia
        JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
        JOIN exam e ON e.exam_id = er.exam_id
        JOIN room r ON r.room_id = er.room_id
        WHERE ia.assignment_id = ?
    ');
    $stmt->execute([$assignId]);
    $assign = $stmt->fetch();

    if (!$assign) {
        header('Location: ' . $redir . '?error=' . urlencode('Assignment record not found.'));
        exit();
    }

    if ($action === 'delete' || $action === 'remove') {
        // Delete or cancel the assignment
        $del = $pdo->prepare('DELETE FROM invigilation_assignment WHERE assignment_id = ?');
        $del->execute([$assignId]);

        header('Location: ' . $redir . '?success=' . urlencode('Duty assignment removed successfully.'));
        exit();
    }

    // Default action: Reassign to replacement faculty
    $newFacultyId = (int)($_POST['new_faculty_id'] ?? 0);
    if (!$newFacultyId) {
        header('Location: ' . $redir . '?error=' . urlencode('Please select a replacement faculty member.'));
        exit();
    }

    if ($newFacultyId === (int)$assign['faculty_id']) {
        header('Location: ' . $redir . '?error=' . urlencode('Selected faculty is already assigned to this slot.'));
        exit();
    }

    // Check duplicate in same exam room
    $dup = $pdo->prepare('
        SELECT assignment_id FROM invigilation_assignment
        WHERE exam_room_id = ? AND faculty_id = ? AND assignment_id != ? AND assignment_status = \'assigned\'
    ');
    $dup->execute([$assign['exam_room_id'], $newFacultyId, $assignId]);
    if ($dup->fetch()) {
        header('Location: ' . $redir . '?error=' . urlencode('The selected faculty member is already assigned to this exam room.'));
        exit();
    }

    // Check time conflict for replacement faculty
    $conflictCheck = $pdo->prepare('
        SELECT e.exam_name, e.start_time, e.end_time, r.room_no
        FROM invigilation_assignment ia
        JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
        JOIN exam e ON e.exam_id = er.exam_id
        JOIN room r ON r.room_id = er.room_id
        WHERE ia.faculty_id = ?
          AND ia.assignment_status = \'assigned\'
          AND ia.assignment_id != ?
          AND e.exam_date = ?
          AND (e.start_time < ? AND e.end_time > ?)
        LIMIT 1
    ');
    $conflictCheck->execute([$newFacultyId, $assignId, $assign['exam_date'], $assign['end_time'], $assign['start_time']]);
    $conflict = $conflictCheck->fetch();

    if ($conflict) {
        $conflictMsg = 'Schedule Conflict: Replacement faculty is already assigned to ' . $conflict['exam_name'] . ' in Room ' . $conflict['room_no'] . ' (' . substr($conflict['start_time'], 0, 5) . '–' . substr($conflict['end_time'], 0, 5) . ').';
        header('Location: ' . $redir . '?error=' . urlencode($conflictMsg));
        exit();
    }

    // Execute reassignment
    $upd = $pdo->prepare('UPDATE invigilation_assignment SET faculty_id = ? WHERE assignment_id = ?');
    $upd->execute([$newFacultyId, $assignId]);

    header('Location: ' . $redir . '?success=' . urlencode('Duty successfully reassigned.'));
    exit();

} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode('Database error: ' . $e->getMessage()));
    exit();
}
