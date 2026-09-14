<?php
$required_role = 'any';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Duty Preferences';
$activePage = 'duty-preferences';

// Resolve faculty_id (admin can pass ?faculty_id=X)
if ($_SESSION['role'] === 'admin') {
    $allFaculty = $pdo->query('SELECT faculty_id, faculty_name FROM faculty ORDER BY faculty_name')->fetchAll();
    $fid = (int) ($_GET['faculty_id'] ?? ($allFaculty[0]['faculty_id'] ?? 0));
} else {
    $allFaculty = [];
    $r = $pdo->prepare('SELECT faculty_id FROM faculty WHERE user_id = ?');
    $r->execute([$_SESSION['user_id']]);
    $fid = (int) ($r->fetchColumn() ?: 0);
}

$preferences = $fid ? $pdo->prepare(
    'SELECT preference_id, preferred_date, preferred_time, preference_type, priority
     FROM duty_preference WHERE faculty_id = ? ORDER BY preferred_date'
) : null;
if ($preferences) { $preferences->execute([$fid]); $preferences = $preferences->fetchAll(); }
else $preferences = [];

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Duty Preferences</span>
</nav>

<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Preference saved.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Add preference -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm p-6 mb-6">
  <h2 class="font-headline-md text-headline-md text-on-surface mb-4">Add Preference</h2>
  <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/preference_save.php"
        class="grid grid-cols-1 sm:grid-cols-4 gap-3">
    <input type="hidden" name="preference_id" value="0"/>
    <input type="hidden" name="faculty_id" value="<?= $fid ?>"/>
    <input type="date" name="preferred_date" required
           class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    <input type="time" name="preferred_time"
           class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
    <select name="preference_type"
            class="px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
      <option value="preferred">Preferred</option>
      <option value="neutral" selected>Neutral</option>
      <option value="avoid">Avoid</option>
    </select>
    <button type="submit" class="px-4 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md hover:bg-primary-container transition-colors">
      Add Preference
    </button>
  </form>
</section>

<!-- Preference List -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
  <div class="px-6 py-4 border-b border-surface-container-high">
    <h2 class="font-headline-md text-headline-md text-on-surface">My Preferences
      <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($preferences) ?></span>
    </h2>
  </div>
  <?php if (empty($preferences)): ?>
  <div class="p-8 text-center text-on-surface-variant">No preferences set yet. Add one above.</div>
  <?php else: ?>
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Time</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Type</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Priority</th>
        <th class="px-5 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($preferences as $p): ?>
      <?php $cls = ['preferred'=>'bg-green-100 text-green-700','neutral'=>'bg-surface-container text-on-surface-variant','avoid'=>'bg-red-100 text-red-700'][$p['preference_type']] ?? ''; ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-5 py-3 font-code text-code"><?= htmlspecialchars($p['preferred_date']) ?></td>
        <td class="px-5 py-3 font-code text-code"><?= $p['preferred_time'] ? substr($p['preferred_time'],0,5) : '—' ?></td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $cls ?>"><?= ucfirst($p['preference_type']) ?></span>
        </td>
        <td class="px-5 py-3 text-center"><?= $p['priority'] ?></td>
        <td class="px-5 py-3 text-right">
          <button onclick="openEdit(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"
                  class="p-1.5 rounded-lg hover:bg-surface-container-high text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px]">edit</span>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
  <div class="bg-surface-container-lowest rounded-2xl shadow-2xl p-8 w-full max-w-md">
    <h3 class="font-headline-sm text-headline-sm text-on-surface mb-5">Edit Preference</h3>
    <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/preference_save.php" class="space-y-4">
      <input type="hidden" name="preference_id" id="ep-id"/>
      <input type="hidden" name="faculty_id" value="<?= $fid ?>"/>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Date</label>
        <input type="date" name="preferred_date" id="ep-date" required
               class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container"/>
      </div>
      <div>
        <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Type</label>
        <select name="preference_type" id="ep-type"
                class="w-full px-4 py-2.5 rounded-xl border border-outline-variant bg-surface text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-container">
          <option value="preferred">Preferred</option>
          <option value="neutral">Neutral</option>
          <option value="avoid">Avoid</option>
        </select>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md">Save</button>
        <button type="button" onclick="closeModal()" class="flex-1 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
function openEdit(p) {
  document.getElementById('ep-id').value = p.preference_id;
  document.getElementById('ep-date').value = p.preferred_date;
  document.getElementById('ep-type').value = p.preference_type;
  document.getElementById('edit-modal').classList.remove('hidden');
  document.getElementById('edit-modal').classList.add('flex');
}
function closeModal() {
  document.getElementById('edit-modal').classList.add('hidden');
  document.getElementById('edit-modal').classList.remove('flex');
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
