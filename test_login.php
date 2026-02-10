<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Login Page</h2>";
echo "1. PHP is working ✅<br>";

if (file_exists('config/database.php')) {
    echo "2. Database config exists ✅<br>";
    
    try {
        require_once 'config/database.php';
        echo "3. Database connected ✅<br>";
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['admin@hostel.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "4. Admin user found ✅<br>";
            echo "   Email: " . $user['email'] . "<br>";
            echo "   Role: " . $user['role'] . "<br>";
        } else {
            echo "4. Admin user NOT found ❌<br>";
        }
        
    } catch (Exception $e) {
        echo "Database error ❌: " . $e->getMessage() . "<br>";
    }
} else {
    echo "2. Database config NOT FOUND ❌<br>";
}

echo "<br><a href='login.php'>Try Login Page</a>";
?>
