<?php
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date = $_POST['attendance_date'] ?? date('Y-m-d');
    $subject = $_POST['subject'] ?? '';
    $attendance_data = $_POST['attendance'] ?? [];

    if (empty($attendance_date)) {
        header("Location: admin_dashboard.php?error=" . urlencode("Date is required."));
        exit;
    }
    if (empty($subject)) {
        header("Location: admin_dashboard.php?date=" . urlencode($attendance_date) . "&error=" . urlencode("Subject is required."));
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, subject, attendance_date, status) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");

        foreach ($attendance_data as $student_id => $status) {
            // Ensure status is valid
            if (in_array($status, ['Present', 'Absent', 'Late'])) {
                $stmt->execute([$student_id, $subject, $attendance_date, $status]);
            }
        }

        $pdo->commit();
        header("Location: admin_dashboard.php?date=" . urlencode($attendance_date) . "&subject=" . urlencode($subject) . "&msg=" . urlencode("Attendance saved successfully."));
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: admin_dashboard.php?date=" . urlencode($attendance_date) . "&subject=" . urlencode($subject) . "&error=" . urlencode("Failed to save attendance: " . $e->getMessage()));
    }
} else {
    header("Location: admin_dashboard.php");
}
exit;
?>
