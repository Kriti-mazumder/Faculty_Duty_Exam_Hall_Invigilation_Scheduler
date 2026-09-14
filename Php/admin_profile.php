<?php
$required_role = 'admin';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Admin Profile & Security';
$activePage = 'admin-profile';

$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT user_id, username, role, status FROM user_account WHERE user_id = ?');
$stmt->execute([$userId]);
$adminUser = $stmt->fetch();

$totalFaculty = (int) $pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
$totalExams   = (int) $pdo->query('SELECT COUNT(*) FROM exam')->fetchColumn();
$totalRooms   = (int) $pdo->query('SELECT COUNT(*) FROM room')->fetchColumn();
$totalDepts   = (int) $pdo->query('SELECT COUNT(*) FROM department')->fetchColumn();

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Admin Profile &amp; Security</span>
</nav>

<?php if ($success): ?>
<div class="mb-5 flex items-start gap-3 px-4 py-3.5 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm font-medium shadow-sm">
  <span class="material-symbols-outlined text-[22px] text-green-600 flex-shrink-0 mt-0.5">lock_reset</span>
  <div>
    <p class="font-semibold">Password updated in database ✓</p>
    <p class="text-green-700 mt-0.5 font-normal">Your new password has been securely hashed (bcrypt) and saved to the <strong>user_account</strong> table. You can verify the new hash in phpMyAdmin — it will look different from the previous one.</p>
  </div>
</div>
<?php elseif ($error): ?>
<div class="mb-5 flex items-center gap-2 px-4 py-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-medium shadow-sm">
  <span class="material-symbols-outlined text-[20px] text-red-600">error</span>
  <span><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Admin Identity Card -->
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-6 flex flex-col items-center text-center border border-surface-container-high/60">
    <div class="w-20 h-20 rounded-2xl bg-primary-container flex items-center justify-center mb-4 text-on-primary shadow-md">
      <span class="material-symbols-outlined text-[40px]">admin_panel_settings</span>
    </div>
    <h2 class="font-headline-md text-headline-md text-on-surface font-bold"><?= htmlspecialchars($adminUser['username'] ?? 'admin') ?></h2>
    <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">System Administrator</p>
    <p class="font-label-sm text-label-sm text-primary mt-1 font-semibold uppercase tracking-wider">Office of Examination Control</p>
    
    <span class="mt-3 px-3 py-1 rounded-full font-label-sm text-label-sm bg-green-100 text-green-800 font-semibold inline-flex items-center gap-1.5">
      <span class="w-2 h-2 rounded-full bg-green-600"></span> Active Session
    </span>

    <div class="w-full border-t border-surface-container-high mt-5 pt-4 text-left space-y-2.5">
      <div class="flex items-center justify-between text-on-surface-variant font-body-sm text-body-sm">
        <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">account_circle</span> Account Type:</span>
        <span class="font-semibold text-on-surface capitalize"><?= htmlspecialchars($adminUser['role'] ?? 'admin') ?></span>
      </div>
      <div class="flex items-center justify-between text-on-surface-variant font-body-sm text-body-sm">
        <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">security</span> Access Level:</span>
        <span class="font-semibold text-emerald-700">Full Superuser Control</span>
      </div>
      <div class="flex items-center justify-between text-on-surface-variant font-body-sm text-body-sm">
        <span class="flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">database</span> Storage Engine:</span>
        <span class="font-code text-code text-on-surface font-semibold">MySQL / MariaDB</span>
      </div>
    </div>
  </div>

  <!-- System Summary & Change Password Form -->
  <div class="lg:col-span-2 space-y-6">

    <!-- System Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 border border-surface-container-high/60 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[20px]">badge</span>
        </div>
        <div>
          <p class="font-label-sm text-label-sm text-on-surface-variant">Faculty</p>
          <p class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= $totalFaculty ?></p>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 border border-surface-container-high/60 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[20px]">assignment</span>
        </div>
        <div>
          <p class="font-label-sm text-label-sm text-on-surface-variant">Exams</p>
          <p class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= $totalExams ?></p>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 border border-surface-container-high/60 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[20px]">meeting_room</span>
        </div>
        <div>
          <p class="font-label-sm text-label-sm text-on-surface-variant">Rooms</p>
          <p class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= $totalRooms ?></p>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 border border-surface-container-high/60 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[20px]">corporate_fare</span>
        </div>
        <div>
          <p class="font-label-sm text-label-sm text-on-surface-variant">Depts</p>
          <p class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= $totalDepts ?></p>
        </div>
      </div>
    </div>

    <!-- Change Password Box -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm p-6 sm:p-7 border border-surface-container-high/60">
      <div class="flex items-center gap-2 mb-2">
        <span class="material-symbols-outlined text-primary text-[24px]">key</span>
        <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold">Change Administrator Password</h3>
      </div>
      <p class="font-body-sm text-body-sm text-on-surface-variant mb-6">
        Update your root portal password. The new password will be hashed and immediately updated in the database.
      </p>

      <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/admin_password_save.php" class="space-y-4 max-w-lg">
        <div>
          <label class="block font-label-md text-label-md text-on-surface font-semibold mb-1.5" for="current_password">
            Current Admin Password <span class="text-error">*</span>
          </label>
          <div class="relative flex items-center">
            <span class="material-symbols-outlined absolute left-3.5 text-secondary text-[20px] pointer-events-none">lock</span>
            <input type="password" name="current_password" id="current_password" required
                   placeholder="Enter current password (e.g. Admin@1234)"
                   class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm"/>
          </div>
        </div>

        <div>
          <label class="block font-label-md text-label-md text-on-surface font-semibold mb-1.5" for="new_password">
            New Password <span class="text-error">*</span>
          </label>
          <div class="relative flex items-center">
            <span class="material-symbols-outlined absolute left-3.5 text-secondary text-[20px] pointer-events-none">lock_reset</span>
            <input type="password" name="new_password" id="new_password" required minlength="6"
                   placeholder="Enter new strong password"
                   class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm"/>
          </div>
          <span class="text-xs text-on-surface-variant mt-1 block">Minimum 6 characters recommended with symbols or numbers.</span>
        </div>

        <div>
          <label class="block font-label-md text-label-md text-on-surface font-semibold mb-1.5" for="confirm_password">
            Confirm New Password <span class="text-error">*</span>
          </label>
          <div class="relative flex items-center">
            <span class="material-symbols-outlined absolute left-3.5 text-secondary text-[20px] pointer-events-none">verified_user</span>
            <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                   placeholder="Re-type new password"
                   class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm"/>
          </div>
        </div>

        <div class="pt-2">
          <button type="submit"
                  class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">save</span> Update Password in Database
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
