<?php
/**
 * Authentication Functions
 */
require_once 'config/database.php';
require_once 'functions.php';

/**
 * Check if user is authenticated
 */
function checkAuth() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['employee_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

/**
 * Redirect based on role
 */
function redirectByRole() {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif (isset($_SESSION['employee_id'])) {
        header("Location: employee/dashboard.php");
        exit();
    }
}

/**
 * Check if user can access admin area
 */
function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
        header("Location: ../login.php?error=access_denied");
        exit();
    }
}

/**
 * Check if user can access employee area
 */
function requireEmployee() {
    if (!isset($_SESSION['employee_id'])) {
        header("Location: ../login.php?error=access_denied");
        exit();
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log login attempt
 */
function logLoginAttempt($username, $success, $ip_address = null) {
    global $conn;
    
    $ip_address = $ip_address ?: $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $timestamp = date('Y-m-d H:i:s');
    
    $log_data = [
        'username' => $username,
        'success' => $success ? 1 : 0,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'timestamp' => $timestamp
    ];
    
    // You can store this in a login_logs table if needed
    // For now, we'll just write to a log file
    $log_message = sprintf(
        "[%s] Login attempt - Username: %s, Success: %s, IP: %s, Agent: %s\n",
        $timestamp,
        $username,
        $success ? 'Yes' : 'No',
        $ip_address,
        $user_agent
    );
    
    file_put_contents('logs/login_attempts.log', $log_message, FILE_APPEND);
}
?>