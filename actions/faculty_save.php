<?php
/**
 * faculty_save.php
 * Handles both faculty self-profile updates AND admin-managed faculty CRUD.
 */
$required_role = 'any';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

// ── Get a FRESH PDO connection (never reuse singleton for writes) ──────────
// We create a direct connection here to avoid static-singleton transaction issues.
function getFreshPdo(): PDO {
    $dsn  = 'mysql:host=127.0.0.1;port=3306;dbname=invigilation_scheduler;charset=utf8mb4';
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ];
    return new PDO($dsn, 'root', '', $opts);
}

$userRole = $_SESSION['role'] ?? '';
$userId   = (int) ($_SESSION['user_id'] ?? 0);

$facultyId   = (int) ($_POST['faculty_id']    ?? 0);
$deptId      = (int) ($_POST['department_id'] ?? 0);
$name        = trim($_POST['faculty_name']    ?? '');
$email       = trim($_POST['email']           ?? '');
$phone       = trim($_POST['phone']           ?? '');
$designation = trim($_POST['designation']     ?? '');
$username    = trim($_POST['username']        ?? '');
$password    = $_POST['password']             ?? '';
$source      = $_POST['source']               ?? '';

// ════════════════════════════════════════════════════════════════════════════
// 1. FACULTY SELF-UPDATE  (logged-in faculty editing their own profile)
// ════════════════════════════════════════════════════════════════════════════
if ($userRole === 'faculty') {
    $redir = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_profile.php';

    if ($name === '' || $email === '') {
        header('Location: ' . $redir . '?error=' . urlencode('Full Name and Email are required.'));
        exit();
    }

    try {
        $pdo = getFreshPdo();   // fresh connection — no transaction state issues

        // Step 1: Get the faculty row linked to this user
        $q = $pdo->prepare('SELECT faculty_id FROM faculty WHERE user_id = ? LIMIT 1');
        $q->execute([$userId]);
        $myFacultyId = (int) $q->fetchColumn();

        if ($myFacultyId === 0) {
            error_log("[faculty_save] No faculty row for user_id=$userId");
            header('Location: ' . $redir . '?error=' . urlencode('Your faculty record was not found. Contact admin.'));
            exit();
        }

        // Step 2: Update faculty table (autocommit — no transaction needed)
        $upd1 = $pdo->prepare(
            'UPDATE faculty SET faculty_name = ?, email = ?, phone = ? WHERE faculty_id = ?'
        );
        $upd1->execute([$name, $email, $phone ?: null, $myFacultyId]);
        $rows1 = $upd1->rowCount();
        error_log("[faculty_save] faculty UPDATE faculty_id=$myFacultyId rowCount=$rows1");

        // Step 3: Update password if provided
        if ($password !== '') {
            if (strlen($password) < 4) {
                header('Location: ' . $redir . '?error=' . urlencode('Password must be at least 4 characters.'));
                exit();
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $upd2 = $pdo->prepare(
                'UPDATE user_account SET password_hash = ? WHERE user_id = ?'
            );
            $upd2->execute([$hash, $userId]);
            $rows2 = $upd2->rowCount();
            error_log("[faculty_save] user_account UPDATE user_id=$userId rowCount=$rows2");

            if ($rows2 === 0) {
                header('Location: ' . $redir . '?error=' . urlencode('Password could not be updated. User account not found.'));
                exit();
            }
        }

        // Use success=2 when password was changed so the profile page can show a specific message
        $successCode = ($password !== '') ? '2' : '1';
        header('Location: ' . $redir . '?success=' . $successCode);
        exit();


    } catch (PDOException $e) {
        error_log("[faculty_save] PDOException: " . $e->getMessage());
        header('Location: ' . $redir . '?error=' . urlencode('Database error: ' . $e->getMessage()));
        exit();
    }
}

// ════════════════════════════════════════════════════════════════════════════
// 2. ADMIN FACULTY MANAGEMENT  (create / edit faculty as admin)
// ════════════════════════════════════════════════════════════════════════════
$redir = ($source === 'profile')
    ? '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_profile.php?faculty_id=' . $facultyId
    : '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_management.php';

$sep = ($source === 'profile') ? '&' : '?';

if ($name === '' || $email === '') {
    header('Location: ' . $redir . $sep . 'error=' . urlencode('Name and Email are required.'));
    exit();
}

try {
    $pdo = getFreshPdo();   // fresh connection
    $pdo->beginTransaction();

    if ($facultyId > 0) {
        // ── UPDATE existing faculty ──────────────────────────────────────
        if ($deptId > 0) {
            $pdo->prepare(
                'UPDATE faculty SET department_id=?, faculty_name=?, email=?, phone=?, designation=? WHERE faculty_id=?'
            )->execute([$deptId, $name, $email, $phone ?: null, $designation ?: null, $facultyId]);
        } else {
            $pdo->prepare(
                'UPDATE faculty SET faculty_name=?, email=?, phone=? WHERE faculty_id=?'
            )->execute([$name, $email, $phone ?: null, $facultyId]);
        }

        // Update password only if provided
        if ($password !== '') {
            $hash  = password_hash($password, PASSWORD_BCRYPT);
            $q     = $pdo->prepare('SELECT user_id FROM faculty WHERE faculty_id = ? LIMIT 1');
            $q->execute([$facultyId]);
            $uid   = (int) $q->fetchColumn();
            if ($uid > 0) {
                $pdo->prepare('UPDATE user_account SET password_hash=? WHERE user_id=?')
                    ->execute([$hash, $uid]);
            }
        }

    } else {
        // ── CREATE new user_account + faculty ────────────────────────────
        if ($username === '' || $password === '' || $deptId <= 0) {
            $pdo->rollBack();
            header('Location: ' . $redir . '?error=' . urlencode('Department, Username, and Password are required for new faculty.'));
            exit();
        }
        $hash    = password_hash($password, PASSWORD_BCRYPT);
        $stmt    = $pdo->prepare("INSERT INTO user_account (username, password_hash, role, status) VALUES (?,?,'faculty','active')");
        $stmt->execute([$username, $hash]);
        $newUid  = (int) $pdo->lastInsertId();

        $pdo->prepare(
            'INSERT INTO faculty (user_id, department_id, faculty_name, email, phone, designation) VALUES (?,?,?,?,?,?)'
        )->execute([$newUid, $deptId, $name, $email, $phone ?: null, $designation ?: null]);
    }

    $pdo->commit();
    header('Location: ' . $redir . $sep . 'success=1');

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("[faculty_save admin] PDOException: " . $e->getMessage());
    header('Location: ' . $redir . $sep . 'error=' . urlencode('Database error: ' . $e->getMessage()));
}
exit();
