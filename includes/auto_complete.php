<?php
/**
 * Auto-complete exams and assignments whose end time has passed.
 */
function autoCompletePastExams($pdo) {
    // 1. Update assignments whose parent exam has passed
    $pdo->query("
        UPDATE invigilation_assignment ia
        JOIN exam_room er ON er.exam_room_id = ia.exam_room_id
        JOIN exam e ON e.exam_id = er.exam_id
        SET ia.assignment_status = 'completed'
        WHERE ia.assignment_status = 'assigned'
          AND (e.exam_date < CURDATE() OR (e.exam_date = CURDATE() AND e.end_time <= CURTIME()))
    ");

    // 2. Update the exams themselves
    $pdo->query("
        UPDATE exam
        SET status = 'completed'
        WHERE status = 'scheduled'
          AND (exam_date < CURDATE() OR (exam_date = CURDATE() AND end_time <= CURTIME()))
    ");
}
