<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Handle room addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $room_number = trim($_POST['room_number']);
    $floor = $_POST['floor'];
    $capacity = $_POST['capacity'];
    $gender = $_POST['gender'];
    
    if (empty($room_number) || empty($floor) || empty($capacity) || empty($gender)) {
        $error = "Please fill all required fields";
    } else {
        try {
            // Check if room number already exists
            $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ?");
            $stmt->execute([$room_number]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Room number already exists";
            } else {
                $stmt = $pdo->prepare("INSERT INTO rooms (room_number, floor, capacity, gender, status) VALUES (?, ?, ?, ?, 'available')");
                $stmt->execute([$room_number, $floor, $capacity, $gender]);
                $success = "Room added successfully!";
            }
        } catch(PDOException $e) {
            $error = "Failed to add room: " . $e->getMessage();
        }
    }
}

// Handle room deletion
if (isset($_GET['delete'])) {
    $room_id = $_GET['delete'];
    try {
        // Check if room has allocations
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM allocations WHERE room_id = ? AND status = 'active'");
        $stmt->execute([$room_id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            $error = "Cannot delete room with active allocations. Please deallocate students first.";
        } else {
            // Delete the room
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            header('Location: rooms.php?msg=deleted');
            exit;
        }
    } catch(PDOException $e) {
        $error = "Failed to delete room: " . $e->getMessage();
    }
}

// Get all rooms
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY floor, room_number");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_rooms = count($rooms);
$available_rooms = count(array_filter($rooms, function($r) { return $r['status'] == 'available'; }));
$full_rooms = count(array_filter($rooms, function($r) { return $r['status'] == 'full'; }));
$total_capacity = array_sum(array_column($rooms, 'capacity'));
$total_occupied = array_sum(array_column($rooms, 'occupied'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms Management</title>
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
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { font-size: 2rem; color: #667eea; margin-bottom: 5px; }
        .stat-card p { color: #666; font-size: 0.9rem; }
        
        .form-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { margin-bottom: 20px; color: #333; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; }
        .btn-submit { padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 4px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: transform 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; color: #333; }
        table tr:hover { background: #f8f9fa; }
        
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .badge-available { background: #d4edda; color: #155724; }
        .badge-full { background: #f8d7da; color: #721c24; }
        .badge-maintenance { background: #fff3cd; color: #856404; }
        
        .btn-action { padding: 6px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; color: white; }
        .btn-edit { background: #007bff; }
        .btn-edit:hover { background: #0056b3; }
        .btn-delete { background: #dc3545; }
        .btn-delete:hover { background: #c82333; }
        
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .required { color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🏠 Rooms Management</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">Students</a>
                <a href="add_student.php">Add Student</a>
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
                    <h3><?php echo $total_rooms; ?></h3>
                    <p>Total Rooms</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $available_rooms; ?></h3>
                    <p>Available</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $full_rooms; ?></h3>
                    <p>Full</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $total_occupied; ?>/<?php echo $total_capacity; ?></h3>
                    <p>Occupancy</p>
                </div>
            </div>
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success">✅ Room deleted successfully!</div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Add Room Form -->
            <div class="form-container">
                <h2>➕ Add New Room</h2>
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Room Number <span class="required">*</span></label>
                            <input type="text" name="room_number" required placeholder="e.g., 101">
                        </div>
                        
                        <div class="form-group">
                            <label>Floor <span class="required">*</span></label>
                            <input type="number" name="floor" required min="1" placeholder="e.g., 1">
                        </div>
                        
                        <div class="form-group">
                            <label>Capacity <span class="required">*</span></label>
                            <input type="number" name="capacity" required min="1" max="4" value="2">
                        </div>
                        
                        <div class="form-group">
                            <label>Gender <span class="required">*</span></label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_room" class="btn-submit">Add Room</button>
                </form>
            </div>
            
            <!-- Rooms List -->
            <div class="table-container">
                <h2>📋 All Rooms (<?php echo $total_rooms; ?>)</h2>
                
                <?php if (count($rooms) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Floor</th>
                                <th>Capacity</th>
                                <th>Occupied</th>
                                <th>Available</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                    <td><?php echo $room['floor']; ?></td>
                                    <td><?php echo $room['capacity']; ?></td>
                                    <td><?php echo $room['occupied']; ?></td>
                                    <td><?php echo $room['capacity'] - $room['occupied']; ?></td>
                                    <td><?php echo ucfirst($room['gender']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $room['status']; ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_room.php?id=<?php echo $room['id']; ?>" 
                                           class="btn-action btn-edit"
                                           title="Edit Room">✏️ Edit</a>
                                        <a href="rooms.php?delete=<?php echo $room['id']; ?>" 
                                           class="btn-action btn-delete"
                                           title="Delete Room"
                                           onclick="return confirm('⚠️ Are you sure you want to delete Room <?php echo $room['room_number']; ?>?\n\nNote: Rooms with active allocations cannot be deleted.')">🗑️ Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        No rooms found. Add your first room using the form above.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>