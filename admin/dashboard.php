<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'pending'");
$pending_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'approved'");
$approved_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
$available_rooms = $stmt->fetch()['total'];

$stmt = $pdo->query("
    SELECT s.*, u.name, u.email, u.phone 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.status = 'pending'
    ORDER BY s.created_at DESC
");
$pending_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; }
        .navbar { background: #2c3e50; color: white; padding: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .nav-links { display: flex; align-items: center; }
        .nav-links span { color: white; margin-right: 15px; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 16px; border-radius: 4px; }
        .nav-links a:hover { background: #34495e; }
        
        .dashboard { padding: 30px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { font-size: 2rem; color: #667eea; margin-bottom: 10px; }
        .stat-card p { color: #666; font-size: 1rem; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        h2 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; color: #333; }
        table tr:hover { background: #f8f9fa; }
        .btn-action { padding: 6px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; text-decoration: none; display: inline-block; }
        .btn-approve { background: #28a745; color: white; }
        .btn-approve:hover { background: #218838; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-reject:hover { background: #c82333; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Admin Dashboard</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="students.php">All Students</a>
                <a href="add_student.php">Add Student</a>    <!-- NEW LINE ADDED HERE -->
                <a href="rooms.php">Rooms</a>
                <a href="allocations.php">Allocations</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="dashboard">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $pending_count; ?></h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $approved_count; ?></h3>
                    <p>Approved Students</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $available_rooms; ?></h3>
                    <p>Available Rooms</p>
                </div>
            </div>
            
            <div class="table-container">
                <h2>Pending Student Approvals</h2>
                
                <?php if (count($pending_students) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo $student['year']; ?></td>
                                    <td><?php echo ucfirst($student['gender']); ?></td>
                                    <td>
                                        <a href="approve_student.php?id=<?php echo $student['id']; ?>&action=approve" 
                                           class="btn-action btn-approve"
                                           onclick="return confirm('Approve this student?')">Approve</a>
                                        <a href="approve_student.php?id=<?php echo $student['id']; ?>&action=reject" 
                                           class="btn-action btn-reject"
                                           onclick="return confirm('Reject this student?')">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No pending approvals</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
```

