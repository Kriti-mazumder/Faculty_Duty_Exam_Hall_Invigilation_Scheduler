<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Manual Duty Reassignment & Removal';
$activePage = 'duty-reassignment';

// Load all upcoming active assignments with required limit context
$assignments = $pdo->query(
    "SELECT ia.assignment_id, ia.duty_role, ia.assignment_status, ia.assigned_at,
            f.faculty_name AS current_faculty, f.faculty_id AS current_faculty_id,
            e.exam_name, e.exam_date, e.start_time, e.end_time, e.required_invigilators,
            c.course_code, r.room_no, er.exam_room_id,
            (SELECT COUNT(*) FROM invigilation_assignment ia2 WHERE ia2.exam_room_id = er.exam_room_id AND ia2.assignment_status = 'assigned') AS room_total_assigned
     FROM invigilation_assignment ia
     JOIN faculty f ON f.faculty_id = ia.faculty_id
     JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
     JOIN exam e ON e.exam_id = er.exam_id
     JOIN course c ON c.course_id = e.course_id
     JOIN room r ON r.room_id = er.room_id
     WHERE ia.assignment_status = 'assigned' AND e.exam_date >= CURDATE()
     ORDER BY e.exam_date, e.start_time, er.exam_room_id"
)->fetchAll();

$allFaculty = $pdo->query(
    'SELECT f.faculty_id, f.faculty_name FROM faculty f
     JOIN user_account u ON u.user_id = f.user_id AND u.status = \'active\'
     ORDER BY f.faculty_name'
)->fetchAll();

$success = $_GET['success'] ?? '';
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

<?php if (!empty($success)): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">check_circle</span>
  <?= $success === '1' ? 'Duty updated successfully.' : htmlspecialchars($success) ?>
</div>
<?php elseif (!empty($error)): ?>
<div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
  <span class="material-symbols-outlined text-[18px]">error</span> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="bg-surface-container-lowest rounded-xl shadow-sm p-5 mb-6 border-l-4 border-primary">
  <p class="font-body-md text-body-md text-on-surface">
    <strong>Duty Reassignment &amp; Over-Assignment Correction:</strong> Manage all active invigilation assignments. You can reassign a duty slot to a replacement faculty member, or remove surplus/mistaken assignments with one click.
  </p>
</div>

<!-- Assignment list with inline reassign & remove -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
  <div class="px-6 py-4 border-b border-surface-container-high flex items-center justify-between">
    <h2 class="font-headline-md text-headline-md text-on-surface">Active Assigned Duties
      <span class="ml-2 px-2 py-0.5 rounded-full bg-primary text-on-primary font-label-sm text-label-sm"><?= count($assignments) ?></span>
    </h2>
  </div>
  <?php if (empty($assignments)): ?>
  <div class="p-8 text-center text-on-surface-variant">No upcoming active assignments found.</div>
  <?php else: ?>
  <table class="w-full table-auto text-sm min-w-[950px]">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Date &amp; Time</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Course / Room</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Assigned Faculty</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Role</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Reassign To</th>
        <th class="px-5 py-3 text-right font-label-sm text-label-sm text-on-surface-variant">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($assignments as $a): ?>
      <?php $isOver = ($a['room_total_assigned'] > $a['required_invigilators']); ?>
      <tr class="hover:bg-surface-container-low transition-colors <?= $isOver ? 'bg-amber-50/50 border-l-4 border-amber-500' : '' ?>">
        <td class="px-5 py-3 font-code text-code">
          <?= htmlspecialchars($a['exam_date']) ?><br>
          <span class="text-xs font-sans text-on-surface-variant"><?= substr($a['start_time'],0,5) ?>–<?= substr($a['end_time'],0,5) ?></span>
        </td>
        <td class="px-5 py-3">
          <div class="flex items-center gap-1.5">
            <span class="font-label-md text-label-md text-primary font-semibold"><?= htmlspecialchars($a['course_code']) ?></span>
            <?php if ($isOver): ?>
            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="This exam room has more assigned invigilators than its required limit">
              Over-assigned (<?= $a['room_total_assigned'] ?>/<?= $a['required_invigilators'] ?>)
            </span>
            <?php endif; ?>
          </div>
          <span class="text-xs text-on-surface-variant">Room <?= htmlspecialchars($a['room_no']) ?> · <?= htmlspecialchars($a['exam_name']) ?></span>
        </td>
        <td class="px-5 py-3 font-label-md text-label-md text-on-surface font-medium">
          <?= htmlspecialchars($a['current_faculty']) ?>
        </td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full font-label-sm text-xs font-semibold <?= $a['duty_role']==='chief' ? 'bg-primary/20 text-primary' : 'bg-surface-container text-on-surface-variant' ?>">
            <?= ucfirst($a['duty_role']) ?>
          </span>
        </td>
        <td class="px-5 py-3">
          <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/reassign_duty.php" class="flex items-center gap-2" id="reassign-form-<?= $a['assignment_id'] ?>">
            <input type="hidden" name="action" value="reassign"/>
            <input type="hidden" name="assignment_id" value="<?= $a['assignment_id'] ?>"/>
            <select name="new_faculty_id" required
                    class="px-3 py-1.5 rounded-lg border border-outline-variant bg-surface text-on-surface text-xs focus:outline-none focus:ring-2 focus:ring-primary-container flex-1 min-w-[180px]">
              <option value="">Select replacement…</option>
              <?php foreach ($allFaculty as $f): ?>
              <?php if ($f['faculty_id'] == $a['current_faculty_id']) continue; ?>
              <option value="<?= $f['faculty_id'] ?>"><?= htmlspecialchars($f['faculty_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-primary text-on-primary font-label-sm text-xs hover:bg-primary-container transition-colors whitespace-nowrap"
                    onclick="return confirm('Reassign duty from <?= htmlspecialchars($a['current_faculty'], ENT_QUOTES) ?> to the selected faculty member?')">
              Reassign
            </button>
          </form>
        </td>
        <td class="px-5 py-3 text-right">
          <form method="POST" action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/actions/reassign_duty.php" class="inline" onsubmit="return confirm('Remove/cancel this invigilation duty assignment for <?= htmlspecialchars($a['current_faculty'], ENT_QUOTES) ?>?')">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="assignment_id" value="<?= $a['assignment_id'] ?>"/>
            <button type="submit"
                    class="p-1.5 rounded-lg text-error hover:bg-error/10 transition-colors inline-flex items-center gap-1 font-label-sm text-xs"
                    title="Remove / Cancel Assignment">
              <span class="material-symbols-outlined text-[18px]">person_remove</span>
              <span class="hidden xl:inline">Remove</span>
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
