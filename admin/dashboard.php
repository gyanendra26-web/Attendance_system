<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Get dashboard statistics
$query = "SELECT 
            (SELECT COUNT(*) FROM employees WHERE status = 'active') as total_employees,
            (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'present') as present_today,
            (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'absent') as absent_today,
            (SELECT COUNT(*) FROM leave_requests WHERE status = 'pending') as pending_leaves,
            (SELECT SUM(overtime_hours) FROM attendance WHERE MONTH(attendance_date) = MONTH(CURDATE())) as monthly_overtime";

$result = mysqli_query($conn, $query);
$stats = mysqli_fetch_assoc($result);

// Get recent attendance
$query = "SELECT e.name, e.department, a.* 
          FROM attendance a
          JOIN employees e ON a.employee_id = e.id
          WHERE a.attendance_date = CURDATE()
          ORDER BY a.check_in DESC
          LIMIT 10";
$recent_attendance = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Admin Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-grid">
            <nav class="sidebar">
                <ul>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="employees.php">Employees</a></li>
                    <li><a href="attendance_report.php">Attendance Report</a></li>
                    <li><a href="leave_management.php">Leave Management</a></li>
                    <li><a href="holidays.php">Holidays</a></li>
                    <li><a href="overtime_report.php">Overtime Report</a></li>
                </ul>
            </nav>

            <main class="main-content">
                <!-- Statistics Cards -->
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Total Employees</h3>
                        <div class="stat-number"><?php echo $stats['total_employees']; ?></div>
                        <a href="employees.php" class="btn btn-primary">View All</a>
                    </div>

                    <div class="card">
                        <h3>Present Today</h3>
                        <div class="stat-number success"><?php echo $stats['present_today']; ?></div>
                        <a href="attendance_report.php?date=<?php echo date('Y-m-d'); ?>" 
                           class="btn btn-success">View Details</a>
                    </div>

                    <div class="card">
                        <h3>Absent Today</h3>
                        <div class="stat-number danger"><?php echo $stats['absent_today']; ?></div>
                        <a href="attendance_report.php?date=<?php echo date('Y-m-d'); ?>&status=absent" 
                           class="btn btn-warning">View Details</a>
                    </div>

                    <div class="card">
                        <h3>Pending Leaves</h3>
                        <div class="stat-number warning"><?php echo $stats['pending_leaves']; ?></div>
                        <a href="leave_management.php" class="btn btn-info">Review</a>
                    </div>
                </div>

                <!-- Recent Attendance -->
                <div class="card">
                    <h3>Today's Attendance</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>OT Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recent_attendance)): ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['department']; ?></td>
                                    <td><?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></td>
                                    <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['overtime_hours']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>