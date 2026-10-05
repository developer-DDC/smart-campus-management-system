<?php
session_start();
require 'config/db.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch()) {
            $error = "This email already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertUser = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
            $insertUser->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role
            ]);

            $userId = $pdo->lastInsertId();

            if ($role === 'student') {
                $studentId = 'STU' . str_pad($userId, 5, '0', STR_PAD_LEFT);
                $pdo->prepare("INSERT INTO students (user_id, student_id, department, year) VALUES (:user_id, :student_id, :department, :year)")->execute([
                    'user_id' => $userId,
                    'student_id' => $studentId,
                    'department' => $_POST['department'] ?? 'General',
                    'year' => $_POST['year'] ?? '1'
                ]);
            } elseif ($role === 'staff') {
                $staffId = 'STF' . str_pad($userId, 5, '0', STR_PAD_LEFT);
                $pdo->prepare("INSERT INTO staff (user_id, staff_id, department, position) VALUES (:user_id, :staff_id, :department, :position)")->execute([
                    'user_id' => $userId,
                    'staff_id' => $staffId,
                    'department' => $_POST['department'] ?? 'General',
                    'position' => $_POST['position'] ?? 'Lecturer'
                ]);
            }

            $success = "Registration successful. Please login.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-box">
            <h2>Create Account</h2>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="Computer Science">
                </div>

                <div class="form-group">
                    <label>Year / Position</label>
                    <input type="text" name="year" placeholder="Example: 1st Year or Lecturer">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary">Register</button>
            </form>

            <p class="bottom-link">
                Already have an account?
                <a href="index.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
