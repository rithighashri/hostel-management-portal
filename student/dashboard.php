<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student information
$stmt = $pdo->prepare("
    SELECT s.*, u.name, u.email, u.phone 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get room allocation if exists
$stmt = $pdo->prepare("
    SELECT r.*, a.allocated_date, a.status as allocation_status
    FROM allocations a 
    JOIN rooms r ON a.room_id = r.id 
    WHERE a.student_id = ? AND a.status = 'active'
");
$stmt->execute([$student['id']]);
$allocation = $stmt->fetch(PDO::FETCH_ASSOC);

// Check for room request
$stmt = $pdo->prepare("SELECT * FROM room_requests WHERE student_id = ? ORDER BY requested_date DESC LIMIT 1");
$stmt->execute([$student['id']]);
$room_request = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f4; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .navbar .container { display: flex; justify-content: space-between; align-items: center; }
        .navbar h1 { font-size: 1.5rem; }
        .nav-links { display: flex; align-items: center; }
        .nav-links span { color: white; margin-right: 20px; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 16px; border-radius: 4px; transition: background 0.3s; }
        .nav-links a:hover { background: rgba(255,255,255,0.2); }
        .content { padding: 30px 0; }
        
        .status-banner { padding: 20px; margin-bottom: 30px; border-radius: 8px; text-align: center; font-size: 1.2rem; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h2 { color: #667eea; margin-bottom: 20px; font-size: 1.3rem; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .info-row { display: flex; margin-bottom: 12px; }
        .info-label { font-weight: bold; color: #666; min-width: 150px; }
        .info-value { color: #333; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .btn-primary { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: bold; transition: transform 0.3s; margin-top: 15px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        
        .request-status { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #667eea; }
        .request-status strong { color: #667eea; display: block; margin-bottom: 8px; }
        .request-status p { color: #666; margin: 5px 0; font-size: 0.95rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🎓 Student Dashboard</h1>
            <div class="nav-links">
    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
    <a href="dashboard.php">Dashboard</a>
    <a href="request_room.php">Request Room</a>  
    <a href="../logout.php">Logout</a>
</div>
        </div>
    </nav>
    
    <div class="content">
        <div class="container">
            <!-- Status Banner -->
            <div class="status-banner status-<?php echo $student['status']; ?>">
                📋 Application Status: <?php echo ucfirst($student['status']); ?>
                <?php if ($student['status'] == 'pending'): ?>
                    - Awaiting Admin Approval
                <?php elseif ($student['status'] == 'approved'): ?>
                    - You can now request a room!
                <?php endif; ?>
            </div>
            
            <div class="grid">
                <!-- Personal Information -->
                <div class="card">
                    <h2>👤 Personal Information</h2>
                    <div class="info-row">
                        <span class="info-label">Student ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['name']); ?></span>
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
                        <span class="info-label">Course:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student['course'] ?? 'Not Available'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Year:</span>
                        <span class="info-value"><?php echo $student['year'] ?? 'Not Available'; ?> Year</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Gender:</span>
                        <span class="info-value"><?php echo ucfirst($student['gender'] ?? 'Not available'); ?></span>
                    </div>
                </div>
                
                <!-- Room Status -->
                <div class="card">
                    <h2>🏠 Room Status</h2>
                    
                    <?php if ($student['status'] == 'pending'): ?>
                        <div class="alert alert-warning">
                            ⏳ <strong>Application Under Review</strong><br>
                            Your registration is being reviewed by the hostel manager. You can request a room after approval.
                        </div>
                    
                    <?php elseif ($student['status'] == 'rejected'): ?>
                        <div class="alert alert-warning">
                            ❌ <strong>Application Rejected</strong><br>
                            Your application was not approved. Please contact the hostel manager for more information.
                        </div>
                    
                    <?php elseif ($allocation): ?>
                        <!-- Room Allocated -->
                        <div class="alert alert-success">
                            ✅ <strong>Room Allocated Successfully!</strong>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Room Number:</span>
                            <span class="info-value"><strong style="color: #667eea; font-size: 1.3rem;"><?php echo htmlspecialchars($allocation['room_number']); ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Floor:</span>
                            <span class="info-value">Floor <?php echo $allocation['floor']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Room Capacity:</span>
                            <span class="info-value"><?php echo $allocation['capacity']; ?> students</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Currently Occupied:</span>
                            <span class="info-value"><?php echo $allocation['occupied']; ?> / <?php echo $allocation['capacity']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Allocated Date:</span>
                            <span class="info-value"><?php echo date('d M Y', strtotime($allocation['allocated_date'])); ?></span>
                        </div>
                    
                    <?php elseif ($room_request && $room_request['status'] == 'pending'): ?>
                        <!-- Pending Room Request -->
                        <div class="alert alert-info">
                            ⏳ <strong>Room Request Submitted</strong><br>
                            Your room request is being reviewed by the hostel manager.
                        </div>
                        <div class="request-status">
                            <strong>Request Details:</strong>
                            <p><strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($room_request['requested_date'])); ?></p>
                            <p><strong>Preferred Floor:</strong> Floor <?php echo $room_request['preferred_floor']; ?></p>
                            <?php if ($room_request['roommate_preference']): ?>
                                <p><strong>Roommate Preference:</strong> <?php echo htmlspecialchars($room_request['roommate_preference']); ?></p>
                            <?php endif; ?>
                            <?php if ($room_request['special_requirements']): ?>
                                <p><strong>Special Requirements:</strong> <?php echo htmlspecialchars($room_request['special_requirements']); ?></p>
                            <?php endif; ?>
                        </div>
                        <p style="color: #666; margin-top: 15px; font-size: 0.95rem;">
                            💡 The hostel manager will review your request and allocate a room based on availability and your preferences.
                        </p>
                    
                    <?php elseif ($room_request && $room_request['status'] == 'rejected'): ?>
                        <!-- Request Rejected -->
                        <div class="alert alert-warning">
                            ❌ <strong>Room Request Rejected</strong><br>
                            <?php if ($room_request['admin_notes']): ?>
                                Reason: <?php echo htmlspecialchars($room_request['admin_notes']); ?>
                            <?php endif; ?>
                        </div>
                        <a href="request_room.php" class="btn-primary">Submit New Request</a>
                    
                    <?php elseif ($room_request && $room_request['status'] == 'approved'): ?>
                        <!-- Request Approved but not allocated yet -->
                        <div class="alert alert-success">
                            ✅ <strong>Room Request Approved!</strong><br>
                            The hostel manager will allocate a room to you shortly.
                        </div>
                    
                    <?php else: ?>
                        <!-- No request submitted - Approved student -->
                        <div class="alert alert-info">
                            🎉 <strong>You're Approved!</strong><br>
                            You can now submit a room request.
                        </div>
                        <p style="color: #666; margin: 15px 0;">
                            Click the button below to submit your room preferences. The hostel manager will review your request and allocate a suitable room.
                        </p>
                        <a href="request_room.php" class="btn-primary">📝 Request Room</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Additional Information -->
            <?php if ($student['address'] || $student['guardian_name']): ?>
            <div class="card" style="margin-top: 20px;">
                <h2>👨‍👩‍👦 Guardian & Contact Information</h2>
                <?php if ($student['address']): ?>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['address']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($student['guardian_name']): ?>
                <div class="info-row">
                    <span class="info-label">Guardian Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['guardian_name']); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($student['guardian_phone']): ?>
                <div class="info-row">
                    <span class="info-label">Guardian Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['guardian_phone']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            
        </div>
    </div>
</body>
</html>