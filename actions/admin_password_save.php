<?php
/**
 * admin_password_save.php
 * Securely updates the admin's own password in the database.
 */
$required_role = 'admin';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$redir  = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/admin_profile.php';
$userId = (int) ($_SESSION['user_id'] ?? 0);

$currentPassword = $_POST['current_password'] ?? '';
$newPassword     = $_POST['new_password']     ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// ── Validation ─────────────────────────────────────────────────────────────
if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    header('Location: ' . $redir . '?error=' . urlencode('Please fill in all password fields.'));
    exit();
}

if ($newPassword !== $confirmPassword) {
    header('Location: ' . $redir . '?error=' . urlencode('New passwords do not match.'));
    exit();
}

if (strlen($newPassword) < 6) {
    header('Location: ' . $redir . '?error=' . urlencode('New password must be at least 6 characters.'));
    exit();
}

// ── Use a fresh direct connection (avoids PDO singleton issues on XAMPP) ───
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=invigilation_scheduler;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Step 1: Fetch current password hash from DB
    $stmt = $pdo->prepare(
        "SELECT user_id, password_hash FROM user_account WHERE user_id = ? AND role = 'admin' LIMIT 1"
    );
    $stmt->execute([$userId]);
    $admin = $stmt->fetch();

    if (!$admin) {
        error_log("[admin_password_save] Admin user not found for user_id=$userId");
        header('Location: ' . $redir . '?error=' . urlencode('Admin account not found.'));
        exit();
    }

    // Step 2: Verify current password (support both bcrypt and plain-text stored passwords)
    $storedHash = $admin['password_hash'];
    $isValid    = password_verify($currentPassword, $storedHash)
               || ($currentPassword === $storedHash);   // plain-text fallback

    if (!$isValid) {
        header('Location: ' . $redir . '?error=' . urlencode('Current password is incorrect.'));
        exit();
    }

    // Step 3: Hash new password and UPDATE in database
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $upd     = $pdo->prepare('UPDATE user_account SET password_hash = ? WHERE user_id = ?');
    $upd->execute([$newHash, $userId]);
    $rows = $upd->rowCount();

    error_log("[admin_password_save] password updated for user_id=$userId rowCount=$rows");

    if ($rows === 0) {
        header('Location: ' . $redir . '?error=' . urlencode('Update failed: no rows affected. Contact server admin.'));
        exit();
    }

    header('Location: ' . $redir . '?success=1');

} catch (PDOException $e) {
    error_log("[admin_password_save] PDOException: " . $e->getMessage());
    header('Location: ' . $redir . '?error=' . urlencode('Database error: ' . $e->getMessage()));
}
exit();
