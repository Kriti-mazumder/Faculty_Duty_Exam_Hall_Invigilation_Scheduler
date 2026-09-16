<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Department Management';
$activePage = 'department-management';

$departments = $pdo->query(
    'SELECT d.department_id, d.department_name, d.status,
            COUNT(DISTINCT f.faculty_id) AS faculty_count,
            COUNT(DISTINCT c.course_id)  AS course_count
     FROM department d
     LEFT JOIN faculty f ON f.department_id = d.department_id
     LEFT JOIN course  c ON c.department_id = d.department_id
     GROUP BY d.department_id
     ORDER BY d.department_name'
)->fetchAll();

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Department Management</span>
</nav>

<!-- Flash messages -->
<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Department saved successfully.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Add / Edit Form -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm p-6 mb-6">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-4">Add New Department</h2>
  <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/department_save.php" class="flex flex-col sm:flex-row gap-3">
    <input type="hidden" name="department_id" value="0"/>
    <input type="text" name="department_name" required placeholder="Department name"
           class="flex-1 px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    <button type="submit"
            class="px-6 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:text-on-primary-container transition-colors">
      Add Department
    </button>
  </form>
</section>

<!-- Department Table -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
  <div class="px-6 py-4 border-b border-surface-container-high flex items-center justify-between">
    <h2 class="font-headline-md text-headline-md text-on-surface">All Departments
      <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($departments) ?></span>
    </h2>
  </div>
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">#</th>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Department Name</th>
        <th class="px-6 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Faculty</th>
        <th class="px-6 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Courses</th>
        <th class="px-6 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Status</th>
        <th class="px-6 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($departments as $i => $dept): ?>
      <tr class="hover:bg-surface-container-low transition-colors" id="dept-row-<?= $dept['department_id'] ?>">
        <td class="px-6 py-3 text-on-surface-variant"><?= $i + 1 ?></td>
        <td class="px-6 py-3 font-label-md text-label-md"><?= htmlspecialchars($dept['department_name']) ?></td>
        <td class="px-6 py-3 text-center"><?= $dept['faculty_count'] ?></td>
        <td class="px-6 py-3 text-center"><?= $dept['course_count'] ?></td>
        <td class="px-6 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= ($dept['status'] ?? 'active')==='active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= ucfirst($dept['status'] ?? 'active') ?>
          </span>
        </td>
        <td class="px-6 py-3 text-right flex items-center justify-end gap-2">
          <!-- Edit (inline) -->
          <button onclick="openEditDept(<?= $dept['department_id'] ?>, '<?= htmlspecialchars($dept['department_name'], ENT_QUOTES) ?>')"
                  class="p-1.5 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant" title="Edit">
            <span class="material-symbols-outlined text-[18px]">edit</span>
          </button>
          <!-- Soft-delete -->
          <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/toggle_status.php" class="inline">
            <input type="hidden" name="table" value="department"/>
            <input type="hidden" name="id" value="<?= $dept['department_id'] ?>"/>
            <input type="hidden" name="current_status" value="<?= $dept['status'] ?? 'active' ?>"/>
            <input type="hidden" name="redirect" value="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/department_management.php"/>
            <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 transition-colors <?= ($dept['status'] ?? 'active')==='active' ? 'text-error' : 'text-green-600' ?>" 
                    title="<?= ($dept['status'] ?? 'active')==='active' ? 'Deactivate' : 'Reactivate' ?>"
                    onclick="return confirm('<?= ($dept['status'] ?? 'active')==='active' ? 'Deactivate' : 'Reactivate' ?> this department?')">
              <span class="material-symbols-outlined text-[18px]"><?= ($dept['status'] ?? 'active')==='active' ? 'block' : 'check_circle' ?></span>
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
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-8 w-full max-w-md">
    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-4">Edit Department</h3>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/department_save.php" class="space-y-4">
      <input type="hidden" name="department_id" id="edit-dept-id"/>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Department Name</label>
        <input type="text" name="department_name" id="edit-dept-name" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:text-on-primary-container transition-colors">Save</button>
        <button type="button" onclick="closeEditModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditDept(id, name) {
  document.getElementById('edit-dept-id').value = id;
  document.getElementById('edit-dept-name').value = name;
  document.getElementById('edit-modal').classList.remove('hidden');
  document.getElementById('edit-modal').classList.add('flex');
}
function closeEditModal() {
  document.getElementById('edit-modal').classList.add('hidden');
  document.getElementById('edit-modal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
