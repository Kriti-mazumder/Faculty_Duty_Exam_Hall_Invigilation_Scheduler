<?php
$required_role = 'faculty';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Faculty Dashboard';
$activePage = 'faculty-dashboard';

// Resolve faculty_id from session user_id
$facultyRow = $pdo->prepare('SELECT faculty_id, faculty_name, designation FROM faculty WHERE user_id = ?');
$facultyRow->execute([$_SESSION['user_id']]);
$faculty = $facultyRow->fetch();
if (!$faculty) {
    // admin viewing faculty dashboard — show a message
    $faculty = ['faculty_id' => 0, 'faculty_name' => $_SESSION['username'], 'designation' => 'Admin Preview'];
}
$fid = $faculty['faculty_id'];

// My upcoming duties
$upcomingDuties = $fid ? $pdo->prepare(
    "SELECT ia.assignment_id, ia.duty_role, ia.assignment_status,
            e.exam_name, e.exam_date, e.start_time, e.end_time,
            c.course_code, r.room_no, r.building
     FROM invigilation_assignment ia
     JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
     JOIN exam e ON e.exam_id = er.exam_id
     JOIN course c ON c.course_id = e.course_id
     JOIN room r ON r.room_id = er.room_id
     WHERE ia.faculty_id = ? AND e.exam_date >= CURDATE() AND ia.assignment_status = 'assigned'
     ORDER BY e.exam_date, e.start_time LIMIT 5"
) : null;
if ($upcomingDuties) { $upcomingDuties->execute([$fid]); $upcomingDuties = $upcomingDuties->fetchAll(); }
else $upcomingDuties = [];

$totalDuties = $fid ? (int) $pdo->prepare('SELECT COUNT(*) FROM invigilation_assignment WHERE faculty_id = ?')->execute([$fid]) : 0;
// Simpler approach:
$stTotal = $pdo->prepare('SELECT COUNT(*) FROM invigilation_assignment WHERE faculty_id = ?');
$stTotal->execute([$fid]);
$totalDuties = (int) $stTotal->fetchColumn();

$stDone = $pdo->prepare("SELECT COUNT(*) FROM invigilation_assignment WHERE faculty_id = ? AND assignment_status='completed'");
$stDone->execute([$fid]);
$completedDuties = (int) $stDone->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Faculty Dashboard</span>
</nav>

<!-- Welcome -->
<div class="bg-gradient-to-r from-primary to-tertiary rounded-xl p-6 mb-6 text-on-primary shadow-lg">
  <div class="flex items-center gap-4">
    <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center">
      <span class="material-symbols-outlined text-[32px] text-white">school</span>
    </div>
    <div>
      <h1 class="font-headline-md text-headline-md text-white">Welcome, <?= htmlspecialchars($faculty['faculty_name']) ?></h1>
      <p class="font-body-md text-body-md text-white/80"><?= htmlspecialchars($faculty['designation'] ?? '') ?> · Premier University</p>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 flex items-center gap-3">
    <div class="w-10 h-10 rounded-lg bg-primary text-on-primary flex items-center justify-center">
      <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
    </div>
    <div><p class="font-label-sm text-label-sm text-on-surface-variant">Upcoming Duties</p>
         <p class="font-headline-sm text-headline-sm text-on-surface"><?= count($upcomingDuties) ?></p></div>
  </div>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 flex items-center gap-3">
    <div class="w-10 h-10 rounded-lg bg-[#15803d] text-white flex items-center justify-center">
      <span class="material-symbols-outlined text-[20px]">check_circle</span>
    </div>
    <div><p class="font-label-sm text-label-sm text-on-surface-variant">Completed</p>
         <p class="font-headline-sm text-headline-sm text-on-surface"><?= $completedDuties ?></p></div>
  </div>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 flex items-center gap-3">
    <div class="w-10 h-10 rounded-lg bg-tertiary text-on-tertiary flex items-center justify-center">
      <span class="material-symbols-outlined text-[20px]">assignment</span>
    </div>
    <div><p class="font-label-sm text-label-sm text-on-surface-variant">Total Duties</p>
         <p class="font-headline-sm text-headline-sm text-on-surface"><?= $totalDuties ?></p></div>
  </div>
</div>

<!-- Upcoming Duties -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm mb-6">
  <div class="px-6 py-4 border-b border-surface-container-high flex items-center justify-between">
    <h2 class="font-headline-md text-headline-md text-on-surface">My Upcoming Duties</h2>
    <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/my_duties.php"
       class="font-label-md text-label-md text-primary hover:underline">View all →</a>
  </div>
  <?php if (empty($upcomingDuties)): ?>
  <div class="p-6 text-center text-on-surface-variant">No upcoming duties scheduled.</div>
  <?php else: ?>
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Exam</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Role</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($upcomingDuties as $ud): ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-5 py-3 font-code text-code"><?= htmlspecialchars($ud['exam_date']) ?><br>
          <span class="text-xs"><?= substr($ud['start_time'],0,5) ?>–<?= substr($ud['end_time'],0,5) ?></span></td>
        <td class="px-5 py-3 font-label-md text-label-md text-primary"><?= htmlspecialchars($ud['course_code']) ?></td>
        <td class="px-5 py-3"><?= htmlspecialchars($ud['exam_name']) ?></td>
        <td class="px-5 py-3"><?= htmlspecialchars($ud['room_no']) ?>, <?= htmlspecialchars($ud['building']) ?></td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $ud['duty_role']==='chief' ? 'bg-primary/20 text-primary' : 'bg-surface-container text-on-surface-variant' ?>">
            <?= ucfirst($ud['duty_role']) ?>
          </span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<!-- Quick Links -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
  <?php
  $links = [
    ['href'=>'my_duties.php',                 'icon'=>'checklist',     'label'=>'My Duties'],
    ['href'=>'faculty_availability.php',       'icon'=>'event_available','label'=>'Availability'],
    ['href'=>'duty_preference_management.php', 'icon'=>'tune',          'label'=>'Preferences'],
    ['href'=>'faculty_profile.php',            'icon'=>'manage_accounts','label'=>'My Profile'],
  ];
  $b = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/';
  foreach ($links as $l): ?>
  <a href="<?= $b.$l['href'] ?>"
     class="flex flex-col items-center gap-2 p-4 bg-surface-container-lowest rounded-xl shadow-sm hover:shadow-md hover:bg-primary-container/20 hover:-translate-y-0.5 transition-all duration-200 group text-center">
    <div class="w-10 h-10 rounded-lg bg-primary/10 group-hover:bg-primary flex items-center justify-center transition-colors">
      <span class="material-symbols-outlined text-primary group-hover:text-on-primary text-[22px]"><?= $l['icon'] ?></span>
    </div>
    <span class="font-label-md text-label-md text-on-surface"><?= $l['label'] ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
