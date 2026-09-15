<?php
/**
 * auth_check.php – Strict Role-Based Access Control (RBAC) Guard.
 * Include at the top of every protected page.
 *
 * Usage (admin page):   require_once __DIR__ . '/../includes/auth_check.php'; // default $required_role = 'admin'
 * Usage (faculty page): $required_role = 'faculty'; require_once __DIR__ . '/../includes/auth_check.php';
 * Usage (shared page):  $required_role = 'any'; require_once __DIR__ . '/../includes/auth_check.php';
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

if ($required_role === 'any') {
    $isAllowed = !empty($userRole);
} elseif (is_array($required_role)) {
    $isAllowed = in_array($userRole, $required_role, true);
} else {
    $isAllowed = ($userRole === $required_role);
}

if (!$isAllowed) {
    http_response_code(403);
    $dashboardHref = ($userRole === 'admin')
        ? '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/admin_dashboard.php'
        : '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_dashboard.php';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>403 Access Denied – Premier University</title>
      <script src="https://cdn.tailwindcss.com"></script>
      <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    </head>
    <body class="bg-[#0f172a] text-slate-100 min-h-screen flex items-center justify-center p-4 font-['Inter']">
      <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center shadow-2xl">
        <div class="w-16 h-16 rounded-2xl bg-red-500/10 text-red-400 mx-auto flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-[36px]">shield_lock</span>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">403 Access Denied</h1>
        <p class="text-slate-400 text-sm mb-6 leading-relaxed">
          You do not have the required permissions to access this page. Your current active role is <span class="px-2 py-0.5 rounded bg-slate-800 text-blue-400 font-semibold uppercase text-xs"><?= htmlspecialchars($userRole) ?></span>.
        </p>
        <div class="flex flex-col gap-3">
          <a href="<?= $dashboardHref ?>"
             class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-medium text-sm transition-colors flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[18px]">dashboard</span> Return to Dashboard
          </a>
          <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/logout.php"
             class="w-full py-2 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs transition-colors">
            Switch Account / Sign Out
          </a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit();
}
