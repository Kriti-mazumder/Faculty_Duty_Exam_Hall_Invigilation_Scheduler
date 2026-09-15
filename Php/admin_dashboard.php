<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Admin Dashboard';
$activePage = 'admin-dashboard';

// ── Overview Stats ─────────────────────────────────────────────
$totalFaculty   = (int) $pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
$availableRooms = (int) $pdo->query("SELECT COUNT(*) FROM room WHERE status = 'active'")->fetchColumn();
$assignedDuties = (int) $pdo->query("SELECT COUNT(*) FROM invigilation_assignment WHERE assignment_status = 'assigned'")->fetchColumn();
$upcomingExams  = (int) $pdo->query("SELECT COUNT(*) FROM exam WHERE exam_date >= CURDATE() AND status = 'scheduled'")->fetchColumn();

// Rooms occupied today
$occupiedRoomsToday = (int) $pdo->query(
    "SELECT COUNT(DISTINCT er.room_id) FROM exam_room er
     JOIN exam e ON e.exam_id = er.exam_id
     WHERE e.exam_date = CURDATE() AND e.status = 'scheduled'"
)->fetchColumn();

// ── Today's Exams ──────────────────────────────────────────────
$todayExams = $pdo->query(
    "SELECT e.exam_name, e.start_time, e.end_time, c.course_code, c.course_title,
            r.room_no, r.building,
            GROUP_CONCAT(f.faculty_name ORDER BY f.faculty_name SEPARATOR ', ') AS invigilators
     FROM exam e
     JOIN course c ON c.course_id = e.course_id
     LEFT JOIN exam_room er ON er.exam_id = e.exam_id
     LEFT JOIN room r ON r.room_id = er.room_id
     LEFT JOIN invigilation_assignment ia ON ia.exam_room_id = er.exam_room_id
     LEFT JOIN faculty f ON f.faculty_id = ia.faculty_id
     WHERE e.exam_date = CURDATE() AND e.status = 'scheduled'
     GROUP BY e.exam_id, er.exam_room_id"
)->fetchAll();

// ── Upcoming Exams (next 14 days) ──────────────────────────────
$upcomingDuties = $pdo->query(
    "SELECT e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time,
            c.course_code, c.course_title,
            r.room_no,
            GROUP_CONCAT(f.faculty_name SEPARATOR ', ') AS invigilators
     FROM exam e
     JOIN course c ON c.course_id = e.course_id
     LEFT JOIN exam_room er ON er.exam_id = e.exam_id
     LEFT JOIN room r ON r.room_id = er.room_id
     LEFT JOIN invigilation_assignment ia ON ia.exam_room_id = er.exam_room_id
     LEFT JOIN faculty f ON f.faculty_id = ia.faculty_id
     WHERE e.exam_date > CURDATE() AND e.exam_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
       AND e.status = 'scheduled'
     GROUP BY e.exam_id, er.exam_room_id
     ORDER BY e.exam_date, e.start_time
     LIMIT 10"
)->fetchAll();

// ── Faculty Workload ───────────────────────────────────────────
$facultyWorkload = $pdo->query(
    "SELECT f.faculty_name, d.department_name,
            COUNT(ia.assignment_id) AS duty_count
     FROM faculty f
     JOIN department d ON d.department_id = f.department_id
     LEFT JOIN invigilation_assignment ia ON ia.faculty_id = f.faculty_id
     GROUP BY f.faculty_id
     ORDER BY duty_count DESC
     LIMIT 10"
)->fetchAll();

// ── Room Utilization ───────────────────────────────────────────
$rooms = $pdo->query(
    "SELECT r.room_no, r.building, r.capacity, r.status,
            COUNT(er.exam_room_id) AS times_used
     FROM room r
     LEFT JOIN exam_room er ON er.room_id = r.room_id
     GROUP BY r.room_id
     ORDER BY r.building, r.room_no"
)->fetchAll();

// ── Alerts: Count scheduled upcoming exams that have fewer than required invigilators ──
$unassignedExams = (int) $pdo->query(
    "SELECT COUNT(*) FROM (
        SELECT e.exam_id, e.required_invigilators
        FROM exam e
        JOIN exam_room er ON er.exam_id = e.exam_id
        LEFT JOIN invigilation_assignment ia ON ia.exam_room_id = er.exam_room_id AND ia.assignment_status = 'assigned'
        WHERE e.exam_date >= CURDATE() AND e.status = 'scheduled'
        GROUP BY e.exam_id, e.required_invigilators
        HAVING COUNT(DISTINCT ia.assignment_id) < e.required_invigilators
    ) AS under_assigned_exams"
)->fetchColumn();

