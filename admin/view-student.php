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

if (!$student_id) {
    header('Location: students.php');
    exit;
}

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, u.name, u.email, u.phone, u.created_at as registered_at
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

// Get room allocation if exists
$stmt = $pdo->prepare("
    SELECT r.*, a.allocated_date 
    FROM allocations a 
    JOIN rooms r ON a.room_id = r.id 
    WHERE a.student_id = ? AND a.status = 'active'
");
$stmt->execute([$student_id]);
$allocation = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Profile</title>
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
        .content { padding: 30px 0; }
        .profile-header { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .profile-header h2 { color: #333; margin-bottom: 10px; }
        .status-badge { padding: 8px 16px; border-radius: 20px; font-weight: bold; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .info-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .info-card h3 { color: #667eea; margin-bottom: 20px; font-size: 1.2rem; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .info-row { display: flex; margin-bottom: 15px; }
        .info-label { font-weight: bold; color: #666; min-width: 150px; }
        .info-value { color: #333; }
        .action-buttons { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .btn { padding: 12px 24px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .no-allocation { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Student Profile</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">All Students</a>
                <a href="add_student.php">Add Student</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="content">
        <div class="container">
            <div class="profile-header">
                <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                <span class="status-badge status-<?php echo $student['status']; ?>">
                    <?php echo ucfirst($student['status']); ?>
                </span>
            </div>
            
            <div class="info-grid">
                <div class="info-card">
                    <h3>👤 Personal Information</h3>
                    <div class="info-row">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['phone'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Gender:</span>
                        <span class="info-value"><?php echo ucfirst($student['gender']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['address'] ?: 'Not provided'); ?></span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3>📚 Academic Information</h3>
                    <div class="info-row">
                        <span class="info-label">Course:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['course']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Year:</span>
                        <span class="info-value"><?php echo $student['year']; ?><?php 
                            $suffix = ['1' => 'st', '2' => 'nd', '3' => 'rd', '4' => 'th'];
                            echo $suffix[$student['year']] ?? 'th';
                        ?> Year</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Registration Date:</span>
                        <span class="info-value"><?php echo date('d M Y', strtotime($student['registered_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value status-badge status-<?php echo $student['status']; ?>">
                            <?php echo ucfirst($student['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-card">
                    <h3>👨‍👩‍👦 Guardian Information</h3>
                    <div class="info-row">
                        <span class="info-label">Guardian Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['guardian_name'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Guardian Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['guardian_phone'] ?: 'Not provided'); ?></span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3>🏠 Room Allocation</h3>
                    <?php if ($allocation): ?>
                        <div class="info-row">
                            <span class="info-label">Room Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($allocation['room_number']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Floor:</span>
                            <span class="info-value"><?php echo $allocation['floor']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Allocated Date:</span>
                            <span class="info-value"><?php echo date('d M Y', strtotime($allocation['allocated_date'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Capacity:</span>
                            <span class="info-value"><?php echo $allocation['capacity']; ?> students</span>
                        </div>
                    <?php else: ?>
                        <p class="no-allocation">No room allocated yet</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-primary">✏️ Edit Profile</a>
                <a href="change_status.php?id=<?php echo $student['id']; ?>" class="btn btn-warning">🔄 Change Status</a>
                <a href="reset_password.php?id=<?php echo $student['id']; ?>" class="btn btn-danger">🔐 Reset Password</a>
                
                <?php if ($student['status'] == 'approved' && !$allocation): ?>
                    <a href="allocations.php?student_id=<?php echo $student['id']; ?>" class="btn btn-success">🏠 Allocate Room</a>
                <?php endif; ?>
                
                <a href="students.php" class="btn btn-secondary">← Back to Students</a>
            </div>
        </div>
    </div>
</body>
</html>