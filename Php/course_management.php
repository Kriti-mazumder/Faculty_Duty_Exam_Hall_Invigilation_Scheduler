<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Course Management';
$activePage = 'course-management';

$courses = $pdo->query(
    'SELECT c.course_id, c.course_code, c.course_title, c.department_id, c.status, d.department_name
     FROM course c
     JOIN department d ON d.department_id = c.department_id
     ORDER BY d.department_name, c.course_code'
)->fetchAll();

$departments = $pdo->query('SELECT department_id, department_name FROM department ORDER BY department_name')->fetchAll();

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Course Management</span>
</nav>

<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Course saved successfully.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Add Form -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm p-6 mb-6">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-4">Add New Course</h2>
  <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/course_save.php"
        class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <input type="hidden" name="course_id" value="0"/>
    <select name="department_id" required
            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
      <option value="">Select Department</option>
      <?php foreach ($departments as $d): ?>
      <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="course_code" required placeholder="Course Code (e.g. CSE-301)"
           class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    <input type="text" name="course_title" required placeholder="Course Title"
           class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    <button type="submit"
            class="sm:col-span-3 px-6 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:text-on-primary-container transition-colors">
      Add Course
    </button>
  </form>
</section>

<!-- Course Table -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
  <div class="px-6 py-4 border-b border-surface-container-high">
    <h2 class="font-headline-md text-headline-md text-on-surface">All Courses
      <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($courses) ?></span>
    </h2>
  </div>
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Code</th>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Title</th>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Department</th>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Status</th>
        <th class="px-6 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($courses as $c): ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-6 py-3 font-label-md text-label-md text-primary font-code"><?= htmlspecialchars($c['course_code']) ?></td>
        <td class="px-6 py-3"><?= htmlspecialchars($c['course_title']) ?></td>
        <td class="px-6 py-3 text-on-surface-variant"><?= htmlspecialchars($c['department_name']) ?></td>
        <td class="px-6 py-3">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= ($c['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= ucfirst($c['status'] ?? 'active') ?>
          </span>
        </td>
        <td class="px-6 py-3 text-right flex items-center justify-end gap-1">
          <button onclick="openEditCourse(<?= $c['course_id'] ?>, '<?= htmlspecialchars($c['course_code'],ENT_QUOTES) ?>', '<?= htmlspecialchars($c['course_title'],ENT_QUOTES) ?>', <?= $c['department_id'] ?>)"
                  class="p-1.5 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant" title="Edit">
            <span class="material-symbols-outlined text-[18px]">edit</span>
          </button>
          <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/toggle_status.php" class="inline">
            <input type="hidden" name="table" value="course"/>
            <input type="hidden" name="id" value="<?= $c['course_id'] ?>"/>
            <input type="hidden" name="current_status" value="<?= $c['status'] ?? 'active' ?>"/>
            <input type="hidden" name="redirect" value="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/course_management.php"/>
            <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 transition-colors <?= ($c['status'] ?? 'active') === 'active' ? 'text-error' : 'text-green-600' ?>"
                    title="<?= ($c['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Reactivate' ?>"
                    onclick="return confirm('<?= ($c['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Reactivate' ?> this course?')">
              <span class="material-symbols-outlined text-[18px]"><?= ($c['status'] ?? 'active') === 'active' ? 'block' : 'check_circle' ?></span>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-8 w-full max-w-lg">
    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-4">Edit Course</h3>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/course_save.php" class="space-y-4">
      <input type="hidden" name="course_id" id="edit-course-id"/>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Department</label>
        <select name="department_id" required
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <?php foreach ($departments as $d): ?>
          <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Course Code</label>
        <input type="text" name="course_code" id="edit-course-code" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Course Title</label>
        <input type="text" name="course_title" id="edit-course-title" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors">Save</button>
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md transition-colors">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEditCourse(id, code, title, deptId) {
  document.getElementById('edit-course-id').value = id;
  document.getElementById('edit-course-code').value = code;
  document.getElementById('edit-course-title').value = title;
  const sel = document.querySelector('#edit-modal select[name="department_id"]');
  if (sel && deptId) sel.value = deptId;
  document.getElementById('edit-modal').classList.remove('hidden');
  document.getElementById('edit-modal').classList.add('flex');
}
function closeModal() {
  document.getElementById('edit-modal').classList.add('hidden');
  document.getElementById('edit-modal').classList.remove('flex');
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
