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

// Get employee info
$query = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employee = mysqli_fetch_assoc($result);

if (!$employee) {
    die("Employee not found");
}

// Get attendance data
$query = "SELECT * FROM attendance 
          WHERE employee_id = ? 
            AND MONTH(attendance_date) = ? 
            AND YEAR(attendance_date) = ?
          ORDER BY attendance_date";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($stmt);
$attendance = mysqli_stmt_get_result($stmt);

// Set headers for CSV download
$filename = "attendance_" . $employee['employee_id'] . "_" . date('F_Y', mktime(0, 0, 0, $month, 1, $year)) . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Write headers
fputcsv($output, [
    'Employee Attendance Report',
    'Generated on: ' . date('Y-m-d H:i:s')
]);
fputcsv($output, []); // Empty row

// Employee information
fputcsv($output, ['Employee Information']);
fputcsv($output, ['Employee ID', $employee['employee_id']]);
fputcsv($output, ['Name', $employee['name']]);
fputcsv($output, ['Department', $employee['department']]);
fputcsv($output, ['Position', $employee['position']]);
fputcsv($output, ['Month', date('F Y', mktime(0, 0, 0, $month, 1, $year))]);
fputcsv($output, []); // Empty row

// Attendance headers
fputcsv($output, [
    'Date',
    'Day',
    'Check-in Time',
    'Check-out Time',
    'Total Hours',
    'Status',
    'Overtime Hours',
    'Late Minutes',
    'Notes'
]);

// Attendance data
$total_hours = 0;
$total_overtime = 0;
$present_days = 0;
$absent_days = 0;
$leave_days = 0;

while ($row = mysqli_fetch_assoc($attendance)) {
    fputcsv($output, [
        $row['attendance_date'],
        date('l', strtotime($row['attendance_date'])),
        $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : 'N/A',
        $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : 'N/A',
        $row['total_hours'] ?? '0',
        ucfirst($row['status']),
        $row['overtime_hours'] ?? '0',
        $row['late_minutes'] ?? '0',
        $row['notes'] ?? ''
    ]);
    
    // Calculate totals
    $total_hours += $row['total_hours'] ?? 0;
    $total_overtime += $row['overtime_hours'] ?? 0;
    
    switch ($row['status']) {
        case 'present': $present_days++; break;
        case 'absent': $absent_days++; break;
        case 'leave': $leave_days++; break;
    }
}

fputcsv($output, []); // Empty row

// Summary
fputcsv($output, ['Summary']);
fputcsv($output, ['Total Present Days', $present_days]);
fputcsv($output, ['Total Absent Days', $absent_days]);
fputcsv($output, ['Total Leave Days', $leave_days]);
fputcsv($output, ['Total Working Hours', round($total_hours, 2)]);
fputcsv($output, ['Total Overtime Hours', round($total_overtime, 2)]);
fputcsv($output, ['Average Daily Hours', round($total_hours / max($present_days, 1), 2)]);

fclose($output);
exit();
?>