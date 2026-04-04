<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hostel_manager') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Handle room allocation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['allocate'])) {
    $student_id = $_POST['student_id'];
    $room_id = $_POST['room_id'];
    
    try {
        // Check if student already has an active allocation
        $stmt = $pdo->prepare("SELECT * FROM allocations WHERE student_id = ? AND status = 'active'");
        $stmt->execute([$student_id]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Student already has an active room allocation";
        } else {
            // Check if room is available
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND occupied < capacity");
            $stmt->execute([$room_id]);
            $room = $stmt->fetch();
            
            if ($room) {
                // Create allocation
                $stmt = $pdo->prepare("INSERT INTO allocations (student_id, room_id, allocated_date) VALUES (?, ?, CURDATE())");
                $stmt->execute([$student_id, $room_id]);
                
                // Update room occupied count
                $stmt = $pdo->prepare("UPDATE rooms SET occupied = occupied + 1 WHERE id = ?");
                $stmt->execute([$room_id]);
                
                // Update room status if full
                if ($room['occupied'] + 1 >= $room['capacity']) {
                    $stmt = $pdo->prepare("UPDATE rooms SET status = 'full' WHERE id = ?");
                    $stmt->execute([$room_id]);
                }
                
                $success = "Room allocated successfully!";
            } else {
                $error = "Room is not available";
            }
        }
    } catch(PDOException $e) {
        $error = "Allocation failed: " . $e->getMessage();
    }
}

// Handle deallocation
if (isset($_GET['deallocate'])) {
    $allocation_id = $_GET['deallocate'];
    try {
        // Get allocation details
        $stmt = $pdo->prepare("SELECT room_id FROM allocations WHERE id = ?");
        $stmt->execute([$allocation_id]);
        $allocation = $stmt->fetch();
        
        if ($allocation) {
            // Update allocation status
            $stmt = $pdo->prepare("UPDATE allocations SET status = 'vacated' WHERE id = ?");
            $stmt->execute([$allocation_id]);
            
            // Update room occupied count
            $stmt = $pdo->prepare("UPDATE rooms SET occupied = occupied - 1 WHERE id = ?");
            $stmt->execute([$allocation['room_id']]);
            
            // Update room status to available
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ? AND occupied < capacity");
            $stmt->execute([$allocation['room_id']]);
            
            header('Location: allocations.php?msg=deallocated');
            exit;
        }
    } catch(PDOException $e) {
        $error = "Deallocation failed: " . $e->getMessage();
    }
}

// Get approved students without allocation
$stmt = $pdo->query("
    SELECT s.*, u.name 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN allocations a ON s.id = a.student_id AND a.status = 'active'
    WHERE s.status = 'approved' AND a.id IS NULL
");
$unallocated_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available rooms
$stmt = $pdo->query("SELECT * FROM rooms WHERE (status = 'available' OR occupied < capacity) ORDER BY floor, room_number");
$available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all active allocations
$stmt = $pdo->query("
    SELECT a.*, s.student_id, u.name, r.room_number, r.floor 
    FROM allocations a 
    JOIN students s ON a.student_id = s.id 
    JOIN users u ON s.user_id = u.id 
    JOIN rooms r ON a.room_id = r.id 
    WHERE a.status = 'active'
    ORDER BY a.allocated_date DESC
");
$allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_allocations = count($allocations);
$unallocated_count = count($unallocated_students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Allocations</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .nav-links { display: flex; align-items: center; }
        .nav-links span { margin-right: 15px; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 16px; border-radius: 4px; transition: background 0.3s; }
        .nav-links a:hover { background: rgba(255,255,255,0.2); }
        .content { padding: 30px 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { font-size: 2rem; color: #667eea; margin-bottom: 5px; }
        .stat-card p { color: #666; }
        
        .form-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { margin-bottom: 20px; color: #333; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        .form-group select:focus { outline: none; border-color: #667eea; }
        .btn-submit { padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 4px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: transform 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; color: #333; }
        table tr:hover { background: #f8f9fa; }
        
        .btn-action { padding: 6px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; color: white; }
        .btn-deallocate { background: #dc3545; }
        .btn-deallocate:hover { background: #c82333; }
        
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .required { color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🏠 Room Allocations</h1>
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
    
    <div class="content">
        <div class="container">
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $total_allocations; ?></h3>
                    <p>Active Allocations</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $unallocated_count; ?></h3>
                    <p>Students Waiting</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count($available_rooms); ?></h3>
                    <p>Available Rooms</p>
                </div>
            </div>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deallocated'): ?>
                <div class="alert alert-success">✅ Room deallocated successfully!</div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Allocation Form -->
            <?php if (count($unallocated_students) > 0 && count($available_rooms) > 0): ?>
                <div class="form-container">
                    <h2>➕ Allocate Room to Student</h2>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Student <span class="required">*</span></label>
                                <select name="student_id" required>
                                    <option value="">Choose student...</option>
                                    <?php foreach ($unallocated_students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['name']) . ' (' . $student['student_id'] . ') - ' . ucfirst($student['gender']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Select Room <span class="required">*</span></label>
                                <select name="room_id" required>
                                    <option value="">Choose room...</option>
                                    <?php foreach ($available_rooms as $room): ?>
                                        <option value="<?php echo $room['id']; ?>">
                                            Room <?php echo $room['room_number']; ?> 
                                            (Floor <?php echo $room['floor']; ?>, 
                                            <?php echo ucfirst($room['gender']); ?>, 
                                            <?php echo $room['capacity'] - $room['occupied']; ?> available)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" name="allocate" class="btn-submit">Allocate Room</button>
                    </form>
                </div>
            <?php elseif (count($unallocated_students) == 0): ?>
                <div class="alert alert-info">ℹ️ No approved students waiting for room allocation.</div>
            <?php elseif (count($available_rooms) == 0): ?>
                <div class="alert alert-error">⚠️ No rooms available. Please add more rooms or deallocate existing rooms.</div>
            <?php endif; ?>
            
            <!-- Current Allocations -->
            <div class="table-container">
                <h2>📋 Current Allocations (<?php echo $total_allocations; ?>)</h2>
                
                <?php if (count($allocations) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Room Number</th>
                                <th>Floor</th>
                                <th>Allocated Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocations as $alloc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alloc['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($alloc['name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($alloc['room_number']); ?></strong></td>
                                    <td><?php echo $alloc['floor']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($alloc['allocated_date'])); ?></td>
                                    <td>
                                        <a href="allocations.php?deallocate=<?php echo $alloc['id']; ?>" 
                                           class="btn-action btn-deallocate"
                                           onclick="return confirm('Are you sure you want to deallocate this room?\n\nStudent: <?php echo htmlspecialchars($alloc['name']); ?>\nRoom: <?php echo $alloc['room_number']; ?>')">
                                           🚪 Deallocate
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No active room allocations yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>