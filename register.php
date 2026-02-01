<?php
/**
 * Employee Registration (Admin Only)
 */
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/validation.php';

// Only admin can access this page
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $message = '<div class="alert error">Security token invalid. Please try again.</div>';
    } else {
        // Get form data
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $department = sanitize($_POST['department']);
        $position = sanitize($_POST['position']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation rules
        $validation_rules = [
            'name' => ['required' => true, 'min_length' => 2],
            'email' => ['required' => true, 'email' => true],
            'phone' => ['required' => true, 'phone' => true],
            'department' => ['required' => true],
            'position' => ['required' => true],
            'password' => ['required' => true, 'min_length' => 6]
        ];
        
        $validation_errors = validateForm($_POST, $validation_rules);
        
        if (!empty($validation_errors)) {
            $errors = $validation_errors;
        } elseif ($password !== $confirm_password) {
            $errors['password'] = "Passwords do not match";
        } else {
            // Check if email already exists
            $query = "SELECT id FROM employees WHERE email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors['email'] = "Email already registered";
            } else {
                // Generate unique employee ID
                $year = date('Y');
                $query = "SELECT MAX(employee_id) as last_id FROM employees WHERE employee_id LIKE 'EMP{$year}%'";
                $result = mysqli_query($conn, $query);
                $row = mysqli_fetch_assoc($result);
                
                if ($row['last_id']) {
                    $last_number = intval(substr($row['last_id'], -3));
                    $new_number = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $new_number = '001';
                }
                
                $employee_id = 'EMP' . $year . $new_number;
                
                // Hash password
                $hashed_password = hashPassword($password);
                
                // Insert employee
                $query = "INSERT INTO employees (employee_id, name, email, phone, department, position, password) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssssss", 
                    $employee_id, $name, $email, $phone, $department, $position, $hashed_password);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = '<div class="alert success">Employee registered successfully! Employee ID: ' . $employee_id . '</div>';
                    
                    // Clear form
                    $_POST = [];
                } else {
                    $message = '<div class="alert error">Error registering employee: ' . mysqli_error($conn) . '</div>';
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Get departments for dropdown
$departments = ['IT', 'HR', 'Finance', 'Marketing', 'Sales', 'Operations', 'Admin', 'Support', 'Management'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Employee</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: #7f8c8d;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .form-section h3 {
            color: #3498db;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section h3::before {
            content: '✓';
            background: #3498db;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #27ae60; }
        
        .employee-id-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Register New Employee</h1>
            <p>Add a new employee to the attendance system</p>
        </div>
        
        <?php echo $message; ?>
        
        <div class="form-container">
            <div class="employee-id-display" id="employeeIdDisplay">
                Employee ID will be generated automatically
            </div>
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <!-- Personal Information -->
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required 
                                   value="<?php echo $_POST['name'] ?? ''; ?>"
                                   placeholder="Enter employee's full name">
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-text"><?php echo $errors['name']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required 
                                   value="<?php echo $_POST['email'] ?? ''; ?>"
                                   placeholder="employee@company.com">
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-text"><?php echo $errors['email']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="text" name="phone" required 
                                   value="<?php echo $_POST['phone'] ?? ''; ?>"
                                   placeholder="98XXXXXXXX">
                            <small class="form-text">Nepali format: 98XXXXXXXX</small>
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-text"><?php echo $errors['phone']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Employment Details -->
                <div class="form-section">
                    <h3>Employment Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Department *</label>
                            <select name="department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" 
                                    <?php echo ($_POST['department'] ?? '') == $dept ? 'selected' : ''; ?>>
                                    <?php echo $dept; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['department'])): ?>
                                <span class="error-text"><?php echo $errors['department']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Position/Designation *</label>
                            <input type="text" name="position" required 
                                   value="<?php echo $_POST['position'] ?? ''; ?>"
                                   placeholder="e.g., Software Developer">
                            <?php if (isset($errors['position'])): ?>
                                <span class="error-text"><?php echo $errors['position']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Account Security -->
                <div class="form-section">
                    <h3>Account Security</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required 
                                   id="password" placeholder="Minimum 6 characters"
                                   oninput="checkPasswordStrength()">
                            <div class="password-strength" id="passwordStrength"></div>
                            <?php if (isset($errors['password'])): ?>
                                <span class="error-text"><?php echo $errors['password']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <input type="password" name="confirm_password" required 
                                   id="confirm_password" placeholder="Re-enter password"
                                   oninput="checkPasswordMatch()">
                            <div class="password-strength" id="passwordMatch"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">Register Employee</button>
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                </div>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin/dashboard.php" class="back-link">← Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (!password) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let tips = [];
            
            // Check password length
            if (password.length >= 8) strength++;
            else tips.push("Make it at least 8 characters");
            
            // Check for mixed case
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            else tips.push("Use both lowercase and uppercase letters");
            
            // Check for numbers
            if (/\d/.test(password)) strength++;
            else tips.push("Include at least one number");
            
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else tips.push("Include at least one special character");
            
            // Display result
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 2) {
                strengthText = 'Weak';
                strengthClass = 'strength-weak';
            } else if (strength === 2 || strength === 3) {
                strengthText = 'Medium';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = `<span class="${strengthClass}">Strength: ${strengthText}</span>`;
            if (tips.length > 0 && strength < 4) {
                strengthDiv.innerHTML += `<br><small>${tips[0]}</small>`;
            }
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (!confirmPassword) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<span class="strength-strong">✓ Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<span class="strength-weak">✗ Passwords do not match</span>';
            }
        }
        
        // Generate preview employee ID
        function generateEmployeeId() {
            const year = new Date().getFullYear();
            const randomNum = Math.floor(Math.random() * 900) + 100;
            return `EMP${year}${randomNum}`;
        }
        
        // Update employee ID display
        document.getElementById('registerForm').addEventListener('input', function() {
            const name = document.querySelector('input[name="name"]').value;
            const department = document.querySelector('select[name="department"]').value;
            
            if (name && department) {
                const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();
                const year = new Date().getFullYear();
                const deptCode = department.substring(0, 3).toUpperCase();
                document.getElementById('employeeIdDisplay').textContent = 
                    `Preview: ${deptCode}${year}${initials}XXX`;
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>