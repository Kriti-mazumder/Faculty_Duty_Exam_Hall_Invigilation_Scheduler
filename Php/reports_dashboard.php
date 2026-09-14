<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Reports & Documents';
$activePage = 'reports-and-documents';

// Aggregate stats
$totalFaculty   = (int) $pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
$totalExams     = (int) $pdo->query('SELECT COUNT(*) FROM exam')->fetchColumn();
$completedExams = (int) $pdo->query("SELECT COUNT(*) FROM exam WHERE status='completed'")->fetchColumn();
$totalAssign    = (int) $pdo->query('SELECT COUNT(*) FROM invigilation_assignment')->fetchColumn();
$totalRooms     = (int) $pdo->query('SELECT COUNT(*) FROM room')->fetchColumn();

// Department-wise faculty count
$deptStats = $pdo->query(
    'SELECT d.department_name, COUNT(f.faculty_id) AS faculty_count,
            COUNT(DISTINCT c.course_id) AS course_count
     FROM department d
     LEFT JOIN faculty f ON f.department_id = d.department_id
     LEFT JOIN course  c ON c.department_id = d.department_id
     GROUP BY d.department_id
     ORDER BY faculty_count DESC'
)->fetchAll();

// Top invigilators
$topInvigilators = $pdo->query(
    'SELECT f.faculty_name, d.department_name, COUNT(ia.assignment_id) AS duties
     FROM invigilation_assignment ia
     JOIN faculty f ON f.faculty_id = ia.faculty_id
     JOIN department d ON d.department_id = f.department_id
     GROUP BY f.faculty_id
     ORDER BY duties DESC LIMIT 10'
)->fetchAll();

// Room utilization
$roomUtil = $pdo->query(
    'SELECT r.room_no, r.building, r.capacity, COUNT(er.exam_room_id) AS times_used
     FROM room r
     LEFT JOIN exam_room er ON er.room_id = r.room_id
     WHERE r.status = \'active\'
     GROUP BY r.room_id
     ORDER BY times_used DESC'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Reports &amp; Documents</span>
</nav>

<!-- Top summary -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
  <?php
  $cards = [
    ['Total Faculty',   $totalFaculty,   'badge',       'bg-primary text-on-primary'],
    ['Total Exams',     $totalExams,     'assignment',  'bg-tertiary text-on-tertiary'],
    ['Completed Exams', $completedExams, 'check_circle','bg-[#15803d] text-white'],
    ['Total Duties',    $totalAssign,    'how_to_reg',  'bg-secondary text-on-secondary'],
    ['Exam Rooms',      $totalRooms,     'meeting_room','bg-[#7c3aed] text-white'],
  ];
  foreach ($cards as [$lbl, $val, $ico, $cls]): ?>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 flex items-center gap-3">
    <div class="w-10 h-10 rounded-lg <?= $cls ?> flex items-center justify-center flex-shrink-0">
      <span class="material-symbols-outlined text-[20px]"><?= $ico ?></span>
    </div>
    <div>
      <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $lbl ?></p>
      <p class="font-headline-sm text-headline-sm text-on-surface"><?= $val ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

  <!-- Department Stats -->
  <section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-surface-container-high flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-[20px]">corporate_fare</span>
      <h2 class="font-headline-md text-headline-md text-on-surface">Department Summary</h2>
    </div>
    <table class="w-full table-auto text-sm">
      <thead class="bg-surface-container-high">
        <tr>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Department</th>
          <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Faculty</th>
          <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Courses</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-surface-container-high">
        <?php foreach ($deptStats as $ds): ?>
        <tr class="hover:bg-surface-container-low transition-colors">
          <td class="px-5 py-3 font-label-md text-label-md"><?= htmlspecialchars($ds['department_name']) ?></td>
          <td class="px-5 py-3 text-center"><?= $ds['faculty_count'] ?></td>
          <td class="px-5 py-3 text-center"><?= $ds['course_count'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <!-- Top Invigilators -->
  <section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-surface-container-high flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-[20px]">military_tech</span>
      <h2 class="font-headline-md text-headline-md text-on-surface">Top Invigilators</h2>
    </div>
    <table class="w-full table-auto text-sm">
      <thead class="bg-surface-container-high">
        <tr>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Faculty</th>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Department</th>
          <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Duties</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-surface-container-high">
        <?php foreach ($topInvigilators as $i => $ti): ?>
        <tr class="hover:bg-surface-container-low transition-colors">
          <td class="px-5 py-3 flex items-center gap-2">
            <?php if ($i < 3): ?>
            <span class="material-symbols-outlined text-[16px] <?= ['text-yellow-500','text-slate-400','text-amber-700'][$i] ?>">emoji_events</span>
            <?php endif; ?>
            <?= htmlspecialchars($ti['faculty_name']) ?>
          </td>
          <td class="px-5 py-3 text-on-surface-variant text-xs"><?= htmlspecialchars($ti['department_name']) ?></td>
          <td class="px-5 py-3 text-center">
            <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-label-sm"><?= $ti['duties'] ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

</div>

<!-- Room Utilization -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden mb-6">
  <div class="px-5 py-4 border-b border-surface-container-high flex items-center gap-2">
    <span class="material-symbols-outlined text-primary text-[20px]">meeting_room</span>
    <h2 class="font-headline-md text-headline-md text-on-surface">Room Utilization</h2>
  </div>
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Building</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Capacity</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Times Used</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Utilization</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php $maxUsed = empty($roomUtil) ? 1 : max(array_column($roomUtil, 'times_used')); ?>
      <?php foreach ($roomUtil as $ru): ?>
      <?php $pct = $maxUsed ? round($ru['times_used'] / $maxUsed * 100) : 0; ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-5 py-3 font-label-md text-label-md"><?= htmlspecialchars($ru['room_no']) ?></td>
        <td class="px-5 py-3 text-on-surface-variant"><?= htmlspecialchars($ru['building']) ?></td>
        <td class="px-5 py-3 text-center"><?= $ru['capacity'] ?></td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-label-sm"><?= $ru['times_used'] ?></span>
        </td>
        <td class="px-5 py-3 w-40">
          <div class="w-full bg-surface-container-high rounded-full h-2">
            <div class="bg-primary h-2 rounded-full" style="width:<?= $pct ?>%"></div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<!-- Export buttons -->
<div class="flex gap-3">
  <button onclick="window.print()"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors">
    <span class="material-symbols-outlined text-[18px]">print</span> Print Report
  </button>
  <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_timetable.php"
     class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors">
    <span class="material-symbols-outlined text-[18px]">calendar_month</span> View Timetable
  </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
