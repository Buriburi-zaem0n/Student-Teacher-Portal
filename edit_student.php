<?php
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: admin_dashboard.php");
    exit;
}

// Fetch student data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: admin_dashboard.php?error=" . urlencode("Student not found."));
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $roll_no = trim($_POST['roll_no'] ?? '');
    $subjects = trim($_POST['subjects'] ?? '');

    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        // Check if email belongs to someone else
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $error = "Email is already in use by another student.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, roll_no = ?, subjects = ? WHERE id = ?");
            if ($stmt->execute([$name, $email, $roll_no, $subjects, $id])) {
                header("Location: admin_dashboard.php?msg=" . urlencode("Student updated successfully."));
                exit;
            } else {
                $error = "Failed to update student.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen">

    <nav class="bg-indigo-600 shadow-md px-6 py-4 flex justify-between items-center text-white">
        <h1 class="text-xl font-bold">Teacher Portal</h1>
        <a href="admin_dashboard.php" class="text-sm font-semibold hover:text-indigo-200 transition-colors">Back to Dashboard</a>
    </nav>

    <div class="max-w-xl mx-auto mt-10 px-4">
        <div class="bg-white rounded-2xl shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Student Profile</h2>
            
            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="edit_student.php?id=<?= $id ?>" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Roll Number</label>
                    <input type="text" name="roll_no" value="<?= htmlspecialchars($student['roll_no'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Subjects</label>
                    <input type="text" name="subjects" value="<?= htmlspecialchars($student['subjects'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                </div>

                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-100">
                    <a href="admin_dashboard.php" class="px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 font-semibold transition-colors">Cancel</a>
                    <button type="submit" class="px-6 py-3 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-semibold transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
