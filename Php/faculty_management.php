<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Faculty Management';
$activePage = 'faculty-management';

$faculty = $pdo->query(
    'SELECT f.faculty_id, f.faculty_name, f.email, f.phone, f.designation,
            f.department_id, d.department_name, u.user_id, u.username, u.status,
            COUNT(ia.assignment_id) AS duty_count
     FROM faculty f
     JOIN department d       ON d.department_id = f.department_id
     JOIN user_account u     ON u.user_id = f.user_id
     LEFT JOIN invigilation_assignment ia ON ia.faculty_id = f.faculty_id
     GROUP BY f.faculty_id
     ORDER BY f.faculty_name'
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
  <span class="font-title-sm text-title-sm text-primary">Faculty Management</span>
</nav>

<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Faculty record saved.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Add Button -->
<div class="flex justify-between items-center mb-4">
  <h2 class="font-headline-md text-headline-md text-on-surface">All Faculty
    <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($faculty) ?></span>
  </h2>
  <button onclick="openAddModal()"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:text-on-primary-container transition-colors">
    <span class="material-symbols-outlined text-[18px]">person_add</span> Add Faculty
  </button>
</div>

<!-- Faculty Table -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Name</th>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Designation</th>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Department</th>
        <th class="px-6 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Email</th>
        <th class="px-6 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Duties</th>
        <th class="px-6 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Status</th>
        <th class="px-6 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($faculty as $f): ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-6 py-3 font-label-md text-label-md"><?= htmlspecialchars($f['faculty_name']) ?></td>
        <td class="px-6 py-3 text-on-surface-variant"><?= htmlspecialchars($f['designation'] ?? '—') ?></td>
        <td class="px-6 py-3 text-on-surface-variant"><?= htmlspecialchars($f['department_name']) ?></td>
        <td class="px-6 py-3 text-on-surface-variant"><?= htmlspecialchars($f['email']) ?></td>
        <td class="px-6 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-label-sm"><?= $f['duty_count'] ?></span>
        </td>
        <td class="px-6 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $f['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= ucfirst($f['status']) ?>
          </span>
        </td>
        <td class="px-6 py-3 text-right flex items-center justify-end gap-1">
          <button onclick="openEditModal(<?= htmlspecialchars(json_encode($f), ENT_QUOTES) ?>)"
                  class="p-1.5 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant" title="Edit">
            <span class="material-symbols-outlined text-[18px]">edit</span>
          </button>
          <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/toggle_status.php" class="inline">
            <input type="hidden" name="table" value="user_account"/>
            <input type="hidden" name="id" value="<?= $f['user_id'] ?>"/>
            <input type="hidden" name="current_status" value="<?= $f['status'] ?>"/>
            <input type="hidden" name="redirect" value="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_management.php"/>
            <button type="submit"
                    class="p-1.5 rounded-lg hover:bg-red-50 transition-colors <?= $f['status']==='active' ? 'text-error' : 'text-green-600' ?>"
                    title="<?= $f['status']==='active' ? 'Deactivate' : 'Reactivate' ?>"
                    onclick="return confirm('<?= $f['status']==='active' ? 'Deactivate' : 'Reactivate' ?> this faculty member?')">
              <span class="material-symbols-outlined text-[18px]"><?= $f['status']==='active' ? 'person_off' : 'person' ?></span>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<!-- Add / Edit Modal -->
<div id="faculty-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-8 w-full max-w-xl max-h-screen overflow-y-auto">
    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-5" id="modal-title">Add Faculty</h3>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/faculty_save.php" class="space-y-4">
      <input type="hidden" name="faculty_id" id="f-id" value="0"/>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Full Name *</label>
          <input type="text" name="faculty_name" id="f-name" required
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Email *</label>
          <input type="email" name="email" id="f-email" required
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Department *</label>
          <select name="department_id" id="f-dept" required
                  class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
            <?php foreach ($departments as $d): ?>
            <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Designation</label>
          <input type="text" name="designation" id="f-desig"
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Phone</label>
          <input type="text" name="phone" id="f-phone"
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div id="username-field">
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Username (new only)</label>
          <input type="text" name="username" id="f-username"
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Password (leave blank to keep)</label>
          <input type="password" name="password" id="f-password"
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors">Save</button>
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md transition-colors">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('modal-title').textContent = 'Add Faculty';
  document.getElementById('f-id').value = 0;
  ['f-name','f-email','f-desig','f-phone','f-username','f-password'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('username-field').style.display = '';
  showModal();
}
function openEditModal(f) {
  document.getElementById('modal-title').textContent = 'Edit Faculty';
  document.getElementById('f-id').value = f.faculty_id;
  document.getElementById('f-name').value = f.faculty_name;
  document.getElementById('f-email').value = f.email;
  document.getElementById('f-desig').value = f.designation ?? '';
  document.getElementById('f-phone').value = f.phone ?? '';
  document.getElementById('f-dept').value = f.department_id ?? '';
  document.getElementById('username-field').style.display = 'none';
  showModal();
}
function showModal() {
  document.getElementById('faculty-modal').classList.remove('hidden');
  document.getElementById('faculty-modal').classList.add('flex');
}
function closeModal() {
  document.getElementById('faculty-modal').classList.add('hidden');
  document.getElementById('faculty-modal').classList.remove('flex');
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
