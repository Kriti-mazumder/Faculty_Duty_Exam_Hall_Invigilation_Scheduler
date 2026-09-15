<?php
/**
 * impersonate.php – Handles Admin impersonation of Faculty members (Role-Based Access Control).
 *
 * Actions:
 *   - ?action=start&faculty_id=X : Starts impersonation session for specified faculty member.
 *   - ?action=stop               : Restores the original administrator session.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? '';
$pdo = db();

// Check authorization: Must be an Admin or already impersonating
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
$isImpersonating = !empty($_SESSION['is_impersonating']) && !empty($_SESSION['admin_orig_user_id']);

if (!$isAdmin && !$isImpersonating) {
    http_response_code(403);
    exit('<h1>403 Forbidden</h1><p>Only administrators can perform impersonation actions.</p><a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php">Back to Login</a>');
}

if ($action === 'start') {
    $facultyId = (int) ($_GET['faculty_id'] ?? 0);
    if ($facultyId <= 0) {
        header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_management.php?error=Invalid+faculty+selected');
        exit();
    }

    $stmt = $pdo->prepare('
        SELECT f.faculty_id, f.faculty_name, f.email, f.designation, f.department_id,
               u.user_id, u.username, u.status
        FROM faculty f
        JOIN user_account u ON u.user_id = f.user_id
        WHERE f.faculty_id = ?
        LIMIT 1
    ');
    $stmt->execute([$facultyId]);
    $target = $stmt->fetch();

    if (!$target) {
        header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_management.php?error=Faculty+member+not+found');
        exit();
    }

    // Preserve original Admin session credentials if not already stored
    if (empty($_SESSION['admin_orig_user_id'])) {
        $_SESSION['admin_orig_user_id'] = $_SESSION['user_id'];
        $_SESSION['admin_orig_username'] = $_SESSION['username'];
        $_SESSION['admin_orig_role'] = 'admin';
    }

    // Set impersonated faculty session state
    $_SESSION['is_impersonating'] = true;
    $_SESSION['user_id']          = (int) $target['user_id'];
    $_SESSION['faculty_id']       = (int) $target['faculty_id'];
    $_SESSION['faculty_name']     = $target['faculty_name'];
    $_SESSION['username']         = $target['username'];
    $_SESSION['role']             = 'faculty';

    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_dashboard.php');
    exit();

} elseif ($action === 'stop') {
    if (!empty($_SESSION['admin_orig_user_id'])) {
        // Restore Administrator session
        $_SESSION['user_id']  = $_SESSION['admin_orig_user_id'];
        $_SESSION['username'] = $_SESSION['admin_orig_username'];
        $_SESSION['role']     = 'admin';

        // Clear impersonation keys
        unset(
            $_SESSION['admin_orig_user_id'],
            $_SESSION['admin_orig_username'],
            $_SESSION['admin_orig_role'],
            $_SESSION['is_impersonating'],
            $_SESSION['faculty_id'],
            $_SESSION['faculty_name']
        );
    }

    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_management.php?impersonation_ended=1');
    exit();

} else {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_management.php');
    exit();
}
