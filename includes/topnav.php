<?php
/**
 * topnav.php – Fixed top navigation bar with dynamic search and impersonation banner.
 * Requires $pageTitle to already be set.
 */
$isImpersonating = !empty($_SESSION['is_impersonating']);
$facultyName = $_SESSION['faculty_name'] ?? ($_SESSION['username'] ?? 'Faculty');
$profileHref = ($_SESSION['role'] ?? '') === 'admin'
    ? '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/admin_profile.php'
    : '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_profile.php';
?>

<!-- Impersonation Notice Banner -->
<?php if ($isImpersonating): ?>
<div class="fixed top-0 left-72 right-0 h-10 bg-amber-500 text-slate-950 z-50 px-gutter flex items-center justify-between font-label-sm text-xs font-semibold shadow-md">
  <div class="flex items-center gap-2">
    <span class="material-symbols-outlined text-[18px]">visibility</span>
    <span>Viewing as Faculty Member: <strong><?= htmlspecialchars($facultyName) ?></strong></span>
  </div>
  <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/impersonate.php?action=stop"
     class="px-3 py-1 bg-slate-950 text-white rounded-lg hover:bg-slate-800 transition-colors flex items-center gap-1">
    <span class="material-symbols-outlined text-[14px]">admin_panel_settings</span>
    <span>Exit &amp; Return to Admin</span>
  </a>
</div>
<?php endif; ?>

<header class="fixed <?= $isImpersonating ? 'top-10' : 'top-0' ?> left-72 right-0 h-16 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 flex items-center justify-between px-gutter">
  <div class="flex items-center gap-space-md">
    <span class="font-title-md text-title-md text-on-surface font-semibold tracking-tight">
      <?= htmlspecialchars($pageTitle ?? 'Premier University — Office of the Controller of Examinations') ?>
    </span>
  </div>

  <div class="flex items-center gap-space-lg">
    <!-- Search Bar with Live Suggestions Dropdown -->
    <div class="relative w-80 hidden md:block" id="search-container">
      <form action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/search_results.php" method="GET" id="global-search-form" autocomplete="off">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px] pointer-events-none">search</span>
        <input class="w-full pl-9 pr-8 py-1.5 bg-surface-container-lowest text-on-surface rounded-lg font-body-sm text-body-sm placeholder:text-on-surface-variant/70 focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm border border-outline-variant/50"
               id="global-search-input"
               name="q"
               placeholder="Search faculty, courses, rooms, exams..."
               type="text"
               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"/>
        <button type="submit" aria-label="Submit search" class="hidden"></button>
      </form>

      <!-- Live Search Results Dropdown -->
      <div id="global-search-dropdown"
           class="absolute left-0 right-0 top-full mt-2 bg-surface-container-lowest border border-outline-variant/80 rounded-xl shadow-2xl overflow-hidden z-50 hidden max-h-96 overflow-y-auto">
        <div id="search-results-content" class="py-2">
          <!-- Dynamically populated via JavaScript -->
        </div>
        <div class="px-4 py-2.5 bg-surface-container-high/60 border-t border-outline-variant/60 flex items-center justify-between text-xs text-on-surface-variant">
          <span>Press <kbd class="px-1.5 py-0.5 rounded bg-surface border border-outline-variant font-code text-[11px]">Enter</kbd> to view all</span>
          <span class="text-primary font-medium cursor-pointer" onclick="document.getElementById('global-search-form').submit();">See all results &rarr;</span>
        </div>
      </div>
    </div>

    <!-- Notification & Profile Controls -->
    <div class="flex items-center gap-space-md">
      <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/notifications.php"
         class="relative p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors"
         title="Notifications">
        <span class="material-symbols-outlined text-[22px]">notifications</span>
      </a>

      <a href="<?= $profileHref ?>" class="flex items-center gap-space-sm pl-space-sm hover:opacity-80 transition-opacity" title="View Profile">
        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-on-primary font-bold text-xs">
          <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="hidden lg:flex flex-col items-start">
          <span class="font-label-md text-label-md text-on-surface font-bold"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
          <span class="font-label-sm text-label-sm text-on-surface-variant capitalize">
            <?= $isImpersonating ? 'Faculty (Preview)' : htmlspecialchars($_SESSION['role'] ?? '') ?>
          </span>
        </div>
      </a>
    </div>
  </div>
</header>

<div class="pl-72">
<main class="w-full <?= $isImpersonating ? 'pt-28' : 'pt-16' ?> bg-surface px-gutter min-h-screen py-space-lg">

<!-- Global Search Live Autocomplete Script -->
<script>
(function() {
  const searchInput    = document.getElementById('global-search-input');
  const searchDropdown = document.getElementById('global-search-dropdown');
  const searchResults  = document.getElementById('search-results-content');
  const searchForm     = document.getElementById('global-search-form');
  let debounceTimer    = null;

  if (!searchInput || !searchDropdown) return;

  searchInput.addEventListener('input', function() {
    const val = this.value.trim();
    clearTimeout(debounceTimer);

    if (val.length < 1) {
      searchDropdown.classList.add('hidden');
      return;
    }

    debounceTimer = setTimeout(() => {
      fetch('/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/api_search.php?q=' + encodeURIComponent(val))
        .then(res => res.json())
        .then(data => {
          if (!data.results || data.results.length === 0) {
            searchResults.innerHTML = `
              <div class="px-4 py-4 text-center text-on-surface-variant text-xs">
                <span class="material-symbols-outlined text-[20px] text-slate-400 block mb-1">search_off</span>
                No instant matches found for "<strong>${escapeHtml(val)}</strong>"
              </div>`;
          } else {
            let html = '';
            let currentCat = '';
            data.results.forEach((item, idx) => {
              if (item.category !== currentCat) {
                currentCat = item.category;
                html += `<div class="px-4 py-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-surface-container-low">${escapeHtml(currentCat)}</div>`;
              }
              html += `
                <a href="${item.url}" class="px-4 py-2 hover:bg-surface-container flex items-center gap-3 transition-colors text-left group">
                  <div class="w-7 h-7 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[16px]">${escapeHtml(item.icon || 'circle')}</span>
                  </div>
                  <div class="overflow-hidden">
                    <div class="text-xs font-semibold text-on-surface truncate">${escapeHtml(item.title)}</div>
                    <div class="text-[11px] text-on-surface-variant truncate">${escapeHtml(item.subtitle)}</div>
                  </div>
                </a>`;
            });
            searchResults.innerHTML = html;
          }
          searchDropdown.classList.remove('hidden');
        })
        .catch(() => {
          searchDropdown.classList.add('hidden');
        });
    }, 200);
  });

  // Close dropdown on outside click
  document.addEventListener('click', function(e) {
    if (!document.getElementById('search-container').contains(e.target)) {
      searchDropdown.classList.add('hidden');
    }
  });

  // Re-open on focus if input not empty
  searchInput.addEventListener('focus', function() {
    if (this.value.trim().length >= 1 && searchResults.innerHTML.trim() !== '') {
      searchDropdown.classList.remove('hidden');
    }
  });

  // Handle ESC key to dismiss
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      searchDropdown.classList.add('hidden');
    }
  });

  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})();
</script>
