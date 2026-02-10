<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Add room
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
    $room_number = trim($_POST['room_number']);
    $floor = $_POST['floor'];
    $capacity = $_POST['capacity'];
    $gender = $_POST['gender'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, floor, capacity, gender) VALUES (?, ?, ?, ?)");
        $stmt->execute([$room_number, $floor, $capacity, $gender]);
        $success = "Room added successfully!";
    } catch(PDOException $e) {
        $error = "Failed to add room";
    }
}

// Get all rooms
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY floor, room_number");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms Management</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Rooms Management</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="students.php">Students</a>
                <a href="allocations.php">Allocations</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="dashboard">
        <div class="container">
            <div class="form-container" style="max-width: 600px;">
                <h3>Add New Room</h3>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" name="room_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Floor</label>
                        <input type="number" name="floor" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" name="capacity" value="2" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_room" class="btn-submit">Add Room</button>
                </form>
            </div>
            
            <div class="table-container" style="margin-top: 30px;">
                <h3>All Rooms</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Floor</th>
                            <th>Capacity</th>
                            <th>Occupied</th>
                            <th>Gender</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td><?php echo $room['floor']; ?></td>
                                <td><?php echo $room['capacity']; ?></td>
                                <td><?php echo $room['occupied']; ?></td>
                                <td><?php echo ucfirst($room['gender']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $room['status']; ?>">
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
