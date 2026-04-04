<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hostel_manager') {
    header('Location: ../login.php');
    exit;
}

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve' || $action == 'reject') {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        
        $stmt = $pdo->prepare("UPDATE room_requests SET status = ?, processed_by = ?, processed_date = NOW() WHERE id = ?");
        $stmt->execute([$status, $_SESSION['user_id'], $request_id]);
        
        header('Location: room_requests.php?msg=' . $action . 'd');
        exit;
    }
}

// Get all room requests
$stmt = $pdo->query("
    SELECT rr.*, s.student_id, s.course, s.year, s.gender, u.name, u.email 
    FROM room_requests rr 
    JOIN students s ON rr.student_id = s.id 
    JOIN users u ON s.user_id = u.id 
    ORDER BY 
        CASE rr.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        rr.requested_date DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending_count = count(array_filter($requests, fn($r) => $r['status'] == 'pending'));
$approved_count = count(array_filter($requests, fn($r) => $r['status'] == 'approved'));
$rejected_count = count(array_filter($requests, fn($r) => $r['status'] == 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Requests</title>
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
        .stat-card p { color: #666; }
        
        .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto; }
        h2 { margin-bottom: 20px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; color: #333; }
        table tr:hover { background: #f8f9fa; }
        
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        
        .btn-action { padding: 6px 12px; margin: 2px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem; text-decoration: none; display: inline-block; color: white; }
        .btn-approve { background: #28a745; }
        .btn-approve:hover { background: #218838; }
        .btn-reject { background: #dc3545; }
        .btn-reject:hover { background: #c82333; }
        .btn-view { background: #17a2b8; }
        .btn-view:hover { background: #138496; }
        
        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>📋 Room Requests</h1>
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
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $pending_count; ?></h3>
                    <p>Pending Requests</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $approved_count; ?></h3>
                    <p>Approved</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $rejected_count; ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert-success">
                    ✅ Request <?php echo htmlspecialchars($_GET['msg']); ?> successfully!
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <h2>All Room Requests</h2>
                
                <?php if (count($requests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Gender</th>
                                <th>Preferred Floor</th>
                                <th>Requested Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['student_id']); ?></td>
                                    <td><?php echo ucfirst($req['gender']); ?></td>
                                    <td>Floor <?php echo $req['preferred_floor']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($req['requested_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $req['status']; ?>">
                                            <?php echo ucfirst($req['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] == 'pending'): ?>
                                            <a href="room_requests.php?action=approve&id=<?php echo $req['id']; ?>" 
                                               class="btn-action btn-approve"
                                               onclick="return confirm('Approve this room request?')">
                                                ✅ Approve
                                            </a>
                                            <a href="room_requests.php?action=reject&id=<?php echo $req['id']; ?>" 
                                               class="btn-action btn-reject"
                                               onclick="return confirm('Reject this room request?')">
                                                ❌ Reject
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #999;">Processed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 40px;">No room requests yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>