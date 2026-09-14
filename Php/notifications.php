<?php
$required_role = 'any';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$pageTitle  = 'Notifications';
$activePage = 'notifications';

$userId = (int) $_SESSION['user_id'];

// Mark as read if requested
if (isset($_GET['mark_all_read'])) {
    $pdo->prepare('UPDATE notification SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/notifications.php');
    exit();
}

$notifications = $pdo->prepare(
    'SELECT notification_id, title, message, is_read, created_at
     FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
);
$notifications->execute([$userId]);
$notifications = $notifications->fetchAll();

$unread = count(array_filter($notifications, fn($n) => !$n['is_read']));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/topnav.php';
?>

<nav class="flex items-center gap-1 font-label-md text-label-md text-on-surface-variant mb-4">
  <span class="material-symbols-outlined text-[16px]">home</span>
  <span class="text-outline-variant">/</span>
  <span class="font-title-sm text-title-sm text-primary">Notifications</span>
</nav>

<div class="flex items-center justify-between mb-5">
  <h2 class="font-headline-md text-headline-md text-on-surface">
    Notifications
    <?php if ($unread > 0): ?>
    <span class="ml-2 px-2 py-0.5 rounded-full bg-error text-on-error font-label-sm text-label-sm"><?= $unread ?> unread</span>
    <?php endif; ?>
  </h2>
  <?php if ($unread > 0): ?>
  <a href="?mark_all_read=1"
     class="flex items-center gap-1 px-4 py-2.5 rounded-xl bg-surface-container text-on-surface font-label-md text-label-md hover:bg-surface-container-high transition-colors">
    <span class="material-symbols-outlined text-[18px]">done_all</span> Mark All Read
  </a>
  <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
<div class="bg-surface-container-lowest rounded-xl shadow-sm p-10 text-center">
  <span class="material-symbols-outlined text-[48px] text-on-surface-variant mb-3 block">notifications_off</span>
  <p class="font-body-lg text-body-lg text-on-surface-variant">No notifications yet.</p>
</div>
<?php else: ?>
<div class="space-y-2">
  <?php foreach ($notifications as $n): ?>
  <div class="flex items-start gap-4 p-4 rounded-xl <?= $n['is_read'] ? 'bg-surface-container-lowest' : 'bg-primary/5 border border-primary/20' ?> shadow-sm hover:shadow-md transition-shadow">
    <div class="w-9 h-9 rounded-full <?= $n['is_read'] ? 'bg-surface-container' : 'bg-primary' ?> flex items-center justify-center flex-shrink-0 mt-0.5">
      <span class="material-symbols-outlined text-[18px] <?= $n['is_read'] ? 'text-on-surface-variant' : 'text-on-primary' ?>">notifications</span>
    </div>
    <div class="flex-1">
      <div class="flex items-center justify-between gap-2">
        <p class="font-label-md text-label-md text-on-surface <?= !$n['is_read'] ? 'font-bold' : '' ?>">
          <?= htmlspecialchars($n['title']) ?>
          <?php if (!$n['is_read']): ?>
          <span class="ml-2 inline-block w-2 h-2 rounded-full bg-primary align-middle"></span>
          <?php endif; ?>
        </p>
        <span class="font-label-sm text-label-sm text-on-surface-variant whitespace-nowrap"><?= date('d M, g:i a', strtotime($n['created_at'])) ?></span>
      </div>
      <p class="font-body-sm text-body-sm text-on-surface-variant mt-1"><?= htmlspecialchars($n['message']) ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
