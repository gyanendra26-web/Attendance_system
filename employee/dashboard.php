<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isEmployee()) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$today = date('Y-m-d');
$current_time = date('H:i:s');

// Get today's attendance
$holiday = isHoliday($today);
$attendance = getTodayAttendance($employee_id);

// Ensure there is an attendance row for today. If missing, create one so
// the dashboard can show check-in / check-out buttons and updates will work.
if (!$attendance) {
    $initial_status = $holiday ? 'holiday' : 'absent';
    $ins_query = "INSERT INTO attendance (employee_id, attendance_date, status) VALUES (?, ?, ?)";
    $ins_stmt = mysqli_prepare($conn, $ins_query);
    mysqli_stmt_bind_param($ins_stmt, "iss", $employee_id, $today, $initial_status);
    mysqli_stmt_execute($ins_stmt);
    // Reload attendance after insert
    $attendance = getTodayAttendance($employee_id);
}

// Handle check-in/check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['check_in'])) {
        // Check if already checked in
        if (!$attendance['check_in']) {
            $query = "UPDATE attendance SET check_in = ?, status = 'present' 
                      WHERE employee_id = ? AND attendance_date = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sis", $current_time, $employee_id, $today);
            mysqli_stmt_execute($stmt);
            
            header("Location: dashboard.php");
            exit();
        }
    } elseif (isset($_POST['check_out'])) {
        // Check if checked in but not out
        if ($attendance['check_in'] && !$attendance['check_out']) {
            // Calculate total hours
            $total_hours = calculateWorkingHours($attendance['check_in'], $current_time);
            
            // Check for overtime (if working on holiday or Saturday)
            $overtime_hours = 0;
            if ($holiday) {
                $overtime_hours = $total_hours;
            }
            
            // Check for half day
            $status = 'present';
            if ($total_hours < 6) {
                $status = 'half_day';
            }
            
            $query = "UPDATE attendance 
                      SET check_out = ?, total_hours = ?, status = ?, overtime_hours = ?
                      WHERE employee_id = ? AND attendance_date = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sdsdis", 
                $current_time, $total_hours, $status, $overtime_hours, 
                $employee_id, $today);
            mysqli_stmt_execute($stmt);
            
            header("Location: dashboard.php");
            exit();
        }
    }
}

// Get monthly summary
$month = date('m');
$year = date('Y');
$query = "SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
            COUNT(CASE WHEN status = 'holiday' THEN 1 END) as holiday_days,
            SUM(overtime_hours) as total_overtime
          FROM attendance 
          WHERE employee_id = ? 
            AND MONTH(attendance_date) = ? 
            AND YEAR(attendance_date) = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$monthly_summary = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>Employee Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['employee_name']; ?></span>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-grid">
            <!-- Left Sidebar -->
            <nav class="sidebar">
                <ul>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="attendance.php">Attendance History</a></li>
                    <li><a href="leave_request.php">Leave Request</a></li>
                    <li><a href="profile.php">Profile</a></li>
                </ul>
            </nav>

            <!-- Main Content -->
            <main class="main-content">
                <div class="dashboard-cards">
                    <!-- Current Day Status -->
                    <div class="card status-card">
                        <h3>Today's Status</h3>
                        <div class="status-info">
                            <p><strong>Date:</strong> <?php echo date('F d, Y'); ?></p>
                            <p><strong>Day:</strong> <?php echo date('l'); ?></p>
                            
                            <?php if ($holiday): ?>
                                <div class="holiday-status">
                                    <span class="status-badge holiday">Holiday</span>
                                    <p><?php echo $holiday['holiday_name']; ?></p>
                                </div>
                            <?php elseif ($attendance): ?>
                                <div class="attendance-status">
                                    <p><strong>Check-in:</strong> 
                                        <?php echo $attendance['check_in'] ? date('h:i A', strtotime($attendance['check_in'])) : 'Not checked in'; ?>
                                    </p>
                                    <p><strong>Check-out:</strong> 
                                        <?php echo $attendance['check_out'] ? date('h:i A', strtotime($attendance['check_out'])) : 'Not checked out'; ?>
                                    </p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge <?php echo $attendance['status']; ?>">
                                            <?php echo ucfirst($attendance['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <!-- Check-in/Check-out buttons -->
                                <form method="POST" class="attendance-actions">
                                    <?php if (!$attendance['check_in']): ?>
                                        <button type="submit" name="check_in" class="btn btn-success">
                                            Check In (<?php echo date('h:i A'); ?>)
                                        </button>
                                    <?php elseif (!$attendance['check_out']): ?>
                                        <button type="submit" name="check_out" class="btn btn-warning">
                                            Check Out (<?php echo date('h:i A'); ?>)
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Monthly Summary -->
                    <div class="card">
                        <h3>Monthly Summary (<?php echo date('F Y'); ?>)</h3>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="count present"><?php echo $monthly_summary['present_days'] ?? 0; ?></span>
                                <span class="label">Present</span>
                            </div>
                            <div class="summary-item">
                                <span class="count absent"><?php echo $monthly_summary['absent_days'] ?? 0; ?></span>
                                <span class="label">Absent</span>
                            </div>
                            <div class="summary-item">
                                <span class="count leave"><?php echo $monthly_summary['leave_days'] ?? 0; ?></span>
                                <span class="label">Leave</span>
                            </div>
                            <div class="summary-item">
                                <span class="count overtime"><?php echo $monthly_summary['total_overtime'] ?? 0; ?></span>
                                <span class="label">OT Hours</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <h3>Quick Actions</h3>
                        <div class="quick-actions">
                            <a href="leave_request.php" class="btn btn-primary">Apply Leave</a>
                            <a href="attendance.php" class="btn btn-secondary">View Attendance</a>
                            <a href="profile.php" class="btn btn-info">Update Profile</a>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Holidays -->
                <div class="card">
                    <h3>Upcoming Holidays</h3>
                    <div class="holiday-list">
                        <?php
                        // Use range-aware query if DB supports start/end dates
                        if (function_exists('columnExists') && columnExists('holidays', 'start_date')) {
                            $query = "SELECT holiday_name, holiday_type, description, COALESCE(start_date, holiday_date) AS start_date, end_date 
                                      FROM holidays 
                                      WHERE COALESCE(start_date, holiday_date) >= CURDATE() 
                                      ORDER BY COALESCE(start_date, holiday_date) 
                                      LIMIT 5";
                        } else {
                            $query = "SELECT holiday_name, holiday_type, description, holiday_date as start_date, NULL as end_date 
                                      FROM holidays 
                                      WHERE holiday_date >= CURDATE() 
                                      ORDER BY holiday_date 
                                      LIMIT 5";
                        }
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0) {
                            while ($holiday = mysqli_fetch_assoc($result)) {
                                $start = $holiday['start_date'];
                                $end = $holiday['end_date'] ?? $holiday['start_date'];
                                echo '<div class="holiday-item">';
                                if ($start == $end) {
                                    echo '<span>' . date('M d', strtotime($start)) . '</span>';
                                } else {
                                    echo '<span>' . date('M d', strtotime($start)) . ' - ' . date('M d', strtotime($end)) . '</span>';
                                }
                                echo '<span>' . $holiday['holiday_name'] . '</span>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>No upcoming holidays</p>';
                        }
                        ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Real-time Clock -->
    <div class="real-time-clock">
        <span id="current-time"></span>
        <span id="current-date"></span>
    </div>
</body>
</html>