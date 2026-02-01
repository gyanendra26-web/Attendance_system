<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Default values
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$employee_id = $_GET['employee_id'] ?? '';
$department = $_GET['department'] ?? '';

// Build query
$conditions = [];
$params = [];
$types = '';

if ($employee_id) {
    $conditions[] = "e.id = ?";
    $params[] = $employee_id;
    $types .= 'i';
}

if ($department) {
    $conditions[] = "e.department = ?";
    $params[] = $department;
    $types .= 's';
}

$where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get attendance report
$query = "SELECT 
            e.employee_id,
            e.name,
            e.department,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN a.status = 'leave' THEN 1 ELSE 0 END) as leave_days,
            SUM(CASE WHEN a.status = 'holiday' THEN 1 ELSE 0 END) as holiday_days,
            SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) as half_days,
            SUM(a.overtime_hours) as overtime_hours
          FROM employees e
          LEFT JOIN attendance a ON e.id = a.employee_id 
            AND MONTH(a.attendance_date) = ? 
            AND YEAR(a.attendance_date) = ?
          $where_clause
          GROUP BY e.id
          ORDER BY e.department, e.name";

array_unshift($params, $month, $year);
$types = 'ii' . $types;

$stmt = mysqli_prepare($conn, $query);
if ($types) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$report_data = mysqli_stmt_get_result($stmt);

// Get departments for filter
$departments = mysqli_query($conn, "SELECT DISTINCT department FROM employees ORDER BY department");
$employees = mysqli_query($conn, "SELECT id, name, employee_id FROM employees ORDER BY name");

// Export to CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['Employee ID', 'Name', 'Department', 'Present', 'Absent', 'Leave', 'Holiday', 'Half Days', 'OT Hours']);
    
    // Data
    while ($row = mysqli_fetch_assoc($report_data)) {
        fputcsv($output, [
            $row['employee_id'],
            $row['name'],
            $row['department'],
            $row['present_days'],
            $row['absent_days'],
            $row['leave_days'],
            $row['holiday_days'],
            $row['half_days'],
            $row['overtime_hours']
        ]);
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>Attendance Report</h2>
                
                <!-- Filters -->
                <div class="card">
                    <h3>Filters</h3>
                    <form method="GET" action="" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Month</label>
                                <select name="month">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo sprintf('%02d', $i); ?>" 
                                        <?php echo $i == $month ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year">
                                    <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                        <?php echo $i == $year ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department">
                                    <option value="">All Departments</option>
                                    <?php while ($dept = mysqli_fetch_assoc($departments)): ?>
                                    <option value="<?php echo $dept['department']; ?>"
                                        <?php echo $dept['department'] == $department ? 'selected' : ''; ?>>
                                        <?php echo $dept['department']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Employee</label>
                                <select name="employee_id">
                                    <option value="">All Employees</option>
                                    <?php while ($emp = mysqli_fetch_assoc($employees)): ?>
                                    <option value="<?php echo $emp['id']; ?>"
                                        <?php echo $emp['id'] == $employee_id ? 'selected' : ''; ?>>
                                        <?php echo $emp['name'] . ' (' . $emp['employee_id'] . ')'; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="?" class="btn btn-secondary">Reset</a>
                            <a href="?export=1&month=<?php echo $month; ?>&year=<?php echo $year; ?>&department=<?php echo $department; ?>&employee_id=<?php echo $employee_id; ?>" 
                               class="btn btn-success">Export to CSV</a>
                        </div>
                    </form>
                </div>
                
                <!-- Report Table -->
                <div class="card">
                    <h3>Attendance Summary - <?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Leave</th>
                                    <th>Holiday</th>
                                    <th>Half Day</th>
                                    <th>OT Hours</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($report_data, 0); // Reset pointer
                                while ($row = mysqli_fetch_assoc($report_data)): 
                                ?>
                                <tr>
                                    <td><?php echo $row['employee_id']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['department']; ?></td>
                                    <td class="present"><?php echo $row['present_days']; ?></td>
                                    <td class="absent"><?php echo $row['absent_days']; ?></td>
                                    <td class="leave"><?php echo $row['leave_days']; ?></td>
                                    <td class="holiday"><?php echo $row['holiday_days']; ?></td>
                                    <td class="half-day"><?php echo $row['half_days']; ?></td>
                                    <td class="overtime"><?php echo $row['overtime_hours']; ?></td>
                                    <td>
                                        <a href="employee_attendance.php?employee_id=<?php echo $row['employee_id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" 
                                           class="btn btn-sm btn-info">View Details</a>
                                    </td>
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