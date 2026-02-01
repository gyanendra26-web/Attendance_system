<?php
/**
 * Session Management
 */
session_start();

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Set session timeout (30 minutes)
$timeout = 1800; // 30 minutes in seconds

// Check if session exists and if last request was more than timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Last request was more than 30 minutes ago
    session_unset();
    session_destroy();
    header("Location: ../login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['LAST_ACTIVITY'] = time();

// Regenerate session ID every 5 minutes to prevent session fixation
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 300) {
    // Session started more than 5 minutes ago
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// Store IP address for security
if (!isset($_SESSION['IP_ADDRESS'])) {
    $_SESSION['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
}

// Verify IP address hasn't changed (basic protection against session hijacking)
if ($_SESSION['IP_ADDRESS'] !== $_SERVER['REMOTE_ADDR']) {
    // IP changed - destroy session for security
    session_unset();
    session_destroy();
    header("Location: ../login.php?error=session_hijack");
    exit();
}

// Store user agent for additional security
if (!isset($_SESSION['USER_AGENT'])) {
    $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
}

// Verify user agent hasn't changed significantly
if ($_SESSION['USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
    // User agent changed - could be session hijacking
    session_unset();
    session_destroy();
    header("Location: ../login.php?error=session_hijack");
    exit();
}

/**
 * Destroy session completely
 */
function destroySession() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Flash message system for one-time notifications
 */
function flash($name = '', $message = '', $class = 'alert alert-success') {
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            if (!empty($_SESSION[$name])) {
                unset($_SESSION[$name]);
            }
            if (!empty($_SESSION[$name . '_class'])) {
                unset($_SESSION[$name . '_class']);
            }
            
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

/**
 * Set session message
 */
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Get and clear session message
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    // Define permissions based on role
    $permissions = [
        'admin' => [
            'manage_employees',
            'view_reports',
            'approve_leaves',
            'manage_holidays',
            'system_settings'
        ],
        'employee' => [
            'check_in_out',
            'view_own_attendance',
            'apply_leave',
            'view_profile'
        ]
    ];
    
    $role = $_SESSION['role'];
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}
?>