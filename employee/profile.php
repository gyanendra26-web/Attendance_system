<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isEmployee()) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$message = '';

// Get current employee info
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        
        $query = "UPDATE employees SET name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $phone, $employee_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update session
            $_SESSION['employee_name'] = $name;
            $_SESSION['employee_email'] = $email;
            
            $message = '<div class="alert success">Profile updated successfully!</div>';
            
            // Refresh employee data
            $query = "SELECT * FROM employees WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $employee_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $employee = mysqli_fetch_assoc($result);
        } else {
            $message = '<div class="alert error">Error updating profile!</div>';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!verifyPassword($current_password, $employee['password'])) {
            $message = '<div class="alert error">Current password is incorrect!</div>';
        } elseif ($new_password !== $confirm_password) {
            $message = '<div class="alert error">New passwords do not match!</div>';
        } elseif (strlen($new_password) < 6) {
            $message = '<div class="alert error">Password must be at least 6 characters!</div>';
        } else {
            $hashed_password = hashPassword($new_password);
            
            $query = "UPDATE employees SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $employee_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = '<div class="alert success">Password changed successfully!</div>';
            } else {
                $message = '<div class="alert error">Error changing password!</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
        }
        
        .profile-info h3 {
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .profile-info p {
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .tab-container {
            margin-top: 30px;
        }
        
        .tab-nav {
            display: flex;
            border-bottom: 2px solid #ecf0f1;
            margin-bottom: 20px;
        }
        
        .tab-link {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            color: #7f8c8d;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        
        .tab-link.active {
            color: #3498db;
            border-bottom: 2px solid #3498db;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($employee['name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo $employee['name']; ?></h3>
                        <p><?php echo $employee['position']; ?></p>
                        <p><?php echo $employee['department']; ?> Department</p>
                        <p>Employee ID: <?php echo $employee['employee_id']; ?></p>
                    </div>
                </div>
                
                <?php echo $message; ?>
                
                <div class="tab-container">
                    <div class="tab-nav">
                        <button class="tab-link active" onclick="openTab(event, 'profile')">Profile Information</button>
                        <button class="tab-link" onclick="openTab(event, 'password')">Change Password</button>
                        <button class="tab-link" onclick="openTab(event, 'statistics')">Statistics</button>
                    </div>
                    
                    <!-- Profile Tab -->
                    <div id="profile" class="tab-content active">
                        <div class="card">
                            <h3>Personal Information</h3>
                            <form method="POST" action="">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="name" value="<?php echo $employee['name']; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="email" value="<?php echo $employee['email']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" value="<?php echo $employee['phone']; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Employee ID</label>
                                        <input type="text" value="<?php echo $employee['employee_id']; ?>" readonly class="readonly">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Department</label>
                                        <input type="text" value="<?php echo $employee['department']; ?>" readonly class="readonly">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Position</label>
                                        <input type="text" value="<?php echo $employee['position']; ?>" readonly class="readonly">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Password Tab -->
                    <div id="password" class="tab-content">
                        <div class="card">
                            <h3>Change Password</h3>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required 
                                           minlength="6">
                                    <small class="form-text">Minimum 6 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Statistics Tab -->
                    <div id="statistics" class="tab-content">
                        <div class="card">
                            <h3>Attendance Statistics</h3>
                            <?php
                            // Get this year's statistics
                            $year = date('Y');
                            $query = "SELECT 
                                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                                        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                                        COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
                                        SUM(overtime_hours) as overtime_hours,
                                        AVG(total_hours) as avg_working_hours
                                      FROM attendance 
                                      WHERE employee_id = ? 
                                        AND YEAR(attendance_date) = ?";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "ii", $employee_id, $year);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $stats = mysqli_fetch_assoc($result);
                            
                            // Get monthly attendance trend
                            $query = "SELECT 
                                        MONTH(attendance_date) as month,
                                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days
                                      FROM attendance 
                                      WHERE employee_id = ? 
                                        AND YEAR(attendance_date) = ?
                                      GROUP BY MONTH(attendance_date)
                                      ORDER BY month";
                            $stmt = mysqli_prepare($conn, $query);
                            mysqli_stmt_bind_param($stmt, "ii", $employee_id, $year);
                            mysqli_stmt_execute($stmt);
                            $monthly_data = mysqli_stmt_get_result($stmt);
                            
                            $monthly_presents = array_fill(1, 12, 0);
                            while ($row = mysqli_fetch_assoc($monthly_data)) {
                                $monthly_presents[$row['month']] = $row['present_days'];
                            }
                            ?>
                            
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['present_days'] ?? 0; ?></div>
                                    <div class="stat-label">Present Days (<?php echo $year; ?>)</div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['leave_days'] ?? 0; ?></div>
                                    <div class="stat-label">Leave Days</div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['overtime_hours'] ?? 0; ?></div>
                                    <div class="stat-label">OT Hours</div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo round($stats['avg_working_hours'] ?? 0, 1); ?>h</div>
                                    <div class="stat-label">Avg. Daily Hours</div>
                                </div>
                            </div>
                            
                            <!-- Monthly Chart (HTML for JS) -->
                            <div style="margin-top: 30px;">
                                <h4>Monthly Attendance Trend - <?php echo $year; ?></h4>
                                <canvas id="attendanceChart" height="100"></canvas>
                            </div>
                            
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const ctx = document.getElementById('attendanceChart').getContext('2d');
                                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                               'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                const presents = <?php echo json_encode(array_values($monthly_presents)); ?>;
                                
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: months,
                                        datasets: [{
                                            label: 'Present Days',
                                            data: presents,
                                            borderColor: '#3498db',
                                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                            tension: 0.3,
                                            fill: true
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Days'
                                                }
                                            }
                                        }
                                    }
                                });
                            });
                            </script>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
    function openTab(evt, tabName) {
        // Hide all tab contents
        const tabContents = document.getElementsByClassName('tab-content');
        for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].classList.remove('active');
        }
        
        // Remove active class from all tab links
        const tabLinks = document.getElementsByClassName('tab-link');
        for (let i = 0; i < tabLinks.length; i++) {
            tabLinks[i].classList.remove('active');
        }
        
        // Show current tab and add active class
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    }
    </script>
</body>
</html>