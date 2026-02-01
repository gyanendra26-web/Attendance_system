<?php
/**
 * System Constants
 */

// Application Information
define('APP_NAME', 'Employee Attendance Management System');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Company Name');
define('APP_YEAR', date('Y'));

// System Paths
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('LOG_PATH', ROOT_PATH . '/logs/');

// File Upload Settings
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Attendance Settings
define('DEFAULT_WORKING_HOURS', 8); // 8 hours per day
define('HALF_DAY_HOURS', 6); // Less than 6 hours = half day
define('LATE_THRESHOLD', 15); // 15 minutes late allowed
define('OVERTIME_RATE', 1.5); // 1.5x normal rate

// Leave Settings
define('MAX_CASUAL_LEAVE', 15); // 15 days per year
define('MAX_SICK_LEAVE', 10); // 10 days per year
define('MAX_LEAVE_CONTINUOUS', 30); // Max 30 days continuous leave

// Nepali Specific Constants
define('NEPAL_TIMEZONE', 'Asia/Kathmandu');
define('NEPAL_COUNTRY_CODE', '+977');
define('NEPAL_CURRENCY', 'NPR');
define('NEPAL_DATE_FORMAT', 'Y-m-d');

// Nepali Festival Colors
define('FESTIVAL_COLORS', [
    'dashain' => '#FF6B6B',
    'tihar' => '#4ECDC4',
    'holi' => '#FFD166',
    'shivaratri' => '#118AB2',
    'buddha_jayanti' => '#06D6A0',
    'christmas' => '#EF476F'
]);

// Departments (Common in Nepal)
define('DEPARTMENTS', [
    'IT' => 'Information Technology',
    'HR' => 'Human Resources',
    'FINANCE' => 'Finance & Accounts',
    'MARKETING' => 'Marketing',
    'SALES' => 'Sales',
    'OPERATIONS' => 'Operations',
    'ADMIN' => 'Administration',
    'SUPPORT' => 'Customer Support',
    'MANAGEMENT' => 'Management'
]);

// Positions
define('POSITIONS', [
    'MANAGER' => 'Manager',
    'SUPERVISOR' => 'Supervisor',
    'TEAM_LEAD' => 'Team Lead',
    'SENIOR' => 'Senior',
    'JUNIOR' => 'Junior',
    'INTERN' => 'Intern',
    'TRAINEE' => 'Trainee'
]);

// Leave Types
define('LEAVE_TYPES', [
    'CASUAL' => 'Casual Leave',
    'SICK' => 'Sick Leave',
    'PAID' => 'Paid Leave',
    'UNPAID' => 'Unpaid Leave',
    'MATERNITY' => 'Maternity Leave',
    'PATERNITY' => 'Paternity Leave'
]);

// Attendance Status
define('ATTENDANCE_STATUS', [
    'PRESENT' => 'present',
    'ABSENT' => 'absent',
    'LEAVE' => 'leave',
    'HOLIDAY' => 'holiday',
    'HALF_DAY' => 'half_day',
    'OVERTIME' => 'overtime'
]);

// Holiday Types
define('HOLIDAY_TYPES', [
    'NATIONAL' => 'national',
    'FESTIVAL' => 'festival',
    'WEEKLY' => 'weekly',
    'GOVERNMENT' => 'government',
    'LOCAL' => 'local'
]);

// User Roles
define('USER_ROLES', [
    'ADMIN' => 'admin',
    'EMPLOYEE' => 'employee',
    'SUPERVISOR' => 'supervisor',
    'HR' => 'hr'
]);

// Session Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('SESSION_REGENERATE', 300); // Regenerate ID every 5 minutes

// Security Settings
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 6);
define('LOGIN_ATTEMPTS', 5); // Max login attempts before lockout
define('LOCKOUT_TIME', 900); // 15 minutes lockout

// Email Settings
define('EMAIL_FROM', 'noreply@company.com');
define('EMAIL_FROM_NAME', APP_NAME);
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_SECURE', 'tls');

// Report Settings
define('REPORT_DAYS', 30); // Default report period
define('EXPORT_MAX_ROWS', 10000); // Max rows for export

