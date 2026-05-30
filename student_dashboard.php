<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT name, roll_no, email, subjects FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$current_subjects = array_filter(array_map('trim', explode(',', $user['subjects'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_subject') {
        $new_subject = trim($_POST['new_subject'] ?? '');
        if (!empty($new_subject) && !in_array($new_subject, $current_subjects)) {
            $current_subjects[] = $new_subject;
            $updated_subjects = implode(', ', $current_subjects);
            $stmt = $pdo->prepare("UPDATE users SET subjects = ? WHERE id = ?");
            $stmt->execute([$updated_subjects, $user_id]);
            header("Location: student_dashboard.php?msg=" . urlencode("Subject added."));
            exit;
        }
    } elseif ($action === 'remove_subject') {
        $remove_subject = trim($_POST['remove_subject'] ?? '');
        if (($key = array_search($remove_subject, $current_subjects)) !== false) {
            unset($current_subjects[$key]);
            $updated_subjects = implode(', ', $current_subjects);
            $stmt = $pdo->prepare("UPDATE users SET subjects = ? WHERE id = ?");
            $stmt->execute([$updated_subjects, $user_id]);
            header("Location: student_dashboard.php?msg=" . urlencode("Subject removed."));
            exit;
        }
    }
}

// Ensure onboarding is done
if (empty($user['roll_no'])) {
    header("Location: student_onboarding.php");
    exit;
}

// Fetch Attendance Stats per subject
$stmt = $pdo->prepare("SELECT subject, COUNT(*) as total_days, 
                              SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days 
                       FROM attendance WHERE student_id = ? GROUP BY subject");
$stmt->execute([$user_id]);
$subject_stats_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$subject_stats = [];
foreach ($subject_stats_raw as $stat) {
    $subject_stats[$stat['subject']] = $stat;
}

// Fetch Absent/Late Dates
$stmt = $pdo->prepare("SELECT subject, attendance_date, status FROM attendance WHERE student_id = ? AND status IN ('Absent', 'Late') ORDER BY attendance_date DESC");
$stmt->execute([$user_id]);
$missing_days = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">

    <!-- Navbar -->
    <nav class="bg-white shadow-sm px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-indigo-600">Student Portal</h1>
        <a href="logout.php" class="text-sm font-semibold text-gray-600 hover:text-red-600 transition-colors">Logout</a>
    </nav>

    <?php if (isset($_GET['msg'])): ?>
        <div class="max-w-5xl mx-auto mt-4 px-4">
            <div class="p-4 bg-green-100 text-green-700 rounded-lg text-sm font-medium shadow-sm">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-5xl mx-auto mt-10 px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Profile Card -->
        <div class="col-span-1">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center border-t-4 border-indigo-600">
                <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4 text-indigo-600 text-3xl font-bold">
                    <?= htmlspecialchars(strtoupper(substr($user['name'], 0, 1))) ?>
                </div>
                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="text-gray-500 mt-1"><?= htmlspecialchars($user['email']) ?></p>
                
                <div class="mt-6 border-t pt-4 text-left">
                    <p class="text-sm text-gray-500 font-medium">Roll Number</p>
                    <p class="text-gray-900 font-semibold mb-3"><?= htmlspecialchars($user['roll_no']) ?></p>
                    
                    <div class="flex justify-between items-center mb-2 mt-4">
                        <p class="text-sm text-gray-500 font-medium">Subjects</p>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php if (empty($current_subjects)): ?>
                            <p class="text-xs text-red-500 w-full text-left">Please add your subjects.</p>
                        <?php else: ?>
                            <?php foreach ($current_subjects as $sub): ?>
                                <div class="flex items-center bg-indigo-50 text-indigo-700 pl-3 pr-1 py-1 rounded-full text-sm font-medium border border-indigo-100">
                                    <?= htmlspecialchars($sub) ?>
                                    <form method="POST" class="ml-2 inline-flex items-center m-0">
                                        <input type="hidden" name="action" value="remove_subject">
                                        <input type="hidden" name="remove_subject" value="<?= htmlspecialchars($sub) ?>">
                                        <button type="submit" class="text-indigo-400 hover:text-red-500 focus:outline-none flex items-center justify-center w-5 h-5 rounded-full hover:bg-red-50 transition-colors" title="Remove">
                                            &times;
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" class="flex items-center space-x-2 mt-3 pt-3 border-t border-gray-100">
                        <input type="hidden" name="action" value="add_subject">
                        <input type="text" name="new_subject" class="flex-1 px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="New subject (e.g. Math)" required>
                        <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg shadow-sm hover:bg-indigo-700 transition-colors whitespace-nowrap">Add</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Attendance Stats -->
        <div class="col-span-1 md:col-span-2 space-y-8">
            
            <!-- Percentage Cards per Subject -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php if (empty($current_subjects)): ?>
                    <div class="col-span-full bg-white rounded-2xl shadow-md p-8 text-center text-gray-500">
                        Add subjects to track your attendance.
                    </div>
                <?php else: ?>
                    <?php foreach ($current_subjects as $subject): 
                        $total = $subject_stats[$subject]['total_days'] ?? 0;
                        $present = $subject_stats[$subject]['present_days'] ?? 0;
                        $pct = $total > 0 ? round(($present / $total) * 100) : 0;
                        
                        $subject_missing_days = array_filter($missing_days, function($day) use ($subject) {
                            return $day['subject'] === $subject;
                        });
                    ?>
                        <div class="bg-white rounded-2xl shadow-md p-6 flex flex-col">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide"><?= htmlspecialchars($subject) ?></h3>
                                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= $pct ?>%</p>
                                    <p class="text-xs text-gray-400 mt-1"><?= $present ?> / <?= $total ?> days present</p>
                                </div>
                                <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center font-bold text-sm <?= $pct >= 75 ? 'border-green-500 text-green-500' : ($pct >= 50 ? 'border-yellow-500 text-yellow-500' : 'border-red-500 text-red-500') ?>">
                                    <?= $pct ?>%
                                </div>
                            </div>
                            
                            <?php if (!empty($subject_missing_days)): ?>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <p class="text-xs font-semibold text-gray-500 mb-2">Missed Classes:</p>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($subject_missing_days as $missed_day): ?>
                                            <span class="px-2 py-1 text-[11px] font-medium rounded-md border <?= $missed_day['status'] === 'Absent' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-yellow-50 text-yellow-600 border-yellow-100' ?>">
                                                <?= date('M j', strtotime($missed_day['attendance_date'])) ?> (<?= htmlspecialchars($missed_day['status']) ?>)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Absent/Late History -->
            <div class="bg-white rounded-2xl shadow-md p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Attendance Alerts</h3>
                
                <?php if (count($missing_days) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($missing_days as $day): ?>
                            <div class="flex items-center justify-between p-4 rounded-lg <?= $day['status'] === 'Absent' ? 'bg-red-50 border border-red-100' : 'bg-yellow-50 border border-yellow-100' ?>">
                                <div class="flex items-center space-x-3">
                                    <span class="w-2 h-2 rounded-full <?= $day['status'] === 'Absent' ? 'bg-red-500' : 'bg-yellow-500' ?>"></span>
                                    <div>
                                        <span class="font-medium text-gray-900 block"><?= htmlspecialchars($day['subject']) ?></span>
                                        <span class="text-xs text-gray-500"><?= date('F j, Y', strtotime($day['attendance_date'])) ?></span>
                                    </div>
                                </div>
                                <span class="px-3 py-1 text-xs font-bold rounded-full <?= $day['status'] === 'Absent' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                    <?= htmlspecialchars($day['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-10">
                        <p class="text-gray-500">You have perfect attendance so far! Keep it up.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

</body>
</html>
