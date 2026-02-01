<?php
/**
 * API: Get Current Attendance Status
 */
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$employee_id = $_SESSION['employee_id'];
$today = date('Y-m-d');

// Get today's attendance
$attendance = getTodayAttendance($employee_id);

// Get working hours settings
$query = "SELECT setting_value FROM system_settings WHERE setting_key = 'working_hours_start'";
$result = mysqli_query($conn, $query);
$working_start = mysqli_fetch_assoc($result)['setting_value'] ?? '09:00:00';

$query = "SELECT setting_value FROM system_settings WHERE setting_key = 'working_hours_end'";
$result = mysqli_query($conn, $query);
$working_end = mysqli_fetch_assoc($result)['setting_value'] ?? '17:00:00';

// Check if today is holiday
$holiday = isHoliday($today);

// Prepare response
$response = [
    'date' => $today,
    'is_holiday' => !empty($holiday),
    'holiday_name' => $holiday['holiday_name'] ?? null,
    'attendance' => $attendance,
    'can_check_in' => false,
    'can_check_out' => false,
    'current_time' => date('H:i:s'),
    'working_hours' => [
        'start' => $working_start,
        'end' => $working_end
    ]
];

// Check if can check in
if (!$attendance['check_in']) {
    // Can check in if not holiday and not already checked in
    if (!$holiday) {
        $response['can_check_in'] = true;
    }
}

// Check if can check out
if ($attendance['check_in'] && !$attendance['check_out']) {
    // Must wait at least 1 hour after check-in to check out
    $check_in_time = strtotime($attendance['check_in']);
    $current_time = time();
    $hours_worked = ($current_time - $check_in_time) / 3600;
    
    if ($hours_worked >= 1) {
        $response['can_check_out'] = true;
    } else {
        $response['check_out_wait'] = ceil(60 - ($hours_worked * 60)); // Minutes to wait
    }
}

// Calculate if late
if ($attendance['check_in']) {
    $check_in = strtotime($attendance['check_in']);
    $start_time = strtotime($working_start);
    
    if ($check_in > $start_time) {
        $late_minutes = round(($check_in - $start_time) / 60);
        $response['is_late'] = true;
        $response['late_minutes'] = $late_minutes;
    }
}

echo json_encode($response);
?>