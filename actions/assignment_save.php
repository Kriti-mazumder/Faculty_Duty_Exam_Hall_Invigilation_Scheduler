<?php
/**
 * assignment_save.php – Assigns faculty invigilator to an exam room.
 * Enforces strict maximum limits, duplicate checks, and schedule conflict validation.
 * Inserts a notification for the assigned faculty member.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$redir      = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_room_management.php';
$examRoomId = (int)($_POST['exam_room_id'] ?? 0);
$facultyId  = (int)($_POST['faculty_id']   ?? 0);
$role       = $_POST['duty_role']          ?? 'assistant';

if (!$examRoomId || !$facultyId) {
    header('Location: ' . $redir . '?error=' . urlencode('Please select both an exam room and a faculty member.'));
    exit();
}

try {
    $pdo = db();

    // 1. Fetch exam room, parent exam, room details, required invigilators limit, and current count
    $stmt = $pdo->prepare('
        SELECT er.exam_room_id, er.room_id,
               e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time,
               e.required_invigilators,
               r.room_no, r.building,
               (SELECT COUNT(*) FROM invigilation_assignment ia WHERE ia.exam_room_id = er.exam_room_id AND ia.assignment_status = \'assigned\') AS assigned_count
        FROM exam_room er
        JOIN exam e ON e.exam_id = er.exam_id
        JOIN room r ON r.room_id = er.room_id
        WHERE er.exam_room_id = ?
    ');
    $stmt->execute([$examRoomId]);
    $roomInfo = $stmt->fetch();

    if (!$roomInfo) {
        header('Location: ' . $redir . '?error=' . urlencode('Exam room record not found.'));
        exit();
    }

    $requiredLimit = (int) $roomInfo['required_invigilators'];
    $currentAssigned = (int) $roomInfo['assigned_count'];

    // 2. Strict Over-Assignment Validation (Cannot exceed required limit)
    if ($currentAssigned >= $requiredLimit) {
        header('Location: ' . $redir . '?error=' . urlencode("Assignment rejected: Required threshold limit of {$requiredLimit} invigilator(s) has already been reached for this exam room."));
        exit();
    }

    // 3. Prevent Duplicate Assignment
    $dupCheck = $pdo->prepare('
        SELECT assignment_id FROM invigilation_assignment
        WHERE exam_room_id = ? AND faculty_id = ? AND assignment_status = \'assigned\'
    ');
    $dupCheck->execute([$examRoomId, $facultyId]);
    if ($dupCheck->fetch()) {
        header('Location: ' . $redir . '?error=' . urlencode('This faculty member is already assigned to this exam room.'));
        exit();
    }

    // 4. Time Conflict Validation (Faculty cannot have overlapping duties at the same date/time)
    $conflictCheck = $pdo->prepare('
        SELECT e.exam_name, e.start_time, e.end_time, r.room_no
        FROM invigilation_assignment ia
        JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
        JOIN exam e ON e.exam_id = er.exam_id
        JOIN room r ON r.room_id = er.room_id
        WHERE ia.faculty_id = ?
          AND ia.assignment_status = \'assigned\'
          AND e.exam_date = ?
          AND (e.start_time < ? AND e.end_time > ?)
        LIMIT 1
    ');
    $conflictCheck->execute([$facultyId, $roomInfo['exam_date'], $roomInfo['end_time'], $roomInfo['start_time']]);
    $conflict = $conflictCheck->fetch();

    if ($conflict) {
        $conflictMsg = 'Schedule Conflict: Faculty is already assigned to ' . $conflict['exam_name'] . ' in Room ' . $conflict['room_no'] . ' (' . substr($conflict['start_time'], 0, 5) . '–' . substr($conflict['end_time'], 0, 5) . ').';
        header('Location: ' . $redir . '?error=' . urlencode($conflictMsg));
        exit();
    }

    // 5. Insert Validated Assignment
    $ins = $pdo->prepare('
        INSERT INTO invigilation_assignment (exam_room_id, faculty_id, duty_role, assignment_status)
        VALUES (?, ?, ?, \'assigned\')
    ');
    $ins->execute([$examRoomId, $facultyId, $role]);

    // 6. Insert Notification for the assigned faculty member
    $userLookup = $pdo->prepare('SELECT user_id FROM faculty WHERE faculty_id = ?');
    $userLookup->execute([$facultyId]);
    $facultyUserId = $userLookup->fetchColumn();

    if ($facultyUserId) {
        $roleName  = ($role === 'chief') ? 'Chief' : 'Assistant';
        $notifTitle = 'New Invigilation Duty Assigned';
        $notifMsg   = "You have been assigned as {$roleName} Invigilator for \"{$roomInfo['exam_name']}\" on {$roomInfo['exam_date']} in Room {$roomInfo['room_no']}, {$roomInfo['building']} (" . substr($roomInfo['start_time'], 0, 5) . "–" . substr($roomInfo['end_time'], 0, 5) . ").";

        $notifIns = $pdo->prepare('INSERT INTO notification (user_id, title, message) VALUES (?, ?, ?)');
        $notifIns->execute([(int)$facultyUserId, $notifTitle, $notifMsg]);
    }

    header('Location: ' . $redir . '?success=1');
    exit();

} catch (PDOException $e) {
    header('Location: ' . $redir . '?error=' . urlencode('Database error: ' . $e->getMessage()));
    exit();
}
