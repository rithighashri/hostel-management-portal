<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: ../login.php');
    exit;
}

// Get student info
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student profile not found");
}

// Check if student is approved
if ($student['status'] != 'approved') {
    $error = "Your application must be approved before requesting a room.";
}

// Check if student has gender set
if (empty($student['gender'])) {
    $error = "Your profile is incomplete. Gender information is required to request a room.";
}

// Check if student already has a room
$stmt = $pdo->prepare("SELECT * FROM allocations WHERE student_id = ? AND status = 'active'");
$stmt->execute([$student['id']]);
$existing_allocation = $stmt->fetch();

if ($existing_allocation) {
    $error = "You already have a room allocated.";
}

// Check if student has pending request
$stmt = $pdo->prepare("SELECT * FROM room_requests WHERE student_id = ? AND status = 'pending'");
$stmt->execute([$student['id']]);
$pending_request = $stmt->fetch();

$success = '';
$error_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$existing_allocation && !$pending_request && $student['status'] == 'approved' && !empty($student['gender'])) {
    $preferred_floor = $_POST['preferred_floor'];
    $roommate_preference = trim($_POST['roommate_preference']);
    $special_requirements = trim($_POST['special_requirements']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO room_requests 
                              (student_id, preferred_floor, roommate_preference, special_requirements, status) 
                              VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$student['id'], $preferred_floor, $roommate_preference, $special_requirements]);
        
        $success = "Room request submitted successfully! The hostel manager will review your request.";
        
        // Refresh to show pending request
        header("refresh:2;url=request_room.php");
        
    } catch(PDOException $e) {
        $error_msg = "Failed to submit request: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Room</title>
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
        
        .info-card { background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #2196f3; }
        .info-card h3 { color: #1976d2; margin-bottom: 10px; }
        .info-card p { color: #424242; line-height: 1.6; }
        
        .form-container { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 30px; }
        h2 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .form-group small { display: block; margin-top: 5px; color: #666; font-size: 0.9rem; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 4px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: transform 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        
        .request-status { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .status-badge { display: inline-block; padding: 10px 20px; border-radius: 20px; font-weight: bold; font-size: 1.2rem; margin: 20px 0; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .request-details { text-align: left; margin-top: 20px; }
        .request-details p { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .request-details strong { color: #667eea; }
        
        .required { color: #dc3545; }
        
        .error-field { border-color: #dc3545 !important; background: #f8d7da !important; }
        .error-message { color: #dc3545; display: block; margin-top: 5px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>🏠 Request Room</h1>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="content">
        <div class="container">
            
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-error">❌ <?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <?php if ($existing_allocation): ?>
                <!-- Student already has a room -->
                <div class="request-status">
                    <h2>✅ Room Already Allocated</h2>
                    <p style="color: #155724; font-size: 1.2rem; margin: 20px 0;">
                        You have been allocated a room. Check your dashboard for details.
                    </p>
                    <a href="dashboard.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px;">
                        View Dashboard
                    </a>
                </div>
                
            <?php elseif ($pending_request): ?>
                <!-- Pending request exists -->
                <div class="request-status">
                    <h2>⏳ Room Request Pending</h2>
                    <span class="status-badge status-pending">Pending Review</span>
                    
                    <div class="request-details">
                        <p><strong>Request Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($pending_request['requested_date'])); ?></p>
                        <p><strong>Preferred Floor:</strong> Floor <?php echo $pending_request['preferred_floor']; ?></p>
                        <?php if ($pending_request['roommate_preference']): ?>
                            <p><strong>Roommate Preference:</strong> <?php echo htmlspecialchars($pending_request['roommate_preference']); ?></p>
                        <?php endif; ?>
                        <?php if ($pending_request['special_requirements']): ?>
                            <p><strong>Special Requirements:</strong> <?php echo htmlspecialchars($pending_request['special_requirements']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <p style="margin-top: 20px; color: #666;">
                        Your request is being reviewed by the hostel manager. You will be notified once a decision is made.
                    </p>
                    
                    <a href="dashboard.php" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px;">
                        Back to Dashboard
                    </a>
                </div>
                
            <?php elseif ($student['status'] != 'approved'): ?>
                <!-- Student not approved yet -->
                <div class="alert alert-warning">
                    <h3 style="margin-bottom: 10px;">⚠️ Application Not Approved</h3>
                    <p>Your student registration is currently <strong><?php echo ucfirst($student['status']); ?></strong>.</p>
                    <p style="margin-top: 10px;">You can only request a room after your application has been approved by the hostel manager.</p>
                    <a href="dashboard.php" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px;">
                        Back to Dashboard
                    </a>
                </div>
                
            <?php elseif (empty($student['gender'])): ?>
                <!-- Gender information missing -->
                <div class="alert alert-error">
                    <h3 style="margin-bottom: 10px;">❌ Profile Incomplete</h3>
                    <p>Your gender information is missing. This is required for room allocation as rooms are gender-specific.</p>
                    <p style="margin-top: 10px;"><strong>Action Required:</strong> Please contact the hostel manager to update your profile information.</p>
                    <a href="dashboard.php" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px;">
                        Back to Dashboard
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Show request form -->
                <div class="info-card">
                    <h3>ℹ️ Room Request Information</h3>
                    <p>Please fill out the form below to request a hostel room. The hostel manager will review your request and allocate a room based on availability and your preferences.</p>
                </div>
                
                <div class="form-container">
                    <h2>📝 Room Request Form</h2>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Your Gender <span class="required">*</span></label>
                            <input type="text" 
                                   value="<?php echo isset($student['gender']) && $student['gender'] ? ucfirst($student['gender']) : 'Not specified'; ?>" 
                                   disabled>
                            <small>Rooms are allocated based on gender</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Preferred Floor <span class="required">*</span></label>
                            <select name="preferred_floor" required>
                                <option value="">Select Floor</option>
                                <option value="1">1st Floor</option>
                                <option value="2">2nd Floor</option>
                                <option value="3">3rd Floor</option>
                                <option value="4">4th Floor</option>
                                <option value="5">5th Floor</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Roommate Preference (Optional)</label>
                            <input type="text" name="roommate_preference" placeholder="Enter student name or ID if you have a preference">
                            <small>Leave blank if you have no preference</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Special Requirements (Optional)</label>
                            <textarea name="special_requirements" placeholder="Any medical conditions, accessibility needs, or other special requirements..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit">Submit Room Request</button>
                    </form>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>