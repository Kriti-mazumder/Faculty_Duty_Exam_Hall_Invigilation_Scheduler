<?php
$userRole = $_SESSION['role'] ?? '';
$activePage = $activePage ?? '';

$base = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/';

$adminLinks = [
    ['path' => 'admin-dashboard',        'href' => $base.'admin_dashboard.php',           'icon' => 'dashboard',           'label' => 'Admin Dashboard'],
    ['path' => 'department-management',  'href' => $base.'department_management.php',      'icon' => 'corporate_fare',      'label' => 'Department Management'],
    ['path' => 'course-management',      'href' => $base.'course_management.php',          'icon' => 'menu_book',           'label' => 'Course Management'],
    ['path' => 'faculty-management',     'href' => $base.'faculty_management.php',         'icon' => 'badge',               'label' => 'Faculty Management'],
    ['path' => 'faculty-availability',   'href' => $base.'faculty_availability.php',       'icon' => 'event_available',     'label' => 'Faculty Availability'],
    ['path' => 'exam-management',        'href' => $base.'exam_management.php',            'icon' => 'assignment',          'label' => 'Exam Management'],
    ['path' => 'exam-timetable',         'href' => $base.'exam_timetable.php',             'icon' => 'calendar_month',      'label' => 'Exam Timetable & Rooms'],
    ['path' => 'room-management',        'href' => $base.'room_management.php',            'icon' => 'meeting_room',        'label' => 'Room Management'],
    ['path' => 'invigilation-assignments','href' => $base.'exam_room_management.php',      'icon' => 'how_to_reg',          'label' => 'Invigilation Assignments'],
    ['path' => 'faculty-workload',       'href' => $base.'faculty_workload.php',           'icon' => 'query_stats',         'label' => 'Faculty Workload'],
    ['path' => 'duty-reassignment',      'href' => $base.'manual_duty_reassignment.php',   'icon' => 'published_with_changes','label' => 'Duty Reassignment'],
    ['path' => 'reports-and-documents',  'href' => $base.'reports_dashboard.php',          'icon' => 'description',         'label' => 'Reports & Documents'],
    ['path' => 'notifications',          'href' => $base.'notifications.php',              'icon' => 'notifications',       'label' => 'Notifications'],
    ['path' => 'admin-profile',          'href' => $base.'admin_profile.php',              'icon' => 'manage_accounts',     'label' => 'Admin Profile & Security'],
];

$facultyLinks = [
    ['path' => 'faculty-dashboard',      'href' => $base.'faculty_dashboard.php',          'icon' => 'space_dashboard',     'label' => 'Faculty Dashboard'],
    ['path' => 'my-assigned-duties',     'href' => $base.'my_duties.php',                  'icon' => 'checklist',           'label' => 'My Assigned Duties'],
    ['path' => 'faculty-availability',   'href' => $base.'faculty_availability.php',       'icon' => 'event_available',     'label' => 'Faculty Availability'],
    ['path' => 'duty-preferences',       'href' => $base.'duty_preference_management.php',  'icon' => 'tune',                'label' => 'Duty Preferences'],
    ['path' => 'notifications',          'href' => $base.'notifications.php',              'icon' => 'notifications',       'label' => 'Notifications'],
];

if (!function_exists('sidebarLink')) {
    function sidebarLink(array $link, string $activePage): string {
        $isActive = ($link['path'] === $activePage);
        $cls = $isActive
            ? 'flex items-center gap-space-sm px-space-sm py-2 rounded-lg bg-primary-container text-on-primary font-title-sm shadow-sm transition-colors'
            : 'flex items-center gap-space-sm px-space-sm py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors';
        $aria = $isActive ? ' aria-current="page"' : '';
        return sprintf(
            '<a class="%s" data-path="%s" href="%s"%s>'
            . '<span class="material-symbols-outlined text-[18px]">%s</span>'
            . '<span class="font-body-md text-body-md">%s</span>'
            . '</a>',
            $cls, htmlspecialchars($link['path']), htmlspecialchars($link['href']),
            $aria, htmlspecialchars($link['icon']), htmlspecialchars($link['label'])
        );
    }
}

?>
<aside class="fixed left-0 top-0 h-full w-72 bg-[#0f172a] text-white z-50 flex flex-col justify-between shadow-[0_1px_8px_rgba(0,0,0,0.12)] overflow-y-auto">
  <div class="flex flex-col">
    <div class="h-16 px-space-md flex items-center gap-space-sm bg-[#09101d] flex-shrink-0">
      <div class="w-9 h-9 rounded bg-primary-container flex items-center justify-center text-white shadow-sm flex-shrink-0">
        <span class="material-symbols-outlined text-[20px]">school</span>
      </div>
      <div class="flex flex-col overflow-hidden">
        <span class="font-title-sm text-title-sm text-white tracking-wide truncate">Premier University</span>
        <span class="font-label-sm text-label-sm text-slate-400 uppercase tracking-wider truncate">Exam Control Division</span>
      </div>
    </div>
    <div class="px-space-md py-space-sm">
      <nav class="flex flex-col gap-1">
        <?php if ($userRole === 'admin'): ?>
          <div class="pt-space-sm pb-space-xs">
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-slate-400 font-bold px-space-sm">Admin &amp; Controller Tools</span>
          </div>
          <?php foreach ($adminLinks as $link): echo sidebarLink($link, $activePage); endforeach; ?>
        <?php else: ?>
          <div class="pt-space-sm pb-space-xs">
            <span class="font-label-sm text-label-sm uppercase tracking-wider text-slate-400 font-bold px-space-sm">Faculty Portal</span>
          </div>
          <?php foreach ($facultyLinks as $link): echo sidebarLink($link, $activePage); endforeach; ?>
        <?php endif; ?>
      </nav>
    </div>
  </div>
  <div class="p-space-md bg-[#09101d]">
    <div class="flex items-center gap-space-xs text-slate-400 font-body-sm text-body-sm">
      <span class="material-symbols-outlined text-[16px] text-emerald-400">verified_user</span>
      <span>Session Secured — <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></span>
    </div>
    <p class="font-label-sm text-label-sm text-slate-500 mt-1">Academic Year 2024-25</p>
    <?php if ($userRole === 'faculty'): ?>
    <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_profile.php"
       class="mt-2 flex items-center gap-1 text-slate-400 hover:text-blue-400 font-label-sm text-label-sm transition-colors">
      <span class="material-symbols-outlined text-[15px]">manage_accounts</span> My Profile
    </a>
    <?php endif; ?>
    <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/logout.php"
       class="mt-2 flex items-center gap-1 text-slate-400 hover:text-red-400 font-label-sm text-label-sm transition-colors">
      <span class="material-symbols-outlined text-[15px]">logout</span> Sign out
    </a>
  </div>
</aside>
