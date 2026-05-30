<?php
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Delete student. The ON DELETE CASCADE in SQL schema will handle attendance records.
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: admin_dashboard.php?msg=" . urlencode("Student deleted successfully."));
    } else {
        header("Location: admin_dashboard.php?error=" . urlencode("Failed to delete student."));
    }
} else {
    header("Location: admin_dashboard.php");
}
exit;
?>
