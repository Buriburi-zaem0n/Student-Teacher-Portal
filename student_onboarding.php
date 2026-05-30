<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current details
$stmt = $pdo->prepare("SELECT name, roll_no, subjects FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If roll_no is already set, they might just be updating, or they shouldn't be here. 
// We'll allow updates just in case, but usually they are redirected to dashboard.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = trim($_POST['roll_no'] ?? '');
    $subjects = trim($_POST['subjects'] ?? '');

    if (empty($roll_no)) {
        $error = "Roll Number is required.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET roll_no = ?, subjects = ? WHERE id = ?");
        if ($stmt->execute([$roll_no, $subjects, $user_id])) {
            header("Location: student_dashboard.php");
            exit;
        } else {
            $error = "Failed to update profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden p-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Complete Your Profile</h2>
            <p class="text-gray-500 mt-2 text-sm">Welcome, <?= htmlspecialchars($user['name']) ?>! Please provide a few more details to set up your dashboard.</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="student_onboarding.php" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Roll Number</label>
                <input type="text" name="roll_no" value="<?= htmlspecialchars($user['roll_no'] ?? '') ?>" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. CS-2023-001">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Subjects (Optional)</label>
                <input type="text" name="subjects" value="<?= htmlspecialchars($user['subjects'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="e.g. Math, Science, English">
                <p class="mt-1 text-xs text-gray-400">Comma separated list</p>
            </div>
            
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-semibold transition-colors">
                Save Profile
            </button>
        </form>
    </div>

</body>
</html>
