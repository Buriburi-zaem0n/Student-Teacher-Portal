<?php
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Fetch Admin Data
$stmt = $pdo->prepare("SELECT name FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Fetch All Students
$stmt = $pdo->query("SELECT id, name, roll_no, email, subjects FROM users ORDER BY id DESC");
$students = $stmt->fetchAll();

// Extract all unique subjects
$all_subjects = [];
foreach ($students as $student) {
    if (!empty($student['subjects'])) {
        $subs = array_filter(array_map('trim', explode(',', $student['subjects'])));
        foreach ($subs as $s) {
            $all_subjects[$s] = true;
        }
    }
}
$all_subjects = array_keys($all_subjects);
sort($all_subjects);

// Get Date from query or default to today
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_subject = $_GET['subject'] ?? ($all_subjects[0] ?? '');

// Fetch attendance for the selected date and subject to pre-fill the form if it exists
$stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE attendance_date = ? AND subject = ?");
$stmt->execute([$selected_date, $selected_subject]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Returns [student_id => status]

// Filter students for the Mark Attendance tab
$attendance_students = [];
if ($selected_subject) {
    foreach ($students as $student) {
        $subs = array_filter(array_map('trim', explode(',', $student['subjects'] ?? '')));
        if (in_array($selected_subject, $subs)) {
            $attendance_students[] = $student;
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen">

    <!-- Navbar -->
    <nav class="bg-indigo-600 shadow-md px-6 py-4 flex justify-between items-center text-white">
        <h1 class="text-xl font-bold">Teacher Portal</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm opacity-80">Welcome, <?= htmlspecialchars($admin['name'] ?? 'Admin') ?></span>
            <a href="logout.php" class="text-sm font-semibold hover:text-red-300 transition-colors bg-indigo-700 px-3 py-1 rounded">Logout</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto mt-8 px-4">

        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg text-sm font-medium shadow-sm">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg text-sm font-medium shadow-sm">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex space-x-1 bg-white p-1 rounded-xl shadow-sm mb-6 border border-gray-100">
            <button onclick="switchTab('manage-students', this)" class="tab-btn flex-1 py-2.5 text-sm font-medium rounded-lg transition-colors bg-indigo-50 text-indigo-700">Manage Students</button>
            <button onclick="switchTab('mark-attendance', this)" class="tab-btn flex-1 py-2.5 text-sm font-medium rounded-lg transition-colors text-gray-500 hover:text-gray-700 hover:bg-gray-50">Mark Attendance</button>
        </div>

        <!-- Tab 1: Manage Students -->
        <div id="manage-students" class="tab-content active">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h2 class="text-xl font-semibold text-gray-800">Student Directory</h2>
                    <div class="relative w-64">
                        <input type="text" id="search-input" onkeyup="filterStudents()" placeholder="Search Name or Roll No..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="students-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subjects</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-gray-50 student-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 student-roll">
                                    <?= htmlspecialchars($student['roll_no'] ?: 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 student-name">
                                    <?= htmlspecialchars($student['name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($student['email']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex flex-wrap gap-1">
                                        <?php 
                                        $subs = array_filter(array_map('trim', explode(',', $student['subjects'] ?? '')));
                                        if (empty($subs)) echo '<span class="text-xs text-gray-400">None</span>';
                                        foreach ($subs as $sub): 
                                        ?>
                                            <span class="inline-block bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-xs border border-indigo-100"><?= htmlspecialchars($sub) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="edit_student.php?id=<?= $student['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                    <a href="delete_student.php?id=<?= $student['id'] ?>" onclick="return confirm('Are you sure you want to delete this student?');" class="text-red-600 hover:text-red-900">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2: Mark Attendance -->
        <div id="mark-attendance" class="tab-content">
            <div class="bg-white rounded-xl shadow-md p-6">
                
                <form method="GET" action="admin_dashboard.php" class="flex items-end space-x-4 mb-8 pb-6 border-b border-gray-100 flex-wrap gap-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Subject</label>
                        <select name="subject" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm bg-white">
                            <?php if (empty($all_subjects)): ?>
                                <option value="">No subjects found</option>
                            <?php else: ?>
                                <?php foreach ($all_subjects as $sub): ?>
                                    <option value="<?= htmlspecialchars($sub) ?>" <?= $selected_subject === $sub ? 'selected' : '' ?>><?= htmlspecialchars($sub) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors text-sm">Load Data</button>
                </form>

                <?php if (empty($selected_subject)): ?>
                    <div class="text-center py-10 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                        Please ensure students have subjects assigned to mark attendance.
                    </div>
                <?php elseif (empty($attendance_students)): ?>
                    <div class="text-center py-10 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                        No students are enrolled in "<?= htmlspecialchars($selected_subject) ?>".
                    </div>
                <?php else: ?>
                    <form action="save_attendance.php" method="POST">
                        <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">
                        <input type="hidden" name="subject" value="<?= htmlspecialchars($selected_subject) ?>">
                        
                        <div class="overflow-x-auto mb-6 border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($attendance_students as $student): 
                                        $status = $attendance_records[$student['id']] ?? 'Present'; // Default to Present if not marked yet
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($student['name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($student['roll_no']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <input type="radio" name="attendance[<?= $student['id'] ?>]" value="Present" <?= $status === 'Present' ? 'checked' : '' ?> class="w-5 h-5 text-green-600 focus:ring-green-500 border-gray-300">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <input type="radio" name="attendance[<?= $student['id'] ?>]" value="Absent" <?= $status === 'Absent' ? 'checked' : '' ?> class="w-5 h-5 text-red-600 focus:ring-red-500 border-gray-300">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <input type="radio" name="attendance[<?= $student['id'] ?>]" value="Late" <?= $status === 'Late' ? 'checked' : '' ?> class="w-5 h-5 text-yellow-600 focus:ring-yellow-500 border-gray-300">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                                Save Attendance for <?= htmlspecialchars($selected_subject) ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        // Check URL for active tab
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('date')) {
                // If date is set, likely came from attendance saving or loading, so switch to it
                switchTab('mark-attendance', document.querySelectorAll('.tab-btn')[1]);
            }
        });

        function switchTab(tabId, btn) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            // Reset buttons
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.className = "tab-btn flex-1 py-2.5 text-sm font-medium rounded-lg transition-colors text-gray-500 hover:text-gray-700 hover:bg-gray-50";
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            // Highlight button
            btn.className = "tab-btn flex-1 py-2.5 text-sm font-medium rounded-lg transition-colors bg-indigo-50 text-indigo-700";
        }

        function filterStudents() {
            const input = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('.student-row');

            rows.forEach(row => {
                const name = row.querySelector('.student-name').innerText.toLowerCase();
                const roll = row.querySelector('.student-roll').innerText.toLowerCase();
                if (name.includes(input) || roll.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
