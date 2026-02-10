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
    
    $stmt = $pdo->query("SHOW TABLES");
    echo "Tables in database:<br>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ <strong>Connection failed:</strong> " . $e->getMessage();
}
?>
