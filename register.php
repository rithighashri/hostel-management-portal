<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $student_id = trim($_POST['student_id']);
    $course = trim($_POST['course']);
    $year = $_POST['year'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $guardian_name = trim($_POST['guardian_name']);
    $guardian_phone = trim($_POST['guardian_phone']);
    
    if (empty($name) || empty($email) || empty($password) || empty($student_id)) {
        $error = "Please fill all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'student')");
                $stmt->execute([$name, $email, $hashed_password, $phone]);
                
                $user_id = $pdo->lastInsertId();
                
                $stmt = $pdo->prepare("INSERT INTO students (user_id, student_id, course, year, gender, address, guardian_name, guardian_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $student_id, $course, $year, $gender, $address, $guardian_name, $guardian_phone]);
                
                $success = "Registration successful! Please wait for admin approval.";
            }
        } catch(PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Hostel Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .form-container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .text-center {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .text-center a {
            color: #667eea;
            text-decoration: none;
        }
        
        .text-center a:hover {
            text-decoration: underline;
        }
        
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1>Hostel Management System</h1>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="login.php">Login</a>
            </div>
        </div>
    </nav>
    
    <div class="form-container">
        <h2>Student Registration</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone">
                </div>
                
                <div class="form-group">
                    <label>Student ID <span class="required">*</span></label>
                    <input type="text" name="student_id" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Course <span class="required">*</span></label>
                    <input type="text" name="course" required>
                </div>
                
                <div class="form-group">
                    <label>Year <span class="required">*</span></label>
                    <select name="year" required>
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Gender <span class="required">*</span></label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Guardian Name</label>
                    <input type="text" name="guardian_name">
                </div>
                
                <div class="form-group">
                    <label>Guardian Phone</label>
                    <input type="tel" name="guardian_phone">
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Register</button>
        </form>
        
        <p class="text-center">
            Already registered? <a href="login.php">Login here</a>
        </p>
    </div>
</body>
</html>