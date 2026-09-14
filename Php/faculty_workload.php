<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Faculty Workload Report';
$activePage = 'faculty-workload';

$workload = $pdo->query(
    "SELECT f.faculty_id, f.faculty_name, f.designation,
            d.department_name,
            COUNT(ia.assignment_id)                                           AS total_duties,
            SUM(ia.assignment_status = 'assigned')                            AS upcoming_duties,
            SUM(ia.assignment_status = 'completed')                           AS completed_duties,
            SUM(ia.assignment_status = 'cancelled')                           AS cancelled_duties,
            GROUP_CONCAT(DISTINCT e.exam_date ORDER BY e.exam_date SEPARATOR ', ') AS exam_dates
     FROM faculty f
     JOIN department d ON d.department_id = f.department_id
     LEFT JOIN invigilation_assignment ia ON ia.faculty_id = f.faculty_id
     LEFT JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
     LEFT JOIN exam e ON e.exam_id = er.exam_id
     GROUP BY f.faculty_id
     ORDER BY total_duties DESC, f.faculty_name"
)->fetchAll();

$maxDuties = empty($workload) ? 1 : max(array_column($workload, 'total_duties'));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Faculty Workload</span>
</nav>

<!-- Summary stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <?php
  $totalFac    = count($workload);
  $totalDuties = array_sum(array_column($workload, 'total_duties'));
  $highLoad    = count(array_filter($workload, fn($f) => $f['total_duties'] > 5));
  $noLoad      = count(array_filter($workload, fn($f) => $f['total_duties'] == 0));
  $stats = [
    ['icon'=>'badge',       'label'=>'Total Faculty',     'val'=>$totalFac,    'cls'=>'bg-primary text-on-primary'],
    ['icon'=>'how_to_reg',  'label'=>'Total Duties',      'val'=>$totalDuties, 'cls'=>'bg-tertiary text-on-tertiary'],
    ['icon'=>'warning',     'label'=>'High Workload (>5)','val'=>$highLoad,    'cls'=>'bg-error text-on-error'],
    ['icon'=>'person_off',  'label'=>'Unassigned',        'val'=>$noLoad,      'cls'=>'bg-secondary text-on-secondary'],
  ];
  foreach ($stats as $s): ?>
  <div class="bg-surface-container-lowest rounded-xl shadow-sm p-4 flex items-center gap-3">
    <div class="w-10 h-10 rounded-lg <?= $s['cls'] ?> flex items-center justify-center flex-shrink-0">
      <span class="material-symbols-outlined text-[20px]"><?= $s['icon'] ?></span>
    </div>
    <div>
      <p class="font-label-sm text-label-sm text-on-surface-variant"><?= $s['label'] ?></p>
      <p class="font-headline-sm text-headline-sm text-on-surface"><?= $s['val'] ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Workload table with bar chart -->
<section class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
  <div class="px-6 py-4 border-b border-surface-container-high">
    <h2 class="font-headline-md text-headline-md text-on-surface">Faculty Duty Distribution</h2>
  </div>
  <table class="w-full table-auto text-sm">
    <thead class="bg-surface-container-high">
      <tr>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Faculty</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant">Department</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Assigned</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Done</th>
        <th class="px-5 py-3 text-center font-label-sm text-label-sm text-on-surface-variant">Total</th>
        <th class="px-5 py-3 text-left font-label-sm text-label-sm text-on-surface-variant w-48">Load</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-surface-container-high">
      <?php foreach ($workload as $fw): ?>
      <?php $pct = $maxDuties ? round($fw['total_duties'] / $maxDuties * 100) : 0;
            $barCls = $fw['total_duties'] > 5 ? 'bg-error' : ($fw['total_duties'] > 2 ? 'bg-primary' : 'bg-green-500');
      ?>
      <tr class="hover:bg-surface-container-low transition-colors">
        <td class="px-5 py-3">
          <span class="font-label-md text-label-md"><?= htmlspecialchars($fw['faculty_name']) ?></span>
          <br><span class="text-xs text-on-surface-variant"><?= htmlspecialchars($fw['designation'] ?? '') ?></span>
        </td>
        <td class="px-5 py-3 text-on-surface-variant text-xs"><?= htmlspecialchars($fw['department_name']) ?></td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-label-sm"><?= $fw['upcoming_duties'] ?></span>
        </td>
        <td class="px-5 py-3 text-center">
          <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-label-sm text-label-sm"><?= $fw['completed_duties'] ?></span>
        </td>
        <td class="px-5 py-3 text-center font-label-md text-label-md"><?= $fw['total_duties'] ?></td>
        <td class="px-5 py-3">
          <div class="w-full bg-surface-container-high rounded-full h-2">
            <div class="<?= $barCls ?> h-2 rounded-full transition-all duration-500" style="width:<?= $pct ?>%"></div>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
