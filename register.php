<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $student_id = trim($_POST['student_id']);
    $course = trim($_POST['course']);
    $year = $_POST['year'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $guardian_name = trim($_POST['guardian_name']);
    $guardian_phone = trim($_POST['guardian_phone']);

    // Validation
    if (
        empty($name) || empty($email) || empty($password) ||
        empty($confirm_password) || empty($student_id) ||
        empty($course) || empty($year) || empty($gender)
    ) {
        $error = "Please fill all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $error = "Email already registered";
            } else {

                // Insert into users table
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, phone, role)
                     VALUES (?, ?, ?, ?, 'student')"
                );
                $stmt->execute([$name, $email, $hashed_password, $phone]);

                $user_id = $pdo->lastInsertId();

                // Insert into students table
                $stmt = $pdo->prepare(
                    "INSERT INTO students
                    (user_id, student_id, course, year, gender, address, guardian_name, guardian_phone)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $user_id,
                    $student_id,
                    $course,
                    $year,
                    $gender,
                    $address,
                    $guardian_name,
                    $guardian_phone
                ]);

                $success = "Registration successful! Please wait for admin approval.";
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <h1>Hostel Management System</h1>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </div>
    </div>
</nav>

<div class="form-container">
    <h2 style="text-align:center; margin-bottom:20px;">Student Registration</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password *</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="confirm_password" required>
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input type="tel" name="phone">
        </div>

        <div class="form-group">
            <label>Student ID *</label>
            <input type="text" name="student_id" required>
        </div>

        <div class="form-group">
            <label>Course *</label>
            <input type="text" name="course" required>
        </div>

        <div class="form-group">
            <label>Year *</label>
            <select name="year" required>
                <option value="">Select Year</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
            </select>
        </div>

        <div class="form-group">
            <label>Gender *</label>
            <select name="gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>Guardian Name</label>
            <input type="text" name="guardian_name">
        </div>

        <div class="form-group">
            <label>Guardian Phone</label>
            <input type="tel" name="guardian_phone">
        </div>

        <button type="submit" class="btn-submit">Register</button>

    </form>

    <p style="text-align:center; margin-top:15px;">
        Already registered? <a href="login.php">Login here</a>
    </p>
</div>

</body>
</html>
