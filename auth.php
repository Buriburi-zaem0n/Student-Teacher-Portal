<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: index.php?error=" . urlencode("All fields are required."));
        exit;
    }

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            header("Location: index.php?error=" . urlencode("Name is required."));
            exit;
        }

        $table = ($role === 'teacher') ? 'admins' : 'users';
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header("Location: index.php?error=" . urlencode("Email is already registered."));
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO $table (name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed_password])) {
            $new_id = $pdo->lastInsertId();
            
            if ($role === 'teacher') {
                $_SESSION['admin_id'] = $new_id;
                header("Location: admin_dashboard.php");
            } else {
                $_SESSION['user_id'] = $new_id;
                header("Location: student_onboarding.php");
            }
            exit;
        } else {
            header("Location: index.php?error=" . urlencode("Registration failed."));
            exit;
        }

    } elseif ($action === 'login') {
        $table = ($role === 'teacher') ? 'admins' : 'users';
        
        $stmt = $pdo->prepare("SELECT id, password FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($role === 'teacher') {
                $_SESSION['admin_id'] = $user['id'];
                header("Location: admin_dashboard.php");
            } else {
                $_SESSION['user_id'] = $user['id'];
                // Check if onboarding is complete (has roll_no)
                $stmt2 = $pdo->prepare("SELECT roll_no FROM users WHERE id = ?");
                $stmt2->execute([$user['id']]);
                $student_data = $stmt2->fetch();
                
                if (empty($student_data['roll_no'])) {
                    header("Location: student_onboarding.php");
                } else {
                    header("Location: student_dashboard.php");
                }
            }
            exit;
        } else {
            header("Location: index.php?error=" . urlencode("Invalid email or password."));
            exit;
        }
    }
}

header("Location: index.php");
exit;
?>