// Notification Settings
define('NOTIFICATION_EXPIRE', 7); // Days to keep notifications

// Color Scheme
define('COLOR_PRIMARY', '#3498db');
define('COLOR_SECONDARY', '#2c3e50');
define('COLOR_SUCCESS', '#27ae60');
define('COLOR_WARNING', '#f39c12');
define('COLOR_DANGER', '#e74c3c');
define('COLOR_INFO', '#3498db');

// Response Codes
define('RESPONSE_SUCCESS', 200);
define('RESPONSE_CREATED', 201);
define('RESPONSE_BAD_REQUEST', 400);
define('RESPONSE_UNAUTHORIZED', 401);
define('RESPONSE_FORBIDDEN', 403);
define('RESPONSE_NOT_FOUND', 404);
define('RESPONSE_SERVER_ERROR', 500);

// Database Constants
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// System Messages
define('MESSAGES', [
    'LOGIN_SUCCESS' => 'Login successful!',
    'LOGIN_FAILED' => 'Invalid credentials!',
    'REGISTER_SUCCESS' => 'Registration successful!',
    'UPDATE_SUCCESS' => 'Update successful!',
    'DELETE_SUCCESS' => 'Deleted successfully!',
    'ATTENDANCE_MARKED' => 'Attendance marked successfully!',
    'LEAVE_APPLIED' => 'Leave application submitted!',
    'HOLIDAY_ADDED' => 'Holiday added successfully!',
    'SETTINGS_SAVED' => 'Settings saved successfully!'
]);

// Error Messages
define('ERRORS', [
    'REQUIRED_FIELD' => 'This field is required',
    'INVALID_EMAIL' => 'Please enter a valid email',
    'INVALID_PHONE' => 'Please enter a valid phone number',
    'PASSWORD_MISMATCH' => 'Passwords do not match',
    'PASSWORD_WEAK' => 'Password is too weak',
    'DUPLICATE_ENTRY' => 'This record already exists',
    'FILE_UPLOAD' => 'Error uploading file',
    'DATABASE_ERROR' => 'Database error occurred',
    'SESSION_EXPIRED' => 'Your session has expired',
    'ACCESS_DENIED' => 'Access denied'
]);

// Week Days
define('WEEK_DAYS', [
    0 => 'Sunday',
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday'
]);

// Nepali Month Names
define('NEPALI_MONTHS', [
    1 => 'Baishakh',
    2 => 'Jestha',
    3 => 'Ashad',
    4 => 'Shrawan',
    5 => 'Bhadra',
    6 => 'Ashwin',
    7 => 'Kartik',
    8 => 'Mangsir',
    9 => 'Poush',
    10 => 'Magh',
    11 => 'Falgun',
    12 => 'Chaitra'
]);

// System Requirements
define('PHP_VERSION_REQUIRED', '7.4.0');
define('MYSQL_VERSION_REQUIRED', '5.7.0');
define('REQUIRED_EXTENSIONS', ['mysqli', 'pdo', 'session', 'json', 'mbstring']);

// API Endpoints
define('API_ENDPOINTS', [
    'ATTENDANCE' => '/api/attendance',
    'LEAVE' => '/api/leave',
    'HOLIDAY' => '/api/holiday',
    'EMPLOYEE' => '/api/employee',
    'REPORT' => '/api/report',
    'NOTIFICATION' => '/api/notification'
]);

// Log Types
define('LOG_TYPES', [
    'LOGIN' => 'login',
    'ATTENDANCE' => 'attendance',
    'LEAVE' => 'leave',
    'SYSTEM' => 'system',
    'ERROR' => 'error',
    'SECURITY' => 'security'
]);

// Export Formats
define('EXPORT_FORMATS', [
    'CSV' => 'csv',
    'PDF' => 'pdf',
    'EXCEL' => 'excel',
    'JSON' => 'json'
]);

// Cache Settings
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600); // 1 hour

// Debug Mode
define('DEBUG_MODE', true);

// Maintenance Mode
define('MAINTENANCE_MODE', false);

// Add these constants to session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['app_constants'] = [
    'app_name' => APP_NAME,
    'base_url' => BASE_URL,
    'timezone' => NEPAL_TIMEZONE
];
?>