<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    $target = ($_SESSION['role'] ?? '') === 'faculty' ? 'faculty_dashboard.php' : 'admin_dashboard.php';
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/' . $target);
    exit();
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login – Premier University Invigilation Scheduler</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config={theme:{extend:{colors:{"primary":"#004ac6","primary-container":"#2563eb","on-primary":"#ffffff","surface":"#f8f9ff","surface-container-lowest":"#ffffff","surface-container":"#e5eeff","on-surface":"#0b1c30","on-surface-variant":"#434655","error":"#ba1a1a","outline":"#737686","outline-variant":"#c3c6d7"},fontFamily:{sans:["Inter","sans-serif"]}}}};
  </script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .glass {
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#0f172a] via-[#1e3a5f] to-[#004ac6]">
  <div class="glass rounded-2xl shadow-2xl w-full max-w-md p-10 border border-white/20">
    <!-- Logo -->
    <div class="flex flex-col items-center mb-8">
      <div class="w-14 h-14 rounded-2xl bg-primary flex items-center justify-center mb-3 shadow-lg">
        <span class="material-symbols-outlined text-white text-[32px]">school</span>
      </div>
      <h1 class="text-2xl font-bold text-[#0b1c30] tracking-tight">Premier University</h1>
      <p class="text-sm text-[#434655] mt-1">Exam Hall Invigilation Scheduler</p>
    </div>

    <!-- Error Banner -->
    <?php if ($error): ?>
    <div class="mb-4 flex items-center gap-2 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
      <span class="material-symbols-outlined text-[18px]">error</span>
      <span>Invalid username or password. Please try again.</span>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form action="/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login_process.php" method="POST" class="space-y-5">
      <div>
        <label class="block text-sm font-semibold text-[#0b1c30] mb-1.5" for="username">Username</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#737686] text-[20px]">person</span>
          <input id="username" name="username" type="text" required autocomplete="username"
                 class="w-full pl-10 pr-4 py-3 rounded-xl border border-[#c3c6d7] bg-white/80 text-[#0b1c30] text-sm focus:outline-none focus:ring-2 focus:ring-[#004ac6] focus:border-transparent transition placeholder:text-[#737686]"
                 placeholder="Enter your username"/>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold text-[#0b1c30] mb-1.5" for="password">Password</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#737686] text-[20px]">lock</span>
          <input id="password" name="password" type="password" required autocomplete="current-password"
                 class="w-full pl-10 pr-4 py-3 rounded-xl border border-[#c3c6d7] bg-white/80 text-[#0b1c30] text-sm focus:outline-none focus:ring-2 focus:ring-[#004ac6] focus:border-transparent transition placeholder:text-[#737686]"
                 placeholder="Enter your password"/>
        </div>
      </div>
      <button type="submit"
              class="w-full py-3 rounded-xl bg-[#004ac6] hover:bg-[#003ea8] text-white font-semibold text-sm tracking-wide shadow-lg hover:shadow-xl transition-all duration-200">
        Sign In
      </button>
    </form>

    <p class="text-center text-xs text-[#737686] mt-6">
      Academic Year 2024-25 &nbsp;·&nbsp; Office of the Controller of Examinations
    </p>
  </div>
</body>
</html>
