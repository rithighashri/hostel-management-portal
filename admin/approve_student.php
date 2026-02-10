<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $student_id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        $status = 'approved';
    } elseif ($action == 'reject') {
        $status = 'rejected';
    } else {
        header('Location: dashboard.php');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE students SET status = ? WHERE id = ?");
        $stmt->execute([$status, $student_id]);
        
        header('Location: dashboard.php?msg=' . $status);
    } catch(PDOException $e) {
        header('Location: dashboard.php?error=1');
    }
} else {
    header('Location: dashboard.php');
}
?>

