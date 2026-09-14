<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Room Management';
$activePage = 'room-management';

$rooms = $pdo->query(
    "SELECT r.room_id, r.room_no, r.building, r.capacity, r.room_type, r.status,
            COUNT(DISTINCT er.exam_room_id) AS times_used
     FROM room r
     LEFT JOIN exam_room er ON er.room_id = r.room_id
     GROUP BY r.room_id
     ORDER BY r.building, r.room_no"
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
  <span class="font-title-sm text-title-sm text-primary">Room Management</span>
</nav>

<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Room saved successfully.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="flex justify-between items-center mb-4">
  <h2 class="font-headline-md text-headline-md text-on-surface">All Rooms
    <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($rooms) ?></span>
  </h2>
  <button onclick="openAddModal()"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container hover:text-on-primary-container transition-colors">
    <span class="material-symbols-outlined text-[18px]">add</span> Add Room
  </button>
</div>

<!-- Stats row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <?php
  $active   = count(array_filter($rooms, fn($r) => $r['status']==='active'));
  $inactive = count($rooms) - $active;
  $totalCap = array_sum(array_column($rooms, 'capacity'));
  ?>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 text-center">
    <p class="font-headline-sm text-headline-sm text-[#15803d]"><?= $active ?></p>
    <p class="font-label-sm text-label-sm text-on-surface-variant">Active Rooms</p>
  </div>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 text-center">
    <p class="font-headline-sm text-headline-sm text-error"><?= $inactive ?></p>
    <p class="font-label-sm text-label-sm text-on-surface-variant">Inactive</p>
  </div>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 text-center">
    <p class="font-headline-sm text-headline-sm text-primary"><?= number_format($totalCap) ?></p>
    <p class="font-label-sm text-label-sm text-on-surface-variant">Total Capacity</p>
  </div>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 text-center">
    <p class="font-headline-sm text-headline-sm text-on-surface"><?= count($rooms) ?></p>
    <p class="font-label-sm text-label-sm text-on-surface-variant">Total Rooms</p>
  </div>
</div>

<!-- Room Table -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Room No.</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Building</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Capacity</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Type</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Times Used</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Status</th>
        <th class="px-5 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($rooms as $r): ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-5 py-3 font-label-md text-label-md"><?= htmlspecialchars($r['room_no']) ?></td>
        <td class="px-5 py-3"><?= htmlspecialchars($r['building']) ?></td>
        <td class="px-5 py-3 text-center"><?= $r['capacity'] ?></td>
        <td class="px-5 py-3 text-on-surface-variant"><?= htmlspecialchars($r['room_type'] ?? '—') ?></td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-label-sm"><?= $r['times_used'] ?></span>
        </td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $r['status']==='active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= ucfirst($r['status']) ?>
          </span>
        </td>
        <td class="px-5 py-3 text-right flex items-center justify-end gap-1">
          <button onclick="openEditModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)"
                  class="p-1.5 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant" title="Edit">
            <span class="material-symbols-outlined text-[18px]">edit</span>
          </button>
          <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/toggle_status.php" class="inline">
            <input type="hidden" name="table" value="room"/>
            <input type="hidden" name="id" value="<?= $r['room_id'] ?>"/>
            <input type="hidden" name="current_status" value="<?= $r['status'] ?>"/>
            <input type="hidden" name="redirect" value="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/room_management.php"/>
            <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 transition-colors <?= $r['status']==='active' ? 'text-error' : 'text-green-600' ?>"
                    title="<?= $r['status']==='active' ? 'Deactivate' : 'Reactivate' ?>"
                    onclick="return confirm('<?= $r['status']==='active' ? 'Deactivate' : 'Reactivate' ?> this room?')">
              <span class="material-symbols-outlined text-[18px]"><?= $r['status']==='active' ? 'block' : 'check_circle' ?></span>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<!-- Add/Edit Modal -->
<div id="room-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-8 w-full max-w-lg">
    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-5" id="modal-title">Add Room</h3>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/room_save.php" class="grid grid-cols-2 gap-4">
      <input type="hidden" name="room_id" id="r-id" value="0"/>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Room No. *</label>
        <input type="text" name="room_no" id="r-no" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Building *</label>
        <input type="text" name="building" id="r-building" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Capacity *</label>
        <input type="number" name="capacity" id="r-capacity" min="1" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Room Type</label>
        <select name="room_type" id="r-type"
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <option value="Exam Hall">Exam Hall</option>
          <option value="Lab">Lab</option>
          <option value="Auditorium">Auditorium</option>
          <option value="Classroom">Classroom</option>
        </select>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Status</label>
        <select name="status" id="r-status"
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="col-span-2 flex gap-3 pt-2">
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md transition-colors">Save</button>
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md transition-colors">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  document.getElementById('modal-title').textContent = 'Add Room';
  document.getElementById('r-id').value = 0;
  ['r-no','r-building'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('r-capacity').value = '';
  showModal();
}
function openEditModal(r) {
  document.getElementById('modal-title').textContent = 'Edit Room';
  document.getElementById('r-id').value = r.room_id;
  document.getElementById('r-no').value = r.room_no;
  document.getElementById('r-building').value = r.building;
  document.getElementById('r-capacity').value = r.capacity;
  document.getElementById('r-type').value = r.room_type || 'Exam Hall';
  document.getElementById('r-status').value = r.status;
  showModal();
}
function showModal() {
  document.getElementById('room-modal').classList.remove('hidden');
  document.getElementById('room-modal').classList.add('flex');
}
function closeModal() {
  document.getElementById('room-modal').classList.add('hidden');
  document.getElementById('room-modal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
