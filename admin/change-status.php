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
$stmt = $pdo->prepare("SELECT s.*, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header('Location: students.php');
    exit;
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE students SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $student_id]);
        $success = "Status changed successfully!";
        
        // Refresh student data
        $stmt = $pdo->prepare("SELECT s.*, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Failed to change status: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Student Status</title>
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
        .student-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .student-info p { margin: 5px 0; }
        .student-info strong { color: #667eea; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 1.1rem; }
        .status-options { display: flex; flex-direction: column; gap: 15px; }
        .status-option { display: flex; align-items: center; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .status-option:hover { border-color: #667eea; background: #f8f9fa; }
        .status-option input[type="radio"] { margin-right: 15px; width: 20px; height: 20px; cursor: pointer; }
        .status-option input[type="radio"]:checked + .status-content { color: #667eea; }
        .status-content { flex: 1; }
        .status-title { font-weight: bold; font-size: 1.1rem; margin-bottom: 5px; }
        .status-description { color: #666; font-size: 0.9rem; }
        .status-pending .status-title { color: #856404; }
        .status-approved .status-title { color: #155724; }
        .status-rejected .status-title { color: #721c24; }
        .btn-submit { width: 100%; padding: 14px; background: #667eea; color: white; border: none; border-radius: 4px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: #5568d3; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-back { display: block; text-align: center; margin-top: 20px; color: #667eea; text-decoration: none; }
        .btn-back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Change Student Status</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">All Students</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="form-container">
        <h2>Change Status</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="student-info">
            <p><strong>Student:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
            <p><strong>Current Status:</strong> <span style="color: #667eea; font-weight: bold;"><?php echo ucfirst($student['status']); ?></span></p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Select New Status:</label>
                <div class="status-options">
                    <label class="status-option status-pending">
                        <input type="radio" name="status" value="pending" <?php echo $student['status'] == 'pending' ? 'checked' : ''; ?>>
                        <div class="status-content">
                            <div class="status-title">⏳ Pending</div>
                            <div class="status-description">Application is under review. Student cannot access hostel yet.</div>
                        </div>
                    </label>
                    
                    <label class="status-option status-approved">
                        <input type="radio" name="status" value="approved" <?php echo $student['status'] == 'approved' ? 'checked' : ''; ?>>
                        <div class="status-content">
                            <div class="status-title">✅ Approved</div>
                            <div class="status-description">Student is approved. Can be allocated a room.</div>
                        </div>
                    </label>
                    
                    <label class="status-option status-rejected">
                        <input type="radio" name="status" value="rejected" <?php echo $student['status'] == 'rejected' ? 'checked' : ''; ?>>
                        <div class="status-content">
                            <div class="status-title">❌ Rejected</div>
                            <div class="status-description">Application rejected. Student cannot access hostel.</div>
                        </div>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Update Status</button>
        </form>
        
        <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn-back">← Back to Profile</a>
    </div>
</body>
</html>