<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Exam Management';
$activePage = 'exam-management';

$exams = $pdo->query(
    'SELECT e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time,
            e.required_invigilators, e.student_count, e.status,
            c.course_code, c.course_title, d.department_name
     FROM exam e
     JOIN course c     ON c.course_id = e.course_id
     JOIN department d ON d.department_id = c.department_id
     ORDER BY e.exam_date DESC, e.start_time'
)->fetchAll();

$courses = $pdo->query('SELECT course_id, course_code, course_title FROM course ORDER BY course_code')->fetchAll();

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Exam Management</span>
</nav>

<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Exam saved.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="flex justify-between items-center mb-4">
  <h2 class="font-headline-md text-headline-md text-on-surface">All Exams
    <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($exams) ?></span>
  </h2>
  <button onclick="openAddModal()"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:text-on-primary-container transition-colors">
    <span class="material-symbols-outlined text-[18px]">add</span> Schedule Exam
  </button>
</div>

<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
  <table class="w-full table-auto text-sm min-w-[700px]">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Exam</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Time</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Students</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Status</th>
        <th class="px-5 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($exams as $e): ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-5 py-3">
          <span class="font-label-md text-label-md text-primary font-code"><?= htmlspecialchars($e['course_code']) ?></span>
          <br><span class="text-xs text-on-surface-variant"><?= htmlspecialchars($e['department_name']) ?></span>
        </td>
        <td class="px-5 py-3"><?= htmlspecialchars($e['exam_name']) ?></td>
        <td class="px-5 py-3 font-code text-code"><?= htmlspecialchars($e['exam_date']) ?></td>
        <td class="px-5 py-3 font-code text-code"><?= substr($e['start_time'],0,5) ?> – <?= substr($e['end_time'],0,5) ?></td>
        <td class="px-5 py-3 text-center"><?= $e['student_count'] ?></td>
        <td class="px-5 py-3 text-center">
          <?php
          $sc = ['scheduled'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
          $cls = $sc[$e['status']] ?? 'bg-surface-container text-on-surface';
          ?>
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $cls ?>"><?= ucfirst($e['status']) ?></span>
        </td>
        <td class="px-5 py-3 text-right">
          <button onclick="openEditModal(<?= htmlspecialchars(json_encode($e), ENT_QUOTES) ?>)"
                  class="p-1.5 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px]">edit</span>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<!-- Add / Edit Modal -->
<div id="exam-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-8 w-full max-w-2xl max-h-screen overflow-y-auto">
    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-5" id="modal-title">Schedule Exam</h3>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/exam_save.php" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <input type="hidden" name="exam_id" id="e-id" value="0"/>
      <div class="sm:col-span-2">
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Course *</label>
        <select name="course_id" id="e-course" required
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <?php foreach ($courses as $c): ?>
          <option value="<?= $c['course_id'] ?>"><?= htmlspecialchars($c['course_code'].' – '.$c['course_title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Exam Name *</label>
        <input type="text" name="exam_name" id="e-name" required placeholder="e.g. Midterm Exam"
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Date *</label>
        <input type="date" name="exam_date" id="e-date" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Status</label>
        <select name="status" id="e-status"
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <option value="scheduled">Scheduled</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Start Time *</label>
        <input type="time" name="start_time" id="e-start" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">End Time *</label>
        <input type="time" name="end_time" id="e-end" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Required Invigilators</label>
        <input type="number" name="required_invigilators" id="e-req" min="1" value="1"
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Student Count</label>
        <input type="number" name="student_count" id="e-students" min="0" value="0"
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div class="sm:col-span-2 flex gap-3 pt-2">
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors">Save</button>
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md transition-colors">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
function openAddModal() {
  document.getElementById('modal-title').textContent = 'Schedule Exam';
  document.getElementById('e-id').value = 0;
  ['e-name','e-date','e-start','e-end'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('e-req').value = 1;
  document.getElementById('e-students').value = 0;
  showModal();
}
function openEditModal(e) {
  document.getElementById('modal-title').textContent = 'Edit Exam';
  document.getElementById('e-id').value = e.exam_id;
  document.getElementById('e-name').value = e.exam_name;
  document.getElementById('e-date').value = e.exam_date;
  document.getElementById('e-start').value = e.start_time;
  document.getElementById('e-end').value = e.end_time;
  document.getElementById('e-req').value = e.required_invigilators;
  document.getElementById('e-students').value = e.student_count;
  document.getElementById('e-status').value = e.status;
  document.getElementById('e-course').value = e.course_id;
  showModal();
}
function showModal() {
  document.getElementById('exam-modal').classList.remove('hidden');
  document.getElementById('exam-modal').classList.add('flex');
}
function closeModal() {
  document.getElementById('exam-modal').classList.add('hidden');
  document.getElementById('exam-modal').classList.remove('flex');
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
