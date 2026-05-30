<?php
require_once 'db.php';
if (isset($_SESSION['user_id'])) {
    header("Location: student_dashboard.php");
    exit;
}
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student-Teacher Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hidden-form { display: none; }
        .fade-in { animation: fadeIn 0.3s ease-in-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen flex items-center justify-center p-4">

    <div class="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row min-h-[600px]">
        
        <!-- Sidebar Image / Welcome Area -->
        <div class="md:w-5/12 bg-indigo-600 text-white p-8 flex flex-col justify-center relative overflow-hidden hidden md:flex">
            <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
            <div class="relative z-10">
                <h1 class="text-4xl font-bold mb-4">Welcome Back!</h1>
                <p class="text-indigo-100 mb-8 leading-relaxed">
                    Access your personalized dashboard. Stay on top of your attendance and manage your academic journey with ease.
                </p>
                <div class="mt-8 pt-8 border-t border-indigo-500">
                    <p class="text-sm font-medium">System Role Selection</p>
                    <div class="flex space-x-4 mt-4">
                        <button onclick="switchRole('student')" id="btn-role-student" class="flex-1 py-2 px-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-md transition-colors hover:bg-indigo-50">Student</button>
                        <button onclick="switchRole('teacher')" id="btn-role-teacher" class="flex-1 py-2 px-4 bg-indigo-700 text-white font-semibold rounded-lg transition-colors border border-indigo-500 hover:bg-indigo-800">Teacher</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Area -->
        <div class="md:w-7/12 p-8 sm:p-12 flex flex-col justify-center">
            
            <!-- Mobile Role Selection (Visible only on small screens) -->
            <div class="md:hidden flex space-x-2 mb-8 bg-gray-100 p-1 rounded-lg">
                <button onclick="switchRole('student')" id="mobile-role-student" class="flex-1 py-2 px-4 bg-white text-indigo-600 font-semibold rounded shadow-sm text-sm">Student</button>
                <button onclick="switchRole('teacher')" id="mobile-role-teacher" class="flex-1 py-2 px-4 text-gray-500 font-semibold rounded text-sm">Teacher</button>
            </div>

            <!-- Header -->
            <div class="text-center mb-10">
                <h2 id="form-title" class="text-3xl font-bold text-gray-900">Student Portal</h2>
                <p id="form-subtitle" class="text-gray-500 mt-2">Log in to view your attendance</p>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['success'])): ?>
                    <div class="mt-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Login Form -->
            <form id="login-form" action="auth.php" method="POST" class="space-y-6 fade-in">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="role" id="login-role" value="student">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="you@example.com">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="••••••••">
                </div>
                
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-semibold transition-colors">
                    Sign In
                </button>
                
                <p class="text-center text-sm text-gray-600 mt-4">
                    Don't have an account? <a href="#" onclick="toggleForm('register')" class="text-indigo-600 hover:text-indigo-800 font-semibold">Create one</a>
                </p>
            </form>

            <!-- Registration Form -->
            <form id="register-form" action="auth.php" method="POST" class="space-y-5 hidden-form">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="role" id="register-role" value="student">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="John Doe">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="you@example.com">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="mt-1 block w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors" placeholder="••••••••">
                </div>
                
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-semibold transition-colors">
                    Create Account
                </button>
                
                <p class="text-center text-sm text-gray-600 mt-4">
                    Already have an account? <a href="#" onclick="toggleForm('login')" class="text-indigo-600 hover:text-indigo-800 font-semibold">Sign in</a>
                </p>
            </form>

        </div>
    </div>

    <script>
        let currentRole = 'student';
        let currentForm = 'login';

        function switchRole(role) {
            currentRole = role;
            document.getElementById('login-role').value = role;
            document.getElementById('register-role').value = role;

            // Desktop Buttons
            const btnStudent = document.getElementById('btn-role-student');
            const btnTeacher = document.getElementById('btn-role-teacher');
            
            // Mobile Buttons
            const mobStudent = document.getElementById('mobile-role-student');
            const mobTeacher = document.getElementById('mobile-role-teacher');

            if (role === 'student') {
                btnStudent.className = "flex-1 py-2 px-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-md transition-colors hover:bg-indigo-50";
                btnTeacher.className = "flex-1 py-2 px-4 bg-indigo-700 text-white font-semibold rounded-lg transition-colors border border-indigo-500 hover:bg-indigo-800";
                
                if(mobStudent) {
                    mobStudent.className = "flex-1 py-2 px-4 bg-white text-indigo-600 font-semibold rounded shadow-sm text-sm";
                    mobTeacher.className = "flex-1 py-2 px-4 text-gray-500 font-semibold rounded text-sm";
                }

                document.getElementById('form-title').innerText = currentForm === 'login' ? 'Student Portal' : 'Student Registration';
                document.getElementById('form-subtitle').innerText = currentForm === 'login' ? 'Log in to view your attendance' : 'Create a student account';
            } else {
                btnTeacher.className = "flex-1 py-2 px-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-md transition-colors hover:bg-indigo-50";
                btnStudent.className = "flex-1 py-2 px-4 bg-indigo-700 text-white font-semibold rounded-lg transition-colors border border-indigo-500 hover:bg-indigo-800";
                
                if(mobStudent) {
                    mobTeacher.className = "flex-1 py-2 px-4 bg-white text-indigo-600 font-semibold rounded shadow-sm text-sm";
                    mobStudent.className = "flex-1 py-2 px-4 text-gray-500 font-semibold rounded text-sm";
                }

                document.getElementById('form-title').innerText = currentForm === 'login' ? 'Teacher Portal' : 'Teacher Registration';
                document.getElementById('form-subtitle').innerText = currentForm === 'login' ? 'Log in to manage your students' : 'Create an admin account';
            }
        }

        function toggleForm(formType) {
            currentForm = formType;
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');

            // Remove animation classes
            loginForm.classList.remove('fade-in');
            registerForm.classList.remove('fade-in');

            if (formType === 'register') {
                loginForm.classList.add('hidden-form');
                registerForm.classList.remove('hidden-form');
                registerForm.classList.add('fade-in');
                document.getElementById('form-title').innerText = currentRole === 'student' ? 'Student Registration' : 'Teacher Registration';
                document.getElementById('form-subtitle').innerText = currentRole === 'student' ? 'Create a student account' : 'Create an admin account';
            } else {
                registerForm.classList.add('hidden-form');
                loginForm.classList.remove('hidden-form');
                loginForm.classList.add('fade-in');
                document.getElementById('form-title').innerText = currentRole === 'student' ? 'Student Portal' : 'Teacher Portal';
                document.getElementById('form-subtitle').innerText = currentRole === 'student' ? 'Log in to view your attendance' : 'Log in to manage your students';
            }
        }
    </script>
</body>
</html>
