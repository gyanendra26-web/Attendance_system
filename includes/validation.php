<?php
/**
 * Input Validation Functions
 */
require_once 'config/database.php';

/**
 * Validate email address
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Check email domain (optional)
    $allowed_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'company.com'];
    $email_parts = explode('@', $email);
    $domain = strtolower(end($email_parts));
    
    // You can remove this check if you want to allow all domains
    // return in_array($domain, $allowed_domains);
    
    return true; // Allow all valid email domains
}

/**
 * Validate phone number (Nepali format)
 */
function validatePhone($phone) {
    // Nepali phone number format: 98XXXXXXXX or +97798XXXXXXXX
    $pattern = '/^(?:\+?977)?98[0-9]{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * Validate Nepali date
 */
function validateNepaliDate($date) {
    $pattern = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
    if (!preg_match($pattern, $date)) {
        return false;
    }
    
    // Check if it's a valid date
    list($year, $month, $day) = explode('-', $date);
    
    // Basic validation
    if ($year < 2000 || $year > 2100) return false;
    if ($month < 1 || $month > 12) return false;
    if ($day < 1 || $day > 31) return false;
    
    // Check for specific month-day combinations
    $days_in_month = [
        1 => 31, 2 => 29, 3 => 31, 4 => 30, 5 => 31, 6 => 30,
        7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31
    ];
    
    if ($day > $days_in_month[(int)$month]) {
        return false;
    }
    
    return true;
}

/**
 * Validate time
 */
function validateTime($time) {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/';
    return preg_match($pattern, $password);
}

/**
 * Sanitize string input
 */
function sanitizeString($input) {
    global $conn;
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    $input = stripslashes($input);
    return mysqli_real_escape_string($conn, trim($input));
}

/**
 * Sanitize integer input
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float input
 */
function sanitizeFloat($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 2097152) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error: " . $file['error'];
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = "File size exceeds maximum allowed size of " . ($max_size / 1024 / 1024) . "MB";
    }
    
    // Check file extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    if (!in_array($mime, $allowed_mimes)) {
        $errors[] = "Invalid file type detected";
    }
    
    return $errors;
}

/**
 * Validate leave dates
 */
function validateLeaveDates($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $today = new DateTime();
    
    // Start date cannot be in the past
    if ($start < $today) {
        return "Start date cannot be in the past";
    }
    
    // End date cannot be before start date
    if ($end < $start) {
        return "End date cannot be before start date";
    }
    
    // Leave cannot be more than 30 days
    $interval = $start->diff($end);
    if ($interval->days > 30) {
        return "Leave cannot exceed 30 days";
    }
    
    return true;
}

/**
 * Validate check-in/check-out times
 */
function validateCheckTimes($check_in, $check_out = null) {
    $check_in_time = strtotime($check_in);
    $current_time = time();
    
    // Check-in cannot be in the future (allow 5 minutes buffer for clock differences)
    if ($check_in_time > ($current_time + 300)) {
        return "Check-in time cannot be in the future";
    }
    
    // Check-out cannot be before check-in
    if ($check_out) {
        $check_out_time = strtotime($check_out);
        if ($check_out_time <= $check_in_time) {
            return "Check-out time must be after check-in time";
        }
        
        // Check-out cannot be in the future
        if ($check_out_time > ($current_time + 300)) {
            return "Check-out time cannot be in the future";
        }
    }
    
    return true;
}

/**
 * Validate employee ID format
 */
function validateEmployeeID($employee_id) {
    // Format: EMPYYYYNNN where YYYY is year, NNN is serial
    $pattern = '/^EMP[0-9]{4}[0-9]{3}$/';
    return preg_match($pattern, $employee_id);
}

/**
 * Validate department name
 */
function validateDepartment($department) {
    $allowed_departments = [
        'IT', 'HR', 'Finance', 'Marketing', 'Sales', 
        'Operations', 'Admin', 'Support', 'Management'
    ];
    
    return in_array($department, $allowed_departments);
}

/**
 * Get validation error message
 */
function getValidationError($field, $rule) {
    $messages = [
        'email' => [
            'required' => 'Email is required',
            'invalid' => 'Please enter a valid email address'
        ],
        'phone' => [
            'required' => 'Phone number is required',
            'invalid' => 'Please enter a valid Nepali phone number (98XXXXXXXX)'
        ],
        'password' => [
            'required' => 'Password is required',
            'weak' => 'Password must be at least 8 characters with uppercase, lowercase, and number'
        ],
        'date' => [
            'required' => 'Date is required',
            'invalid' => 'Please enter a valid date (YYYY-MM-DD)'
        ],
        'time' => [
            'required' => 'Time is required',
            'invalid' => 'Please enter a valid time (HH:MM)'
        ]
    ];
    
    return $messages[$field][$rule] ?? 'Validation error';
}

/**
 * Validate all form data
 */
function validateForm($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $field_rules) {
        $value = $data[$field] ?? '';
        
        foreach ($field_rules as $rule => $rule_value) {
            switch ($rule) {
                case 'required':
                    if ($rule_value && empty($value)) {
                        $errors[$field] = getValidationError($field, 'required');
                    }
                    break;
                    
                case 'email':
                    if (!empty($value) && !validateEmail($value)) {
                        $errors[$field] = getValidationError($field, 'invalid');
                    }
                    break;
                    
                case 'phone':
                    if (!empty($value) && !validatePhone($value)) {
                        $errors[$field] = getValidationError($field, 'invalid');
                    }
                    break;
                    
                case 'password':
                    if (!empty($value) && !validatePassword($value)) {
                        $errors[$field] = getValidationError($field, 'weak');
                    }
                    break;
                    
                case 'min_length':
                    if (strlen($value) < $rule_value) {
                        $errors[$field] = "Must be at least $rule_value characters";
                    }
                    break;
                    
                case 'max_length':
                    if (strlen($value) > $rule_value) {
                        $errors[$field] = "Cannot exceed $rule_value characters";
                    }
                    break;
                    
                case 'date':
                    if (!empty($value) && !validateNepaliDate($value)) {
                        $errors[$field] = getValidationError($field, 'invalid');
                    }
                    break;
                    
                case 'time':
                    if (!empty($value) && !validateTime($value)) {
                        $errors[$field] = getValidationError($field, 'invalid');
                    }
                    break;
            }
        }
    }
    
    return $errors;
}
?>