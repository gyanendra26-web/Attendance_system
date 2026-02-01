<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isEmployee()) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Filter parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Get attendance history
$query = "SELECT * FROM attendance 
          WHERE employee_id = ? 
            AND MONTH(attendance_date) = ? 
            AND YEAR(attendance_date) = ?
          ORDER BY attendance_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$attendance_history = mysqli_stmt_get_result($stmt);

// Get attendance summary
$query = "SELECT 
            COUNT(*) as total_days,
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
            COUNT(CASE WHEN status = 'holiday' THEN 1 END) as holiday_days,
            COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_days,
            SUM(total_hours) as total_worked_hours,
            SUM(overtime_hours) as total_overtime_hours
          FROM attendance 
          WHERE employee_id = ? 
            AND MONTH(attendance_date) = ? 
            AND YEAR(attendance_date) = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$summary = mysqli_stmt_get_result($stmt);
$attendance_summary = mysqli_fetch_assoc($summary);

// Get current employee info
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employee_info = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .attendance-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }
        
        .calendar-day {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            min-height: 80px;
        }
        
        .day-header {
            font-weight: bold;
            background: #f8f9fa;
            padding: 5px;
        }
        
        .status-present { background: #d4edda; }
        .status-absent { background: #f8d7da; }
        .status-leave { background: #fff3cd; }
        .status-holiday { background: #d1ecf1; }
        .status-half_day { background: #ffeaa7; }
        
        .day-number {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .day-status {
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .empty-day {
            background: #f8f9fa;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>Attendance History</h2>
                
                <!-- Employee Info -->
                <div class="card">
                    <h3>Employee Information</h3>
                    <div class="employee-info-grid">
                        <div class="info-item">
                            <span class="info-label">Employee ID:</span>
                            <span class="info-value"><?php echo $employee_info['employee_id']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo $employee_info['name']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Department:</span>
                            <span class="info-value"><?php echo $employee_info['department']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Position:</span>
                            <span class="info-value"><?php echo $employee_info['position']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Month Selector -->
                <div class="card">
                    <h3>Select Month</h3>
                    <form method="GET" action="" class="month-selector">
                        <div class="form-row">
                            <div class="form-group">
                                <select name="month">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $month ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <select name="year">
                                    <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $year ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">View</button>
                        </div>
                    </form>
                </div>
                
                <!-- Attendance Summary -->
                <div class="card">
                    <h3>Monthly Summary - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-value present"><?php echo $attendance_summary['present_days'] ?? 0; ?></div>
                            <div class="summary-label">Present</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value absent"><?php echo $attendance_summary['absent_days'] ?? 0; ?></div>
                            <div class="summary-label">Absent</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value leave"><?php echo $attendance_summary['leave_days'] ?? 0; ?></div>
                            <div class="summary-label">Leave</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value holiday"><?php echo $attendance_summary['holiday_days'] ?? 0; ?></div>
                            <div class="summary-label">Holiday</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value half_day"><?php echo $attendance_summary['half_days'] ?? 0; ?></div>
                            <div class="summary-label">Half Day</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value overtime"><?php echo $attendance_summary['total_overtime_hours'] ?? 0; ?></div>
                            <div class="summary-label">OT Hours</div>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar View -->
                <div class="card">
                    <h3>Attendance Calendar</h3>
                    <div class="attendance-calendar">
                        <!-- Day headers -->
                        <div class="calendar-day day-header">Sun</div>
                        <div class="calendar-day day-header">Mon</div>
                        <div class="calendar-day day-header">Tue</div>
                        <div class="calendar-day day-header">Wed</div>
                        <div class="calendar-day day-header">Thu</div>
                        <div class="calendar-day day-header">Fri</div>
                        <div class="calendar-day day-header">Sat</div>
                        
                        <!-- Empty days for start of month -->
                        <?php
                        $first_day = date('w', strtotime("$year-$month-01"));
                        for ($i = 0; $i < $first_day; $i++) {
                            echo '<div class="calendar-day empty-day"></div>';
                        }
                        
                        // Days of month
                        $days_in_month = date('t', strtotime("$year-$month-01"));
                        
                        // Create array of attendance data for quick lookup
                        $attendance_data = [];
                        mysqli_data_seek($attendance_history, 0);
                        while ($att = mysqli_fetch_assoc($attendance_history)) {
                            $day = date('j', strtotime($att['attendance_date']));
                            $attendance_data[$day] = $att;
                        }
                        
                        // Display days
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
                            $attendance = $attendance_data[$day] ?? null;
                            $status_class = $attendance ? 'status-' . $attendance['status'] : '';
                            $status_text = $attendance ? $attendance['status'] : 'n/a';
                            
                            echo '<div class="calendar-day ' . $status_class . '">';
                            echo '<div class="day-number">' . $day . '</div>';
                            
                            if ($attendance) {
                                echo '<div class="day-status">' . ucfirst($status_text) . '</div>';
                                
                                if ($attendance['check_in']) {
                                    echo '<div class="check-in">' . date('h:i A', strtotime($attendance['check_in'])) . '</div>';
                                }
                                
                                if ($attendance['check_out']) {
                                    echo '<div class="check-out">' . date('h:i A', strtotime($attendance['check_out'])) . '</div>';
                                }
                                
                                if ($attendance['overtime_hours'] > 0) {
                                    echo '<div class="overtime">OT: ' . $attendance['overtime_hours'] . 'h</div>';
                                }
                            } else {
                                // Check if it's Saturday
                                $day_of_week = date('w', strtotime($date));
                                if ($day_of_week == 6) {
                                    echo '<div class="day-status holiday">Saturday</div>';
                                } else {
                                    echo '<div class="day-status">-</div>';
                                }
                            }
                            
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Detailed List View -->
                <div class="card">
                    <h3>Detailed Attendance</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Total Hours</th>
                                    <th>Status</th>
                                    <th>OT Hours</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($attendance_history, 0);
                                while ($att = mysqli_fetch_assoc($attendance_history)): 
                                ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($att['attendance_date'])); ?></td>
                                    <td><?php echo date('D', strtotime($att['attendance_date'])); ?></td>
                                    <td><?php echo $att['check_in'] ? date('h:i A', strtotime($att['check_in'])) : '-'; ?></td>
                                    <td><?php echo $att['check_out'] ? date('h:i A', strtotime($att['check_out'])) : '-'; ?></td>
                                    <td><?php echo $att['total_hours'] ? $att['total_hours'] . 'h' : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $att['status']; ?>">
                                            <?php echo ucfirst($att['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $att['overtime_hours'] ? $att['overtime_hours'] . 'h' : '-'; ?></td>
                                    <td><?php echo $att['notes']; ?></td>
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