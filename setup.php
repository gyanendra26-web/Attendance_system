<?php
// Initial setup wizard
session_start();
require_once 'config/database.php';

$message = '';
$step = $_GET['step'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 1) {
        // Create database tables
        $sql_file = file_get_contents('database/attendance_system.sql');
        $queries = explode(';', $sql_file);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                mysqli_query($conn, $query);
            }
        }
        
        $step = 2;
    } elseif ($step == 2) {
        // Create admin account
        $username = sanitize($_POST['username']);
        $password = hashPassword($_POST['password']);
        $email = sanitize($_POST['email']);
        
        $query = "INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $username, $password);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert success">Setup completed successfully!</div>';
            $step = 3;
        } else {
            $message = '<div class="alert error">Error creating admin account!</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .setup-step {
            margin-bottom: 30px;
        }
        
        .setup-step h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .progress-bar {
            height: 10px;
            background: #ecf0f1;
            border-radius: 5px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            background: #3498db;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Attendance System Setup</h1>
        <p class="setup-info">Welcome to the setup wizard. Please follow these steps to configure your system.</p>
        
        <div class="progress-bar">
            <div class="progress" style="width: <?php echo ($step-1)*33.33; ?>%"></div>
        </div>
        
        <?php echo $message; ?>
        
        <?php if ($step == 1): ?>
        <div class="setup-step">
            <h3>Step 1: Database Setup</h3>
            <p>The system will now create the required database tables.</p>
            <p>Make sure you have created the database <strong>attendance_system</strong> in phpMyAdmin.</p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" value="attendance_system" readonly class="readonly">
                </div>
                
                <div class="form-group">
                    <label>Database User</label>
                    <input type="text" value="root" readonly class="readonly">
                </div>
                
                <button type="submit" class="btn btn-primary">Create Database Tables</button>
            </form>
        </div>
        
        <?php elseif ($step == 2): ?>
        <div class="setup-step">
            <h3>Step 2: Create Admin Account</h3>
            <p>Create your first administrator account.</p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="username" required placeholder="Enter username">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="Enter email">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm password">
                </div>
                
                <button type="submit" class="btn btn-primary">Create Admin Account</button>
            </form>
        </div>
        
        <?php elseif ($step == 3): ?>
        <div class="setup-step">
            <h3>Step 3: Setup Complete!</h3>
            <div class="alert success">
                <p>System setup has been completed successfully!</p>
                <p>Your admin account has been created.</p>
            </div>
            
            <div class="setup-info">
                <h4>Next Steps:</h4>
                <ol>
                    <li>Login with your admin credentials</li>
                    <li>Add employees to the system</li>
                    <li>Configure system settings</li>
                    <li>Add Nepali festivals to holidays</li>
                </ol>
                
                <p><strong>Login URL:</strong> <a href="login.php">login.php</a></p>
            </div>
            
            <div class="setup-actions">
                <a href="login.php" class="btn btn-success">Go to Login</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>