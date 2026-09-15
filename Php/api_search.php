<?php
/**
 * api_search.php – Secure AJAX API for global live search.
 * Returns JSON formatted search results across Faculty, Courses, Rooms, and Exams.
 */
header('Content-Type: application/json; charset=utf-8');

$required_role = 'any';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$pdo = db();
$query = trim($_GET['q'] ?? '');
$userRole = $_SESSION['role'] ?? 'faculty';

if (mb_strlen($query) < 1) {
    echo json_encode(['success' => true, 'query' => $query, 'total' => 0, 'results' => []]);
    exit();
}

$like = '%' . $query . '%';
$results = [];
$base = '/Faculty_Duty_Exam_Hall_Invigilation_Scheduler/Php/';

// 1. Faculty Search
try {
    $stmt = $pdo->prepare('
        SELECT f.faculty_id, f.faculty_name, f.designation, f.email, d.department_name
        FROM faculty f
        LEFT JOIN department d ON d.department_id = f.department_id
        WHERE f.faculty_name LIKE ? OR f.email LIKE ? OR f.designation LIKE ? OR d.department_name LIKE ?
        ORDER BY f.faculty_name ASC
        LIMIT 5
    ');
    $stmt->execute([$like, $like, $like, $like]);
    $facultyMatches = $stmt->fetchAll();

    foreach ($facultyMatches as $row) {
        $results[] = [
            'category' => 'Faculty',
            'icon'     => 'badge',
            'title'    => $row['faculty_name'],
            'subtitle' => ($row['designation'] ? $row['designation'] . ' • ' : '') . ($row['department_name'] ?? 'Faculty'),
            'url'      => ($userRole === 'admin')
                ? $base . 'faculty_management.php'
                : $base . 'faculty_workload.php'
        ];
    }
} catch (Exception $e) {}

// 2. Course Search
try {
    $stmt = $pdo->prepare('
        SELECT c.course_id, c.course_code, c.course_title, d.department_name
        FROM course c
        LEFT JOIN department d ON d.department_id = c.department_id
        WHERE c.course_code LIKE ? OR c.course_title LIKE ? OR d.department_name LIKE ?
        ORDER BY c.course_code ASC
        LIMIT 5
    ');
    $stmt->execute([$like, $like, $like]);
    $courseMatches = $stmt->fetchAll();

    foreach ($courseMatches as $row) {
        $results[] = [
            'category' => 'Courses',
            'icon'     => 'menu_book',
            'title'    => $row['course_code'] . ' – ' . $row['course_title'],
            'subtitle' => $row['department_name'] ?? 'Course Catalog',
            'url'      => ($userRole === 'admin')
                ? $base . 'course_management.php'
                : $base . 'faculty_dashboard.php'
        ];
    }
} catch (Exception $e) {}

// 3. Room Search
try {
    $stmt = $pdo->prepare('
        SELECT r.room_id, r.room_no, r.building, r.room_type, r.capacity
        FROM room r
        WHERE r.room_no LIKE ? OR r.building LIKE ? OR r.room_type LIKE ?
        ORDER BY r.room_no ASC
        LIMIT 5
    ');
    $stmt->execute([$like, $like, $like]);
    $roomMatches = $stmt->fetchAll();

    foreach ($roomMatches as $row) {
        $results[] = [
            'category' => 'Rooms & Halls',
            'icon'     => 'meeting_room',
            'title'    => 'Room ' . $row['room_no'] . ' (' . $row['building'] . ')',
            'subtitle' => ucfirst($row['room_type'] ?? 'Hall') . ' • Capacity: ' . $row['capacity'] . ' seats',
            'url'      => ($userRole === 'admin')
                ? $base . 'room_management.php'
                : $base . 'faculty_dashboard.php'
        ];
    }
} catch (Exception $e) {}

// 4. Exam Search
try {
    $stmt = $pdo->prepare('
        SELECT e.exam_id, e.exam_name, e.exam_date, e.start_time, e.end_time, c.course_code, c.course_title
        FROM exam e
        JOIN course c ON c.course_id = e.course_id
        WHERE e.exam_name LIKE ? OR c.course_code LIKE ? OR c.course_title LIKE ?
        ORDER BY e.exam_date DESC
        LIMIT 5
    ');
    $stmt->execute([$like, $like, $like]);
    $examMatches = $stmt->fetchAll();

    foreach ($examMatches as $row) {
        $results[] = [
            'category' => 'Exams',
            'icon'     => 'calendar_month',
            'title'    => $row['exam_name'] . ' (' . $row['course_code'] . ')',
            'subtitle' => date('M d, Y', strtotime($row['exam_date'])) . ' • ' . substr($row['start_time'], 0, 5) . '-' . substr($row['end_time'], 0, 5),
            'url'      => ($userRole === 'admin')
                ? $base . 'exam_timetable.php'
                : $base . 'my_duties.php'
        ];
    }
} catch (Exception $e) {}

echo json_encode([
    'success' => true,
    'query'   => $query,
    'total'   => count($results),
    'results' => $results
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
