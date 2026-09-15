<?php
/**
 * search_results.php – Comprehensive Search Results Page.
 */
$required_role = 'any';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$query = trim($_GET['q'] ?? '');
$pageTitle  = 'Search Results' . ($query !== '' ? ': "' . htmlspecialchars($query) . '"' : '');
$activePage = 'search';

$userRole = $_SESSION['role'] ?? 'faculty';
$base = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/';

$facultyList = [];
$coursesList = [];
$roomsList   = [];
$examsList   = [];

if ($query !== '') {
    $like = '%' . $query . '%';

    // Faculty
    $stmt = $pdo->prepare('
        SELECT f.faculty_id, f.faculty_name, f.designation, f.email, f.phone, d.department_name, u.status
        FROM faculty f
        LEFT JOIN department d ON d.department_id = f.department_id
        LEFT JOIN user_account u ON u.user_id = f.user_id
        WHERE f.faculty_name LIKE ? OR f.email LIKE ? OR f.designation LIKE ? OR d.department_name LIKE ?
        ORDER BY f.faculty_name ASC
    ');
    $stmt->execute([$like, $like, $like, $like]);
    $facultyList = $stmt->fetchAll();

    // Courses
    $stmt = $pdo->prepare('
        SELECT c.course_id, c.course_code, c.course_title, d.department_name
        FROM course c
        LEFT JOIN department d ON d.department_id = c.department_id
        WHERE c.course_code LIKE ? OR c.course_title LIKE ? OR d.department_name LIKE ?
        ORDER BY c.course_code ASC
    ');
    $stmt->execute([$like, $like, $like]);
    $coursesList = $stmt->fetchAll();

    // Rooms
    $stmt = $pdo->prepare('
        SELECT r.room_id, r.room_no, r.building, r.room_type, r.capacity, r.status
        FROM room r
        WHERE r.room_no LIKE ? OR r.building LIKE ? OR r.room_type LIKE ?
        ORDER BY r.room_no ASC
    ');
    $stmt->execute([$like, $like, $like]);
    $roomsList = $stmt->fetchAll();

    // Exams
    $stmt = $pdo->prepare('
        SELECT e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time, e.status,
               c.course_code, c.course_title
        FROM exam e
        JOIN course c ON c.course_id = e.course_id
        WHERE e.exam_name LIKE ? OR c.course_code LIKE ? OR c.course_title LIKE ?
        ORDER BY e.exam_date DESC
    ');
    $stmt->execute([$like, $like, $like]);
    $examsList = $stmt->fetchAll();
}

$totalMatches = count($facultyList) + count($coursesList) + count($roomsList) + count($examsList);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<!-- Breadcrumb -->
<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Search Results</span>
</nav>

<div class="mb-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="font-headline-md text-headline-md text-on-surface font-bold">
        Search Results for <span class="text-primary">"<?= htmlspecialchars($query) ?>"</span>
      </h1>
      <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">
        Found <?= $totalMatches ?> matching record<?= $totalMatches === 1 ? '' : 's' ?> across institutional database.
      </p>
    </div>
  </div>
</div>

<?php if ($query === ''): ?>
  <div class="p-8 bg-surface-container-lowest rounded-2xl border border-outline-variant text-center max-w-lg mx-auto shadow-sm">
    <span class="material-symbols-outlined text-outline text-[48px] mb-3">search</span>
    <h3 class="font-title-md text-title-md text-on-surface font-semibold mb-1">Enter a Search Query</h3>
    <p class="text-sm text-on-surface-variant">Use the search bar at the top or type keywords like faculty names, course codes, or room numbers.</p>
  </div>
<?php elseif ($totalMatches === 0): ?>
  <div class="p-8 bg-surface-container-lowest rounded-2xl border border-outline-variant text-center max-w-lg mx-auto shadow-sm">
    <span class="material-symbols-outlined text-outline text-[48px] mb-3">search_off</span>
    <h3 class="font-title-md text-title-md text-on-surface font-semibold mb-1">No Results Found</h3>
    <p class="text-sm text-on-surface-variant mb-4">No matching records found for "<?= htmlspecialchars($query) ?>". Try different keywords or check spelling.</p>
    <a href="<?= ($userRole === 'admin') ? $base.'admin_dashboard.php' : $base.'faculty_dashboard.php' ?>"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-medium">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span> Return to Dashboard
    </a>
  </div>
<?php else: ?>

  <div class="space-y-6">

    <!-- 1. Faculty Section -->
    <?php if (!empty($facultyList)): ?>
    <section class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/60 overflow-hidden">
      <div class="px-6 py-4 bg-surface-container-high/60 border-b border-outline-variant/60 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-primary text-[20px]">badge</span>
          <h2 class="font-title-md text-title-md text-on-surface font-semibold">Faculty Members</h2>
          <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label-sm text-xs font-bold"><?= count($facultyList) ?></span>
        </div>
        <?php if ($userRole === 'admin'): ?>
        <a href="<?= $base ?>faculty_management.php" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
          Open Faculty Management <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
        <?php endif; ?>
      </div>
      <div class="divide-y divide-surface-container-high/60">
        <?php foreach ($facultyList as $f): ?>
        <div class="p-4 sm:px-6 hover:bg-surface-container-low/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center font-bold flex-shrink-0">
              <?= strtoupper(substr($f['faculty_name'], 0, 1)) ?>
            </div>
            <div>
              <h3 class="font-title-sm text-on-surface font-semibold"><?= htmlspecialchars($f['faculty_name']) ?></h3>
              <p class="text-xs text-on-surface-variant">
                <?= htmlspecialchars($f['designation'] ?? 'Faculty') ?> • <span class="font-medium"><?= htmlspecialchars($f['department_name'] ?? 'Department') ?></span>
              </p>
              <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                <span>📧 <?= htmlspecialchars($f['email']) ?></span>
                <?php if (!empty($f['phone'])): ?>
                <span>📞 <?= htmlspecialchars($f['phone']) ?></span>
                <?php endif; ?>
              </p>
            </div>
          </div>
          <?php if ($userRole === 'admin'): ?>
          <div class="flex items-center gap-2">
            <a href="<?= $base ?>impersonate.php?action=start&faculty_id=<?= $f['faculty_id'] ?>"
               class="px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors text-xs font-semibold flex items-center gap-1">
              <span class="material-symbols-outlined text-[16px]">visibility</span> View Perspective
            </a>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- 2. Courses Section -->
    <?php if (!empty($coursesList)): ?>
    <section class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/60 overflow-hidden">
      <div class="px-6 py-4 bg-surface-container-high/60 border-b border-outline-variant/60 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-indigo-600 text-[20px]">menu_book</span>
          <h2 class="font-title-md text-title-md text-on-surface font-semibold">Academic Courses</h2>
          <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-label-sm text-xs font-bold"><?= count($coursesList) ?></span>
        </div>
        <?php if ($userRole === 'admin'): ?>
        <a href="<?= $base ?>course_management.php" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
          Open Course Management <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
        <?php endif; ?>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 sm:p-6">
        <?php foreach ($coursesList as $c): ?>
        <div class="p-4 rounded-xl border border-outline-variant/70 bg-surface-container-lowest flex items-start justify-between hover:border-primary transition-colors">
          <div>
            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-code text-xs font-bold">
              <?= htmlspecialchars($c['course_code']) ?>
            </span>
            <h3 class="font-title-sm text-on-surface font-semibold mt-1.5"><?= htmlspecialchars($c['course_title']) ?></h3>
            <p class="text-xs text-on-surface-variant mt-0.5"><?= htmlspecialchars($c['department_name']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- 3. Rooms Section -->
    <?php if (!empty($roomsList)): ?>
    <section class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/60 overflow-hidden">
      <div class="px-6 py-4 bg-surface-container-high/60 border-b border-outline-variant/60 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-amber-600 text-[20px]">meeting_room</span>
          <h2 class="font-title-md text-title-md text-on-surface font-semibold">Rooms &amp; Examination Halls</h2>
          <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-label-sm text-xs font-bold"><?= count($roomsList) ?></span>
        </div>
        <?php if ($userRole === 'admin'): ?>
        <a href="<?= $base ?>room_management.php" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
          Open Room Management <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
        <?php endif; ?>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4 sm:p-6">
        <?php foreach ($roomsList as $r): ?>
        <div class="p-4 rounded-xl border border-outline-variant/70 bg-surface-container-lowest flex items-center justify-between hover:border-amber-500 transition-colors">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
              <span class="material-symbols-outlined text-[20px]">door_front</span>
            </div>
            <div>
              <h3 class="font-title-sm text-on-surface font-bold">Room <?= htmlspecialchars($r['room_no']) ?></h3>
              <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($r['building']) ?> • <?= ucfirst($r['room_type']) ?></p>
            </div>
          </div>
          <div class="text-right">
            <span class="font-label-md text-sm font-bold text-on-surface"><?= $r['capacity'] ?></span>
            <p class="text-[10px] text-on-surface-variant uppercase">seats</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- 4. Exams Section -->
    <?php if (!empty($examsList)): ?>
    <section class="bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant/60 overflow-hidden">
      <div class="px-6 py-4 bg-surface-container-high/60 border-b border-outline-variant/60 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined text-emerald-600 text-[20px]">calendar_month</span>
          <h2 class="font-title-md text-title-md text-on-surface font-semibold">Exams &amp; Schedules</h2>
          <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-label-sm text-xs font-bold"><?= count($examsList) ?></span>
        </div>
        <?php if ($userRole === 'admin'): ?>
        <a href="<?= $base ?>exam_timetable.php" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
          Open Exam Timetable <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
        <?php else: ?>
        <a href="<?= $base ?>my_duties.php" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
          View My Duties <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
        </a>
        <?php endif; ?>
      </div>
      <div class="divide-y divide-surface-container-high/60">
        <?php foreach ($examsList as $e): ?>
        <div class="p-4 sm:px-6 hover:bg-surface-container-low/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-code text-xs font-bold">
                <?= htmlspecialchars($e['course_code']) ?>
              </span>
              <h3 class="font-title-sm text-on-surface font-semibold"><?= htmlspecialchars($e['exam_name']) ?></h3>
            </div>
            <p class="text-xs text-on-surface-variant mt-1">
              Course: <?= htmlspecialchars($e['course_title']) ?>
            </p>
          </div>
          <div class="text-left sm:text-right">
            <span class="text-xs font-semibold text-on-surface">📅 <?= date('D, M d, Y', strtotime($e['exam_date'])) ?></span>
            <p class="text-xs text-on-surface-variant">⏰ <?= substr($e['start_time'], 0, 5) ?> – <?= substr($e['end_time'], 0, 5) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
