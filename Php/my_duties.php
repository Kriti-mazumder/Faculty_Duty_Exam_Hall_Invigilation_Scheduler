<?php
$required_role = 'faculty';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
require_once __DIR__ . '/../includes/auto_complete.php';
autoCompletePastExams($pdo);
$pageTitle  = 'My Assigned Duties';
$activePage = 'my-assigned-duties';

// Get faculty_id — admin can pass ?faculty_id=X to preview
if ($_SESSION['role'] === 'admin' && isset($_GET['faculty_id'])) {
    $fid = (int) $_GET['faculty_id'];
} else {
    $r = $pdo->prepare('SELECT faculty_id FROM faculty WHERE user_id = ?');
    $r->execute([$_SESSION['user_id']]);
    $fid = (int) ($r->fetchColumn() ?: 0);
}

$filter = $_GET['filter'] ?? 'all';
$statusWhere = match($filter) {
    'upcoming'  => "AND e.exam_date >= CURDATE() AND ia.assignment_status = 'assigned'",
    'completed' => "AND ia.assignment_status = 'completed'",
    'cancelled' => "AND ia.assignment_status = 'cancelled'",
    default     => ''
};

$duties = $pdo->prepare(
    "SELECT ia.assignment_id, ia.duty_role, ia.assignment_status, ia.assigned_at,
            e.exam_name, e.exam_date, e.start_time, e.end_time,
            c.course_code, c.course_title,
            r.room_no, r.building
     FROM invigilation_assignment ia
     JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
     JOIN exam e ON e.exam_id = er.exam_id
     JOIN course c ON c.course_id = e.course_id
     JOIN room r ON r.room_id = er.room_id
     WHERE ia.faculty_id = ? $statusWhere
     ORDER BY e.exam_date DESC, e.start_time"
);
$duties->execute([$fid]);
$duties = $duties->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">My Assigned Duties</span>
</nav>

<!-- Filter tabs -->
<div class="flex gap-2 mb-5 flex-wrap">
  <?php foreach (['all'=>'All','upcoming'=>'Upcoming','completed'=>'Completed','cancelled'=>'Cancelled'] as $val=>$lbl): ?>
  <a href="?filter=<?= $val ?>"
     class="px-4 py-2 rounded-xl font-label-md text-label-md transition-colors <?= $filter===$val ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface hover:bg-surface-container-high' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
  <div class="px-6 py-4 border-b border-surface-container-high">
    <h2 class="font-headline-md text-headline-md text-on-surface">Duty History
      <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($duties) ?></span>
    </h2>
  </div>
  <?php if (empty($duties)): ?>
  <div class="p-8 text-center text-on-surface-variant">No duties found for selected filter.</div>
  <?php else: ?>
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Exam</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Role</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Status</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($duties as $d): ?>
      <?php
        $sc = ['assigned'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
        $sCls = $sc[$d['assignment_status']] ?? '';
        $isToday = ($d['exam_date'] === date('Y-m-d'));
      ?>
      <tr class="hover:bg-surface-container-low transition-colors <?= $isToday ? 'border-l-2 border-primary' : '' ?>">
        <td class="px-5 py-3 font-code text-code <?= $isToday ? 'text-primary font-bold' : '' ?>">
          <?= $isToday ? 'TODAY' : htmlspecialchars($d['exam_date']) ?><br>
          <span class="text-xs"><?= substr($d['start_time'],0,5) ?>–<?= substr($d['end_time'],0,5) ?></span>
        </td>
        <td class="px-5 py-3 font-label-md text-label-md text-primary"><?= htmlspecialchars($d['course_code']) ?></td>
        <td class="px-5 py-3"><?= htmlspecialchars($d['exam_name']) ?></td>
        <td class="px-5 py-3"><?= htmlspecialchars($d['room_no']) ?>, <?= htmlspecialchars($d['building']) ?></td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $d['duty_role']==='chief' ? 'bg-primary/20 text-primary' : 'bg-surface-container text-on-surface-variant' ?>">
            <?= ucfirst($d['duty_role']) ?>
          </span>
        </td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $sCls ?>"><?= ucfirst($d['assignment_status']) ?></span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
