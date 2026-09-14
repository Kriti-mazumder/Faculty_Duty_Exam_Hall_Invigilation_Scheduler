<?php
session_start();
session_unset();
session_destroy();
header('Location: /Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/login.php');
exit();
