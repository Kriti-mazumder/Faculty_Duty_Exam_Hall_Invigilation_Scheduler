<?php
/**
 * auth_check.php – Include at the top of every protected page.
 *
 * Usage (admin page):   require_once __DIR__ . '/../includes/auth_check.php'; // default role = admin
 * Usage (faculty page): $required_role = 'faculty'; require_once ...
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$required_role = $required_role ?? 'admin';

if (empty($_SESSION['user_id'])) {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php');
    exit();
}

$userRole = $_SESSION['role'] ?? '';
$isAllowed = false;

if ($userRole === 'admin') {
    // Admin has access to all pages
    $isAllowed = true;
} elseif ($required_role === 'any') {
    // Any authenticated user
    $isAllowed = true;
} elseif (is_array($required_role)) {
    $isAllowed = in_array($userRole, $required_role, true);
} else {
    $isAllowed = ($userRole === $required_role);
}

if (!$isAllowed) {
    http_response_code(403);
    exit('<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p><a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php">Back to Login</a>');
}
