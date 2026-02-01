<?php
/**
 * API: Get Dashboard Statistics
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) && !isset($_SESSION['employee_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$stats = [];

if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    // Admin dashboard stats
    $today = date('Y-m-d');
    
    // Total employees
    $query = "SELECT COUNT(*) as count FROM employees WHERE status = 'active'";
    $result = mysqli_query($conn, $query);
    $stats['total_employees'] = mysqli_fetch_assoc($result)['count'];
    
    // Present today
    $query = "SELECT COUNT(*) as count FROM attendance 
              WHERE attendance_date = ? AND status = 'present'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['present_today'] = mysqli_fetch_assoc($result)['count'];
    
    // Absent today
    $query = "SELECT COUNT(*) as count FROM attendance 
              WHERE attendance_date = ? AND status = 'absent'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['absent_today'] = mysqli_fetch_assoc($result)['count'];
    
    // Pending leaves
    $query = "SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'";
    $result = mysqli_query($conn, $query);
    $stats['pending_leaves'] = mysqli_fetch_assoc($result)['count'];
    
    // Pending overtime
    $query = "SELECT COUNT(*) as count FROM overtime WHERE status = 'pending'";
    $result = mysqli_query($conn, $query);
    $stats['pending_overtime'] = mysqli_fetch_assoc($result)['count'];
    
    // This month's attendance
    $month = date('m');
    $year = date('Y');
    $query = "SELECT COUNT(*) as count FROM attendance 
              WHERE MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $month, $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['monthly_attendance'] = mysqli_fetch_assoc($result)['count'];
    
} else {
    // Employee dashboard stats
    $employee_id = $_SESSION['employee_id'];
    $month = date('m');
    $year = date('Y');
    
    // Today's status
    $today = date('Y-m-d');
    $attendance = getTodayAttendance($employee_id);
    $stats['today_status'] = $attendance['status'] ?? 'not_checked';
    $stats['check_in'] = $attendance['check_in'] ?? null;
    $stats['check_out'] = $attendance['check_out'] ?? null;
    
    // Monthly summary
    $query = "SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
                SUM(overtime_hours) as overtime_hours
              FROM attendance 
              WHERE employee_id = ? 
                AND MONTH(attendance_date) = ? 
                AND YEAR(attendance_date) = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $employee_id, $month, $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $monthly_stats = mysqli_fetch_assoc($result);
    
    $stats['monthly'] = $monthly_stats;
    
    // Leave balance
    $query = "SELECT 
                COUNT(CASE WHEN leave_type = 'casual' THEN 1 END) as casual_taken,
                COUNT(CASE WHEN leave_type = 'sick' THEN 1 END) as sick_taken
              FROM leave_requests 
              WHERE employee_id = ? 
                AND status = 'approved'
                AND YEAR(start_date) = YEAR(CURDATE())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $leave_taken = mysqli_fetch_assoc($result);
    
    $stats['leave_balance'] = [
        'casual' => 15 - ($leave_taken['casual_taken'] ?? 0),
        'sick' => 10 - ($leave_taken['sick_taken'] ?? 0)
    ];
}

$stats['server_time'] = date('Y-m-d H:i:s');
$stats['timezone'] = date_default_timezone_get();

echo json_encode($stats);
?>