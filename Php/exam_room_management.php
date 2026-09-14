<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Invigilation Assignments';
$activePage = 'invigilation-assignments';

// All exam-rooms with assignment info
$examRooms = $pdo->query(
    "SELECT er.exam_room_id, er.allocated_students,
            e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time,
            e.required_invigilators, e.status AS exam_status,
            c.course_code, c.course_title,
            r.room_no, r.building,
            COUNT(ia.assignment_id) AS assigned_count,
            GROUP_CONCAT(CONCAT(f.faculty_name,' (',ia.duty_role,')') SEPARATOR ' · ') AS assignments
     FROM exam_room er
     JOIN exam e ON e.exam_id = er.exam_id
     JOIN course c ON c.course_id = e.course_id
     JOIN room r ON r.room_id = er.room_id
     LEFT JOIN invigilation_assignment ia ON ia.exam_room_id = er.exam_room_id
     LEFT JOIN faculty f ON f.faculty_id = ia.faculty_id
     GROUP BY er.exam_room_id
     ORDER BY e.exam_date, e.start_time"
)->fetchAll();

// All active faculty for assignment dropdown
$allFaculty = $pdo->query(
    "SELECT f.faculty_id, f.faculty_name, d.department_name
     FROM faculty f
     JOIN department d ON d.department_id = f.department_id
     JOIN user_account u ON u.user_id = f.user_id AND u.status = 'active'
     ORDER BY f.faculty_name"
)->fetchAll();

// Exams that have no rooms yet (for room allocation form)
$unallocated = $pdo->query(
    "SELECT e.exam_id, e.exam_name, e.exam_date, c.course_code
     FROM exam e
     JOIN course c ON c.course_id = e.course_id
     WHERE e.status = 'scheduled'
       AND e.exam_id NOT IN (SELECT DISTINCT exam_id FROM exam_room)
     ORDER BY e.exam_date"
)->fetchAll();

$allRooms = $pdo->query("SELECT room_id, room_no, building, capacity FROM room WHERE status='active' ORDER BY building, room_no")->fetchAll();

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Invigilation Assignments</span>
</nav>

<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Assignment saved.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Allocate Room to Exam (if any unallocated) -->
<?php if (!empty($unallocated)): ?>
<section class="bg-surface-container-lowest rounded-xl shadow-sm p-6 mb-6 border border-outline-variant/30">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-4 flex items-center gap-2">
    <span class="material-symbols-outlined text-warning text-[20px]">warning</span>
    Exams Without Room Allocation
  </h2>
  <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/exam_room_save.php"
        class="grid grid-cols-1 sm:grid-cols-4 gap-3">
    <select name="exam_id" required
            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
      <option value="">Select Exam</option>
      <?php foreach ($unallocated as $u): ?>
      <option value="<?= $u['exam_id'] ?>"><?= htmlspecialchars($u['course_code'].' – '.$u['exam_name'].' ('.$u['exam_date'].')') ?></option>
      <?php endforeach; ?>
    </select>
    <select name="room_id" required
            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
      <option value="">Select Room</option>
      <?php foreach ($allRooms as $room): ?>
      <option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_no'].' – '.$room['building'].' (cap '.$room['capacity'].')') ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="allocated_students" placeholder="Allocated students" min="0"
           class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    <button type="submit" class="px-4 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors">
      Allocate Room
    </button>
  </form>
</section>
<?php endif; ?>

<!-- Assignment Table -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
  <div class="px-6 py-4 border-b border-surface-container-high">
    <h2 class="font-headline-md text-headline-md text-on-surface">Exam Room Assignments</h2>
  </div>
  <table class="w-full table-auto text-sm min-w-[900px]">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
        <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course / Exam</th>
        <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room</th>
        <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Students</th>
        <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Required</th>
        <th class="px-4 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Assigned</th>
        <th class="px-4 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Invigilators</th>
        <th class="px-4 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Action</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($examRooms as $er): ?>
      <?php $gap = $er['required_invigilators'] - $er['assigned_count']; ?>
      <tr class="hover:bg-surface-container-low transition-colors <?= $gap > 0 ? 'border-l-2 border-error/40' : '' ?>">
        <td class="px-4 py-3 font-code text-code"><?= htmlspecialchars($er['exam_date']) ?><br>
          <span class="text-xs"><?= substr($er['start_time'],0,5) ?>–<?= substr($er['end_time'],0,5) ?></span></td>
        <td class="px-4 py-3">
          <span class="font-label-md text-label-md text-primary"><?= htmlspecialchars($er['course_code']) ?></span><br>
          <span class="text-xs text-on-surface-variant"><?= htmlspecialchars($er['exam_name']) ?></span>
        </td>
        <td class="px-4 py-3"><?= htmlspecialchars($er['room_no']) ?>, <?= htmlspecialchars($er['building']) ?></td>
        <td class="px-4 py-3 text-center"><?= $er['allocated_students'] ?></td>
        <td class="px-4 py-3 text-center"><?= $er['required_invigilators'] ?></td>
        <td class="px-4 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $gap > 0 ? 'bg-error/10 text-error' : 'bg-green-100 text-green-700' ?>">
            <?= $er['assigned_count'] ?><?= $gap > 0 ? " (need $gap more)" : '' ?>
          </span>
        </td>
        <td class="px-4 py-3 text-xs text-on-surface-variant"><?= htmlspecialchars($er['assignments'] ?? '—') ?></td>
        <td class="px-4 py-3 text-right">
          <?php if ($er['exam_status'] === 'scheduled'): ?>
          <button onclick="openAssignModal(<?= $er['exam_room_id'] ?>, '<?= htmlspecialchars($er['course_code'].' '.$er['exam_name'].' – Room '.$er['room_no'], ENT_QUOTES) ?>')"
                  class="px-3 py-1.5 rounded-lg bg-primary/10 text-primary font-label-sm text-label-sm hover:bg-primary hover:text-on-primary transition-colors">
            Assign
          </button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<!-- Assign Invigilator Modal -->
<div id="assign-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-8 w-full max-w-md">
    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-1">Assign Invigilator</h3>
    <p class="font-body-sm text-body-sm text-on-surface-variant mb-5" id="assign-label"></p>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/assignment_save.php" class="space-y-4">
      <input type="hidden" name="exam_room_id" id="a-exam-room-id"/>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Faculty *</label>
        <select name="faculty_id" required
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <option value="">Select Faculty</option>
          <?php foreach ($allFaculty as $f): ?>
          <option value="<?= $f['faculty_id'] ?>"><?= htmlspecialchars($f['faculty_name'].' – '.$f['department_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Role</label>
        <select name="duty_role"
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <option value="assistant">Assistant Invigilator</option>
          <option value="chief">Chief Invigilator</option>
        </select>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors">Assign</button>
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md transition-colors">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAssignModal(examRoomId, label) {
  document.getElementById('a-exam-room-id').value = examRoomId;
  document.getElementById('assign-label').textContent = label;
  document.getElementById('assign-modal').classList.remove('hidden');
  document.getElementById('assign-modal').classList.add('flex');
}
function closeModal() {
  document.getElementById('assign-modal').classList.add('hidden');
  document.getElementById('assign-modal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
