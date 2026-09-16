<?php
$required_role = 'any';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Faculty Availability';
$activePage = 'faculty-availability';

$isAdmin = ($_SESSION['role'] === 'admin');

if ($isAdmin) {
    $allFaculty = $pdo->query('
        SELECT f.faculty_id, f.faculty_name, d.department_name 
        FROM faculty f 
        LEFT JOIN department d ON d.department_id = f.department_id 
        ORDER BY f.faculty_name
    ')->fetchAll();

    // If 'all' or not specified, default to 0 (show all records)
    $filterParam = $_GET['faculty_id'] ?? 'all';
    $selectedFid = ($filterParam === 'all' || $filterParam === '') ? 0 : (int) $filterParam;

    if ($selectedFid > 0) {
        $stmt = $pdo->prepare('
            SELECT fa.availability_id, fa.faculty_id, f.faculty_name, d.department_name,
                   fa.available_date, fa.start_time, fa.end_time, fa.status
            FROM faculty_availability fa
            JOIN faculty f ON f.faculty_id = fa.faculty_id
            LEFT JOIN department d ON d.department_id = f.department_id
            WHERE fa.faculty_id = ?
            ORDER BY fa.available_date DESC, fa.start_time ASC
        ');
        $stmt->execute([$selectedFid]);
    } else {
        $stmt = $pdo->query('
            SELECT fa.availability_id, fa.faculty_id, f.faculty_name, d.department_name,
                   fa.available_date, fa.start_time, fa.end_time, fa.status
            FROM faculty_availability fa
            JOIN faculty f ON f.faculty_id = fa.faculty_id
            LEFT JOIN department d ON d.department_id = f.department_id
            ORDER BY fa.available_date DESC, fa.start_time ASC
        ');
    }
    $availability = $stmt->fetchAll();
} else {
    $allFaculty = [];
    $selectedFid = 0;
    $r = $pdo->prepare('SELECT faculty_id, faculty_name FROM faculty WHERE user_id = ?');
    $r->execute([$_SESSION['user_id']]);
    $myFac = $r->fetch();
    $fid = (int) ($myFac['faculty_id'] ?? 0);

    if ($fid > 0) {
        $stmt = $pdo->prepare('
            SELECT fa.availability_id, fa.faculty_id, f.faculty_name, d.department_name,
                   fa.available_date, fa.start_time, fa.end_time, fa.status
            FROM faculty_availability fa
            JOIN faculty f ON f.faculty_id = fa.faculty_id
            LEFT JOIN department d ON d.department_id = f.department_id
            WHERE fa.faculty_id = ?
            ORDER BY fa.available_date DESC, fa.start_time ASC
        ');
        $stmt->execute([$fid]);
        $availability = $stmt->fetchAll();
    } else {
        $availability = [];
    }
}

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Faculty Availability</span>
</nav>

<?php if ($success === '1'): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Availability record saved successfully.
</div>
<?php elseif ($success === 'deleted'): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Availability record removed.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Admin Faculty Filter -->
<?php if ($isAdmin && !empty($allFaculty)): ?>
<div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 mb-6 flex flex-wrap items-center justify-between gap-4">
  <form method="GET" class="flex items-center gap-3">
    <label for="faculty_filter" class="font-label-md text-label-md text-on-surface font-semibold flex items-center gap-1.5">
      <span class="material-symbols-outlined text-[18px] text-primary">filter_alt</span> Filter by Faculty:
    </label>
    <select id="faculty_filter" name="faculty_id" onchange="this.form.submit()"
            class="px-4 py-2 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm">
      <option value="all" <?= $selectedFid === 0 ? 'selected' : '' ?>>All Faculty Members (<?= count($allFaculty) ?> registered)</option>
      <?php foreach ($allFaculty as $f): ?>
      <option value="<?= $f['faculty_id'] ?>" <?= $f['faculty_id'] === $selectedFid ? 'selected' : '' ?>>
        <?= htmlspecialchars($f['faculty_name']) ?> (<?= htmlspecialchars($f['department_name'] ?? 'General') ?>)
      </option>
      <?php endforeach; ?>
    </select>
  </form>
  <div class="text-xs text-on-surface-variant">
    Showing <strong><?= count($availability) ?></strong> record(s)
  </div>
</div>
<div class="mb-6 p-4 rounded-xl bg-surface-container-high/40 text-on-surface-variant text-sm border border-surface-container-high">
  <span class="material-symbols-outlined text-[18px] align-text-bottom mr-1">info</span>
  Availability is declared by faculty members. Use Invigilation Assignments to schedule duties.
</div>
<?php endif; ?>

<?php if (!$isAdmin): ?>
<!-- Add availability -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm p-6 mb-6 border border-surface-container-high/60">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-4 flex items-center gap-2">
    <span class="material-symbols-outlined text-primary">more_time</span> Add Availability Window
  </h2>
  <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/availability_save.php"
        class="grid grid-cols-1 sm:grid-cols-2 <?= $isAdmin ? 'lg:grid-cols-6' : 'lg:grid-cols-5' ?> gap-3">
    <input type="hidden" name="availability_id" value="0"/>
    <input type="hidden" name="action" value="save"/>
    <input type="hidden" name="return_faculty_id" value="<?= $isAdmin ? ($selectedFid ? $selectedFid : 'all') : '' ?>"/>

    <?php if ($isAdmin): ?>
    <div>
      <label class="block text-xs font-semibold text-on-surface-variant mb-1">Faculty Member</label>
      <select name="faculty_id" required
              class="w-full px-3 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
        <?php foreach ($allFaculty as $f): ?>
        <option value="<?= $f['faculty_id'] ?>" <?= ($selectedFid > 0 && $f['faculty_id'] === $selectedFid) ? 'selected' : '' ?>>
          <?= htmlspecialchars($f['faculty_name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <input type="hidden" name="faculty_id" value="<?= $fid ?>"/>
    <?php endif; ?>

    <div>
      <label class="block text-xs font-semibold text-on-surface-variant mb-1">Date</label>
      <input type="date" name="available_date" required
             class="w-full px-3 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    </div>
    <div>
      <label class="block text-xs font-semibold text-on-surface-variant mb-1">Start Time</label>
      <input type="time" name="start_time" required
             class="w-full px-3 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    </div>
    <div>
      <label class="block text-xs font-semibold text-on-surface-variant mb-1">End Time</label>
      <input type="time" name="end_time" required
             class="w-full px-3 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    </div>
    <div>
      <label class="block text-xs font-semibold text-on-surface-variant mb-1">Status</label>
      <select name="status"
              class="w-full px-3 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
        <option value="available">Available</option>
        <option value="unavailable">Unavailable</option>
      </select>
    </div>
    <div class="flex items-end">
      <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors shadow-sm font-semibold flex items-center justify-center gap-1">
        <span class="material-symbols-outlined text-[18px]">add</span> Add Record
      </button>
    </div>
  </form>
</section>
<?php endif; ?>

<!-- Availability Table -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden border border-surface-container-high/60">
  <div class="px-6 py-4 border-b border-surface-container-high flex items-center justify-between">
    <h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
      <span class="material-symbols-outlined text-primary">event_available</span> Availability Records
      <span class="ml-2 px-2.5 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-bold"><?= count($availability) ?></span>
    </h2>
  </div>
  <?php if (empty($availability)): ?>
  <div class="p-12 text-center text-on-surface-variant">
    <span class="material-symbols-outlined text-[42px] text-outline-variant block mb-2">event_busy</span>
    <p class="font-body-md text-body-md">No availability records found. Add one above.</p>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full table-auto text-sm">
      <thead class="bg-surface-container-high">
        <tr>
          <?php if ($isAdmin): ?>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant font-bold">Faculty Member</th>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant font-bold">Department</th>
          <?php endif; ?>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant font-bold">Date</th>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant font-bold">Start Time</th>
          <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant font-bold">End Time</th>
          <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant font-bold">Status</th>
          <?php if (!$isAdmin): ?>
          <th class="px-5 py-3 text-right font-label-sm text-label-sm text-on-surface-variant font-bold">Actions</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-surface-container-high">
        <?php foreach ($availability as $a): ?>
        <tr class="hover:bg-surface-container-low transition-colors">
          <?php if ($isAdmin): ?>
          <td class="px-5 py-3.5 font-label-md text-label-md text-on-surface font-semibold">
            <?= htmlspecialchars($a['faculty_name'] ?? 'Faculty #' . $a['faculty_id']) ?>
          </td>
          <td class="px-5 py-3.5 text-on-surface-variant font-body-sm">
            <?= htmlspecialchars($a['department_name'] ?? 'General') ?>
          </td>
          <?php endif; ?>
          <td class="px-5 py-3.5 font-code text-code text-on-surface">
            <?= htmlspecialchars($a['available_date']) ?>
          </td>
          <td class="px-5 py-3.5 font-code text-code text-on-surface">
            <?= substr($a['start_time'], 0, 5) ?>
          </td>
          <td class="px-5 py-3.5 font-code text-code text-on-surface">
            <?= substr($a['end_time'], 0, 5) ?>
          </td>
          <td class="px-5 py-3.5 text-center">
            <span class="px-2.5 py-1 rounded-full font-label-sm text-label-sm font-semibold inline-flex items-center gap-1 <?= $a['status']==='available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
              <span class="w-1.5 h-1.5 rounded-full <?= $a['status']==='available' ? 'bg-green-600' : 'bg-red-600' ?>"></span>
              <?= ucfirst($a['status']) ?>
            </span>
          </td>
          <?php if (!$isAdmin): ?>
          <td class="px-5 py-3.5 text-right">
            <div class="inline-flex items-center gap-1">
              <button onclick="openEdit(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)"
                      title="Edit Record"
                      class="p-1.5 rounded-lg hover:bg-surface-container-high text-primary hover:text-primary-container transition-colors">
                <span class="material-symbols-outlined text-[18px]">edit</span>
              </button>
              <button onclick="confirmDelete(<?= (int)$a['availability_id'] ?>, '<?= htmlspecialchars($a['available_date'], ENT_QUOTES) ?>')"
                      title="Delete Record"
                      class="p-1.5 rounded-lg hover:bg-red-50 text-red-600 hover:text-red-700 transition-colors">
                <span class="material-symbols-outlined text-[18px]">delete</span>
              </button>
            </div>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-6 sm:p-8 w-full max-w-md border border-surface-container-high">
    <div class="flex items-center justify-between mb-5">
      <h3 class="font-headline-sm text-headline-sm text-on-surface font-bold flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">edit_calendar</span> Edit Availability
      </h3>
      <button type="button" onclick="closeModal()" class="text-on-surface-variant hover:text-on-surface">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
    </div>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/availability_save.php" class="space-y-4">
      <input type="hidden" name="availability_id" id="ea-id"/>
      <input type="hidden" name="action" value="save"/>
      <input type="hidden" name="return_faculty_id" value="<?= $isAdmin ? ($selectedFid ? $selectedFid : 'all') : '' ?>"/>

      <?php if ($isAdmin): ?>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Faculty Member</label>
        <select name="faculty_id" id="ea-faculty-id" required
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <?php foreach ($allFaculty as $f): ?>
          <option value="<?= $f['faculty_id'] ?>"><?= htmlspecialchars($f['faculty_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <input type="hidden" name="faculty_id" value="<?= $fid ?>"/>
      <?php endif; ?>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Date</label>
          <input type="date" name="available_date" id="ea-date" required
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Status</label>
          <select name="status" id="ea-status"
                  class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Start Time</label>
          <input type="time" name="start_time" id="ea-start" required
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
        <div>
          <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">End Time</label>
          <input type="time" name="end_time" id="ea-end" required
                 class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
        </div>
      </div>
      <div class="flex gap-3 pt-3">
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors">Cancel</button>
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors shadow-sm font-semibold">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Form -->
<form id="delete-form" method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/availability_save.php" style="display:none;">
  <input type="hidden" name="action" value="delete"/>
  <input type="hidden" name="availability_id" id="delete-id"/>
  <input type="hidden" name="return_faculty_id" value="<?= $isAdmin ? ($selectedFid ? $selectedFid : 'all') : '' ?>"/>
</form>

<script>
function openEdit(a) {
  document.getElementById('ea-id').value = a.availability_id;
  document.getElementById('ea-date').value = a.available_date;
  document.getElementById('ea-start').value = a.start_time.substring(0,5);
  document.getElementById('ea-end').value = a.end_time.substring(0,5);
  document.getElementById('ea-status').value = a.status;
  const facSelect = document.getElementById('ea-faculty-id');
  if (facSelect && a.faculty_id) {
    facSelect.value = a.faculty_id;
  }
  const modal = document.getElementById('edit-modal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeModal() {
  const modal = document.getElementById('edit-modal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function confirmDelete(id, date) {
  if (confirm('Are you sure you want to remove the availability record for ' + date + '?')) {
    document.getElementById('delete-id').value = id;
    document.getElementById('delete-form').submit();
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

