<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Allocate room
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['allocate'])) {
    $student_id = $_POST['student_id'];
    $room_id = $_POST['room_id'];
    
    try {
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
    } catch(PDOException $e) {
        $error = "Allocation failed";
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
$stmt = $pdo->query("SELECT * FROM rooms WHERE status = 'available' OR occupied < capacity ORDER BY floor, room_number");
$available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all allocations
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Allocations</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Room Allocations</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">Students</a>
                <a href="rooms.php">Rooms</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="dashboard">
        <div class="container">
            <?php if (count($unallocated_students) > 0): ?>
                <div class="form-container" style="max-width: 600px;">
                    <h3>Allocate Room</h3>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Select Student</label>
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
                            <label>Select Room</label>
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
                        
                        <button type="submit" name="allocate" class="btn-submit">Allocate Room</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="table-container" style="margin-top: 30px;">
                <h3>Current Allocations</h3>
                
                <?php if (count($allocations) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Room Number</th>
                                <th>Floor</th>
                                <th>Allocated Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocations as $alloc): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alloc['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($alloc['name']); ?></td>
                                    <td><?php echo htmlspecialchars($alloc['room_number']); ?></td>
                                    <td><?php echo $alloc['floor']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($alloc['allocated_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No allocations yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
