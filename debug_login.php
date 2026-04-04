<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Test</h2>";

echo "1. Testing PHP... ✅<br>";

echo "2. Testing file paths...<br>";
if (file_exists('config/database.php')) {
    echo "   - config/database.php exists ✅<br>";
} else {
    echo "   - config/database.php NOT FOUND ❌<br>";
}

if (file_exists('css/style.css')) {
    echo "   - css/style.css exists ✅<br>";
} else {
    echo "   - css/style.css NOT FOUND ❌<br>";
}

echo "<br>3. Testing database connection...<br>";
try {
    require_once 'config/database.php';
    echo "   - Database connection successful ✅<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   - Users table accessible ✅<br>";
    echo "   - Total users in database: " . $result['count'] . "<br>";
    
    $stmt = $pdo->query("SELECT * FROM users WHERE role='hostel_manager' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "   - Admin user found ✅<br>";
        echo "   - Admin email: " . $admin['email'] . "<br>";
    } else {
        echo "   - No admin user found ❌<br>";
    }
    
} catch(Exception $e) {
    echo "   - Database error ❌: " . $e->getMessage() . "<br>";
}

echo "<br>4. All checks complete!<br>";
?>
