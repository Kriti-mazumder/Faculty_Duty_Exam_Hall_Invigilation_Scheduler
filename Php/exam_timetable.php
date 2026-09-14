<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Exam Timetable & Rooms';
$activePage = 'exam-timetable';

// All scheduled exams with rooms and invigilators
$exams = $pdo->query(
    "SELECT e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time,
            e.student_count, e.required_invigilators, e.status,
            c.course_code, c.course_title,
            d.department_name,
            GROUP_CONCAT(DISTINCT r.room_no ORDER BY r.room_no SEPARATOR ', ') AS rooms,
            GROUP_CONCAT(DISTINCT f.faculty_name ORDER BY f.faculty_name SEPARATOR ', ') AS invigilators,
            COUNT(DISTINCT ia.assignment_id) AS assigned_count
     FROM exam e
     JOIN course c ON c.course_id = e.course_id
     JOIN department d ON d.department_id = c.department_id
     LEFT JOIN exam_room er ON er.exam_id = e.exam_id
     LEFT JOIN room r ON r.room_id = er.room_id
     LEFT JOIN invigilation_assignment ia ON ia.exam_room_id = er.exam_room_id
     LEFT JOIN faculty f ON f.faculty_id = ia.faculty_id
     GROUP BY e.exam_id
     ORDER BY e.exam_date, e.start_time"
)->fetchAll();

// Group by date for calendar-like display
$byDate = [];
foreach ($exams as $ex) {
    $byDate[$ex['exam_date']][] = $ex;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Exam Timetable &amp; Rooms</span>
</nav>

<!-- Header bar -->
<div class="bg-surface-container-lowest rounded-xl shadow-sm p-5 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
  <div>
    <div class="flex items-center gap-2 mb-1">
      <span class="px-2.5 py-0.5 rounded bg-primary text-on-primary font-label-sm text-label-sm uppercase tracking-wider">Controller Ledger</span>
      <span class="text-on-surface-variant font-code text-code">AY-2024-25</span>
    </div>
    <h1 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Central Examination Timetable</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">University-wide exam schedule with room and invigilator assignments.</p>
  </div>
  <div class="flex gap-2">
    <button onclick="window.print()"
            class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors">
      <span class="material-symbols-outlined text-[18px]">print</span> Print
    </button>
    <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_management.php"
       class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors">
      <span class="material-symbols-outlined text-[18px]">add</span> New Exam
    </a>
  </div>
</div>

<!-- View Toggle -->
<div class="flex gap-2 mb-4">
  <button onclick="showView('table')" id="btn-table"
          class="px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors">
    <span class="material-symbols-outlined text-[16px]">table_chart</span> Table View
  </button>
  <button onclick="showView('grouped')" id="btn-grouped"
          class="px-4 py-2 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors">
    <span class="material-symbols-outlined text-[16px]">calendar_month</span> Grouped by Date
  </button>
</div>

<!-- Table View -->
<div id="view-table">
  <section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
    <table class="w-full table-auto text-sm min-w-[900px]">
      <thead class="bg-surface-container-high">
        <tr>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Time</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Exam</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Dept</th>
          <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Students</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room(s)</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Invigilator(s)</th>
          <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-surface-container-high">
        <?php foreach ($exams as $e): ?>
        <?php
          $isToday = ($e['exam_date'] === date('Y-m-d'));
          $rowCls  = $isToday ? 'bg-primary/5 hover:bg-primary/10' : 'hover:bg-surface-container-low';
          $sc = ['scheduled'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
          $sCls = $sc[$e['status']] ?? 'bg-surface-container text-on-surface';
          $noInvig = ($e['assigned_count'] < $e['required_invigilators']);
        ?>
        <tr class="<?= $rowCls ?> transition-colors">
          <td class="px-4 py-3 font-code text-code <?= $isToday ? 'text-primary font-bold' : '' ?>">
            <?= $isToday ? '<span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span> Today</span>' : htmlspecialchars($e['exam_date']) ?>
          </td>
          <td class="px-4 py-3 font-code text-code"><?= substr($e['start_time'],0,5) ?>–<?= substr($e['end_time'],0,5) ?></td>
          <td class="px-4 py-3"><span class="font-label-md text-label-md text-primary"><?= htmlspecialchars($e['course_code']) ?></span></td>
          <td class="px-4 py-3"><?= htmlspecialchars($e['exam_name']) ?></td>
          <td class="px-4 py-3 text-on-surface-variant text-xs"><?= htmlspecialchars($e['department_name']) ?></td>
          <td class="px-4 py-3 text-center"><?= $e['student_count'] ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($e['rooms'] ?? '—') ?></td>
          <td class="px-4 py-3">
            <?php if ($noInvig && $e['status'] === 'scheduled'): ?>
              <span class="text-error font-label-sm text-label-sm"><?= $e['invigilators'] ? htmlspecialchars($e['invigilators']) . ' (needs more)' : 'Unassigned' ?></span>
            <?php else: ?>
              <?= htmlspecialchars($e['invigilators'] ?? '—') ?>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $sCls ?>"><?= ucfirst($e['status']) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>

<!-- Grouped by Date View -->
<div id="view-grouped" class="hidden space-y-4">
  <?php foreach ($byDate as $date => $dayExams): ?>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-3 bg-surface-container-high flex items-center gap-3">
      <span class="material-symbols-outlined text-primary text-[20px]">calendar_today</span>
      <span class="font-title-sm text-title-sm text-on-surface"><?= date('l, d F Y', strtotime($date)) ?></span>
      <?php if ($date === date('Y-m-d')): ?>
        <span class="px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm">Today</span>
      <?php endif; ?>
      <span class="ml-auto font-label-sm text-label-sm text-on-surface-variant"><?= count($dayExams) ?> exam(s)</span>
    </div>
    <div class="divide-y divide-surface-container-high">
      <?php foreach ($dayExams as $e): ?>
      <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-shrink-0 w-24 font-code text-code text-on-surface-variant text-center">
          <?= substr($e['start_time'],0,5) ?><br><span class="text-xs">to <?= substr($e['end_time'],0,5) ?></span>
        </div>
        <div class="flex-1">
          <p class="font-label-md text-label-md text-primary"><?= htmlspecialchars($e['course_code']) ?> – <?= htmlspecialchars($e['exam_name']) ?></p>
          <p class="font-body-sm text-body-sm text-on-surface-variant"><?= htmlspecialchars($e['department_name']) ?> · <?= $e['student_count'] ?> students</p>
        </div>
        <div class="text-sm text-on-surface-variant">
          <span class="material-symbols-outlined text-[14px] align-middle">meeting_room</span> <?= htmlspecialchars($e['rooms'] ?? 'No room') ?>
        </div>
        <div class="text-sm text-on-surface-variant">
          <span class="material-symbols-outlined text-[14px] align-middle">how_to_reg</span> <?= htmlspecialchars($e['invigilators'] ?? 'Unassigned') ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($byDate)): ?>
  <div class="bg-surface-container-lowest rounded-xl p-8 text-center text-on-surface-variant">No exams scheduled.</div>
  <?php endif; ?>
</div>

<script>
function showView(v) {
  document.getElementById('view-table').classList.toggle('hidden', v !== 'table');
  document.getElementById('view-grouped').classList.toggle('hidden', v !== 'grouped');
  document.getElementById('btn-table').className   = v==='table'   ? 'px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors' : 'px-4 py-2 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors';
  document.getElementById('btn-grouped').className = v==='grouped' ? 'px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors' : 'px-4 py-2 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
