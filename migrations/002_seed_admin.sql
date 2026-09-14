-- ============================================================
-- 002_seed_admin.sql
-- Run once in phpMyAdmin or via mysql CLI.
-- Creates default admin account (password: Admin@1234)
-- and sample data so the dashboard shows real numbers.
-- ============================================================

USE invigilation_scheduler;

-- ---------------------------------------------------------------
-- 1. Default admin user  (password: Admin@1234)
--    Hash generated with: password_hash('Admin@1234', PASSWORD_BCRYPT)
-- ---------------------------------------------------------------
INSERT INTO user_account (username, password_hash, role, status)
VALUES (
  'admin',
  '$2y$10$uR6yJiN5DnlWIEHpedlwk.D8uGedRSDriCgqO/dGviDfX0ll6B43a',
  'admin',
  'active'
) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), status = 'active';

-- ---------------------------------------------------------------
-- 2. Sample departments
-- ---------------------------------------------------------------
INSERT IGNORE INTO department (department_name) VALUES
  ('Computer Science & Engineering'),
  ('Electrical & Electronic Engineering'),
  ('Business Administration'),
  ('Mathematics');

-- ---------------------------------------------------------------
-- 3. Sample rooms
-- ---------------------------------------------------------------
INSERT INTO room (room_no, building, capacity, room_type, status) VALUES
  ('101', 'Academic Block A', 60, 'Exam Hall', 'active'),
  ('102', 'Academic Block A', 60, 'Exam Hall', 'active'),
  ('201', 'Academic Block B', 80, 'Exam Hall', 'active'),
  ('202', 'Academic Block B', 40, 'Lab',       'active'),
  ('301', 'Academic Block C', 100,'Auditorium', 'active')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- ---------------------------------------------------------------
-- 4. Sample faculty user accounts (password: Faculty@1234)
-- ---------------------------------------------------------------
INSERT INTO user_account (username, password_hash, role, status) VALUES
  ('dr.rahman',   '$2y$10$ITFa7c8bciF3LzFVsnwXQukCn97Qsut5z7qqPKwPBHF5dq27IsKQ.', 'faculty', 'active'),
  ('prof.akter',  '$2y$10$ITFa7c8bciF3LzFVsnwXQukCn97Qsut5z7qqPKwPBHF5dq27IsKQ.', 'faculty', 'active'),
  ('dr.hassan',   '$2y$10$ITFa7c8bciF3LzFVsnwXQukCn97Qsut5z7qqPKwPBHF5dq27IsKQ.', 'faculty', 'active'),
  ('dr.chowdhury','$2y$10$ITFa7c8bciF3LzFVsnwXQukCn97Qsut5z7qqPKwPBHF5dq27IsKQ.', 'faculty', 'active')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), status = 'active';

-- ---------------------------------------------------------------
-- 5. Faculty records (linked to user accounts above)
-- ---------------------------------------------------------------
INSERT INTO faculty (user_id, department_id, faculty_name, email, phone, designation)
SELECT u.user_id, d.department_id, f.name, f.email, f.phone, f.designation
FROM (
  SELECT 'dr.rahman'   AS uname, 'Computer Science & Engineering'      AS dname, 'Dr. M. Rahman'      AS name, 'rahman@premier.edu'     AS email, '01711-000001' AS phone, 'Associate Professor' AS designation UNION ALL
  SELECT 'prof.akter',           'Electrical & Electronic Engineering', 'Prof. S. Akter',     'akter@premier.edu',    '01711-000002', 'Professor' UNION ALL
  SELECT 'dr.hassan',            'Business Administration',             'Dr. K. Hassan',      'hassan@premier.edu',   '01711-000003', 'Lecturer' UNION ALL
  SELECT 'dr.chowdhury',         'Mathematics',                        'Dr. N. Chowdhury',   'chowdhury@premier.edu','01711-000004', 'Senior Lecturer'
) AS f
JOIN user_account u ON u.username = f.uname
JOIN department   d ON d.department_name = f.dname;

-- ---------------------------------------------------------------
-- 6. Sample courses
-- ---------------------------------------------------------------
INSERT INTO course (department_id, course_code, course_title)
SELECT d.department_id, c.code, c.title
FROM (
  SELECT 'Computer Science & Engineering'      AS dname, 'CSE-101' AS code, 'Introduction to Programming'   AS title UNION ALL
  SELECT 'Computer Science & Engineering',              'CSE-301',          'Database Systems' UNION ALL
  SELECT 'Electrical & Electronic Engineering',         'EEE-201',          'Circuit Analysis' UNION ALL
  SELECT 'Business Administration',                     'BBA-101',          'Principles of Management' UNION ALL
  SELECT 'Mathematics',                                 'MTH-201',          'Calculus II'
) AS c
JOIN department d ON d.department_name = c.dname;

-- ---------------------------------------------------------------
-- 7. Sample exams (upcoming)
-- ---------------------------------------------------------------
INSERT INTO exam (course_id, exam_name, exam_date, start_time, end_time, required_invigilators, student_count, status)
SELECT c.course_id, e.name, e.edate, e.stime, e.etime, e.req, e.students, 'scheduled'
FROM (
  SELECT 'CSE-301' AS code, 'Midterm Exam'  AS name, CURDATE() AS edate, '09:00:00' AS stime, '11:00:00' AS etime, 2 AS req, 55 AS students UNION ALL
  SELECT 'EEE-201',         'Midterm Exam',           DATE_ADD(CURDATE(), INTERVAL 1 DAY),  '14:00:00', '16:00:00', 2, 48 UNION ALL
  SELECT 'MTH-201',         'Final Exam',             DATE_ADD(CURDATE(), INTERVAL 3 DAY),  '09:00:00', '12:00:00', 3, 70 UNION ALL
  SELECT 'CSE-101',         'Final Exam',             DATE_ADD(CURDATE(), INTERVAL 5 DAY),  '10:00:00', '12:00:00', 2, 60 UNION ALL
  SELECT 'BBA-101',         'Final Exam',             DATE_ADD(CURDATE(), INTERVAL 7 DAY),  '13:00:00', '15:00:00', 2, 45
) AS e
JOIN course c ON c.course_code = e.code;

-- ---------------------------------------------------------------
-- 8. Assign rooms to today's exam
-- ---------------------------------------------------------------
INSERT INTO exam_room (exam_id, room_id, allocated_students)
SELECT e.exam_id, r.room_id, 55
FROM exam e
JOIN room r ON r.room_no = '101'
WHERE e.exam_date = CURDATE()
LIMIT 1;

-- ---------------------------------------------------------------
-- 9. Assign an invigilator to today's exam room
-- ---------------------------------------------------------------
INSERT INTO invigilation_assignment (exam_room_id, faculty_id, duty_role, assignment_status)
SELECT er.exam_room_id, f.faculty_id, 'chief', 'assigned'
FROM exam_room er
JOIN exam e  ON e.exam_id = er.exam_id AND e.exam_date = CURDATE()
JOIN faculty f ON f.email = 'rahman@premier.edu'
LIMIT 1;
