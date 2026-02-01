<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_GET['employee_id'] ?? 0;
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Validate employee
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($result);

if (!$employee) {
    header("Location: attendance_report.php?error=employee_not_found");
    exit();
}

// Get attendance details
$query = "SELECT * FROM attendance 
          WHERE employee_id = ? 
            AND MONTH(attendance_date) = ? 
            AND YEAR(attendance_date) = ?
          ORDER BY attendance_date";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$attendance = mysqli_stmt_get_result($stmt);

// Calculate summary
$query = "SELECT 
            COUNT(*) as total_days,
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
            COUNT(CASE WHEN status = 'holiday' THEN 1 END) as holiday_days,
            COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_days,
            SUM(total_hours) as total_worked_hours,
            SUM(overtime_hours) as total_overtime,
            AVG(total_hours) as avg_daily_hours
          FROM attendance 
          WHERE employee_id = ? 
            AND MONTH(attendance_date) = ? 
            AND YEAR(attendance_date) = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$summary_result = mysqli_stmt_get_result($stmt);
$summary = mysqli_fetch_assoc($summary_result);

// Get leave requests for this month
$query = "SELECT * FROM leave_requests 
          WHERE employee_id = ? 
            AND MONTH(start_date) = ? 
            AND YEAR(start_date) = ?
          ORDER BY start_date";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$leaves = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .employee-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .employee-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .info-label {
            display: block;
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
        }
        
        .month-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .summary-value {
            font-size: 32px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        
        .summary-value.present { color: #27ae60; }
        .summary-value.absent { color: #e74c3c; }
        .summary-value.leave { color: #f39c12; }
        .summary-value.overtime { color: #3498db; }
        
        .attendance-day {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        
        .attendance-day:hover {
            background: #f8f9fa;
        }
        
        .day-date {
            font-weight: 600;
            min-width: 100px;
        }
        
        .day-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-leave { background: #fff3cd; color: #856404; }
        .status-holiday { background: #d1ecf1; color: #0c5460; }
        .status-half_day { background: #ffeaa7; color: #2d3436; }
        
        .day-times {
            display: flex;
            gap: 20px;
        }
        
        .time-item {
            min-width: 80px;
        }
        
        .time-label {
            font-size: 11px;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        
        .time-value {
            font-weight: 600;
        }
        
        .print-btn {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-btn:hover {
            background: #1a252f;
        }
        
        @media print {
            .sidebar, .header, .month-navigation .nav-buttons, .print-btn {
                display: none !important;
            }
            
            .container {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .employee-header {
                background: white !important;
                color: black !important;
                padding: 10px !important;
            }
        }
    </style>
</head>
<body>
    <?php 
    $page = 'attendance_report';
    include 'header.php'; 
    ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <!-- Employee Header -->
                <div class="employee-header">
                    <h2><?php echo $employee['name']; ?></h2>
                    <p><?php echo $employee['position']; ?> • <?php echo $employee['department']; ?> Department</p>
                    
                    <div class="employee-info">
                        <div class="info-card">
                            <span class="info-label">Employee ID</span>
                            <span class="info-value"><?php echo $employee['employee_id']; ?></span>
                        </div>
                        <div class="info-card">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo $employee['email']; ?></span>
                        </div>
                        <div class="info-card">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo $employee['phone']; ?></span>
                        </div>
                        <div class="info-card">
                            <span class="info-label">Status</span>
                            <span class="info-value" style="color: <?php echo $employee['status'] == 'active' ? '#27ae60' : '#e74c3c'; ?>">
                                <?php echo ucfirst($employee['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Month Navigation -->
                <div class="month-navigation">
                    <div>
                        <h3>Attendance Details - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
                    </div>
                    
                    <div class="nav-buttons">
                        <form method="GET" action="" style="display: inline;">
                            <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                            <input type="month" name="month_year" 
                                   value="<?php echo $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT); ?>"
                                   onchange="this.form.submit()" style="padding: 8px;">
                        </form>
                        <button onclick="window.print()" class="print-btn">Print Report</button>
                        <a href="attendance_report.php" class="btn btn-secondary">Back to Report</a>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <span class="summary-value present"><?php echo $summary['present_days'] ?? 0; ?></span>
                        <span>Present Days</span>
                    </div>
                    <div class="summary-card">
                        <span class="summary-value absent"><?php echo $summary['absent_days'] ?? 0; ?></span>
                        <span>Absent Days</span>
                    </div>
                    <div class="summary-card">
                        <span class="summary-value leave"><?php echo $summary['leave_days'] ?? 0; ?></span>
                        <span>Leave Days</span>
                    </div>
                    <div class="summary-card">
                        <span class="summary-value"><?php echo $summary['holiday_days'] ?? 0; ?></span>
                        <span>Holidays</span>
                    </div>
                    <div class="summary-card">
                        <span class="summary-value overtime"><?php echo $summary['total_overtime'] ?? 0; ?>h</span>
                        <span>Overtime Hours</span>
                    </div>
                    <div class="summary-card">
                        <span class="summary-value"><?php echo round($summary['avg_daily_hours'] ?? 0, 1); ?>h</span>
                        <span>Avg Daily Hours</span>
                    </div>
                </div>
                
                <!-- Attendance Details -->
                <div class="card">
                    <h3>Daily Attendance</h3>
                    <div class="attendance-list">
                        <?php if (mysqli_num_rows($attendance) > 0): ?>
                            <?php while ($day = mysqli_fetch_assoc($attendance)): ?>
                            <div class="attendance-day">
                                <div class="day-date">
                                    <?php echo date('D, M d', strtotime($day['attendance_date'])); ?>
                                </div>
                                
                                <span class="day-status status-<?php echo $day['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $day['status'])); ?>
                                </span>
                                
                                <div class="day-times">
                                    <div class="time-item">
                                        <div class="time-label">Check-in</div>
                                        <div class="time-value">
                                            <?php echo $day['check_in'] ? date('h:i A', strtotime($day['check_in'])) : '-'; ?>
                                        </div>
                                    </div>
                                    <div class="time-item">
                                        <div class="time-label">Check-out</div>
                                        <div class="time-value">
                                            <?php echo $day['check_out'] ? date('h:i A', strtotime($day['check_out'])) : '-'; ?>
                                        </div>
                                    </div>
                                    <div class="time-item">
                                        <div class="time-label">Total</div>
                                        <div class="time-value">
                                            <?php echo $day['total_hours'] ? $day['total_hours'] . 'h' : '-'; ?>
                                        </div>
                                    </div>
                                    <div class="time-item">
                                        <div class="time-label">Overtime</div>
                                        <div class="time-value">
                                            <?php echo $day['overtime_hours'] ? $day['overtime_hours'] . 'h' : '-'; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($day['late_minutes'] > 0): ?>
                                <div class="late-indicator" style="color: #e74c3c; font-size: 12px;">
                                    Late: <?php echo $day['late_minutes']; ?>m
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="no-data">No attendance records found for this month.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Leave Requests -->
                <div class="card">
                    <h3>Leave Requests for <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
                    <div class="table-container">
                        <?php if (mysqli_num_rows($leaves) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($leave = mysqli_fetch_assoc($leaves)): ?>
                                <tr>
                                    <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $start = new DateTime($leave['start_date']);
                                        $end = new DateTime($leave['end_date']);
                                        echo $start->diff($end)->days + 1;
                                        ?>
                                    </td>
                                    <td><?php echo substr($leave['reason'], 0, 50) . '...'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $leave['status']; ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($leave['applied_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="no-data">No leave requests for this month.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card">
                    <h3>Actions</h3>
                    <div class="action-buttons" style="display: flex; gap: 15px;">
                        <a href="employees.php?edit=<?php echo $employee['id']; ?>" 
                           class="btn btn-info">Edit Employee</a>
                        <a href="attendance_report.php?employee_id=<?php echo $employee['id']; ?>" 
                           class="btn btn-secondary">View All Reports</a>
                        <button onclick="exportAttendance()" class="btn btn-success">Export to CSV</button>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        function exportAttendance() {
            const employeeId = <?php echo $employee_id; ?>;
            const month = <?php echo $month; ?>;
            const year = <?php echo $year; ?>;
            
            window.location.href = `export_attendance.php?employee_id=${employeeId}&month=${month}&year=${year}`;
        }
        
        // Auto-refresh every 5 minutes to update status
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>