<?php
$required_role = 'faculty';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Faculty Profile';
$activePage = '';

// Admin can view any faculty profile via ?faculty_id=X
if ($_SESSION['role'] === 'admin' && isset($_GET['faculty_id'])) {
    $fid = (int) $_GET['faculty_id'];
} else {
    $r = $pdo->prepare('SELECT faculty_id FROM faculty WHERE user_id = ?');
    $r->execute([$_SESSION['user_id']]);
    $fid = (int) ($r->fetchColumn() ?: 0);
}

$profile = null;
if ($fid) {
    $stmt = $pdo->prepare(
        'SELECT f.faculty_id, f.faculty_name, f.email, f.phone, f.designation,
                d.department_name, u.username, u.status, u.role
         FROM faculty f
         JOIN department d ON d.department_id = f.department_id
         JOIN user_account u ON u.user_id = f.user_id
         WHERE f.faculty_id = ?'
    );
    $stmt->execute([$fid]);
    $profile = $stmt->fetch();
}

// Duty summary
$dutyStats = ['total'=>0,'assigned'=>0,'completed'=>0];
if ($fid) {
    $ds = $pdo->prepare(
        "SELECT assignment_status, COUNT(*) AS cnt FROM invigilation_assignment WHERE faculty_id = ? GROUP BY assignment_status"
    );
    $ds->execute([$fid]);
    foreach ($ds->fetchAll() as $row) {
        $dutyStats[$row['assignment_status']] = $row['cnt'];
        $dutyStats['total'] += $row['cnt'];
    }
}

$successCode = $_GET['success'] ?? '';
$success     = ($successCode !== '');
$error       = $_GET['error'] ?? '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Faculty Profile</span>
</nav>

<?php if (!$profile): ?>
<div class="p-8 text-center text-on-surface-variant">Profile not found.</div>
<?php else: ?>

<?php if ($successCode === '2'): ?>
<div class="mb-4 flex items-start gap-3 px-4 py-3.5 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm shadow-sm">
  <span class="material-symbols-outlined text-[22px] text-green-600 flex-shrink-0 mt-0.5">lock_reset</span>
  <div>
    <p class="font-semibold">Password updated in database ✓</p>
    <p class="text-green-700 mt-0.5">Your new password has been securely hashed (bcrypt) and saved to <strong>user_account</strong> table in the database. Use your new password the next time you log in.</p>
  </div>
</div>
<?php elseif ($successCode === '1'): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Profile information (name, email, phone) updated in database ✓
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Profile Card -->
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-6 flex flex-col items-center text-center">
    <div class="w-20 h-20 rounded-full bg-primary flex items-center justify-center mb-4">
      <span class="material-symbols-outlined text-on-primary text-[40px]">person</span>
    </div>
    <h2 class="font-headline-md text-headline-md text-on-surface"><?= htmlspecialchars($profile['faculty_name']) ?></h2>
    <p class="font-body-md text-body-md text-on-surface-variant mt-1"><?= htmlspecialchars($profile['designation'] ?? '—') ?></p>
    <p class="font-label-md text-label-md text-primary mt-1"><?= htmlspecialchars($profile['department_name']) ?></p>
    <span class="mt-3 px-3 py-1 rounded-full font-label-sm text-label-sm <?= $profile['status']==='active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
      <?= ucfirst($profile['status']) ?>
    </span>
    <div class="w-full border-t border-surface-container-high mt-4 pt-4 text-left space-y-2">
      <div class="flex items-center gap-2 text-on-surface-variant font-body-sm text-body-sm">
        <span class="material-symbols-outlined text-[16px]">email</span><?= htmlspecialchars($profile['email']) ?>
      </div>
      <div class="flex items-center gap-2 text-on-surface-variant font-body-sm text-body-sm">
        <span class="material-symbols-outlined text-[16px]">phone</span><?= htmlspecialchars($profile['phone'] ?? '—') ?>
      </div>
      <div class="flex items-center gap-2 text-on-surface-variant font-body-sm text-body-sm">
        <span class="material-symbols-outlined text-[16px]">manage_accounts</span><?= htmlspecialchars($profile['username']) ?> (<?= $profile['role'] ?>)
      </div>
    </div>
  </div>

  <!-- Stats + Edit form -->
  <div class="lg:col-span-2 space-y-6">

    <!-- Duty Stats -->
    <div class="grid grid-cols-3 gap-4">
      <?php foreach ([['Total Duties',$dutyStats['total'],'assignment','bg-primary text-on-primary'],
                      ['Upcoming',$dutyStats['assigned'],'how_to_reg','bg-tertiary text-on-tertiary'],
                      ['Completed',$dutyStats['completed'],'check_circle','bg-[#15803d] text-white']] as [$lbl,$val,$ico,$cls]): ?>
      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg <?= $cls ?> flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[18px]"><?= $ico ?></span>
        </div>
        <div><p class="font-label-sm text-label-sm text-on-surface-variant"><?= $lbl ?></p>
             <p class="font-headline-sm text-headline-sm text-on-surface"><?= $val ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Edit Form -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm p-6">
      <h3 class="font-headline-sm text-headline-sm text-on-surface mb-4">Edit Profile</h3>
      <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/faculty_save.php" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <input type="hidden" name="faculty_id" value="<?= $profile['faculty_id'] ?>"/>
        <input type="hidden" name="department_id" value="<?= 0 ?>"/>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Full Name</label>
          <input type="text" name="faculty_name" value="<?= htmlspecialchars($profile['faculty_name']) ?>" required
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Phone</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">New Password (leave blank)</label>
          <input type="password" name="password"
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div class="sm:col-span-2">
          <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors">
            Save Changes
          </button>
        </div>
      </form>
    </div>

  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
