<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

/**
 * Check whether a column exists in a table
 */
function columnExists($table, $column) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $query = "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $column . "'";
    $res = mysqli_query($conn, $query);
    return $res && mysqli_num_rows($res) > 0;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['employee_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

/**
 * Check if user is employee
 */
function isEmployee() {
    return isset($_SESSION['employee_id']);
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

/**
 * Get today's attendance status for employee
 */
function getTodayAttendance($employee_id) {
    global $conn;
    $today = date('Y-m-d');
    
    $query = "SELECT * FROM attendance 
              WHERE employee_id = ? AND attendance_date = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $employee_id, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_fetch_assoc($result);
}

/**
 * Check if today is holiday
 */
function isHoliday($date = null) {
    global $conn;
    $date = $date ?: date('Y-m-d');

    // Check if Saturday
    if (date('w', strtotime($date)) == 6) {
        return ['holiday_name' => 'Saturday', 'holiday_type' => 'weekly'];
    }

    // If DB has start_date/end_date columns, check ranges first
    if (columnExists('holidays', 'start_date') && columnExists('holidays', 'end_date')) {
        $query = "SELECT * FROM holidays WHERE start_date IS NOT NULL AND end_date IS NOT NULL AND ? BETWEEN start_date AND end_date LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        if ($row) return $row;
    }

    // Fallback to single-date holiday (legacy) or start_date equal to date
    $query = "SELECT * FROM holidays WHERE (holiday_date = ?";
    if (columnExists('holidays', 'start_date')) {
        $query .= " OR start_date = ?";
    }
    $query .= ") LIMIT 1";

    $stmt = mysqli_prepare($conn, $query);
    if (columnExists('holidays', 'start_date')) {
        mysqli_stmt_bind_param($stmt, "ss", $date, $date);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $date);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return mysqli_fetch_assoc($result);
}

/**
 * Calculate working hours
 */
function calculateWorkingHours($check_in, $check_out) {
    if (!$check_in || !$check_out) return 0;
    
    $check_in_time = strtotime($check_in);
    $check_out_time = strtotime($check_out);
    
    $hours = ($check_out_time - $check_in_time) / 3600;
    
    // Deduct lunch break (1 hour) if working more than 5 hours
    if ($hours > 5) {
        $hours -= 1;
    }
    
    return round($hours, 2);
}

/**
 * Mark attendance automatically for all employees
 */
function autoMarkAttendance() {
    global $conn;
    $today = date('Y-m-d');
    
    // Check if already marked today
    $query = "SELECT COUNT(*) as count FROM attendance WHERE attendance_date = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        return; // Already marked for today
    }
    
    // Get all active employees
    $query = "SELECT id FROM employees WHERE status = 'active'";
    $result = mysqli_query($conn, $query);
    
    while ($employee = mysqli_fetch_assoc($result)) {
        $employee_id = $employee['id'];
        $status = 'absent';
        
        // Check if holiday
        $holiday = isHoliday($today);
        if ($holiday) {
            $status = 'holiday';
        }
        
        // Insert attendance record
        $query = "INSERT INTO attendance (employee_id, attendance_date, status) 
                  VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $employee_id, $today, $status);
        mysqli_stmt_execute($stmt);
    }
}

// Auto mark attendance at start of each day
if (date('H') == 0) { // Run at midnight
    autoMarkAttendance();
}
?>