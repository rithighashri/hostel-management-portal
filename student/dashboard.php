<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

// Get student details
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.phone 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get room allocation if exists
$stmt = $pdo->prepare("
    SELECT r.*, a.allocated_date 
    FROM allocations a 
    JOIN rooms r ON a.room_id = r.id 
    WHERE a.student_id = ? AND a.status = 'active'
");
$stmt->execute([$student['id']]);
$allocation = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Student Dashboard</h1>
            <div class="nav-links">
                <span style="color: white; margin-right: 15px;">
                    Welcome, <?php echo $_SESSION['name']; ?>
                </span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h2>
                    Application Status:
                    <span class="badge badge-<?php echo $student['status']; ?>">
                        <?php echo ucfirst($student['status']); ?>
                    </span>
                </h2>
            </div>
            
            <div class="table-container">
                <h3>Personal Information</h3>
                <table>
                    <tr>
                        <th>Student ID</th>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                    </tr>
                    <tr>
                        <th>Course</th>
                        <td><?php echo htmlspecialchars($student['course']); ?></td>
                    </tr>
                    <tr>
                        <th>Year</th>
                        <td><?php echo $student['year']; ?></td>
                    </tr>
                    <tr>
                        <th>Gender</th>
                        <td><?php echo ucfirst($student['gender']); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($allocation): ?>
                <div class="table-container" style="margin-top: 30px;">
                    <h3>Room Allocation</h3>
                    <table>
                        <tr>
                            <th>Room Number</th>
                            <td><?php echo htmlspecialchars($allocation['room_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Floor</th>
                            <td><?php echo $allocation['floor']; ?></td>
                        </tr>
                        <tr>
                            <th>Allocated Date</th>
                            <td><?php echo date('d M Y', strtotime($allocation['allocated_date'])); ?></td>
                        </tr>
                    </table>
                </div>
            <?php elseif ($student['status'] == 'approved'): ?>
                <div class="alert alert-success" style="margin-top: 20px;">
                    Your application has been approved! Room allocation will be done shortly.
                </div>
            <?php elseif ($student['status'] == 'pending'): ?>
                <div class="alert" style="background-color: #fff3cd; color: #856404; margin-top: 20px;">
                    Your application is under review. Please wait for admin approval.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

