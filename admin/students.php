<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hostel_manager') {
    header('Location: ../login.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $student_id = $_GET['delete'];
    try {
        // Delete allocations first (foreign key)
        $stmt = $pdo->prepare("DELETE FROM allocations WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        // Get user_id
        $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $user_id = $stmt->fetch()['user_id'];
        
        // Delete student
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        header('Location: students.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "Delete failed: " . $e->getMessage();
    }
}

$stmt = $pdo->query("
    SELECT s.*, u.name, u.email 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; }
        .navbar { background: #2c3e50; color: white; padding: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .nav-links { display: flex; align-items: center; }
        .nav-links span { margin-right: 15px; color: white; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 16px; border-radius: 4px; }
        .nav-links a:hover { background: #34495e; }
        .dashboard { padding: 30px 0; }
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; margin-top: 20px; }
        h3 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; color: #333; }
        table tr:hover { background: #f8f9fa; }
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .btn-action { padding: 6px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; color: white; }
        .btn-view { background: #17a2b8; }
        .btn-view:hover { background: #138496; }
        .btn-edit { background: #007bff; }
        .btn-edit:hover { background: #0056b3; }
        .btn-delete { background: #dc3545; }
        .btn-delete:hover { background: #c82333; }
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>All Students</h1>
            <div class="nav-links">
    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
    <a href="dashboard.php">Dashboard</a>
    <a href="students.php">Students</a>
   
    <a href="rooms.php">Rooms</a>
    <a href="allocations.php">Allocations</a>
    <a href="../logout.php">Logout</a>
</div>
        </div>
    </nav>
    
    <div class="dashboard">
        <div class="container">
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert-success">✅ Student deleted successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <h3>📚 Student List (<?php echo count($students); ?> students)</h3>
                
                <?php if (count($students) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']??'not available'); ?></td>
                                    <td><?php echo $student['year']; ?></td>
                                    <td><?php echo ucfirst($student['gender']??'not available'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $student['status']; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td>
    <a href="students.php?delete=<?php echo $student['id']; ?>" 
       class="btn-action btn-delete"
       title="Delete Student"
       onclick="return confirm('⚠️ Are you sure you want to delete this student?\n\nThis will also delete:\n- Their user account\n- Room allocations\n\nThis action cannot be undone!')">🗑️ Delete</a>
</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No students found. <a href="add_student.php">Add a student</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>