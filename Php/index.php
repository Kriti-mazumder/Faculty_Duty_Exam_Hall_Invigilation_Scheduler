<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'faculty') {
        header('Location: faculty_dashboard.php');
    } else {
        header('Location: admin_dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit();

