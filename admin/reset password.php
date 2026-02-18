<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_GET['id'] ?? null;
$success = '';
$error = '';

if (!$student_id) {
    header('Location: students.php');
    exit;
}

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, u.name, u.email, u.id as user_id 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header('Location: students.php');
    exit;
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password)) {
        $error = "Please enter a new password";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $student['user_id']]);
            $success = "Password reset successfully! New password: " . $new_password;
        } catch(PDOException $e) {
            $error = "Failed to reset password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Student Password</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; }
        .navbar { background: #2c3e50; color: white; padding: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .nav-links { display: flex; align-items: center; }
        .nav-links span { margin-right: 15px; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 16px; border-radius: 4px; }
        .nav-links a:hover { background: #34495e; }
        .form-container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        h2 { text-align: center; margin-bottom: 10px; color: #333; }
        .warning-box { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .warning-box h3 { color: #856404; margin-bottom: 10px; }
        .warning-box p { color: #856404; line-height: 1.6; }
        .student-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .student-info p { margin: 5px 0; }
        .student-info strong { color: #667eea; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn-submit { width: 100%; padding: 14px; background: #dc3545; color: white; border: none; border-radius: 4px; font-size: 1.1rem; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #c82333; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .password-display { background: #e7f3ff; padding: 15px; border-radius: 4px; margin-top: 15px; border: 2px solid #007bff; }
        .password-display strong { color: #007bff; font-size: 1.2rem; }
        .btn-back { display: block; text-align: center; margin-top: 20px; color: #667eea; text-decoration: none; }
        .btn-back:hover { text-decoration: underline; }
        .required { color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Reset Student Password</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">All Students</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="form-container">
        <h2>🔐 Reset Password</h2>
        
        <div class="warning-box">
            <h3>⚠️ Important</h3>
            <p>You are about to reset the password for this student. Make sure to share the new password with them securely. They can change it after logging in.</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <div class="password-display">
                    <strong>Share this password with the student!</strong>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="student-info">
            <p><strong>Student:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>New Password <span class="required">*</span></label>
                <input type="password" name="new_password" required minlength="6" placeholder="Enter new password (min 6 characters)">
            </div>
            
            <div class="form-group">
                <label>Confirm Password <span class="required">*</span></label>
                <input type="password" name="confirm_password" required placeholder="Re-enter new password">
            </div>
            
            <button type="submit" class="btn-submit" onclick="return confirm('Are you sure you want to reset this student\'s password?')">
                Reset Password
            </button>
        </form>
        
        <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn-back">← Back to Profile</a>
    </div>
</body>
</html>