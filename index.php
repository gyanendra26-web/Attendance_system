<?php
/**
 * Attendance System - Main Entry Point
 */
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header("Location: admin/dashboard.php");
    exit();
} elseif (isset($_SESSION['employee_id'])) {
    header("Location: employee/dashboard.php");
    exit();
} else {
    // Check if system is set up
    require_once 'config/database.php';
    
    $query = "SELECT COUNT(*) as count FROM system_settings";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        // System not set up, redirect to setup
        header("Location: setup.php");
        exit();
    } else {
        // System is set up, redirect to login
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management System</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .welcome-container {
            text-align: center;
            color: white;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: fadeIn 1s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .subtitle {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }
        
        .actions {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            margin: 0 10px;
            transition: all 0.3s;
            border: 2px solid white;
        }
        
        .btn:hover {
            background: transparent;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #667eea;
        }
        
        .loading {
            margin-top: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .features {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .welcome-container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h1>Attendance Management System</h1>
        <div class="subtitle">For Companies in Nepal</div>
        
        <div class="features">
            <div class="feature">
                <strong>🇳🇵 Nepal-Focused</strong>
                <p>Saturday holidays & Nepali festivals</p>
            </div>
            <div class="feature">
                <strong>⏱️ Automated</strong>
                <p>Auto check-in/out & attendance</p>
            </div>
            <div class="feature">
                <strong>📊 Smart Reports</strong>
                <p>Real-time analytics & exports</p>
            </div>
            <div class="feature">
                <strong>🔒 Secure</strong>
                <p>Encrypted & protected data</p>
            </div>
        </div>
        
        <div class="actions">
            <a href="login.php" class="btn">Login to System</a>
            <a href="setup.php" class="btn btn-outline">First Time Setup</a>
        </div>
        
        <div class="loading">
            Redirecting to login...
        </div>
    </div>
    
    <script>
        // Auto-redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 5000);
        
        // Display current time
        function updateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'Asia/Kathmandu'
            };
            document.querySelector('.subtitle').innerHTML = 
                now.toLocaleDateString('en-US', options) + ' (Nepal Time)';
        }
        
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>