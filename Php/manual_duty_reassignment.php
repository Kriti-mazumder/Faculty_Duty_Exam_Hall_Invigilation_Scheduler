<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Manual Duty Reassignment';
$activePage = 'duty-reassignment';

// Load all pending assignments that can be reassigned
$assignments = $pdo->query(
    "SELECT ia.assignment_id, ia.duty_role, ia.assignment_status,
            f.faculty_name AS current_faculty, f.faculty_id AS current_faculty_id,
            e.exam_name, e.exam_date, e.start_time,
            c.course_code, r.room_no
     FROM invigilation_assignment ia
     JOIN faculty f ON f.faculty_id = ia.faculty_id
     JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
     JOIN exam e ON e.exam_id = er.exam_id
     JOIN course c ON c.course_id = e.course_id
     JOIN room r ON r.room_id = er.room_id
     WHERE ia.assignment_status = 'assigned' AND e.exam_date >= CURDATE()
     ORDER BY e.exam_date, e.start_time"
)->fetchAll();

$allFaculty = $pdo->query(
    'SELECT f.faculty_id, f.faculty_name FROM faculty f
     JOIN user_account u ON u.user_id = f.user_id AND u.status = \'active\'
     ORDER BY f.faculty_name'
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
  <span class="font-title-sm text-title-sm text-primary">Manual Duty Reassignment</span>
</nav>

<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span> Duty reassigned successfully.
</div>
<?php elseif ($error): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="bg-surface-container-lowest rounded-xl shadow-sm p-5 mb-6 border-l-4 border-primary">
  <p class="font-body-md text-body-md text-on-surface">
    <strong>Instructions:</strong> Select an existing assignment below, choose a replacement faculty member, and click <strong>Reassign</strong>. This replaces the current invigilator for that duty slot.
  </p>
</div>

<!-- Assignment list with inline reassign -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
  <div class="px-6 py-4 border-b border-surface-container-high">
    <h2 class="font-headline-md text-headline-md text-on-surface">Upcoming Assigned Duties
      <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($assignments) ?></span>
    </h2>
  </div>
  <?php if (empty($assignments)): ?>
  <div class="p-8 text-center text-on-surface-variant">No upcoming assignments to reassign.</div>
  <?php else: ?>
  <table class="w-full table-auto text-sm min-w-[700px]">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course / Room</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Current Invigilator</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Role</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Reassign To</th>
        <th class="px-5 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Action</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($assignments as $a): ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-5 py-3 font-code text-code"><?= htmlspecialchars($a['exam_date']) ?><br>
          <span class="text-xs"><?= substr($a['start_time'],0,5) ?></span></td>
        <td class="px-5 py-3">
          <span class="font-label-md text-label-md text-primary"><?= htmlspecialchars($a['course_code']) ?></span><br>
          <span class="text-xs text-on-surface-variant">Room <?= htmlspecialchars($a['room_no']) ?> · <?= htmlspecialchars($a['exam_name']) ?></span>
        </td>
        <td class="px-5 py-3 font-label-md text-label-md"><?= htmlspecialchars($a['current_faculty']) ?></td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm <?= $a['duty_role']==='chief' ? 'bg-primary/20 text-primary' : 'bg-surface-container text-on-surface-variant' ?>">
            <?= ucfirst($a['duty_role']) ?>
          </span>
        </td>
        <td class="px-5 py-3">
          <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/reassign_duty.php" class="flex items-center gap-2">
            <input type="hidden" name="assignment_id" value="<?= $a['assignment_id'] ?>"/>
            <select name="new_faculty_id" required
                    class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-on-surface text-xs focus:outline-none focus:ring-2 focus:ring-primary-container flex-1">
              <option value="">Select faculty…</option>
              <?php foreach ($allFaculty as $f): ?>
              <?php if ($f['faculty_id'] == $a['current_faculty_id']) continue; ?>
              <option value="<?= $f['faculty_id'] ?>"><?= htmlspecialchars($f['faculty_name']) ?></option>
              <?php endforeach; ?>
            </select>
        </td>
        <td class="px-5 py-3 text-right">
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-primary text-on-primary font-label-sm text-label-sm hover:bg-primary-container transition-colors"
                    onclick="return confirm('Reassign this duty?')">
              Reassign
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
