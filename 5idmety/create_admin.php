<?php
require_once 'config.php';

// This script creates an admin user - run it once then delete it for security
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = 'admin@mauritania.gov';
    $password = 'admin123'; // Change this to a secure password
    $full_name = 'System Administrator';
    
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "Admin user already exists!";
    } else {
        // Create admin user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, is_verified, created_at) VALUES (?, ?, ?, 'admin', 1, NOW())");
        
        if ($stmt->execute([$full_name, $email, $hashed_password])) {
            echo "Admin user created successfully!<br>";
            echo "Email: $email<br>";
            echo "Password: $password<br>";
            echo "<strong>Please change the password after first login and delete this file!</strong>";
        } else {
            echo "Error creating admin user.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin User</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>Create Admin User</h2>
    
    <div class="warning">
        <strong>Warning:</strong> This script creates an admin user with default credentials. 
        Run it once, then delete this file for security reasons.
    </div>
    
    <form method="POST">
        <p>This will create an admin user with:</p>
        <ul>
            <li>Email: admin@mauritania.gov</li>
            <li>Password: admin123</li>
        </ul>
        
        <button type="submit">Create Admin User</button>
    </form>
    
    <p><a href="admin_login.php">Go to Admin Login</a></p>
</body>
</html>
