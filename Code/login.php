<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../Php/login_process.php';
} else {
    header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php');
    exit();
}
