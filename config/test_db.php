<?php
echo "<h2>Testing Database Connection...</h2>";

$host = 'localhost';
$dbname = 'hostel_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ <strong>Database connection successful!</strong><br>";
    echo "Database: " . $dbname . "<br><br>";
    
    // Test if tables exist
    $tables = ['users', 'students', 'rooms', 'allocations'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists!<br>";
        } else {
            echo "❌ Table '$table' NOT found!<br>";
        }
    }
    
    // Check admin user
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'hostel_manager'");
    if ($stmt->rowCount() > 0) {
        echo "<br>✅ Admin user exists!<br>";
    } else {
        echo "<br>❌ No admin user found. You need to create one!<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ <strong>Connection failed:</strong> " . $e->getMessage();
}
?>