// Maintain single active admin notification for staffing alert
if ($unassignedExams > 0) {
    $alertTitle = 'Staffing Alert';
    
    // Check for existing unread staffing alert for admin (user_id = 1)
    $stmt = $pdo->prepare('SELECT notification_id FROM notification WHERE user_id = 1 AND title = ? AND is_read = 0');
    $stmt->execute([$alertTitle]);
    $existingAlertId = $stmt->fetchColumn();
    
    $msg = "{$unassignedExams} upcoming scheduled exam(s) require more invigilators to meet required staffing.";
    
    if (!$existingAlertId) {
        $pdo->prepare('INSERT INTO notification (user_id, title, message) VALUES (1, ?, ?)')
            ->execute([$alertTitle, $msg]);
    } else {
        $pdo->prepare('UPDATE notification SET message = ?, created_at = NOW() WHERE notification_id = ?')
            ->execute([$msg, $existingAlertId]);
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<!-- Breadcrumb -->
<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Admin Dashboard</span>
</nav>

<!-- ── Overview & Stats ─────────────────────────────────────── -->
<section class="mb-6">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-3">Overview &amp; Statistics</h2>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $stats = [
      ['icon'=>'badge',        'label'=>'Total Faculty',     'value'=>$totalFaculty,    'color'=>'bg-primary text-on-primary'],
      ['icon'=>'assignment',   'label'=>'Upcoming Exams',    'value'=>$upcomingExams,   'color'=>'bg-tertiary text-on-tertiary'],
      ['icon'=>'how_to_reg',   'label'=>'Assigned Duties',   'value'=>$assignedDuties,  'color'=>'bg-secondary text-on-secondary'],
      ['icon'=>'meeting_room', 'label'=>'Available Rooms',   'value'=>$availableRooms,  'color'=>'bg-[#15803d] text-white'],
    ];
    foreach ($stats as $s): ?>
    <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg <?= $s['color'] ?> flex items-center justify-center flex-shrink-0">
        <span class="material-symbols-outlined text-[22px]"><?= $s['icon'] ?></span>
      </div>
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $s['label'] ?></p>
        <p class="font-headline-sm text-headline-sm text-on-surface"><?= $s['value'] ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Today's Overview ─────────────────────────────────────── -->
<section class="mb-6">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-3">Today's Overview
    <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($todayExams) ?> exam<?= count($todayExams) !== 1 ? 's' : '' ?></span>
  </h2>
  <?php if (empty($todayExams)): ?>
    <div class="bg-surface-container-lowest rounded-xl p-6 text-center text-on-surface-variant">No exams scheduled for today.</div>
  <?php else: ?>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <table class="w-full table-auto text-sm">
      <thead class="bg-surface-container-high">
        <tr>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Exam</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Time</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Invigilator(s)</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-surface-container-high">
        <?php foreach ($todayExams as $ex): ?>
        <tr class="hover:bg-surface-container-low transition-colors">
          <td class="px-4 py-3 font-label-md text-label-md text-primary"><?= htmlspecialchars($ex['course_code']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($ex['exam_name']) ?></td>
          <td class="px-4 py-3 font-code text-code"><?= substr($ex['start_time'],0,5) ?> – <?= substr($ex['end_time'],0,5) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($ex['room_no'] ?? '—') ?>, <?= htmlspecialchars($ex['building'] ?? '') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($ex['invigilators'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<!-- ── Upcoming Exams & Duties ──────────────────────────────── -->
<section class="mb-6">
  <div class="flex items-center justify-between mb-3">
    <h2 class="font-headline-md text-headline-md text-on-surface">Upcoming Exams &amp; Invigilation Duties</h2>
    <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_timetable.php"
       class="font-label-md text-label-md text-primary hover:underline">View full timetable →</a>
  </div>
  <?php if (empty($upcomingDuties)): ?>
    <div class="bg-surface-container-lowest rounded-xl p-6 text-center text-on-surface-variant">No upcoming exams in the next 14 days.</div>
  <?php else: ?>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <table class="w-full table-auto text-sm">
      <thead class="bg-surface-container-high">
        <tr>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Time</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Exam</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room</th>
          <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Invigilator(s)</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-surface-container-high">
        <?php foreach ($upcomingDuties as $d): ?>
        <tr class="hover:bg-surface-container-low transition-colors">
          <td class="px-4 py-3 font-code text-code"><?= htmlspecialchars($d['exam_date']) ?></td>
          <td class="px-4 py-3 font-code text-code"><?= substr($d['start_time'],0,5) ?></td>
          <td class="px-4 py-3 font-label-md text-label-md text-primary"><?= htmlspecialchars($d['course_code']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($d['exam_name']) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($d['room_no'] ?? '—') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($d['invigilators'] ?? '<span class="text-error">Unassigned</span>') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<!-- ── Faculty Workload + Room Utilization ─────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

  <!-- Faculty Workload -->
  <section>
    <div class="flex items-center justify-between mb-3">
      <h2 class="font-headline-md text-headline-md text-on-surface">Faculty Workload Summary</h2>
      <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_workload.php"
         class="font-label-md text-label-md text-primary hover:underline">View all →</a>
    </div>
    <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
      <table class="w-full table-auto text-sm">
        <thead class="bg-surface-container-high">
          <tr>
            <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Faculty</th>
            <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Department</th>
            <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Duties</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container-high">
          <?php foreach ($facultyWorkload as $fw): ?>
          <tr class="hover:bg-surface-container-low transition-colors">
            <td class="px-4 py-3"><?= htmlspecialchars($fw['faculty_name']) ?></td>
            <td class="px-4 py-3 text-on-surface-variant text-xs"><?= htmlspecialchars($fw['department_name']) ?></td>
            <td class="px-4 py-3 text-center">
              <span class="px-2 py-0.5 rounded-full <?= $fw['duty_count'] > 5 ? 'bg-error/10 text-error' : 'bg-primary/10 text-primary' ?> font-label-sm text-label-sm">
                <?= $fw['duty_count'] ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Room Utilization -->
  <section>
    <div class="flex items-center justify-between mb-3">
      <h2 class="font-headline-md text-headline-md text-on-surface">Room Utilization</h2>
      <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/room_management.php"
         class="font-label-md text-label-md text-primary hover:underline">Manage →</a>
    </div>
    <div class="grid grid-cols-2 gap-3 mb-3">
      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 text-center">
        <p class="font-headline-sm text-headline-sm text-[#15803d]"><?= $availableRooms - $occupiedRoomsToday ?></p>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Available Today</p>
      </div>
      <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 text-center">
        <p class="font-headline-sm text-headline-sm text-primary"><?= $occupiedRoomsToday ?></p>
        <p class="font-label-sm text-label-sm text-on-surface-variant">In Use Today</p>
      </div>
    </div>
    <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
      <table class="w-full table-auto text-sm">
        <thead class="bg-surface-container-high">
          <tr>
            <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room</th>
            <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Building</th>
            <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Cap.</th>
            <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container-high">
          <?php foreach ($rooms as $r): ?>
          <tr class="hover:bg-surface-container-low transition-colors">
            <td class="px-4 py-3 font-label-md text-label-md"><?= htmlspecialchars($r['room_no']) ?></td>
            <td class="px-4 py-3 text-on-surface-variant text-xs"><?= htmlspecialchars($r['building']) ?></td>
            <td class="px-4 py-3 text-center"><?= $r['capacity'] ?></td>
            <td class="px-4 py-3 text-center">
              <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $r['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>

<!-- ── Quick Actions ────────────────────────────────────────── -->
<section class="mb-6">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-3">Quick Actions</h2>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
    <?php
    $actions = [
      ['href'=>'faculty_management.php',   'icon'=>'badge',            'label'=>'Manage Faculty'],
      ['href'=>'exam_management.php',      'icon'=>'assignment',       'label'=>'Manage Exams'],
      ['href'=>'exam_room_management.php', 'icon'=>'how_to_reg',       'label'=>'Assign Invigilators'],
      ['href'=>'room_management.php',      'icon'=>'meeting_room',     'label'=>'Manage Rooms'],
      ['href'=>'exam_timetable.php',       'icon'=>'calendar_month',   'label'=>'Exam Timetable'],
    ];
    $base2 = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/';
    foreach ($actions as $a): ?>
    <a href="<?= $base2 . $a['href'] ?>"
       class="flex flex-col items-center gap-2 p-4 bg-surface-container-lowest rounded-xl shadow-sm hover:shadow-md hover:bg-primary-container/20 hover:-translate-y-0.5 transition-all duration-200 text-center group">
      <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary group-hover:text-on-primary transition-colors">
        <span class="material-symbols-outlined text-primary group-hover:text-on-primary text-[22px]"><?= $a['icon'] ?></span>
      </div>
      <span class="font-label-md text-label-md text-on-surface"><?= $a['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── Alerts & Staffing Status ────────────────────────────── -->
<section class="mb-6">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-3">Alerts &amp; Staffing Status</h2>
  <div class="space-y-2">
    <?php if ($unassignedExams > 0): ?>
    <div class="flex items-start gap-3 p-4 rounded-xl bg-error/5 border border-error/20">
      <span class="material-symbols-outlined text-error text-[20px] mt-0.5">warning</span>
      <div>
        <p class="font-label-md text-label-md text-error font-semibold">Staffing Alert: Under-assigned / Unassigned Exams</p>
        <p class="font-body-sm text-body-sm text-on-surface-variant"><?= $unassignedExams ?> upcoming scheduled exam<?= $unassignedExams !== 1 ? 's require' : ' requires' ?> more invigilators to meet required staffing.</p>
      </div>
      <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/exam_room_management.php"
         class="ml-auto font-label-md text-label-md text-error hover:underline whitespace-nowrap font-medium">Assign Invigilators →</a>
    </div>
    <?php else: ?>
    <div class="flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 shadow-sm">
      <span class="material-symbols-outlined text-green-600 text-[20px]">check_circle</span>
      <p class="font-label-md text-label-md text-green-700 font-semibold">All upcoming exams have sufficient invigilators assigned (100% staffed).</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
