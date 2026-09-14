<?php
/**
 * topnav.php – fixed top navigation bar.
 * Requires $pageTitle to already be set.
 */
?>
<header class="fixed top-0 left-72 right-0 h-16 bg-surface/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 flex items-center justify-between px-gutter">
  <div class="flex items-center gap-space-md">
    <span class="font-title-md text-title-md text-on-surface font-semibold tracking-tight">
      <?= htmlspecialchars($pageTitle ?? 'Premier University — Office of the Controller of Examinations') ?>
    </span>
  </div>
  <div class="flex items-center gap-space-lg">
    <div class="relative w-72 hidden md:block">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
      <input class="w-full pl-9 pr-space-md py-1.5 bg-surface-container-lowest text-on-surface rounded-lg font-body-sm text-body-sm placeholder:text-on-surface-variant/70 focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm"
             placeholder="Search faculty, courses, rooms..." type="text"/>
    </div>
    <div class="flex items-center gap-space-md">
      <a href="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/notifications.php"
         class="relative p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors">
        <span class="material-symbols-outlined text-[22px]">notifications</span>
        <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-error rounded-full ring-2 ring-surface"></span>
      </a>
      <?php
        $profileHref = ($_SESSION['role'] ?? '') === 'admin'
          ? '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/admin_profile.php'
          : '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/faculty_profile.php';
      ?>
      <a href="<?= $profileHref ?>" class="flex items-center gap-space-sm pl-space-sm hover:opacity-80 transition-opacity" title="View Profile">
        <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
          <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
        </div>
        <div class="hidden lg:flex flex-col items-start">
          <span class="font-label-md text-label-md text-on-surface font-bold"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
          <span class="font-label-sm text-label-sm text-on-surface-variant capitalize"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
        </div>
      </a>
    </div>
  </div>
</header>
<div class="pl-72">
<main class="w-full pt-16 bg-surface px-gutter min-h-screen py-space-lg">
