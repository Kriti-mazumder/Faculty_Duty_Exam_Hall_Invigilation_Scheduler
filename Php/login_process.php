<?php
session_start();

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php');
    exit();
}

$identity = trim($_POST['username'] ?? $_POST['email'] ?? $_POST['login_identity'] ?? '');
$password = $_POST['password'] ?? $_POST['login_password'] ?? '';

if ($identity === '' || $password === '') {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php?error=1');
    exit();
}

try {
    $pdo = db();
    // Allow login by username OR faculty email
    $stmt = $pdo->prepare('
        SELECT u.user_id, u.username, u.password_hash, u.role, u.status 
        FROM user_account u 
        LEFT JOIN faculty f ON u.user_id = f.user_id 
        WHERE u.username = ? OR f.email = ? 
        LIMIT 1
    ');
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php?error=1');
    exit();
}

if (!$user || $user['status'] !== 'active') {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php?error=1');
    exit();
}

$passwordValid = password_verify($password, $user['password_hash']) || ($password === $user['password_hash']);

if (!$passwordValid) {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php?error=1');
    exit();
}

// Enforce selected role matching account role
$selectedRole = trim($_POST['role'] ?? '');
if ($selectedRole !== '' && $user['role'] !== $selectedRole) {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php?error=role_mismatch');
    exit();
}

// If password was stored as plain text or needs rehash, upgrade it
if ($password === $user['password_hash'] || password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    try {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE user_account SET password_hash = ? WHERE user_id = ?');
        $upd->execute([$newHash, $user['user_id']]);
    } catch (Exception $e) {
        // Continue login even if hash update fails
    }
}

// Regenerate session ID to prevent fixation
session_regenerate_id(true);

$_SESSION['user_id']  = (int) $user['user_id'];
$_SESSION['role']     = $user['role'];
$_SESSION['username'] = $user['username'];

if ($user['role'] === 'admin') {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/admin_dashboard.php');
} else {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_dashboard.php');
}
exit();

